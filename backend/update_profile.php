<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    die(json_encode(["success" => false, "message" => "Vui long dang nhap"], JSON_UNESCAPED_UNICODE));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userId = $_SESSION["user_id"];

    $fullName = trim(to_utf8($_POST["full_name"]));
    $email = trim($_POST["email"]);

    if (empty($fullName)) {
        die(json_encode(["success" => false, "message" => "Vui long nhap ho ten"], JSON_UNESCAPED_UNICODE));
    }

    if (empty($email)) {
        die(json_encode(["success" => false, "message" => "Vui long nhap email"], JSON_UNESCAPED_UNICODE));
    }

    if (mb_strlen($fullName) < 2) {
        die(json_encode(["success" => false, "message" => "Ho ten qua ngan"], JSON_UNESCAPED_UNICODE));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(["success" => false, "message" => "Email khong hop le"], JSON_UNESCAPED_UNICODE));
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die(json_encode(["success" => false, "message" => "Email da ton tai"], JSON_UNESCAPED_UNICODE));
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $fullName, $email, $userId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Cap nhat thanh cong"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["success" => false, "message" => "Cap nhat that bai"], JSON_UNESCAPED_UNICODE);
    }

}
