<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

$userId = $_SESSION['user_id'];

$sql = "SELECT user_id, full_name, email, avatar, role, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Không tìm thấy người dùng.");
}

$avatarPath = '../assets/images/default-avatar.png';
if (!empty($user['avatar']) && $user['avatar'] !== 'default.png') {
    $uploadedPath = '../uploads/avatars/' . $user['avatar'];
    if (file_exists($uploadedPath)) {
        $avatarPath = $uploadedPath;
    }
}

$memberSince = date('d/m/Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - AI Study Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            text-decoration: none;
        }
        .dashboard-header a:hover {
            background: rgba(255,255,255,0.3);
        }
        .dashboard-header .logo a {
            background: transparent;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .dashboard-header .logo a:hover {
            background: transparent;
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
            <a href="edit_profile.php"><i class="fas fa-edit"></i> Chỉnh sửa</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-cover"></div>
            
            <div class="profile-details">
                <div class="avatar-container">
                    <img src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="profile-avatar">
                </div>
                
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="subtitle"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <span class="role-badge <?php echo $user['role']; ?>">
                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'cog' : 'user'; ?>"></i>
                        <?php echo $user['role'] === 'admin' ? 'Quản trị viên' : 'Người dùng'; ?>
                    </span>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="stat-info">
                        <span class="stat-label">Tham gia từ</span>
                        <span class="stat-value"><?php echo $memberSince; ?></span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-file-alt"></i>
                    <div class="stat-info">
                        <span class="stat-label">Tài liệu đã tải lên</span>
                        <span class="stat-value" id="document-count">-</span>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <a href="edit_profile.php" class="btn-profile-edit">
                    <i class="fas fa-edit"></i> Chỉnh sửa hồ sơ
                </a>
                <a href="documents.php" class="btn-profile-secondary">
                    <i class="fas fa-folder-open"></i> Tài liệu của tôi
                </a>
            </div>
        </div>
    </div>

    <script>
        async function loadStats() {
            try {
                const response = await fetch('../backend/upload_status.php');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('document-count').textContent = data.count || 0;
                }
            } catch (e) {
                document.getElementById('document-count').textContent = '0';
            }
        }
        loadStats();
    </script>
</body>
</html>
