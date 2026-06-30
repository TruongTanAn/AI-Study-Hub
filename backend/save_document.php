<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function saveDocumentToDatabase($data) {
    global $conn;

    $required = ['user_id', 'title', 'file_name', 'file_path', 'file_type', 'file_size'];

    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            return [
                'success' => false,
                'error' => 'Thiếu thông tin bắt buộc: ' . $field
            ];
        }
    }

    $userId = intval($data['user_id']);
    $title = trim($data['title']);
    $description = isset($data['description']) ? trim($data['description']) : '';
    $fileName = trim($data['file_name']);
    $originalName = isset($data['original_name']) ? trim($data['original_name']) : $fileName;
    $filePath = trim($data['file_path']);
    $fileType = trim($data['file_type']);
    $fileSize = intval($data['file_size']);

    $checkUser = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    if (!$checkUser) {
        return [
            'success' => false,
            'error' => 'Prepare failed: ' . $conn->error
        ];
    }
    $checkUser->bind_param("i", $userId);
    $checkUser->execute();
    $userResult = $checkUser->get_result();

    if ($userResult->num_rows === 0) {
        $checkUser->close();
        return [
            'success' => false,
            'error' => 'Người dùng không tồn tại'
        ];
    }
    $checkUser->close();

    $visibility = isset($data['visibility']) ? trim($data['visibility']) : 'public';
    $status = isset($data['status']) ? trim($data['status']) : 'pending';
    $categoryId = isset($data['category_id']) && !empty($data['category_id']) ? intval($data['category_id']) : null;

    $stmt = $conn->prepare("
        INSERT INTO documents (
            user_id, title, description, file_name, original_name,
            file_path, file_type, file_size, visibility, status, category_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Prepare INSERT failed: ' . $conn->error
        ];
    }

    $stmt->bind_param(
        "issssssissi",
        $userId, $title, $description, $fileName, $originalName,
        $filePath, $fileType, $fileSize, $visibility, $status, $categoryId
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Execute INSERT failed: ' . $error
        ];
    }

    $documentId = $conn->insert_id;
    $stmt->close();

    return [
        'success' => true,
        'document_id' => $documentId,
        'message' => 'Lưu document thành công'
    ];
}

function updateDocumentStatus($documentId, $status) {
    global $conn;

    $documentId = intval($documentId);
    $status = trim($status);

    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        return [
            'success' => false,
            'error' => 'Trạng thái không hợp lệ'
        ];
    }

    $stmt = $conn->prepare("
        UPDATE documents
        SET status = ?
        WHERE document_id = ?
    ");

    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Prepare UPDATE failed: ' . $conn->error
        ];
    }

    $stmt->bind_param("si", $status, $documentId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Execute UPDATE failed: ' . $error
        ];
    }

    $stmt->close();
    return [
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công'
    ];
}

function getDocumentById($documentId) {
    global $conn;

    $documentId = intval($documentId);

    $stmt = $conn->prepare("
        SELECT d.*, u.full_name as uploader_name
        FROM documents d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.document_id = ?
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $documentId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    $stmt->close();
    return null;
}

function getUserDocuments($userId, $status = null) {
    global $conn;

    $userId = intval($userId);

    if ($status !== null) {
        $stmt = $conn->prepare("
            SELECT document_id, user_id, title, description, file_name, original_name,
                   file_path, file_type, file_size, visibility, status, downloads_count,
                   category_id, created_at, updated_at
            FROM documents
            WHERE user_id = ? AND status = ?
            ORDER BY created_at DESC
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("is", $userId, $status);
    } else {
        $stmt = $conn->prepare("
            SELECT document_id, user_id, title, description, file_name, original_name,
                   file_path, file_type, file_size, visibility, status, downloads_count,
                   category_id, created_at, updated_at
            FROM documents
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    $stmt->close();
    return $documents;
}

function deleteDocument($documentId, $userId = null) {
    global $conn;

    $documentId = intval($documentId);

    if ($userId !== null) {
        $userId = intval($userId);
        $checkStmt = $conn->prepare("SELECT document_id, file_path FROM documents WHERE document_id = ? AND user_id = ?");

        if (!$checkStmt) {
            return [
                'success' => false,
                'error' => 'Prepare SELECT failed: ' . $conn->error
            ];
        }

        $checkStmt->bind_param("ii", $documentId, $userId);
    } else {
        $checkStmt = $conn->prepare("SELECT document_id, file_path FROM documents WHERE document_id = ?");

        if (!$checkStmt) {
            return [
                'success' => false,
                'error' => 'Prepare SELECT failed: ' . $conn->error
            ];
        }

        $checkStmt->bind_param("i", $documentId);
    }

    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        return [
            'success' => false,
            'error' => 'Document không tồn tại hoặc bạn không có quyền xóa'
        ];
    }

    $document = $checkResult->fetch_assoc();
    $checkStmt->close();

    $deleteDoc = $conn->prepare("DELETE FROM documents WHERE document_id = ?");

    if (!$deleteDoc) {
        return [
            'success' => false,
            'error' => 'Prepare DELETE failed: ' . $conn->error
        ];
    }

    $deleteDoc->bind_param("i", $documentId);

    if ($deleteDoc->execute()) {
        $deleteDoc->close();

        if (!empty($document['file_path']) && file_exists($document['file_path'])) {
            @unlink($document['file_path']);
        }

        return [
            'success' => true,
            'message' => 'Xóa document thành công'
        ];
    }

    $error = $deleteDoc->error;
    $deleteDoc->close();
    return [
        'success' => false,
        'error' => 'Không thể xóa document: ' . $error
    ];
}

function incrementDownloadCount($documentId) {
    global $conn;

    $documentId = intval($documentId);

    $stmt = $conn->prepare("
        UPDATE documents
        SET downloads_count = downloads_count + 1
        WHERE document_id = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $documentId);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}
