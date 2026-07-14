<?php
/**
 * AI Study Hub - Migrate local files to Supabase Storage
 *
 * Script nay giup chuyen toan bo file PDF/DOCX/PPTX dang luu o uploads/documents/
 * len Supabase Storage va cap nhat lai file_path trong DB thanh Public URL.
 *
 * Cach chay:
 *   - Trong trinh duyet: truy cap /backend/migrate_files_to_supabase.php (can dang nhap admin)
 *   - Hoac CLI: php migrate_files_to_supabase.php
 *
 * Luong:
 *   1. SELECT document_id, file_path, file_name, user_id, file_type WHERE file_path
 *      KHONG phai Supabase URL.
 *   2. Voi moi dong:
 *      - Doc noi dung file tu local
 *      - Upload len Supabase Storage qua CloudStorage::uploadBytes()
 *      - UPDATE documents SET file_path = public_url WHERE document_id = ?
 *      - Neu upload that bai -> ghi log va bo qua (giu file_path cu)
 */

if (php_sapi_name() === 'cli') {
    // CLI: chay thang, khong can session admin
    $requireAdmin = false;
} else {
    $requireAdmin = true;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cloud_storage.php';
require_once __DIR__ . '/../config/ai_logger.php';

if ($requireAdmin) {
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden: Can dang nhap admin moi chay duoc migration.\n";
        exit;
    }
}

header('Content-Type: text/plain; charset=utf-8');

echo "Starting migration: local files -> Supabase Storage\n";
echo str_repeat('=', 60) . "\n";

// Lay danh sach document co file_path local
$result = $conn->query("
    SELECT document_id, user_id, file_name, original_name, file_path, file_type, file_size
    FROM documents
    WHERE file_path IS NOT NULL AND file_path <> ''
    ORDER BY document_id ASC
");

if (!$result) {
    echo "ERROR: query failed: " . $conn->error . "\n";
    exit(1);
}

$total = 0;
$success = 0;
$skipped = 0;
$failed = 0;
$bytes = 0;

while ($row = $result->fetch_assoc()) {
    $total++;
    $docId = (int) $row['document_id'];
    $filePath = (string) $row['file_path'];

    // Da la Supabase URL -> skip
    if (CloudStorage::isSupabaseUrl($filePath)) {
        $skipped++;
        continue;
    }

    // Neu gia tri DB khong con tro den file local hop le -> thu cac vi tri khac
    $absoluteLocal = null;
    if (is_file($filePath)) {
        $absoluteLocal = $filePath;
    } else {
        $candidates = [
            __DIR__ . '/../uploads/documents/' . $row['file_name'],
            realpath(__DIR__ . '/../uploads/documents/' . $row['file_name']) ?: null,
        ];
        foreach ($candidates as $c) {
            if ($c !== null && is_file($c)) {
                $absoluteLocal = $c;
                break;
            }
        }
    }

    if ($absoluteLocal === null) {
        echo "[$docId] SKIP (khong tim thay file local): file_name=" . $row['file_name'] . " db_path=" . $filePath . "\n";
        $skipped++;
        continue;
    }

    // Lay user_id lam prefix (de giu toc do quy uoc cu)
    $userId = (int) ($row['user_id'] ?? 0) ?: null;

    echo "[$docId] Uploading: $absoluteLocal -> Supabase ... ";

    $contents = @file_get_contents($absoluteLocal);
    if ($contents === false || $contents === '') {
        echo "FAIL (khong doc duoc file local)\n";
        $failed++;
        continue;
    }

    $ext = strtolower((string) pathinfo($row['file_name'], PATHINFO_EXTENSION));
    if ($ext === '') {
        $ext = strtolower((string) pathinfo($absoluteLocal, PATHINFO_EXTENSION));
    }

    // Upload bytes len Supabase Storage
    $objectPath = 'user-uploads/documents/user_' . ($userId ?? 'unknown') . '/' . $row['file_name'];
    $r = CloudStorage::uploadBytes($contents, $objectPath);

    if (!$r['success']) {
        echo "FAIL (" . ($r['error'] ?? 'unknown') . ")\n";
        if (function_exists('ai_log')) {
            ai_log('migration_failed', 'Migration that bai', [
                'document_id' => $docId,
                'file_name'   => $row['file_name'],
                'error'       => $r['error'] ?? 'unknown',
            ]);
        }
        $failed++;
        continue;
    }

    $newUrl = $r['url'];

    // UPDATE row trong DB
    $updateStmt = $conn->prepare("UPDATE documents SET file_path = ? WHERE document_id = ?");
    if ($updateStmt === false) {
        echo "FAIL (prepare update): " . $conn->error . "\n";
        // Xoa file da upload de khong bi rac
        @CloudStorage::delete($objectPath);
        $failed++;
        continue;
    }
    $updateStmt->bind_param("si", $newUrl, $docId);
    if (!$updateStmt->execute()) {
        echo "FAIL (update db): " . $updateStmt->error . "\n";
        $updateStmt->close();
        @CloudStorage::delete($objectPath);
        $failed++;
        continue;
    }
    $updateStmt->close();

    $success++;
    $bytes += filesize($absoluteLocal);
    echo "OK -> $newUrl\n";

    // Don dep file local neu khong con duoc ai dung
    @unlink($absoluteLocal);
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Total docs: $total\n";
echo "Migrated  : $success (" . round($bytes / 1024 / 1024, 2) . " MB)\n";
echo "Skipped   : $skipped (da la Supabase URL hoac thieu file)\n";
echo "Failed    : $failed\n";
echo "\nDone.\n";
