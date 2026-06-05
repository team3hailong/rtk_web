<?php
/**
 * Auto Schedule Script for Station Management
 * 
 * This script automatically starts stations at 6:00 AM and stops them at 8:00 PM (20:00)
 * 
 * To run this script automatically, you can set up a cron job:
 * Linux/Mac: Add to crontab
 *   0 6 * * * /usr/bin/php /path/to/station_auto_schedule.php start
 *   0 20 * * * /usr/bin/php /path/to/station_auto_schedule.php stop
 * 
 * Windows: Use Task Scheduler
 *   - Create a task that runs at 6:00 AM daily
 *     Action: php.exe "C:\path\to\station_auto_schedule.php" start
 *   - Create a task that runs at 8:00 PM daily
 *     Action: php.exe "C:\path\to\station_auto_schedule.php" stop
 */

require_once __DIR__ . '/../../private/config/config.php';
require_once __DIR__ . '/../../private/classes/Map.php';
require_once __DIR__ . '/../../private/api/rtk_system/generate_hash.php';

// Get action from command line argument
$action = $argv[1] ?? '';

if (!in_array($action, ['start', 'stop'])) {
    echo "Usage: php station_auto_schedule.php [start|stop]\n";
    echo "Example:\n";
    echo "  php station_auto_schedule.php start  - Start all stations\n";
    echo "  php station_auto_schedule.php stop   - Stop all stations\n";
    exit(1);
}

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
    echo $logMessage;
}

writeLog("========================================");
writeLog("Auto Schedule Script Started - Action: " . strtoupper($action));

try {
    // Get all stations
    $stations = Map::getAllStations($pdo);
    
    if (empty($stations)) {
        writeLog("No stations found in database");
        exit(0);
    }
    
    $stationIds = array_column($stations, 'id');
    writeLog("Found " . count($stationIds) . " stations");
    
    // Determine API endpoint
    $uri = ($action === 'start') ? '/stream/stations/batch-start' : '/stream/stations/batch-stop';
    
    writeLog("Calling API endpoint: $uri");
    
    // Call RTK API
    $result = callRtkStreamApi($uri, $stationIds);
    
    if ($result['success']) {
        writeLog("SUCCESS: " . $result['message']);
        writeLog("Stations affected: " . count($stationIds));
    } else {
        writeLog("FAILED: " . $result['message']);
    }
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

writeLog("Auto Schedule Script Completed");
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
    // RTK_API_URL = http://rtk.taikhoandodac.vn:8090/openapi/broadcast/users
    // We need: http://rtk.taikhoandodac.vn:8090/openapi
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
    // URI for signature should be: /openapi/stream/stations/batch-start
    $signUri = '/openapi' . $uri;
    
    // Generate signature
    $sign = generateRtkApiSignature($method, $signUri, $headers, $secretKey);
    
    $headers['Sign'] = $sign;
    $headers['Content-Type'] = 'application/json';
    
    // Prepare request payload
    // API expects: {ids: ["57", "58", ...]} - array of string IDs
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
    
    // For development/testing, you may need to disable SSL verification
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    writeLog("Request URL: $fullUrl");
    writeLog("Request Headers: " . json_encode($headers));
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
            'message' => "Lỗi kết nối: $curlError"
        ];
    }
    
    // Parse response
    $responseData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'message' => 'Thao tác thành công',
            'data' => $responseData
        ];
    } else {
        $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Lỗi không xác định';
        return [
            'success' => false,
            'message' => "API trả về lỗi (HTTP $httpCode): $errorMessage",
            'data' => $responseData
        ];
    }
}
