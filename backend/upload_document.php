<?php

// FE phải gửi:
// <input type="file" name="document">

session_start();
include "../config/database.php";
include "validate_file.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION["user_id"])) {
        die("Vui lòng đăng nhập");
    }

    $file = $_FILES["document"];

    // Kiểm tra lỗi upload

    if ($file["error"] !== 0) {
        die("Upload lỗi");
    }

    // Đổi tên file tránh trùng

    $fileName =
        time() . "_" .
        basename($file["name"]);

    // Đường dẫn lưu file

    $targetPath =
        "../uploads/" . $fileName;

    // Upload file

    if (
        move_uploaded_file(
            $file["tmp_name"],
            $targetPath
        )
    ) {

        echo json_encode([
            "success" => true,
            "message" => "Upload thành công",
            "file_name" => $fileName
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Upload thất bại"
        ]);

    }

}

?>