<?php

/**
 * auth_check.php
 * Kiểm tra người dùng đã đăng nhập trước khi truy cập các trang yêu cầu xác thực.
 * File này được include ở đầu các trang trong thư mục pages/.
 */

// Bắt đầu session nếu chưa tồn tại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra user_id trong session — chưa đăng nhập thì chuyển về login
if (!isset($_SESSION['user_id'])) {
    // Đường dẫn tương đối theo URL trang hiện tại (pages/*.php)
    header('Location: login.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");

    exit();
}
