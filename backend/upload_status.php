<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui long dang nhap'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = intval($_SESSION['user_id']);

    try {
        $stmt = $conn->prepare("
            SELECT
                d.document_id,
                d.user_id,
                d.category_id,
                d.subject_id,
                d.title,
                d.description,
                d.file_name,
                d.original_name,
                d.file_type,
                d.file_size,
                d.visibility,
                d.status,
                d.downloads_count,
                d.created_at,
                d.updated_at,
                c.category_name,
                s.subject_name
            FROM documents d
            LEFT JOIN categories c ON d.category_id = c.category_id
            LEFT JOIN subjects s ON d.subject_id = s.subject_id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $documents = [];
        while ($row = $result->fetch_assoc()) {
            // FIX: Force UTF-8 on every DB string field
            $row['id'] = (int)$row['document_id'];
            $row['title'] = to_utf8($row['title']);
            $row['description'] = to_utf8($row['description']);
            $row['file_name'] = to_utf8($row['file_name']);
            $row['original_name'] = to_utf8($row['original_name']);
            $row['category_name'] = to_utf8($row['category_name'] ?? '');
            $row['subject_name'] = to_utf8($row['subject_name'] ?? '');
            $row['file_size_formatted'] = format_filesize((int)$row['file_size']);
            $row['status_text'] = get_status_text($row['status']);
            $documents[] = $row;
        }

        $stmt->close();
        $conn->close();

        echo json_encode([
            'success' => true,
            'count' => count($documents),
            'documents' => $documents
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Loi co so du lieu: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
