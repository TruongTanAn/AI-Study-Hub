<?php

session_start();
include "../config/database.php";

// Chỉ chấp nhận GET
if ($_SERVER["REQUEST_METHOD"] != "GET") {

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

$sessionId = $_GET["session_id"] ?? "";

if (empty($sessionId)) {

    echo json_encode([
        "success" => false,
        "message" => "Thiếu session_id"
    ]);

    exit();
}

// Kiểm tra phiên chat có thuộc user không

$sql = "
SELECT id
FROM chat_sessions
WHERE id = ?
AND user_id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $sessionId,
    $userId
);

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
// Lấy toàn bộ lịch sử chat
// =======================

$sql = "
SELECT
    id,
    role,
    message,
    document_id,
    created_at
FROM chat_messages
WHERE session_id = ?
ORDER BY created_at ASC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $sessionId
);

$stmt->execute();

$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {

    $messages[] = [
        "id" => $row["id"],
        "role" => $row["role"],
        "message" => $row["message"],
        "document_id" => $row["document_id"],
        "created_at" => $row["created_at"]
    ];

}

echo json_encode([
    "success" => true,
    "session_id" => $sessionId,
    "messages" => $messages
]);

$stmt->close();
$conn->close();

?>