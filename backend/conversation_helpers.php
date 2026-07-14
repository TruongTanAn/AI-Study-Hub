<?php
/**
 * AI Study Hub - Conversation helpers (Week 5 - AN)
 *
 * Cung cap cac ham thao tac voi bang conversations & chat_messages.
 * File nay KHONG goi OpenRouter. Moi request AI deu phai di qua chat_api.php.
 */

require_once __DIR__ . '/../config/ai.php';

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

// Helpers shared by other weeks - load only if available (do not modify those files)
$__an_utf8_helper = __DIR__ . '/../includes/utf8_helper.php';
if (is_file($__an_utf8_helper)) {
    require_once $__an_utf8_helper;
}
unset($__an_utf8_helper);

// Document text extractor (PDF / DOCX / PPTX / TXT / MD) - thuan PHP, khong can Composer
$__an_doc_extractor = __DIR__ . '/document_extractor.php';
if (is_file($__an_doc_extractor)) {
    require_once $__an_doc_extractor;
}
unset($__an_doc_extractor);

if (!function_exists('ai_db_ensure_conversation')) {
    /**
     * Lay hoac tao conversation theo user_id + optional document_id.
     * Neu $conversationId > 0, se load lai (neu khong thuoc user se tra ve null).
     *
     * @return array|null ['conversation_id' => int, ...]
     */
    function ai_db_ensure_conversation(
        mysqli $conn,
        int $userId,
        int $conversationId = 0,
        int $documentId = 0,
        string $title = ''
    ): ?array {
        $userId = max(0, $userId);
        if ($userId <= 0) {
            return null;
        }

        // Load existing conversation
        if ($conversationId > 0) {
            $stmt = $conn->prepare(
                'SELECT * FROM conversations WHERE conversation_id = ? AND user_id = ? LIMIT 1'
            );
            if ($stmt === false) {
                return null;
            }
            $stmt->bind_param('ii', $conversationId, $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return $row;
            }
            // Neu khong ton tai -> tao moi
        }

        $defaultTitle = trim($title) !== '' ? trim($title) : 'Cuoc tro chuyen moi';
        $docParam = $documentId > 0 ? $documentId : null;
        $model = (string) ai_config_get('model', '');

        $stmt = $conn->prepare(
            'INSERT INTO conversations (user_id, title, document_id, model) VALUES (?, ?, ?, ?)'
        );
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('isis', $userId, $defaultTitle, $docParam, $model);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $newId = $conn->insert_id;
        $stmt->close();

        return [
            'conversation_id' => (int) $newId,
            'user_id'         => $userId,
            'title'           => $defaultTitle,
            'document_id'     => $docParam !== null ? (int) $docParam : null,
            'model'           => $model,
        ];
    }
}

if (!function_exists('ai_db_update_conversation_meta')) {
    /**
     * Cap nhat tieu de conversation (neu la user message dau tien) va updated_at.
     */
    function ai_db_update_conversation_meta(
        mysqli $conn,
        int $conversationId,
        ?string $title = null
    ): bool {
        if ($conversationId <= 0) {
            return false;
        }
        if ($title !== null) {
            $title = trim($title);
            if ($title === '') {
                $title = 'Cuoc tro chuyen moi';
            }
            $title = mb_substr($title, 0, 255);
            $stmt = $conn->prepare('UPDATE conversations SET title = ? WHERE conversation_id = ?');
            if ($stmt === false) {
                return false;
            }
            $stmt->bind_param('si', $title, $conversationId);
            $ok = $stmt->execute();
            $stmt->close();
            return (bool) $ok;
        }
        $stmt = $conn->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = ?');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('i', $conversationId);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }
}

if (!function_exists('ai_db_save_message')) {
    /**
     * Luu mot message user / assistant / system vao chat_messages.
     *
     * @return int message_id moi (hoac 0 neu loi)
     */
    function ai_db_save_message(
        mysqli $conn,
        int $conversationId,
        string $role,
        string $message,
        int $documentId = 0,
        int $promptTokens = 0,
        int $completionTokens = 0
    ): int {
        if ($conversationId <= 0) {
            return 0;
        }

        $allowedRoles = ['user', 'assistant', 'system'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }
        $message = (string) $message;

        $docParam = $documentId > 0 ? $documentId : null;

        $stmt = $conn->prepare(
            'INSERT INTO chat_messages (conversation_id, role, message, document_id, prompt_tokens, completion_tokens)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if ($stmt === false) {
            return 0;
        }
        $stmt->bind_param(
            'isssii',
            $conversationId,
            $role,
            $message,
            $docParam,
            $promptTokens,
            $completionTokens
        );
        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }
        $newId = (int) $conn->insert_id;
        $stmt->close();

        ai_db_update_conversation_meta($conn, $conversationId);

        return $newId;
    }
}

