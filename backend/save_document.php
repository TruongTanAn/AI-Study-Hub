<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function saveDocumentToDatabase($data) {
    global $conn;

    $required = ['user_id', 'title', 'file_name', 'file_path', 'file_type', 'file_size'];

    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            return [
                'success' => false,
                'error' => 'Thieu thong tin bat buoc: ' . $field
            ];
        }
    }

    $userId = intval($data['user_id']);
    $title = to_utf8(trim($data['title']));
    $description = to_utf8(isset($data['description']) ? trim($data['description']) : '');
    $fileName = to_utf8(trim($data['file_name']));
    $originalName = to_utf8(isset($data['original_name']) ? trim($data['original_name']) : $fileName);
    $filePath = trim($data['file_path']);
    $fileType = trim($data['file_type']);
    $fileSize = intval($data['file_size']);

    $checkUser = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    if (!$checkUser) {
        return [
            'success' => false,
            'error' => 'Prepare that bai: ' . $conn->error
        ];
    }
    $checkUser->bind_param("i", $userId);
    $checkUser->execute();
    $userResult = $checkUser->get_result();

    if ($userResult->num_rows === 0) {
        $checkUser->close();
        return [
            'success' => false,
            'error' => 'Nguoi dung khong ton tai'
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
            'error' => 'Prepare INSERT that bai: ' . $conn->error
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
            'error' => 'Execute INSERT that bai: ' . $error
        ];
    }

    $documentId = $conn->insert_id;
    $stmt->close();

    return [
        'success' => true,
        'document_id' => $documentId,
        'message' => 'Luu tai lieu thanh cong'
    ];
}

function updateDocumentStatus($documentId, $status) {
    global $conn;

    $documentId = intval($documentId);
    $status = trim($status);

    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        return [
            'success' => false,
            'error' => 'Trang thai khong hop le'
        ];
    }

    $stmt = $conn->prepare("UPDATE documents SET status = ? WHERE document_id = ?");

    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Prepare UPDATE that bai: ' . $conn->error
        ];
    }

    $stmt->bind_param("si", $status, $documentId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Execute UPDATE that bai: ' . $error
        ];
    }

    $stmt->close();
    return [
        'success' => true,
        'message' => 'Cap nhat trang thai thanh cong'
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
        // FIX: Force UTF-8 on DB strings
        if ($data) {
            $data['title'] = to_utf8($data['title']);
            $data['description'] = to_utf8($data['description'] ?? '');
            $data['uploader_name'] = to_utf8($data['uploader_name']);
        }
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
        // FIX: Force UTF-8
        $row['title'] = to_utf8($row['title']);
        $row['description'] = to_utf8($row['description'] ?? '');
        $row['file_name'] = to_utf8($row['file_name']);
        $row['original_name'] = to_utf8($row['original_name']);
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
                'error' => 'Prepare SELECT that bai: ' . $conn->error
            ];
        }

        $checkStmt->bind_param("ii", $documentId, $userId);
    } else {
        $checkStmt = $conn->prepare("SELECT document_id, file_path FROM documents WHERE document_id = ?");

        if (!$checkStmt) {
            return [
                'success' => false,
                'error' => 'Prepare SELECT that bai: ' . $conn->error
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
            'error' => 'Tai lieu khong ton tai hoac ban khong co quyen xoa'
        ];
    }

    $document = $checkResult->fetch_assoc();
    $checkStmt->close();

    $deleteDoc = $conn->prepare("DELETE FROM documents WHERE document_id = ?");

    if (!$deleteDoc) {
        return [
            'success' => false,
            'error' => 'Prepare DELETE that bai: ' . $conn->error
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
            'message' => 'Xoa tai lieu thanh cong'
        ];
    }

    $error = $deleteDoc->error;
    $deleteDoc->close();
    return [
        'success' => false,
        'error' => 'Khong the xoa tai lieu: ' . $error
    ];
}

function incrementDownloadCount($documentId) {
    global $conn;

    $documentId = intval($documentId);

    $stmt = $conn->prepare("UPDATE documents SET downloads_count = downloads_count + 1 WHERE document_id = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $documentId);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

function updateDocument($documentId, $userId, $data) {
    global $conn;

    $documentId = intval($documentId);
    $userId = intval($userId);

    $checkStmt = $conn->prepare("SELECT user_id, file_path FROM documents WHERE document_id = ?");
    $checkStmt->bind_param("i", $documentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        return [
            'success' => false,
            'error' => 'Tai lieu khong ton tai'
        ];
    }

    $document = $checkResult->fetch_assoc();
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

    if ($document['user_id'] !== $userId && $userRole !== 'admin') {
        $checkStmt->close();
        return [
            'success' => false,
            'error' => 'Ban khong co quyen chinh sua tai lieu nay'
        ];
    }
    $checkStmt->close();

    $title = isset($data['title']) ? to_utf8(trim($data['title'])) : '';
    $description = isset($data['description']) ? to_utf8(trim($data['description'])) : '';
    $visibility = isset($data['visibility']) ? trim($data['visibility']) : 'public';
    $categoryId = isset($data['category_id']) && !empty($data['category_id']) ? intval($data['category_id']) : null;
    $subjectId = isset($data['subject_id']) && !empty($data['subject_id']) ? intval($data['subject_id']) : null;

    if (empty($title)) {
        return [
            'success' => false,
            'error' => 'Tieu de khong duoc de trong'
        ];
    }

    if (mb_strlen($title) > 255) {
        return [
            'success' => false,
            'error' => 'Tieu de qua dai (toi da 255 ky tu)'
        ];
    }

    $validVisibilities = ['public', 'private', 'shared'];
    if (!in_array($visibility, $validVisibilities)) {
        return [
            'success' => false,
            'error' => 'Che do hien thi khong hop le'
        ];
    }

    $updateFields = "title = ?, description = ?, visibility = ?";
    $params = [$title, $description, $visibility];
    $types = "sss";

    if ($categoryId !== null) {
        $updateFields .= ", category_id = ?";
        $params[] = $categoryId;
        $types .= "i";
    } else {
        $updateFields .= ", category_id = NULL";
    }

    if ($subjectId !== null) {
        $updateFields .= ", subject_id = ?";
        $params[] = $subjectId;
        $types .= "i";
    } else {
        $updateFields .= ", subject_id = NULL";
    }

    $params[] = $documentId;
    $types .= "i";

    $stmt = $conn->prepare("UPDATE documents SET {$updateFields}, updated_at = CURRENT_TIMESTAMP WHERE document_id = ?");

    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Prepare UPDATE that bai: ' . $conn->error
        ];
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Execute UPDATE that bai: ' . $error
        ];
    }

    $stmt->close();

    return [
        'success' => true,
        'message' => 'Cap nhat tai lieu thanh cong',
        'document_id' => $documentId
    ];
}
