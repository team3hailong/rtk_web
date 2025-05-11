<?php
session_start();
require_once dirname(__DIR__, 3) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Referral.php';

// Set content type to JSON for AJAX responses
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Yêu cầu không hợp lệ.'];

// Initialize error logging to a dedicated file for withdrawal requests
$log_file = PROJECT_ROOT_PATH . '/private/logs/withdrawal_requests.log';
ini_set('error_log', $log_file);

error_log("--- New Withdrawal Request --- Method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức yêu cầu không hợp lệ.';
    error_log("[Process Withdrawal] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode($response);
    exit;
}

// CSRF Token Validation
$post_csrf = $_POST['csrf_token'] ?? 'NOT SET';
$session_csrf = $_SESSION['csrf_token'] ?? 'NOT SET';
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $response['message'] = 'CSRF token không hợp lệ. Vui lòng thử lại.';
    error_log("[Process Withdrawal] CSRF token validation failed. POST CSRF: $post_csrf, Session CSRF: $session_csrf");
    echo json_encode($response);
    exit;
}
error_log("[Process Withdrawal] CSRF token validated successfully.");

// User Authentication Check
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Bạn cần đăng nhập để thực hiện chức năng này.';
    error_log("[Process Withdrawal] User not logged in. Session ID: " . session_id());
    echo json_encode($response);
    exit;
}
$user_id = $_SESSION['user_id'];
error_log("[Process Withdrawal] Authenticated User ID: $user_id");

// Input Sanitization and Validation
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$bankName = filter_input(INPUT_POST, 'bank_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$accountNumber = filter_input(INPUT_POST, 'account_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$accountHolder = filter_input(INPUT_POST, 'account_holder', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

error_log("[Process Withdrawal] Input Data - User ID: $user_id, Amount: '$amount', BankName: '$bankName', AccountNumber: '$accountNumber', AccountHolder: '$accountHolder'");

if ($amount === false || $amount === null || trim($bankName ?? '') === '' || trim($accountNumber ?? '') === '' || trim($accountHolder ?? '') === '') {
    $response['message'] = 'Vui lòng điền đầy đủ thông tin: Số tiền, Tên ngân hàng, Số tài khoản, Tên chủ tài khoản.';
    error_log("[Process Withdrawal] Validation failed: Missing or invalid fields for User ID: $user_id.");
    echo json_encode($response);
    exit;
}

// Define MIN_WITHDRAWAL_AMOUNT (Consider moving to config.php if used globally)
if (!defined('MIN_WITHDRAWAL_AMOUNT')) {
    define('MIN_WITHDRAWAL_AMOUNT', 100000); 
}

if ($amount < MIN_WITHDRAWAL_AMOUNT) {
    $response['message'] = 'Số tiền rút tối thiểu là ' . number_format(MIN_WITHDRAWAL_AMOUNT, 0, ',', '.') . ' VNĐ.';
    error_log("[Process Withdrawal] Validation failed: Amount $amount is less than minimum " . MIN_WITHDRAWAL_AMOUNT . " for User ID: $user_id");
    echo json_encode($response);
    exit;
}
error_log("[Process Withdrawal] Input validation passed for User ID: $user_id.");

try {
    $db = new Database(); 
    $referralService = new Referral($db);

    error_log("[Process Withdrawal] Calling createWithdrawalRequest method for User ID: $user_id.");
    $result = $referralService->createWithdrawalRequest($user_id, $amount, $bankName, $accountNumber, $accountHolder);
    
    error_log("[Process Withdrawal] Service call result for User ID $user_id: " . print_r($result, true));
    echo json_encode($result); // $result from createWithdrawalRequest should be ['success' => bool, 'message' => string]

} catch (PDOException $e) {
    error_log("[Process Withdrawal] PDOException for User ID $user_id: " . $e->getMessage() . "\nSQLSTATE: " . $e->getCode() . "\nTrace: " . $e->getTraceAsString());
    $response['message'] = 'Lỗi kết nối hoặc truy vấn cơ sở dữ liệu. Vui lòng thử lại sau.';
    echo json_encode($response);
} catch (Exception $e) {
    error_log("[Process Withdrawal] General Exception for User ID $user_id: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    $response['message'] = 'Đã xảy ra lỗi hệ thống không mong muốn. Vui lòng thử lại sau.';
    echo json_encode($response);
}
exit;
?>