<?php
require_once __DIR__ . '/../../private/config/config.php';
require_once __DIR__ . '/../../private/api/rtk_system/generate_hash.php';

// Initialize session
init_session();

// Set JSON response header
header('Content-Type: application/json');

// Check if user has station access
if (!isset($_SESSION['station_access']) || $_SESSION['station_access'] !== true) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập. Vui lòng nhập mật khẩu tại trang quản lý trạm.'
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
$station_ids = $input['station_ids'] ?? [];

// Validate input
if (empty($action)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tham số action'
    ]);
    exit();
}

if (empty($station_ids) || !is_array($station_ids)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Danh sách station_ids không hợp lệ'
    ]);
    exit();
}

// Map action to API endpoint
// Full URL will be: http://rtk.taikhoandodac.vn:8090/openapi + /stream/stations/batch-start
$endpoint_map = [
    'start' => '/stream/stations/batch-start',
    'stop' => '/stream/stations/batch-stop',
    'batch_start' => '/stream/stations/batch-start',
    'batch_stop' => '/stream/stations/batch-stop'
];

if (!isset($endpoint_map[$action])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Action không hợp lệ'
    ]);
    exit();
}

$uri = $endpoint_map[$action];

// Call RTK API
try {
    $result = callRtkStreamApi($uri, $station_ids);
    echo json_encode($result);
} catch (Exception $e) {
    error_log("[STATION_CONTROL] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi gọi API: ' . $e->getMessage()
    ]);
}

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
    
    // Prepare request payload
    // API expects: {ids: ["57", "58", ...]} - array of string IDs  
    $payload = [
        'ids' => array_map('strval', $station_ids)
    ];
    $bodyJson = json_encode($payload);
    
    // Signature must match the full URI path including /openapi
    // URI for signature should be: /openapi/stream/stations/batch-start
    $signUri = '/openapi' . $uri;
    
    // Generate signature
    $sign = generateRtkApiSignature($method, $signUri, $headers, $secretKey);
    
    $headers['Sign'] = $sign;
    $headers['Content-Type'] = 'application/json';
    
    // Log signature string for debugging
    $signStr = "$method $signUri ";
    $tempHeaders = $headers;
    unset($tempHeaders['Sign']);
    unset($tempHeaders['Content-Type']);
    ksort($tempHeaders);
    foreach ($tempHeaders as $key => $value) {
        $signStr .= strtolower($key) . "=" . $value . "&";
    }
    $signStr = rtrim($signStr, "&");
    error_log("[STATION_CONTROL] Signature string: $signStr");
    
    // Initialize cURL
    $fullUrl = rtrim($baseUrl, '/') . $uri;
    $ch = curl_init($fullUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    
    // Set headers
    $curlHeaders = [];
    foreach ($headers as $key => $value) {
        $curlHeaders[] = "$key: $value";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    
    // For development/testing, you may need to disable SSL verification
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Log request
    error_log("[STATION_CONTROL] Calling API: $fullUrl");
    error_log("[STATION_CONTROL] Payload: " . json_encode($payload));
    error_log("[STATION_CONTROL] Headers: " . json_encode($headers));
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);
    
    // Log response
    error_log("[STATION_CONTROL] Response code: $httpCode");
    error_log("[STATION_CONTROL] Response: " . ($response ?: 'Empty'));
    
    if ($curlError) {
        error_log("[STATION_CONTROL] cURL Error: $curlError");
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
