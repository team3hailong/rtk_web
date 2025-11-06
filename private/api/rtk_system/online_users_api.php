<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/generate_hash.php';

/**
 * Lấy danh sách người dùng online từ hệ thống RTK
 * 
 * LƯU Ý: API này có thể yêu cầu authentication hoặc chỉ accessible từ internal network
 * Hiện tại đang sử dụng HMAC authentication giống như createRtkAccount()
 * 
 * @param int $page Trang hiện tại (mặc định: 1)
 * @param int $size Số lượng bản ghi mỗi trang (mặc định: 100000)
 * @param string $status Trạng thái (mặc định: '')
 * @param bool $useAuth Sử dụng authentication hay không (mặc định: true)
 * @return array
 */
function getRtkOnlineUsers(int $page = 1, int $size = 100000, string $status = '', bool $useAuth = true): array {
    try {
        // Xây dựng URI và query params
        $uri = '/openapi/broadcast/online-users';
        $queryParams = [
            'page' => $page,
            'size' => $size,
            'status' => $status
        ];
        
        // Lấy base URL từ config (giống account_api.php)
        // RTK_API_URL = 'http://rtk.taikhoandodac.vn:8090/openapi/broadcast/users'
        // Ta cần lấy base URL (remove path)
        $configUrl = RTK_API_URL;
        $baseUrl = str_replace('/openapi/broadcast/users', '', $configUrl);
        
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
            
            error_log("[RTK_API] Getting online users with AUTH - Page: $page, Size: $size");
        } else {
            error_log("[RTK_API] Getting online users WITHOUT AUTH - Page: $page, Size: $size");
        }
        
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

        // Kiểm tra HTTP status code
        if ($httpCode !== 200) {
            error_log("[RTK_API] HTTP Error: $httpCode");
            return [
                'success' => false,
                'error' => 'HTTP Error',
                'http_code' => $httpCode,
                'response' => substr($response, 0, 500)
            ];
        }

        // Kiểm tra content type - nếu là HTML thì có vấn đề
        if ($contentType && strpos($contentType, 'text/html') !== false) {
            error_log("[RTK_API] Warning: Received HTML instead of JSON");
            return [
                'success' => false,
                'error' => 'API returned HTML instead of JSON. This endpoint may require different authentication or may not be accessible.',
                'http_code' => $httpCode,
                'content_type' => $contentType,
                'response' => substr($response, 0, 500)
            ];
        }

        // Parse JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[RTK_API] JSON Parse Error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'JSON Parse Error: ' . json_last_error_msg(),
                'raw_response' => substr($response, 0, 500)
            ];
        }

        error_log("[RTK_API] Success: Got " . (count($data['data']['records'] ?? [])) . " records");
        
        return [
            'success' => true,
            'data' => $data
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Lấy thông tin chi tiết từ kết quả API
 * @param array $apiResult Kết quả từ hàm getRtkOnlineUsers()
 * @return array
 */
function parseOnlineUsersData(array $apiResult): array {
    if (!$apiResult['success']) {
        return [
            'success' => false,
            'error' => $apiResult['error'] ?? 'Unknown error'
        ];
    }

    $data = $apiResult['data'];
    
    return [
        'success' => true,
        'code' => $data['code'] ?? null,
        'message' => $data['msg'] ?? null,
        'total' => $data['data']['total'] ?? 0,
        'page' => $data['data']['page'] ?? 1,
        'size' => $data['data']['size'] ?? 0,
        'records' => $data['data']['records'] ?? []
    ];
}

/**
 * Lọc người dùng online theo username
 * @param array $records Danh sách records
 * @param string $userName Username cần tìm
 * @return array|null
 */
function findUserByUsername(array $records, string $userName): ?array {
    foreach ($records as $record) {
        if (isset($record['userName']) && $record['userName'] === $userName) {
            return $record;
        }
    }
    return null;
}

/**
 * Lọc người dùng online theo trạng thái
 * @param array $records Danh sách records
 * @param int $status Trạng thái cần lọc
 * @return array
 */
function filterUsersByStatus(array $records, int $status): array {
    return array_filter($records, function($record) use ($status) {
        return isset($record['status']) && $record['status'] === $status;
    });
}

/**
 * Lấy thống kê người dùng online
 * @param array $records Danh sách records
 * @return array
 */
function getOnlineUsersStatistics(array $records): array {
    $stats = [
        'total_users' => count($records),
        'by_status' => [],
        'by_mount' => [],
        'total_send_bytes' => 0,
        'avg_send_bytes' => 0
    ];

    foreach ($records as $record) {
        // Thống kê theo status
        $status = $record['status'] ?? 'unknown';
        if (!isset($stats['by_status'][$status])) {
            $stats['by_status'][$status] = 0;
        }
        $stats['by_status'][$status]++;

        // Thống kê theo mount
        $mount = $record['mountName'] ?? 'unknown';
        if (!isset($stats['by_mount'][$mount])) {
            $stats['by_mount'][$mount] = 0;
        }
        $stats['by_mount'][$mount]++;

        // Tổng bytes
        $stats['total_send_bytes'] += $record['sendBytes'] ?? 0;
    }

    // Tính trung bình
    if ($stats['total_users'] > 0) {
        $stats['avg_send_bytes'] = $stats['total_send_bytes'] / $stats['total_users'];
    }

    return $stats;
}
