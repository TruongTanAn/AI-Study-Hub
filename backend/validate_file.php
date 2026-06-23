<?php

if (!isset($_FILES["document"])) {
    die("Chưa chọn file");
}

$fileType = strtolower(
    pathinfo(
        $_FILES["document"]["name"],
        PATHINFO_EXTENSION
    )
);

$allowedTypes = ["pdf", "docx", "pptx"];

if (!in_array($fileType, $allowedTypes)) {
    die("Chỉ cho phép PDF, DOCX, PPTX");
}

$maxSize = 10 * 1024 * 1024;

if ($_FILES["document"]["size"] > $maxSize) {
    die("File vượt quá 10MB");
}

?>