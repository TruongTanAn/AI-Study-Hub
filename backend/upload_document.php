<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function returnJson($success, $message = '', $error = '', $extra = []) {
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($error) $response['error'] = $error;
    $response = array_merge($response, $extra);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    returnJson(false, '', 'Vui lòng đăng nhập để tải lên tài liệu');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnJson(false, '', 'Phương thức không được hỗ trợ');
}

$userId = $_SESSION['user_id'];
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$visibility = isset($_POST['visibility']) ? trim($_POST['visibility']) : 'public';
$status = 'pending';

if (empty($title)) {
    returnJson(false, '', 'Tiêu đề tài liệu không được để trống');
}

if (strlen($title) > 255) {
    returnJson(false, '', 'Tiêu đề quá dài (tối đa 255 ký tự)');
}

$fileInputName = 'file';
if (!isset($_FILES[$fileInputName]) || empty($_FILES[$fileInputName]['name'])) {
    returnJson(false, '', 'Vui lòng chọn file để tải lên');
}

$file = $_FILES[$fileInputName];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File vượt quá giới hạn upload của server',
        UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn upload của form',
        UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
        UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
        UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm để lưu file',
        UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào đĩa',
        UPLOAD_ERR_EXTENSION => 'Upload bị dừng bởi extension PHP'
    ];
    $errorMsg = $uploadErrors[$file['error']] ?? 'Lỗi upload không xác định';
    returnJson(false, '', $errorMsg);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'docx', 'pptx'];

if (!in_array($extension, $allowedExtensions)) {
    returnJson(false, '', 'Chỉ cho phép upload file PDF, DOCX, PPTX');
}

if ($file['size'] <= 0) {
    returnJson(false, '', 'File rỗng hoặc không hợp lệ');
}

if ($file['size'] > 50 * 1024 * 1024) {
    returnJson(false, '', 'File vượt quá kích thước cho phép (50MB)');
}

// Kiem tra MIME type (chan upload file doc hai - chi validate khi extension co ve OK)
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = [
            'pdf'  => ['application/pdf'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
                'application/zip',
                'application/octet-stream',
            ],
            'pptx' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-powerpoint',
                'application/zip',
                'application/octet-stream',
            ],
        ];
        if ($detectedMime && isset($allowedMimes[$extension])
            && !in_array($detectedMime, $allowedMimes[$extension], true)) {
            returnJson(false, '', 'MIME type không hợp lệ cho định dạng ' . strtoupper($extension));
        }
    }
}

// Scan noi dung PHP/shell
$fileContent = @file_get_contents($file['tmp_name'], false, null, 0, 8192);
if ($fileContent !== false) {
    if (preg_match('/<\?php/i', $fileContent)) {
        returnJson(false, '', 'File chứa mã PHP không được phép upload');
    }
    if (preg_match('/#!\/bin\/(ba)?sh/i', $fileContent)) {
        returnJson(false, '', 'File chứa shell script không được phép');
    }
}

$originalName = preg_replace('/[^\w\-\.]/', '_', $file['name']);
$originalName = preg_replace('/_+/', '_', $originalName);
$originalName = trim($originalName, '_');

$timestamp = time();
$randomString = bin2hex(random_bytes(8));
$secureFilename = 'user_' . $userId . '_' . $timestamp . '_' . $randomString . '.' . $extension;

// Upload truc tiep len Supabase Storage (KHONG qua uploads/ local)
require_once __DIR__ . '/../config/cloud_storage.php';

$uploadResult = CloudStorage::upload($file['tmp_name'], $secureFilename, $userId, 'documents');

if (!$uploadResult['success']) {
    $errMsg = isset($uploadResult['error']) ? $uploadResult['error'] : 'Upload thất bại';
    if (function_exists('ai_log')) {
        ai_log('supabase_upload_failed', 'Upload tai lieu that bai', [
            'user_id'     => $userId,
            'file_name'   => $secureFilename,
            'file_size'   => $file['size'],
            'error'       => $errMsg,
        ]);
    }
    returnJson(false, '', $errMsg);
}

$publicUrl = $uploadResult['url'];
$objectPath = isset($uploadResult['object_path']) ? $uploadResult['object_path'] : $secureFilename;

// Luu DB: file_path = public URL (de preview/download truc tiep va tuong thich nguoc)
try {
    require_once __DIR__ . '/../config/database.php';

    $documentData = [
        'user_id'        => $userId,
        'title'          => $title,
        'description'    => $description,
        'file_name'      => $secureFilename,
        'original_name'  => $originalName,
        'file_path'      => $publicUrl,
        'file_type'      => strtoupper($extension),
        'file_size'      => $file['size'],
        'visibility'     => $visibility,
        'status'         => $status,
    ];

    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $documentData['category_id'] = intval($_POST['category_id']);
    }

    require_once __DIR__ . '/save_document.php';

    $saveResult = saveDocumentToDatabase($documentData);

    if (!$saveResult['success']) {
        // Rollback: xoa file tren Supabase neu luu DB loi
        if (!empty($objectPath)) {
            @CloudStorage::delete($objectPath);
        } elseif (!empty($publicUrl)) {
            @CloudStorage::delete($publicUrl);
        }
        throw new Exception($saveResult['error']);
    }
} catch (Exception $e) {
    returnJson(false, '', 'Lỗi lưu trữ dữ liệu: ' . $e->getMessage());
}

$documentId = $saveResult['document_id'];
$fileSizeFormatted = formatFileSize($file['size']);

if (function_exists('ai_log')) {
    ai_log('supabase_upload_success', 'Upload tai lieu thanh cong', [
        'user_id'     => $userId,
        'document_id' => $documentId,
        'file_name'   => $secureFilename,
        'file_size'   => $file['size'],
        'url'         => $publicUrl,
    ]);
}

returnJson(true, 'Tải lên tài liệu thành công', '', [
    'document_id' => $documentId,
    'file_url'    => $publicUrl,
    'file' => [
        'name'            => $originalName,
        'type'            => strtoupper($extension),
        'size'            => $file['size'],
        'size_formatted'  => $fileSizeFormatted,
        'url'             => $publicUrl,
        'storage'         => 'supabase',
    ],
]);

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}
