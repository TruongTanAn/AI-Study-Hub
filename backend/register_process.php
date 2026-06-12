<?php

include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullName = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($fullName)) {
        die("Vui lòng nhập họ tên");
    }

    if (strlen($fullName) < 2) {
        die("Họ tên quá ngắn");
    }

    if (empty($email)) {
        die("Vui lòng nhập email");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email không hợp lệ");
    }

    if (empty($password)) {
        die("Vui lòng nhập mật khẩu");
    }

    if (strlen($password) < 6) {
        die("Mật khẩu phải từ 6 ký tự trở lên");
    }

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        die("Email đã tồn tại");
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $fullName, $email, $passwordHash);

    if ($stmt->execute()) {
        header("Location: ../pages/login.php?success=" . urlencode("Đăng ký thành công! Vui lòng đăng nhập."));
        exit();
    } else {
        die("Đăng ký thất bại");
    }
}
