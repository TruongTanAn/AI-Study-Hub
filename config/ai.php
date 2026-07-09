<?php
/**
 * AI Study Hub - AI Configuration (Week 5 - AN)
 *
 * Day du thong tin ket noi OpenRouter chi ton tai trong file nay.
 * Moi file khac KHONG duoc phep hardcode API key hoac goi truc tiep OpenRouter.
 */

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

/**
 * Lay cau hinh AI dang key => value.
 *
 * @return array<string, string|int|float>
 */
function ai_config(): array {
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = [
        // ===== OpenRouter =====
        'api_key'         => getenv('OPENROUTER_API_KEY') ?: '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',
        'base_url'        => 'https://openrouter.ai/api/v1/chat/completions',
        'referer'         => 'http://localhost',
        'app_title'       => 'AI Study Hub',

        // ===== Model mac dinh (co the doi de dang tai day) =====
        'model'           => 'meta-llama/llama-3.1-8b-instruct',

        // ===== Tham so sinh =====
        'temperature'     => 0.7,
        'max_tokens'      => 1024,
        'top_p'           => 0.95,

        // ===== Network =====
        'timeout'         => 45, // giay
        'max_retries'     => 1,

        // ===== Gioi han prompt =====
        'max_prompt_chars'      => 8000,
        'max_question_chars'    => 2000,
        'max_context_chars'     => 6000,

        // ===== System prompt mac dinh =====
        'system_prompt'   => "Ban la tro ly AI cua AI Study Hub - mot nen tang hoctap thong minh cho sinh vien viet nam.\n"
                           . "Hay tra loi bang tieng Viet, ro rang, dung trong tam, ngan gon va de hieu.\n"
                           . "Neu khong biet dap an, hay noi minh khong biet, khong tu bia ra thong tin sai.\n"
                           . "Luon giu gioi han noi dung trong khuon khoao dao duc va an toan.",
    ];

    return $config;
}

/**
 * Lay mot gia tri rieng trong cau hinh AI.
 */
function ai_config_get(string $key, $default = null) {
    $cfg = ai_config();
    return $cfg[$key] ?? $default;
}
