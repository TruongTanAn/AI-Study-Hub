<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = intval($_SESSION['user_id']);

    $stmt = $conn->prepare("
        SELECT 
            document_id,
            user_id,
            category_id,
            title,
            description,
            file_name,
            original_name,
            file_type,
            file_size,
            visibility,
            status,
            downloads_count,
            created_at,
            updated_at
        FROM documents
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Prepare failed: ' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = $row['document_id'];
        $row['file_size_formatted'] = formatFileSize($row['file_size']);
        $row['status_text'] = getStatusText($row['status']);
        $documents[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'count' => count($documents),
        'documents' => $documents
    ]);
    exit;
}

function getStatusText($status) {
    $statuses = [
        'pending' => 'Đang chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối'
    ];
    return $statuses[$status] ?? 'Không xác định';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}
