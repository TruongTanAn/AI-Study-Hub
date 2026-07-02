<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/save_document.php'; // contains incrementDownloadCount

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

// Fetch document details from DB
$stmt = $conn->prepare("
    SELECT document_id, user_id, title, file_name, original_name, file_path, file_type, file_size, visibility, status 
    FROM documents 
    WHERE document_id = ?
");

if (!$stmt) {
    header('Location: ../pages/documents.php?error=' . urlencode('Lỗi hệ thống: prepare failed'));
    exit;
}

$stmt->bind_param("i", $documentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: ../pages/documents.php?error=' . urlencode('Tài liệu không tồn tại'));
    exit;
}

$doc = $result->fetch_assoc();
$stmt->close();

// Permissions Check:
// If private, only the uploader or an admin can access
if ($doc['visibility'] === 'private' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: ../pages/documents.php?error=' . urlencode('Bạn không có quyền truy cập tài liệu này'));
    exit;
}

// If document is not approved (pending/rejected), only the owner or an admin can access
if ($doc['status'] !== 'approved' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: ../pages/documents.php?error=' . urlencode('Tài liệu này chưa được phê duyệt'));
    exit;
}

// Verify file existence on disk
// Since the DB file_path is stored as absolute server path, we should check if file exists.
// Fallback: If absolute path fails, check relative path
$filePath = $doc['file_path'];
if (!file_exists($filePath)) {
    // try relative path
    $relativeFilePath = __DIR__ . '/../uploads/documents/' . $doc['file_name'];
    if (file_exists($relativeFilePath)) {
        $filePath = $relativeFilePath;
    } else {
        header('Location: ../pages/documents.php?error=' . urlencode('File vật lý không tồn tại trên hệ thống'));
        exit;
    }
}

// Perform DB Updates
// 1. Increment downloads_count
incrementDownloadCount($documentId);

// 2. Log in download_history
$logStmt = $conn->prepare("INSERT INTO download_history (user_id, document_id) VALUES (?, ?)");
if ($logStmt) {
    $logStmt->bind_param("ii", $userId, $documentId);
    $logStmt->execute();
    $logStmt->close();
}

$conn->close();

// Map content types
$fileType = strtoupper($doc['file_type']);
$contentType = 'application/octet-stream';
if ($fileType === 'PDF') {
    $contentType = 'application/pdf';
} else if ($fileType === 'DOCX') {
    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
} else if ($fileType === 'PPTX') {
    $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
}

// Send appropriate download headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
// Use original name if available, fallback to file name
$downloadName = !empty($doc['original_name']) ? $doc['original_name'] : $doc['file_name'];
header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffering to avoid corruption
ob_clean();
flush();

// Read file stream
readfile($filePath);
exit;
