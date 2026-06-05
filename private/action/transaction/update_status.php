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
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/purchase/AutoAccountCreator.php';
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
            // Kiểm tra xem registration đã có tài khoản chưa
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM account_groups WHERE registration_id = :id");
            $stmt_check->bindParam(':id', $registration_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $account_exists = $stmt_check->fetchColumn() > 0;
            
            if (!$account_exists) {
                // Tự động tạo tài khoản
                $accountCreator = new AutoAccountCreator();
                $result = $accountCreator->createAccountsForRegistration($registration_id);
                
                if ($result['success']) {
                    error_log("[AUTO_ACCOUNT] Successfully created accounts for registration $registration_id via update_status");
                } else {
                    error_log("[AUTO_ACCOUNT] Failed to create accounts for registration $registration_id: " . $result['error']);
                    // Update registration status to active manually nếu tạo tài khoản thất bại
                    $stmt = $pdo->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = :id AND status = 'pending'");
                    $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } else {
                // Đã có tài khoản rồi, chỉ cập nhật status
                $stmt = $pdo->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = :id AND status = 'pending'");
                $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
          // Process referral commission if this is a completed transaction with confirmed payment
        try {            // Only calculate commission if status is completed
            // We no longer check if payment is confirmed - transaction_history status being 'completed' is sufficient
            if ($new_status === 'completed') {
                $referralService = new Referral($db);
                
                // Check if commission record already exists
                $commissionExists = false;
                $checkStmt = $pdo->prepare("SELECT id, status FROM referral_commission WHERE transaction_id = :transaction_id");
                $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
                $checkStmt->execute();
                $commissionRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($commissionRecord) {
                    // Make sure it's set to approved if it's not already
                    if ($commissionRecord['status'] !== 'approved') {
                        $updateStmt = $pdo->prepare("
                            UPDATE referral_commission 
                            SET status = 'approved', updated_at = NOW()
                            WHERE transaction_id = :transaction_id
                        ");
                        $updateStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
                        $updateStmt->execute();
                        error_log("Updated existing commission for transaction ID: $transaction_id to approved status");
                    }
                } else {
                    // Calculate and add new commission record - this will set status to 'approved' automatically
                    $result = $referralService->calculateCommission($transaction_id);
                    error_log("Processing new commission for transaction ID: $transaction_id with confirmed payment. Result: " . ($result ? "Success" : "Failed"));
                }
            } else {
                error_log("Skipping commission for transaction ID: $transaction_id - Status: $new_status, Payment confirmed: " . ($payment_confirmed ? "Yes" : "No"));
            }
        } catch (Exception $e) {
            // Log error but continue processing (commission calculation shouldn't block the main transaction)
            error_log("Error calculating referral commission: " . $e->getMessage());
        }

        // Ghi log vào activity_logs khi giao dịch hoàn thành (được duyệt bởi admin)
        try {
            // Lấy thông tin transaction và registration
            $stmt_tx = $pdo->prepare("SELECT th.user_id, th.registration_id, th.transaction_type, th.amount, th.voucher_id
                                      FROM transaction_history th
                                      WHERE th.id = :transaction_id");
            $stmt_tx->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $stmt_tx->execute();
            $tx_info = $stmt_tx->fetch(PDO::FETCH_ASSOC);
            
            if ($tx_info) {
                // Lấy thông tin registration
                $sql_reg_info = "SELECT r.package_id, r.num_account, r.total_price, p.name as package_name, l.province 
                                 FROM registration r 
                                 LEFT JOIN package p ON r.package_id = p.id 
                                 LEFT JOIN location l ON r.location_id = l.id 
                                 WHERE r.id = :registration_id";
                $stmt_reg_info = $pdo->prepare($sql_reg_info);
                $stmt_reg_info->bindParam(':registration_id', $tx_info['registration_id'], PDO::PARAM_INT);
                $stmt_reg_info->execute();
                $reg_info = $stmt_reg_info->fetch(PDO::FETCH_ASSOC);
                
                $is_renewal = ($tx_info['transaction_type'] === 'renewal');
                
                if ($is_renewal) {
                    // Lấy thông tin tài khoản được gia hạn
                    $sql_accounts = "SELECT ra.username, ra.id 
                                    FROM account_groups ag 
                                    JOIN rtk_account ra ON ag.account_id = ra.id 
                                    WHERE ag.registration_id = :registration_id";
                    $stmt_accounts = $pdo->prepare($sql_accounts);
                    $stmt_accounts->bindParam(':registration_id', $tx_info['registration_id'], PDO::PARAM_INT);
                    $stmt_accounts->execute();
                    $accounts = $stmt_accounts->fetchAll(PDO::FETCH_ASSOC);
                    
                    $account_usernames = array_column($accounts, 'username');
                    $notify_content = 'Giao dịch gia hạn được duyệt và hoàn thành cho ' . count($accounts) . ' tài khoản: ' . implode(', ', $account_usernames);
                    
                    $log_data = [
                        'transaction_id' => $transaction_id,
                        'registration_id' => $tx_info['registration_id'],
                        'transaction_type' => 'renewal',
                        'package' => $reg_info['package_name'] ?? '',
                        'province' => $reg_info['province'] ?? '',
                        'amount' => $tx_info['amount'],
                        'renewed_accounts' => $account_usernames,
                        'total_accounts' => count($accounts),
                        'auto_approved' => false,
                        'approved_by' => 'admin'
                    ];
                    $action = 'transaction_renewal_completed';
                } else {
                    // Lấy thông tin tài khoản được tạo
                    $sql_accounts = "SELECT ra.username, ra.id 
                                    FROM rtk_account ra 
                                    WHERE ra.registration_id = :registration_id";
                    $stmt_accounts = $pdo->prepare($sql_accounts);
                    $stmt_accounts->bindParam(':registration_id', $tx_info['registration_id'], PDO::PARAM_INT);
                    $stmt_accounts->execute();
                    $accounts = $stmt_accounts->fetchAll(PDO::FETCH_ASSOC);
                    
                    $account_usernames = array_column($accounts, 'username');
                    $notify_content = 'Giao dịch mua mới được duyệt và hoàn thành, tạo ' . count($accounts) . ' tài khoản: ' . implode(', ', $account_usernames);
                    
                    $log_data = [
                        'transaction_id' => $transaction_id,
                        'registration_id' => $tx_info['registration_id'],
                        'transaction_type' => 'purchase',
                        'package' => $reg_info['package_name'] ?? '',
                        'province' => $reg_info['province'] ?? '',
                        'amount' => $tx_info['amount'],
                        'created_accounts' => $account_usernames,
                        'total_accounts' => count($accounts),
                        'auto_approved' => false,
                        'approved_by' => 'admin'
                    ];
                    $action = 'transaction_purchase_completed';
                }
                
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $new_values = json_encode($log_data, JSON_UNESCAPED_UNICODE);
                
                $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at) 
                            VALUES (:user_id, :action, 'transaction', :entity_id, :ip_address, :user_agent, :new_values, :notify_content, NOW())";
                $stmt_log = $pdo->prepare($sql_log);
                $stmt_log->bindParam(':user_id', $tx_info['user_id'], PDO::PARAM_INT);
                $stmt_log->bindParam(':action', $action, PDO::PARAM_STR);
                $stmt_log->bindParam(':entity_id', $transaction_id, PDO::PARAM_INT);
                $stmt_log->bindParam(':ip_address', $ip_address);
                $stmt_log->bindParam(':user_agent', $user_agent);
                $stmt_log->bindParam(':new_values', $new_values);
                $stmt_log->bindParam(':notify_content', $notify_content);
                $stmt_log->execute();
            }
        } catch (Exception $e) {
            error_log("Error logging transaction completion in update_status: " . $e->getMessage());
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
