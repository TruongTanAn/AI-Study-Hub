<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$success = "";

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập với mật khẩu mới.";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - AI Study Hub</title>

    <link rel="stylesheet" href="../assets/css/login.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<div class="register-wrapper">

    <!-- LEFT -->
    <div class="register-banner">

        <div class="logo">
            <i class="fas fa-brain"></i>
            AI Study Hub
        </div>

        <h1>Chào mừng trở lại</h1>

        <p>
            Đăng nhập để tiếp tục sử dụng AI Study Hub,
            quản lý tài liệu và học tập hiệu quả hơn.
        </p>

        <div class="feature-list">

            <div class="feature">
                <i class="fas fa-folder-open"></i>
                <div>
                    <h3>Quản lý tài liệu</h3>
                    <p>Lưu trữ và tìm kiếm dễ dàng</p>
                </div>
            </div>

            <div class="feature">
                <i class="fas fa-cloud"></i>
                <div>
                    <h3>Cloud Storage</h3>
                    <p>Truy cập mọi lúc mọi nơi</p>
                </div>
            </div>

            <div class="feature">
                <i class="fas fa-comments"></i>
                <div>
                    <h3>AI Chatbot</h3>
                    <p>Hỗ trợ học tập thông minh</p>
                </div>
            </div>

        </div>
    </div>

    <!-- RIGHT -->
    <div class="register-form-container">

        <div class="register-form">

            <h2>Đăng nhập</h2>

            <p class="subtitle">
                Chưa có tài khoản?
                <a href="register.php">Đăng ký ngay</a>
            </p>

            <?php if($error): ?>
                <div class="alert error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="../backend/login_process.php" method="POST">

                <div class="form-group">
                    <label>Email</label>

                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            placeholder="Nhập email"
                            required>
                    </div>
                </div>

             <div class="form-group">
    <label>Mật khẩu</label>

    <div class="input-wrapper">
        <i class="fas fa-lock"></i>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Nhập mật khẩu"
            required>

        <span class="toggle-password" onclick="togglePassword()">
            <i class="fas fa-eye" id="eyeIcon"></i>
        </span>
    </div>

    <div class="forgot-password">
        <a href="forgot_password.php">Quên mật khẩu?</a>
    </div>
</div>

                <button type="submit" class="register-btn">
                    Đăng nhập
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>