<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/error_handler.php';

// Log logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'] ?? 'Người dùng';
        $notify_content = $username . ' đã đăng xuất khỏi hệ thống';
        log_activity($conn, $user_id, 'logout', 'user', $user_id, null, [
            'logout_time' => date('Y-m-d H:i:s'),
        ], $notify_content);
        
        // Xóa token "Remember Me" trong database nếu có
        if (isset($_COOKIE['remember_token'])) {
            list($cookie_user_id, $token) = explode(':', $_COOKIE['remember_token'], 2);
            if ($cookie_user_id == $user_id) {
                $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        log_error($conn, 'auth', "Error logging logout: " . $e->getMessage(), $e->getTraceAsString(), $_SESSION['user_id'] ?? null);
    }
}

// Unset all session variables
$_SESSION = array();

// Xóa cookie "Remember Me"
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Define the base path for redirection (adjust if necessary)
$base_path = '/public'; // Or determine dynamically if needed

// Redirect to the homepage instead of login
header("Location: " . $base_path . "/pages/homepage.php");
exit();
?>
