<?php
// Handler to mark notifications as read
session_start();

// Require configuration file
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize database connection
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
$db = new Database();
$pdo = $db->getConnection();

try {
    // Set user activities as read
    $stmt = $pdo->prepare("
        UPDATE activity_logs 
        SET has_read = 1 
        WHERE user_id = :user_id AND has_read = 0
    ");
    $stmt->execute(['user_id' => $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
