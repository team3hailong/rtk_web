<?php
/**
 * API: Tìm các mốc quốc gia trong bán kính 50km
 * File: get_nearby_mocqg.php
 */

require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Mocqg.php';

header('Content-Type: application/json');

try {
    // Lấy tọa độ từ request
    $lat = isset($_GET['lat']) ? $_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? $_GET['lng'] : null;
    $radius = isset($_GET['radius']) ? $_GET['radius'] : 50; // Mặc định 50km

    if ($lat === null || $lng === null) {
        throw new Exception('Thiếu thông tin tọa độ (lat, lng)');
    }

    $db = new Database();
    $pdo = $db->getConnection();

    // Sử dụng class Mocqg để tìm các mốc gần đó
    $mocqg_list = Mocqg::findNearbyMocqg($pdo, $lat, $lng, $radius);

    echo json_encode([
        'success' => true,
        'data' => [
            'center' => [
                'lat' => floatval($lat),
                'lng' => floatval($lng)
            ],
            'radius_km' => floatval($radius),
            'count' => count($mocqg_list),
            'mocqg_list' => $mocqg_list
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
