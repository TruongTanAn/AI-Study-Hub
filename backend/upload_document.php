<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Vui long dang nhap de tai tai lieu'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phuong thuc khong duoc ho tro'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$title = isset($_POST['title']) ? trim(to_utf8($_POST['title'])) : '';
$description = isset($_POST['description']) ? trim(to_utf8($_POST['description'])) : '';
$visibility = isset($_POST['visibility']) ? trim($_POST['visibility']) : 'public';
$status = 'pending';

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Tieu de tai lieu khong duoc de trong'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($title) > 255) {
    echo json_encode(['success' => false, 'error' => 'Tieu de qua dai (toi da 255 ky tu)'], JSON_UNESCAPED_UNICODE);
    exit;
}

$fileInputName = 'file';
if (!isset($_FILES[$fileInputName]) || empty($_FILES[$fileInputName]['name'])) {
    echo json_encode(['success' => false, 'error' => 'Vui long chon file de tai len'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES[$fileInputName];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File vuot qua gioi han upload cua server',
        UPLOAD_ERR_FORM_SIZE => 'File vuot qua gioi han upload cua form',
        UPLOAD_ERR_PARTIAL => 'File chi duoc upload mot phan',
        UPLOAD_ERR_NO_FILE => 'Khong co file nao duoc upload',
        UPLOAD_ERR_NO_TMP_DIR => 'Thieu thu muc tam de luu file',
        UPLOAD_ERR_CANT_WRITE => 'Khong the ghi file vao dia',
        UPLOAD_ERR_EXTENSION => 'Upload bi dung boi extension PHP'
    ];
    $errorMsg = $uploadErrors[$file['error']] ?? 'Loi upload khong xac dinh';
    echo json_encode(['success' => false, 'error' => $errorMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'docx', 'pptx'];

if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'error' => 'Chi cho phep upload file PDF, DOCX, PPTX'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($file['size'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'File rong hoac khong hop le'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($file['size'] > 50 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File vuot qua kich thuoc cho phep (50MB)'], JSON_UNESCAPED_UNICODE);
    exit;
}

$originalName = to_utf8(preg_replace('/[^\w\-\.]/', '_', $file['name']));
$originalName = trim(preg_replace('/_+/', '_', $originalName), '_');

$timestamp = time();
$randomString = bin2hex(random_bytes(8));
$secureFilename = 'user_' . $userId . '_' . $timestamp . '_' . $randomString . '.' . $extension;

$uploadDir = __DIR__ . '/../uploads/documents/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Khong the tao thu muc luu tru'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'Thu muc luu tru khong co quyen ghi'], JSON_UNESCAPED_UNICODE);
    exit;
}

$targetPath = $uploadDir . $secureFilename;

if (file_exists($targetPath)) {
    $secureFilename = 'user_' . $userId . '_retry_' . time() . '_' . $randomString . '.' . $extension;
    $targetPath = $uploadDir . $secureFilename;
}

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'error' => 'Khong the di chuyen file den thu muc upload'], JSON_UNESCAPED_UNICODE);
    exit;
}

chmod($targetPath, 0644);

$categoryId = isset($_POST['category_id']) && !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;

$documentData = [
    'user_id' => $userId,
    'title' => $title,
    'description' => $description,
    'file_name' => $secureFilename,
    'original_name' => $originalName,
    'file_path' => $targetPath,
    'file_type' => strtoupper($extension),
    'file_size' => $file['size'],
    'visibility' => $visibility,
    'status' => $status,
    'category_id' => $categoryId
];

require_once __DIR__ . '/save_document.php';
$saveResult = saveDocumentToDatabase($documentData);

if (!$saveResult['success']) {
    @unlink($targetPath);
    echo json_encode(['success' => false, 'error' => $saveResult['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$documentId = $saveResult['document_id'];
$fileSizeFormatted = format_filesize($file['size']);

echo json_encode([
    'success' => true,
    'message' => 'Tai tai lieu thanh cong',
    'document_id' => $documentId,
    'file' => [
        'name' => $originalName,
        'type' => strtoupper($extension),
        'size' => $file['size'],
        'size_formatted' => $fileSizeFormatted
    ]
], JSON_UNESCAPED_UNICODE);
