<?php

include "../config/database.php";

if (!isset($_GET["id"])) {
    die("Thiếu document id");
}

$documentId = $_GET["id"];

$stmt = $conn->prepare("
    SELECT file_name, file_path
    FROM documents
    WHERE document_id = ?
");

$stmt->bind_param("i", $documentId);
$stmt->execute();

$result = $stmt->get_result();
$document = $result->fetch_assoc();

if (!$document) {
    die("Không tìm thấy tài liệu");
}

$filePath = $document["file_path"];

if (!file_exists($filePath)) {
    die("File không tồn tại");
}

header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=" . basename($document["file_name"]));
header("Content-Length: " . filesize($filePath));

readfile($filePath);
exit();

?>