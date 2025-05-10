<?php

/**
 * Voucher Class
 * Handles all voucher-related operations
 */
class Voucher {
    private $db;
    
    /**
     * Constructor
     * @param Database $db An instance of the Database class.
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }
      /**
     * Check if a voucher is valid and can be applied
     * 
     * @param string $code The voucher code to validate
     * @param float $orderValue The current order value
     * @param int $userId The user ID trying to use the voucher
     * @return array Result with status and message/voucher data
     */
    public function validateVoucher($code, $orderValue, $userId = null) {
        // Validate parameters
        if (empty($code)) {
            return ['status' => false, 'message' => 'Mã voucher không được để trống'];
        }
        
        // Get voucher from database
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM voucher WHERE code = :code AND is_active = 1 AND NOW() BETWEEN start_date AND end_date";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if voucher exists
        if (!$voucher) {
            return ['status' => false, 'message' => 'Mã voucher không hợp lệ hoặc đã hết hạn'];
        }
        
        // Check if voucher has been used up (tổng số lượng)
        if ($voucher['quantity'] !== null && $voucher['used_quantity'] >= $voucher['quantity']) {
            return ['status' => false, 'message' => 'Mã voucher đã hết lượt sử dụng'];
        }
        
        // Check user limit usage if userId provided
        if ($userId && $voucher['limit_usage'] !== null) {
            $sql = "SELECT COUNT(*) FROM user_voucher_usage WHERE user_id = :user_id AND voucher_id = :voucher_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':voucher_id', $voucher['id'], PDO::PARAM_INT);
            $stmt->execute();
            $userUsageCount = (int)$stmt->fetchColumn();
            
            if ($userUsageCount >= $voucher['limit_usage']) {
                return ['status' => false, 'message' => 'Bạn đã sử dụng hết số lần áp dụng mã giảm giá này'];
            }
        }
        
        // Check minimum order value
        if ($voucher['min_order_value'] !== null && $orderValue < $voucher['min_order_value']) {
            return [
                'status' => false, 
                'message' => 'Giá trị đơn hàng tối thiểu phải từ ' . number_format($voucher['min_order_value'], 0, ',', '.') . ' VNĐ'
            ];
        }
        
        return ['status' => true, 'data' => $voucher];
    }
    
    /**
     * Apply voucher to an order and calculate new amount
     * 
     * @param array $voucher The voucher data
     * @param float $amount The original amount
     * @return array Result with new amount and discount value
     */
    public function applyVoucher($voucher, $amount) {
        $newAmount = $amount;
        $discountValue = 0;
        $additionalMonths = 0;
        
        // Apply discount based on voucher type
        switch ($voucher['voucher_type']) {
            case 'percentage_discount':
                // Calculate discount
                $discountAmount = $amount * ($voucher['discount_value'] / 100);
                
                // Apply max discount if set
                if ($voucher['max_discount'] !== null && $discountAmount > $voucher['max_discount']) {
                    $discountAmount = $voucher['max_discount'];
                }
                
                $newAmount = $amount - $discountAmount;
                $discountValue = $discountAmount;
                break;
                
            case 'fixed_discount':
                $discountAmount = $voucher['discount_value'];
                $newAmount = $amount - $discountAmount;
                
                // Ensure amount doesn't go below zero
                if ($newAmount < 0) {
                    $newAmount = 0;
                }
                
                $discountValue = $discountAmount;
                break;
                
            case 'extend_duration':
                // For extend_duration, amount stays the same but we record the extra months
                $additionalMonths = $voucher['discount_value'];
                break;
        }
        
        return [
            'new_amount' => $newAmount,
            'discount_value' => $discountValue,
            'additional_months' => $additionalMonths,
            'voucher_type' => $voucher['voucher_type']
        ];
    }
    
    /**
     * Update the usage count of a voucher
     * 
     * @param int $voucherId The ID of the voucher
     * @return bool Success/failure of the operation
     */
    public function incrementUsage($voucherId) {
        $pdo = $this->db->getConnection();
        $sql = "UPDATE voucher SET used_quantity = used_quantity + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $voucherId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Get a voucher by ID
     * 
     * @param int $id The voucher ID
     * @return array|false The voucher data or false if not found
     */
    public function getVoucherById($id) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM voucher WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get voucher details by code
     * 
     * @param string $code The voucher code
     * @return array|false The voucher data or false if not found
     */
    public function getVoucherByCode($code) {
        if (empty($code)) {
            return false;
        }
        
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM voucher WHERE code = :code";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Record user's usage of a voucher
     * 
     * @param int $userId ID of the user
     * @param int $voucherId ID of the voucher used
     * @param int|null $transactionId ID of the transaction (if available)
     * @return bool Success/failure of the operation
     */
    public function recordUserVoucherUsage($userId, $voucherId, $transactionId = null) {
        try {
            $pdo = $this->db->getConnection();
            
            $sql = "INSERT INTO user_voucher_usage (user_id, voucher_id, transaction_id, used_at) 
                    VALUES (:user_id, :voucher_id, :transaction_id, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':voucher_id', $voucherId, PDO::PARAM_INT);
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error recording voucher usage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove user's voucher usage record 
     * 
     * @param int $userId ID of the user
     * @param int $voucherId ID of the voucher
     * @param int|null $transactionId Optional transaction ID to make removal more specific
     * @return bool Success/failure of the operation
     */
    public function removeUserVoucherUsage($userId, $voucherId, $transactionId = null) {
        try {
            $pdo = $this->db->getConnection();
            
            if ($transactionId !== null) {
                $sql = "DELETE FROM user_voucher_usage 
                        WHERE user_id = :user_id AND voucher_id = :voucher_id AND transaction_id = :transaction_id
                        LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            } else {
                $sql = "DELETE FROM user_voucher_usage 
                        WHERE user_id = :user_id AND voucher_id = :voucher_id
                        ORDER BY used_at DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
            }
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':voucher_id', $voucherId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error removing voucher usage: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Decrease the usage count of a voucher
     * 
     * @param int $voucherId The ID of the voucher
     * @return bool Success/failure of the operation
     */
    public function decrementUsage($voucherId) {
        try {
            $pdo = $this->db->getConnection();
            $sql = "UPDATE voucher SET used_quantity = GREATEST(used_quantity - 1, 0) WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $voucherId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error decrementing voucher usage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset voucher session data
     * 
     * @param string $sessionKey The session key to reset ('order' or 'renewal')
     * @return void
     */
    public function resetVoucherSession($sessionKey = 'order') {
        if (isset($_SESSION[$sessionKey])) {
            // Remove voucher-specific data but keep other order data
            if (isset($_SESSION[$sessionKey]['voucher_id'])) {
                unset($_SESSION[$sessionKey]['voucher_id']);
            }
            if (isset($_SESSION[$sessionKey]['voucher_code'])) {
                unset($_SESSION[$sessionKey]['voucher_code']);
            }
            if (isset($_SESSION[$sessionKey]['voucher_discount'])) {
                unset($_SESSION[$sessionKey]['voucher_discount']);
            }
            if (isset($_SESSION[$sessionKey]['original_amount'])) {
                // Restore original amount if it was saved
                if (isset($_SESSION[$sessionKey]['total_price'])) {
                    $_SESSION[$sessionKey]['total_price'] = $_SESSION[$sessionKey]['original_amount'];
                } else if (isset($_SESSION[$sessionKey]['amount'])) {
                    $_SESSION[$sessionKey]['amount'] = $_SESSION[$sessionKey]['original_amount'];
                }
                unset($_SESSION[$sessionKey]['original_amount']);
            }
        }
    }
}
