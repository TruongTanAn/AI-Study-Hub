<?php
<<<<<<< HEAD
=======
/**
 * upload_status.php
 * Trả trạng thái upload (success / failed / pending) cho Frontend.
 */

header('Content-Type: application/json; charset=utf-8');
>>>>>>> origin/kietnguyen-be

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

<<<<<<< HEAD
require_once __DIR__ . '/../config/database.php';

function getUploadStatus($documentId) {
    global $conn;

    $documentId = intval($documentId);

    $stmt = $conn->prepare("
        SELECT 
            document_id,
            title,
            file_name,
            original_name,
            file_type,
            file_size,
            file_path,
            cloud_url,
            cloud_provider,
            upload_status,
            status,
            upload_date,
            updated_at
        FROM documents
        WHERE document_id = ?
    ");
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'error' => 'Document không tồn tại'
        ];
    }

    $document = $result->fetch_assoc();

    $statusInfo = getStatusInfo($document['upload_status']);

    return [
        'success' => true,
        'document' => [
            'id' => $document['document_id'],
            'title' => $document['title'],
            'file_name' => $document['file_name'],
            'original_name' => $document['original_name'],
            'file_type' => $document['file_type'],
            'file_size' => $document['file_size'],
            'file_size_formatted' => formatFileSize($document['file_size']),
            'file_path' => $document['file_path'],
            'cloud_url' => $document['cloud_url'],
            'cloud_provider' => $document['cloud_provider'],
            'status_code' => $document['upload_status'],
            'status_text' => $statusInfo['text'],
            'status_color' => $statusInfo['color'],
            'moderation_status' => $document['status'],
            'upload_date' => $document['upload_date'],
            'updated_at' => $document['updated_at'],
            'progress' => calculateProgress($document['upload_status'])
        ]
    ];
}

function getStatusInfo($status) {
    $statuses = [
        'pending' => [
            'text' => 'Đang chờ xử lý',
            'color' => '#f59e0b'
        ],
        'uploading' => [
            'text' => 'Đang tải lên Cloud',
            'color' => '#3b82f6'
        ],
        'uploaded' => [
            'text' => 'Đã tải lên thành công',
            'color' => '#22c55e'
        ],
        'failed' => [
            'text' => 'Tải lên thất bại',
            'color' => '#ef4444'
        ]
    ];

    return $statuses[$status] ?? [
        'text' => 'Không xác định',
        'color' => '#6b7280'
    ];
}

function calculateProgress($status) {
    $progressMap = [
        'pending' => 25,
        'uploading' => 75,
        'uploaded' => 100,
        'failed' => 0
    ];

    return $progressMap[$status] ?? 0;
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

function getAllUploadStatuses($userId = null) {
    global $conn;

    if ($userId !== null) {
        $userId = intval($userId);
        $stmt = $conn->prepare("
            SELECT 
                document_id,
                title,
                file_name,
                original_name,
                file_type,
                file_size,
                upload_status,
                cloud_url,
                cloud_provider,
                upload_date,
                updated_at
            FROM documents
            WHERE user_id = ?
            ORDER BY upload_date DESC
        ");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                document_id,
                title,
                file_name,
                original_name,
                file_type,
                file_size,
                upload_status,
                cloud_url,
                cloud_provider,
                upload_date,
                updated_at
            FROM documents
            ORDER BY upload_date DESC
        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];

    while ($row = $result->fetch_assoc()) {
        $statusInfo = getStatusInfo($row['upload_status']);

        $documents[] = [
            'id' => $row['document_id'],
            'title' => $row['title'],
            'file_name' => $row['file_name'],
            'original_name' => $row['original_name'],
            'file_type' => $row['file_type'],
            'file_size' => $row['file_size'],
            'file_size_formatted' => formatFileSize($row['file_size']),
            'cloud_url' => $row['cloud_url'],
            'cloud_provider' => $row['cloud_provider'],
            'status_code' => $row['upload_status'],
            'status_text' => $statusInfo['text'],
            'status_color' => $statusInfo['color'],
            'progress' => calculateProgress($row['upload_status']),
            'upload_date' => $row['upload_date'],
            'updated_at' => $row['updated_at']
        ];
    }

    return [
        'success' => true,
        'count' => count($documents),
        'documents' => $documents
    ];
}

function updateUploadStatus($documentId, $status) {
    global $conn;

    $documentId = intval($documentId);

    if (!in_array($status, ['pending', 'uploading', 'uploaded', 'failed'])) {
        return [
            'success' => false,
            'error' => 'Trạng thái không hợp lệ'
        ];
    }

    $stmt = $conn->prepare("
        UPDATE documents
        SET upload_status = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE document_id = ?
    ");
    $stmt->bind_param("si", $status, $documentId);

    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công'
        ];
    }

    return [
        'success' => false,
        'error' => 'Không thể cập nhật trạng thái'
    ];
}
=======
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'status' => 'failed',
        'message' => 'Bạn cần đăng nhập để kiểm tra trạng thái upload.',
    ]);
    exit();
}

$uploadStatus = $_SESSION['upload_status'] ?? 'pending';
$uploadMessage = $_SESSION['upload_message'] ?? 'Đang xử lý upload.';
$documentId = isset($_SESSION['document_id']) ? (int) $_SESSION['document_id'] : null;

$allowedStatus = ['success', 'failed', 'pending'];
if (!in_array($uploadStatus, $allowedStatus, true)) {
    $uploadStatus = 'pending';
}

$response = [
    'success' => true,
    'status' => $uploadStatus,
    'message' => $uploadMessage,
];

if ($documentId !== null) {
    $response['document_id'] = $documentId;
}

echo json_encode($response);
>>>>>>> origin/kietnguyen-be
