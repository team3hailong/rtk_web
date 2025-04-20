<?php
/**
 * Tạo tài khoản RTK mới qua API.
 * 
 * @param array $accountData Dữ liệu tài khoản cần tạo
 * @return array Response dạng ['success' => bool, 'data' => array|null, 'error' => string|null]
 */
function createRtkAccount(array $accountData): array {
    try {
        // Validate required fields
        $requiredFields = ['name', 'userPwd', 'startTime', 'endTime'];
        foreach ($requiredFields as $field) {
            if (empty($accountData[$field])) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => "Missing required field: $field"
                ];
            }
        }

        // Set default values for optional fields
        $accountData = array_merge([
            'enabled' => 1,
            'numOnline' => 1,
            'customerBizType' => 1,
            'customerCompany' => [],
            'casterIds' => [],
            'regionIds' => [],
            'mountIds' => []
        ], $accountData);

        // API endpoint
        $url = 'http://203.171.25.138:8090/openapi/broadcast/users';

        // Generate headers
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string)(round(microtime(true) * 1000));
        $accessKey = 'Zb5F6iKUuAISy4qY';
        $secretKey = 'KL1KEEJj2s6HA8LB';
        $signMethod = 'HmacSHA256';

        $headers = [
            'X-Nonce' => $nonce,
            'X-Access-Key' => $accessKey,
            'X-Sign-Method' => $signMethod,
            'X-Timestamp' => $timestamp
        ];

        // Calculate signature
        $method = 'POST';
        $uri = '/openapi/broadcast/users';
        $signStr = "$method $uri ";
        ksort($headers);
        foreach ($headers as $key => $value) {
            $signStr .= strtolower($key) . "=" . $value . "&";
        }
        $signStr = rtrim($signStr, "&");
        
        $sign = hash_hmac('sha256', $signStr, $secretKey);
        $headers['Sign'] = $sign;
        $headers['Content-Type'] = 'application/json';

        // Prepare cURL request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($accountData));

        // Set headers
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'data' => null,
                'error' => "cURL Error: $curlError"
            ];
        }

        $responseData = json_decode($response, true);

        // Check response
        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['code']) && 
            ($responseData['code'] === 'SUCCESS' || $responseData['code'] === 'OK')) {
            return [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData,
                'error' => null
            ];
        }

        return [
            'success' => false,
            'data' => $responseData,
            'error' => $responseData['msg'] ?? "HTTP Error: $httpCode"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'data' => null,
            'error' => "Exception: " . $e->getMessage()
        ];
    }
}
?>