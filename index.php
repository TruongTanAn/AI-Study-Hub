<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Study Hub - Trang chủ</title>
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="landing-page">
        <nav class="navbar">
            <div class="container nav-container">
                <a href="index.php" class="logo">
                    <i class="fas fa-brain"></i> AI Study Hub
                </a>
                <div class="nav-links">
                    <a href="pages/login.php">Đăng nhập</a>
                    <a href="pages/register.php" class="btn-nav">Đăng ký</a>
                </div>
            </div>
        </nav>

        <section class="hero">
            <div class="container hero-content">
                <h1>Học tập thông minh cùng <span class="gradient-text">AI</span></h1>
                <p>Nền tảng quản lý tài liệu và trợ lý AI giúp bạn học tập hiệu quả hơn bao giờ hết.</p>
                <div class="hero-buttons">
                    <a href="pages/register.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Bắt đầu ngay
                    </a>
                    <a href="pages/login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
