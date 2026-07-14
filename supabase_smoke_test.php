<?php
require_once __DIR__ . '/config/cloud_storage.php';

$testFile = sys_get_temp_dir() . '/test_smoke.pdf';
$content = '%PDF-1.4 test smoke upload ' . date('Y-m-d H:i:s');
file_put_contents($testFile, $content);

echo "=== Supabase Upload Smoke Test ===\n\n";
echo "Test file: $testFile\n";
echo "File size: " . filesize($testFile) . " bytes\n\n";

$r = CloudStorage::upload($testFile, 'smoke_test_' . time() . '.pdf', 999, 'documents');
echo "Upload result:\n";
var_export($r);
echo "\n\n";

if ($r['success']) {
    echo "Public URL: " . $r['url'] . "\n\n";
    // Test delete
    $d = CloudStorage::delete($r['url']);
    echo "Delete result: " . var_export($d, true) . "\n";
}

unlink($testFile);
echo "\nDone.\n";
