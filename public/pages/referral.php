<?php
// Simple route handler to redirect to referral dashboard
session_start();
require_once dirname(__DIR__, 2) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . $base_path . "/pages/auth/login.php");
    exit();
}

// Redirect to referral dashboard
header("Location: " . $base_url . $base_path . "/pages/referral/dashboard_referal.php");
exit();
?>
