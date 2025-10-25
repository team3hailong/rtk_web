<?php
// RTK Account Handler file - Handles AJAX requests for RTK accounts

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
init_session();

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không được phép. Vui lòng đăng nhập.']);
    exit;
}

// --- Include Database and Repository ---
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/RtkAccount.php';
require_once PROJECT_ROOT_PATH . '/private/utils/email_helper.php';  // Add email helper

$db = new Database();
$rtkAccountManager = new RtkAccount($db);
$userId = $_SESSION['user_id']; // Get user ID from session

// Process based on action type - handle both JSON and form-data requests
$action = '';
$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true);

// Try to get action from JSON body first, then fall back to $_POST
if (isset($requestData['action'])) {
    $action = $requestData['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'validate_accounts':
        validateAccountCredentials($rtkAccountManager, $requestData);
        break;
    case 'change_password':
        changeAccountPassword($rtkAccountManager, $requestData);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ', 'debug_action' => $action]);
        break;
}

// Close database connection if needed
if (method_exists($db, 'close')) {
    $db->close();
}

/**
 * Validate account credentials and update ownership
 */
function validateAccountCredentials($rtkAccountManager, $requestData = null) {
    // Get accounts data - handle JSON request body
    $accounts = [];
    
    // Try to get accounts from JSON body first, then fall back to $_POST
    if (isset($requestData['accounts']) && is_array($requestData['accounts'])) {
        $accounts = $requestData['accounts'];
    } elseif (isset($_POST['accounts'])) {
        // If it's already an array, use it directly
        if (is_array($_POST['accounts'])) {
            $accounts = $_POST['accounts'];
        } else {
            // Otherwise try to decode it as JSON string
            $accounts = json_decode($_POST['accounts'], true);
        }
    }
    
    if (empty($accounts) || !is_array($accounts)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không có tài khoản nào được gửi hoặc dữ liệu không hợp lệ']);
        exit;
    }
    
    // Get current user ID from session
    $currentUserId = $_SESSION['user_id'];
    if (!$currentUserId) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không thể xác định người dùng hiện tại']);
        exit;
    }
    
    $results = [];
    $allValid = true;
    $updatedCount = 0;
    
    $dbClass = '\\Database'; // ensure Database class is available
    
    foreach ($accounts as $account) {
        $username = $account['username'] ?? '';
        $password = $account['password'] ?? '';
        
        if (empty($username) || empty($password)) continue;
        
        // Get account details if credentials are valid
        $accountDetails = $rtkAccountManager->getAccountByCredentials($username, $password);
        $isValid = !empty($accountDetails);
        
        // Prepare result item - only add registration_id if account is valid
        $resultsItem = [
            'username'              => $username,
            'valid'                 => $isValid,
            'updated'               => false,
            'requires_confirmation' => false
        ];
        
        // Add debug info when account is not found
        if (!$isValid) {
            $resultsItem['message'] = 'Tên đăng nhập hoặc mật khẩu không đúng';
            error_log("Account not found: username={$username}");
        }
        
        // Only add registration_id if account details exist
        if ($isValid && isset($accountDetails['registration_id'])) {
            $resultsItem['registration_id'] = $accountDetails['registration_id'];
        }
        
        if ($isValid) {
            $registrationId = $accountDetails['registration_id'];
            // Check existing owner
            $db2 = new Database();
            $conn2 = $db2->getConnection();
            $stmtOwner = $conn2->prepare("SELECT user_id FROM registration WHERE id = ?");
            $stmtOwner->execute([$registrationId]);
            $oldOwnerId = $stmtOwner->fetchColumn();
            if (empty($oldOwnerId)) {
                // No existing owner, update immediately
                $updateSuccess = $rtkAccountManager->updateAccountOwnership($registrationId, $currentUserId);
                if ($updateSuccess) {
                    $updatedCount++;
                    $resultsItem['updated'] = true;
                }
            } else {
                // Existing owner: send confirmation OTP
                $otp = generateOTP();
                $_SESSION['transfer_confirmation'][$registrationId] = ['otp' => $otp, 'new_user' => $currentUserId];
                // fetch old owner email
                $userStmt = $conn2->prepare("SELECT email, username FROM user WHERE id = ?");
                $userStmt->execute([$oldOwnerId]);
                $ownerData = $userStmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($ownerData)) {
                    // send OTP email
                    sendSurveyAccountLinkNotification($ownerData['email'], $ownerData['username'], $username . ' - OTP: ' . $otp);
                }
                $resultsItem['requires_confirmation'] = true;
                $resultsItem['message'] = 'Yêu cầu chuyển sở hữu đã được gửi tới chủ tài khoản để xác nhận.';
            }
            $db2->close();
        }
        $results[] = $resultsItem;
        if (!$isValid) $allValid = false;
    }
    
    // Count accounts requiring confirmation
    $confirmationCount = count(array_filter($results, function($r) {
        return $r['requires_confirmation'] === true;
    }));
    
    // Count invalid accounts
    $invalidCount = count(array_filter($results, function($r) {
        return $r['valid'] === false;
    }));
    
    // Generate appropriate message
    $message = '';
    if ($allValid) {
        if ($updatedCount > 0 && $confirmationCount > 0) {
            $message = "Đã cập nhật {$updatedCount} tài khoản. {$confirmationCount} tài khoản cần xác nhận từ chủ sở hữu cũ.";
        } elseif ($updatedCount > 0) {
            $message = "Đã cập nhật quyền sở hữu cho {$updatedCount} tài khoản";
        } elseif ($confirmationCount > 0) {
            $message = "Yêu cầu xác nhận đã được gửi cho {$confirmationCount} tài khoản";
        } else {
            $message = "Không có tài khoản nào được cập nhật";
        }
    } else {
        $message = "Có {$invalidCount} tài khoản với thông tin đăng nhập không đúng. ";
        if ($updatedCount > 0) {
            $message .= "Đã cập nhật {$updatedCount} tài khoản hợp lệ.";
        }
        if ($confirmationCount > 0) {
            $message .= " {$confirmationCount} tài khoản cần xác nhận.";
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'all_valid' => $allValid,
        'results' => $results,
        'updated_count' => $updatedCount,
        'confirmation_count' => $confirmationCount,
        'invalid_count' => $invalidCount,
        'message' => $message
    ]);
    exit;
}

/**
 * Change account password
 */
function changeAccountPassword($rtkAccountManager, $requestData = null) {
    // Try to get data from JSON body first, then fall back to $_POST
    $accountId = '';
    $newPassword = '';
    
    if (isset($requestData['account_id'])) {
        $accountId = $requestData['account_id'];
    } elseif (isset($_POST['account_id'])) {
        $accountId = $_POST['account_id'];
    }
    
    if (isset($requestData['new_password'])) {
        $newPassword = $requestData['new_password'];
    } elseif (isset($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
    }
    
    if (empty($accountId) || empty($newPassword)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin tài khoản hoặc mật khẩu mới']);
        exit;
    }
    
    // Validate password strength (optional)
    if (strlen($newPassword) < 6) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
        exit;
    }
    
    // Update password in database AND RTK system
    $success = $rtkAccountManager->updatePassword($accountId, $newPassword);
    
    $message = '';
    if ($success) {
        $message = 'Đổi mật khẩu thành công!';
    } else {
        $message = 'Không thể đổi mật khẩu. Vui lòng kiểm tra log để biết chi tiết.';
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}
?>
