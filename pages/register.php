<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - AI Study Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <!-- LEFT SIDE - Branding -->
        <div class="register-left">
            <div class="left-content">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <span class="logo-text">AI Study Hub</span>
                </div>

                <div class="hero-text">
                    <h1>Khởi tạo tài khoản mới</h1>
                    <p>Đăng ký để bắt đầu sử dụng AI Study Hub, quản lý tài liệu và học tập hiệu quả hơn mỗi ngày.</p>
                </div>

                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-folder2-open"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Quản lý tài liệu dễ dàng</h3>
                            <p>Tổ chức và tìm kiếm tài liệu nhanh chóng</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div class="feature-text">
                            <h3>AI Chatbot thông minh</h3>
                            <p>Hỏi đáp trực tiếp trên tài liệu của bạn</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Bảo mật tài khoản</h3>
                            <p>Dữ liệu được mã hóa và bảo vệ an toàn</p>
                        </div>
                    </div>
                </div>

                <div class="illustration">
                    <div class="illus-card illus-card-1">
                        <i class="bi bi-mortarboard"></i>
                        <span>Học tập</span>
                    </div>
                    <div class="illus-card illus-card-2">
                        <i class="bi bi-lightning"></i>
                        <span>AI</span>
                    </div>
                    <div class="illus-card illus-card-3">
                        <i class="bi bi-cloud"></i>
                        <span>Cloud</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE - Form -->
        <div class="register-right">
            <div class="form-card">
                <div class="form-header">
                    <h2>Tạo tài khoản mới</h2>
                    <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
                </div>

                <form action="../backend/register_process.php" method="POST" class="register-form">
                    <div class="form-group">
                        <label for="full_name">Họ và tên</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" id="full_name" name="full_name" placeholder="Nhập họ và tên của bạn" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" id="email" name="email" placeholder="example@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-register">
                        <span>Đăng ký</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <div class="form-footer">
                    <p>Bằng việc đăng ký, bạn đồng ý với <a href="#">Điều khoản sử dụng</a> và <a href="#">Chính sách bảo mật</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
