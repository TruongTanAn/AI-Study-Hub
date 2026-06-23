<?php
/**
 * upload_status.php
 * Trả trạng thái upload (success / failed / pending) cho Frontend.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'status' => 'failed',
        'message' => 'Bạn cần đăng nhập để kiểm tra trạng thái upload.',
    ]);
    exit();
}

$uploadStatus = $_SESSION['upload_status'] ?? 'pending';
$uploadMessage = $_SESSION['upload_message'] ?? 'Đang xử lý upload.';
$documentId = isset($_SESSION['document_id']) ? (int) $_SESSION['document_id'] : null;

$allowedStatus = ['success', 'failed', 'pending'];
if (!in_array($uploadStatus, $allowedStatus, true)) {
    $uploadStatus = 'pending';
}

$response = [
    'success' => true,
    'status' => $uploadStatus,
    'message' => $uploadMessage,
];

if ($documentId !== null) {
    $response['document_id'] = $documentId;
}

echo json_encode($response);
