<?php

class FileValidator {
    private static $allowedExtensions = ['pdf', 'docx', 'pptx'];

    private static $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
        'asp', 'aspx', 'cer', 'cgi', 'pl', 'py', 'jsp', 'jspx',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'shell', 'scr', 'vbs',
        'js', 'jar', 'war', 'sql', 'htaccess', 'ini'
    ];

    public static function validate($file) {
        $result = [
            'valid' => true,
            'errors' => [],
            'extension' => '',
            'mime_type' => '',
            'file_type' => ''
        ];

        if (!isset($file) || empty($file['name'])) {
            $result['valid'] = false;
            $result['errors'][] = 'Không có file nào được chọn';
            return $result;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['valid'] = false;
            $result['errors'][] = self::getUploadError($file['error']);
            return $result;
        }

        if ($file['size'] <= 0) {
            $result['valid'] = false;
            $result['errors'][] = 'File rỗng hoặc không hợp lệ';
            return $result;
        }

        $maxSize = 50 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $result['valid'] = false;
            $result['errors'][] = 'File vượt quá kích thước cho phép (50MB)';
            return $result;
        }

        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (empty($extension)) {
            $result['valid'] = false;
            $result['errors'][] = 'File không có phần mở rộng';
            return $result;
        }

        if (!in_array($extension, self::$allowedExtensions)) {
            $result['valid'] = false;
            $result['errors'][] = 'Chỉ cho phép upload file PDF, DOCX, PPTX';
            return $result;
        }

        if (in_array($extension, self::$dangerousExtensions)) {
            $result['valid'] = false;
            $result['errors'][] = 'Định dạng file không được phép upload';
            return $result;
        }

        $mimeType = self::getMimeType($file['tmp_name']);

        if ($mimeType === false) {
            $result['valid'] = false;
            $result['errors'][] = 'Không thể xác định MIME type của file';
            return $result;
        }

        $allowedMimes = self::getAllowedMimes($extension);
        if (!in_array($mimeType, $allowedMimes)) {
            $result['valid'] = false;
            $result['errors'][] = 'MIME type không hợp lệ cho định dạng ' . strtoupper($extension);
            return $result;
        }

        $fileContent = @file_get_contents($file['tmp_name'], false, null, 0, 8192);

        if ($fileContent !== false) {
            if (preg_match('/<\?php/i', $fileContent)) {
                $result['valid'] = false;
                $result['errors'][] = 'File chứa mã PHP không được phép upload';
                return $result;
            }

            if (preg_match('/#!\/bin\/(ba)?sh/i', $fileContent)) {
                $result['valid'] = false;
                $result['errors'][] = 'File chứa shell script không được phép';
                return $result;
            }
        }

        $result['extension'] = $extension;
        $result['mime_type'] = $mimeType;
        $result['file_type'] = strtoupper($extension);

        return $result;
    }

    private static function getAllowedMimes($extension) {
        $mimes = [
            'pdf' => ['application/pdf'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword'
            ],
            'pptx' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-powerpoint'
            ]
        ];
        return $mimes[$extension] ?? [];
    }

    private static function getMimeType($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: false;
    }

    private static function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File vượt quá giới hạn upload của server',
            UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn upload của form',
            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
            UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm để lưu file',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào đĩa',
            UPLOAD_ERR_EXTENSION => 'Upload bị dừng bởi extension PHP'
        ];

        return $errors[$errorCode] ?? 'Lỗi upload không xác định';
    }

    public static function generateSecureFilename($originalName, $userId = null) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        $prefix = $userId ? 'user_' . $userId . '_' : '';
        return $prefix . $timestamp . '_' . $randomString . '.' . $extension;
    }

    public static function sanitizeFilename($filename) {
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 190) . '.' . $ext;
        }
        return $filename;
    }
}
