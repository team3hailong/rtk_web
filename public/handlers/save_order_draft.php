<?php
// Start session if not already started
session_start();

// Include configuration file
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// Initialize session
init_session();

// Define constants
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// Redirect function
function redirect_with_error($message) {
    $base_url = BASE_URL;
    $error_query = http_build_query(['error' => $message]);
    header("Location: {$base_url}/public/pages/purchase/details.php?{$_SERVER['QUERY_STRING']}&{$error_query}");
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error('invalid_request_method');
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// Validate required fields
$required_fields = ['package_id', 'package_name', 'purchase_type', 'total_price'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        redirect_with_error('missing_' . $field);
    }
}

// Validate quantity (if not trial package)
if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity']) || intval($_POST['quantity']) < 1) {
    redirect_with_error('invalid_quantity');
}

// Validate location selections
if (!isset($_POST['location_id']) || !is_array($_POST['location_id']) || count($_POST['location_id']) < 1) {
    redirect_with_error('no_locations_selected');
}

// Validate total price
if (!is_numeric($_POST['total_price']) || floatval($_POST['total_price']) <= 0) {
    redirect_with_error('invalid_total_price');
}

// Prepare order draft data
$order_draft = [
    'package_id' => $_POST['package_id'],
    'package_name' => $_POST['package_name'],
    'package_varchar_id' => $_POST['package_varchar_id'] ?? '',
    'quantity' => intval($_POST['quantity']),
    'location_id' => $_POST['location_id'],
    'total_price' => floatval($_POST['total_price']),
    'purchase_type' => $_POST['purchase_type'],
    'created_at' => time(),
    'user_id' => $_SESSION['user_id']
];

// Add base_price if available
if (isset($_POST['base_price'])) {
    $order_draft['base_price'] = floatval($_POST['base_price']);
}

// Store in session
$_SESSION['order_draft'] = $order_draft;

// Set required session variables for payment.php
$_SESSION['pending_registration_id'] = rand(100000, 999999); // Temporary ID until actual order processing
$_SESSION['pending_total_price'] = $order_draft['total_price'];
$_SESSION['pending_is_trial'] = isset($_POST['package_varchar_id']) && $_POST['package_varchar_id'] === 'trial_7d';

// Set up order session object required by the payment system
$_SESSION['order'] = [
    'registration_id' => $_SESSION['pending_registration_id'],
    'total_price' => $order_draft['total_price'],
    'package_id' => $order_draft['package_id'],
    'package_name' => $order_draft['package_name'],
    'quantity' => $order_draft['quantity'],
    'locations' => $order_draft['location_id'],
    'purchase_type' => $order_draft['purchase_type']
];

// Log the action
error_log("Order draft saved for user ID: {$_SESSION['user_id']}, Package: {$_POST['package_name']}, Total: {$_POST['total_price']}");

// Redirect to payment page
header('Location: ' . $base_url . '/public/pages/purchase/payment.php');
exit;
?>