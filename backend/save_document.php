<?php
/**
 * save_document.php
 * Nhận thông tin file sau upload thành công, lưu metadata vào bảng documents.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để lưu tài liệu.',
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
$title = trim($_POST['title'] ?? '');
$fileName = trim($_POST['file_name'] ?? '');
$filePath = trim($_POST['file_path'] ?? '');
$fileType = trim($_POST['file_type'] ?? '');
$fileSize = (int) ($_POST['file_size'] ?? 0);
$cloudUrl = trim($_POST['cloud_url'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($title === '' || $fileName === '' || $filePath === '') {
    $_SESSION['upload_status'] = 'failed';
    $_SESSION['upload_message'] = 'Thông tin tài liệu không đầy đủ.';

    echo json_encode([
        'success' => false,
        'message' => 'Thông tin tài liệu không đầy đủ.',
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$sql = 'INSERT INTO documents
        (user_id, title, description, file_name, file_path, file_type, file_size, cloud_url, upload_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['upload_status'] = 'failed';
    $_SESSION['upload_message'] = 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.';

    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$stmt->bind_param(
    'isssssis',
    $userId,
    $title,
    $description,
    $fileName,
    $filePath,
    $fileType,
    $fileSize,
    $cloudUrl
);

if ($stmt->execute()) {
    $documentId = (int) $stmt->insert_id;
    $stmt->close();

    $_SESSION['upload_status'] = 'success';
    $_SESSION['upload_message'] = 'Lưu tài liệu thành công.';
    $_SESSION['document_id'] = $documentId;

    echo json_encode([
        'success' => true,
        'document_id' => $documentId,
        'message' => 'Lưu tài liệu thành công.',
    ]);
    exit();
}

$stmt->close();
$_SESSION['upload_status'] = 'failed';
$_SESSION['upload_message'] = 'Lưu tài liệu thất bại.';

echo json_encode([
    'success' => false,
    'message' => 'Lưu tài liệu thất bại.',
]);
