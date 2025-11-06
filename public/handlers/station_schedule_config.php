<?php
require_once __DIR__ . '/../../private/config/config.php';
require_once __DIR__ . '/../../private/classes/Database.php';

// Initialize session
init_session();

// Set JSON response header
header('Content-Type: application/json');

// Check if user has station access
if (!isset($_SESSION['station_access']) || $_SESSION['station_access'] !== true) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit();
}

$action = $input['action'] ?? '';
$stationId = $input['station_id'] ?? 0;
$type = $input['type'] ?? '';
$enabled = isset($input['enabled']) ? (bool)$input['enabled'] : false;

// Validate input
if ($action !== 'toggle') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Action không hợp lệ'
    ]);
    exit();
}

if (!$stationId || !in_array($type, ['start', 'stop'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tham số không hợp lệ'
    ]);
    exit();
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if station exists
    $stationCheck = $pdo->prepare("SELECT id FROM station WHERE id = ?");
    $stationCheck->execute([$stationId]);
    if (!$stationCheck->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Trạm không tồn tại'
        ]);
        exit();
    }
    
    // Check if schedule config exists
    $checkQuery = "SELECT id, auto_start, auto_stop FROM station_auto_schedule WHERE station_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$stationId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing record
        $column = $type === 'start' ? 'auto_start' : 'auto_stop';
        $updateQuery = "UPDATE station_auto_schedule SET $column = ?, updated_at = NOW() WHERE station_id = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$enabled ? 1 : 0, $stationId]);
    } else {
        // Insert new record
        $autoStart = $type === 'start' ? ($enabled ? 1 : 0) : 0;
        $autoStop = $type === 'stop' ? ($enabled ? 1 : 0) : 0;
        
        $insertQuery = "INSERT INTO station_auto_schedule (station_id, auto_start, auto_stop, enabled) VALUES (?, ?, ?, 1)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$stationId, $autoStart, $autoStop]);
    }
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thành công'
    ]);
    
} catch (Exception $e) {
    error_log("[STATION_SCHEDULE_CONFIG] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
