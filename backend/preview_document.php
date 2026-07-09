<?php
/**
 * AI Study Hub - Document Preview Backend (Week 5 - AN)
 *
 * Tra ve metadata cua tai lieu + preview text (neu co) de hien thi trong chatbot.
 * Su dung duoc cho ca frontend preview va document_qa RAG.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/conversation_helpers.php';

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

if (!function_exists('an_respond')) {
    function an_respond(bool $success, array $payload): void {
        $out = $success
            ? array_merge(['success' => true], $payload)
            : array_merge(['success' => false], $payload);
        $json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['success' => false, 'error' => 'Khong the ma hoa phan hoi JSON'], JSON_UNESCAPED_UNICODE);
        }
        echo (string) $json;
    }
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    an_respond(false, ['error' => 'Phien dang nhap het han. Vui long dang nhap lai.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$documentId = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;
$includeText = !isset($_GET['meta_only']) || (string) $_GET['meta_only'] !== '1';

if ($documentId <= 0) {
    http_response_code(400);
    an_respond(false, ['error' => 'document_id khong hop le']);
    exit;
}

$document = ai_db_get_document_for_user($conn, $documentId, $userId);
if (!$document) {
    http_response_code(404);
    an_respond(false, ['error' => 'Tai lieu khong ton tai hoac ban khong co quyen truy cap']);
    exit;
}

$filePath     = (string) $document['file_path'];
$fileExists   = is_file($filePath) && is_readable($filePath);
$fileUrl      = '../uploads/documents/' . rawurlencode((string) $document['file_name']);
$ext          = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$previewText  = '';
$previewType  = 'unsupported';

if ($fileExists) {
    $mimeMap = [
        'pdf'  => 'pdf',
        'txt'  => 'text',
        'md'   => 'text',
        'jpg'  => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image',
    ];
    $previewType = $mimeMap[$ext] ?? 'unsupported';

    if (in_array($ext, ['txt', 'md'], true) && $includeText) {
        $raw = @file_get_contents($filePath);
        if ($raw !== false) {
            $previewText = (string) $raw;
            if (function_exists('to_utf8')) {
                $previewText = to_utf8($previewText);
            }
            if (mb_strlen($previewText) > 6000) {
                $previewText = mb_substr($previewText, 0, 6000) . '... [noi dung da rut gon]';
            }
        }
    }
}

an_respond(true, [
    'document' => [
        'document_id'   => (int) $document['document_id'],
        'title'         => (string) $document['title'],
        'description'   => (string) ($document['description'] ?? ''),
        'file_name'     => (string) $document['file_name'],
        'original_name' => (string) $document['original_name'],
        'file_type'     => (string) $document['file_type'],
        'visibility'    => (string) $document['visibility'],
        'status'        => (string) $document['status'],
    ],
    'file' => [
        'path'    => $filePath,
        'url'     => $fileUrl,
        'exists'  => $fileExists,
        'ext'     => $ext,
        'preview' => $previewType,
        'text'    => $previewText,
    ],
]);