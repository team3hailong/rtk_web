<?php
// filepath: e:\Application\laragon\www\test_web-Long2\private\utils\vietqr_helper.php

// Tải env_loader nếu chưa được tải
if (!function_exists('env')) {
    require_once dirname(dirname(__FILE__)) . '/config/env_loader.php';
}

// --- VietQR Configuration ---
// Sử dụng biến môi trường, với giá trị mặc định là các giá trị hiện tại
define('VIETQR_BANK_ID', env('VIETQR_BANK_ID', '970418'));
define('VIETQR_ACCOUNT_NO', env('VIETQR_ACCOUNT_NO', '112233445566'));
define('VIETQR_ACCOUNT_NAME', env('VIETQR_ACCOUNT_NAME', 'NGUYEN VAN A'));
define('VIETQR_BANK_NAME', env('VIETQR_BANK_NAME', 'VietinBank'));
define('VIETQR_IMAGE_TEMPLATE', env('VIETQR_IMAGE_TEMPLATE', 'compact2'));

// Template VietQR chuẩn
define('VIETQR_TEMPLATE', '00020101021238570010A00000072701270006%s0115%s0208QRIBFTTA530370454%.0f5802VN62%d%s6304');

/**
 * Calculates the CRC16 checksum for VietQR data.
 *
 * @param string $data The data string to calculate CRC for.
 * @return string The uppercase hexadecimal CRC16 value, padded to 4 characters.
 */
function calculate_vietqr_crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : $crc << 1;
        }
    }
    return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
}

/**
 * Generates the complete VietQR payload string.
 *
 * @param float $amount The payment amount (will be formatted as integer).
 * @param string $description The payment description (will be sanitized).
 * @return string The final VietQR payload string including CRC.
 */
function generate_vietqr_payload($amount, $description) {
    // Sanitize and format description for QR payload
    $qr_description_raw = preg_replace('/[^A-Z0-9]/', '', strtoupper(str_replace(' ', '', $description)));
    // Limit description length if necessary (VietQR has payload limits)
    $qr_description = substr($qr_description_raw, 0, 50); // Example limit
    // Format description parameter (field 08)
    $qr_description_param = '08' . str_pad(strlen($qr_description), 2, '0', STR_PAD_LEFT) . $qr_description;

    // Format account name parameter (field 62, subfield 00)
    $account_name_param = '00' . str_pad(strlen(VIETQR_ACCOUNT_NAME), 2, '0', STR_PAD_LEFT) . VIETQR_ACCOUNT_NAME;

    // Create the base QR payload using the template
    $qr_payload_base = sprintf(
        VIETQR_TEMPLATE,
        VIETQR_BANK_ID,                     // %s: Bank BIN
        VIETQR_ACCOUNT_NO,                  // %s: Account Number
        $amount,                            // %.0f: Amount (ensure integer format)
        strlen($account_name_param),        // %d: Length of Account Name Parameter (including 00xx)
        str_replace(' ','%20', $account_name_param), // %s: Account Name Param URL Encoded
        $qr_description_param               // %s: Description parameter (08xx...)
    );

    // Calculate CRC and append it
    $crc_value = calculate_vietqr_crc16($qr_payload_base);
    return $qr_payload_base . $crc_value;
}

?>