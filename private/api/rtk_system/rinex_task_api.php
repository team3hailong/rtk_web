<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/generate_hash.php';

/**
 * Tạo task cho hệ thống CGBAS/RTK
 * 
 * API này tạo một task mới cho việc lưu trữ và xử lý dữ liệu từ các trạm RTK
 * 
 * @param array $taskData Dữ liệu task cần tạo
 * @param bool $useAuth Sử dụng authentication hay không (mặc định: true)
 * @return array
 */
function createStorageTask(array $taskData, bool $useAuth = true): array {
    try {
        // Xây dựng URI
        $uri = '/openapi/stream/storage/tasks';
        
        // Lấy base URL từ config
        $configUrl = RTK_API_URL;
        $baseUrl = str_replace('/openapi/broadcast/users', '', $configUrl);
        
        // Xây dựng URL đầy đủ
        $url = $baseUrl . $uri;
        
        $curlHeaders = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        // Nếu sử dụng authentication
        if ($useAuth) {
            $accessKey = RTK_API_ACCESS_KEY;
            $secretKey = RTK_API_SECRET_KEY;
            $signMethod = RTK_API_SIGN_METHOD;
            
            // Tạo nonce và timestamp
            $nonce = bin2hex(random_bytes(16));
            $timestamp = (string)(round(microtime(true) * 1000));
            
            // Xây dựng headers cho authentication
            $authHeaders = [
                'X-Nonce' => $nonce,
                'X-Access-Key' => $accessKey,
                'X-Sign-Method' => $signMethod,
                'X-Timestamp' => $timestamp
            ];
            
            // Tạo signature
            $method = 'POST';
            $sign = generateRtkApiSignature($method, $uri, $authHeaders, $secretKey);
            $authHeaders['Sign'] = $sign;
            
            // Thêm auth headers vào cURL headers
            foreach ($authHeaders as $key => $value) {
                $curlHeaders[] = "$key: $value";
            }
            
            error_log("[RTK_API] Creating storage task with AUTH");
        } else {
            error_log("[RTK_API] Creating storage task WITHOUT AUTH");
        }
        
        // Log request data
        error_log("[RTK_API] Task data: " . json_encode($taskData));
        
        // Khởi tạo cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        // Thực hiện request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        
        curl_close($ch);

        // Kiểm tra lỗi cURL
        if ($error) {
            error_log("[RTK_API] CURL Error: $error");
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        // Log response
        error_log("[RTK_API] Response HTTP Code: $httpCode");
        error_log("[RTK_API] Response Content-Type: $contentType");
        error_log("[RTK_API] Response: " . substr($response, 0, 500));

        // Parse response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[RTK_API] JSON Parse Error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }

        // Kiểm tra HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData,
                'http_code' => $httpCode
            ];
        } else {
            error_log("[RTK_API] API Error: HTTP $httpCode - " . json_encode($responseData));
            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Unknown error',
                'error_details' => $responseData,
                'http_code' => $httpCode
            ];
        }

    } catch (Exception $e) {
        error_log("[RTK_API] Exception: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'exception' => get_class($e)
        ];
    }
}

/**
 * Tạo task với dữ liệu mẫu
 * Hàm helper để tạo task nhanh với các tham số cơ bản
 * 
 * @param string $taskName Tên task
 * @param array $stationIds Danh sách ID trạm (string array)
 * @param array $additionalParams Tham số bổ sung (optional)
 * @return array
 */
