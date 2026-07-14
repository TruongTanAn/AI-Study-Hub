<?php
/**
 * AI Study Hub - Document QA (RAG Core) (Week 5 - AN)
 *
 * File nay KHONG goi OpenRouter truc tiep.
 * No chi:
 *   - nhan document_id (va user_id tu session)
 *   - lay noi dung tai lieu tu DB
 *   - xy ly context + prompt
 *   - goi OpenRouter thong qua config/ai.php va internal helper
 *
 * Hai cach su dung:
 *
 *  1. HTTP API (Frontend goi)
 *      POST /backend/document_qa.php
 *      { "document_id": 1, "message": "...", "conversation_id": 0 }
 *
 *  2. Noi bo (PHP goi)
 *      require 'document_qa.php';
 *      $res = ai_document_qa($conn, $userId, [
 *          'document_id'     => 1,
 *          'message'         => '...',
 *          'conversation_id' => 0,
 *      ]);
 */

// Helpers shared by other weeks - load only if available (do not modify those files)
$__an_utf8_helper = __DIR__ . '/../includes/utf8_helper.php';
if (is_file($__an_utf8_helper)) {
    require_once $__an_utf8_helper;
}
unset($__an_utf8_helper);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../config/ai_logger.php';
require_once __DIR__ . '/conversation_helpers.php';

// Chi load function thu vien tu chat_api.php, khong chay MAIN cua no
define('AI_CHAT_API_INCLUDE_ONLY', true);
require_once __DIR__ . '/chat_api.php'; // de tai su dung ai_call_openrouter + helpers

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

