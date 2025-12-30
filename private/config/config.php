<?php
// Tải env_loader trước tiên để có thể sử dụng hàm env()
require_once __DIR__ . '/env_loader.php';

// Load tiện ích xử lý đường dẫn
require_once dirname(__DIR__) . '/utils/path_helpers/bootstrap.php';

// Load session middleware
require_once dirname(__DIR__) . '/utils/session_middleware.php';

// Thiết lập múi giờ mặc định là +7 (Asia/Ho_Chi_Minh)
date_default_timezone_set('Asia/Ho_Chi_Minh');


// RTK API credentials
define('RTK_API_URL', env('RTK_API_URL', 'http://rtk.taikhoandodac.vn:8090/openapi/broadcast/users'));
define('RTK_API_ACCESS_KEY', env('RTK_API_ACCESS_KEY', 'TxfJxeX7wuU7XOPU'));
define('RTK_API_SECRET_KEY', env('RTK_API_SECRET_KEY', 'NZbVkrJ5e5SDcP0R'));
define('RTK_API_SIGN_METHOD', env('RTK_API_SIGN_METHOD', 'HmacSHA256'));

// Email Configuration 
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'dovannguyen2005bv@gmail.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', 'qbut ryan pedr aawk'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'dovannguyen2005bv@gmail.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'SMTP Mail'));
// Optional explicit SMTP security and auth flags. Use env variables to override.
// Examples: SMTP_SECURE=ssl | tls | starttls ; SMTP_AUTH=true|false ; SMTP_DEBUG=0|1|2|3
define('SMTP_SECURE', env('SMTP_SECURE', ''));
define('SMTP_AUTH', env('SMTP_AUTH', 'true') === 'true');
define('SMTP_DEBUG', (int) env('SMTP_DEBUG', 0));

// Site Configuration
// Define SITE_URL for link generation. If not set in env, derive from base URL helper.
if (!defined('SITE_URL')) {
    $site_url_candidate = env('SITE_URL', '');
    if (empty($site_url_candidate)) {
        // get_base_url is provided by utils/path_helpers/bootstrap.php
        $site_url_candidate = function_exists('get_base_url') ? get_base_url() : 'http://localhost';
    }
    define('SITE_URL', rtrim($site_url_candidate, '/'));
}
define('ADMIN_SITE', 'http://quantri.taikhoandodac.vn');

// Global discount configuration
// `GLOBAL_DISCOUNT_CODE`: Mã giảm giá toàn cục (string)
// `SHOW_GLOBAL_DISCOUNT`: "yes" hoặc "no"
define('GLOBAL_DISCOUNT_CODE', env('GLOBAL_DISCOUNT_CODE', ''));
define('SHOW_GLOBAL_DISCOUNT', env('SHOW_GLOBAL_DISCOUNT', 'no'));

// Toggle to disable the Kinh Tuyến Trục popup without removing files.
// Set DISABLE_KINH_TUYEN_TRUC_POPUP=true in the environment to disable.
define('DISABLE_KINH_TUYEN_TRUC_POPUP', env('DISABLE_KINH_TUYEN_TRUC_POPUP', 'true') === 'true');

// Environment and error handling settings
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', APP_ENV === 'development');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

if (!APP_DEBUG) {
    // Convert PHP errors to log entries and show friendly error page
    set_error_handler(function($severity, $message, $file, $line) {
        error_log("PHP Error [{$severity}]: {$message} in {$file} on line {$line}");
        header('Location: ' . get_base_url() . '/public/pages/error.php');
        exit;
    });
    set_exception_handler(function($e) {
        // Log the full error details to the error log file
        error_log("Uncaught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());

        // Set a generic error message for the user in the session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error_message'] = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau hoặc liên hệ quản trị viên.';
        // Optionally log minimal context if needed for user session, e.g., error ID

        // Redirect to a generic error page without exposing details in URL
        // Ensure SITE_URL is defined and correct
        header('Location: ' . get_base_url() . '/public/pages/error.php');
        exit;
    });
}