<?php

session_start();
include "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    die("Vui lòng đăng nhập");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userId = $_SESSION["user_id"];

    if (!isset($_FILES["avatar"])) {
        die("Chưa chọn ảnh");
    }

    // Kiểm tra lỗi upload

    if ($_FILES["avatar"]["error"] != 0) {
        die("Upload thất bại");
    }

    // Kiểm tra loại file

    $fileType = strtolower(
        pathinfo(
            $_FILES["avatar"]["name"],
            PATHINFO_EXTENSION
        )
    );

    $allowTypes = [
        "jpg",
        "jpeg",
        "png",
        "gif"
    ];

    if (!in_array($fileType, $allowTypes)) {
        die("Chỉ cho phép jpg, jpeg, png, gif");
    }

    // Kiểm tra dung lượng

    if ($_FILES["avatar"]["size"] > 2000000) {
        die("Ảnh vượt quá 2MB");
    }

    // Đặt tên file

    $fileName =
        time() . "_" .
        basename($_FILES["avatar"]["name"]);

    $targetPath =
        "../uploads/" . $fileName;

    // Upload file

    if (
        move_uploaded_file(
            $_FILES["avatar"]["tmp_name"],
            $targetPath
        )
    ) {

        $stmt = $conn->prepare("
            UPDATE users
            SET avatar = ?
            WHERE user_id = ?
        ");

        $stmt->bind_param(
            "si",
            $fileName,
            $userId
        );

        $stmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Upload avatar thành công"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Upload avatar thất bại"
        ]);

    }

}
?>