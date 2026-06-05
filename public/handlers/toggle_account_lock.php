<?php
/**
 * Handler to lock/unlock RTK account
 */

require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
init_session();

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$account_id || !in_array($action, ['lock', 'unlock'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Verify account belongs to user
    $sql_check = "SELECT sa.id, sa.enabled, sa.username_acc 
                  FROM survey_account sa
                  JOIN registration r ON sa.registration_id = r.id
                  WHERE sa.id = :account_id 
                  AND r.user_id = :user_id
                  AND sa.deleted_at IS NULL";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':account_id', $account_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->execute();
    
    $account = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        echo json_encode(['success' => false, 'error' => 'Tài khoản không tồn tại hoặc không thuộc về bạn']);
        exit;
    }
    
    // Determine new enabled status
    $new_enabled = ($action === 'unlock') ? 1 : 0;
    
    // Update account status
    $sql_update = "UPDATE survey_account 
                   SET enabled = :enabled,
                       updated_at = NOW()
                   WHERE id = :account_id";
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':enabled', $new_enabled, PDO::PARAM_INT);
    $stmt_update->bindParam(':account_id', $account_id, PDO::PARAM_INT);
    $stmt_update->execute();
    
    // TODO: Call RTK API to update account status on RTK server
    // For now, just update database
    
    $action_text = ($action === 'lock') ? 'khóa' : 'mở khóa';
    
    error_log("[TOGGLE_LOCK] User $user_id {$action_text} account {$account['username_acc']} (ID: $account_id)");
    
    echo json_encode([
        'success' => true,
        'message' => "Đã {$action_text} tài khoản {$account['username_acc']} thành công!"
    ]);
    
} catch (Exception $e) {
    error_log("[TOGGLE_LOCK] Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Đã xảy ra lỗi khi xử lý yêu cầu'
    ]);
}
