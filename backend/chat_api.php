<?php
/**
 * AI Study Hub - AI Gateway (Week 5 - AN)
 *
 * File DUY NHAT duoc phep goi OpenRouter trong toan bo du an.
 * Moi request AI phai di qua file nay.
 *
 * Endpoint: POST /backend/chat_api.php
 * JSON body (Content-Type: application/json) hoac form-urlencoded:
 *  {
 *      "message":         "cau hoi cua nguoi dung",
 *      "conversation_id": 0,
 *      "title":           "",
 *      "document_id":     0,
 *      "system_prompt":   "",
 *      "model":           ""
 *  }
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

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

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

if (!function_exists('ai_trim_messages')) {
    function ai_trim_messages(array $messages, int $maxChars): array {
        $system = null;
        foreach ($messages as $m) {
            if (isset($m['role']) && $m['role'] === 'system') {
                $system = $m;
                break;
            }
        }
        $others = [];
        foreach ($messages as $m) {
            if (!isset($m['role']) || $m['role'] !== 'system') {
                $others[] = $m;
            }
        }
        $last = !empty($others) ? $others[count($others) - 1] : null;
        $others = !empty($last) ? array_slice($others, 0, count($others) - 1) : $others;

        $result = [];
        if ($system !== null) {
            $result[] = $system;
            $total = mb_strlen((string) $system['content']);
        } else {
            $total = 0;
        }

        $packed = [];
        for ($i = count($others) - 1; $i >= 0; $i--) {
            $candidate = array_merge(array_reverse($packed), [$others[$i]]);
            $sum = $total;
            foreach ($candidate as $c) {
                $sum += mb_strlen((string) $c['content']);
            }
            if ($sum > $maxChars) {
                break;
            }
            $packed[] = $others[$i];
        }
        foreach (array_reverse($packed) as $m) {
            $result[] = $m;
        }
        if ($last !== null) {
            $result[] = $last;
        }
        return $result;
    }
}

if (!function_exists('ai_call_openrouter')) {
    /**
     * @return array{ok:bool, reply:string, usage:array, error:string, error_code:string, http_status:int}
     */
    function ai_call_openrouter(
        string $endpoint,
        string $apiKey,
        array $payload,
        string $referer,
        string $appTitle,
        int $timeout,
        string $modelForLog
    ): array {
        if ($endpoint === '' || $apiKey === '') {
            return [
                'ok'          => false,
                'error'       => 'AI chua duoc cau hinh',
                'error_code'  => 'openrouter_error',
                'http_status' => 500,
            ];
        }
        if (!function_exists('curl_init')) {
            return [
                'ok'          => false,
                'error'       => 'Thieu curl extension trong PHP',
                'error_code'  => 'openrouter_error',
                'http_status' => 500,
            ];
        }

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [
                'ok'          => false,
                'error'       => 'Khong the khoi tao curl',
                'error_code'  => 'openrouter_error',
                'http_status' => 500,
            ];
        }

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonBody === false) {
            curl_close($ch);
            return [
                'ok'          => false,
                'error'       => 'Khong the ma hoa payload',
                'error_code'  => 'request_error',
                'http_status' => 500,
            ];
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];
        if ($referer !== '') {
            $headers[] = 'HTTP-Referer: ' . $referer;
        }
        if ($appTitle !== '') {
            $headers[] = 'X-Title: ' . $appTitle;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(15, $timeout));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $responseBody = curl_exec($ch);
        $curlErrno    = curl_errno($ch);
        $curlError    = curl_error($ch);
        $httpStatus   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0) {
            $event = ($curlErrno === CURLE_OPERATION_TIMEDOUT) ? 'timeout' : 'request_error';
            ai_log($event, 'Loi curl khi goi OpenRouter', [
                'errno'       => $curlErrno,
                'curl_error'  => $curlError,
                'http_status' => $httpStatus,
                'model'       => $modelForLog,
            ]);
            $msg = $event === 'timeout'
                ? 'AI phan hoi qua cham (timeout). Vui long thu lai.'
                : 'Khong the ket noi den AI. Vui long kiem tra mang.';
            return [
                'ok'          => false,
                'error'       => $msg,
                'error_code'  => $event,
                'http_status' => 502,
            ];
        }

        if ($responseBody === false || $responseBody === '') {
            ai_log('response_error', 'OpenRouter tra ve response rong', ['http_status' => $httpStatus]);
            return [
                'ok'          => false,
                'error'       => 'AI tra ve phan hoi rong',
                'error_code'  => 'response_error',
                'http_status' => 502,
            ];
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            ai_log('json_decode_error', 'Khong the giai ma JSON tu OpenRouter', [
                'http_status' => $httpStatus,
                'snippet'     => mb_substr((string) $responseBody, 0, 200),
            ]);
            return [
                'ok'          => false,
                'error'       => 'Phan hoi tu AI khong hop le',
                'error_code'  => 'json_decode_error',
                'http_status' => 502,
            ];
        }

        if ($httpStatus === 401 || $httpStatus === 403) {
            ai_log('invalid_api_key', 'OpenRouter tu choi quyen truy cap', ['http_status' => $httpStatus]);
            return [
                'ok'          => false,
                'error'       => 'API key khong hop le hoac da het han',
                'error_code'  => 'invalid_api_key',
                'http_status' => 401,
            ];
        }

        if ($httpStatus === 429) {
            ai_log('rate_limit', 'OpenRouter tra loi 429 rate limit', ['http_status' => $httpStatus]);
            return [
                'ok'          => false,
                'error'       => 'AI dang qua tai, vui long thu lai sau giay lat',
                'error_code'  => 'rate_limit',
                'http_status' => 429,
            ];
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            $errMsg = isset($decoded['error']['message'])
                ? (string) $decoded['error']['message']
                : ('OpenRouter HTTP ' . $httpStatus);
            ai_log('openrouter_error', 'OpenRouter tra ve loi', [
                'http_status' => $httpStatus,
                'error'       => $errMsg,
            ]);
            return [
                'ok'          => false,
                'error'       => $errMsg,
                'error_code'  => 'openrouter_error',
                'http_status' => $httpStatus,
            ];
        }

        $reply = '';
        if (isset($decoded['choices'][0]['message']['content'])) {
            $reply = (string) $decoded['choices'][0]['message']['content'];
        } elseif (isset($decoded['choices'][0]['text'])) {
            $reply = (string) $decoded['choices'][0]['text'];
        }

        $usage = [];
        if (isset($decoded['usage']) && is_array($decoded['usage'])) {
            $usage['prompt_tokens']     = isset($decoded['usage']['prompt_tokens'])     ? (int) $decoded['usage']['prompt_tokens']     : 0;
            $usage['completion_tokens'] = isset($decoded['usage']['completion_tokens']) ? (int) $decoded['usage']['completion_tokens'] : 0;
        }

        return [
            'ok'          => true,
            'reply'       => $reply,
            'usage'       => $usage,
            'http_status' => $httpStatus,
        ];
    }
}

