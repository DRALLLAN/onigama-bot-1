<?php
$key = getenv('OPENROUTER_KEY');
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'model'      => 'meta-llama/llama-3.3-8b-instruct:free',
        'max_tokens' => 50,
        'messages'   => [['role' => 'user', 'content' => 'say hello']],
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $key,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);
header('Content-Type: application/json');
echo json_encode(['http_code' => $httpCode, 'curl_error' => $error, 'key_prefix' => substr($key, 0, 10) . '...', 'response' => json_decode($response, true) ?? $response], JSON_PRETTY_PRINT);
