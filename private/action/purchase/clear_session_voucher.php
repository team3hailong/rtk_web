<?php
/**
 * Action: Clear Session Voucher
 * This action is called via navigator.sendBeacon() when a user leaves the payment page
 * without completing the transaction. It cleans up the voucher data from the session.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security: Only proceed if there is an active user session.
if (!isset($_SESSION['user_id'])) {
    // Silently exit, no action needed.
    http_response_code(204); // No Content
    exit;
}

// Get the context from the POST data
$context = $_POST['context'] ?? 'purchase';
$sessionKey = ($context === 'renewal') ? 'renewal' : 'order';

// Check if the corresponding session key exists and unset it
if (isset($_SESSION[$sessionKey])) {
    // We only want to remove voucher information, not the entire order session
    // as it might be needed for other purposes.
    unset($_SESSION[$sessionKey]['voucher_id']);
    unset($_SESSION[$sessionKey]['voucher_code']);
    unset($_SESSION[$sessionKey]['voucher_discount']);
    unset($_SESSION[$sessionKey]['additional_months']);
    unset($_SESSION[$sessionKey]['discounted_subtotal']);
    
    // Optional: Log the event for debugging
    error_log("User {$_SESSION['user_id']} left payment page. Cleared voucher from session key '{$sessionKey}'.");
}

// Always return a "No Content" response as beacon doesn't process responses.
http_response_code(204);
exit;