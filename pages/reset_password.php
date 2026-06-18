<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if (!isset($_GET['email']) || empty($_GET['email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = htmlspecialchars($_GET['email']);
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - AI Study Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon success">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h1>Đặt lại mật khẩu</h1>
                <p>Nhập mật khẩu mới cho tài khoản</p>
                <div class="user-email">
                    <i class="bi bi-person"></i>
                    <?php echo $email; ?>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert-error" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form action="../backend/reset_password_process.php" method="POST" class="auth-form">
                <input type="hidden" name="email" value="<?php echo $email; ?>">

                <div class="form-group">
                    <label for="password">Mật khẩu mới</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'eye-password')">
                            <i class="bi bi-eye" id="eye-password"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'eye-confirm')">
                            <i class="bi bi-eye" id="eye-confirm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <span>Đổi mật khẩu</span>
                    <i class="bi bi-check-lg"></i>
                </button>
            </form>

            <div class="auth-footer">
                <a href="login.php">
                    <i class="bi bi-arrow-left"></i>
                    Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