// =============================================================
// MAIN
// (Chi chay khi file duoc goi truc tiep, khong goi tu file khac qua require)
// =============================================================

if (!defined('AI_CHAT_API_INCLUDE_ONLY')) {

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ai_respond(false, ['error' => 'Phuong thuc khong duoc phep']);
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    ai_log('invalid_session', 'Session het han hoac chua dang nhap');
    ai_respond(false, ['error' => 'Phien dang nhap het han. Vui long dang nhap lai.']);
    return;
}
$userId = (int) $_SESSION['user_id'];
if ($userId <= 0) {
    http_response_code(401);
    ai_respond(false, ['error' => 'Nguoi dung khong hop le']);
    return;
}

$rawBody = file_get_contents('php://input');
$input   = json_decode((string) $rawBody, true);
if (!is_array($input)) {
    $input = $_POST;
}

$userMessage    = isset($input['message'])         ? trim((string) $input['message'])         : '';
$conversationId = isset($input['conversation_id']) ? (int) $input['conversation_id']         : 0;
$incomingTitle  = isset($input['title'])           ? trim((string) $input['title'])           : '';
$documentIdRaw  = isset($input['document_id'])     ? (int) $input['document_id']             : 0;
$overridePrompt = isset($input['system_prompt'])   ? (string) $input['system_prompt']         : '';
$overrideModel  = isset($input['model'])           ? trim((string) $input['model'])           : '';

$maxQuestionChars = (int) ai_config_get('max_question_chars', 2000);
$maxPromptChars   = (int) ai_config_get('max_prompt_chars', 8000);

if ($userMessage === '') {
    ai_respond(false, ['error' => 'Tin nhan khong duoc de trong']);
    return;
}
if (mb_strlen($userMessage) > $maxQuestionChars) {
    ai_respond(false, ['error' => 'Cau hoi qua dai (toi da ' . $maxQuestionChars . ' ky tu)']);
    return;
}

$danger = [
    '/^\s*ignore (all|previous) (instructions|prompts)/i',
    '/^\s*forget (everything|all)/i',
    '/system\s*:\s*you are now/i',
    '/\bact as\b.*\bwithout (any )?restrictions?\b/i',
];
foreach ($danger as $re) {
    if (preg_match($re, $userMessage)) {
        ai_log('prompt_injection', 'Phat hien prompt injection dang ngo', [
            'preview' => mb_substr($userMessage, 0, 200),
        ]);
        ai_respond(false, ['error' => 'Cau hoi vi pham chinh sach noi dung']);
        return;
    }
}

$conversationId = max(0, $conversationId);
if ($conversationId > 0) {
    if (!ai_db_user_owns_conversation($conn, $conversationId, $userId)) {
        ai_respond(false, ['error' => 'Cuoc tro chuyen khong ton tai hoac khong thuoc ve ban']);
        return;
    }
}

if ($documentIdRaw > 0) {
    $docCheck = ai_db_get_document_for_user($conn, $documentIdRaw, $userId);
    if (!$docCheck) {
        ai_respond(false, ['error' => 'Tai lieu khong ton tai hoac ban khong co quyen truy cap']);
        return;
    }
}

