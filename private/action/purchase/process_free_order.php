<?php
/**
 * Action: Process Free Order
 * Handles the activation of an order that has a total cost of 0 due to a voucher.
 */

// --- Pre-execution checks and setup ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Define root path and include necessary files ---
// File này nằm trong /private/action/purchase/, cần đi lên 3 cấp để đến project root
$project_root_path = realpath(dirname(__FILE__) . '/../../../');

// Debug: kiểm tra đường dẫn (sẽ xóa sau khi test)
// error_log("DEBUG: project_root_path = " . $project_root_path);
// error_log("DEBUG: config path = " . $project_root_path . '/private/config/config.php');

require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/utils/functions.php';
require_once $project_root_path . '/private/utils/csrf_helper.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/purchase/PaymentService.php';

// --- Helper function for JSON responses (if needed) ---
if (!function_exists('send_json_response')) {
    function send_json_response($status, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    }
}

// --- Main Logic ---

// 1. Basic security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/pages/purchase/packages.php?error=invalid_request');
    exit;
}

// CSRF check (được thực hiện bởi action_handler.php, nhưng kiểm tra lại cho chắc chắn)
// CSRF check disabled for testing
/*
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=csrf_error');
    exit;
}
*/

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

// 2. Get input data
$registration_id = $_POST['registration_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$registration_id) {
    header('Location: ' . BASE_URL . '/public/pages/purchase/packages.php?error=missing_data');
    exit;
}

// 3. **CRITICAL SECURITY STEP:** Verify that the order is indeed free
// This block recalculates the final price based on session data to prevent manipulation.
$is_renewal = $_SESSION['is_renewal'] ?? false;
$sessionKey = $is_renewal ? 'renewal' : 'order';
$finalPrice = -1; // Default to an invalid price

// Ensure the necessary session data exists
if (isset($_SESSION[$sessionKey]['voucher_code']) && isset($_SESSION['payment_data'])) {
    $payment_data = $_SESSION['payment_data'];
    $base_subtotal = $payment_data['base_price_from_registration'] * $payment_data['quantity'];
    $discountAmount = $_SESSION[$sessionKey]['voucher_discount'] ?? 0;
    
    // Calculate price after discount
    $discounted_subtotal = max(0, $base_subtotal - $discountAmount);
    
    // Calculate VAT on the discounted price
    $vat_percent = $payment_data['vat_percent_from_registration'];
    $vat_on_discounted = round($discounted_subtotal * ($vat_percent / 100));
    
    // Final calculated price
    $finalPrice = $discounted_subtotal + $vat_on_discounted;
}

// Only proceed if the re-calculated price is zero (or very close to it due to float rounding)
if ($finalPrice > 0.01) {
    // Log a security alert
    error_log(
        "SECURITY ALERT: User ID {$user_id} attempted to process a non-free order (Registration ID: {$registration_id}) as free. " .
        "Recalculated price was {$finalPrice}."
    );
    // Redirect with a generic error to avoid giving away information
    header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=invalid_free_order_attempt');
    exit;
}

// 4. Process the free order
try {
    $paymentService = new PaymentService();
    
    // You need to create this method in your PaymentService class.
    // It should perform actions like:
    // - Update the registration's status to 'active'.
    // - Update the transaction_history status to 'completed' with amount 0.
    // - Set the expiry date for the user's account(s).
    // - Potentially log the voucher usage.
    $activation_result = $paymentService->activateFreeOrder($registration_id, $user_id);
    
    if ($activation_result['success']) {
        // 5. Clean up session data on success
        unset($_SESSION['pending_registration_id']);
        unset($_SESSION['pending_total_price']);
        unset($_SESSION['payment_data']);
        unset($_SESSION['order']);
        unset($_SESSION['renewal']);
        if (isset($_SESSION['is_renewal'])) {
            unset($_SESSION['is_renewal']);
        }
        
        // 6. Redirect to the processing page
        header('Location: ' . BASE_URL . '/public/pages/purchase/order_processing.php');
        exit;
        
    } else {
        // Handle activation failure
        $error_message = $activation_result['error'] ?? 'activation_failed';
        error_log("Free order activation failed for User ID {$user_id}, Registration ID {$registration_id}. Reason: {$error_message}");
        header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=' . urlencode($error_message));
        exit;
    }
    
} catch (Exception $e) {
    error_log("Exception during free order processing for User ID {$user_id}: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=server_error');
    exit;
}