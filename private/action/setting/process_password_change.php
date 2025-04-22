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

// --- Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['profile_error'] = 'Invalid request method.';
    header('Location: ' . $profile_page_url);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['profile_error'] = 'User not authenticated.';
    header('Location: ' . $base_url . '/public/pages/auth/login.php'); // Redirect to login
    exit;
}

// --- Get Data from POST ---
$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// --- Validation ---
$errors = [];
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $errors[] = "Vui lòng điền đầy đủ các trường mật khẩu.";
}
if (strlen($new_password) < 6) {
     $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự.";
}
if ($new_password !== $confirm_password) {
    $errors[] = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
}

if (!empty($errors)) {
    $_SESSION['profile_error'] = implode('<br>', $errors);
    header('Location: ' . $profile_page_url);
    exit;
}

// --- Database Interaction ---
$db = null;
$pdo = null;

try {
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) {
        throw new Exception("Database connection failed.");
    }

    // Fetch current password hash
    $stmt = $pdo->prepare("SELECT password FROM user WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();

    if (!$user_data) {
        throw new Exception("User not found."); // Should not happen if session is valid
    }

    // Verify current password
    if (!password_verify($current_password, $user_data['password'])) {
        $_SESSION['profile_error'] = "Mật khẩu hiện tại không chính xác.";
        header('Location: ' . $profile_page_url);
        exit;
    }

    // Hash the new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $sql = "UPDATE user SET password = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_password_hash, $user_id]);

    $_SESSION['profile_message'] = "Đổi mật khẩu thành công!";

} catch (PDOException $e) {
    error_log("Password Change DB Error (User ID: {$user_id}): " . $e->getMessage());
    $_SESSION['profile_error'] = "Lỗi đổi mật khẩu. Vui lòng thử lại.";
} catch (Exception $e) {
    error_log("Password Change General Error (User ID: {$user_id}): " . $e->getMessage());
    $_SESSION['profile_error'] = "Đã xảy ra lỗi không mong muốn khi đổi mật khẩu.";
} finally {
    if ($db) {
        $db->close();
    }
}

// Redirect back to the profile page
header('Location: ' . $profile_page_url);
exit;
?>
