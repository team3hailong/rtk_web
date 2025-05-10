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
     * @return array Result with status and message/voucher data
     */
    public function validateVoucher($code, $orderValue) {
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
        
        // Check if voucher has been used up
        if ($voucher['quantity'] !== null && $voucher['used_quantity'] >= $voucher['quantity']) {
            return ['status' => false, 'message' => 'Mã voucher đã hết lượt sử dụng'];
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
}
