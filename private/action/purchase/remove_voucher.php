<?php
/**
 * Remove Voucher from Order/Renewal
 * 
 * This action removes a previously applied voucher from the current order
 */

// Include necessary files
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// Initialize response array
$response = [
    'status' => false, 
    'message' => 'Unknown error', 
    'data' => null
];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$orderContext = isset($_POST['context']) ? $_POST['context'] : 'purchase'; // purchase or renewal

// Use the appropriate session key based on context
$sessionKey = ($orderContext === 'renewal') ? 'renewal' : 'order';

// Check if our session has the order data
if (!isset($_SESSION[$sessionKey])) {
    // Try to create session data from pending info if available
    if (isset($_SESSION['pending_registration_id']) && isset($_SESSION['pending_total_price'])) {
        $is_renewal_context = ($orderContext === 'renewal' || (isset($_SESSION['is_renewal']) && $_SESSION['is_renewal']));
        
        if ($is_renewal_context) {
            $_SESSION['renewal'] = [
                'registration_ids' => isset($_SESSION['renewal_account_ids']) ? 
                    $_SESSION['renewal_account_ids'] : [$_SESSION['pending_registration_id']],
                'amount' => $_SESSION['pending_total_price']
            ];
        } else {
            $_SESSION['order'] = [
                'registration_id' => $_SESSION['pending_registration_id'],
                'total_price' => $_SESSION['pending_total_price']
            ];
        }
    } else {
        $response['message'] = 'Không tìm thấy thông tin đơn hàng';
        echo json_encode($response);
        exit;
    }
}

// Check if there's a voucher to remove
if (!isset($_SESSION[$sessionKey]['voucher_id'])) {
    $response['message'] = 'Không có mã giảm giá nào được áp dụng';
    echo json_encode($response);
    exit;
}

// Save original values to return
$originalValues = [
    'voucher_id' => $_SESSION[$sessionKey]['voucher_id'],
    'voucher_code' => $_SESSION[$sessionKey]['voucher_code'] ?? '',
    'original_amount' => $_SESSION[$sessionKey]['original_amount'] ?? 0,
];

// Remove voucher from session
unset($_SESSION[$sessionKey]['voucher_id']);
unset($_SESSION[$sessionKey]['voucher_code']);
unset($_SESSION[$sessionKey]['voucher_discount']);

// Restore original amount
if (isset($_SESSION[$sessionKey]['original_amount'])) {
    if ($sessionKey === 'order') {
        $_SESSION[$sessionKey]['total_price'] = $_SESSION[$sessionKey]['original_amount'];
    } elseif ($sessionKey === 'renewal') {
        $_SESSION[$sessionKey]['amount'] = $_SESSION[$sessionKey]['original_amount'];
    }
    unset($_SESSION[$sessionKey]['original_amount']);
}

// Remove additional_months if exists
if (isset($_SESSION[$sessionKey]['additional_months'])) {
    unset($_SESSION[$sessionKey]['additional_months']);
}

// Prepare successful response
$response = [
    'status' => true,
    'message' => 'Đã xóa mã giảm giá',
    'data' => $originalValues
];

// Output the response
echo json_encode($response);
exit;
