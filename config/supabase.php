<?php
/**
 * AI Study Hub - Supabase Storage Configuration
 *
 * Cau hinh Supabase Storage (Object Storage) de thay the luu tru cuc bo (uploads/).
 * Moi thao tac upload/download/delete file PDF/DOCX/PPTX deu di qua file nay.
 *
 * - SUPABASE_URL         : Project URL cua Supabase.
 * - SUPABASE_PUBLISHABLE_KEY : Publishable key (anon), dung de goi REST API public.
 * - BUCKET_NAME          : Ten bucket (mac dinh: documents).
 *
 * Bucket can duoc PUBLIC trong Supabase dashboard de browser co the truy cap
 * truc tiep den file qua Public URL. Neu bucket la PRIVATE, can chuyen
 * sang dung signed URL (se duoc bo sung neu can).
 */

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', 'https://aeimwdklvjvvxssayknp.supabase.co');
}

if (!defined('SUPABASE_SECRET_KEY')) {
    define('SUPABASE_SECRET_KEY', 'sb_secret_2Kyl0esbMVFJ7LeiDL27Fw_K9TMYJ2N');
}

if (!defined('SUPABASE_BUCKET_NAME')) {
    define('SUPABASE_BUCKET_NAME', 'documents');
}

if (!defined('SUPABASE_UPLOAD_PREFIX')) {
    /**
     * Thu muc ao trong bucket. Moi file se duoc luu voi prefix nay.
     * Khong co dau '/' o dau hay cuoi.
     */
    define('SUPABASE_UPLOAD_PREFIX', 'user-uploads');
}

/**
 * Tra ve URL goc cua Supabase Storage cho mot bucket.
 */
function supabase_storage_base_url(string $bucket = SUPABASE_BUCKET_NAME): string {
    return rtrim(SUPABASE_URL, '/') . '/storage/v1';
}

/**
 * Lay header Authorization can thiet cho moi request toi Supabase.
 */
function supabase_auth_headers(): array {
    return [
        'Authorization: Bearer ' . SUPABASE_SECRET_KEY,
        'apikey: ' . SUPABASE_SECRET_KEY,
    ];
}

/**
 * Log mot su kien Supabase (upload/delete/error) vao logs/ai.log neu ham ai_log co san.
 */
function supabase_log(string $event, string $message = '', array $context = []): void {
    if (function_exists('ai_log')) {
        ai_log('supabase_' . $event, $message, $context);
        return;
    }

    $logDir = realpath(__DIR__ . '/../logs');
    if ($logDir === false) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
    }
    $logFile = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . 'supabase.log';
    $line = '[' . date('Y-m-d H:i:s') . '] [SUPABASE_' . strtoupper($event) . '] ' . $message
        . (empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE))
        . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
