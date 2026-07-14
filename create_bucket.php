<?php
// Try creating bucket using API (may need service_role key which we don't have)
$ch = curl_init('https://aeimwdklvjvvxssayknp.supabase.co/storage/v1/bucket');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'documents',
    'public' => true,
    'file_size_limit' => 50 * 1024 * 1024,
    'allowed_mime_types' => ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer sb_publishable_LM9v3b6Wss5MYvcyy9lgfg_T7KG0jRH',
    'apikey: sb_publishable_LM9v3b6Wss5MYvcyy9lgfg_T7KG0jRH',
    'Content-Type: application/json',
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP: $code\n";
echo $response . "\n";
curl_close($ch);
