<?php
// Simple route handler to redirect to referral dashboard
session_start();
require_once dirname(__DIR__, 2) . '/private/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/public/pages/auth/login.php");
    exit();
}

// Redirect to referral dashboard
header("Location: " . BASE_URL . "/public/pages/referral/dashboard_referal.php");
exit();
?>
