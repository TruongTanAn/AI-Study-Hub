<?php

/**
 * AI Study Hub - Database Configuration
 *
 * Doc config tu bien moi truong (Docker):
 *   DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME
 *
 * Fallback cho local (XAMPP) neu khong co bien moi truong.
 */

// ============================================================
// DEBUG: Hien thi loi de truy tim nguyen nhan HTTP 500
// ============================================================
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Doc bien moi truong (Docker set qua docker-compose.yml)
$dbHost = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';
$dbUser = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('MYSQL_ROOT_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'ai_study_hub';

// Debug log (se hien thi neu loi xay ra)
if (function_exists('error_log')) {
    error_log('[database.php] Connecting to: ' . $dbHost . ':' . $dbPort . ' / db=' . $dbName . ' / user=' . $dbUser);
}

// Build connection (include port if non-standard)
if ((int)$dbPort > 0 && (int)$dbPort !== 3306) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
} else {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
}

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $errMsg = mysqli_connect_error();
    $errNo  = mysqli_connect_errno();
    echo json_encode([
        'success'  => false,
        'error'    => 'Ket noi database that bai',
        'debug'    => [
            'host'   => $dbHost,
            'port'   => $dbPort,
            'user'   => $dbUser,
            'dbname' => $dbName,
            'errno'  => $errNo,
            'errstr' => $errMsg,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Log success
if (function_exists('error_log')) {
    error_log('[database.php] Connected OK to ' . $dbHost);
}