<?php
/**
 * Complete Order Without Proof
 * Xử lý hoàn tất đơn hàng không cần upload minh chứng
 * Chỉ áp dụng cho voucher có need_upload_proof = 0
 */

// Project root path
$project_root_path = dirname(dirname(dirname(__DIR__)));

// Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');

// Include required files
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Voucher.php';
require_once $project_root_path . '/private/classes/purchase/AutoAccountCreator.php';
require_once $project_root_path . '/private/utils/functions.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=invalid_request');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

if (!isset($_POST['registration_id'])) {
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=missing_data');
    exit;
}

$registration_id = (int)$_POST['registration_id'];
$user_id = $_SESSION['user_id'];
$is_renewal = isset($_SESSION['is_renewal']) && $_SESSION['is_renewal'];
$sessionKey = $is_renewal ? 'renewal' : 'order';

error_log("[COMPLETE_WITHOUT_PROOF] Starting process for registration_id: $registration_id, user_id: $user_id");

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // Verify registration belongs to user
    $sql_verify = "SELECT id, num_account, total_price, package_id, location_id, status 
                   FROM registration 
                   WHERE id = :registration_id AND user_id = :user_id";
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_verify->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_verify->execute();
    $registration = $stmt_verify->fetch(PDO::FETCH_ASSOC);

    if (!$registration) {
        throw new Exception('Registration not found or access denied.');
    }

    if ($registration['status'] !== 'pending') {
        throw new Exception('Registration is not in pending state.');
    }

    // Get voucher info if applied
    $voucher_id = $_SESSION[$sessionKey]['voucher_id'] ?? null;
    $voucher_code = null; // Initialize voucher code
    $need_upload_proof = true;
    $auto_approve = false;

    error_log("[COMPLETE_WITHOUT_PROOF] Voucher ID: " . ($voucher_id ?? 'NULL'));

    if ($voucher_id) {
        $sql_voucher = "SELECT code, need_upload_proof, auto_approve FROM voucher WHERE id = :voucher_id AND is_active = 1";
        $stmt_voucher = $conn->prepare($sql_voucher);
        $stmt_voucher->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $stmt_voucher->execute();
        $voucher = $stmt_voucher->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            throw new Exception('Voucher not found or inactive.');
        }

        $voucher_code = $voucher['code']; // Assign voucher code
        $need_upload_proof = (bool)$voucher['need_upload_proof'];
        $auto_approve = (bool)$voucher['auto_approve'];
        
        error_log("[COMPLETE_WITHOUT_PROOF] Voucher: code=$voucher_code, need_upload_proof=$need_upload_proof, auto_approve=$auto_approve");

        // Security check: Chỉ cho phép nếu voucher không cần upload proof
        if ($need_upload_proof) {
            throw new Exception('This voucher requires proof upload.');
        }
    } else {
        // Nếu không có voucher, mặc định cần upload proof
        throw new Exception('No voucher applied. Proof upload is required.');
    }

    // Tính toán số tiền sau voucher
    $total_price = (float)$registration['total_price'];
    $discount_amount = $_SESSION[$sessionKey]['voucher_discount'] ?? 0;
    $final_amount = max(0, $total_price - $discount_amount);

    error_log("[COMPLETE_WITHOUT_PROOF] Amount calculation: total=$total_price, discount=$discount_amount, final=$final_amount");

    // Check if transaction already exists
    $sql_check = "SELECT id FROM transaction_history 
                  WHERE registration_id = :registration_id AND user_id = :user_id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $existing_transaction = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($existing_transaction) {
        throw new Exception('Transaction already exists for this registration.');
    }

    // Xác định transaction_type
    $transaction_type = $sessionKey === 'renewal' ? 'renewal' : 'purchase';
    $payment_method = 'Chuyển khoản ngân hàng';
    
    // Tạo transaction record (chỉ các cột có trong bảng transaction_history)
    $sql_transaction = "INSERT INTO transaction_history 
                       (user_id, registration_id, voucher_id, transaction_type, 
                        amount, status, payment_method, payment_image, 
                        payment_confirmed, payment_confirmed_at, 
                        created_at, updated_at) 
                       VALUES 
                       (:user_id, :registration_id, :voucher_id, :transaction_type, 
                        :amount, :status, :payment_method, NULL, 
                        :payment_confirmed, :payment_confirmed_at, 
                        NOW(), NOW())";
    
    $stmt_transaction = $conn->prepare($sql_transaction);
    $stmt_transaction->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_transaction->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_transaction->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
    $stmt_transaction->bindParam(':transaction_type', $transaction_type, PDO::PARAM_STR);
    $stmt_transaction->bindParam(':amount', $final_amount, PDO::PARAM_STR);
    $stmt_transaction->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);

    // YÊU CẦU MỚI: Auto-approve chỉ khi auto_approve = 1 VÀ final_amount = 0
    $should_auto_approve = ($auto_approve && $final_amount == 0);
    
    if ($should_auto_approve) {
        $status = 'completed';
        $payment_confirmed = 1;
        $payment_confirmed_at = date('Y-m-d H:i:s');
        error_log("[COMPLETE_WITHOUT_PROOF] Auto-approve enabled AND final_amount=0, status=completed");
    } else {
        $status = 'pending';
        $payment_confirmed = 0;
        $payment_confirmed_at = null;
        
        if ($auto_approve && $final_amount > 0) {
            error_log("[COMPLETE_WITHOUT_PROOF] Auto-approve enabled but final_amount=$final_amount > 0, status=pending");
        } else {
            error_log("[COMPLETE_WITHOUT_PROOF] Auto-approve disabled or conditions not met, status=pending");
        }
    }

    $stmt_transaction->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt_transaction->bindParam(':payment_confirmed', $payment_confirmed, PDO::PARAM_INT);
    $stmt_transaction->bindParam(':payment_confirmed_at', $payment_confirmed_at, PDO::PARAM_STR);
    $stmt_transaction->execute();

    $transaction_id = $conn->lastInsertId();
    error_log("[COMPLETE_WITHOUT_PROOF] Transaction created with ID: $transaction_id, status: $status");

    // Nếu should_auto_approve = true, tự động tạo tài khoản
    if ($should_auto_approve) {
        error_log("[COMPLETE_WITHOUT_PROOF] Starting auto account creation for registration $registration_id");
        
        // Commit transaction trước để đảm bảo dữ liệu đã được lưu
        $conn->commit();
        error_log("[COMPLETE_WITHOUT_PROOF] Database transaction committed");
        
        // Tạo tài khoản tự động
        $accountCreator = new AutoAccountCreator();
        error_log("[COMPLETE_WITHOUT_PROOF] AutoAccountCreator instantiated");
        
        $result = $accountCreator->createAccountsForRegistration($registration_id);
        error_log("[COMPLETE_WITHOUT_PROOF] createAccountsForRegistration result: " . json_encode($result));
        
        if (!$result['success']) {
            error_log("[AUTO_ACCOUNT] Failed to create accounts for registration $registration_id: " . $result['error']);
            // Không throw exception, vẫn cho hoàn tất đơn hàng
            // Admin có thể tạo tài khoản thủ công sau
        } else {
            error_log("[AUTO_ACCOUNT] Successfully created " . count($result['accounts']) . " accounts for registration $registration_id");
        }

        // Ghi log vào activity_logs khi giao dịch hoàn thành
        try {
            // Lấy thông tin registration
            $sql_reg_info = "SELECT r.package_id, r.num_account, r.total_price, p.name as package_name, l.province 
                             FROM registration r 
                             LEFT JOIN package p ON r.package_id = p.id 
                             LEFT JOIN location l ON r.location_id = l.id 
                             WHERE r.id = :registration_id";
            $stmt_reg_info = $conn->prepare($sql_reg_info);
            $stmt_reg_info->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt_reg_info->execute();
            $reg_info = $stmt_reg_info->fetch(PDO::FETCH_ASSOC);
            
            if ($is_renewal) {
                // Lấy thông tin tài khoản được gia hạn
                $sql_accounts = "SELECT ra.username, ra.id 
                                FROM account_groups ag 
                                JOIN rtk_account ra ON ag.account_id = ra.id 
                                WHERE ag.registration_id = :registration_id";
                $stmt_accounts = $conn->prepare($sql_accounts);
                $stmt_accounts->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
                $stmt_accounts->execute();
                $accounts = $stmt_accounts->fetchAll(PDO::FETCH_ASSOC);
                
                $account_usernames = array_column($accounts, 'username');
                $notify_content = 'Giao dịch gia hạn hoàn thành cho ' . count($accounts) . ' tài khoản: ' . implode(', ', $account_usernames);
                
                $log_data = [
                    'transaction_id' => $transaction_id,
                    'registration_id' => $registration_id,
                    'transaction_type' => 'renewal',
                    'package' => $reg_info['package_name'] ?? '',
                    'province' => $reg_info['province'] ?? '',
                    'amount' => $final_amount,
                    'renewed_accounts' => $account_usernames,
                    'total_accounts' => count($accounts),
                    'auto_approved' => true,
                    'voucher_code' => $voucher_code ?? null
                ];
                $action = 'transaction_renewal_completed';
            } else {
                // Lấy thông tin tài khoản được tạo
                $created_accounts = $result['success'] ? $result['accounts'] : [];
                $account_usernames = array_column($created_accounts, 'username');
                
                $notify_content = 'Giao dịch mua mới hoàn thành, tạo ' . count($created_accounts) . ' tài khoản: ' . implode(', ', $account_usernames);
                
                $log_data = [
                    'transaction_id' => $transaction_id,
                    'registration_id' => $registration_id,
                    'transaction_type' => 'purchase',
                    'package' => $reg_info['package_name'] ?? '',
                    'province' => $reg_info['province'] ?? '',
                    'amount' => $final_amount,
                    'created_accounts' => $account_usernames,
                    'total_accounts' => count($created_accounts),
                    'auto_approved' => true,
                    'voucher_code' => $voucher_code ?? null
                ];
                $action = 'transaction_purchase_completed';
            }
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $new_values = json_encode($log_data, JSON_UNESCAPED_UNICODE);
            
            $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at) 
                        VALUES (:user_id, :action, 'transaction', :entity_id, :ip_address, :user_agent, :new_values, :notify_content, NOW())";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_log->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt_log->bindParam(':entity_id', $transaction_id, PDO::PARAM_INT);
            $stmt_log->bindParam(':ip_address', $ip_address);
            $stmt_log->bindParam(':user_agent', $user_agent);
            $stmt_log->bindParam(':new_values', $new_values);
            $stmt_log->bindParam(':notify_content', $notify_content);
            $stmt_log->execute();
        } catch (Exception $e) {
            error_log("Error logging transaction completion: " . $e->getMessage());
        }
        
        // Đánh dấu đã commit để không commit lại ở cuối
        $transaction_committed = true;
    }

    // Mark device voucher as used if present
    if (isset($_SESSION['device_fingerprint']) && $voucher_code) {
        try {
            $voucherService = new Voucher($db);
            $voucherService->markDeviceVoucherUsed($_SESSION['device_fingerprint'], $voucher_code);
        } catch (Exception $e) {
            error_log("Error marking device voucher as used: " . $e->getMessage());
        }
    }

    // Commit transaction nếu chưa commit (trường hợp không auto_approve)
    if (!isset($transaction_committed) || !$transaction_committed) {
        $conn->commit();
    }

    // Clear session data
    unset($_SESSION['pending_registration_id']);
    unset($_SESSION['pending_total_price']);
    unset($_SESSION['pending_is_trial']);
    unset($_SESSION[$sessionKey]);
    unset($_SESSION['payment_data']);

    // Redirect to success page
    if ($auto_approve) {
        header('Location: ' . $base_url . '/public/pages/purchase/success.php?transaction_id=' . $transaction_id . '&auto_approved=1');
    } else {
        header('Location: ' . $base_url . '/public/pages/purchase/success.php?transaction_id=' . $transaction_id);
    }
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error completing order without proof: " . $e->getMessage());
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php?error=' . urlencode($e->getMessage()));
    exit;
}