function createStorageTaskQuick(string $taskName, array $stationIds, array $additionalParams = []): array {
    // Dữ liệu mặc định
    $defaultData = [
        'fileFormat' => 'Rinex v304',
        'dailyQuantity' => 1,
        'satelliteSystem' => [1, 2, 3, 4, 6], // GPS, BDS, GLO, GAL, QZSS
        'obsType' => [1, 2, 3, 4],
        'sampleRate' => 30,
        'compressMode' => 2,
        'fileDeletePeriod' => '3',
        'highAngle' => 15,
        'rateEpoch' => 85,
        'mp1' => 0.5,
        'snr1' => 36,
        'mp2' => 0.5,
        'snr2' => 36,
        'mp5' => 0.5,
        'snr5' => 36,
        'mp6' => 0.5,
        'snr6' => 36,
        'mp7' => 0.5,
        'snr7' => 36,
        'mp8' => 0.5,
        'snr8' => 36,
        'uploadStrategy' => 0,
        'uploadAddresses' => ['2'],
        'uploadEnabled' => 1,
        'qcEnabled' => 1,
        'storeEph' => 1,
        'isDefaultLocalDir' => 0,
        'localDir' => '/YYYY/MM/DD,/STATION',
        'isDefaultUploadDir' => 0,
        'uploadDir' => '/STATION,/YYYY/MM/DD',
        'thresholdValue' => [
            ['satellite' => 'GPS', 'rateUse' => 95, 'rateCs' => 400],
            ['satellite' => 'BDS', 'rateUse' => 95, 'rateCs' => 400],
            ['satellite' => 'GLO', 'rateUse' => 95, 'rateCs' => 400],
            ['satellite' => 'GAL', 'rateUse' => 95, 'rateCs' => 400],
            ['satellite' => 'QZSS', 'rateUse' => 95, 'rateCs' => 400]
        ]
    ];
    
    // Merge với tham số bổ sung
    $taskData = array_merge($defaultData, $additionalParams, [
        'taskName' => $taskName,
        'stationIds' => $stationIds
    ]);
    
    return createStorageTask($taskData);
}

// Ví dụ sử dụng (uncomment để test):
/*
// Test tạo task với dữ liệu đầy đủ
$taskData = [
    'taskName' => 'sc2',
    'stationIds' => ['56', '58'],
    'fileFormat' => 'Rinex v304',
    'dailyQuantity' => 1,
    'satelliteSystem' => [1, 2, 3, 4, 6],
    'obsType' => [1, 2, 3, 4],
    'sampleRate' => 30,
    'compressMode' => 2,
    'fileDeletePeriod' => '3',
    'highAngle' => 15,
    'rateEpoch' => 85,
    'mp1' => 0.5,
    'snr1' => 36,
    'mp2' => 0.5,
    'snr2' => 36,
    'mp5' => 0.5,
    'snr5' => 36,
    'mp6' => 0.5,
    'snr6' => 36,
    'mp7' => 0.5,
    'snr7' => 36,
    'mp8' => 0.5,
    'snr8' => 36,
    'uploadStrategy' => 0,
    'uploadAddresses' => ['2'],
    'uploadEnabled' => 1,
    'qcEnabled' => 1,
    'storeEph' => 1,
    'isDefaultLocalDir' => 0,
    'localDir' => '/YYYY/MM/DD,/STATION',
    'isDefaultUploadDir' => 0,
    'uploadDir' => '/STATION,/YYYY/MM/DD',
    'thresholdValue' => [
        ['satellite' => 'GPS', 'rateUse' => 95, 'rateCs' => 400],
        ['satellite' => 'BDS', 'rateUse' => 95, 'rateCs' => 400],
        ['satellite' => 'GLO', 'rateUse' => 95, 'rateCs' => 400],
        ['satellite' => 'GAL', 'rateUse' => 95, 'rateCs' => 400],
        ['satellite' => 'QZSS', 'rateUse' => 95, 'rateCs' => 400]
    ]
];

$result = createStorageTask($taskData);
echo json_encode($result, JSON_PRETTY_PRINT);

// Hoặc sử dụng hàm quick:
$result = createStorageTaskQuick('my_task', ['56', '58']);
echo json_encode($result, JSON_PRETTY_PRINT);
*/

/**
 * Lấy danh sách tasks từ hệ thống CGBAS/RTK
 * 
 * API này lấy danh sách các task đã được tạo trong hệ thống
 * 
 * @param int $page Số trang (mặc định: 1)
 * @param int $size Số lượng bản ghi mỗi trang (mặc định: 20)
 * @param bool $useAuth Sử dụng authentication hay không (mặc định: true)
 * @return array
 */
