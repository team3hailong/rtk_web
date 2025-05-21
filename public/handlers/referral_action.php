<?php
require_once dirname(__DIR__, 2) . '/private/config/config.php';

// Define the path to the private action script
$private_action_path = PROJECT_ROOT_PATH . '/private/action/referral/process_withdrawal.php';

// Check if the private action script exists
if (file_exists($private_action_path)) {
    // Include the private action script
    require_once $private_action_path;
} else {
    // Handle the error if the script doesn't exist
    // You can log this error or return a specific error message
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error processing request.']);
    exit();
}
?>
