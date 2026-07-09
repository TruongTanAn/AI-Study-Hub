<?php
/**
 * load_chat_history_paged.php
 * Tối ưu hóa Database API bằng cách xử lý phân trang (Pagination) lịch sử trò chuyện.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra trạng thái đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để xem lịch sử trò chuyện.'
    ]);
    exit();
}

// 2. Nhúng file kết nối cơ sở dữ liệu và khai báo global
require_once __DIR__ . '/../config/database.php';
global $conn;

if (!isset($conn)) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối đến cơ sở dữ liệu hệ thống.'
    ]);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// 3. Nhận các tham số phân trang từ GET Request
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$page            = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit           = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

if ($conversation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID cuộc hội thoại không hợp lệ.'
    ]);
    exit();
}

if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 15;

// Tính toán vị trí dịch chuyển dữ liệu trong câu lệnh SQL
$offset = ($page - 1) * $limit;

try {
    // 4. Xác thực bảo mật: cuộc hội thoại này có phải của người dùng này không
    $check_stmt = $conn->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ? LIMIT 1");
    $check_stmt->execute([$conversation_id, $current_user_id]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền truy cập vào lịch sử cuộc trò chuyện này.'
        ]);
        exit();
    }

    // 5. Tính toán tổng số tin nhắn để chia trang
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ?");
    $count_stmt->execute([$conversation_id]);
    $total_messages = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_messages / $limit);

    // 6. Lấy danh sách tin nhắn theo phân trang
    $query = "SELECT id, sender, message, created_at 
              FROM chat_messages 
              WHERE conversation_id = :conv_id 
              ORDER BY created_at ASC 
              LIMIT :limit OFFSET :offset";
              
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':conv_id', $conversation_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Trả kết quả JSON tối ưu dữ liệu phản hồi
    echo json_encode([
        'success' => true,
        'data' => $messages,
        'pagination' => [
            'total_messages' => $total_messages,
            'total_pages'    => $total_pages,
            'current_page'   => $page,
            'limit'          => $limit
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn lịch sử chat: ' . $e->getMessage()
    ]);
}
exit();