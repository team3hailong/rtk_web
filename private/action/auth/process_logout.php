<?php
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

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

// Redirect to the login page
header("Location: " . $base_path . "/pages/auth/login.php");
exit();
?>
