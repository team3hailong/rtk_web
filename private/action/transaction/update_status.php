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
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Referral.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/utils/security_helper.php';

// Anyone can access this endpoint (no verification needed)

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
          // Process referral commission if this is a completed transaction with confirmed payment
        try {
            // Get payment_confirmed status
            $payment_confirmed = false;
            if ($new_status === 'completed') {
                $stmt = $pdo->prepare("SELECT payment_confirmed FROM transaction_history WHERE id = :id");
                $stmt->bindParam(':id', $transaction_id, PDO::PARAM_INT);
                $stmt->execute();
                $payment_confirmed = (bool)$stmt->fetchColumn();
            }
            
            // Only calculate commission if status is completed and payment is confirmed
            if ($new_status === 'completed' && $payment_confirmed) {
                $referralService = new Referral($db);
                $referralService->calculateCommission($transaction_id);
                error_log("Processing commission for transaction ID: $transaction_id with confirmed payment");
            } else {
                error_log("Skipping commission for transaction ID: $transaction_id - Status: $new_status, Payment confirmed: " . ($payment_confirmed ? "Yes" : "No"));
            }
        } catch (Exception $e) {
            // Log error but continue processing (commission calculation shouldn't block the main transaction)
            error_log("Error calculating referral commission: " . $e->getMessage());
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
