<?php
/**
 * AI Study Hub - Profile Page
 * Displays user profile, database stats, and link to edit page.
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Fetch the absolute newest data of user from database
$userId = $_SESSION['user_id'];
$sql = "SELECT full_name, email, avatar, role, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Không tìm thấy người dùng. Vui lòng đăng nhập lại.");
}

// Stats Query
// 1. Uploaded documents
$uploadCount = 0;
$sqlUpload = "SELECT COUNT(*) AS total FROM documents WHERE user_id = ?";
if ($stmtUpload = $conn->prepare($sqlUpload)) {
    $stmtUpload->bind_param('i', $userId);
    $stmtUpload->execute();
    $res = $stmtUpload->get_result()->fetch_assoc();
    $uploadCount = $res['total'];
    $stmtUpload->close();
}

// 2. Downloaded documents
$downloadCount = 0;
$sqlDownload = "SELECT COUNT(*) AS total FROM download_history WHERE user_id = ?";
if ($stmtDownload = $conn->prepare($sqlDownload)) {
    $stmtDownload->bind_param('i', $userId);
    $stmtDownload->execute();
    $res = $stmtDownload->get_result()->fetch_assoc();
    $downloadCount = $res['total'];
    $stmtDownload->close();
}

// 3. AI Chat questions
$chatCount = 0;
$sqlChat = "SELECT COUNT(*) AS total FROM chat_history WHERE user_id = ?";
if ($stmtChat = $conn->prepare($sqlChat)) {
    $stmtChat->bind_param('i', $userId);
    $stmtChat->execute();
    $res = $stmtChat->get_result()->fetch_assoc();
    $chatCount = $res['total'];
    $stmtChat->close();
}

// Format Join Date: "dd/mm/yyyy"
$joinDate = date('d/m/Y', strtotime($user['created_at']));

// Handle avatar fallback
$avatarPath = '../assets/images/default-avatar.png';
if (!empty($user['avatar']) && $user['avatar'] !== 'default.png') {
    // If the file exists on server, use it. Otherwise fall back to default
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
    <title>Trang cá nhân - AI Study Hub</title>
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            <span>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <div class="profile-container">
        <!-- Success/Error alert if redirected from process -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo htmlspecialchars($_SESSION['error_message']); 
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-details">
                <div class="avatar-container">
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="profile-avatar">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <div class="profile-email">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    
                    <span class="badge-role <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                        <i class="fas <?php echo $user['role'] === 'admin' ? 'fa-user-shield' : 'fa-user'; ?>"></i>
                        <?php echo $user['role'] === 'admin' ? 'Quản trị viên' : 'Học viên'; ?>
                    </span>

                    <p class="join-date">
                        <i class="fas fa-calendar-alt"></i> Thành viên từ: <strong><?php echo $joinDate; ?></strong>
                    </p>
                </div>

                <div class="profile-actions">
                    <a href="dashboard.php" class="btn-profile-back">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <a href="edit_profile.php" class="btn-profile-edit">
                        <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <h3 class="profile-stats-title">Thống kê hoạt động</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-file-arrow-up"></i>
                <div class="stat-number"><?php echo $uploadCount; ?></div>
                <div class="stat-label">Tài liệu đã tải lên</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-arrow-down"></i>
                <div class="stat-number"><?php echo $downloadCount; ?></div>
                <div class="stat-label">Tài liệu đã tải xuống</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-comments"></i>
                <div class="stat-number"><?php echo $chatCount; ?></div>
                <div class="stat-label">Số câu hỏi AI Chatbot</div>
            </div>
        </div>
    </div>
</body>
</html>
