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

$db = new Database();
$rtkAccountManager = new RtkAccount($db);
$userId = $_SESSION['user_id']; // Get user ID from session

// Process based on action type
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'validate_accounts':
        validateAccountCredentials($rtkAccountManager);
        break;
    case 'change_password':
        changeAccountPassword($rtkAccountManager);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}

// Close database connection if needed
if (method_exists($db, 'close')) {
    $db->close();
}

/**
 * Validate account credentials and update ownership
 */
function validateAccountCredentials($rtkAccountManager) {
    // Get accounts data from POST
    $accountsJson = $_POST['accounts'] ?? '';
    $accounts = [];
    
    // Decode the JSON string to PHP array
    if (!empty($accountsJson)) {
        $accounts = json_decode($accountsJson, true);
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
    
    foreach ($accounts as $account) {
        $username = $account['username'] ?? '';
        $password = $account['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            continue; // Skip empty entries
        }
        
        // Get account details if credentials are valid
        $accountDetails = $rtkAccountManager->getAccountByCredentials($username, $password);
        $isValid = !empty($accountDetails);
        
        if ($isValid) {
            // Update the account ownership
            $updateSuccess = $rtkAccountManager->updateAccountOwnership($accountDetails['registration_id'], $currentUserId);
            if ($updateSuccess) {
                $updatedCount++;
            }
        }
        
        $results[] = [
            'username' => $username,
            'valid' => $isValid,
            'updated' => $isValid ? ($updateSuccess ?? false) : false
        ];
        
        if (!$isValid) {
            $allValid = false;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'all_valid' => $allValid,
        'results' => $results,
        'updated_count' => $updatedCount,
        'message' => $allValid ? "Đã cập nhật quyền sở hữu cho {$updatedCount} tài khoản" : 'Sai tên đăng nhập hoặc mật khẩu cho một số tài khoản'
    ]);
    exit;
}

/**
 * Change account password
 */
function changeAccountPassword($rtkAccountManager) {
    $accountId = $_POST['account_id'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($accountId) || empty($newPassword)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin tài khoản hoặc mật khẩu mới']);
        exit;
    }
    
    // Update password in database
    $success = $rtkAccountManager->updatePassword($accountId, $newPassword);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Đổi mật khẩu thành công' : 'Không thể đổi mật khẩu'
    ]);
    exit;
}
?>
