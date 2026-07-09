<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ban can dang nhap de xem chi tiet tai lieu.'
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
$documentData['uploader_name'] = to_utf8($documentData['uploader_name']);

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
        'message' => 'Ban khong co quyen xem tai lieu nay.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $documentData
], JSON_UNESCAPED_UNICODE);
