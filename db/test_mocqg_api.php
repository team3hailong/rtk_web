<?php
/**
 * Test API get_nearby_mocqg.php
 */

// Test với tọa độ Hà Nội
$testLat = 21.028511;
$testLng = 105.804817;
$testRadius = 50;

$url = "http://localhost:3000/api/map/get_nearby_mocqg.php?lat={$testLat}&lng={$testLng}&radius={$testRadius}";

echo "=== TEST API TÌM MỐC ===\n\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✓ API hoạt động bình thường!\n";
        echo "Tìm thấy " . $data['data']['count'] . " mốc\n";
    } else {
        echo "✗ API trả về lỗi\n";
    }
} else {
    echo "✗ Lỗi HTTP $httpCode\n";
}
