<?php
/**
 * edit_document.php
 * Lấy toàn bộ thông tin hiện tại của tài liệu để phục vụ chỉnh sửa, trả về JSON.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để chỉnh sửa tài liệu.',
    ]);
    exit();
}

$documentId = (int) ($_GET['document_id'] ?? $_POST['document_id'] ?? 0);

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Document ID không hợp lệ.',
    ]);
    exit();
}

$userId = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../config/database.php';

$sql = 'SELECT document_id, user_id, subject_id, category_id, title, description,
               file_name, original_name, file_path, file_type, file_size,
               visibility, downloads_count, status, created_at, updated_at
        FROM documents
        WHERE document_id = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$stmt->bind_param('i', $documentId);
$stmt->execute();
$result = $stmt->get_result();
$documentData = $result->fetch_assoc();
$stmt->close();

if (!$documentData) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy tài liệu.',
    ]);
    exit();
}

$isOwner = ((int) $documentData['user_id'] === $userId);
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
        'message' => 'Bạn không có quyền chỉnh sửa tài liệu này.',
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $documentData,
]);
