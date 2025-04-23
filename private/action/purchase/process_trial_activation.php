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
    
    // Format context data
    $context_str = '';
    if (!empty($context)) {
        $context_str = ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    // Format the log entry
    $log_entry = sprintf(
        "[%s] [%s] [User: %s] %s%s\n",
        $timestamp,
        $ip,
        $user_id,
        $message,
        $context_str
    );
    
    // Write to log file
    error_log($log_entry, 3, ERROR_LOG_FILE);
}

// Base URL for redirects
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$base_url .= $_SERVER['HTTP_HOST'];
if (strpos($_SERVER['PHP_SELF'], '/private/') !== false) {
    $base_url .= substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], '/private/'));
}

// Check login
if (!isset($_SESSION['user_id'])) {
    write_error_log("Unauthorized access attempt to trial activation");
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// Get registration_id from POST
$registration_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
if (!$registration_id) {
    write_error_log("Invalid registration ID provided", ['post_data' => $_POST]);
    $_SESSION['error'] = 'Registration ID không hợp lệ';
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();

    // Get registration and check status
    $sql = "SELECT r.*, u.phone, u.username as customer_name, p.price, p.name as package_name 
            FROM registration r 
            JOIN user u ON r.user_id = u.id 
            JOIN package p ON r.package_id = p.id
            WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$registration_id, $_SESSION['user_id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        write_error_log("Registration not found or already processed", [
            'registration_id' => $registration_id,
            'user_id' => $_SESSION['user_id']
        ]);
        throw new Exception("Đăng ký không tồn tại hoặc đã được xử lý");
    }

    // Verify this is a trial package (price = 0)
    if ($data['price'] > 0) {
        write_error_log("Non-trial package activation attempt", [
            'registration_id' => $registration_id,
            'package_name' => $data['package_name'],
            'price' => $data['price']
        ]);
        throw new Exception("Gói này không phải gói dùng thử");
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
        throw new Exception("Đã tồn tại tài khoản RTK cho đăng ký này");
    }

    // Create unique username from customer name
    $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $data['customer_name']);
    if (empty($base_username)) {
        $base_username = 'user' . $_SESSION['user_id'];
    }
    $username = $base_username;
    $counter = 1;

    // Check username availability in local DB
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
        "locationId" => $data['location_id'], // Location ID for API to get mount points
        "enabled" => 1,
        "numOnline" => $data['num_account'],
        "customerName" => $data['customer_name'],
        "customerPhone" => $data['phone'],
        "customerBizType" => 1,
        "customerCompany" => "" // Thay đổi từ [] thành ""
    ];

    // Get mount points for the location
    $locationId = $data['location_id'];
    $mountIds = [];
    
    // Get mount point IDs as integers for the RTK API which requires numeric values
    $stmt_mount = $conn->prepare("SELECT CAST(REGEXP_REPLACE(id, '[^0-9]', '') AS UNSIGNED) as numeric_id FROM mount_point WHERE location_id = ?");
    if (!$stmt_mount->execute([$locationId])) {
        // Fallback if REGEXP_REPLACE is not supported
        $stmt_mount = $conn->prepare("SELECT id FROM mount_point WHERE location_id = ?");
        $stmt_mount->execute([$locationId]);
        
        while ($row = $stmt_mount->fetch(PDO::FETCH_ASSOC)) {
            // Extract numeric part from ID string or generate a placeholder number
            preg_match('/(\d+)/', $row['id'], $matches);
            if (!empty($matches[1])) {
                $mountIds[] = (int)$matches[1]; // Convert to integer
            } else {
                // Use ID hash as numeric value if no number found
                $mountIds[] = abs(crc32($row['id'])) % 1000 + 1000; // Generate a unique number
            }
        }
    } else {
        while ($row = $stmt_mount->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['numeric_id'])) {
                $mountIds[] = (int)$row['numeric_id']; // Make sure it's an integer
            }
        }
    }
    
    // If no mount points found, use default IDs for the location
    if (empty($mountIds)) {
        // Default mount point IDs based on location
        switch ($locationId) {
            case 63: // Yên Bái
                $mountIds = [44, 45, 46, 47, 48, 49, 64];
                break;
            case 24: // Hà Nội
                $mountIds = [1, 2, 3];
                break;
            default:
                $mountIds = [40 + $locationId % 10]; // Generate a reasonable default
        }
    }
    
    // Add mount points to account data
    $accountData['casterIds'] = [];
    $accountData['regionIds'] = [];
    $accountData['mountIds'] = $mountIds;
    
    // Log mount point information
    write_error_log("Mount points for location (numeric)", [
        'location_id' => $locationId,
        'mount_points' => $mountIds
    ]);

    // Call RTK API
    $result = createRtkAccount($accountData);
    if (!$result['success']) {
        write_error_log("RTK API account creation failed", [
            'registration_id' => $registration_id,
            'api_error' => $result['error'] ?? 'Unknown error',
            'api_response' => $result['data'] ?? null,
            'request_data' => array_merge($accountData, ['userPwd' => '******']) // Mask password in logs
        ]);
        throw new Exception("Không thể tạo tài khoản RTK: " . ($result['error'] ?? 'Lỗi không xác định'));
    }

    // Save account to database
    $account_id = 'RTK_' . $registration_id . '_' . time();
    $sql_insert = "INSERT INTO survey_account 
                   (id, registration_id, username_acc, password_acc, concurrent_user, 
                    enabled, customerBizType, created_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([
        $account_id,
        $registration_id,
        $username,
        $password, // Store plain password without hashing
        $data['num_account'],
        1, // enabled
        1  // customerBizType
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
    write_error_log("Trial activation successful", [
        'registration_id' => $registration_id,
        'account_id' => $account_id,
        'username' => $username
    ]);

    // Commit transaction
    $conn->commit();

    // Save account info in session for display
    $_SESSION['rtk_account'] = [
        'username' => $username,
        'password' => $password
    ];

    // Clear pending registration session data
    unset($_SESSION['pending_registration_id']);
    unset($_SESSION['pending_total_price']);
    unset($_SESSION['pending_is_trial']);

    // Redirect to success page
    $_SESSION['success'] = "Kích hoạt thành công! Tài khoản RTK của bạn đã được tạo.";
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?success=1');
    exit;

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    write_error_log("Database error during trial activation", [
        'registration_id' => $registration_id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $_SESSION['error'] = "Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.";
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php?error=1');
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    write_error_log("General error during trial activation", [
        'registration_id' => $registration_id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php?error=1');
    exit;

} finally {
    if (isset($db)) {
        $db->close();
    }
}
?>