if (!function_exists('ai_db_load_recent_messages')) {
    /**
     * Load lich su tin nắn gan nhat trong conversation (gioi han $limit).
     * Tra ve mang theo thu tu thoi gian tang dan: [{role, message}, ...]
     *
     * @return array<int, array{role:string,message:string}>
     */
    function ai_db_load_recent_messages(
        mysqli $conn,
        int $conversationId,
        int $limit = 10
    ): array {
        if ($conversationId <= 0) {
            return [];
        }
        $limit = max(1, min(50, $limit));

        $stmt = $conn->prepare(
            'SELECT role, message
             FROM chat_messages
             WHERE conversation_id = ?
             ORDER BY message_id DESC
             LIMIT ?'
        );
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $conversationId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'role'    => (string) $r['role'],
                'message' => (string) $r['message'],
            ];
        }
        $stmt->close();

        // Dao nguoc de co thu tu thoi gian tang dan
        return array_reverse($rows);
    }
}

if (!function_exists('ai_db_user_owns_conversation')) {
    /**
     * Kiem tra conversation co thuoc user khong.
     */
    function ai_db_user_owns_conversation(
        mysqli $conn,
        int $conversationId,
        int $userId
    ): bool {
        if ($conversationId <= 0 || $userId <= 0) {
            return false;
        }
        $stmt = $conn->prepare(
            'SELECT 1 FROM conversations WHERE conversation_id = ? AND user_id = ? LIMIT 1'
        );
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('ii', $conversationId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = $res && $res->num_rows > 0;
        $stmt->close();
        return (bool) $ok;
    }
}

if (!function_exists('ai_db_get_document_for_user')) {
    /**
     * Lay document neu ton tai va user duoc phep xem.
     * - Owner (user upload) duoc phep xem bat ke status (pending/approved/rejected)
     * - Nguoi khac chi xem duoc khi status = 'approved' va visibility = 'public'
     */
    function ai_db_get_document_for_user(
        mysqli $conn,
        int $documentId,
        int $userId
    ): ?array {
        if ($documentId <= 0 || $userId <= 0) {
            return null;
        }
        $stmt = $conn->prepare(
            'SELECT document_id, user_id, title, description, file_name, original_name,
                    file_path, file_type, visibility, status
             FROM documents
             WHERE document_id = ?
             LIMIT 1'
        );
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }
        $isOwner = ((int) $row['user_id']) === $userId;
        // Owner luon duoc xem (de chat voi tai lieu cua minh khi dang cho duyet)
        if ($isOwner) {
            return $row;
        }
        // Nguoi khac: phai approved + moiVisibility phai cho phep
        $isPublic = $row['visibility'] === 'public';
        if ($row['status'] !== 'approved' || !$isPublic) {
            return null;
        }
        return $row;
    }
}

if (!function_exists('ai_db_read_document_text')) {
    /**
     * Trich noi dung van ban tu mot tai lieu de lam context cho RAG.
     * Ho tro: .pdf .docx .pptx .txt .md
     * Tra ve '' neu khong doc duoc (kem ly do trong $debug neu truyen vao).
     *
     * Tuong thich Supabase Storage:
     *   - Neu file_path la Supabase public URL -> download ve file tam
     *   - Neu la duong dan local -> doc truc tiep
     */
    function ai_db_read_document_text(array $document, int $maxChars = 6000): string {
        if (empty($document['file_path'])) {
            return '';
        }
        $path = (string) $document['file_path'];

        $isSupabaseUrl = false;
        if (!class_exists('CloudStorage')) {
            $cloudPath = __DIR__ . '/../config/cloud_storage.php';
            if (is_file($cloudPath)) {
                require_once $cloudPath;
            }
        }
        if (class_exists('CloudStorage')) {
            $isSupabaseUrl = CloudStorage::isSupabaseUrl($path);
        }

        // Lay extension: uu tien tu file_name (chinh xac nhat)
        $fileName = isset($document['file_name']) ? (string) $document['file_name'] : '';
        $ext = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        }

        if ($isSupabaseUrl) {
            return ai_db_read_supabase_document_text($path, $ext, $maxChars);
        }

        if (!is_file($path) || !is_readable($path)) {
            return '';
        }

        if (!function_exists('ai_extract_document_text')) {
            return '';
        }

        $debug = null;
        $text = ai_extract_document_text($path, $ext, $maxChars, $debug);

        if ($text === '' && function_exists('ai_log')) {
            ai_log('rag_extract_failed', 'Khong trich duoc noi dung tai lieu', [
                'file_path' => $path,
                'file_type' => isset($document['file_type']) ? (string) $document['file_type'] : '',
                'ext'       => $ext,
                'reason'    => isset($debug['reason']) ? (string) $debug['reason'] : 'unknown',
                'size'      => filesize($path),
            ]);
        }

        return $text;
    }
}

