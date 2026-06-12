<?php

include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Nhận dữ liệu từ Form Register

    $fullName = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Kiểm tra họ tên

    if (empty($fullName)) {
        die("Vui lòng nhập họ tên");
    }

    if (strlen($fullName) < 2) {
        die("Họ tên quá ngắn");
    }

    // Kiểm tra email

    if (empty($email)) {
        die("Vui lòng nhập email");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email không hợp lệ");
    }

    // Kiểm tra mật khẩu

    if (empty($password)) {
        die("Vui lòng nhập mật khẩu");
    }

    if (strlen($password) < 6) {
        die("Mật khẩu phải từ 6 ký tự trở lên");
    }

    // Kiểm tra email đã tồn tại

    $sql = "SELECT * FROM users WHERE email = '$email'";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        die("Email đã tồn tại");
    }

    // Mã hóa mật khẩu

    $password = password_hash($password, PASSWORD_DEFAULT);

    // Thêm tài khoản

    $sql = "INSERT INTO users
    (full_name, email, password)

    VALUES

    ('$fullName', '$email', '$password')";

    if (mysqli_query($conn, $sql)) {

        echo json_encode([
            "success" => true,
            "message" => "Register success"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Register failed"
        ]);

    }

}

?>