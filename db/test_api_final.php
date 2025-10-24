<?php
// Test và hiển thị kết quả đẹp
$lat = 21.028511;
$lng = 105.804817;
$radius = 50;

$url = "http://localhost:3000/public/api/map/get_nearby_mocqg.php?lat=$lat&lng=$lng&radius=$radius";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== TEST API TÌM MỐC ===\n\n";
echo "URL: $url\n";
echo "HTTP Code: $httpCode\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✓ API hoạt động thành công!\n\n";
    echo "Kết quả:\n";
    echo "- Vị trí tìm kiếm: {$data['data']['center']['lat']}, {$data['data']['center']['lng']}\n";
    echo "- Bán kính: {$data['data']['radius_km']} km\n";
    echo "- Số mốc tìm thấy: {$data['data']['count']}\n\n";
    
    if ($data['data']['count'] > 0) {
        echo "Danh sách mốc:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($data['data']['mocqg_list'] as $index => $mocqg) {
            echo sprintf(
                "%d. %s\n   Tọa độ: %.6f, %.6f\n   Khoảng cách: %.2f km\n\n",
                $index + 1,
                $mocqg['ten_moc'],
                $mocqg['lat'],
                $mocqg['lng'],
                $mocqg['distance_km']
            );
        }
        echo str_repeat("-", 80) . "\n";
    }
    
    echo "\nJSON Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "✗ Lỗi HTTP $httpCode\n";
    echo "Response: $response\n";
}
