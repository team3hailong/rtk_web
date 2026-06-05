<?php

// Test cơ bản nhất - chỉ gọi API mà không load config phức tạp

echo "Testing RTK Online Users API...\n\n";

// URL API
$url = 'http://rtk.taikhoandodac.vn:8085/openapi/broadcast/online-users?page=1&size=100&status=';

echo "URL: $url\n";
echo "Method: GET\n";
echo str_repeat("-", 80) . "\n\n";

// Khởi tạo cURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ],
]);

echo "Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "CURL Error: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "HTTP Error!\n";
    echo "Response: $response\n";
    exit(1);
}

echo "Success! Parsing JSON...\n\n";

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Parse Error: " . json_last_error_msg() . "\n";
    echo "Raw response (first 500 chars):\n";
    echo substr($response, 0, 500) . "\n";
    exit(1);
}

// Hiển thị kết quả
echo "API Response:\n";
echo str_repeat("=", 80) . "\n";
echo "Code: " . ($data['code'] ?? 'N/A') . "\n";
echo "Message: " . ($data['msg'] ?? 'NULL') . "\n";
echo "Trace ID: " . ($data['traceId'] ?? 'NULL') . "\n";
echo "\nData:\n";
echo "  Total: " . ($data['data']['total'] ?? 'N/A') . "\n";
echo "  Page: " . ($data['data']['page'] ?? 'N/A') . "\n";
echo "  Size: " . ($data['data']['size'] ?? 'N/A') . "\n";
echo "  Records count: " . count($data['data']['records'] ?? []) . "\n";

// Hiển thị 3 record đầu
if (!empty($data['data']['records'])) {
    echo "\nFirst 3 records:\n";
    echo str_repeat("-", 80) . "\n";
    
    $records = array_slice($data['data']['records'], 0, 3);
    foreach ($records as $i => $record) {
        echo "\nRecord #" . ($i + 1) . ":\n";
        echo "  ID: " . ($record['id'] ?? 'N/A') . "\n";
        echo "  Username: " . ($record['userName'] ?? 'N/A') . "\n";
        echo "  Mount: " . ($record['mountName'] ?? 'N/A') . "\n";
        echo "  Caster: " . ($record['casterName'] ?? 'N/A') . "\n";
        echo "  Status: " . ($record['status'] ?? 'N/A') . "\n";
        echo "  Sat Count: " . ($record['satCount'] ?? 'N/A') . "\n";
        echo "  Send Bytes: " . ($record['sendBytes'] ?? 'N/A') . "\n";
        echo "  User IP: " . ($record['userIp'] ?? 'N/A') . "\n";
        echo "  User Agent: " . ($record['userAgent'] ?? 'N/A') . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Test completed successfully!\n";
