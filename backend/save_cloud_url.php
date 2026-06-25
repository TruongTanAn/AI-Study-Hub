<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function saveCloudUrl($documentId, $cloudData) {
    global $conn;

    $documentId = intval($documentId);

    $required = ['url', 'path', 'provider'];

    foreach ($required as $field) {
        if (!isset($cloudData[$field]) || empty($cloudData[$field])) {
            return [
                'success' => false,
                'error' => 'Thiếu thông tin cloud: ' . $field
            ];
        }
    }

    $checkStmt = $conn->prepare("SELECT document_id FROM documents WHERE document_id = ?");
    $checkStmt->bind_param("i", $documentId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'error' => 'Document không tồn tại'
        ];
    }

    $cloudUrl = trim($cloudData['url']);
    $cloudPath = trim($cloudData['path']);
    $cloudProvider = trim($cloudData['provider']);
    $cloudBucket = isset($cloudData['bucket']) ? trim($cloudData['bucket']) : null;

    $stmt = $conn->prepare("
        UPDATE documents
        SET cloud_url = ?,
            cloud_path = ?,
            cloud_provider = ?,
            cloud_bucket = ?,
            upload_status = 'uploaded',
            updated_at = CURRENT_TIMESTAMP
        WHERE document_id = ?
    ");

    $stmt->bind_param("ssssi", $cloudUrl, $cloudPath, $cloudProvider, $cloudBucket, $documentId);

    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Lưu Cloud URL thành công',
            'cloud_url' => $cloudUrl
        ];
    }

    return [
        'success' => false,
        'error' => 'Không thể lưu Cloud URL: ' . $stmt->error
    ];
}

function getCloudUrl($documentId) {
    global $conn;

    $documentId = intval($documentId);

    $stmt = $conn->prepare("
        SELECT cloud_url, cloud_path, cloud_provider, cloud_bucket, upload_status
        FROM documents
        WHERE document_id = ?
    ");
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

function updateCloudStatus($documentId, $status, $errorMessage = null) {
    global $conn;

    $documentId = intval($documentId);

    if (!in_array($status, ['uploading', 'uploaded', 'failed'])) {
        return [
            'success' => false,
            'error' => 'Trạng thái không hợp lệ'
        ];
    }

    if ($errorMessage !== null) {
        $stmt = $conn->prepare("
            UPDATE documents
            SET upload_status = ?,
                description = CONCAT(IFNULL(description, ''), '\nLỗi Cloud: ', ?),
                updated_at = CURRENT_TIMESTAMP
            WHERE document_id = ?
        ");
        $stmt->bind_param("ssi", $status, $errorMessage, $documentId);
    } else {
        $stmt = $conn->prepare("
            UPDATE documents
            SET upload_status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE document_id = ?
        ");
        $stmt->bind_param("si", $status, $documentId);
    }

    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Cập nhật trạng thái cloud thành công'
        ];
    }

    return [
        'success' => false,
        'error' => 'Không thể cập nhật trạng thái cloud'
    ];
}
