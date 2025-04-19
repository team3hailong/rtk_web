<?php
session_start();

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
$profile_page_url = $base_url . '/public/pages/setting/profile.php';

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';

// --- Database Connection & User Data Fetch ---
function fetchUserData($user_id) {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        return ['error' => 'Lỗi kết nối cơ sở dữ liệu.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT username, email, phone, is_company, company_name, tax_code 
                              FROM user 
                              WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['error' => 'Không tìm thấy thông tin người dùng.'];
        }
        
        return ['data' => $user];
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return ['error' => 'Lỗi khi truy vấn thông tin người dùng.'];
    } finally {
        $db->close();
    }
}

// Fetch user data if not processing form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $result = fetchUserData($_SESSION['user_id']);
    if (isset($result['data'])) {
        $_SESSION['user_data'] = $result['data'];
    }
    if (isset($result['error'])) {
        $_SESSION['profile_error'] = $result['error'];
    }
    header('Location: ' . $profile_page_url);
    exit;
}

// --- Security Checks ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['profile_error'] = 'User not authenticated.';
    header('Location: ' . $base_url . '/public/pages/auth/login.php'); // Redirect to login
    exit;
}

// --- Get Data from POST ---
$user_id = $_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$is_company = isset($_POST['is_company']) ? 1 : 0;
$company_name = $is_company ? trim($_POST['company_name'] ?? '') : null;
$tax_code = $is_company ? trim($_POST['tax_code'] ?? '') : null;

// If company name is empty but is_company is checked, use username
if ($is_company && empty($company_name)) {
    $company_name = $username;
}

// --- Basic Validation ---
$errors = [];
if (empty($username)) {
    $errors[] = "Tên người dùng không được để trống.";
}
if ($is_company && empty($tax_code)) {
    $errors[] = "Mã số thuế không được để trống nếu đăng ký là công ty.";
}
if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
    $errors[] = "Số điện thoại không hợp lệ (phải có 10-11 chữ số).";
}

if (!empty($errors)) {
    $_SESSION['profile_error'] = implode('<br>', $errors);
    header('Location: ' . $profile_page_url);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        // ...existing update code...

        // After successful update, fetch fresh user data
        $result = fetchUserData($user_id);
        if (isset($result['data'])) {
            $_SESSION['user_data'] = $result['data'];
            $_SESSION['profile_message'] = "Hồ sơ đã được cập nhật thành công.";
        }
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        $_SESSION['profile_error'] = "Có lỗi xảy ra khi cập nhật hồ sơ.";
    }
}

header('Location: ' . $profile_page_url);
exit;
?>
