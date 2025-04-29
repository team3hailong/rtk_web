<?php
/**
 * Action Handler - Proxy file to securely access private action scripts
 * 
 * This file acts as a bridge between public requests and private actions
 * It validates the request and then forwards to the appropriate private file
 */
session_start();

// --- Define root paths ---
$project_root_path = dirname(dirname(__DIR__)); // Go up two levels from /public/handlers
$private_action_path = $project_root_path . '/private/action';

// --- Get action parameters ---
$module = $_GET['module'] ?? '';  // e.g., auth, purchase, setting
$action = $_GET['action'] ?? '';  // e.g., login, register, update

// --- Validate parameters ---
if (empty($module) || empty($action)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// --- Security: Validate module and action names to prevent path traversal ---
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $module) || !preg_match('/^[a-zA-Z0-9_-]+$/', $action)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid parameter format']);
    exit;
}

// --- Build target path ---
$target_script = "{$private_action_path}/{$module}/{$action}.php";

// --- Check if the target script exists ---
if (!file_exists($target_script)) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Action not found']);
    exit;
}

// --- Authentication check for protected actions ---
$public_actions = [
    'auth/process_login',
    'auth/process_register',
    'auth/verify-email'
];

// Check if the current action requires authentication
if (!in_array("$module/$action", $public_actions) && !isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// --- CSRF protection (optional but recommended) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // You can implement CSRF token validation here
    // if (csrf_token is invalid) { ... error ... }
}

// --- Include the target script ---
include $target_script;

// --- Note: The target script should handle its own output ---