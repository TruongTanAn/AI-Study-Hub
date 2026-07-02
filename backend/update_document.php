<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ban can dang nhap de cap nhat tai lieu.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phuong thuc yeu cau khong hop le.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$documentId = (int)($_POST['document_id'] ?? 0);
$title = trim(to_utf8($_POST['title'] ?? ''));
$description = trim(to_utf8($_POST['description'] ?? ''));
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== ''
    ? (int)$_POST['category_id']
    : null;
$subjectId = isset($_POST['subject_id']) && $_POST['subject_id'] !== ''
    ? (int)$_POST['subject_id']
    : null;
$visibility = trim($_POST['visibility'] ?? '');

if ($documentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Document ID khong hop le.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($title === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Tieu de khong duoc de trong.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (mb_strlen($title) > 255) {
    echo json_encode([
        'success' => false,
        'message' => 'Tieu de khong duoc vuot qua 255 ky tu.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$allowedVisibility = ['public', 'private', 'shared'];
if ($visibility !== '' && !in_array($visibility, $allowedVisibility, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Quyen hien thi khong hop le.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$checkSql = 'SELECT user_id, visibility FROM documents WHERE document_id = ?';
$checkStmt = $conn->prepare($checkSql);

if ($checkStmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Khong the ket noi du lieu. Vui long thu lai sau.'
    ], JSON_UNESCAPED_UNICODE);
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
        'message' => 'Khong tim thay tai lieu.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$isOwner = ((int)$existingDocument['user_id'] === $userId);
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
        'message' => 'Ban khong co quyen cap nhat tai lieu nay.'
    ], JSON_UNESCAPED_UNICODE);
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
                'message' => 'Danh muc khong ton tai.'
            ], JSON_UNESCAPED_UNICODE);
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
                'message' => 'Mon hoc khong ton tai.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
}

$updateSql = 'UPDATE documents
              SET title = ?, description = ?, category_id = ?, subject_id = ?, visibility = ?, updated_at = CURRENT_TIMESTAMP
              WHERE document_id = ?';
$updateStmt = $conn->prepare($updateSql);

if ($updateStmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Khong the ket noi du lieu. Vui long thu lai sau.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$updateStmt->bind_param('ssiisi', $title, $description, $categoryId, $subjectId, $visibility, $documentId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    echo json_encode([
        'success' => true,
        'message' => 'Cap nhat tai lieu thanh cong.',
        'document_id' => $documentId,
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$updateStmt->close();
echo json_encode([
    'success' => false,
    'message' => 'Cap nhat tai lieu that bai.'
], JSON_UNESCAPED_UNICODE);
