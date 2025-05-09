<?php
// Tải env_loader trước tiên để có thể sử dụng hàm env()
require_once __DIR__ . '/env_loader.php';

// Load tiện ích xử lý đường dẫn
require_once dirname(__DIR__) . '/utils/path_helpers/bootstrap.php';

// RTK API credentials
define('RTK_API_URL', env('RTK_API_URL', 'http://203.171.25.138:8090/openapi/broadcast/users'));
define('RTK_API_ACCESS_KEY', env('RTK_API_ACCESS_KEY', 'Zb5F6iKUuAISy4qY'));
define('RTK_API_SECRET_KEY', env('RTK_API_SECRET_KEY', 'KL1KEEJj2s6HA8LB'));
define('RTK_API_SIGN_METHOD', env('RTK_API_SIGN_METHOD', 'HmacSHA256'));

// Email Configuration 
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'dovannguyen2005bv@gmail.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', 'qbut ryan pedr aawk'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'dovannguyen2005bv@gmail.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'SMTP Mail'));

// Site Configuration
define('SITE_URL', env('SITE_URL', 'http://localhost:3000'));

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
        header('Location: ' . SITE_URL . '/public/pages/error.php');
        exit;
    });
    set_exception_handler(function($e) {
        error_log("Uncaught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        header('Location: ' . SITE_URL . '/public/pages/error.php');
        exit;
    });
}