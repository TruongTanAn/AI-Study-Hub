<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot_password.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($email)) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Email không hợp lệ'));
    exit();
}

if (empty($password)) {
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Vui lòng nhập mật khẩu'));
    exit();
}

if (strlen($password) < 6) {
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Mật khẩu phải có ít nhất 6 ký tự'));
    exit();
}

if ($password !== $confirm_password) {
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Mật khẩu xác nhận không khớp'));
    exit();
}

$sql = "SELECT email FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Email không tồn tại'));
    exit();
}

$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ? WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $passwordHash, $email);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: ../pages/login.php?reset=success');
    exit();
} else {
    $stmt->close();
    $conn->close();
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Có lỗi xảy ra, vui lòng thử lại'));
    exit();
}
