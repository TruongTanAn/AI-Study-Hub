<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit();
}

session_start();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error_message'] = 'Email và mật khẩu không được để trống.';
    header('Location: ../pages/login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

$sql = 'SELECT user_id, full_name, email, password FROM users WHERE email = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error_message'] = 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.';
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
    $_SESSION['full_name'] = $userData['full_name'];
    $_SESSION['email'] = $userData['email'];

    header('Location: ../pages/dashboard.php');
    exit();
}

$_SESSION['error_message'] = 'Email hoặc mật khẩu không chính xác.';
header('Location: ../pages/login.php');
exit();
