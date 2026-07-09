<?php
/**
 * UTF-8 Helper for AI Study Hub
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set UTF-8 for all mb_* functions
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

// Vietnamese locale
setlocale(LC_TIME, 'vi_VN.UTF-8', 'vietnamese');

// Flush output buffers (but don't lose headers)
while (ob_get_level() > 0) {
    ob_end_flush();
}

// No cache headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

/**
 * Escape HTML - UTF-8 safe
 */
if (!function_exists('e')) {
    function e(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Escape for JSON attribute - safe in JS
 */
if (!function_exists('json_e')) {
    function json_e(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Force any string to UTF-8 clean
 * Use when data comes from uncertain source
 */
if (!function_exists('to_utf8')) {
    function to_utf8(?string $str): string {
        if ($str === null || $str === '') {
            return '';
        }
        $detected = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP850'], true);
        if ($detected && $detected !== 'UTF-8') {
            return mb_convert_encoding($str, 'UTF-8', $detected);
        }
        return $str;
    }
}

/**
 * Document status labels
 */
if (!function_exists('get_status_text')) {
    function get_status_text(?string $status): string {
        static $map = [
            'pending'  => "\x44\x61\x6E\x67\x20\x63\x68\xE1\xBB\x9D\x20\x64\x75\x79\xE1\xBB\x87\x74",
            'approved'  => "\x44\x61\xCC\x80\x20\x64\x75\x79\xE1\xBB\x87\x74",
            'rejected'  => "\x54\x75\xCC\x80\x20\x63\x68\x6F\xE1\xBB\x9i",
        ];
        return $map[$status] ?? "\x4B\x68\x6F\x6E\x67\x20\x78\x61\x63\x20\x64\x69\x6E\x68";
    }
}

/**
 * Format file size
 */
if (!function_exists('format_filesize')) {
    function format_filesize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

/**
 * Format date Vietnamese
 */
if (!function_exists('format_date_vn')) {
    function format_date_vn(string $dateStr): string {
        $date = date_create($dateStr);
        if ($date === false) {
            return $dateStr;
        }
        return date_format($date, 'd/m/Y');
    }
}
