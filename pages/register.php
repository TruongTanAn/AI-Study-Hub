<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
$errors = [];
$success = '';
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - AI Study Hub</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="register-wrapper">
        <section class="register-banner">
            <div class="banner-inner">
                <a href="../index.php" class="logo-link">
                    <span class="logo-icon"><i class="fas fa-brain"></i></span>
                    AI Study Hub
                </a>
                <h1>Khởi đầu hành trình học tập thông minh</h1>
                <p>Nền tảng AI giúp bạn quản lý tài liệu, lưu trữ an toàn và học tập hiệu quả hơn bao giờ hết.</p>
                <div class="banner-features">
                    <div class="feature-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-folder-open"></i></div>
                        <div>
                            <h3>Hệ thống Quản lý Tài liệu</h3>
                            <p>Tổ chức và tìm kiếm tài liệu nhanh chóng</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-cloud"></i></div>
                        <div>
                            <h3>Lưu trữ Cloud Bảo mật</h3>
                            <p>Truy cập mọi lúc, mọi nơi</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-wrapper"><i class="fas fa-comments"></i></div>
                        <div>
                            <h3>Trợ lý AI Thông minh</h3>
                            <p>Hỏi đáp trực tiếp trên tài liệu của bạn</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <main class="register-form-container">
            <div class="register-form">
                <div class="form-header">
                    <h2>Tạo tài khoản mới</h2>
                    <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-circle-check"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form action="../backend/register_process.php" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Họ và tên</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="full_name" name="full_name" class="form-input" placeholder="Nguyễn Văn A" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-input" placeholder="example@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Ít nhất 6 ký tự" required>
                            <span class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-shield-halved input-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Nhập lại mật khẩu" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span>Đăng ký tài khoản</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="form-footer">
                    <p>Bằng việc đăng ký, bạn đồng ý với <a href="#">Điều khoản</a> và <a href="#">Chính sách bảo mật</a></p>
                </div>

                <div style="text-align:center; margin-top:20px;">
                    <a href="../index.php" style="color:#64748b; text-decoration:none;">
                        ← Quay lại trang chủ
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
