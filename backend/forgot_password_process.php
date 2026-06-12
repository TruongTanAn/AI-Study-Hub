<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot_password.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Vui lòng nhập email'));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Email không hợp lệ'));
    exit();
}

$sql = "SELECT email FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Email không tồn tại trong hệ thống'));
    exit();
}

$stmt->close();
$conn->close();

header('Location: ../pages/reset_password.php?email=' . urlencode($email));
exit();
