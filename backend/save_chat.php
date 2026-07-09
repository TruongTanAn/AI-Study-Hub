<?php
/**
 * AI Study Hub - Save Chat (Week 5 - AN)
 *
 * Luu thu cong mot message vao bang chat_messages.
 * chat_api.php da tu dong luu khi goi AI, nhung save_chat.php cho phep frontend
 * luu tin nhan ngoai luong (vi du: them system note, override luu conversation...).
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    an_respond(false, ['error' => 'Phuong thuc khong duoc phep']);
    exit;
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

$rawBody = file_get_contents('php://input');
$input   = json_decode((string) $rawBody, true);
if (!is_array($input)) {
    $input = $_POST;
}

$conversationId = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;
$title          = isset($input['title'])           ? trim((string) $input['title']) : '';
$role           = isset($input['role'])            ? trim((string) $input['role'])  : 'user';
$message        = isset($input['message'])         ? trim((string) $input['message']) : '';
$documentId     = isset($input['document_id'])     ? (int) $input['document_id']    : 0;
$promptTokens   = isset($input['prompt_tokens'])   ? (int) $input['prompt_tokens']  : 0;
$completionTokens = isset($input['completion_tokens']) ? (int) $input['completion_tokens'] : 0;
$createIfMissing = isset($input['create']) ? ((int) $input['create'] === 1 || $input['create'] === true || $input['create'] === 'true') : true;

if ($message === '' && $conversationId <= 0) {
    an_respond(false, ['error' => 'Thieu noi dung tin nhan']);
    exit;
}

if ($message !== '') {
    $maxChars = 8000;
    if (mb_strlen($message) > $maxChars) {
        an_respond(false, ['error' => 'Tin nhan qua dai (toi da ' . $maxChars . ' ky tu)']);
        exit;
    }
}

$allowedRoles = ['user', 'assistant', 'system'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'user';
}

if ($conversationId > 0) {
    if (!ai_db_user_owns_conversation($conn, $conversationId, $userId)) {
        an_respond(false, ['error' => 'Cuoc tro chuyen khong ton tai hoac khong thuoc ve ban']);
        exit;
    }
    if ($title !== '') {
        ai_db_update_conversation_meta($conn, $conversationId, $title);
    }
} else {
    if (!$createIfMissing) {
        an_respond(false, ['error' => 'conversation_id khong hop le']);
        exit;
    }
    $defaultTitle = $title !== '' ? $title : ($message !== '' ? mb_substr($message, 0, 80) : 'Cuoc tro chuyen moi');
    $conversation = ai_db_ensure_conversation($conn, $userId, 0, $documentId, $defaultTitle);
    if (!$conversation) {
        an_respond(false, ['error' => 'Khong the tao cuoc tro chuyen']);
        exit;
    }
    $conversationId = (int) $conversation['conversation_id'];
}

if ($message === '') {
    an_respond(true, [
        'conversation_id' => $conversationId,
        'message_id'       => 0,
        'skipped'          => true,
    ]);
    exit;
}

$messageId = ai_db_save_message(
    $conn,
    $conversationId,
    $role,
    $message,
    $documentId,
    $promptTokens,
    $completionTokens
);

if ($messageId <= 0) {
    an_respond(false, ['error' => 'Khong the luu tin nhan']);
    exit;
}

an_respond(true, [
    'conversation_id' => $conversationId,
    'message_id'       => $messageId,
    'role'             => $role,
    'saved_at'         => date('Y-m-d H:i:s'),
]);