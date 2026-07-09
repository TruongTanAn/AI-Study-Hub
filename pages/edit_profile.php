<?php
/**
 * AI Study Hub - Edit Profile Page
 * Provides form to update profile information and change password.
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Fetch the newest data of user
$userId = $_SESSION['user_id'];
$sql = "SELECT full_name, email, avatar FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Không tìm thấy người dùng. Vui lòng đăng nhập lại.");
}

// Handle avatar fallback
$avatarPath = '../assets/images/default-avatar.png';
if (!empty($user['avatar']) && $user['avatar'] !== 'default.png') {
    $uploadedPath = '../uploads/avatars/' . $user['avatar'];
    if (file_exists($uploadedPath)) {
        $avatarPath = $uploadedPath;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa trang cá nhân - AI Study Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="../assets/js/profile.js" defer></script>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .dashboard-header a {
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255,255,255,0.2);
            transition: var(--transition);
        }
        .dashboard-header a:hover {
            background: rgba(255,255,255,0.3);
        }
        .dashboard-header .logo {
            color: white;
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">
            <a href="dashboard.php"><i class="fas fa-brain"></i> AI Study Hub</a>
        </div>
        <div class="user-info">
            <span>Xin chào, <strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
            <a href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <div class="profile-container">
        <!-- Error alerts -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['error_message'] ?? '', ENT_QUOTES, 'UTF-8'); 
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['success_message'] ?? '', ENT_QUOTES, 'UTF-8'); 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form Card -->
        <div class="profile-card">
            <div class="profile-cover"></div>
            
            <form id="profile-edit-form" action="../backend/edit_profile_process.php" method="POST" enctype="multipart/form-data">
                <div class="profile-details">
                    <!-- Avatar section with click-to-upload overlay -->
                    <div class="avatar-container">
                        <img src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="profile-avatar" id="avatar-preview">
                        <label for="avatar-input" class="avatar-upload-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Chọn ảnh</span>
                        </label>
                        <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display: none;">
                    </div>
                    
                    <div class="profile-info" style="margin-bottom: 25px;">
                        <h2>Chỉnh sửa thông tin cá nhân</h2>
                        <p class="subtitle" style="color: var(--gray);">Cập nhật ảnh đại diện, họ tên, email hoặc thay đổi mật khẩu của bạn.</p>
                    </div>
                </div>

                <div class="form-card-body">
                    <div class="form-grid">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label for="full_name">Họ và tên <span style="color: var(--danger);">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Địa chỉ Email <span style="color: var(--danger);">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>

                        <!-- Divider for password changes -->
                        <div class="form-divider"></div>

                        <!-- New Password -->
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key"></i>
                                <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới">
                                <span class="toggle-password" onclick="togglePasswordVisibility('new_password', 'new-eye')">
                                    <i class="fas fa-eye" id="new-eye"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key"></i>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới">
                                <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', 'confirm-eye')">
                                    <i class="fas fa-eye" id="confirm-eye"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Verification Current Password -->
                        <div class="form-group full-width" style="margin-top: 15px;">
                            <label for="current_password">Mật khẩu hiện tại <span style="color: var(--danger);">*</span></label>
                            <span style="font-size: 0.85rem; color: var(--gray); margin-bottom: 5px; display: inline-block;">
                                (Cần thiết để xác nhận bất kỳ thay đổi nào bên trên)
                            </span>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại để xác nhận thay đổi" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('current_password', 'current-eye')">
                                    <i class="fas fa-eye" id="current-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="profile-actions" style="margin-top: 40px; justify-content: flex-end;">
                        <a href="profile.php" class="btn-profile-back">
                            Hủy bỏ
                        </a>
                        <button type="submit" class="btn-profile-edit">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
