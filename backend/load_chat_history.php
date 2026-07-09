<?php
/**
 * AI Study Hub - Load Chat History (Week 5 - AN)
 *
 * Tra ve JSON:
 *  - ?scope=conversations: danh sach conversations cua user (sidebar).
 *  - ?scope=messages&conversation_id=X: danh sach messages cua conversation do.
 *  - (mac dinh): conversations neu khong co conversation_id; messages neu co.
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

$scope         = isset($_GET['scope']) ? trim((string) $_GET['scope']) : '';
$conversationId = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;

if ($scope === '') {
    $scope = $conversationId > 0 ? 'messages' : 'conversations';
}

if ($scope === 'messages') {
    if ($conversationId <= 0) {
        an_respond(false, ['error' => 'conversation_id khong hop le']);
        exit;
    }
    if (!ai_db_user_owns_conversation($conn, $conversationId, $userId)) {
        http_response_code(403);
        an_respond(false, ['error' => 'Cuoc tro chuyen khong ton tai hoac khong thuoc ve ban']);
        exit;
    }

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;
    $limit = max(1, min(500, $limit));

    $stmt = $conn->prepare(
        'SELECT message_id, conversation_id, role, message, document_id,
                prompt_tokens, completion_tokens, created_at
         FROM chat_messages
         WHERE conversation_id = ?
         ORDER BY message_id ASC
         LIMIT ?'
    );
    if ($stmt === false) {
        an_respond(false, ['error' => 'Khong the truy van messages: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('ii', $conversationId, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = [];
    while ($row = $res->fetch_assoc()) {
        if (function_exists('to_utf8')) {
            $row['message'] = to_utf8($row['message']);
        }
        $row['message_id']        = (int) $row['message_id'];
        $row['conversation_id']   = (int) $row['conversation_id'];
        $row['document_id']       = $row['document_id'] !== null ? (int) $row['document_id'] : null;
        $row['prompt_tokens']     = (int) $row['prompt_tokens'];
        $row['completion_tokens'] = (int) $row['completion_tokens'];
        $messages[] = $row;
    }
    $stmt->close();

    an_respond(true, [
        'conversation_id' => $conversationId,
        'count'           => count($messages),
        'messages'        => $messages,
    ]);
    exit;
}

// scope = conversations (sidebar)
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
$limit = max(1, min(200, $limit));
$documentFilter = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;

if ($documentFilter > 0) {
    $stmt = $conn->prepare(
        'SELECT c.conversation_id, c.title, c.document_id, c.model, c.created_at, c.updated_at,
                (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.conversation_id) AS message_count
         FROM conversations c
         WHERE c.user_id = ? AND c.document_id = ?
         ORDER BY c.updated_at DESC
         LIMIT ?'
    );
    if ($stmt === false) {
        an_respond(false, ['error' => 'Khong the truy van conversations: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('iii', $userId, $documentFilter, $limit);
} else {
    $stmt = $conn->prepare(
        'SELECT c.conversation_id, c.title, c.document_id, c.model, c.created_at, c.updated_at,
                (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id = c.conversation_id) AS message_count
         FROM conversations c
         WHERE c.user_id = ?
         ORDER BY c.updated_at DESC
         LIMIT ?'
    );
    if ($stmt === false) {
        an_respond(false, ['error' => 'Khong the truy van conversations: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('ii', $userId, $limit);
}

$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($row = $res->fetch_assoc()) {
    if (function_exists('to_utf8')) {
        $row['title'] = to_utf8($row['title']);
    }
    $row['conversation_id'] = (int) $row['conversation_id'];
    $row['document_id']     = $row['document_id'] !== null ? (int) $row['document_id'] : null;
    $row['message_count']   = (int) $row['message_count'];
    $rows[] = $row;
}
$stmt->close();

an_respond(true, [
    'count'         => count($rows),
    'conversations' => $rows,
]);