<?php
/**
 * backend/edit_document_process.php
 *
 * Cap nhat tai lieu (thong tin + tuy chinh file moi).
 *
 * Luong xu ly:
 *   - Validate input (title, description, visibility, category, subject)
 *   - Quyen: owner hoac admin moi duoc sua
 *   - Neu co file moi (POST file):
 *       + Validate (type/size/MIME/no PHP)
 *       + Upload truc tiep len Supabase Storage
 *       + Cap nhat file_path, file_name, file_size, file_type, original_name
 *       + Xoa file cu tren Supabase Storage (rollback neu moi that bai)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'Vui lòng đăng nhập để thực hiện tác vụ này'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error'   => 'Phương thức request không được hỗ trợ'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloud_storage.php';

$userId   = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

$documentId = isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0;
$title      = isset($_POST['title'])       ? trim((string) $_POST['title'])       : '';
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
$categoryId = (isset($_POST['category_id']) && $_POST['category_id'] !== '') ? (int) $_POST['category_id'] : null;
$subjectId  = (isset($_POST['subject_id'])  && $_POST['subject_id']  !== '') ? (int) $_POST['subject_id']  : null;
$visibility = isset($_POST['visibility'])  ? trim((string) $_POST['visibility']) : 'public';

if ($documentId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID tài liệu không hợp lệ']);
    exit;
}

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Tiêu đề tài liệu không được để trống']);
    exit;
}

if (mb_strlen($title) > 255) {
    echo json_encode(['success' => false, 'error' => 'Tiêu đề quá dài (tối đa 255 ký tự)']);
    exit;
}

if (!in_array($visibility, ['public', 'private', 'shared'], true)) {
    echo json_encode(['success' => false, 'error' => 'Quyền riêng tư không hợp lệ']);
    exit;
}

// Fetch document de lay file_path cu va check quyen
$oldFilePath  = null;
$oldFileName  = null;
$ownerId = 0;

$checkStmt = $conn->prepare("SELECT user_id, file_path, file_name FROM documents WHERE document_id = ?");
if (!$checkStmt) {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Prepare check failed']);
    exit;
}
$checkStmt->bind_param("i", $documentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$existingDoc = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$existingDoc) {
    echo json_encode(['success' => false, 'error' => 'Tài liệu không tồn tại']);
    exit;
}

$ownerId     = (int) $existingDoc['user_id'];
$oldFilePath = (string) ($existingDoc['file_path'] ?? '');
$oldFileName = (string) ($existingDoc['file_name'] ?? '');

if ($ownerId !== $userId && $userRole !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Bạn không có quyền chỉnh sửa tài liệu này']);
    exit;
}

// Check category/subject ton tai (neu co gia tri)
if ($categoryId !== null && $categoryId > 0) {
    $catStmt = $conn->prepare("SELECT category_id FROM categories WHERE category_id = ?");
    if ($catStmt) {
        $catStmt->bind_param("i", $categoryId);
        $catStmt->execute();
        $catRes = $catStmt->get_result();
        if (!$catRes->fetch_assoc()) {
            $catStmt->close();
            echo json_encode(['success' => false, 'error' => 'Danh mục không tồn tại']);
            exit;
        }
        $catStmt->close();
    }
}

if ($subjectId !== null && $subjectId > 0) {
    $subStmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_id = ?");
    if ($subStmt) {
        $subStmt->bind_param("i", $subjectId);
        $subStmt->execute();
        $subRes = $subStmt->get_result();
        if (!$subRes->fetch_assoc()) {
            $subStmt->close();
            echo json_encode(['success' => false, 'error' => 'Môn học không tồn tại']);
            exit;
        }
        $subStmt->close();
    }
}

// ============== Xu ly file moi (neu co) ==============
$newFilePath  = null;
$newFileName  = null;
$newOriginal  = null;
$newFileType  = null;
$newFileSize  = null;
$uploadedObjectPath = null;

if (isset($_FILES['file']) && is_array($_FILES['file']) && (int) $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
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
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    $ext = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'docx', 'pptx'], true)) {
        echo json_encode(['success' => false, 'error' => 'Chỉ cho phép upload file PDF, DOCX, PPTX']);
        exit;
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > 50 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File không hợp lệ hoặc vượt quá 50MB']);
        exit;
    }

    // MIME check
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $file['tmp_name']);
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
            if ($detected && isset($allowedMimes[$ext]) && !in_array($detected, $allowedMimes[$ext], true)) {
                echo json_encode(['success' => false, 'error' => 'MIME type không hợp lệ cho định dạng ' . strtoupper($ext)]);
                exit;
            }
        }
    }

    // Scan ma doc hai
    $fileContent = @file_get_contents($file['tmp_name'], false, null, 0, 8192);
    if ($fileContent !== false) {
        if (preg_match('/<\?php/i', $fileContent)) {
            echo json_encode(['success' => false, 'error' => 'File chứa mã PHP không được phép upload']);
            exit;
        }
        if (preg_match('/#!\/bin\/(ba)?sh/i', $fileContent)) {
            echo json_encode(['success' => false, 'error' => 'File chứa shell script không được phép']);
            exit;
        }
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
        if (function_exists('ai_log')) {
            ai_log('supabase_edit_replace_failed', 'Edit replace upload failed', [
                'document_id' => $documentId,
                'user_id'     => $userId,
                'error'       => $msg,
            ]);
        }
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    $newFilePath        = $uploadResult['url'];
    $uploadedObjectPath = isset($uploadResult['object_path']) ? $uploadResult['object_path'] : null;
    $newFileName        = $secureFilename;
    $newOriginal        = $originalName;
    $newFileType        = strtoupper($ext);
    $newFileSize        = (int) $file['size'];
}

// ============== Cap nhat DB ==============
if ($newFilePath !== null) {
    $updateStmt = $conn->prepare("
        UPDATE documents
        SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?,
            file_path = ?, file_name = ?, original_name = ?, file_type = ?, file_size = ?
        WHERE document_id = ?
    ");
    if (!$updateStmt) {
        // Rollback: xoa file moi tren Supabase
        if ($uploadedObjectPath !== null) {
            @CloudStorage::delete($uploadedObjectPath);
        } elseif ($newFilePath !== null) {
            @CloudStorage::delete($newFilePath);
        }
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Prepare update failed']);
        exit;
    }
    $updateStmt->bind_param(
        "ssiisssssii",
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
    $updateStmt = $conn->prepare("
        UPDATE documents
        SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?
        WHERE document_id = ?
    ");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Prepare update failed']);
        exit;
    }
    $updateStmt->bind_param(
        "ssiisi",
        $title,
        $description,
        $categoryId,
        $subjectId,
        $visibility,
        $documentId
    );
}

if (!$updateStmt->execute()) {
    $error = $updateStmt->error;
    $updateStmt->close();
    // Rollback neu dang replace file
    if ($newFilePath !== null) {
        if ($uploadedObjectPath !== null) {
            @CloudStorage::delete($uploadedObjectPath);
        } else {
            @CloudStorage::delete($newFilePath);
        }
    }
    echo json_encode(['success' => false, 'error' => 'Không thể cập nhật tài liệu: ' . $error]);
    exit;
}

$updateStmt->close();

// Neu replace file thanh cong -> xoa file cu tren Supabase
if ($newFilePath !== null && $oldFilePath !== '') {
    @CloudStorage::delete($oldFilePath);
    // Don dep local neu con sot
    if (CloudStorage::isLocalUploadPath($oldFilePath)) {
        $abs = CloudStorage::toAbsoluteLocalPath($oldFilePath);
        if ($abs !== null && is_file($abs)) {
            @unlink($abs);
        }
    }
}

if (function_exists('ai_log')) {
    ai_log('edit_document_success', 'Cap nhat tai lieu thanh cong', [
        'document_id'    => $documentId,
        'user_id'        => $userId,
        'file_replaced'  => $newFilePath !== null,
        'new_file_url'   => $newFilePath,
    ]);
}

echo json_encode([
    'success' => true,
    'message' => 'Cập nhật tài liệu thành công',
    'document_id' => $documentId,
    'file_replaced' => $newFilePath !== null,
    'new_url' => $newFilePath,
]);
exit;
