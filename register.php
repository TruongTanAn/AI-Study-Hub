<?php
$errors = [];
$success_message = "";

// Keep values for input persistence
$fullname_val = trim($_POST['fullname'] ?? '');
$email_val = trim($_POST['email'] ?? '');
$username_val = trim($_POST['username'] ?? '');
$terms = isset($_POST['terms']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Backend Validation
    if (empty($fullname_val)) {
        $errors['fullname'] = "Họ và tên không được để trống.";
    } elseif (strlen($fullname_val) < 2) {
        $errors['fullname'] = "Họ và tên phải có ít nhất 2 ký tự.";
    }

    if (empty($email_val)) {
        $errors['email'] = "Email không được để trống.";
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email không đúng định dạng.";
    }

    if (empty($username_val)) {
        $errors['username'] = "Tên đăng nhập không được để trống.";
    } elseif (strlen($username_val) < 4) {
        $errors['username'] = "Tên đăng nhập phải có ít nhất 4 ký tự.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username_val)) {
        $errors['username'] = "Tên đăng nhập chỉ gồm chữ cái, số và dấu gạch dưới.";
    }

    if (empty($password)) {
        $errors['password'] = "Mật khẩu không được để trống.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Mật khẩu phải có ít nhất 8 ký tự.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors['password'] = "Mật khẩu chưa đủ mạnh.";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Mật khẩu xác nhận không khớp.";
    }

    if (!$terms) {
        $errors['terms'] = "Bạn phải đồng ý với Điều khoản và Chính sách.";
    }

    if (empty($errors)) {
        $success_message = "Đăng ký tài khoản thành công! Chào mừng " . htmlspecialchars($fullname_val) . " đến với AI Study Hub.";
        // Reset form sau khi thành công
        $fullname_val = $email_val = $username_val = "";
        $terms = false;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký | AI Study Hub</title>
    <link rel="stylesheet" href="assets/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --success: #22c55e;
        --gray: #64748b;
    }
    body {
        font-family: 'Segoe UI', system-ui, sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
        margin: 0;
        min-height: 100vh;
    }
    .register-wrapper {
        display: flex;
        min-height: 100vh;
    }
    .register-banner {
        flex: 1;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 60px 50px;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    .register-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('https://source.unsplash.com/random/800x600/?ai,technology') center/cover;
        opacity: 0.15;
    }
    .banner-inner {
        position: relative;
        z-index: 2;
        max-width: 420px;
    }
    .logo-link {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: white;
        text-decoration: none;
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 40px;
    }
    .logo-icon {
        font-size: 2.2rem;
    }
    .register-banner h1 {
        font-size: 2.4rem;
        line-height: 1.2;
        margin-bottom: 20px;
    }
    .register-banner p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 40px;
    }
    .feature-item {
        display: flex;
        gap: 16px;
        margin-bottom: 28px;
    }
    .feature-icon-wrapper {
        width: 52px;
        height: 52px;
        background: rgba(255,255,255,0.15);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    /* Form */
    .register-form-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background: white;
    }
    .register-form {
        width: 100%;
        max-width: 460px;
    }
    .form-header h2 {
        font-size: 2rem;
        margin-bottom: 8px;
        color: #1e2937;
    }
    .form-header p {
        color: var(--gray);
    }
    .form-group {
        margin-bottom: 24px;
    }
    .input-wrapper {
        position: relative;
    }
    .form-input {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s;
    }
    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }
    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.2rem;
    }
    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 10px;
    }
    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
    }
    .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    </style>
</head>
<body>

