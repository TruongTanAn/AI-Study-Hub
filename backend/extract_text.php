<?php

session_start();

header("Content-Type: application/json");

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

// Kiểm tra file
if (!isset($_FILES["document"])) {

    echo json_encode([
        "success" => false,
        "message" => "Chưa chọn tài liệu"
    ]);

    exit();
}

$file = $_FILES["document"];

if ($file["error"] != 0) {

    echo json_encode([
        "success" => false,
        "message" => "Upload thất bại"
    ]);

    exit();
}

// Lấy phần mở rộng
$fileExtension = strtolower(
    pathinfo(
        $file["name"],
        PATHINFO_EXTENSION
    )
);

$text = "";

// =============================
// TXT
// =============================

if ($fileExtension == "txt") {

    $text = file_get_contents(
        $file["tmp_name"]
    );

}

// =============================
// PDF
// =============================

elseif ($fileExtension == "pdf") {

    $text =
        "PDF parser sẽ được tích hợp bởi document_qa.php";

}

// =============================
// DOCX
// =============================

elseif ($fileExtension == "docx") {

    $text =
        "DOCX parser sẽ được tích hợp bởi document_qa.php";

}

// =============================
// PPTX
// =============================

elseif ($fileExtension == "pptx") {

    $text =
        "PPTX parser sẽ được tích hợp bởi document_qa.php";

}

// =============================
// Không hỗ trợ
// =============================

else {

    echo json_encode([
        "success" => false,
        "message" => "Định dạng không hỗ trợ"
    ]);

    exit();

}

// Trả kết quả

echo json_encode([

    "success" => true,

    "file_name" => $file["name"],

    "file_type" => $fileExtension,

    "text" => $text

]);

?>