<?php
/**
 * update_document.php
 * Validate và cập nhật thông tin tài liệu, trả về JSON.
 * Ho tro ca truong hop thay the file (Supabase Storage).
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để cập nhật tài liệu.',
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.',
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloud_storage.php';

$userId = (int) $_SESSION['user_id'];
$documentId = (int) ($_POST['document_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== ''
    ? (int) $_POST['category_id']
    : null;
$subjectId = isset($_POST['subject_id']) && $_POST['subject_id'] !== ''
    ? (int) $_POST['subject_id']
    : null;
$visibility = trim($_POST['visibility'] ?? '');

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Document ID không hợp lệ.',
    ]);
    exit();
}

if ($title === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Tiêu đề không được để trống.',
    ]);
    exit();
}

if (strlen($title) > 255) {
    echo json_encode([
        'success' => false,
        'message' => 'Tiêu đề không được vượt quá 255 ký tự.',
    ]);
    exit();
}

$allowedVisibility = ['public', 'private', 'shared'];
if ($visibility !== '' && !in_array($visibility, $allowedVisibility, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Quyền hiển thị không hợp lệ.',
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$checkSql = 'SELECT user_id, visibility FROM documents WHERE document_id = ?';
$checkStmt = $conn->prepare($checkSql);

if ($checkStmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$checkStmt->bind_param('i', $documentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$existingDocument = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$existingDocument) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy tài liệu.',
    ]);
    exit();
}

$isOwner = ((int) $existingDocument['user_id'] === $userId);
$userRole = 'user';

$roleStmt = $conn->prepare('SELECT role FROM users WHERE user_id = ?');
if ($roleStmt) {
    $roleStmt->bind_param('i', $userId);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $roleData = $roleResult->fetch_assoc();
    $roleStmt->close();

    if ($roleData) {
        $userRole = $roleData['role'];
    }
}

if (!$isOwner && $userRole !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền cập nhật tài liệu này.',
    ]);
    exit();
}

if ($visibility === '') {
    $visibility = $existingDocument['visibility'];
}

// Lay file_path cu de rollback khi replace file that bai
$oldFilePathGetStmt = $conn->prepare('SELECT file_path FROM documents WHERE document_id = ?');
if ($oldFilePathGetStmt) {
    $oldFilePathGetStmt->bind_param('i', $documentId);
    $oldFilePathGetStmt->execute();
    $oldFilePathRow = $oldFilePathGetStmt->get_result()->fetch_assoc();
    $oldFilePathGetStmt->close();
    $oldFilePath = $oldFilePathRow ? (string) ($oldFilePathRow['file_path'] ?? '') : '';
} else {
    $oldFilePath = '';
}

// ============== Xu ly file moi (neu co) ==============
$newFilePath        = null;
$newFileName        = null;
$newOriginal        = null;
$newFileType        = null;
$newFileSize        = null;
$uploadedObjectPath = null;

if (isset($_FILES['file']) && is_array($_FILES['file'])
    && isset($_FILES['file']['error'])
    && (int) $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['file'];

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File vượt quá giới hạn upload của server',
            UPLOAD_ERR_FORM_SIZE  => 'File vượt quá giới hạn upload của form',
            UPLOAD_ERR_PARTIAL    => 'File chỉ được upload một phần',
            UPLOAD_ERR_NO_FILE    => 'Không có file nào được upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm để lưu file',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào đĩa',
            UPLOAD_ERR_EXTENSION  => 'Upload bị dừng bởi extension PHP',
        ];
        $msg = $errMap[(int) $file['error']] ?? 'Lỗi upload không xác định';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    $ext = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'docx', 'pptx'], true)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ cho phép upload file PDF, DOCX, PPTX']);
        exit();
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > 50 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File không hợp lệ hoặc vượt quá 50MB']);
        exit();
    }

    $originalName = preg_replace('/[^\w\-\.]/', '_', (string) $file['name']);
    $originalName = preg_replace('/_+/', '_', $originalName);
    $originalName = trim($originalName, '_');

    $timestamp = time();
    $random    = bin2hex(random_bytes(8));
    $secureFilename = 'user_' . $userId . '_' . $timestamp . '_' . $random . '.' . $ext;

    $uploadResult = CloudStorage::upload($file['tmp_name'], $secureFilename, $userId, 'documents');
    if (!$uploadResult['success']) {
        $msg = isset($uploadResult['error']) ? $uploadResult['error'] : 'Upload file mới thất bại';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    $newFilePath        = $uploadResult['url'];
    $uploadedObjectPath = isset($uploadResult['object_path']) ? $uploadResult['object_path'] : null;
    $newFileName        = $secureFilename;
    $newOriginal        = $originalName;
    $newFileType        = strtoupper($ext);
    $newFileSize        = (int) $file['size'];
}

if ($categoryId !== null && $categoryId > 0) {
    $categoryStmt = $conn->prepare('SELECT category_id FROM categories WHERE category_id = ?');
    if ($categoryStmt) {
        $categoryStmt->bind_param('i', $categoryId);
        $categoryStmt->execute();
        $categoryResult = $categoryStmt->get_result();
        $categoryExists = $categoryResult->fetch_assoc();
        $categoryStmt->close();

        if (!$categoryExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Danh mục không tồn tại.',
            ]);
            exit();
        }
    }
}

if ($subjectId !== null && $subjectId > 0) {
    $subjectStmt = $conn->prepare('SELECT subject_id FROM subjects WHERE subject_id = ?');
    if ($subjectStmt) {
        $subjectStmt->bind_param('i', $subjectId);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->get_result();
        $subjectExists = $subjectResult->fetch_assoc();
        $subjectStmt->close();

        if (!$subjectExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Môn học không tồn tại.',
            ]);
            exit();
        }
    }
}

$replaceFile = ($newFilePath !== null);

if ($replaceFile) {
    $updateSql = 'UPDATE documents
                  SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?,
                      file_path = ?, file_name = ?, original_name = ?, file_type = ?, file_size = ?
                  WHERE document_id = ?';
    $updateStmt = $conn->prepare($updateSql);
    if ($updateStmt === false) {
        if ($uploadedObjectPath !== null) {
            @CloudStorage::delete($uploadedObjectPath);
        } elseif ($newFilePath !== null) {
            @CloudStorage::delete($newFilePath);
        }
        echo json_encode(['success' => false, 'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.']);
        exit();
    }
    $updateStmt->bind_param(
        'ssiisssssii',
        $title,
        $description,
        $categoryId,
        $subjectId,
        $visibility,
        $newFilePath,
        $newFileName,
        $newOriginal,
        $newFileType,
        $newFileSize,
        $documentId
    );
} else {
    $updateSql = 'UPDATE documents
                  SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?
                  WHERE document_id = ?';
    $updateStmt = $conn->prepare($updateSql);

    if ($updateStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.']);
        exit();
    }

    $updateStmt->bind_param(
        'ssiisi',
        $title,
        $description,
        $categoryId,
        $subjectId,
        $visibility,
        $documentId
    );
}

if ($updateStmt->execute()) {
    $updateStmt->close();

    // Neu replace file thanh cong -> xoa file cu tren Supabase
    if ($replaceFile && $oldFilePath !== '') {
        @CloudStorage::delete($oldFilePath);
        if (CloudStorage::isLocalUploadPath($oldFilePath)) {
            $abs = CloudStorage::toAbsoluteLocalPath($oldFilePath);
            if ($abs !== null && is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $replaceFile ? 'Cập nhật tài liệu và thay file thành công.' : 'Cập nhật tài liệu thành công.',
        'document_id' => $documentId,
        'file_replaced' => $replaceFile,
        'new_url' => $replaceFile ? $newFilePath : null,
    ]);
    exit();
}

$updateStmt->close();
if ($replaceFile) {
    if ($uploadedObjectPath !== null) {
        @CloudStorage::delete($uploadedObjectPath);
    } elseif ($newFilePath !== null) {
        @CloudStorage::delete($newFilePath);
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Cập nhật tài liệu thất bại.',
]);
