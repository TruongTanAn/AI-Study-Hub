<?php
session_start();
require_once '../includes/auth_check.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded without errors
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $fileSize = $_FILES['document']['size'];
        $fileType = $_FILES['document']['type'];
        
        // Define allowed extensions
        $allowedExts = ['pdf', 'doc', 'docx', 'txt'];
        
        // Get file extension
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Check if extension is allowed
        if (in_array($fileExtension, $allowedExts)) {
            
            // Limit file size to 10MB
            if ($fileSize < (10 * 1024 * 1024)) {
                
                // Set upload directory
                $uploadFileDir = '../uploads/';
                
                // Create directory if not exists
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                // Rename file to prevent duplicates
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;
                
                // Move file to destination
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Success, redirect back with success message
                    $message = urlencode("Tài liệu đã được tải lên thành công!");
                    header("Location: ../pages/upload.php?success=" . $message);
                    exit();
                } else {
                    $error = urlencode("Đã có lỗi xảy ra khi di chuyển file tải lên.");
                }
            } else {
                $error = urlencode("Kích thước file vượt quá 10MB.");
            }
        } else {
            $error = urlencode("Định dạng file không được hỗ trợ. Vui lòng tải lên PDF, DOC, DOCX hoặc TXT.");
        }
    } else {
        $error = urlencode("Lỗi khi tải file lên hoặc bạn chưa chọn file.");
    }
} else {
    $error = urlencode("Yêu cầu không hợp lệ.");
}

// Redirect back with error
header("Location: ../pages/upload.php?error=" . $error);
exit();
?>
