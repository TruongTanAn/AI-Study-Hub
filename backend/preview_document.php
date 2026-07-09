<?php
/**
 * preview_document.php
 * Lấy thông tin tài liệu để hiển thị Preview (PDF, DOCX, PPTX).
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra trạng thái đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để sử dụng chức năng xem trước.'
    ]);
    exit();
}

// 2. Nhúng file kết nối cơ sở dữ liệu
require_once __DIR__ . '/../config/database.php';

// Đảm bảo PHP nhận diện được biến $conn từ file database.php đổ vào
global $conn; 

if (!isset($conn)) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối đến cơ sở dữ liệu hệ thống.'
    ]);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// 3. Lấy ID tài liệu từ yêu cầu gửi lên
$document_id = isset($_REQUEST['document_id']) ? (int)$_REQUEST['document_id'] : 0;

if ($document_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Mã định danh tài liệu không hợp lệ.'
    ]);
    exit();
}

try {
    // 4. Truy vấn kiểm tra quyền sở hữu và lấy thông tin tệp
    $stmt = $conn->prepare("SELECT id, file_name, file_type, file_size, cloud_url FROM documents WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$document_id, $current_user_id]);
    $document = $stmt->fetch();

    if (!$document) {
        echo json_encode([
            'success' => false,
            'message' => 'Tài liệu không tồn tại hoặc bạn không có quyền xem trước tệp này.'
        ]);
        exit();
    }

    // 5. Trả về cấu trúc JSON phản hồi cho Frontend
    echo json_encode([
        'success' => true,
        'message' => 'Tải dữ liệu thành công.',
        'data' => [
            'id' => (int)$document['id'],
            'file_name' => $document['file_name'],
            'file_type' => $document['file_type'],
            'preview_url' => $document['cloud_url'] ?? ''
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống khi tải thông tin xem trước: ' . $e->getMessage()
    ]);
}
exit();