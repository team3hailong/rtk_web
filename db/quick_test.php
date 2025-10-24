<?php
// Test API directly
$lat = 21.028511;
$lng = 105.804817;
$radius = 50;

$url = "http://localhost:3000/public/api/map/get_nearby_mocqg.php?lat=$lat&lng=$lng&radius=$radius";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

curl_close($ch);

$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "Response Body:\n";
echo $body;
