<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Vui long dang nhap de thuc hien tac vu nay'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phuong thuc request khong duoc ho tro'], JSON_UNESCAPED_UNICODE);
    exit;
}

$documentId = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;

if ($documentId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID tai lieu khong hop le'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

$checkStmt = $conn->prepare('SELECT user_id FROM documents WHERE document_id = ?');
$checkStmt->bind_param('i', $documentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Tai lieu khong ton tai'], JSON_UNESCAPED_UNICODE);
    exit;
}

$doc = $checkResult->fetch_assoc();

if ($doc['user_id'] !== $userId && $userRole !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Ban khong co quyen chinh sua tai lieu nay'], JSON_UNESCAPED_UNICODE);
    exit;
}

$title = isset($_POST['title']) ? trim(to_utf8($_POST['title'])) : '';
$description = isset($_POST['description']) ? trim(to_utf8($_POST['description'])) : '';
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
$subjectId = isset($_POST['subject_id']) && $_POST['subject_id'] !== '' ? intval($_POST['subject_id']) : null;
$visibility = isset($_POST['visibility']) ? trim($_POST['visibility']) : 'public';

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Tieu de tai lieu khong duoc de trong'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($title) > 255) {
    echo json_encode(['success' => false, 'error' => 'Tieu de qua dai (toi da 255 ky tu)'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($visibility, ['public', 'private', 'shared'])) {
    echo json_encode(['success' => false, 'error' => 'Quyen rieng tu khong hop le'], JSON_UNESCAPED_UNICODE);
    exit;
}

$updateStmt = $conn->prepare('UPDATE documents SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?, updated_at = CURRENT_TIMESTAMP WHERE document_id = ?');

if (!$updateStmt) {
    echo json_encode(['success' => false, 'error' => 'Loi he thong: Prepare update failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$updateStmt->bind_param('ssiisi', $title, $description, $categoryId, $subjectId, $visibility, $documentId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cap nhat thong tin tai lieu thanh cong!'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'error' => 'Khong the cap nhat tai lieu: ' . $updateStmt->error], JSON_UNESCAPED_UNICODE);
}

$updateStmt->close();
$conn->close();
exit;
