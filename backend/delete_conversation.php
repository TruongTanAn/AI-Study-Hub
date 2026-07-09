<?php
/**
 * AI Study Hub - Delete Conversation (Week 5 - AN)
 *
 * Endpoint: POST ../backend/delete_conversation.php
 * Body: { conversation_id: int }
 *
 * Xoa conversation + tat ca chat_messages lien quan.
 * Chi xoa duoc conversation thuoc user hien tai (kiem tra ownership).
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
if ($userId <= 0) {
    http_response_code(401);
    an_respond(false, ['error' => 'Nguoi dung khong hop le']);
    exit;
}

// Nhan input: hoac tu JSON body, hoac tu form-encoded
$raw = file_get_contents('php://input');
$payload = [];
if ($raw !== '' && $raw !== false) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (empty($payload['conversation_id'])) {
    $payload['conversation_id'] = $_POST['conversation_id'] ?? $_GET['conversation_id'] ?? 0;
}

$conversationId = (int) ($payload['conversation_id'] ?? 0);
if ($conversationId <= 0) {
    http_response_code(400);
    an_respond(false, ['error' => 'conversation_id khong hop le']);
    exit;
}

// Kiem tra quyen so huu (tranh user khac xoa conversation cua nguoi khac)
if (!ai_db_user_owns_conversation($conn, $conversationId, $userId)) {
    http_response_code(403);
    an_respond(false, ['error' => 'Cuoc tro chuyen khong ton tai hoac khong thuoc ve ban']);
    exit;
}

// Xoa chat_messages truoc (FK constraint)
$stmt = $conn->prepare('DELETE FROM chat_messages WHERE conversation_id = ?');
if ($stmt === false) {
    http_response_code(500);
    an_respond(false, ['error' => 'Khong the xoa messages: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $conversationId);
$stmt->execute();
$deletedMessages = $stmt->affected_rows;
$stmt->close();

// Xoa conversation
$stmt = $conn->prepare('DELETE FROM conversations WHERE conversation_id = ? AND user_id = ?');
if ($stmt === false) {
    http_response_code(500);
    an_respond(false, ['error' => 'Khong the xoa conversation: ' . $conn->error]);
    exit;
}
$stmt->bind_param('ii', $conversationId, $userId);
$ok = $stmt->execute();
$deletedConv = $stmt->affected_rows;
$stmt->close();

if (!$ok || $deletedConv <= 0) {
    http_response_code(500);
    an_respond(false, ['error' => 'Khong the xoa conversation']);
    exit;
}

an_respond(true, [
    'conversation_id'    => $conversationId,
    'deleted_messages'   => $deletedMessages,
    'deleted_conversation' => $deletedConv,
]);