<div class="register-wrapper">
    <!-- Banner -->
    <section class="register-banner">
        <div class="banner-inner">
            <a href="index.html" class="logo-link">
                <span class="logo-icon"><i class="fa-solid fa-brain"></i></span>
                AI Study Hub
            </a>
            
            <h1>Khởi đầu hành trình học tập thông minh</h1>
            <p>Nền tảng AI giúp bạn quản lý tài liệu, lưu trữ an toàn và học tập hiệu quả hơn bao giờ hết.</p>

            <div class="banner-features">
                <div class="feature-item">
                    <div class="feature-icon-wrapper"><i class="fa-solid fa-folder-open"></i></div>
                    <div>
                        <h3>Hệ thống Quản lý Tài liệu</h3>
                        <p>Tổ chức và tìm kiếm tài liệu nhanh chóng</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-wrapper"><i class="fa-solid fa-cloud"></i></div>
                    <div>
                        <h3>Lưu trữ Cloud Bảo mật</h3>
                        <p>Truy cập mọi lúc, mọi nơi</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-wrapper"><i class="fa-solid fa-comments"></i></div>
                    <div>
                        <h3>Trợ lý AI Thông minh</h3>
                        <p>Hỏi đáp trực tiếp trên tài liệu của bạn</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form -->
    <main class="register-form-container">
        <div class="register-form">
            <div class="form-header">
                <h2>Tạo tài khoản mới</h2>
                <p>Đã có tài khoản? <a href="#" style="color: var(--primary); font-weight: 500;">Đăng nhập ngay</a></p>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" style="background:#ecfdf5; color:#10b981; padding:16px; border-radius:12px; margin-bottom:20px;">
                    <i class="fa-solid fa-circle-check"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                <div class="form-group">
                    <label class="form-label" for="fullname">Họ và tên</label>
                    <div class="input-wrapper">
                        <input type="text" name="fullname" id="fullname" class="form-input <?= isset($errors['fullname']) ? 'is-invalid' : '' ?>" 
                               placeholder="Nguyễn Văn A" value="<?= htmlspecialchars($fullname_val) ?>" required>
                        <i class="fa-regular fa-user input-icon"></i>
                    </div>
                    <span class="error-message <?= isset($errors['fullname']) ? 'active' : '' ?>" id="fullname_error"><?= $errors['fullname'] ?? '' ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" class="form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               placeholder="example@email.com" value="<?= htmlspecialchars($email_val) ?>" required>
                        <i class="fa-regular fa-envelope input-icon"></i>
                    </div>
                    <span class="error-message <?= isset($errors['email']) ? 'active' : '' ?>" id="email_error"><?= $errors['email'] ?? '' ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" id="username" class="form-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                               placeholder="username123" value="<?= htmlspecialchars($username_val) ?>" required>
                        <i class="fa-solid fa-at input-icon"></i>
                    </div>
                    <span class="error-message <?= isset($errors['username']) ? 'active' : '' ?>" id="username_error"><?= $errors['username'] ?? '' ?></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               placeholder="••••••••" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                    <span class="error-message <?= isset($errors['password']) ? 'active' : '' ?>" id="password_error"><?= $errors['password'] ?? '' ?></span>
                    
                    <div class="password-strength-wrapper">
                        <div class="strength-meter-bar">
                            <div class="strength-meter-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText">Độ mạnh: <span style="color: #6b7280">Chưa nhập</span></div>
                    </div>

                    <div class="password-requirements">
                        <ul class="requirements-list">
                            <li class="requirement-item unmet" id="req_length">
                                <i class="fa-regular fa-circle"></i> Ít nhất 8 ký tự
                            </li>
                            <li class="requirement-item unmet" id="req_uppercase">
                                <i class="fa-regular fa-circle"></i> Chữ hoa (A-Z)
                            </li>
                            <li class="requirement-item unmet" id="req_lowercase">
                                <i class="fa-regular fa-circle"></i> Chữ thường (a-z)
                            </li>
                            <li class="requirement-item unmet" id="req_number">
                                <i class="fa-regular fa-circle"></i> Chữ số (0-9)
                            </li>
                            <li class="requirement-item unmet" id="req_special">
                                <i class="fa-regular fa-circle"></i> Ký tự đặc biệt (@, #,...)
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                               placeholder="••••••••" required>
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                    </div>
                    <span class="error-message <?= isset($errors['confirm_password']) ? 'active' : '' ?>" id="confirm_password_error"><?= $errors['confirm_password'] ?? '' ?></span>
                </div>

                <div class="form-group">
                    <label class="terms-wrapper">
                        <input type="checkbox" name="terms" id="terms" class="terms-checkbox" <?= $terms ? 'checked' : '' ?> required>
                        <span>Tôi đồng ý với <a href="#">Điều khoản</a> và <a href="#">Chính sách bảo mật</a></span>
                    </label>
                    <span class="error-message <?= isset($errors['terms']) ? 'active' : '' ?>" id="terms_error"><?= $errors['terms'] ?? '' ?></span>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span>Đăng ký tài khoản</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div style="text-align:center; margin-top:30px;">
                <a href="index.html" style="color:#64748b; text-decoration:none;">
                    ← Quay lại trang chủ
                </a>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/register.js"></script>
</body>
</html>