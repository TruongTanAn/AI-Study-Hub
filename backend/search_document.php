<?php

session_start();
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $keyword = trim($_GET["q"] ?? "");

    $stmt = $conn->prepare("
        SELECT *
        FROM documents
        WHERE title LIKE ?
    ");

    $search = "%" . $keyword . "%";

    $stmt->bind_param("s", $search);
    $stmt->execute();

    $result = $stmt->get_result();

    $documents = [];

    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    echo json_encode($documents);

}

?>