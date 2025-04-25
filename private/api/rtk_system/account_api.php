<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/generate_hash.php';
/**
 * Tạo tài khoản RTK qua API
 * @param array $accountData
 * @return array
 */
function createRtkAccount(array $accountData): array {
    try {
        $accountData = array_merge([
            'enabled' => 1,
            'numOnline' => 1,
            'customerBizType' => 1,
            'customerCompany' => '',
            'casterIds' => [],
            'regionIds' => [],
            'mountIds' => []
        ], $accountData);
        $url = RTK_API_URL;
        $accessKey = RTK_API_ACCESS_KEY;
        $secretKey = RTK_API_SECRET_KEY;
        $signMethod = RTK_API_SIGN_METHOD;
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string)(round(microtime(true) * 1000));
        $headers = [
            'X-Nonce' => $nonce,
            'X-Access-Key' => $accessKey,
            'X-Sign-Method' => $signMethod,
            'X-Timestamp' => $timestamp
        ];
        $method = 'POST';
        $uri = '/openapi/broadcast/users';
        $sign = generateRtkApiSignature($method, $uri, $headers, $secretKey);
        $headers['Sign'] = $sign;
        $headers['Content-Type'] = 'application/json';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($accountData));
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        if ($curlError) {
            return [ 'success' => false, 'data' => null, 'error' => "cURL Error: $curlError" ];
        }
        $responseData = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['code']) && 
            ($responseData['code'] === 'SUCCESS' || $responseData['code'] === 'OK')) {
            return [ 'success' => true, 'data' => $responseData['data'] ?? $responseData, 'error' => null ];
        }
        return [ 'success' => false, 'data' => $responseData, 'error' => $responseData['msg'] ?? "HTTP Error: $httpCode" ];
    } catch (Exception $e) {
        return [ 'success' => false, 'data' => null, 'error' => "Exception: " . $e->getMessage() ];
    }
}