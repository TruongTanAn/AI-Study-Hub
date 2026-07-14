<?php
// Test bucket info
$ch = curl_init('https://aeimwdklvjvvxssayknp.supabase.co/storage/v1/bucket/documents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer sb_publishable_LM9v3b6Wss5MYvcyy9lgfg_T7KG0jRH',
    'apikey: sb_publishable_LM9v3b6Wss5MYvcyy9lgfg_T7KG0jRH'
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP: $code\n";
echo $response . "\n";
curl_close($ch);
