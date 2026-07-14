<?php
/**
 * backend/download.php
 * Download file theo document_id.
 *
 * Tuong thich ca 2 truong hop:
 *   - file_path = Supabase URL (sau khi migrate sang Supabase Storage)
 *   - file_path = duong dan local cu (uploads/documents/...) - cho du lieu cu
 *
 * - Neu la Supabase URL: proxy qua Supabase (Content-Disposition: attachment) de
 *   trinh duyet tai xuong voi ten file goc.
 * - Neu la local file: stream thang.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloud_storage.php';
require_once __DIR__ . '/save_document.php'; // contains incrementDownloadCount

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php?error=' . urlencode('Vui lòng đăng nhập để tải tài liệu'));
    exit;
}

$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($documentId <= 0) {
    header('Location: ../pages/documents.php?error=' . urlencode('ID tài liệu không hợp lệ'));
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

$stmt = $conn->prepare("
    SELECT document_id, user_id, title, file_name, original_name, file_path, file_type, file_size, visibility, status
    FROM documents
    WHERE document_id = ?
");

if (!$stmt) {
    header('Location: ../pages/documents.php?error=' . urlencode('Lỗi hệ thống: prepare failed'));
    exit;
}

$stmt->bind_param("i", $documentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: ../pages/documents.php?error=' . urlencode('Tài liệu không tồn tại'));
    exit;
}

$doc = $result->fetch_assoc();
$stmt->close();

// Permissions Check
if ($doc['visibility'] === 'private' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: ../pages/documents.php?error=' . urlencode('Bạn không có quyền truy cập tài liệu này'));
    exit;
}

if ($doc['status'] !== 'approved' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: ../pages/documents.php?error=' . urlencode('Tài liệu này chưa được phê duyệt'));
    exit;
}

// Perform DB Updates
incrementDownloadCount($documentId);

$logStmt = $conn->prepare("INSERT INTO download_history (user_id, document_id) VALUES (?, ?)");
if ($logStmt) {
    $logStmt->bind_param("ii", $userId, $documentId);
    $logStmt->execute();
    $logStmt->close();
}

$conn->close();

// Map content types
$fileType = strtoupper($doc['file_type']);
$contentType = 'application/octet-stream';
if ($fileType === 'PDF') {
    $contentType = 'application/pdf';
} else if ($fileType === 'DOCX') {
    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
} else if ($fileType === 'PPTX') {
    $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
}

$downloadName = !empty($doc['original_name']) ? $doc['original_name'] : $doc['file_name'];

$filePath = (string) $doc['file_path'];
$isSupabaseUrl = $filePath !== '' && CloudStorage::isSupabaseUrl($filePath);

// ====== CASE 1: Supabase URL ======
if ($isSupabaseUrl) {
    $remoteUrl = $filePath;
    if (function_exists('ai_log')) {
        ai_log('download_proxy', 'Proxy download qua Supabase', [
            'document_id' => $documentId,
            'user_id'     => $userId,
            'url'         => $remoteUrl,
        ]);
    }

    // Forward request toi Supabase bang cURL de giu ten file dung (Content-Disposition)
    if (function_exists('curl_init')) {
        $ch = curl_init($remoteUrl);
        if ($ch !== false) {
            $headers = ['Accept: */*'];
            $out = fopen('php://output', 'wb');
            if ($out !== false) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$sentDisposition) {
                    $lower = strtolower($header);
                    if (strpos($lower, 'content-type:') === 0) {
                        // bo qua, ta se set lai sau
                        return strlen($header);
                    }
                    if (strpos($lower, 'content-disposition:') === 0) {
                        $sentDisposition = true;
                    }
                    // Skip cac header noi dung (de set lai)
                    $skip = ['content-length:', 'transfer-encoding:', 'content-encoding:'];
                    foreach ($skip as $s) {
                        if (strpos($lower, $s) === 0) {
                            return strlen($header);
                        }
                    }
                    header(rtrim($header));
                    return strlen($header);
                });

                header('Content-Description: File Transfer');
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                // Neu co Content-Length tu Supabase thi buffer no, neu khong thi de chunked
                ob_clean();
                flush();

                $ok = curl_exec($ch);
                $errno = curl_errno($ch);
                $error = curl_error($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($errno !== 0) {
                    if (function_exists('ai_log')) {
                        ai_log('download_proxy_error', 'curl error khi proxy Supabase', [
                            'errno'       => $errno,
                            'curl_error'  => $error,
                            'document_id' => $documentId,
                        ]);
                    }
                    header('Location: ../pages/documents.php?error=' . urlencode('Khong the tai file tu Supabase: ' . $error));
                    exit;
                }

                if ($httpCode >= 200 && $httpCode < 300 && $ok !== false) {
                    exit;
                }

                if (function_exists('ai_log')) {
                    ai_log('download_proxy_failed', 'Supabase tra loi khi download', [
                        'http_status' => $httpCode,
                        'document_id' => $documentId,
                    ]);
                }
                header('Location: ../pages/documents.php?error=' . urlencode('File khong kha dung tren Supabase (HTTP ' . $httpCode . ')'));
                exit;
            }
            curl_close($ch);
        }
    }

    // Fallback: redirect truc tiep sang Supabase neu khong dung duoc cURL
    header('Location: ' . $remoteUrl);
    exit;
}

// ====== CASE 2: Local file (du lieu cu) ======
$absolutePath = null;
if ($filePath !== '' && is_file($filePath)) {
    $absolutePath = $filePath;
} else {
    $candidates = [
        __DIR__ . '/../uploads/documents/' . $doc['file_name'],
        __DIR__ . '/../' . ltrim($doc['file_name'], '/\\'),
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) {
            $absolutePath = $c;
            break;
        }
    }
}

if ($absolutePath === null) {
    // Co the file da duoc upload len Supabase nhung DB van luu URL - thu fetch qua Supabase
    // (truong hop migrate nua che).
    $publicUrl = null;
    if (class_exists('CloudStorage')) {
        $objPath = CloudStorage::pathToObjectPath($filePath);
        if ($objPath !== null && $objPath !== '') {
            $publicUrl = CloudStorage::getPublicUrl($objPath);
        }
    }
    if ($publicUrl !== null) {
        header('Location: ' . $publicUrl);
        exit;
    }

    header('Location: ../pages/documents.php?error=' . urlencode('File vật lý không tồn tại trên hệ thống'));
    exit;
}

$fileSize = filesize($absolutePath);

header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $fileSize);

ob_clean();
flush();
readfile($absolutePath);
exit;
