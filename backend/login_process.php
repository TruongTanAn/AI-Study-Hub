<?php
/**
 * login_process.php
 * Xử lý đăng nhập: nhận email và password từ form POST,
 * kiểm tra với database và thiết lập session nếu hợp lệ.
 */

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit();
}

// Lấy dữ liệu từ form đăng nhập
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Kiểm tra email và password không được rỗng
if ($email === '' || $password === '') {
    session_start();
    $_SESSION['error_message'] = 'Email và mật khẩu không được để trống.';
    header('Location: ../pages/login.php');
    exit();
}

// Kết nối database thông qua config/database.php
require_once __DIR__ . '/../config/database.php';

// Tìm user theo email (sử dụng prepared statement để bảo mật)
$sql = 'SELECT user_id, full_name, email, password FROM users WHERE email = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    session_start();
    $_SESSION['error_message'] = 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.';
    header('Location: ../pages/login.php');
    exit();
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Kiểm tra mật khẩu bằng password_verify()
if ($userData && password_verify($password, $userData['password'])) {
    // Đăng nhập thành công: tạo session và chuyển hướng đến dashboard
    session_start();
    $_SESSION['user_id'] = $userData['user_id'];
    $_SESSION['full_name'] = $userData['full_name'];
    $_SESSION['email'] = $userData['email'];

    header('Location: ../pages/dashboard.php');
    exit();
}

// Đăng nhập thất bại: lưu thông báo lỗi vào session
session_start();
$_SESSION['error_message'] = 'Email hoặc mật khẩu không chính xác.';
header('Location: ../pages/login.php');
exit();
