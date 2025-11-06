<?php

require_once __DIR__ . '/../../private/config/config.php';
require_once __DIR__ . '/../../private/api/rtk_system/generate_hash.php';

echo "=== DEBUG RTK ONLINE USERS API ===\n\n";

// Config
$accessKey = RTK_API_ACCESS_KEY;
$secretKey = RTK_API_SECRET_KEY;
$signMethod = RTK_API_SIGN_METHOD;

echo "Config:\n";
echo "  Access Key: " . substr($accessKey, 0, 4) . "..." . substr($accessKey, -4) . "\n";
echo "  Secret Key: " . substr($secretKey, 0, 4) . "..." . substr($secretKey, -4) . "\n";
echo "  Sign Method: $signMethod\n\n";

// Tạo nonce và timestamp
$nonce = bin2hex(random_bytes(16));
$timestamp = (string)(round(microtime(true) * 1000));

echo "Request Info:\n";
echo "  Nonce: $nonce\n";
echo "  Timestamp: $timestamp\n\n";

// Headers
$headers = [
    'X-Nonce' => $nonce,
    'X-Access-Key' => $accessKey,
    'X-Sign-Method' => $signMethod,
    'X-Timestamp' => $timestamp
];

// URI và query params
$uri = '/openapi/broadcast/online-users';
$queryParams = [
    'page' => 1,
    'size' => 10,
    'status' => ''
];

// Tạo signature
$method = 'GET';
$sign = generateRtkApiSignature($method, $uri, $headers, $secretKey);
echo "Signature: $sign\n\n";

$headers['Sign'] = $sign;
$headers['Content-Type'] = 'application/json';

// URL
$baseUrl = 'http://rtk.taikhoandodac.vn:8085';
$queryString = http_build_query($queryParams);
$url = $baseUrl . $uri . '?' . $queryString;

echo "URL: $url\n\n";

echo "Headers:\n";
foreach ($headers as $key => $value) {
    if ($key === 'Sign') {
        echo "  $key: " . substr($value, 0, 20) . "...\n";
    } else {
        echo "  $key: $value\n";
    }
}
echo "\n";

// cURL request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$curlHeaders = [];
foreach ($headers as $key => $value) {
    $curlHeaders[] = "$key: $value";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

echo "Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);

curl_close($ch);

echo "\nResponse:\n";
echo "  HTTP Code: $httpCode\n";
echo "  Error: " . ($error ?: 'None') . "\n";
echo "  Content Type: " . ($info['content_type'] ?? 'N/A') . "\n";
echo "  Size: " . strlen($response) . " bytes\n\n";

echo "Raw Response (first 1000 chars):\n";
echo str_repeat("-", 80) . "\n";
echo substr($response, 0, 1000) . "\n";
echo str_repeat("-", 80) . "\n\n";

// Try to parse JSON
$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "[✓] JSON Valid!\n\n";
    echo "Parsed Data:\n";
    print_r($data);
} else {
    echo "[✗] JSON Parse Error: " . json_last_error_msg() . "\n";
    echo "\nFull Response:\n";
    echo $response . "\n";
}
