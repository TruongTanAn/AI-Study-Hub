<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php?error=' . urlencode('Vui lòng đăng nhập để tải tài liệu'));
    exit;
}

$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($documentId <= 0) {
    header('Location: ../pages/documents.php?error=' . urlencode('ID tài liệu không hợp lệ'));
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

$stmt = $conn->prepare('SELECT document_id, user_id, title, file_name, file_path, file_type, file_size, visibility, status FROM documents WHERE document_id = ?');
$stmt->bind_param('i', $documentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../pages/documents.php?error=' . urlencode('Tài liệu không tồn tại'));
    exit;
}

$doc = $result->fetch_assoc();
$stmt->close();

if ($doc['visibility'] === 'private' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: ../pages/documents.php?error=' . urlencode('Bạn không có quyền truy cập tài liệu này'));
    exit;
}

if ($doc['status'] !== 'approved' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: ../pages/documents.php?error=' . urlencode('Tài liệu này chưa được phê duyệt'));
    exit;
}

$filePath = $doc['file_path'];
if (!file_exists($filePath)) {
    $relativeFilePath = __DIR__ . '/../uploads/documents/' . $doc['file_name'];
    if (file_exists($relativeFilePath)) {
        $filePath = $relativeFilePath;
    } else {
        header('Location: ../pages/documents.php?error=' . urlencode('File vật lý không tồn tại trên hệ thống'));
        exit;
    }
}

$logStmt = $conn->prepare('INSERT INTO download_history (user_id, document_id) VALUES (?, ?)');
if ($logStmt) {
    $logStmt->bind_param('ii', $userId, $documentId);
    $logStmt->execute();
    $logStmt->close();
}

$countStmt = $conn->prepare('UPDATE documents SET downloads_count = downloads_count + 1 WHERE document_id = ?');
if ($countStmt) {
    $countStmt->bind_param('i', $documentId);
    $countStmt->execute();
    $countStmt->close();
}

$conn->close();

$fileType = strtoupper($doc['file_type']);
$contentType = 'application/octet-stream';
if ($fileType === 'PDF') {
    $contentType = 'application/pdf';
} elseif ($fileType === 'DOCX') {
    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
} elseif ($fileType === 'PPTX') {
    $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
}

$downloadName = !empty($doc['file_name']) ? $doc['file_name'] : 'document';
header('Content-Type: text/html; charset=utf-8');
header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

ob_clean();
flush();
readfile($filePath);
exit;
