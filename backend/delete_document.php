<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/save_document.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'success' => false,
            'error' => 'Phương thức không được hỗ trợ'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $documentId = isset($input['document_id']) ? intval($input['document_id']) : 0;
} else {
    $documentId = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
}

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'ID tài liệu không hợp lệ'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

$checkStmt = $conn->prepare("SELECT user_id FROM documents WHERE document_id = ?");
$checkStmt->bind_param("i", $documentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Tài liệu không tồn tại'
    ]);
    exit;
}

$document = $checkResult->fetch_assoc();

if ($document['user_id'] !== $userId && $userRole !== 'admin') {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn không có quyền xóa tài liệu này'
    ]);
    exit;
}

$result = deleteDocument($documentId, $userId);

echo json_encode($result);
