<?php
/**
 * logout.php
 * Đăng xuất: xóa toàn bộ session và chuyển hướng về trang đăng nhập.
 */

session_start();

// Xóa toàn bộ dữ liệu session
$_SESSION = [];

// Xóa cookie session nếu có
if (ini_get('session.use_cookies')) {
    $sessionParams = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $sessionParams['path'],
        $sessionParams['domain'],
        $sessionParams['secure'],
        $sessionParams['httponly']
    );
}

// Hủy session
session_destroy();

// Chuyển hướng về trang đăng nhập
header('Location: ../pages/login.php');
exit();
