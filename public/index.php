<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // If logged in, redirect to dashboard
    header("Location: pages/dashboard.php"); // Corrected path
    exit();
} else {
    // If not logged in, redirect to login page
    header("Location: pages/auth/login.php"); // Corrected path
    exit();
}
?>
