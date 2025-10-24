<?php
/**
 * Debug API get_nearby_mocqg.php
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG API TÌM MỐC ===\n\n";

// Test with coordinates
$testLat = 21.028511;
$testLng = 105.804817;
$testRadius = 50;

echo "Test parameters:\n";
echo "- Latitude: $testLat\n";
echo "- Longitude: $testLng\n";
echo "- Radius: $testRadius km\n\n";

// Build URL
$baseUrl = "http://localhost:3000";
$url = "$baseUrl/api/map/get_nearby_mocqg.php?lat={$testLat}&lng={$testLng}&radius={$testRadius}";

echo "Full URL: $url\n\n";

// Make request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "=== RESPONSE ===\n";
echo "HTTP Code: $httpCode\n\n";
echo "Headers:\n$header\n";
echo "Body:\n$body\n\n";

// Try to decode JSON
if ($httpCode == 200) {
    $data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✓ JSON decoded successfully\n";
        print_r($data);
    } else {
        echo "✗ JSON decode error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "✗ HTTP Error $httpCode\n";
    
    // Try to parse error response
    $errorData = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($errorData['error'])) {
        echo "\nError message from API: " . $errorData['error'] . "\n";
    }
}

// Direct file test
echo "\n=== DIRECT FILE TEST ===\n";
$apiFilePath = __DIR__ . '/../public/api/map/get_nearby_mocqg.php';
echo "File path: $apiFilePath\n";
echo "File exists: " . (file_exists($apiFilePath) ? "YES" : "NO") . "\n";

if (file_exists($apiFilePath)) {
    echo "File size: " . filesize($apiFilePath) . " bytes\n";
    echo "Readable: " . (is_readable($apiFilePath) ? "YES" : "NO") . "\n";
}
