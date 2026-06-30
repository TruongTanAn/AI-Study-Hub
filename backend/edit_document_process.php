<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng đăng nhập để thực hiện tác vụ này'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Phương thức request không được hỗ trợ'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

// Extract and sanitize inputs
$documentId = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
$subjectId = isset($_POST['subject_id']) && $_POST['subject_id'] !== '' ? intval($_POST['subject_id']) : null;
$visibility = isset($_POST['visibility']) ? trim($_POST['visibility']) : 'public';

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'ID tài liệu không hợp lệ'
    ]);
    exit;
}

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

if (!in_array($visibility, ['public', 'private', 'shared'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Quyền riêng tư không hợp lệ'
    ]);
    exit;
}

// Fetch document details to verify permissions
$checkStmt = $conn->prepare("SELECT user_id FROM documents WHERE document_id = ?");
if (!$checkStmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi hệ thống: Prepare check failed'
    ]);
    exit;
}

$checkStmt->bind_param("i", $documentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    $checkStmt->close();
    echo json_encode([
        'success' => false,
        'error' => 'Tài liệu không tồn tại'
    ]);
    exit;
}

$doc = $checkResult->fetch_assoc();
$checkStmt->close();

// Owner or Admin check
if ($doc['user_id'] !== $userId && $userRole !== 'admin') {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền chỉnh sửa tài liệu này'
    ]);
    exit;
}

// Perform update query
$updateStmt = $conn->prepare("
    UPDATE documents 
    SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?
    WHERE document_id = ?
");

if (!$updateStmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi hệ thống: Prepare update failed'
    ]);
    exit;
}

$updateStmt->bind_param("ssiisi", $title, $description, $categoryId, $subjectId, $visibility, $documentId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    $conn->close();
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thông tin tài liệu thành công!'
    ]);
} else {
    $error = $updateStmt->error;
    $updateStmt->close();
    $conn->close();
    echo json_encode([
        'success' => false,
        'error' => 'Không thể cập nhật tài liệu: ' . $error
    ]);
}
exit;
