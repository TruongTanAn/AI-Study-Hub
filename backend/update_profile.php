<?php

session_start();
include "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    die("Vui lòng đăng nhập");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userId = $_SESSION["user_id"];

    $fullName = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);

    // Kiểm tra rỗng

    if (empty($fullName)) {
        die("Vui lòng nhập họ tên");
    }

    if (empty($email)) {
        die("Vui lòng nhập email");
    }

    // Kiểm tra độ dài họ tên

    if (strlen($fullName) < 2) {
        die("Họ tên quá ngắn");
    }

    // Kiểm tra email hợp lệ

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email không hợp lệ");
    }

    // Kiểm tra email trùng

    $stmt = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE email = ?
        AND user_id != ?
    ");

    $stmt->bind_param(
        "si",
        $email,
        $userId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("Email đã tồn tại");
    }

    // Update thông tin

    $stmt = $conn->prepare("
        UPDATE users
        SET full_name = ?, email = ?
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "ssi",
        $fullName,
        $email,
        $userId
    );

    if ($stmt->execute()) {

        echo json_encode([
            "success" => true,
            "message" => "Cập nhật thành công"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Cập nhật thất bại"
        ]);

    }

}
?>