$conversation = ai_db_ensure_conversation($conn, $userId, $conversationId, $documentIdRaw, $incomingTitle);
if (!$conversation) {
    ai_respond(false, ['error' => 'Khong the khoi tao cuoc tro chuyen']);
    return;
}
$conversationId = (int) $conversation['conversation_id'];

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
    $titleForDb = $incomingTitle !== '' ? $incomingTitle : mb_substr($userMessage, 0, 80);
} elseif ($incomingTitle !== '') {
    $titleForDb = $incomingTitle;
} else {
    $titleForDb = '';
}
if ($titleForDb !== '') {
    ai_db_update_conversation_meta($conn, $conversationId, $titleForDb);
}

ai_db_save_message($conn, $conversationId, 'user', $userMessage, $documentIdRaw, 0, 0);

$messages = [];
$systemPrompt = $overridePrompt !== ''
    ? $overridePrompt
    : (string) ai_config_get('system_prompt', '');

$documentContext = '';
if ($documentIdRaw > 0) {
    $docRow = ai_db_get_document_for_user($conn, $documentIdRaw, $userId);
    if ($docRow) {
        $documentContext = (string) ai_db_read_document_text(
            $docRow,
            (int) ai_config_get('max_context_chars', 6000)
        );
    }
}
if ($documentContext !== '') {
    $systemPrompt .= "\n\n=== NOI DUNG TAI LIEU ===\n" . $documentContext . "\n=== HET TAI LIEU ===\n";
}

$systemPrompt = mb_substr($systemPrompt, 0, $maxPromptChars);
$messages[] = ['role' => 'system', 'content' => $systemPrompt];

$dbHistory = ai_db_load_recent_messages($conn, $conversationId, 20);
if (!empty($dbHistory)) {
    $last = end($dbHistory);
    if ($last && isset($last['role'], $last['message'])
        && $last['role'] === 'user'
        && trim($last['message']) === $userMessage) {
        array_pop($dbHistory);
    }
}
foreach ($dbHistory as $h) {
    if (!isset($h['role'], $h['message'])) continue;
    if (!in_array($h['role'], ['user', 'assistant'], true)) continue;
    $messages[] = ['role' => (string) $h['role'], 'content' => (string) $h['message']];
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

$totalChars = 0;
foreach ($messages as $m) {
    $totalChars += mb_strlen((string) $m['content']);
}
if ($totalChars > $maxPromptChars) {
    $messages = ai_trim_messages($messages, $maxPromptChars);
}

$apiKey = (string) ai_config_get('api_key', '');
if ($apiKey === '' || $apiKey === 'YOUR_OPENROUTER_API_KEY') {
    ai_log('invalid_api_key', 'API key chua duoc cau hinh');
    ai_db_save_message($conn, $conversationId, 'system', '[ERROR] invalid_api_key', $documentIdRaw, 0, 0);
    ai_respond(false, ['error' => 'AI chua duoc cau hinh. Vui long lien quan quan tri vien.']);
    return;
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

$response = ai_call_openrouter($endpoint, $apiKey, $payload, $referer, $appTitle, $timeout, $model);

if (!isset($response['ok']) || $response['ok'] !== true) {
    $errCode = isset($response['error_code']) ? (string) $response['error_code'] : 'openrouter_error';
    $errMsg  = isset($response['error']) ? (string) $response['error'] : 'Loi khong xac dinh';

    ai_db_save_message($conn, $conversationId, 'system', '[ERROR] ' . $errCode, $documentIdRaw, 0, 0);

    $httpStatus = isset($response['http_status']) ? (int) $response['http_status'] : 502;
    if ($httpStatus < 400 || $httpStatus >= 600) {
        $httpStatus = 502;
    }
    http_response_code($httpStatus);

    ai_respond(false, ['error' => $errMsg]);
    return;
}

$reply = isset($response['reply']) ? trim((string) $response['reply']) : '';
$usage = isset($response['usage']) && is_array($response['usage']) ? $response['usage'] : [];

if ($reply === '') {
    ai_log('response_error', 'OpenRouter tra ve noi dung rong', [
        'conversation_id' => $conversationId,
    ]);
    ai_db_save_message($conn, $conversationId, 'system', '[ERROR] response_error', $documentIdRaw, 0, 0);
    http_response_code(502);
    ai_respond(false, ['error' => 'AI tra ve phan hoi rong. Vui long thu lai.']);
    return;
}

$promptTokens     = isset($usage['prompt_tokens'])     ? (int) $usage['prompt_tokens']     : 0;
$completionTokens = isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : 0;

ai_db_save_message(
    $conn,
    $conversationId,
    'assistant',
    $reply,
    $documentIdRaw,
    $promptTokens,
    $completionTokens
);

http_response_code(200);
ai_respond(true, [
    'conversation_id' => $conversationId,
    'message'         => $reply,
    'model'           => $model,
    'usage'           => [
        'prompt_tokens'     => $promptTokens,
        'completion_tokens' => $completionTokens,
    ],
]);
return;

} // end AI_CHAT_API_INCLUDE_ONLY
