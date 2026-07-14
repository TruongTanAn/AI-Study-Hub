<?php
/**
 * AI Study Hub - Document Preview Backend (Week 5 - AN)
 *
 * Tra ve metadata cua tai lieu + preview text (neu co) de hien thi trong chatbot.
 * Su dung duoc cho ca frontend preview va document_qa RAG.
 *
 * Sau khi migrate sang Supabase Storage:
 * - file_path co the la Supabase public URL (moi)
 * - file_path co the la duong dan local cu (cu, de tuong thich)
 *
 * Browser nhan duoc 'url' de mo truc tiep (iframe / MS Office viewer).
 * Voi PDF: iframe src = Supabase URL => render binh thuong.
 * Voi DOCX/PPTX: 'office_online' type + Microsoft Office Viewer URL.
 */

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloud_storage.php';
require_once __DIR__ . '/conversation_helpers.php';

/* =========================================================
   Helpers: download file tu Supabase Storage (neu la URL) ve file tam
   de cac extractor (PDF/DOCX/PPTX) co the doc.
   Phai khai bao TRUOC HTTP entrypoint vi HTTP entrypoint goi cac ham nay.
   ========================================================= */

if (!function_exists('ai_storage_download_to_temp')) {
    /**
     * Neu file_path la Supabase URL -> download ve file tam, tra ve duong dan tmp.
     * Neu khong phai -> tra ve duong dan goc neu la file local ton tai.
     */
    function ai_storage_download_to_temp(string $filePath, bool $isSupabaseUrl): ?string {
        if ($filePath === '') return null;

        if (!$isSupabaseUrl) {
            return is_file($filePath) ? $filePath : null;
        }

        if (!function_exists('curl_init')) return null;

        $tmp = @tempnam(sys_get_temp_dir(), 'ai_prev_');
        if ($tmp === false) return null;

        $ch = curl_init($filePath);
        if ($ch === false) {
            @unlink($tmp);
            return null;
        }

        $fp = @fopen($tmp, 'wb');
        if ($fp === false) {
            curl_close($ch);
            @unlink($tmp);
            return null;
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $errno !== 0 || $httpCode >= 400) {
            @unlink($tmp);
            return null;
        }

        return $tmp;
    }
}

if (!function_exists('ai_storage_download_to_string')) {
    /**
     * Download tu Supabase URL (hoac doc local) tra ve string.
     */
    function ai_storage_download_to_string(string $filePath, bool $isSupabaseUrl): ?string {
        if ($filePath === '') return null;

        if (!$isSupabaseUrl) {
            $content = @file_get_contents($filePath);
            return $content === false ? null : $content;
        }

        $tmp = ai_storage_download_to_temp($filePath, true);
        if ($tmp === null) return null;

        $content = @file_get_contents($tmp);
        @unlink($tmp);
        return $content === false ? null : $content;
    }
}

if (!function_exists('ai_storage_extract_document_text_from_any')) {
    /**
     * Trich text tu local file HOAC tu Supabase URL.
     */
    function ai_storage_extract_document_text_from_any(string $filePath, string $ext, bool $isSupabaseUrl): ?string {
        $tmp = ai_storage_download_to_temp($filePath, $isSupabaseUrl);
        if ($tmp === null) return null;

        if (!function_exists('ai_extract_document_text')) {
            if ($tmp !== $filePath) {
                @unlink($tmp);
            }
            return null;
        }

        $debugLocal = null;
        $text = ai_extract_document_text($tmp, $ext, 6000, $debugLocal);

        // Chi xoa neu la file tam (Supabase download), giu local neu co
        if ($tmp !== $filePath) {
            @unlink($tmp);
        }

        if ($text === '' && function_exists('ai_log')) {
            ai_log('preview_extract_failed', 'preview_document khong trich duoc text', [
                'source' => $filePath,
                'ext'    => $ext,
                'reason' => $debugLocal['reason'] ?? 'empty',
            ]);
        }

        return $text;
    }
}

