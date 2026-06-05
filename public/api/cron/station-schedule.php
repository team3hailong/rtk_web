<?php
/**
 * Cron Endpoint for Station Auto Start/Stop
 * 
 * This endpoint is designed to be called by external cron services like cron-job.org
 * 
 * Usage:
 * - Start all stations: https://your-domain.com/api/cron/station-schedule.php?action=start&key=YOUR_SECRET_KEY
 * - Stop all stations: https://your-domain.com/api/cron/station-schedule.php?action=stop&key=YOUR_SECRET_KEY
 */

require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Map.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/api/rtk_system/generate_hash.php';

// Secret key for authentication (change this!)
define('CRON_SECRET_KEY', 'taikhoandodac_cron_2025');

// Set JSON response header
header('Content-Type: application/json');

// Log file path
$logFile = __DIR__ . '/../../private/logs/station_schedule.log';

// Ensure log directory exists
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

// Function to write log
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Check authentication
$providedKey = $_GET['key'] ?? '';
if ($providedKey !== CRON_SECRET_KEY) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid authentication key'
    ]);
    writeLog("FAILED: Invalid authentication key provided");
    exit();
}

// Get action parameter
$action = $_GET['action'] ?? '';

if (!in_array($action, ['start', 'stop'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action. Use: start or stop'
    ]);
    writeLog("FAILED: Invalid action '$action'");
    exit();
}

writeLog("========================================");
writeLog("Cron Job Started - Action: " . strtoupper($action));

try {
    // Initialize session
    init_session();
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get stations with auto schedule enabled
    $column = $action === 'start' ? 'auto_start' : 'auto_stop';
    $query = "SELECT s.* FROM station s 
              INNER JOIN station_auto_schedule sas ON s.id = sas.station_id 
              WHERE sas.$column = 1 AND sas.enabled = 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stations)) {
        writeLog("No stations configured for auto $action");
        echo json_encode([
            'success' => true,
            'message' => "No stations configured for auto $action",
            'stations_count' => 0
        ]);
        $db->close();
        exit();
    }
    
    $stationIds = array_column($stations, 'id');
    writeLog("Found " . count($stationIds) . " stations configured for auto $action");
    
    // Determine API endpoint
    $uri = ($action === 'start') ? '/stream/stations/batch-start' : '/stream/stations/batch-stop';
    
    writeLog("Calling API endpoint: $uri");
    
    // Call RTK API
    $result = callRtkStreamApi($uri, $stationIds);
    
    if ($result['success']) {
        writeLog("SUCCESS: " . $result['message']);
        writeLog("Stations affected: " . count($stationIds));
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => ucfirst($action) . ' completed successfully',
            'stations_count' => count($stationIds),
            'data' => $result['data']
        ]);
    } else {
        writeLog("FAILED: " . $result['message']);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'stations_count' => count($stationIds)
        ]);
    }
    
    // Close database connection
    if (isset($db)) $db->close();
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

writeLog("Cron Job Completed");
writeLog("========================================\n");

/**
 * Call RTK Stream API
 * @param string $uri
 * @param array $station_ids
 * @return array
 */
function callRtkStreamApi(string $uri, array $station_ids): array {
    // Get API configuration
    $apiUrl = RTK_API_URL;
    $accessKey = RTK_API_ACCESS_KEY;
    $secretKey = RTK_API_SECRET_KEY;
    $signMethod = RTK_API_SIGN_METHOD;
    
    // Extract base URL (remove /openapi/broadcast/users if exists)
    $baseUrl = preg_replace('#/openapi/broadcast/users$#', '/openapi', $apiUrl);
    
    // Generate headers
    $nonce = bin2hex(random_bytes(16));
    $timestamp = (string)(round(microtime(true) * 1000));
    
    $headers = [
        'X-Nonce' => $nonce,
        'X-Access-Key' => $accessKey,
        'X-Sign-Method' => $signMethod,
        'X-Timestamp' => $timestamp
    ];
    
    $method = 'POST';
    
    // Signature must match the full URI path including /openapi
    $signUri = '/openapi' . $uri;
    
    // Generate signature
    $sign = generateRtkApiSignature($method, $signUri, $headers, $secretKey);
    
    $headers['Sign'] = $sign;
    $headers['Content-Type'] = 'application/json';
    
    // Prepare request payload
    $payload = [
        'ids' => array_map('strval', $station_ids)
    ];
    
    // Initialize cURL
    $fullUrl = rtrim($baseUrl, '/') . $uri;
    $ch = curl_init($fullUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    // Set headers
    $curlHeaders = [];
    foreach ($headers as $key => $value) {
        $curlHeaders[] = "$key: $value";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    
    writeLog("Request URL: $fullUrl");
    writeLog("Request Payload: " . json_encode($payload));
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);
    
    writeLog("Response HTTP Code: $httpCode");
    if ($response) {
        writeLog("Response Body: " . $response);
    }
    
    if ($curlError) {
        writeLog("cURL Error: $curlError");
        return [
            'success' => false,
            'message' => "Connection error: $curlError"
        ];
    }
    
    // Parse response
    $responseData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'message' => 'Operation completed successfully',
            'data' => $responseData
        ];
    } else {
        $errorMessage = $responseData['message'] ?? $responseData['msg'] ?? $responseData['error'] ?? 'Unknown error';
        return [
            'success' => false,
            'message' => "API returned error (HTTP $httpCode): $errorMessage",
            'data' => $responseData
        ];
    }
}