if (!function_exists('ai_respond')) {
    function ai_respond(bool $success, array $payload): void {
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

if (!function_exists('ai_build_document_prompt')) {
    /**
     * Tao system prompt cho RAG dua tren context tai lieu.
     */
    function ai_build_document_prompt(string $documentTitle, string $documentDescription, string $documentContext): string {
        $title       = trim($documentTitle) !== '' ? trim($documentTitle) : '(khong co tieu de)';
        $description = trim($documentDescription);
        $ctxMax      = (int) ai_config_get('max_context_chars', 6000);
        if (mb_strlen($documentContext) > $ctxMax) {
            $documentContext = mb_substr($documentContext, 0, $ctxMax) . '... [noi dung da rut gon]';
        }

        $prompt  = "Ban la tro ly AI cua AI Study Hub - mot nen tang hoctap thong minh cho sinh vien viet nam.\n";
        $prompt .= "Nhiem vu cua ban: TRA LOI CAC CAU HOI CUA NGUOI DUNG dua tren TAI LIEU duoc cung cap duoi day.\n";
        $prompt .= "Hay tra loi bang tieng Viet, ro rang, dung trong tam, ngan gon va de hieu.\n";
        $prompt .= "Neu tai lieu khong chua dap an, hay noi ro rang va khong tu bia thong tin sai.\n\n";
        $prompt .= "Tieu de tai lieu: " . $title . "\n";
        if ($description !== '') {
            $prompt .= "Mo ta: " . $description . "\n";
        }
        $prompt .= "\n=== NOI DUNG TAI LIEU ===\n" . $documentContext . "\n=== HET TAI LIEU ===\n";

        return $prompt;
    }
}

if (!function_exists('ai_document_qa')) {
    /**
     * Xu ly mot yeu cau RAG noi bo.
     *
     * @param mysqli $conn    database connection (global)
     * @param int    $userId  user dang nhap
     * @param array  $opts    {document_id:int, message:string, conversation_id?:int, title?:string, model?:string}
     * @return array{success:bool, conversation_id?:int, message?:string, error?:string, usage?:array}
     */
    function ai_document_qa(mysqli $conn, int $userId, array $opts): array {
        $documentId     = isset($opts['document_id'])     ? (int) $opts['document_id']     : 0;
        $message        = isset($opts['message'])         ? trim((string) $opts['message']) : '';
        $conversationId = isset($opts['conversation_id']) ? (int) $opts['conversation_id'] : 0;
        $incomingTitle  = isset($opts['title'])           ? trim((string) $opts['title'])   : '';
        $overrideModel  = isset($opts['model'])           ? trim((string) $opts['model'])   : '';

        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Nguoi dung khong hop le'];
        }
        if ($documentId <= 0) {
            return ['success' => false, 'error' => 'document_id khong hop le'];
        }
        if ($message === '') {
            return ['success' => false, 'error' => 'Tin nhan khong duoc de trong'];
        }
        $maxQuestion = (int) ai_config_get('max_question_chars', 2000);
        if (mb_strlen($message) > $maxQuestion) {
            return ['success' => false, 'error' => 'Cau hoi qua dai (toi da ' . $maxQuestion . ' ky tu)'];
        }

        // Kiem tra conversation thuoc user (neu co)
        if ($conversationId > 0) {
            if (!ai_db_user_owns_conversation($conn, $conversationId, $userId)) {
                return ['success' => false, 'error' => 'Cuoc tro chuyen khong ton tai hoac khong thuoc ve ban'];
            }
        }

        // Kiem tra quyen truy cap tai lieu
        $document = ai_db_get_document_for_user($conn, $documentId, $userId);
        if (!$document) {
            return ['success' => false, 'error' => 'Tai lieu khong ton tai hoac ban khong co quyen truy cap'];
        }

        $context = (string) ai_db_read_document_text(
            $document,
            (int) ai_config_get('max_context_chars', 6000)
        );
        if ($context === '') {
            $filePath = (string) ($document['file_path'] ?? '');
            $isSupabaseUrl = false;
            if (class_exists('CloudStorage')) {
                $isSupabaseUrl = CloudStorage::isSupabaseUrl($filePath);
            }
            if ($isSupabaseUrl) {
                $reason = 'supabase_fetch_or_extract_failed';
            } else {
                $reason = is_file($filePath) ? 'empty_content' : 'file_missing';
            }
            if (function_exists('ai_log')) {
                ai_log('rag_extract_failed', 'Tai lieu ton tai nhung khong trich duoc text', [
                    'document_id' => $documentId,
                    'file_path'   => $filePath,
                    'file_type'   => isset($document['file_type']) ? (string) $document['file_type'] : '',
                    'reason'      => $reason,
                ]);
            }
            return ['success' => false, 'error' => 'Khong the trich noi dung tu tai lieu (hoac tai lieu khong ho tro).'];
        }

        // Lay / tao conversation (gan voi document_id)
        $conversation = ai_db_ensure_conversation($conn, $userId, $conversationId, $documentId, $incomingTitle !== '' ? $incomingTitle : ('QA: ' . ($document['title'] ?? 'Tai lieu')));
        if (!$conversation) {
            return ['success' => false, 'error' => 'Khong the khoi tao cuoc tro chuyen'];
        }
        $conversationId = (int) $conversation['conversation_id'];

        // Cap nhat title lan dau
        $countRow = null;
        if ($stmtC = $conn->prepare('SELECT COUNT(*) AS c FROM chat_messages WHERE conversation_id = ? AND role = "user"')) {
            $stmtC->bind_param('i', $conversationId);
            if ($stmtC->execute()) {
                $r = $stmtC->get_result();
                $countRow = $r ? $r->fetch_assoc() : null;
            }
            $stmtC->close();
        }
        $hasUserMsgBefore = $countRow && isset($countRow['c']) ? ((int) $countRow['c']) > 0 : true;
        if (!$hasUserMsgBefore) {
            $titleForDb = $incomingTitle !== '' ? $incomingTitle : ('QA: ' . mb_substr($message, 0, 60));
            ai_db_update_conversation_meta($conn, $conversationId, $titleForDb);
        }

        // Luu user message
        ai_db_save_message($conn, $conversationId, 'user', $message, $documentId, 0, 0);

        // Build messages
        $maxPrompt = (int) ai_config_get('max_prompt_chars', 8000);

        $systemPrompt = ai_build_document_prompt(
            (string) ($document['title'] ?? ''),
            (string) ($document['description'] ?? ''),
            $context
        );
        $systemPrompt = mb_substr($systemPrompt, 0, $maxPrompt);
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        $history = ai_db_load_recent_messages($conn, $conversationId, 20);
        if (!empty($history)) {
            $last = end($history);
            if ($last && isset($last['role'], $last['message'])
                && $last['role'] === 'user' && trim($last['message']) === $message) {
                array_pop($history);
            }
        }
        foreach ($history as $h) {
            if (!isset($h['role'], $h['message'])) continue;
            if (!in_array($h['role'], ['user', 'assistant'], true)) continue;
            $messages[] = ['role' => (string) $h['role'], 'content' => (string) $h['message']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $total = 0;
        foreach ($messages as $m) {
            $total += mb_strlen((string) $m['content']);
        }
        if ($total > $maxPrompt && function_exists('ai_trim_messages')) {
            $messages = ai_trim_messages($messages, $maxPrompt);
        }

        // API key check
        $apiKey = (string) ai_config_get('api_key', '');
        if ($apiKey === '' || $apiKey === 'YOUR_OPENROUTER_API_KEY') {
            ai_log('invalid_api_key', 'API key chua duoc cau hinh (RAG)');
            ai_db_save_message($conn, $conversationId, 'system', '[ERROR] invalid_api_key', $documentId, 0, 0);
            return ['success' => false, 'error' => 'AI chua duoc cau hinh'];
        }

        $model = $overrideModel !== ''
            ? $overrideModel
            : (string) ai_config_get('model', 'meta-llama/llama-3.1-8b-instruct');

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => (float) ai_config_get('temperature', 0.7),
            'max_tokens'  => (int) ai_config_get('max_tokens', 1024),
            'top_p'       => (float) ai_config_get('top_p', 0.95),
            'stream'      => false,
        ];

        $endpoint = (string) ai_config_get('base_url', '');
        $referer  = (string) ai_config_get('referer', '');
        $appTitle = (string) ai_config_get('app_title', 'AI Study Hub');
        $timeout  = (int) ai_config_get('timeout', 45);

        if (!function_exists('ai_call_openrouter')) {
            return ['success' => false, 'error' => 'Thieu ai_call_openrouter (khoi tao that bai)'];
        }

        $resp = ai_call_openrouter($endpoint, $apiKey, $payload, $referer, $appTitle, $timeout, $model);
        if (!isset($resp['ok']) || $resp['ok'] !== true) {
            $errCode = isset($resp['error_code']) ? (string) $resp['error_code'] : 'openrouter_error';
            $errMsg  = isset($resp['error']) ? (string) $resp['error'] : 'Loi khong xac dinh';
            ai_db_save_message($conn, $conversationId, 'system', '[ERROR] ' . $errCode, $documentId, 0, 0);
            return ['success' => false, 'error' => $errMsg];
        }

        $reply = isset($resp['reply']) ? trim((string) $resp['reply']) : '';
        $usage = isset($resp['usage']) && is_array($resp['usage']) ? $resp['usage'] : [];

        if ($reply === '') {
            ai_log('response_error', 'OpenRouter (RAG) tra ve noi dung rong', [
                'conversation_id' => $conversationId,
                'document_id'     => $documentId,
            ]);
            return ['success' => false, 'error' => 'AI tra ve phan hoi rong'];
        }

        $pt = isset($usage['prompt_tokens'])     ? (int) $usage['prompt_tokens']     : 0;
        $ct = isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : 0;

        ai_db_save_message($conn, $conversationId, 'assistant', $reply, $documentId, $pt, $ct);

        return [
            'success'         => true,
            'conversation_id' => $conversationId,
            'message'         => $reply,
            'model'           => $model,
            'usage'           => ['prompt_tokens' => $pt, 'completion_tokens' => $ct],
        ];
    }
}

// =============================================================
// HTTP entrypoint
// =============================================================

if (!defined('AI_DOCUMENT_QA_HTTP_SKIP') && php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');

    // Chi xu ly khi la request truc tiep, tranh tu goi khi file nay chi require_no_once tu module khac
    $selfName = basename(__FILE__);
    $calledDirectly = false;
    if (isset($_SERVER['SCRIPT_FILENAME']) && basename((string) $_SERVER['SCRIPT_FILENAME']) === $selfName) {
        $calledDirectly = true;
    }
    if (PHP_SAPI !== 'cgi-fcgi' && $calledDirectly && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            ai_respond(false, ['error' => 'Phien dang nhap het han. Vui long dang nhap lai.']);
            return;
        }
        $userId = (int) $_SESSION['user_id'];

        $rawBody = file_get_contents('php://input');
        $input   = json_decode((string) $rawBody, true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $result = ai_document_qa($conn, $userId, [
            'document_id'     => isset($input['document_id'])     ? (int) $input['document_id']     : 0,
            'message'         => isset($input['message'])         ? (string) $input['message']       : '',
            'conversation_id' => isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0,
            'title'           => isset($input['title'])           ? (string) $input['title']         : '',
            'model'           => isset($input['model'])           ? (string) $input['model']         : '',
        ]);

        if (!empty($result['success'])) {
            ai_respond(true, $result);
        } else {
            http_response_code(400);
            ai_respond(false, ['error' => isset($result['error']) ? $result['error'] : 'Loi khong xac dinh']);
        }
        return;
    }
}
