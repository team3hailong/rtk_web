<?php
/**
 * Action: Update Transaction Status
 * 
 * This action updates a transaction's status and handles related operations like voucher usage.
 */

// Include necessary files
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Transaction.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/utils/security_helper.php';

// Verify user permissions (admin or staff only)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    $response = [
        'status' => false,
        'message' => 'Access denied',
    ];
    echo json_encode($response);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = [
        'status' => false,
        'message' => 'Invalid request method',
    ];
    echo json_encode($response);
    exit;
}

// Get parameters
$transaction_id = $_POST['transaction_id'] ?? 0;
$new_status = $_POST['status'] ?? '';
$update_voucher = isset($_POST['update_voucher']) ? (bool)$_POST['update_voucher'] : true;

// Validate parameters
if (!$transaction_id || !in_array($new_status, ['completed', 'pending', 'failed', 'cancelled', 'refunded'])) {
    $response = [
        'status' => false,
        'message' => 'Invalid parameters',
    ];
    echo json_encode($response);
    exit;
}

// Connect to database and update transaction
try {
    $db = new Database();
    $transactionService = new Transaction($db);
    
    // Update transaction status (this will also handle voucher if needed)
    $result = $transactionService->updateTransactionStatus($transaction_id, $new_status, $update_voucher);
    
    // If status is completed, we need to update registration status as well
    if ($new_status === 'completed') {
        $pdo = $db->getConnection();
        
        // Get registration ID from transaction
        $stmt = $pdo->prepare("SELECT registration_id FROM transaction_history WHERE id = :id");
        $stmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();
        $registration_id = $stmt->fetchColumn();
        
        if ($registration_id) {
            // Update registration status to active
            $stmt = $pdo->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = :id AND status = 'pending'");
            $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Check if update was successful
    if ($result) {
        $response = [
            'status' => true,
            'message' => 'Cập nhật trạng thái giao dịch thành công',
            'data' => [
                'transaction_id' => $transaction_id,
                'new_status' => $new_status
            ]
        ];
    } else {
        $response = [
            'status' => false,
            'message' => 'Không thể cập nhật trạng thái giao dịch',
        ];
    }
    
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    error_log("Error updating transaction status: " . $e->getMessage());
    $response = [
        'status' => false,
        'message' => 'Đã xảy ra lỗi khi cập nhật trạng thái giao dịch',
        'error' => $e->getMessage()
    ];
    echo json_encode($response);
    exit;
}
