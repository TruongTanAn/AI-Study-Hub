<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ban can dang nhap de cap nhat ho so.'
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
$fullName = trim(to_utf8($_POST['full_name'] ?? ''));
$email = trim($_POST['email'] ?? '');

if ($fullName === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Ho ten khong duoc de trong.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email khong dung dinh dang.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$sql = 'SELECT user_id FROM users WHERE email = ? AND user_id != ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Khong the ket noi du lieu. Vui long thu lai sau.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->bind_param('si', $email, $userId);
$stmt->execute();
$result = $stmt->get_result();
$existingUser = $result->fetch_assoc();
$stmt->close();

if ($existingUser) {
    echo json_encode([
        'success' => false,
        'message' => 'Email da duoc su dung boi tai khoan khac.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Du lieu hop le.'
], JSON_UNESCAPED_UNICODE);
