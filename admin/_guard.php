<?php
/**
 * Admin guard - check login + role=admin
 * Moi file admin/*.php phai require file nay o dau tien.
 *
 * LUAT: KHONG sua cac file cu (Week 1-5).
 * File nay doc lap, chi phu trach khu vuc /admin/.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

$currentAdminId = (int) $_SESSION['user_id'];
$currentAdminName = (string) ($_SESSION['full_name'] ?? 'Admin');

/**
 * Lay thong ke don gian cho dashboard.
 * @return array<string,int>
 */
function admin_get_stats(mysqli $conn): array {
    $stats = [
        'users'         => 0,
        'documents'     => 0,
        'categories'    => 0,
        'subjects'      => 0,
        'conversations' => 0,
    ];
    $res = $conn->query('SELECT COUNT(*) AS c FROM users');
    if ($res) $stats['users'] = (int) $res->fetch_assoc()['c'];
    $res = $conn->query('SELECT COUNT(*) AS c FROM documents');
    if ($res) $stats['documents'] = (int) $res->fetch_assoc()['c'];
    $res = $conn->query('SHOW TABLES LIKE "categories"');
    if ($res && $res->num_rows > 0) {
        $r2 = $conn->query('SELECT COUNT(*) AS c FROM categories');
        if ($r2) $stats['categories'] = (int) $r2->fetch_assoc()['c'];
    }
    $res = $conn->query('SHOW TABLES LIKE "subjects"');
    if ($res && $res->num_rows > 0) {
        $r2 = $conn->query('SELECT COUNT(*) AS c FROM subjects');
        if ($r2) $stats['subjects'] = (int) $r2->fetch_assoc()['c'];
    }
    $res = $conn->query('SHOW TABLES LIKE "conversations"');
    if ($res && $res->num_rows > 0) {
        $r2 = $conn->query('SELECT COUNT(*) AS c FROM conversations');
        if ($r2) $stats['conversations'] = (int) $r2->fetch_assoc()['c'];
    }
    return $stats;
}
