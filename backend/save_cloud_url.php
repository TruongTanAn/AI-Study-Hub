<?php
/**
 * save_cloud_url.php
 * Nhận cloud_url từ module Cloud Storage, cập nhật cho document tương ứng.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để cập nhật cloud URL.',
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.',
    ]);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$documentId = (int) ($_POST['document_id'] ?? 0);
$cloudUrl = trim($_POST['cloud_url'] ?? '');

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Document ID không hợp lệ.',
    ]);
    exit();
}

if ($cloudUrl === '' || !filter_var($cloudUrl, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Cloud URL không hợp lệ.',
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$sql = 'UPDATE documents
        SET cloud_url = ?
        WHERE document_id = ? AND user_id = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$stmt->bind_param('sii', $cloudUrl, $documentId, $userId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật cloud URL thành công.',
        'document_id' => $documentId,
        'cloud_url' => $cloudUrl,
    ]);
    exit();
}

$stmt->close();

echo json_encode([
    'success' => false,
    'message' => 'Không tìm thấy tài liệu hoặc cập nhật thất bại.',
]);
