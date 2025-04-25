<?php
/**
 * Tạo chữ ký hash HMAC SHA256 cho RTK API
 * @param string $method
 * @param string $uri
 * @param array $headers
 * @param string $secretKey
 * @return string
 */
function generateRtkApiSignature(string $method, string $uri, array $headers, string $secretKey): string {
    $signStr = "$method $uri ";
    ksort($headers);
    foreach ($headers as $key => $value) {
        $signStr .= strtolower($key) . "=" . $value . "&";
    }
    $signStr = rtrim($signStr, "&");
    return hash_hmac('sha256', $signStr, $secretKey);
}
