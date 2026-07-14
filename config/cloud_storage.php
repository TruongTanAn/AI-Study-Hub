<?php
/**
 * AI Study Hub - Cloud Storage Abstraction
 *
 * Cung cap mot lop wrapper thong nhat cho nhieu backend luu tru.
 * Hien tai chi ho tro Supabase Storage (REST API).
 *
 * Moi file se duoc upload truc tiep len Supabase Storage (KHONG qua uploads/ local).
 * Sau khi upload thanh cong se nhan ve Public URL:
 *
 *   {SUPABASE_URL}/storage/v1/object/public/{BUCKET}/{objectPath}
 *
 * Su dung:
 *   $result = CloudStorage::upload($fileTmpPath, $fileName, $userId);
 *   $result = CloudStorage::delete($filePathOrUrl);
 *   $url    = CloudStorage::getPublicUrl($objectPath);
 *   $ok     = CloudStorage::isSupabaseUrl($pathOrUrl);
 */

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

require_once __DIR__ . '/supabase.php';

class CloudStorage
{
    /** Provider hien tai - chi ho tro Supabase */
    private static $config = [
        'provider'   => 'supabase',
        'max_size'   => 50 * 1024 * 1024, // 50 MB
        'tmp_dir'    => null,
    ];

    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Upload file truc tiep tu tmp path cua PHP den Supabase Storage.
     *
     * @param string $fileTmpPath  Duong dan file tam (vd: $_FILES['x']['tmp_name'])
     * @param string $filename     Ten file da duoc secure hoa (se ghep them prefix user)
     * @param int|null $userId     ID user (de to_object_path)
     * @param string $folder       Ten thu muc ao trong bucket (mac dinh 'documents')
     * @return array{success:bool, url?:string, path?:string, object_path?:string, error?:string}
     */
    public static function upload($fileTmpPath, $filename, $userId = null, $folder = 'documents')
    {
        if (!is_string($fileTmpPath) || $fileTmpPath === '') {
            return ['success' => false, 'error' => 'Duong dan file tam khong hop le'];
        }
        if (!file_exists($fileTmpPath)) {
            return ['success' => false, 'error' => 'File tam khong ton tai tren server'];
        }

        $objectPath = self::buildObjectPath((string) $filename, $userId, $folder);
        $result = self::uploadToSupabase($fileTmpPath, $objectPath);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success'     => true,
            'url'         => $result['url'],
            'path'        => $result['url'], // tuong thich nguoc: 'path' gio la URL public
            'object_path' => $objectPath,
        ];
    }

    /**
     * Upload noi dung bytes (khong qua file tam) len Supabase Storage.
     */
    public static function uploadBytes(string $bytes, string $objectPath, string $contentType = 'application/octet-stream'): array
    {
        if ($bytes === '') {
            return ['success' => false, 'error' => 'Noi dung file rong'];
        }

        $endpoint = supabase_storage_base_url()
            . '/object/'
            . rawurlencode(SUPABASE_BUCKET_NAME)
            . '/'
            . self::encodeObjectPath($objectPath);

        $tmpFile = @tempnam(sys_get_temp_dir(), 'sup_');
        if ($tmpFile === false) {
            return ['success' => false, 'error' => 'Khong the tao file tam de upload'];
        }
        if (@file_put_contents($tmpFile, $bytes) === false) {
            @unlink($tmpFile);
            return ['success' => false, 'error' => 'Khong the ghi vao file tam'];
        }

        $result = self::uploadToSupabase($tmpFile, $objectPath, $contentType);

        @unlink($tmpFile);
        return $result;
    }

    /**
     * Upload file tu tmp path toi Supabase Storage (goi REST API).
     *
     * LUON doc noi dung file bang file_get_contents() va truyen qua
     * CURLOPT_POSTFIELDS dang binary (string) - KHONG truyen resource
     * vi se bi PHP stringify thanh "Resource id #X" lam hong file tren bucket.
     */
    private static function uploadToSupabase(string $fileTmpPath, string $objectPath, ?string $contentType = null): array
    {
        if (!function_exists('curl_init')) {
            supabase_log('upload_error', 'curl extension khong kha dung', ['object' => $objectPath]);
            return ['success' => false, 'error' => 'Server thieu curl extension'];
        }

        $endpoint = supabase_storage_base_url()
            . '/object/'
            . rawurlencode(SUPABASE_BUCKET_NAME)
            . '/'
            . self::encodeObjectPath($objectPath);

        if (!is_string($fileTmpPath) || $fileTmpPath === '' || !file_exists($fileTmpPath)) {
            supabase_log('upload_error', 'File tam khong ton tai', ['file' => $fileTmpPath]);
            return ['success' => false, 'error' => 'File tam khong ton tai tren server'];
        }

        $size = filesize($fileTmpPath);
        if ($size === false || $size <= 0) {
            return ['success' => false, 'error' => 'File rong hoac khong the xac dinh kich thuoc'];
        }

        if ($size > self::$config['max_size']) {
            return ['success' => false, 'error' => 'File vuot qua dung luong toi da (50MB)'];
        }

        // Doc toan bo noi dung file thanh binary string (KHONG dung resource)
        $binary = @file_get_contents($fileTmpPath);
        if ($binary === false || $binary === '') {
            supabase_log('upload_error', 'Khong the doc noi dung file tam', ['file' => $fileTmpPath]);
            return ['success' => false, 'error' => 'Khong the doc noi dung file tam'];
        }

        // Kiem tra mot lan nua: so byte da doc phai bang filesize
        if (strlen($binary) !== $size) {
            supabase_log('upload_error', 'So byte doc duoc khong khop voi filesize', [
                'file'         => $fileTmpPath,
                'filesize'     => $size,
                'bytes_read'   => strlen($binary),
            ]);
            return ['success' => false, 'error' => 'Khong the doc du noi dung file (size khong khop)'];
        }

        // Tu choi neu chi nhan duoc "Resource id #X" (bug cu da gay ra file hong tren bucket)
        if (preg_match('/^Resource id #\d+$/', trim($binary)) === 1) {
            supabase_log('upload_error', 'Phat hien noi dung file tam bi serialize thanh PHP resource', [
                'file' => $fileTmpPath,
            ]);
            return ['success' => false, 'error' => 'File tam upload len Supabase bi loi (resource string). Vui long thu lai.'];
        }

        $mime = $contentType !== null && $contentType !== ''
            ? $contentType
            : self::detectMimeFromContent($binary, $fileTmpPath);

        $headers = supabase_auth_headers();
        $headers[] = 'Content-Type: ' . $mime;
        $headers[] = 'x-upsert: false';
        $headers[] = 'Content-Length: ' . strlen($binary);

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return ['success' => false, 'error' => 'Khong the khoi tao curl'];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        // QUAN TRONG: truyen binary string, KHONG truyen resource stream
        curl_setopt($ch, CURLOPT_POSTFIELDS, $binary);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $responseBody = curl_exec($ch);
        $curlErrno    = curl_errno($ch);
        $curlError    = curl_error($ch);
        $httpStatus   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Giai phong binary ngay
        unset($binary);

        if ($curlErrno !== 0) {
            supabase_log('upload_error', 'curl error', [
                'errno'       => $curlErrno,
                'curl_error'  => $curlError,
                'object'      => $objectPath,
            ]);
            return ['success' => false, 'error' => 'Loi ket noi toi Supabase Storage: ' . $curlError];
        }

        if ($httpStatus >= 200 && $httpStatus < 300) {
            $publicUrl = self::getPublicUrl($objectPath);
            return [
                'success' => true,
                'url'     => $publicUrl,
            ];
        }

        $errText = self::extractErrorMessage((string) $responseBody, $httpStatus);
        supabase_log('upload_error', 'Supabase tu choi upload', [
            'http_status' => $httpStatus,
            'object'      => $objectPath,
            'response'    => mb_substr((string) $responseBody, 0, 500),
        ]);

        return ['success' => false, 'error' => $errText];
    }

    /**
     * Xoa 1 object khoi Supabase Storage.
     * Co the truyen vao:
     *   - Public URL day du (https://...supabase.co/storage/v1/object/public/documents/...)
     *   - Object path (user-uploads/user_1_xxx.pdf)
     *   - Duong dan local cu (/uploads/documents/xxx.pdf) -> se bo qua an toan
     *
     * @return bool true neu xoa thanh cong hoac khong ton tai; false neu loi that bai
     */
    public static function delete($pathOrUrl): bool
    {
        if (!is_string($pathOrUrl) || $pathOrUrl === '') {
            return false;
        }

        // Duong dan local cu -> khong can xoa, chi don dep uploads/
        if (self::isLocalUploadPath($pathOrUrl)) {
            $localAbs = self::toAbsoluteLocalPath($pathOrUrl);
            if ($localAbs !== null && is_file($localAbs)) {
                @unlink($localAbs);
            }
            return true;
        }

        $objectPath = self::pathToObjectPath($pathOrUrl);
        if ($objectPath === null || $objectPath === '') {
            return false;
        }

        return self::deleteObjectFromSupabase($objectPath);
    }

    /**
     * Xoa 1 object truc tiep tren Supabase Storage.
     */
    private static function deleteObjectFromSupabase(string $objectPath): bool
    {
        if (!function_exists('curl_init')) {
            supabase_log('delete_error', 'curl extension khong kha dung', ['object' => $objectPath]);
            return false;
        }

        $endpoint = supabase_storage_base_url()
            . '/object/'
            . rawurlencode(SUPABASE_BUCKET_NAME)
            . '/'
            . self::encodeObjectPath($objectPath);

        $headers = supabase_auth_headers();
        $headers[] = 'Content-Type: application/json';

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $responseBody = curl_exec($ch);
        $curlErrno    = curl_errno($ch);
        $curlError    = curl_error($ch);
        $httpStatus   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0) {
            supabase_log('delete_error', 'curl error', [
                'errno'      => $curlErrno,
                'curl_error' => $curlError,
                'object'     => $objectPath,
            ]);
            return false;
        }

        // 200, 204 (OK), 404 (not found - coi nhu thanh cong) deu la OK
        if ($httpStatus === 200 || $httpStatus === 204 || $httpStatus === 404) {
            return true;
        }

        supabase_log('delete_error', 'Supabase tu choi xoa', [
            'http_status' => $httpStatus,
            'object'      => $objectPath,
            'response'    => mb_substr((string) $responseBody, 0, 500),
        ]);
        return false;
    }

    /**
     * Lay Public URL cho 1 object path.
     */
    public static function getPublicUrl(string $objectPath): string
    {
        $objectPath = ltrim($objectPath, '/');
        return rtrim(SUPABASE_URL, '/')
            . '/storage/v1/object/public/'
            . rawurlencode(SUPABASE_BUCKET_NAME)
            . '/'
            . self::encodeObjectPath($objectPath);
    }

    /**
     * Kiem tra mot chuoi co phai la Supabase Storage URL hop le.
     */
    public static function isSupabaseUrl(?string $pathOrUrl): bool
    {
        if (!is_string($pathOrUrl) || $pathOrUrl === '') {
            return false;
        }
        $prefix = rtrim(SUPABASE_URL, '/') . '/storage/v1/';
        return stripos($pathOrUrl, $prefix) === 0;
    }

    /**
     * Tu URL/public path, trich ra object path trong bucket.
     * Tra ve null neu khong phai Supabase URL.
     */
    public static function pathToObjectPath(string $pathOrUrl): ?string
    {
        if (!is_string($pathOrUrl) || $pathOrUrl === '') {
            return null;
        }

        $url = $pathOrUrl;

        if (($qPos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $qPos);
        }

        $base = rtrim(SUPABASE_URL, '/') . '/storage/v1';

        // Kiem tra co phai Supabase URL (bat ky prefix nao)
        if (stripos($url, $base . '/') !== 0 && stripos($url, rtrim(SUPABASE_URL, '/')) !== 0) {
            return null;
        }

        $suffixes = [
            '/storage/v1/object/public/' . SUPABASE_BUCKET_NAME . '/',
            '/storage/v1/object/public/' . rawurlencode(SUPABASE_BUCKET_NAME) . '/',
            '/storage/v1/object/' . SUPABASE_BUCKET_NAME . '/',
            '/storage/v1/object/' . rawurlencode(SUPABASE_BUCKET_NAME) . '/',
            '/storage/v1/object/sign/' . SUPABASE_BUCKET_NAME . '/',
            '/storage/v1/render/image/sign/' . SUPABASE_BUCKET_NAME . '/',
        ];

        foreach ($suffixes as $suf) {
            $pos = strripos($url, $suf);
            if ($pos !== false) {
                $rel = substr($url, $pos + strlen($suf));
                $rel = rawurldecode($rel);
                return ltrim($rel, '/');
            }
        }

        return null;
    }

    /**
     * Upload file truc tiep tu local path (giu lai de tuong thich nguoc).
     * Luu y: hien tai tro ve loi vi provider da chuyen sang Supabase.
     */
    public static function uploadToLocal($fileTmpName, $filename, $folder = 'documents')
    {
        return self::upload($fileTmpName, $filename, null, $folder);
    }

    /* ============================================================
       Helpers noi bo
       ============================================================ */

    /**
     * Build object path trong bucket.
     * Dinh dang: user-uploads/user_<id>_<random>_xxx.pdf
     */
    private static function buildObjectPath(string $filename, $userId, string $folder): string
    {
        $filename = ltrim((string) $filename, '/');
        if ($filename === '') {
            $filename = 'file_' . uniqid('', true);
        }

        $prefix = SUPABASE_UPLOAD_PREFIX;
        $parts = [$prefix];

        if ($userId !== null && $userId !== '') {
            $parts[] = 'user_' . preg_replace('/[^0-9]/', '', (string) $userId);
        } elseif ($folder !== '' && $folder !== 'documents') {
            $cleanFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $folder);
            if ($cleanFolder !== '') {
                $parts[] = $cleanFolder;
            }
        }

        $parts[] = $filename;
        return implode('/', $parts);
    }

    /**
     * Ma hoa object path: giu nguyen cac segment nhu dung %2F cho dau /
     * (Supabase REST yeu cau ca path cha va object deu encoded)
     */
    private static function encodeObjectPath(string $objectPath): string
    {
        $objectPath = ltrim($objectPath, '/');
        return implode('/', array_map('rawurlencode', explode('/', $objectPath)));
    }

    /**
     * Detect mime type bang finfo (uu tien) hoac fallback theo extension.
     */
    private static function detectMime(string $filePath): string
    {
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $filePath);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
                finfo_close($finfo);
            }
        }
        if ($mime === '' || $mime === 'application/octet-stream') {
            $mime = self::detectMimeByExtension($filePath);
        }
        return $mime;
    }

    /**
     * Detect mime type tu noi dung binary (finfo_buffer) + fallback theo extension.
     * Dung khi da co binary trong memory (tranh doc file lan 2).
     */
    private static function detectMimeFromContent(string $binary, string $filePath = ''): string
    {
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_buffer($finfo, $binary);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
                finfo_close($finfo);
            }
        }
        if ($mime === '' || $mime === 'application/octet-stream') {
            $mime = self::detectMimeByExtension($filePath);
        }
        return $mime;
    }

    /**
     * Map extension (pdf/docx/pptx) -> mime chinh xac.
     */
    private static function detectMimeByExtension(string $filePath): string
    {
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'pdf':
                return 'application/pdf';
            case 'docx':
                return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            case 'pptx':
                return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            case 'doc':
                return 'application/msword';
            case 'ppt':
                return 'application/vnd.ms-powerpoint';
            case 'txt':
                return 'text/plain';
            case 'md':
            case 'markdown':
                return 'text/markdown';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            case 'webp':
                return 'image/webp';
            default:
                return 'application/octet-stream';
        }
    }

    /**
     * Parse thong bao loi tu response body cua Supabase.
     */
    private static function extractErrorMessage(string $body, int $httpStatus): string
    {
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                if (isset($decoded['message']) && is_string($decoded['message'])) {
                    return 'Supabase: ' . $decoded['message'];
                }
                if (isset($decoded['error']) && is_string($decoded['error'])) {
                    return 'Supabase: ' . $decoded['error'];
                }
                if (isset($decoded['error_description']) && is_string($decoded['error_description'])) {
                    return 'Supabase: ' . $decoded['error_description'];
                }
            }
            $txt = strip_tags($body);
            if (mb_strlen($txt) > 0 && mb_strlen($txt) < 250) {
                return 'Supabase (HTTP ' . $httpStatus . '): ' . $txt;
            }
        }

        switch ($httpStatus) {
            case 400: return 'Supabase: Yêu cầu không hợp lệ (HTTP 400). Kiểm tra bucket name hoặc API key.';
            case 401: return 'Supabase: API key không hợp lệ hoặc đã hết hạn (HTTP 401).';
            case 403: return 'Supabase: Không có quyền truy cập bucket (HTTP 403). Kiểm tra policy của bucket.';
            case 404: return 'Supabase: Bucket "' . SUPABASE_BUCKET_NAME . '" không tồn tại (HTTP 404). Vui lòng tạo bucket trong Supabase Dashboard.';
            case 413: return 'Supabase: File vượt quá dung lượng cho phép của bucket (HTTP 413).';
            case 500: return 'Supabase: Lỗi máy chủ nội bộ (HTTP 500). Vui lòng thử lại.';
            case 503: return 'Supabase: Dịch vụ tạm thời không khả dụng (HTTP 503).';
            default:  return 'Supabase upload thất bại (HTTP ' . $httpStatus . ').';
        }
    }

    /**
     * Kiem tra mot chuoi co phai duong dan local (uploads/documents/...) khong.
     */
    public static function isLocalUploadPath(?string $path): bool
    {
        if (!is_string($path) || $path === '') {
            return false;
        }
        if (preg_match('#(^|/|\.\./)uploads/(documents|avatars)/#i', $path) === 1) {
            return true;
        }
        return false;
    }

    /**
     * Chuyen duong dan local thanh duong dan tuyet doi (neu co the).
     */
    public static function toAbsoluteLocalPath(string $path): ?string
    {
        if (self::isWindows()) {
            if (preg_match('#^[a-zA-Z]:[\\\\/]#', $path) === 1) {
                return $path;
            }
        }
        if ($path[0] === '/' || $path[0] === '\\') {
            return $path;
        }
        // Tuong doi so voi project root
        $abs = realpath(__DIR__ . '/..' . DIRECTORY_SEPARATOR . $path);
        return $abs !== false ? $abs : null;
    }

    private static function isWindows(): bool
    {
        return defined('PHP_OS_FAMILY') ? (PHP_OS_FAMILY === 'Windows') : (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }
}
