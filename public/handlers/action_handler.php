<?php
/**
 * Action Handler - Proxy file to securely access private action scripts
 * 
 * This file acts as a bridge between public requests and private actions
 * It validates the request and then forwards to the appropriate private file
 */
session_start();

// --- Define root paths ---
$project_root_path = dirname(dirname(__DIR__)); // Go up two levels from /public/handlers
$private_action_path = $project_root_path . '/private/action';

// --- Get action parameters ---
$module = $_GET['module'] ?? '';  // e.g., auth, purchase, setting
$action = $_GET['action'] ?? '';  // e.g., login, register, update

// --- Validate parameters ---
if (empty($module) || empty($action)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// --- Security: Validate module and action names to prevent path traversal ---
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $module) || !preg_match('/^[a-zA-Z0-9_-]+$/', $action)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid parameter format']);
    exit;
}

// --- Build target path ---
$target_script = "{$private_action_path}/{$module}/{$action}.php";

// --- Check if the target script exists ---
if (!file_exists($target_script)) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Action not found']);
    exit;
}

// --- Authentication check for protected actions ---
$public_actions = [
    'auth/process_login',
    'auth/process_register',
    'auth/verify-email',
    'auth/process_forgot_password',
    'auth/process_reset_password'
];

// Check if the current action requires authentication
if (!in_array("$module/$action", $public_actions) && !isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// --- CSRF protection ---
require_once $project_root_path . '/private/utils/csrf_helper.php';

// Danh sách các action không cần kiểm tra CSRF (như API endpoints)
$csrf_exempt_actions = [
    'auth/verify-email',  // Verification qua email link không cần CSRF
    'auth/process_forgot_password',  // Form quên mật khẩu không cần CSRF
    'auth/process_reset_password',  // Form đặt lại mật khẩu không cần CSRF
    'purchase/apply_voucher',  // Voucher applications via AJAX
    'purchase/remove_voucher',  // Voucher removal via AJAX
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array("$module/$action", $csrf_exempt_actions)) {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($token)) {
        // Log lỗi CSRF
        $error_message = "CSRF validation failed for $module/$action";
        $user_id = $_SESSION['user_id'] ?? 0;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        error_log("[CSRF ERROR] $error_message | User: $user_id | IP: $ip_address | Token: " . substr($token, 0, 8));
        
        // Kiểm tra xem có session tokens không
        $has_tokens = isset($_SESSION['csrf_tokens']) && is_array($_SESSION['csrf_tokens']);
        $token_count = $has_tokens ? count($_SESSION['csrf_tokens']) : 0;
        error_log("[CSRF DEBUG] User: $user_id has $token_count tokens in session");
          // Đối với form submission thông thường, set thông báo lỗi trong session và chuyển hướng
        // thay vì trả về JSON response
        // Xác định loại thông báo lỗi dựa vào module 
        if ($module == 'support') {
            $_SESSION['support_error'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        } else {
            $_SESSION['error'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        }
        
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!empty($referer)) {
            // Nếu có HTTP_REFERER, quay về trang gửi request
            header('Location: ' . $referer);
        } else {
            // Nếu không, quay về trang chủ hoặc dashboard
            header('Location: ' . '/public/pages/dashboard.php');
        }
        exit;
    }
}

// --- Include the target script ---
include $target_script;

// --- Note: The target script should handle its own output ---