<?php
/**
 * AI Study Hub - Edit Profile Process
 * Validates inputs, verifies passwords, handles avatar uploads, and updates DB.
 */

// 1. Verify Authentication
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/edit_profile.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get parameters
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

// Initial Validation
if ($fullName === '' || $email === '' || $currentPassword === '') {
    $_SESSION['error_message'] = 'Vui lòng điền đầy đủ các thông tin bắt buộc (họ tên, email, mật khẩu hiện tại).';
    header('Location: ../pages/edit_profile.php');
    exit();
}

if (strlen($fullName) < 2) {
    $_SESSION['error_message'] = 'Họ tên quá ngắn (tối thiểu phải có 2 ký tự).';
    header('Location: ../pages/edit_profile.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Địa chỉ Email không đúng định dạng.';
    header('Location: ../pages/edit_profile.php');
    exit();
}

// 2. Fetch User Password from Database to verify current password
$sql = "SELECT password, avatar FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userResult || !password_verify($currentPassword, $userResult['password'])) {
    $_SESSION['error_message'] = 'Mật khẩu hiện tại không chính xác. Không thể cập nhật thông tin.';
    header('Location: ../pages/edit_profile.php');
    exit();
}

$currentAvatar = $userResult['avatar'];

// 3. Verify Email uniqueness if changed
$sqlEmail = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
$stmtEmail = $conn->prepare($sqlEmail);
$stmtEmail->bind_param('si', $email, $userId);
$stmtEmail->execute();
$emailResult = $stmtEmail->get_result();
if ($emailResult->num_rows > 0) {
    $_SESSION['error_message'] = 'Địa chỉ Email này đã được sử dụng bởi một tài khoản khác.';
    $stmtEmail->close();
    header('Location: ../pages/edit_profile.php');
    exit();
}
$stmtEmail->close();

// 4. Handle Avatar Upload
$newAvatarName = $currentAvatar; // default to old avatar
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['avatar']['tmp_name'];
    $fileName = $_FILES['avatar']['name'];
    $fileSize = $_FILES['avatar']['size'];
    $fileType = $_FILES['avatar']['type'];
    
    // Extensions
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
    if (in_array($fileExtension, $allowedExtensions)) {
        // Limit to 2MB
        $maxSize = 2 * 1024 * 1024;
        if ($fileSize <= $maxSize) {
            // Upload directory
            $uploadFileDir = '../uploads/avatars/';
            
            // Create folder if it doesn't exist
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            // Unique file name to prevent collision
            $newAvatarName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newAvatarName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete previous avatar file if not default
                if ($currentAvatar !== 'default.png' && !empty($currentAvatar)) {
                    $oldAvatarPath = $uploadFileDir . $currentAvatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
            } else {
                $_SESSION['error_message'] = 'Đã có lỗi xảy ra khi di chuyển file tải lên vào thư mục lưu trữ.';
                header('Location: ../pages/edit_profile.php');
                exit();
            }
        } else {
            $_SESSION['error_message'] = 'Dung lượng ảnh đại diện tối đa là 2MB.';
            header('Location: ../pages/edit_profile.php');
            exit();
        }
    } else {
        $_SESSION['error_message'] = 'Định dạng file không hợp lệ. Chỉ chấp nhận các đuôi: JPG, JPEG, PNG, GIF.';
        header('Location: ../pages/edit_profile.php');
        exit();
    }
}

// 5. Check if Password is being changed
$passwordUpdated = false;
$passwordHash = '';
if ($newPassword !== '') {
    if (strlen($newPassword) < 6) {
        $_SESSION['error_message'] = 'Mật khẩu mới phải từ 6 ký tự trở lên.';
        header('Location: ../pages/edit_profile.php');
        exit();
    }
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $passwordUpdated = true;
}

// 6. Update user info in DB
if ($passwordUpdated) {
    $sqlUpdate = "UPDATE users SET full_name = ?, email = ?, avatar = ?, password = ? WHERE user_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('ssssi', $fullName, $email, $newAvatarName, $passwordHash, $userId);
} else {
    $sqlUpdate = "UPDATE users SET full_name = ?, email = ?, avatar = ? WHERE user_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('sssi', $fullName, $email, $newAvatarName, $userId);
}

if ($stmtUpdate->execute()) {
    // Sync session data
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    
    $_SESSION['success_message'] = 'Cập nhật thông tin cá nhân thành công!';
    $stmtUpdate->close();
    header('Location: ../pages/profile.php');
    exit();
} else {
    $_SESSION['error_message'] = 'Lỗi hệ thống: Không thể cập nhật thông tin trong database.';
    $stmtUpdate->close();
    header('Location: ../pages/edit_profile.php');
    exit();
}
