<?php
/**
 * Purchase Success Action Handler
 * 
 * Processes requests for the purchase success page
 */

// Basic validation and security checks
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header('Location: ' . BASE_URL . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

// Include necessary files
require_once PROJECT_ROOT_PATH . '/private/classes/purchase/SuccessService.php';

// Initialize service
$successService = new SuccessService();

// Determine the action to take
$sub_action = $_GET['sub_action'] ?? 'view';

switch ($sub_action) {
    case 'get_details':
        // Get the registration ID
        $registration_id = isset($_GET['registration_id']) ? intval($_GET['registration_id']) : 0;
        
        if ($registration_id <= 0) {
            echo json_encode([
                'success' => false, 
                'error' => 'invalid_registration_id',
                'message' => 'ID đăng ký không hợp lệ'
            ]);
            exit;
        }
        
        // Get purchase details
        $result = $successService->getSuccessPageDetails($registration_id);
        
        if ($result['success']) {
            // Store in session for the success page
            $successService->saveSuccessDataToSession($result['data']);
            
            // Redirect to success page or return data based on format parameter
            $format = $_GET['format'] ?? 'redirect';
            if ($format === 'json') {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            } else {
                // Redirect to success page
                header('Location: ' . BASE_URL . '/public/pages/purchase/success.php');
                exit;
            }
        } else {
            // Handle error
            if (isset($_GET['format']) && $_GET['format'] === 'json') {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            } else {
                // Redirect with error
                header('Location: ' . BASE_URL . '/public/pages/purchase/packages.php?error=' . $result['error']);
                exit;
            }
        }
        break;
        
    case 'clear_session':
        // Clear session data
        $successService->clearSuccessDataFromSession();
        
        $redirect_url = $_GET['redirect_url'] ?? BASE_URL . '/public/pages/dashboard.php';
        header('Location: ' . $redirect_url);
        exit;
        break;
        
    default:
        // Invalid action
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'invalid_action',
            'message' => 'Thao tác không hợp lệ'
        ]);
        exit;
}
