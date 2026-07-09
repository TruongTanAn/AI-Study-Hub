<?php
require_once '../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AI Study Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
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
        .dashboard-header .admin-link {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: white !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }
        .dashboard-header .admin-link:hover {
            background: linear-gradient(135deg, #d97706, #b45309) !important;
            transform: translateY(-1px);
        }
        .dashboard-header .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: rgba(245, 158, 11, 0.95);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .dashboard-content {
            padding: 60px 0;
        }
        .dashboard-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            color: var(--dark);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .dashboard-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        .dashboard-card:hover {
            transform: translateY(-10px);
        }
        .dashboard-card i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        .dashboard-card h3 {
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        .dashboard-card p {
            color: var(--gray);
            margin-bottom: 25px;
        }
        .dashboard-card a {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
        }
        .dashboard-card a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        @media(max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media(max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">
            <i class="fas fa-brain"></i> AI Study Hub
        </div>
        <div class="user-info">
<<<<<<< HEAD
            <span>
                Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <span class="admin-badge" title="Tài khoản có quyền quản trị"><i class="fas fa-shield-halved"></i> Admin</span>
                <?php endif; ?>
            </span>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="../admin/dashboard.php" class="admin-link" title="Vào khu vực quản trị">
                    <i class="fas fa-shield-halved"></i> Quản trị
                </a>
            <?php endif; ?>
=======
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
>>>>>>> f9777c795a599b3177a3020d309477735eab22fa
            <a href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>
    <div class="container dashboard-content">
        <h1 class="dashboard-title">Dashboard</h1>
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <i class="fas fa-file-alt"></i>
                <h3>Tài liệu</h3>
                <p>Quản lý tài liệu học tập của bạn</p>
                <a href="documents.php">Xem thêm →</a>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-upload"></i>
                <h3>Tải lên</h3>
                <p>Tải tài liệu mới lên hệ thống</p>
                <a href="upload.php">Xem thêm →</a>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-comments"></i>
                <h3>AI Chatbot</h3>
                <p>Hỏi đáp với trợ lý AI thông minh</p>
                <a href="chatbot.php">Xem thêm →</a>
            </div>
        </div>
    </div>
</body>
</html>
