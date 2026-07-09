<?php
/**
 * delete_document.php
 * Xóa tài liệu khỏi database (chỉ cho phép chính chủ sở hữu tài liệu xóa).
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.'
    ]);
    exit();
}

require_once '../config/database.php';
$current_user_id = (int)$_SESSION['user_id'];

// 2. Lấy document_id từ request (hỗ trợ cả POST và GET để linh hoạt kết nối UI)
$document_id = isset($_REQUEST['document_id']) ? (int)$_REQUEST['document_id'] : 0;

if ($document_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID tài liệu không hợp lệ.'
    ]);
    exit();
}

try {
    // 3. Kiểm tra xem tài liệu có tồn tại và có thuộc quyền sở hữu của user này không (Phân quyền)
    $stmt = $conn->prepare("SELECT id, user_id, file_path FROM documents WHERE id = ? LIMIT 1");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();

    if (!$document) {
        echo json_encode([
            'success' => false,
            'message' => 'Tài liệu không tồn tại trên hệ thống.'
        ]);
        exit();
    }

    if ((int)$document['user_id'] !== $current_user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Bạn không có quyền xóa tài liệu của người khác!'
        ]);
        exit();
    }

    // 4. Tiến hành xóa trong Database
    $delete_stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
    $delete_stmt->execute([$document_id]);

    // 5. (Tùy chọn bổ sung) Xóa file vật lý trên server nếu lưu local để tránh rác bộ nhớ
    if (!empty($document['file_path']) && file_exists($document['file_path'])) {
        unlink($document['file_path']); 
    }

    echo json_encode([
        'success' => true,
        'message' => 'Xóa tài liệu thành công!'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống không thể xóa: ' . $e->getMessage()
    ]);
}
exit();