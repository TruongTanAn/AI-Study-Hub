<?php
/**
 * AI Study Hub - Database Configuration
 * Ket noi MySQL voi encoding UTF-8mb4 day du.
 */

// UTF-8 headers (cho tat ca output)
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Ket noi database
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'ai_study_hub';

$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ket noi database that bai'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// FIX: Su dung SET NAMES utf8mb4 thay cho mysqli_set_charset
// Day la cach dang tin cay de dam bao tat ca truy van deu dung UTF-8
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Ngon ngu string cua PHP
mb_internal_encoding('UTF-8');
