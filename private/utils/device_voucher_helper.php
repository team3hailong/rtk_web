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
      // Determine order amount from session - ALWAYS get the ORIGINAL price without any discounts
    $orderAmount = 0;
    
    // First try to get the raw base price from payment_data if possible
    if (isset($_SESSION['payment_data']) && isset($_SESSION['payment_data']['base_price_from_registration'])) {
        $orderAmount = $_SESSION['payment_data']['base_price_from_registration'];
        // If we have quantity, multiply by it
        if (isset($_SESSION['payment_data']['quantity'])) {
            $orderAmount *= $_SESSION['payment_data']['quantity'];
        }
        error_log("Using base price from payment_data: " . $orderAmount);
    }
    // Fallback to other session values if needed
    else if (isset($_SESSION[$sessionKey]['original_amount'])) {
        $orderAmount = $_SESSION[$sessionKey]['original_amount']; // Use original amount if available
        error_log("Using original amount: " . $orderAmount);
    }
    else if (isset($_SESSION[$sessionKey]['total_price'])) {
        $orderAmount = $_SESSION[$sessionKey]['total_price'];
        error_log("Using session total price: " . $orderAmount);
    } elseif (isset($_SESSION[$sessionKey]['amount'])) {
        $orderAmount = $_SESSION[$sessionKey]['amount'];
        error_log("Using session amount: " . $orderAmount);
    } elseif (isset($_SESSION['pending_total_price'])) {
        $orderAmount = $_SESSION['pending_total_price'];
        error_log("Using pending total price: " . $orderAmount);
    } else {
        error_log("No price information found for auto voucher");
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
            // Calculate new amount correctly (original - discount)
            $newAmount = $orderAmount - $appliedVoucher['discount_value'];
            if ($newAmount < 0) $newAmount = 0;
            
            error_log("AUTO VOUCHER: Original amount: {$orderAmount}, Discount: {$appliedVoucher['discount_value']}, New amount: {$newAmount}");
            
            // Update session values with the correct amount
            if (isset($_SESSION[$sessionKey]['total_price'])) {
                $_SESSION[$sessionKey]['total_price'] = $newAmount;
            } else if (isset($_SESSION[$sessionKey]['amount'])) {
                $_SESSION[$sessionKey]['amount'] = $newAmount;
            }
            
            // Also update pending amount for consistency
            if (isset($_SESSION['pending_total_price'])) {
                $_SESSION['pending_total_price'] = $newAmount;
            }
            
            // Update the applied voucher's new_amount to match our calculation
            $appliedVoucher['new_amount'] = $newAmount;
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
