<?php
/**
 * Auto Apply Device Voucher Helper
 * This function checks for a device-specific voucher and applies it automatically
 * if this is the first purchase
 */
function checkAndApplyDeviceVoucher($deviceFingerprint, $sessionKey = 'order') {
    if (empty($deviceFingerprint)) {
        return false;
    }

    // Already has a voucher applied
    if (isset($_SESSION[$sessionKey]['voucher_code'])) {
        return false;
    }
    
    // Determine order amount from session
    $orderAmount = 0;
    if (isset($_SESSION[$sessionKey]['total_price'])) {
        $orderAmount = $_SESSION[$sessionKey]['total_price'];
    } elseif (isset($_SESSION[$sessionKey]['amount'])) {
        $orderAmount = $_SESSION[$sessionKey]['amount'];
    } elseif (isset($_SESSION['pending_total_price'])) {
        $orderAmount = $_SESSION['pending_total_price'];
    } else {
        return false; // No price information
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return false;
    }

    try {
        require_once dirname(__DIR__) . '/classes/Database.php';
        require_once dirname(__DIR__) . '/classes/Voucher.php';
        
        $db = new Database();
        $voucherService = new Voucher($db);
        
        // Check if device has a voucher
        $voucherResult = $voucherService->checkDeviceVoucher($deviceFingerprint, $orderAmount, $userId);
        
        if (empty($voucherResult)) {
            return false;
        }
        
        $voucherCode = $voucherResult['code'];
        $voucherData = $voucherResult['data'];
        
        // If we have a valid voucher, simulate the apply_voucher.php functionality
        // Store original amount for potential reset
        $_SESSION[$sessionKey]['original_amount'] = $orderAmount;
        $_SESSION[$sessionKey]['voucher_id'] = $voucherData['id'];
        $_SESSION[$sessionKey]['voucher_code'] = $voucherCode;
        
        // Apply discount calculation based on voucher type
        $appliedVoucher = $voucherService->applyVoucher($voucherData, $orderAmount);
        
        // Store the discount amount
        $_SESSION[$sessionKey]['voucher_discount'] = $appliedVoucher['discount_value'];
        
        // Update the totals based on voucher type
        if ($voucherData['voucher_type'] !== 'extend_duration') {
            if (isset($_SESSION[$sessionKey]['total_price'])) {
                $_SESSION[$sessionKey]['total_price'] = $appliedVoucher['new_amount'];
            } else if (isset($_SESSION[$sessionKey]['amount'])) {
                $_SESSION[$sessionKey]['amount'] = $appliedVoucher['new_amount'];
            }
            
            // Also update pending amount for consistency
            if (isset($_SESSION['pending_total_price'])) {
                $_SESSION['pending_total_price'] = $appliedVoucher['new_amount'];
            }
        }
        
        // If there's additional months from the voucher
        if ($appliedVoucher['additional_months'] > 0) {
            $_SESSION[$sessionKey]['additional_months'] = $appliedVoucher['additional_months'];
        }
        
        return [
            'voucher_code' => $voucherCode,
            'discount_value' => $appliedVoucher['discount_value'],
            'new_amount' => $appliedVoucher['new_amount'],
            'additional_months' => $appliedVoucher['additional_months']
        ];
        
    } catch (Exception $e) {
        error_log("Error auto-applying device voucher: " . $e->getMessage());
        return false;
    }
}
