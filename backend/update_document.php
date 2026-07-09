<?php
/**
 * update_document.php
 * Validate và cập nhật thông tin tài liệu, trả về JSON.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để cập nhật tài liệu.',
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
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== ''
    ? (int) $_POST['category_id']
    : null;
$subjectId = isset($_POST['subject_id']) && $_POST['subject_id'] !== ''
    ? (int) $_POST['subject_id']
    : null;
$visibility = trim($_POST['visibility'] ?? '');

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Document ID không hợp lệ.',
    ]);
    exit();
}

if ($title === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Tiêu đề không được để trống.',
    ]);
    exit();
}

if (strlen($title) > 255) {
    echo json_encode([
        'success' => false,
        'message' => 'Tiêu đề không được vượt quá 255 ký tự.',
    ]);
    exit();
}

$allowedVisibility = ['public', 'private', 'shared'];
if ($visibility !== '' && !in_array($visibility, $allowedVisibility, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Quyền hiển thị không hợp lệ.',
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$checkSql = 'SELECT user_id, visibility FROM documents WHERE document_id = ?';
$checkStmt = $conn->prepare($checkSql);

if ($checkStmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$checkStmt->bind_param('i', $documentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$existingDocument = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$existingDocument) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy tài liệu.',
    ]);
    exit();
}

$isOwner = ((int) $existingDocument['user_id'] === $userId);
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
        'message' => 'Bạn không có quyền cập nhật tài liệu này.',
    ]);
    exit();
}

if ($visibility === '') {
    $visibility = $existingDocument['visibility'];
}

if ($categoryId !== null && $categoryId > 0) {
    $categoryStmt = $conn->prepare('SELECT category_id FROM categories WHERE category_id = ?');
    if ($categoryStmt) {
        $categoryStmt->bind_param('i', $categoryId);
        $categoryStmt->execute();
        $categoryResult = $categoryStmt->get_result();
        $categoryExists = $categoryResult->fetch_assoc();
        $categoryStmt->close();

        if (!$categoryExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Danh mục không tồn tại.',
            ]);
            exit();
        }
    }
}

if ($subjectId !== null && $subjectId > 0) {
    $subjectStmt = $conn->prepare('SELECT subject_id FROM subjects WHERE subject_id = ?');
    if ($subjectStmt) {
        $subjectStmt->bind_param('i', $subjectId);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->get_result();
        $subjectExists = $subjectResult->fetch_assoc();
        $subjectStmt->close();

        if (!$subjectExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Môn học không tồn tại.',
            ]);
            exit();
        }
    }
}

$updateSql = 'UPDATE documents
              SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?
              WHERE document_id = ?';
$updateStmt = $conn->prepare($updateSql);

if ($updateStmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$updateStmt->bind_param(
    'ssiisi',
    $title,
    $description,
    $categoryId,
    $subjectId,
    $visibility,
    $documentId
);

if ($updateStmt->execute()) {
    $updateStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật tài liệu thành công.',
        'document_id' => $documentId,
    ]);
    exit();
}

$updateStmt->close();

echo json_encode([
    'success' => false,
    'message' => 'Cập nhật tài liệu thất bại.',
]);
