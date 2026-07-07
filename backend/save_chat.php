<?php

session_start();
include "../config/database.php";

// Chỉ chấp nhận POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Phương thức không hợp lệ"
    ]);
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Vui lòng đăng nhập"
    ]);
    exit();
}

$userId = $_SESSION["user_id"];

// Nhận dữ liệu
$sessionId = $_POST["session_id"] ?? "";
$userMessage = trim($_POST["user_message"] ?? "");
$aiResponse = trim($_POST["ai_response"] ?? "");
$documentId = $_POST["document_id"] ?? null;

// Validate
if (empty($sessionId)) {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu session_id"
    ]);
    exit();
}

if (empty($userMessage)) {
    echo json_encode([
        "success" => false,
        "message" => "Tin nhắn không được để trống"
    ]);
    exit();
}

if (empty($aiResponse)) {
    echo json_encode([
        "success" => false,
        "message" => "Phản hồi AI không được để trống"
    ]);
    exit();
}

// Kiểm tra session chat có thuộc user hay không
$sql = "
SELECT id
FROM chat_sessions
WHERE id = ?
AND user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $sessionId, $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    echo json_encode([
        "success" => false,
        "message" => "Không tìm thấy phiên chat"
    ]);

    exit();
}

$stmt->close();


// =======================
// Lưu tin nhắn User
// =======================

$sql = "
INSERT INTO chat_messages
(session_id, role, message, document_id)
VALUES
(?, 'user', ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isi",
    $sessionId,
    $userMessage,
    $documentId
);

$stmt->execute();
$stmt->close();


// =======================
// Lưu phản hồi AI
// =======================

$sql = "
INSERT INTO chat_messages
(session_id, role, message, document_id)
VALUES
(?, 'assistant', ?, ?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isi",
    $sessionId,
    $aiResponse,
    $documentId
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Lưu hội thoại thành công"
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Không thể lưu hội thoại"
    ]);

}

$stmt->close();
$conn->close();

?>