function getStorageTasks(int $page = 1, int $size = 20, bool $useAuth = true): array {
    try {
        // Xây dựng URI và query params
        $uri = '/openapi/stream/storage/tasks';
        $queryParams = [
            'page' => $page,
            'size' => $size
        ];
        
        // Lấy base URL từ config
        // RTK_API_URL = 'http://rtk.taikhoandodac.vn:8090/openapi/broadcast/users'
        // Cần thay đổi port từ 8090 sang 8085 và bỏ /openapi
        $configUrl = RTK_API_URL;
        $baseUrl = str_replace('/openapi/broadcast/users', '', $configUrl);
        // Thay đổi port từ 8090 sang 8085 cho API stream
        // $baseUrl = str_replace(':8090', ':8085', $baseUrl);
        
        // Xây dựng URL đầy đủ
        $queryString = http_build_query($queryParams);
        $url = $baseUrl . $uri . '?' . $queryString;
        
        $curlHeaders = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        // Nếu sử dụng authentication
        if ($useAuth) {
            $accessKey = RTK_API_ACCESS_KEY;
            $secretKey = RTK_API_SECRET_KEY;
            $signMethod = RTK_API_SIGN_METHOD;
            
            // Tạo nonce và timestamp
            $nonce = bin2hex(random_bytes(16));
            $timestamp = (string)(round(microtime(true) * 1000));
            
            // Xây dựng headers cho authentication
            $authHeaders = [
                'X-Nonce' => $nonce,
                'X-Access-Key' => $accessKey,
                'X-Sign-Method' => $signMethod,
                'X-Timestamp' => $timestamp
            ];
            
            // Tạo signature
            $method = 'GET';
            $sign = generateRtkApiSignature($method, $uri, $authHeaders, $secretKey);
            $authHeaders['Sign'] = $sign;
            
            // Thêm auth headers vào cURL headers
            foreach ($authHeaders as $key => $value) {
                $curlHeaders[] = "$key: $value";
            }
            
            error_log("[RTK_API] Getting storage tasks with AUTH - Page: $page, Size: $size");
        } else {
            error_log("[RTK_API] Getting storage tasks WITHOUT AUTH - Page: $page, Size: $size");
        }
        
        // Log URL
        error_log("[RTK_API] Request URL: $url");
        
        // Khởi tạo cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        // Thực hiện request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        
        curl_close($ch);

        // Kiểm tra lỗi cURL
        if ($error) {
            error_log("[RTK_API] CURL Error: $error");
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        // Log response
        error_log("[RTK_API] Response HTTP Code: $httpCode");
        error_log("[RTK_API] Response Content-Type: $contentType");
        error_log("[RTK_API] Response: " . substr($response, 0, 1000));

        // Parse response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[RTK_API] JSON Parse Error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }

        // Kiểm tra HTTP status code và response code
        if ($httpCode >= 200 && $httpCode < 300) {
            // Kiểm tra code từ API response
            if (isset($responseData['code']) && $responseData['code'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? [],
                    'total' => $responseData['data']['total'] ?? 0,
                    'page' => $responseData['data']['page'] ?? $page,
                    'size' => $responseData['data']['size'] ?? $size,
                    'records' => $responseData['data']['records'] ?? [],
                    'http_code' => $httpCode
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['msg'] ?? 'Unknown error',
                    'error_code' => $responseData['code'] ?? 'UNKNOWN',
                    'http_code' => $httpCode
                ];
            }
        } else {
            error_log("[RTK_API] API Error: HTTP $httpCode - " . json_encode($responseData));
            return [
                'success' => false,
                'error' => $responseData['msg'] ?? 'Unknown error',
                'error_details' => $responseData,
                'http_code' => $httpCode
            ];
        }

    } catch (Exception $e) {
        error_log("[RTK_API] Exception: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'exception' => get_class($e)
        ];
    }
}


/**
 * Dừng các task lưu trữ (batch stop)
 * 
 * @param array $ids Danh sách ID task cần dừng (string array)
 * @param bool $useAuth Sử dụng authentication hay không (mặc định: true)
 * @return array
 */
function stopStorageTasks(array $ids, bool $useAuth = true): array {
    return _controlStorageTasks($ids, '/openapi/stream/storage/tasks/batch-stop', $useAuth);
}

/**
 * Bắt đầu các task lưu trữ (batch start)
 * 
 * @param array $ids Danh sách ID task cần bắt đầu (string array)
 * @param bool $useAuth Sử dụng authentication hay không (mặc định: true)
 * @return array
 */
function startStorageTasks(array $ids, bool $useAuth = true): array {
    return _controlStorageTasks($ids, '/openapi/stream/storage/tasks/batch-start', $useAuth);
}

/**
 * Internal Helper function để thực hiện start/stop tasks
 * 
 * @param array $ids Danh sách ID
 * @param string $uri URI endpoint
 * @param bool $useAuth Auth flag
 * @return array
 */
function _controlStorageTasks(array $ids, string $uri, bool $useAuth = true): array {
    try {
        // Lấy base URL từ config
        $configUrl = RTK_API_URL;
        $baseUrl = str_replace('/openapi/broadcast/users', '', $configUrl);
        
        // Xây dựng URL đầy đủ
        $url = $baseUrl . $uri;
        
        // Payload
        $payload = ['ids' => $ids];
        
        $curlHeaders = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        // Nếu sử dụng authentication
        if ($useAuth) {
            $accessKey = RTK_API_ACCESS_KEY;
            $secretKey = RTK_API_SECRET_KEY;
            $signMethod = RTK_API_SIGN_METHOD;
            
            // Tạo nonce và timestamp
            $nonce = bin2hex(random_bytes(16));
            $timestamp = (string)(round(microtime(true) * 1000));
            
            // Xây dựng headers cho authentication
            $authHeaders = [
                'X-Nonce' => $nonce,
                'X-Access-Key' => $accessKey,
                'X-Sign-Method' => $signMethod,
                'X-Timestamp' => $timestamp
            ];
            
            // Tạo signature
            $method = 'POST';
            $sign = generateRtkApiSignature($method, $uri, $authHeaders, $secretKey);
            $authHeaders['Sign'] = $sign;
            
            // Thêm auth headers vào cURL headers
            foreach ($authHeaders as $key => $value) {
                $curlHeaders[] = "$key: $value";
            }
            
            error_log("[RTK_API] Control tasks ($uri) with AUTH - IDs: " . implode(',', $ids));
        } else {
            error_log("[RTK_API] Control tasks ($uri) WITHOUT AUTH - IDs: " . implode(',', $ids));
        }
        
        // Log URL and payload
        error_log("[RTK_API] Request URL: $url");
        error_log("[RTK_API] Payload: " . json_encode($payload));
        
        // Khởi tạo cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        // Thực hiện request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        
        curl_close($ch);

        // Kiểm tra lỗi cURL
        if ($error) {
            error_log("[RTK_API] CURL Error: $error");
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error,
                'http_code' => $httpCode
            ];
        }

        // Log response
        error_log("[RTK_API] Response HTTP Code: $httpCode");
        error_log("[RTK_API] Response: " . substr($response, 0, 500));

        // Parse response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[RTK_API] JSON Parse Error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }

        // Kiểm tra HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            // Kiểm tra code từ API response (dựa trên các phản hồi trước đó)
             if (isset($responseData['code']) && $responseData['code'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? null,
                    'http_code' => $httpCode
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['msg'] ?? 'Unknown error',
                    'error_code' => $responseData['code'] ?? 'UNKNOWN',
                    'http_code' => $httpCode
                ];
            }
        } else {
            error_log("[RTK_API] API Error: HTTP $httpCode - " . json_encode($responseData));
            return [
                'success' => false,
                'error' => $responseData['msg'] ?? 'Unknown error',
                'error_details' => $responseData,
                'http_code' => $httpCode
            ];
        }

    } catch (Exception $e) {
        error_log("[RTK_API] Exception: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'exception' => get_class($e)
        ];
    }
}

// Ví dụ sử dụng (uncomment để test):
/*
// Lấy danh sách tasks - trang 1, 20 bản ghi
$result = getStorageTasks(1, 20);
echo json_encode($result, JSON_PRETTY_PRINT);

// Hoặc với tham số mặc định
$result = getStorageTasks();
echo json_encode($result, JSON_PRETTY_PRINT);
*/
