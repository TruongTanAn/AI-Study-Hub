<?php
/**
 * AI Study Hub - AI Logger (Week 5 - AN)
 *
 * Ghi log cho cac su kien AI:
 *  - openrouter_error
 *  - invalid_api_key
 *  - timeout
 *  - rate_limit
 *  - json_decode_error
 *  - request_error
 *  - response_error
 */

if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

if (!function_exists('ai_log_path')) {
    /**
     * Lay duong dan file log. Tao thu muc neu chua ton tai.
     */
    function ai_log_path(): string {
        $dir = realpath(__DIR__ . '/../logs');
        if ($dir === false) {
            $dir = __DIR__ . '/../logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'ai.log';
    }
}

if (!function_exists('ai_log')) {
    /**
     * Ghi mot dong log vao logs/ai.log
     *
     * @param string               $event   ten su kien (vi du: openrouter_error)
     * @param string               $message mo ta chi tiet (se duoc scrub api key truoc khi ghi)
     * @param array<string, mixed> $context du lieu them
     */
    function ai_log(string $event, string $message = '', array $context = []): void {
        $path = ai_log_path();

        $scrubbedMessage = ai_log_scrub($message);
        $scrubbedContext = ai_log_scrub($context);

        $record = [
            'time'    => date('Y-m-d H:i:s'),
            'event'   => $event,
            'message' => $scrubbedMessage,
        ];

        if (!empty($scrubbedContext)) {
            $record['context'] = $scrubbedContext;
        }

        $line = '[' . $record['time'] . '] '
              . '[' . strtoupper($event) . '] '
              . $record['message']
              . (isset($record['context']) ? ' ' . json_encode($record['context'], JSON_UNESCAPED_UNICODE) : '')
              . PHP_EOL;

        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('ai_log_scrub')) {
    /**
     * An cac api key / bearer token truoc khi ghi log.
     *
     * @param mixed $value
     * @return mixed
     */
    function ai_log_scrub($value) {
        if (is_string($value)) {
            return preg_replace(
                '/(sk-or-[A-Za-z0-9_\-]+|sk-[A-Za-z0-9_\-]+|Bearer\s+[A-Za-z0-9_\-\.]+)/i',
                '[REDACTED]',
                $value
            );
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $keyLower = is_string($k) ? strtolower($k) : '';
                if ($keyLower === 'api_key' || $keyLower === 'authorization' || $keyLower === 'bearer') {
                    $out[$k] = '[REDACTED]';
                    continue;
                }
                $out[$k] = ai_log_scrub($v);
            }
            return $out;
        }

        return $value;
    }
}
