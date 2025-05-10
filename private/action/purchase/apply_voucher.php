<?php
/**
 * Apply Voucher to Order/Renewal
 * 
 * This action applies a voucher to the current order and returns updated information
 */

// Include necessary files
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Voucher.php';

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

// Get voucher code from POST
$voucherCode = isset($_POST['voucher_code']) ? trim($_POST['voucher_code']) : '';
$orderAmount = isset($_POST['order_amount']) ? (float) $_POST['order_amount'] : 0;
$orderContext = isset($_POST['context']) ? $_POST['context'] : 'purchase'; // purchase or renewal
$userId = $_SESSION['user_id'] ?? null;

// Debug information (remove in production)
$debug_info = [
    'has_order_session' => isset($_SESSION['order']),
    'has_renewal_session' => isset($_SESSION['renewal']),
    'has_pending_registration' => isset($_SESSION['pending_registration_id']),
    'has_pending_price' => isset($_SESSION['pending_total_price']),
    'request_context' => $orderContext,
    'session_data' => array_keys($_SESSION)
];

// Check if our session has the order data
if (!isset($_SESSION['order']) && !isset($_SESSION['renewal'])) {
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
        $response['debug'] = $debug_info; // Debug info
        echo json_encode($response);
        exit;
    }
}

// Use the appropriate session key based on context
$sessionKey = ($orderContext === 'renewal') ? 'renewal' : 'order';

// Get the current order data
$orderData = $_SESSION[$sessionKey];

// Check if voucher is already applied
if (isset($orderData['voucher_id'])) {
    $response['message'] = 'Đã có một mã giảm giá được áp dụng. Vui lòng xóa mã hiện tại trước khi thêm mã mới.';
    echo json_encode($response);
    exit;
}

// Connect to database
try {
    $db = new Database();
} catch (Exception $e) {
    $response['message'] = 'Lỗi kết nối cơ sở dữ liệu';
    $response['debug'] = $e->getMessage();
    echo json_encode($response);
    exit;
}

// Create Voucher instance and validate
try {
    $voucherService = new Voucher($db);
    $validationResult = $voucherService->validateVoucher($voucherCode, $orderAmount, $userId);
} catch (Exception $e) {
    $response['message'] = 'Lỗi xử lý mã giảm giá';
    $response['debug'] = $e->getMessage();
    echo json_encode($response);
    exit;
}

if (!$validationResult['status']) {
    // Voucher validation failed
    $response['message'] = $validationResult['message'];
    echo json_encode($response);
    exit;
}

// Get voucher data
$voucher = $validationResult['data'];

// Apply voucher to order
$applicationResult = $voucherService->applyVoucher($voucher, $orderAmount);

// Update session data with voucher information
$_SESSION[$sessionKey]['voucher_id'] = $voucher['id'];
$_SESSION[$sessionKey]['voucher_code'] = $voucher['code'];
$_SESSION[$sessionKey]['original_amount'] = $orderAmount;
$_SESSION[$sessionKey]['voucher_discount'] = $applicationResult['discount_value'];

// Update total price
if ($sessionKey === 'order') {
    $_SESSION[$sessionKey]['total_price'] = $applicationResult['new_amount'];
} elseif ($sessionKey === 'renewal') {
    $_SESSION[$sessionKey]['amount'] = $applicationResult['new_amount'];
}

// If this is an extend_duration voucher, update end time for renewal
if ($voucher['voucher_type'] === 'extend_duration' && $sessionKey === 'renewal') {
    // Store the additional months
    $_SESSION[$sessionKey]['additional_months'] = $applicationResult['additional_months'];
}

// Update transaction in database if it exists
if (isset($_SESSION['pending_registration_id'])) {
    $registration_id = $_SESSION['pending_registration_id'];
    try {
        $conn = $db->getConnection();
        // Check if transaction exists and update it
        $stmt = $conn->prepare("SELECT id FROM transaction_history WHERE registration_id = ? AND status = 'pending'");
        $stmt->execute([$registration_id]);
        $transaction_id = $stmt->fetchColumn();
        
        if ($transaction_id) {
            // Update existing transaction with new voucher and amount
            $stmt = $conn->prepare("UPDATE transaction_history SET 
                                   voucher_id = ?, 
                                   amount = ?, 
                                   updated_at = NOW() 
                                   WHERE id = ?");
            $stmt->execute([$voucher['id'], $applicationResult['new_amount'], $transaction_id]);
            
            // Record user's voucher usage with transaction ID
            if ($userId) {
                $voucherService->recordUserVoucherUsage($userId, $voucher['id'], $transaction_id);
            }
        }
    } catch (Exception $e) {
        error_log("Error updating transaction with voucher: " . $e->getMessage());
        // We continue despite error since session data still exists
    }
} else {
    // Just record user's intended usage of voucher, transaction ID will be updated later
    if ($userId) {
        $voucherService->recordUserVoucherUsage($userId, $voucher['id']);
    }
}

// Increment voucher usage counter
$voucherService->incrementUsage($voucher['id']);

// Prepare response
$response = [
    'status' => true,
    'message' => 'Mã giảm giá áp dụng thành công',
    'data' => [
        'voucher_id' => $voucher['id'],
        'voucher_code' => $voucher['code'],
        'voucher_type' => $voucher['voucher_type'],
        'discount_value' => $applicationResult['discount_value'],
        'additional_months' => $applicationResult['additional_months'] ?? 0,
        'new_amount' => $applicationResult['new_amount'],
        'original_amount' => $orderAmount
    ]
];

// Output the response
echo json_encode($response);
exit;
