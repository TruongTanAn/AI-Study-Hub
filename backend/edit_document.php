<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ban can dang nhap de chinh sua tai lieu.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$documentId = (int)($_GET['document_id'] ?? $_POST['document_id'] ?? 0);

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Document ID khong hop le.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = (int)$_SESSION['user_id'];

$sql = 'SELECT document_id, user_id, subject_id, category_id, title, description,
               file_name, original_name, file_path, file_type, file_size,
               visibility, downloads_count, status, created_at, updated_at
        FROM documents
        WHERE document_id = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Khong the ket noi du lieu. Vui long thu lai sau.'
    ], JSON_UNESCAPED_UNICODE);
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
        'message' => 'Khong tim thay tai lieu.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// FIX: Force UTF-8 on every DB string
$documentData['title'] = to_utf8($documentData['title']);
$documentData['description'] = to_utf8($documentData['description'] ?? '');
$documentData['file_name'] = to_utf8($documentData['file_name']);
$documentData['original_name'] = to_utf8($documentData['original_name']);

$isOwner = ((int)$documentData['user_id'] === $userId);
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
        'message' => 'Ban khong co quyen chinh sua tai lieu nay.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $documentData
], JSON_UNESCAPED_UNICODE);
