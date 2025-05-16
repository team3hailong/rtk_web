<?php
/**
 * Referral Class
 * Manages referral system functionality including referral code generation, tracking, and commission
 */
class Referral {
    private $db;
    private $conn;
    private $commission_rate = 0.05; // 5% commission rate

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    /**
     * Generate or get a user's referral code
     * 
     * @param int $userId User ID to generate code for
     * @return array Result with referral code or error message
     */
    public function getUserReferralCode($userId) {
        try {
            // Check if user already has a referral code
            $stmt = $this->conn->prepare("SELECT referral_code FROM referral WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing && !empty($existing['referral_code'])) {
                return [
                    'success' => true, 
                    'code' => $existing['referral_code'],
                    'is_new' => false
                ];
            }
            
            // Generate a new unique referral code
            $referralCode = $this->generateUniqueReferralCode();
            
            // Insert new referral code
            $stmt = $this->conn->prepare("INSERT INTO referral (user_id, referral_code) VALUES (:user_id, :referral_code)");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':referral_code', $referralCode, PDO::PARAM_STR);
            $stmt->execute();
            
            return [
                'success' => true, 
                'code' => $referralCode,
                'is_new' => true
            ];
            
        } catch (PDOException $e) {
            error_log("Error generating referral code: " . $e->getMessage());
            return ['success' => false, 'error' => 'Đã xảy ra lỗi khi tạo mã giới thiệu.'];
        }
    }

    /**
     * Generate a unique referral code
     * 
     * @return string Unique referral code
     */
    private function generateUniqueReferralCode($length = 8) {
        $isUnique = false;
        $referralCode = '';
        
        while (!$isUnique) {
            // Generate random alphanumeric code
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $referralCode = '';
            for ($i = 0; $i < $length; $i++) {
                $referralCode .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Check if code is unique
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM referral WHERE referral_code = :code");
            $stmt->bindParam(':code', $referralCode, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                $isUnique = true;
            }
        }
        
        return $referralCode;
    }

    /**
     * Track a referral when a new user registers using a referral code
     * 
     * @param int $referredUserId The newly registered user ID
     * @param string $referralCode The referral code used during registration
     * @return bool Success/failure of the operation
     */    public function trackReferral($referredUserId, $referralCode) {
        try {
            error_log("Tracking referral: User ID $referredUserId with code '$referralCode'");
            
            // Get the referrer user ID from the code
            $stmt = $this->conn->prepare("SELECT user_id FROM referral WHERE referral_code = :code");
            $stmt->bindParam(':code', $referralCode, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("Referral tracking failed: Invalid referral code '$referralCode'");
                return false; // Invalid referral code
            }
            
            $referrerId = $result['user_id'];
            error_log("Found referrer ID: $referrerId for code '$referralCode'");
            
            // Make sure user isn't referring themselves
            if ($referrerId == $referredUserId) {
                error_log("Referral tracking failed: User $referredUserId tried to refer themselves");
                return false;
            }
            
            // Check if this referred user has already been tracked
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM referred_user WHERE referred_user_id = :referred_id");
            $stmt->bindParam(':referred_id', $referredUserId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                error_log("Referral tracking skipped: User $referredUserId has already been referred");
                return false; // User has already been referred
            }
            
            // Begin transaction to ensure data consistency
            $this->conn->beginTransaction();
              
            try {
                // Record the referral
                $stmt = $this->conn->prepare("INSERT INTO referred_user (referrer_id, referred_user_id) VALUES (:referrer_id, :referred_id)");
                $stmt->bindParam(':referrer_id', $referrerId, PDO::PARAM_INT);
                $stmt->bindParam(':referred_id', $referredUserId, PDO::PARAM_INT);
                
                $result = $stmt->execute();
                
                if ($result) {
                    error_log("Referral tracking successful: User $referredUserId was referred by user $referrerId");
                    
                    // The main activity logging will be handled in process_register.php
                    // This is just a debug log for the referral system itself
                    $this->conn->commit();
                } else {
                    error_log("Referral tracking database insert failed for user $referredUserId");
                    $this->conn->rollBack();
                }
                  return $result;            } catch (PDOException $e) {
                $this->conn->rollBack();
                error_log("Error in referral transaction: " . $e->getMessage());
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error tracking referral: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate and record commission for a completed purchase by a referred user
     * 
     * @param int $transactionId The transaction ID that was completed
     * @return bool Success/failure of the operation
     */    public function calculateCommission($transactionId) {
        try {
            $this->conn->beginTransaction();
              // First get transaction details including status and payment_confirmed
            $stmt = $this->conn->prepare("
                SELECT th.id, th.user_id, th.amount, th.status, th.payment_confirmed 
                FROM transaction_history th
                WHERE th.id = :transaction_id
            ");
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                error_log("Commission calculation failed: Transaction ID $transactionId not found");
                $this->conn->rollBack();
                return false; // Transaction not found
            }              // Check if status is 'completed' and payment confirmed
            if (strtolower($transaction['status']) !== 'completed' || !isset($transaction['payment_confirmed']) || $transaction['payment_confirmed'] != 1) {
                error_log("Commission calculation skipped: Transaction ID $transactionId has status '{$transaction['status']}' or payment not confirmed");
                $this->conn->rollBack();
                return false; // Transaction not completed or payment not confirmed
            }
            
            // Double check that we're not creating a duplicate commission
            $checkStmt = $this->conn->prepare("SELECT id FROM referral_commission WHERE transaction_id = :transaction_id");
            $checkStmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn()) {
                error_log("Commission calculation skipped: Commission already exists for transaction ID $transactionId");
                $this->conn->rollBack();
                return true; // Already exists, return success
            }
              $purchaserId = $transaction['user_id'];
            $transactionAmount = $transaction['amount'];
            
            error_log("Processing commission for user ID: $purchaserId, transaction amount: $transactionAmount");
            
            // Check if the user was referred by someone
            $stmt = $this->conn->prepare("
                SELECT referrer_id FROM referred_user 
                WHERE referred_user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $purchaserId, PDO::PARAM_INT);
            $stmt->execute();
            $referral = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$referral) {
                error_log("Commission calculation skipped: User ID $purchaserId was not referred by anyone");
                $this->conn->rollBack();
                return true; // User not referred, no commission to calculate (still success)
            }
              $referrerId = $referral['referrer_id'];
            $commissionAmount = $transactionAmount * $this->commission_rate;
            
            // Check if commission has already been recorded for this transaction
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM referral_commission 
                WHERE transaction_id = :transaction_id
            ");
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                error_log("Commission already recorded for transaction ID: $transactionId - skipping");
                $this->conn->rollBack();
                return true; // Already recorded, consider it a success
            }
            
            // Log commission calculation for debugging
            error_log("Recording new commission: Transaction ID: $transactionId, Referrer ID: $referrerId, Purchaser ID: $purchaserId, Amount: $transactionAmount, Commission: $commissionAmount");
              // Record the commission with status 'approved' since payment is confirmed
            $stmt = $this->conn->prepare("
                INSERT INTO referral_commission 
                (referrer_id, referred_user_id, transaction_id, commission_amount, status) 
                VALUES (:referrer_id, :referred_id, :transaction_id, :commission_amount, 'approved')
            ");
            $stmt->bindParam(':referrer_id', $referrerId, PDO::PARAM_INT);
            $stmt->bindParam(':referred_id', $purchaserId, PDO::PARAM_INT);
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $stmt->bindParam(':commission_amount', $commissionAmount, PDO::PARAM_STR);
            $stmt->execute();
            
            error_log("Commission automatically approved: Transaction ID: $transactionId, Commission Amount: $commissionAmount VND");
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error calculating commission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a list of referred users for a specific user
     * 
     * @param int $userId User ID to get referrals for
     * @return array List of referred users
     */
    public function getReferredUsers($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT ru.id, ru.created_at as referred_date,
                       u.id as user_id, u.username, u.email
                FROM referred_user ru
                JOIN user u ON ru.referred_user_id = u.id
                WHERE ru.referrer_id = :user_id
                ORDER BY ru.created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching referred users: " . $e->getMessage());
            return [];
        }
    }    /**
     * Get total commission earned for a user
     * 
     * @param int $userId User ID to get commission for
     * @return float Total commission amount
     */
    public function getTotalCommissionEarned($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(commission_amount) as total
                FROM referral_commission
                WHERE referrer_id = :user_id
                AND status IN ('approved', 'paid')
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?: 0;
            
        } catch (PDOException $e) {
            error_log("Error fetching total commission: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total commission that's been paid out for a user
     * 
     * @param int $userId User ID to get paid commission for
     * @return float Total paid commission amount
     */
    public function getTotalCommissionPaid($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total
                FROM withdrawal_request
                WHERE user_id = :user_id AND status = 'completed'
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?: 0;
            
        } catch (PDOException $e) {
            error_log("Error fetching total paid commission: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create a withdrawal request for a user
     * 
     * @param int $userId User ID requesting withdrawal
     * @param float $amount Amount to withdraw
     * @param string $bankName Bank name for payment
     * @param string $accountNumber Bank account number
     * @param string $accountHolder Bank account holder name
     * @return array Result with success status and message
     */
    public function createWithdrawalRequest($userId, $amount, $bankName, $accountNumber, $accountHolder) {
        try {
            // Check if user has sufficient balance
            $totalCommission = $this->getTotalCommissionEarned($userId);
            $totalPaid = $this->getTotalCommissionPaid($userId);
            $pendingWithdrawals = $this->getTotalPendingWithdrawals($userId);
            
            $availableBalance = $totalCommission - $totalPaid - $pendingWithdrawals;
            
            if ($amount > $availableBalance) {
                return [
                    'success' => false,
                    'message' => 'Số dư không đủ để thực hiện yêu cầu rút tiền này.'
                ];
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO withdrawal_request 
                (user_id, amount, bank_name, account_number, account_holder) 
                VALUES (:user_id, :amount, :bank_name, :account_number, :account_holder)
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':bank_name', $bankName, PDO::PARAM_STR);
            $stmt->bindParam(':account_number', $accountNumber, PDO::PARAM_STR);
            $stmt->bindParam(':account_holder', $accountHolder, PDO::PARAM_STR);
            $stmt->execute();

            // --- Ghi log vào activity_logs ---
            $withdrawal_id = $this->conn->lastInsertId();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $new_values = json_encode([
                'amount' => $amount,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_holder' => $accountHolder
            ], JSON_UNESCAPED_UNICODE);
            $notify_content = 'Yêu cầu rút tiền: ' . number_format($amount, 0, ',', '.') . ' VND về ngân hàng ' . $bankName . ' (' . $accountNumber . ')';
            $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at) 
                        VALUES (:user_id, 'withdrawal_request', 'withdrawal_request', :entity_id, :ip_address, :user_agent, :new_values, :notify_content, NOW())";
            $stmt_log = $this->conn->prepare($sql_log);
            $stmt_log->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt_log->bindParam(':entity_id', $withdrawal_id, PDO::PARAM_INT);
            $stmt_log->bindParam(':ip_address', $ip_address);
            $stmt_log->bindParam(':user_agent', $user_agent);
            $stmt_log->bindParam(':new_values', $new_values);
            $stmt_log->bindParam(':notify_content', $notify_content);
            $stmt_log->execute();

            return [
                'success' => true,
                'message' => 'Yêu cầu rút tiền đã được gửi thành công và đang chờ xử lý.'
            ];
            
        } catch (PDOException $e) {
            error_log("Error creating withdrawal request: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi gửi yêu cầu rút tiền.'
            ];
        }
    }

    /**
     * Get total pending withdrawals for a user
     * 
     * @param int $userId User ID to get pending withdrawals for
     * @return float Total pending withdrawal amount
     */
    public function getTotalPendingWithdrawals($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total
                FROM withdrawal_request
                WHERE user_id = :user_id AND status = 'pending'
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?: 0;
            
        } catch (PDOException $e) {
            error_log("Error fetching pending withdrawals: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get withdrawal request history for a user
     * 
     * @param int $userId User ID to get withdrawal history for
     * @return array List of withdrawal requests
     */
    public function getWithdrawalHistory($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, amount, bank_name, account_number, account_holder, 
                       status, notes, created_at, updated_at
                FROM withdrawal_request
                WHERE user_id = :user_id
                ORDER BY created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching withdrawal history: " . $e->getMessage());
            return [];
        }
    }    /**
     * Get detailed commission transactions for a user
     * 
     * @param int $userId User ID to get commission details for
     * @return array List of commission transactions
     */    public function getCommissionTransactions($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COALESCE(rc.id, 0) as commission_id,
                    th.id as transaction_id,
                    th.amount as transaction_amount, 
                    th.status as transaction_status,
                    th.payment_confirmed,
                    th.created_at,
                    u.username as referred_username,
                    u.id as referred_user_id,
                    -- Use actual commission amount from referral_commission if it exists
                    COALESCE(rc.commission_amount, 
                        CASE 
                            WHEN th.status = 'completed' AND th.payment_confirmed = 1 
                            THEN (th.amount * :commission_rate) 
                            ELSE 0 
                        END
                    ) as commission_amount,                    -- Use actual status from referral_commission if it exists
                    -- Otherwise, if transaction is completed and payment confirmed, show as approved
                    CASE 
                        WHEN rc.id IS NOT NULL THEN rc.status
                        WHEN th.status = 'completed' AND th.payment_confirmed = 1 THEN 'approved'
                        ELSE 'pending'
                    END as status
                FROM 
                    referred_user ru
                JOIN 
                    user u ON ru.referred_user_id = u.id
                JOIN 
                    transaction_history th ON th.user_id = u.id
                LEFT JOIN 
                    referral_commission rc ON rc.transaction_id = th.id
                WHERE 
                    ru.referrer_id = :user_id
                ORDER BY 
                    th.created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':commission_rate', $this->commission_rate, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error fetching commission transactions: " . $e->getMessage());
            return [];
        }
    }
}
?>