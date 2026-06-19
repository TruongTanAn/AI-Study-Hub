-- =============================================
-- SỬA LỖI: Đổi id thành user_id trong bảng users
-- Chạy file này trong phpMyAdmin hoặc MySQL CLI
-- =============================================

-- Kiểm tra cấu trúc bảng users trước
DESCRIBE users;

-- Nếu bảng dùng `id`, đổi thành `user_id`
ALTER TABLE users CHANGE COLUMN id user_id INT AUTO_INCREMENT PRIMARY KEY;

-- =============================================
-- Sau khi sửa, chạy lại import database.sql nếu cần
-- =============================================
