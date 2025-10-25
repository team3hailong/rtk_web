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

/**
 * Cập nhật mật khẩu tài khoản RTK qua API
 * @param string $rtkUserId - RTK User ID (survey_account.id)
 * @param string $username - Username của tài khoản
 * @param string $newPassword - Mật khẩu mới
 * @param array $accountData - Dữ liệu đầy đủ của account
 * @return array
 */
function updateRtkAccountPassword(string $rtkUserId, string $username, string $newPassword, array $accountData): array {
    try {
        // Use PUT /openapi/broadcast/users/{id} with HMAC authentication
        $baseUrl = str_replace('/openapi/broadcast/users', '', RTK_API_URL);
        $url = $baseUrl . '/openapi/broadcast/users/' . $rtkUserId;
        
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

        // Use PUT method with ID in URL
        $method = 'PUT';
        $uri = '/openapi/broadcast/users/' . $rtkUserId;

        $sign = generateRtkApiSignature($method, $uri, $headers, $secretKey);
        $headers['Sign'] = $sign;
        $headers['Content-Type'] = 'application/json';

        // Prepare full update data (no id in payload, it's in URL)
        $updateData = array_merge([
            'enabled' => 1,
            'numOnline' => 1,
            'customerBizType' => 1,
            'customerCompany' => null,
            'customerName' => null,
            'customerPhone' => null,
            'casterIds' => [],
            'regionIds' => [],
            'mountIds' => []
        ], $accountData, [
            'name' => $username,
            'userPwd' => $newPassword
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));

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
            error_log("RTK API Error (Update Password): $curlError");
            return [ 'success' => false, 'data' => null, 'error' => "cURL Error: $curlError" ];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['code']) && 
            ($responseData['code'] === 'SUCCESS' || $responseData['code'] === 'OK')) {
            error_log("RTK API Success (Update Password): Username=$username, RTK_ID=$rtkUserId");
            return [ 'success' => true, 'data' => $responseData['data'] ?? $responseData, 'error' => null ];
        }

        error_log("RTK API Failed (Update Password): HTTP $httpCode - " . json_encode($responseData));
        return [ 'success' => false, 'data' => $responseData, 'error' => $responseData['msg'] ?? "HTTP Error: $httpCode" ];

    } catch (Exception $e) {
        error_log("RTK API Exception (Update Password): " . $e->getMessage());
        return [ 'success' => false, 'data' => null, 'error' => "Exception: " . $e->getMessage() ];
    }
}
