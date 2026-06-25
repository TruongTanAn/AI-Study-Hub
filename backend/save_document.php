<?php
<<<<<<< HEAD
=======
/**
 * save_document.php
 * Nhận thông tin file sau upload thành công, lưu metadata vào bảng documents.
 */

header('Content-Type: application/json; charset=utf-8');
>>>>>>> origin/kietnguyen-be

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

<<<<<<< HEAD
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validate_file.php';

function saveDocumentToDatabase($data) {
    global $conn;

    $required = ['user_id', 'title', 'file_name', 'file_path', 'file_type', 'mime_type', 'file_size'];

    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'error' => 'Thiếu thông tin bắt buộc: ' . $field
            ];
        }
    }

    $userId = intval($data['user_id']);
    $title = trim($data['title']);
    $description = isset($data['description']) ? trim($data['description']) : '';
    $fileName = trim($data['file_name']);
    $originalName = isset($data['original_name']) ? trim($data['original_name']) : $fileName;
    $filePath = trim($data['file_path']);
    $fileType = trim($data['file_type']);
    $mimeType = trim($data['mime_type']);
    $fileSize = intval($data['file_size']);

    $checkUser = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $checkUser->bind_param("i", $userId);
    $checkUser->execute();
    $userResult = $checkUser->get_result();

    if ($userResult->num_rows === 0) {
        return [
            'success' => false,
            'error' => 'Người dùng không tồn tại'
        ];
    }

    $stmt = $conn->prepare("
        INSERT INTO documents (
            user_id, title, description, file_name, original_name,
            file_path, file_type, mime_type, file_size, upload_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->bind_param(
        "isssssssi",
        $userId, $title, $description, $fileName, $originalName,
        $filePath, $fileType, $mimeType, $fileSize
    );

    if ($stmt->execute()) {
        $documentId = $conn->insert_id;

        if (isset($data['category_id']) && !empty($data['category_id'])) {
            $categoryId = intval($data['category_id']);
            $catStmt = $conn->prepare("
                INSERT INTO document_categories (document_id, category_id)
                VALUES (?, ?)
            ");
            $catStmt->bind_param("ii", $documentId, $categoryId);
            $catStmt->execute();
        }

        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $catInsert = $conn->prepare("
                INSERT INTO document_categories (document_id, category_id)
                VALUES (?, ?)
            ");
            foreach ($data['category_ids'] as $categoryId) {
                $catId = intval($categoryId);
                $catInsert->bind_param("ii", $documentId, $catId);
                $catInsert->execute();
            }
        }

        return [
            'success' => true,
            'document_id' => $documentId,
            'message' => 'Lưu document thành công'
        ];
    }

    return [
        'success' => false,
        'error' => 'Không thể lưu document: ' . $stmt->error
    ];
}

function updateDocumentStatus($documentId, $status, $cloudUrl = null) {
    global $conn;

    $documentId = intval($documentId);

    if (!in_array($status, ['pending', 'uploading', 'uploaded', 'failed'])) {
        return [
            'success' => false,
            'error' => 'Trạng thái không hợp lệ'
        ];
    }

    if ($cloudUrl !== null) {
        $stmt = $conn->prepare("
            UPDATE documents
            SET upload_status = ?, cloud_url = ?, updated_at = CURRENT_TIMESTAMP
            WHERE document_id = ?
        ");
        $stmt->bind_param("ssi", $status, $cloudUrl, $documentId);
    } else {
        $stmt = $conn->prepare("
            UPDATE documents
            SET upload_status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE document_id = ?
        ");
        $stmt->bind_param("si", $status, $documentId);
    }

    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công'
        ];
    }

    return [
        'success' => false,
        'error' => 'Không thể cập nhật trạng thái: ' . $stmt->error
    ];
}

function getDocumentById($documentId) {
    global $conn;

    $documentId = intval($documentId);

    $stmt = $conn->prepare("
        SELECT d.*, u.full_name as uploader_name
        FROM documents d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.document_id = ?
    ");
    $stmt->bind_param("i", $documentId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

function getUserDocuments($userId, $status = null) {
    global $conn;

    $userId = intval($userId);

    if ($status !== null) {
        $stmt = $conn->prepare("
            SELECT * FROM documents
            WHERE user_id = ? AND upload_status = ?
            ORDER BY upload_date DESC
        ");
        $stmt->bind_param("is", $userId, $status);
    } else {
        $stmt = $conn->prepare("
            SELECT * FROM documents
            WHERE user_id = ?
            ORDER BY upload_date DESC
        ");
        $stmt->bind_param("i", $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    return $documents;
}

function deleteDocument($documentId, $userId = null) {
    global $conn;

    $documentId = intval($documentId);

    if ($userId !== null) {
        $userId = intval($userId);
        $checkStmt = $conn->prepare("SELECT document_id, file_path, cloud_url, cloud_provider FROM documents WHERE document_id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $documentId, $userId);
    } else {
        $checkStmt = $conn->prepare("SELECT document_id, file_path, cloud_url, cloud_provider FROM documents WHERE document_id = ?");
        $checkStmt->bind_param("i", $documentId);
    }

    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        return [
            'success' => false,
            'error' => 'Document không tồn tại hoặc bạn không có quyền xóa'
        ];
    }

    $document = $checkResult->fetch_assoc();

    $deleteCats = $conn->prepare("DELETE FROM document_categories WHERE document_id = ?");
    $deleteCats->bind_param("i", $documentId);
    $deleteCats->execute();

    $deleteDoc = $conn->prepare("DELETE FROM documents WHERE document_id = ?");
    $deleteDoc->bind_param("i", $documentId);

    if ($deleteDoc->execute()) {
        require_once __DIR__ . '/../config/cloud_storage.php';

        CloudStorage::delete($document['file_path'], $document['cloud_provider']);

        if (!empty($document['cloud_url']) && $document['cloud_provider'] !== 'local') {
            CloudStorage::delete($document['cloud_path'], $document['cloud_provider']);
        }

        return [
            'success' => true,
            'message' => 'Xóa document thành công'
        ];
    }

    return [
        'success' => false,
        'error' => 'Không thể xóa document: ' . $deleteDoc->error
    ];
}
=======
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để lưu tài liệu.',
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

$userId = (int) $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$fileName = trim($_POST['file_name'] ?? '');
$filePath = trim($_POST['file_path'] ?? '');
$fileType = trim($_POST['file_type'] ?? '');
$fileSize = (int) ($_POST['file_size'] ?? 0);
$cloudUrl = trim($_POST['cloud_url'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($title === '' || $fileName === '' || $filePath === '') {
    $_SESSION['upload_status'] = 'failed';
    $_SESSION['upload_message'] = 'Thông tin tài liệu không đầy đủ.';

    echo json_encode([
        'success' => false,
        'message' => 'Thông tin tài liệu không đầy đủ.',
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$sql = 'INSERT INTO documents
        (user_id, title, description, file_name, file_path, file_type, file_size, cloud_url, upload_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['upload_status'] = 'failed';
    $_SESSION['upload_message'] = 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.';

    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$stmt->bind_param(
    'isssssis',
    $userId,
    $title,
    $description,
    $fileName,
    $filePath,
    $fileType,
    $fileSize,
    $cloudUrl
);

if ($stmt->execute()) {
    $documentId = (int) $stmt->insert_id;
    $stmt->close();

    $_SESSION['upload_status'] = 'success';
    $_SESSION['upload_message'] = 'Lưu tài liệu thành công.';
    $_SESSION['document_id'] = $documentId;

    echo json_encode([
        'success' => true,
        'document_id' => $documentId,
        'message' => 'Lưu tài liệu thành công.',
    ]);
    exit();
}

$stmt->close();
$_SESSION['upload_status'] = 'failed';
$_SESSION['upload_message'] = 'Lưu tài liệu thất bại.';

echo json_encode([
    'success' => false,
    'message' => 'Lưu tài liệu thất bại.',
]);
>>>>>>> origin/kietnguyen-be
