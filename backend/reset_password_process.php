<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($email)) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Email khong hop le'));
    exit();
}

if (empty($password)) {
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Vui long nhap mat khau'));
    exit();
}

if (mb_strlen($password) < 6) {
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Mat khau phai co it nhat 6 ky tu'));
    exit();
}

if ($password !== $confirm_password) {
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Mat khau xac nhan khong khop'));
    exit();
}

$sql = "SELECT email FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../pages/forgot_password.php?error=' . urlencode('Email khong ton tai'));
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
    header('Location: ../pages/reset_password.php?email=' . urlencode($email) . '&error=' . urlencode('Co loi xay ra, vui long thu lai'));
    exit();
}
