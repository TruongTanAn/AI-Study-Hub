<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    die(json_encode(["success" => false, "message" => "Vui long dang nhap"], JSON_UNESCAPED_UNICODE));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userId = $_SESSION["user_id"];

    if (!isset($_FILES["avatar"])) {
        die(json_encode(["success" => false, "message" => "Chua chon anh"], JSON_UNESCAPED_UNICODE));
    }

    if ($_FILES["avatar"]["error"] != 0) {
        die(json_encode(["success" => false, "message" => "Upload that bai"], JSON_UNESCAPED_UNICODE));
    }

    $fileType = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));

    $allowTypes = ["jpg", "jpeg", "png", "gif"];

    if (!in_array($fileType, $allowTypes)) {
        die(json_encode(["success" => false, "message" => "Chi cho phep jpg, jpeg, png, gif"], JSON_UNESCAPED_UNICODE));
    }

    if ($_FILES["avatar"]["size"] > 2000000) {
        die(json_encode(["success" => false, "message" => "Anh vuot qua 2MB"], JSON_UNESCAPED_UNICODE));
    }

    $fileName = time() . "_" . basename($_FILES["avatar"]["name"]);
    $targetPath = "../uploads/" . $fileName;

    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetPath)) {

        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
        $stmt->bind_param("si", $fileName, $userId);
        $stmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Upload avatar thanh cong"
        ], JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode([
            "success" => false,
            "message" => "Upload avatar that bai"
        ], JSON_UNESCAPED_UNICODE);
    }

}
