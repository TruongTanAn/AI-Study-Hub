<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/register.php");
    exit();
}

require_once "../config/database.php";

$fullName = trim($_POST["full_name"] ?? '');
$email = trim($_POST["email"] ?? '');
$password = $_POST["password"] ?? '';
$confirmPassword = $_POST["confirm_password"] ?? '';

if (empty($fullName)) {
    $_SESSION['error_message'] = "Họ và tên không được để trống.";
    header("Location: ../pages/register.php");
    exit();
}

if (strlen($fullName) < 2) {
    $_SESSION['error_message'] = "Họ tên phải có ít nhất 2 ký tự.";
    header("Location: ../pages/register.php");
    exit();
}

if (empty($email)) {
    $_SESSION['error_message'] = "Email không được để trống.";
    header("Location: ../pages/register.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Email không hợp lệ.";
    header("Location: ../pages/register.php");
    exit();
}

if (empty($password)) {
    $_SESSION['error_message'] = "Mật khẩu không được để trống.";
    header("Location: ../pages/register.php");
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error_message'] = "Mật khẩu phải từ 6 ký tự trở lên.";
    header("Location: ../pages/register.php");
    exit();
}

if ($password !== $confirmPassword) {
    $_SESSION['error_message'] = "Mật khẩu xác nhận không khớp.";
    header("Location: ../pages/register.php");
    exit();
}

$sql = "SELECT user_id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $_SESSION['error_message'] = "Email đã được sử dụng bởi tài khoản khác.";
    header("Location: ../pages/register.php");
    exit();
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $fullName, $email, $passwordHash);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../pages/login.php?success=" . urlencode("Đăng ký thành công! Vui lòng đăng nhập."));
    exit();
} else {
    $stmt->close();
    $conn->close();
    $_SESSION['error_message'] = "Đăng ký thất bại. Vui lòng thử lại.";
    header("Location: ../pages/register.php");
    exit();
}
