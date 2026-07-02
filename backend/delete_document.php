<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Vui long dang nhap'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phuong thuc khong duoc ho tro'], JSON_UNESCAPED_UNICODE);
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

$document = $checkResult->fetch_assoc();

if ($document['user_id'] !== $userId && $userRole !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Ban khong co quyen xoa tai lieu nay'], JSON_UNESCAPED_UNICODE);
    exit;
}

$deleteStmt = $conn->prepare('DELETE FROM documents WHERE document_id = ?');
$deleteStmt->bind_param('i', $documentId);

if ($deleteStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Xoa tai lieu thanh cong'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'error' => 'Khong the xoa tai lieu'], JSON_UNESCAPED_UNICODE);
}

$deleteStmt->close();
$conn->close();
