<?php
/**
 * document_detail.php
 * Lấy thông tin chi tiết tài liệu theo document_id, trả về JSON.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để xem chi tiết tài liệu.',
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

$sql = 'SELECT d.document_id, d.user_id, d.subject_id, d.category_id, d.title, d.description,
               d.file_name, d.original_name, d.file_path, d.file_type, d.file_size,
               d.visibility, d.downloads_count, d.status, d.created_at, d.updated_at,
               u.full_name AS uploader_name
        FROM documents d
        INNER JOIN users u ON d.user_id = u.user_id
        WHERE d.document_id = ?';
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

$isAdmin = ($userRole === 'admin');
$canView = false;

if ($isOwner || $isAdmin) {
    $canView = true;
} elseif ($documentData['status'] === 'approved') {
    if ($documentData['visibility'] === 'public' || $documentData['visibility'] === 'shared') {
        $canView = true;
    }
}

if (!$canView) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền xem tài liệu này.',
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $documentData,
]);
