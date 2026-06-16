<?php
/**
 * validate_profile.php
 * Validate dữ liệu cập nhật hồ sơ từ form, trả về kết quả JSON.
 */

header('Content-Type: application/json; charset=utf-8');

// Bắt đầu session nếu chưa tồn tại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để cập nhật hồ sơ.',
    ]);
    exit();
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.',
    ]);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate: họ tên không được rỗng
if ($fullName === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Họ tên không được để trống.',
    ]);
    exit();
}

// Validate: email đúng định dạng
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email không đúng định dạng.',
    ]);
    exit();
}

// Kết nối database thông qua config/database.php
require_once __DIR__ . '/../config/database.php';

// Validate: email không trùng với tài khoản khác
$sql = 'SELECT user_id FROM users WHERE email = ? AND user_id != ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
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
        'message' => 'Email đã được sử dụng bởi tài khoản khác.',
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Dữ liệu hợp lệ.',
]);
