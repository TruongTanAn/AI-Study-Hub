<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit();
}

$fullName = trim(to_utf8($_POST['full_name'] ?? ''));
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($fullName)) {
    $_SESSION['error_message'] = "Ho va ten khong duoc de trong.";
    header("Location: ../pages/register.php");
    exit();
}

if (mb_strlen($fullName) < 2) {
    $_SESSION['error_message'] = "Ho ten phai co it nhat 2 ky tu.";
    header("Location: ../pages/register.php");
    exit();
}

if (empty($email)) {
    $_SESSION['error_message'] = "Email khong duoc de trong.";
    header("Location: ../pages/register.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Email khong hop le.";
    header("Location: ../pages/register.php");
    exit();
}

if (empty($password)) {
    $_SESSION['error_message'] = "Mat khau khong duoc de trong.";
    header("Location: ../pages/register.php");
    exit();
}

if (mb_strlen($password) < 6) {
    $_SESSION['error_message'] = "Mat khau phai tu 6 ky tu tro len.";
    header("Location: ../pages/register.php");
    exit();
}

if ($password !== $confirmPassword) {
    $_SESSION['error_message'] = "Mat khau xac nhan khong khop.";
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
    $_SESSION['error_message'] = "Email da duoc su dung boi tai khoan khac.";
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
    header("Location: ../pages/login.php?success=" . urlencode("Dang ky thanh cong! Vui long dang nhap."));
    exit();
} else {
    $stmt->close();
    $conn->close();
    $_SESSION['error_message'] = "Dang ky that bai. Vui long thu lai.";
    header("Location: ../pages/register.php");
    exit();
}
