<?php
// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(__DIR__) . '/private/config/config.php';

// Sử dụng middleware session thay vì session_start thông thường
init_session();

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // If logged in, redirect to dashboard
    header("Location: " . $base_url . "/public/pages/dashboard.php");
    exit();
} else {    // If not logged in, redirect to homepage
    header("Location: " . $base_url . "/public/pages/homepage.php");
    exit();
}
?>
