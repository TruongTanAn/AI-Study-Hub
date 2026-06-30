<?php

session_start();
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $categoryId = $_GET["category_id"] ?? 0;

    $stmt = $conn->prepare("
        SELECT d.*
        FROM documents d
        JOIN document_categories dc
        ON d.document_id = dc.document_id
        WHERE dc.category_id = ?
    ");

    $stmt->bind_param("i", $categoryId);
    $stmt->execute();

    $result = $stmt->get_result();

    $documents = [];

    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    echo json_encode($documents);

}

?>