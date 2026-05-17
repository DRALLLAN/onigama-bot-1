<?php

$key = getenv('GOLDAPI_KEY') ?: 'goldapi-7f60882f0aff7eb9d7fbe2bc2abee42b-io';

$ch = curl_init('https://www.goldapi.io/api/XAU/USD');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'x-access-token: ' . $key,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $httpCode,
    'curl_error' => $error,
    'response' => json_decode($response, true) ?? $response,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
