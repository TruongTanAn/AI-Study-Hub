<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Hết phiên làm việc.']);
    exit();
}

$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
$document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn rỗng.']);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Luồng chat hợp lệ.',
    'flow_context' => [
        'user_id' => (int)$_SESSION['user_id'],
        'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        'conversation_id' => $conversation_id,
        'document_id' => $document_id
    ]
]);
exit();