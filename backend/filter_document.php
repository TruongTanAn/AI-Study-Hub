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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'get_filters';
    
    if ($action === 'get_filters') {
        $subjects = [];
        $stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        $stmt->close();

        $categories = [];
        $catStmt = $conn->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name");
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        while ($row = $catResult->fetch_assoc()) {
            $categories[] = $row;
        }
        $catStmt->close();

        echo json_encode([
            'success' => true,
            'subjects' => $subjects,
            'categories' => $categories
        ]);
        exit;
    }
    
    if ($action === 'filter') {
        $subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
        $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? min(50, max(1, intval($_GET['per_page']))) : 20;

        $params = [];
        $types = '';
        $whereClause = "WHERE d.visibility = 'public' AND d.status = 'approved'";

        if ($subjectId > 0) {
            $whereClause .= " AND d.subject_id = ?";
            $params[] = $subjectId;
            $types .= 'i';
        }

        if ($categoryId > 0) {
            $whereClause .= " AND d.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        $countSql = "SELECT COUNT(*) as total FROM documents d {$whereClause}";
        $countStmt = $conn->prepare($countSql);
        
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        $totalCount = $totalRow['total'];
        $countStmt->close();

        $offset = ($page - 1) * $perPage;
        $filterSql = "
            SELECT 
                d.document_id,
                d.user_id,
                d.subject_id,
                d.category_id,
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
                u.full_name as uploader_name,
                s.subject_name,
                c.category_name
            FROM documents d
            LEFT JOIN users u ON d.user_id = u.user_id
            LEFT JOIN subjects s ON d.subject_id = s.subject_id
            LEFT JOIN categories c ON d.category_id = c.category_id
            {$whereClause}
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';

        $filterStmt = $conn->prepare($filterSql);
        $filterStmt->bind_param($types, ...$params);
        $filterStmt->execute();
        $filterResult = $filterStmt->get_result();

        $documents = [];
        while ($row = $filterResult->fetch_assoc()) {
            $row['id'] = $row['document_id'];
            $row['file_size_formatted'] = formatFileSize($row['file_size']);
            $row['status_text'] = getStatusText($row['status']);
            $row['upload_date'] = date('d/m/Y', strtotime($row['created_at']));
            $documents[] = $row;
        }

        $filterStmt->close();
        $conn->close();

        echo json_encode([
            'success' => true,
            'count' => count($documents),
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
            'documents' => $documents
        ]);
        exit;
    }
}

echo json_encode([
    'success' => false,
    'error' => 'Phương thức hoặc action không được hỗ trợ'
]);

function getStatusText($status) {
    $statuses = [
        'pending' => 'Đang chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối'
    ];
    return $statuses[$status] ?? 'Không xác định';
}

function formatFileSize($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}
