<?php
/**
 * get_profile.php
 * Lấy thông tin hồ sơ người dùng hiện tại từ session, trả về JSON.
 */

header('Content-Type: application/json; charset=utf-8');

// Bắt đầu session nếu chưa tồn tại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập — chưa đăng nhập trả về lỗi JSON
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để xem hồ sơ.',
    ]);
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Kết nối database thông qua config/database.php
require_once __DIR__ . '/../config/database.php';

// Lấy thông tin user theo user_id (chỉ các trường cần thiết, không lấy password)
$sql = 'SELECT user_id, full_name, email, avatar, role, status, created_at
        FROM users
        WHERE user_id = ?';
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối dữ liệu. Vui lòng thử lại sau.',
    ]);
    exit();
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Không tìm thấy user trong database
if (!$userData) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy thông tin người dùng.',
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $userData,
]);
