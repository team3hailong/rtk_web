<?php
// --- Project Root Path ---
$project_root_path = dirname(dirname(dirname(__DIR__))); // Adjust path as needed

// --- Base URL ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/private/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/private/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');
$contact_page_url = $base_url . '/public/pages/support/contact.php';

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/SupportRequest.php';
require_once $project_root_path . '/private/utils/csrf_helper.php';

// --- Security Checks ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['support_error'] = 'Người dùng chưa đăng nhập.';
    header('Location: ' . $base_url . '/public/pages/auth/login.php'); // Redirect to login
    exit;
}

// Lưu ý: CSRF token đã được kiểm tra trong action_handler.php
// Không cần kiểm tra lại ở đây để tránh xung đột

// --- Get Data from POST ---
$user_id = $_SESSION['user_id'];
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$category = trim($_POST['category'] ?? 'other');

// Validate category
$valid_categories = ['technical', 'billing', 'account', 'other'];
if (!in_array($category, $valid_categories)) {
    $category = 'other';
}

// --- Basic Validation ---
$errors = [];
if (empty($subject)) {
    $errors[] = "Tiêu đề không được để trống.";
}
if (empty($message)) {
    $errors[] = "Nội dung yêu cầu không được để trống.";
}

if (!empty($errors)) {
    $_SESSION['support_error'] = implode('<br>', $errors);
    header('Location: ' . $contact_page_url);
    exit;
}

// --- Process Form Submission ---
try {
    $db = new Database();
    $supportRequest = new SupportRequest($db);
    
    $result = $supportRequest->createRequest(
        $user_id, 
        $subject, 
        $message, 
        $category
    );
    
    if ($result['success']) {
        $_SESSION['support_message'] = "Yêu cầu hỗ trợ đã được gửi thành công. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.";
    } else {
        $_SESSION['support_error'] = $result['error'] ?? 'Đã xảy ra lỗi khi gửi yêu cầu hỗ trợ.';
    }
    
} catch (PDOException $e) {
    error_log("Support request PDO error: " . $e->getMessage());
    $_SESSION['support_error'] = "Lỗi kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.";
} catch (Exception $e) {
    error_log("Support request general error: " . $e->getMessage());
    $_SESSION['support_error'] = "Đã xảy ra lỗi không mong muốn. Vui lòng thử lại sau.";
}

// --- Redirect Back to Support Page ---
header('Location: ' . $contact_page_url);
exit;