/* =========================================================
   HTTP entrypoint
   ========================================================= */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('an_respond')) {
    function an_respond(bool $success, array $payload): void {
        $out = $success
            ? array_merge(['success' => true], $payload)
            : array_merge(['success' => false], $payload);
        $json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['success' => false, 'error' => 'Khong the ma hoa phan hoi JSON'], JSON_UNESCAPED_UNICODE);
        }
        echo (string) $json;
    }
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    an_respond(false, ['error' => 'Phien dang nhap het han. Vui long dang nhap lai.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$documentId = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;
$includeText = !isset($_GET['meta_only']) || (string) $_GET['meta_only'] !== '1';

if ($documentId <= 0) {
    http_response_code(400);
    an_respond(false, ['error' => 'document_id khong hop le']);
    exit;
}

$document = ai_db_get_document_for_user($conn, $documentId, $userId);
if (!$document) {
    http_response_code(404);
    an_respond(false, ['error' => 'Tai lieu khong ton tai hoac ban khong co quyen truy cap']);
    exit;
}

$filePath = (string) $document['file_path'];
$fileName = (string) $document['file_name'];

$isSupabaseUrl = ($filePath !== '') && class_exists('CloudStorage') && CloudStorage::isSupabaseUrl($filePath);

// Xac dinh URL frontend truy cap duoc
$fileUrl = '';
if ($isSupabaseUrl) {
    $fileUrl = $filePath; // Supabase public URL - mo truc tiep
} else if ($filePath !== '') {
    // Local path - file se duoc serve qua backend/download.php
    $fileUrl = '../backend/download.php?id=' . (int) $document['document_id'];
}

// Extension theo file_name (chinh xac nhat)
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($ext === '') {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
}

$previewText  = '';
$previewType  = 'unsupported';
$fileExists   = $isSupabaseUrl || is_file($filePath);

$mimeMap = [
    'pdf'  => 'pdf',
    'txt'  => 'text',
    'md'   => 'text',
    'jpg'  => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image',
];
$previewType = $mimeMap[$ext] ?? 'unsupported';

// DOCX/PPTX: preview qua Microsoft Office Online viewer
if ($previewType === 'unsupported' && in_array($ext, ['docx', 'pptx'], true)) {
    $previewType = 'office_online';
}

if ($includeText) {
    if (in_array($ext, ['txt', 'md'], true)) {
        $rawContent = ai_storage_download_to_string($filePath, $isSupabaseUrl);
        if (is_string($rawContent) && $rawContent !== '') {
            $previewText = $rawContent;
            if (function_exists('to_utf8')) {
                $previewText = to_utf8($previewText);
            }
            if (mb_strlen($previewText) > 6000) {
                $previewText = mb_substr($previewText, 0, 6000) . '... [noi dung da rut gon]';
            }
        }
    } elseif (in_array($ext, ['pdf', 'docx', 'pptx'], true)) {
        // Trich text cho RAG context
        $extracted = ai_storage_extract_document_text_from_any($filePath, $ext, $isSupabaseUrl);
        if (is_string($extracted) && $extracted !== '') {
            $previewText = $extracted;
        }
    }
}

an_respond(true, [
    'document' => [
        'document_id'   => (int) $document['document_id'],
        'title'         => (string) $document['title'],
        'description'   => (string) ($document['description'] ?? ''),
        'file_name'     => $fileName,
        'original_name' => (string) $document['original_name'],
        'file_type'     => (string) $document['file_type'],
        'visibility'    => (string) $document['visibility'],
        'status'        => (string) $document['status'],
    ],
    'file' => [
        'path'    => $filePath,
        'url'     => $fileUrl,
        'exists'  => $fileExists,
        'ext'     => $ext,
        'preview' => $previewType,
        'text'    => $previewText,
    ],
]);
