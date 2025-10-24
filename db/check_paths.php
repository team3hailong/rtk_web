<?php
/**
 * Check document root and paths
 */

echo "=== KIỂM TRA CẤU HÌNH SERVER ===\n\n";

echo "1. PHP Info:\n";
echo "   - Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "   - Script Filename: " . __FILE__ . "\n";
echo "   - Current Dir: " . __DIR__ . "\n";
echo "   - Public Dir: " . realpath(__DIR__ . '/../public') . "\n\n";

echo "2. API File:\n";
$apiFile = realpath(__DIR__ . '/../public/api/map/get_nearby_mocqg.php');
echo "   - Full Path: $apiFile\n";
echo "   - Exists: " . (file_exists($apiFile) ? 'YES' : 'NO') . "\n\n";

echo "3. Test URLs:\n";
echo "   URL 1: /api/map/get_nearby_mocqg.php\n";
echo "   URL 2: /public/api/map/get_nearby_mocqg.php\n\n";

echo "4. Suggested fix:\n";
echo "   - Nếu document root là: " . realpath(__DIR__ . '/..') . "\n";
echo "   - Thì URL phải là: /public/api/map/get_nearby_mocqg.php\n\n";

// Test with correct path
$testUrls = [
    'http://localhost:3000/api/map/get_nearby_mocqg.php',
    'http://localhost:3000/public/api/map/get_nearby_mocqg.php',
];

foreach ($testUrls as $url) {
    echo "Testing: $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?lat=21&lng=105&radius=50');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "   Result: HTTP $httpCode " . ($httpCode == 200 ? '✓' : '✗') . "\n";
}