/**
 * Download file tu Supabase ve file tam roi trich text.
 * Trang thai: text tra ve da duoc rut gon whitespace + gioi han maxChars.
 *
 * Luu y:
 *   - Su dung Authorization Bearer (Supabase secret key) de lam viec voi ca
 *     bucket public lan bucket private.
 *   - File tam se tu duoc don dep sau khi trich xuat xong.
 */
if (!function_exists('ai_db_read_supabase_document_text')) {
    function ai_db_read_supabase_document_text(string $url, string $ext, int $maxChars): string {
        if (!function_exists('curl_init')) {
            return '';
        }
        if ($ext === '' || !in_array($ext, ['pdf', 'docx', 'pptx', 'txt', 'md', 'markdown'], true)) {
            return '';
        }

        $tmp = @tempnam(sys_get_temp_dir(), 'ai_rag_');
        if ($tmp === false) {
            return '';
        }

        // Bat buoc phai rename sang extension chinh xac de cac parser (PharData/PDF) nhan dien
        $tmpWithExt = $tmp . '.' . strtolower($ext);
        if (!@rename($tmp, $tmpWithExt)) {
            @unlink($tmp);
            $tmpWithExt = $tmp; // fallback giu ten goc neu rename that bai
        }

        // Header Authorization: su dung Secret Key de dam bao tuong thich
        // (neu bucket public thi header bi bo qua, khong anh huong).
        $headers = [];
        if (defined('SUPABASE_SECRET_KEY') && SUPABASE_SECRET_KEY !== '') {
            $headers[] = 'Authorization: Bearer ' . SUPABASE_SECRET_KEY;
            $headers[] = 'apikey: ' . SUPABASE_SECRET_KEY;
        } elseif (defined('SUPABASE_PUBLISHABLE_KEY') && SUPABASE_PUBLISHABLE_KEY !== '') {
            $headers[] = 'Authorization: Bearer ' . SUPABASE_PUBLISHABLE_KEY;
            $headers[] = 'apikey: ' . SUPABASE_PUBLISHABLE_KEY;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            @unlink($tmpWithExt);
            return '';
        }

        $fp = @fopen($tmpWithExt, 'wb');
        if ($fp === false) {
            curl_close($ch);
            @unlink($tmpWithExt);
            return '';
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // ep flush va dong file
        fflush($fp);
        fclose($fp);

        if ($ok === false || $errno !== 0 || $httpCode >= 400) {
            @unlink($tmpWithExt);
            if (function_exists('ai_log')) {
                ai_log('supabase_download_failed', 'Khong download duoc file tu Supabase', [
                    'url'         => $url,
                    'http_status' => $httpCode,
                    'errno'       => $errno,
                    'curl_error'  => $curlError,
                ]);
            }
            return '';
        }

        clearstatcache(true, $tmpWithExt);
        $size = @filesize($tmpWithExt);
        if ($size === false || $size <= 0) {
            @unlink($tmpWithExt);
            return '';
        }

        if (!function_exists('ai_extract_document_text')) {
            @unlink($tmpWithExt);
            return '';
        }

        $debug = null;
        $text = ai_extract_document_text($tmpWithExt, $ext, $maxChars, $debug);

        @unlink($tmpWithExt);

        if ($text === '' && function_exists('ai_log')) {
            ai_log('rag_extract_failed', 'Khong trich duoc noi dung tai lieu tu Supabase', [
                'url'    => $url,
                'ext'    => $ext,
                'reason' => isset($debug['reason']) ? (string) $debug['reason'] : 'unknown',
                'size'   => $size,
            ]);
        }

        return $text;
    }
}
