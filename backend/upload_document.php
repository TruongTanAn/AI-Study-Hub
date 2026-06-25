<?php
<<<<<<< HEAD

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloud_storage.php';
require_once __DIR__ . '/validate_file.php';
require_once __DIR__ . '/save_document.php';
require_once __DIR__ . '/save_cloud_url.php';
require_once __DIR__ . '/upload_status.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng đăng nhập để tải lên tài liệu'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if (empty($title)) {
    echo json_encode([
        'success' => false,
        'error' => 'Tiêu đề tài liệu không được để trống'
    ]);
    exit;
}

if (strlen($title) > 255) {
    echo json_encode([
        'success' => false,
        'error' => 'Tiêu đề quá dài (tối đa 255 ký tự)'
    ]);
    exit;
}

if (!isset($_FILES['document']) || empty($_FILES['document']['name'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng chọn file để tải lên'
    ]);
    exit;
}

$file = $_FILES['document'];

$validation = FileValidator::validate($file);

if (!$validation['valid']) {
    echo json_encode([
        'success' => false,
        'error' => implode('. ', $validation['errors']),
        'errors' => $validation['errors']
    ]);
    exit;
}

$originalName = FileValidator::sanitizeFilename($file['name']);
$secureFilename = FileValidator::generateSecureFilename($originalName, $userId);

$uploadDir = __DIR__ . '/../uploads/documents/';

if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode([
            'success' => false,
            'error' => 'Không thể tạo thư mục lưu trữ'
        ]);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode([
        'success' => false,
        'error' => 'Thư mục lưu trữ không có quyền ghi'
    ]);
    exit;
}

$targetPath = $uploadDir . $secureFilename;

if (file_exists($targetPath)) {
    $secureFilename = FileValidator::generateSecureFilename($originalName, $userId . '_retry_' . time());
    $targetPath = $uploadDir . $secureFilename;
}

$result = CloudStorage::upload($file['tmp_name'], $secureFilename, 'documents');

if (!$result['success']) {
    echo json_encode([
        'success' => false,
        'error' => 'Không thể tải file lên: ' . $result['error']
    ]);
    exit;
}

$localPath = $result['path'] ?? $uploadDir . $secureFilename;
$cloudUrl = $result['url'] ?? null;
$cloudProvider = CloudStorage::getConfig()['provider'];

$documentData = [
    'user_id' => $userId,
    'title' => $title,
    'description' => $description,
    'file_name' => $secureFilename,
    'original_name' => $originalName,
    'file_path' => $localPath,
    'file_type' => $validation['file_type'],
    'mime_type' => $validation['mime_type'],
    'file_size' => $file['size']
];

if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
    $documentData['category_id'] = intval($_POST['category_id']);
}

$saveResult = saveDocumentToDatabase($documentData);

if (!$saveResult['success']) {
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }

    echo json_encode([
        'success' => false,
        'error' => 'Không thể lưu thông tin tài liệu: ' . $saveResult['error']
    ]);
    exit;
}

$documentId = $saveResult['document_id'];

if ($cloudUrl !== null) {
    $cloudData = [
        'url' => $cloudUrl,
        'path' => $result['path'] ?? $secureFilename,
        'provider' => $cloudProvider,
        'bucket' => $result['bucket'] ?? null
    ];

    $cloudResult = saveCloudUrl($documentId, $cloudData);

    if ($cloudResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Tải lên tài liệu thành công',
            'document_id' => $documentId,
            'cloud_url' => $cloudUrl,
            'file' => [
                'name' => $originalName,
                'type' => $validation['file_type'],
                'size' => $file['size'],
                'size_formatted' => formatFileSize($file['size'])
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Tải lên tài liệu thành công (Cloud URL chưa được lưu)',
            'document_id' => $documentId,
            'file' => [
                'name' => $originalName,
                'type' => $validation['file_type'],
                'size' => $file['size'],
                'size_formatted' => formatFileSize($file['size'])
            ]
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Tải lên tài liệu thành công',
        'document_id' => $documentId,
        'file' => [
            'name' => $originalName,
            'type' => $validation['file_type'],
            'size' => $file['size'],
            'size_formatted' => formatFileSize($file['size'])
        ]
    ]);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;

    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }

    return round($bytes, 2) . ' ' . $units[$unitIndex];
}
=======
session_start();
require_once '../includes/auth_check.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded without errors
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $fileSize = $_FILES['document']['size'];
        $fileType = $_FILES['document']['type'];
        
        // Define allowed extensions
        $allowedExts = ['pdf', 'doc', 'docx', 'txt'];
        
        // Get file extension
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Check if extension is allowed
        if (in_array($fileExtension, $allowedExts)) {
            
            // Limit file size to 10MB
            if ($fileSize < (10 * 1024 * 1024)) {
                
                // Set upload directory
                $uploadFileDir = '../uploads/';
                
                // Create directory if not exists
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                // Rename file to prevent duplicates
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                
                // Move file to destination
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Success, redirect back with success message
                    $message = urlencode("Tài liệu đã được tải lên thành công!");
                    header("Location: ../pages/upload.php?success=" . $message);
                    exit();
                } else {
                    $error = urlencode("Đã có lỗi xảy ra khi di chuyển file tải lên.");
                }
            } else {
                $error = urlencode("Kích thước file vượt quá 10MB.");
            }
        } else {
            $error = urlencode("Định dạng file không được hỗ trợ. Vui lòng tải lên PDF, DOC, DOCX hoặc TXT.");
        }
    } else {
        $error = urlencode("Lỗi khi tải file lên hoặc bạn chưa chọn file.");
    }
} else {
    $error = urlencode("Yêu cầu không hợp lệ.");
}

// Redirect back with error
header("Location: ../pages/upload.php?error=" . $error);
exit();
?>
>>>>>>> origin/hoa-fe
