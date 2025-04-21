<?php
session_start();

require_once __DIR__ . '/../../../private/config/config.php';
require_once __DIR__ . '/../../../private/config/database.php';
require_once __DIR__ . '/../../../private/classes/Database.php';
require_once __DIR__ . '/../../../private/api/rtk_system/account_api.php';

// Define error log path
define('ERROR_LOG_FILE', __DIR__ . '/../../../private/logs/error.log');

// Function to write to error log
function write_error_log($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $user_id = $_SESSION['user_id'] ?? 'No User';
    
    $context_str = '';
    if (!empty($context)) {
        $context_str = ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $log_entry = sprintf(
        "[%s] [%s] [User: %s] %s%s\n",
        $timestamp,
        $ip,
        $user_id,
        $message,
        $context_str
    );
    
    error_log($log_entry, 3, ERROR_LOG_FILE);
}

function createRtkAccountForCompletedPayment($registration_id) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->beginTransaction();

        // Get registration data
        $sql = "SELECT r.*, u.phone, u.username as customer_name, p.price, p.name as package_name 
                FROM registration r 
                JOIN user u ON r.user_id = u.id 
                JOIN package p ON r.package_id = p.id
                WHERE r.id = ? AND r.status = 'pending'";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([$registration_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            write_error_log("Registration not found or already processed", [
                'registration_id' => $registration_id
            ]);
            return false;
        }

        // Check for existing RTK account
        $sql_check = "SELECT COUNT(*) FROM survey_account 
                      WHERE registration_id = ? AND deleted_at IS NULL";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$registration_id]);
        
        if ($stmt_check->fetchColumn() > 0) {
            write_error_log("Duplicate RTK account creation attempt", [
                'registration_id' => $registration_id
            ]);
            return false;
        }

        // Create unique username from customer name
        $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $data['customer_name']);
        if (empty($base_username)) {
            $base_username = 'user' . $data['user_id'];
        }
        $username = $base_username;
        $counter = 1;

        // Check username availability
        $stmt_check_user = $conn->prepare(
            "SELECT COUNT(*) FROM survey_account WHERE username_acc = ? AND deleted_at IS NULL"
        );
        do {
            $stmt_check_user->execute([$username]);
            if ($stmt_check_user->fetchColumn() > 0) {
                $username = $base_username . $counter;
                $counter++;
            } else {
                break;
            }
        } while (true);

        // Generate random password
        $password = bin2hex(random_bytes(8));

        // Prepare RTK API payload
        $accountData = [
            "name" => $username,
            "userPwd" => $password,
            "startTime" => strtotime($data['start_time']) * 1000,
            "endTime" => strtotime($data['end_time']) * 1000,
            "enabled" => 1,
            "numOnline" => $data['num_account'],
            "customerName" => $data['customer_name'],
            "customerPhone" => $data['phone'],
            "customerBizType" => 1,
            "customerCompany" => "",
            "casterIds" => [],
            "regionIds" => [],
            "mountIds" => []
        ];

        // Call RTK API
        $result = createRtkAccount($accountData);
        if (!$result['success']) {
            write_error_log("RTK API account creation failed", [
                'registration_id' => $registration_id,
                'api_error' => $result['error'] ?? 'Unknown error',
                'api_response' => $result['data'] ?? null,
                'request_data' => array_merge($accountData, ['userPwd' => '******'])
            ]);
            return false;
        }

        // Save account to database
        $account_id = 'ACC_REG' . $registration_id . '_' . time();
        $sql_insert = "INSERT INTO survey_account 
                       (id, registration_id, username_acc, password_acc, concurrent_user, 
                        enabled, customerBizType, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([
            $account_id,
            $registration_id,
            $username,
            $password,
            $data['num_account'],
            1,
            1
        ]);

        // Update registration status
        $stmt_update = $conn->prepare(
            "UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = ?"
        );
        $stmt_update->execute([$registration_id]);

        // Update transaction status
        $stmt_trans = $conn->prepare(
            "UPDATE transaction_history 
             SET status = 'completed', updated_at = NOW() 
             WHERE registration_id = ? AND status = 'pending'"
        );
        $stmt_trans->execute([$registration_id]);

        // Log successful activation
        write_error_log("Account creation successful for completed payment", [
            'registration_id' => $registration_id,
            'account_id' => $account_id,
            'username' => $username
        ]);

        // Commit transaction
        $conn->commit();
        return true;

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        write_error_log("Error creating account for completed payment", [
            'registration_id' => $registration_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return false;
    }
}
?>