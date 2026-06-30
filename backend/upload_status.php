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
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
        ]);
        exit;
    }
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
