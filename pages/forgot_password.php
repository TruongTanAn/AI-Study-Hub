<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email'], ENT_QUOTES, 'UTF-8') : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - AI Study Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <i class="bi bi-key"></i>
                </div>
                <h1>Quên mật khẩu?</h1>
                <p>Không worry, chúng tôi sẽ giúp bạn khôi phục tài khoản</p>
            </div>

            <?php if ($error): ?>
            <div class="alert-error" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form action="../backend/forgot_password_process.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="Nhập email đã đăng ký" value="<?php echo $email; ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <span>Tiếp tục</span>
                    <i class="bi bi-arrow-right"></i>
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
</body>
</html>
