<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ban can dang nhap de xem ho so.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$userId = (int)$_SESSION['user_id'];

$sql = 'SELECT user_id, full_name, email, avatar, role, status, created_at
        FROM users
        WHERE user_id = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Khong the ket noi du lieu. Vui long thu lai sau.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if (!$userData) {
    echo json_encode([
        'success' => false,
        'message' => 'Khong tim thay thong tin nguoi dung.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// FIX: Force UTF-8 on DB strings
$userData['full_name'] = to_utf8($userData['full_name']);
$userData['email'] = to_utf8($userData['email']);
$userData['avatar'] = to_utf8($userData['avatar'] ?? '');

echo json_encode([
    'success' => true,
    'data' => $userData
], JSON_UNESCAPED_UNICODE);
