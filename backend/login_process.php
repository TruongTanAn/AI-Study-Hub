<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error_message'] = 'Email va mat khau khong duoc de trong.';
    header('Location: ../pages/login.php');
    exit();
}

$sql = 'SELECT user_id, full_name, email, password FROM users WHERE email = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error_message'] = 'Khong the ket noi du lieu. Vui long thu lai sau.';
    header('Location: ../pages/login.php');
    exit();
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if ($userData && password_verify($password, $userData['password'])) {
    $_SESSION['user_id'] = $userData['user_id'];
    $_SESSION['full_name'] = to_utf8($userData['full_name']);
    $_SESSION['email'] = $userData['email'];

    header('Location: ../pages/dashboard.php');
    exit();
}

$_SESSION['error_message'] = 'Email hoac mat khau khong chinh xac.';
header('Location: ../pages/login.php');
exit();
