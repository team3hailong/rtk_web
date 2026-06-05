<?php
/**
 * Handler để lấy danh sách người dùng RTK đang online
 * Gọi logic từ private/api/rtk_system/online_users_api.php
 * Chỉ trả về thông tin người dùng của tài khoản đang đăng nhập
 */

require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once dirname(dirname(__DIR__)) . '/private/api/rtk_system/online_users_api.php';
require_once dirname(dirname(__DIR__)) . '/private/classes/Database.php';

init_session();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Gọi API RTK để lấy danh sách online users
    $apiResult = getRtkOnlineUsers(1, 100000, '', true);
    
    if (!$apiResult['success']) {
        echo json_encode($apiResult);
        exit;
    }
    
    // Parse dữ liệu từ API
    $parsedData = parseOnlineUsersData($apiResult);
    
    if (!$parsedData['success']) {
        echo json_encode($parsedData);
        exit;
    }
    
    $records = $parsedData['records'];
    
    // Lấy danh sách username_acc của user hiện tại từ database
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Sử dụng prepared statement để tránh SQL injection
    $stmt = $pdo->prepare("
        SELECT sa.username_acc, sa.id as account_id
        FROM survey_account sa
        INNER JOIN registration r ON sa.registration_id = r.id
        WHERE r.user_id = :user_id 
        AND sa.deleted_at IS NULL
        AND sa.enabled = 1
    ");
    $stmt->execute(['user_id' => $user_id]);
    $userAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $db->close();
    
    // Tạo map username_acc => account_id để tra cứu nhanh
    $userAccountMap = [];
    foreach ($userAccounts as $acc) {
        $userAccountMap[$acc['username_acc']] = $acc['account_id'];
    }
    
    // Lọc chỉ lấy các records thuộc về user hiện tại
    $userOnlineRecords = [];
    foreach ($records as $record) {
        $userName = $record['userName'] ?? '';
        
        if (isset($userAccountMap[$userName])) {
            // Thêm account_id vào record
            $record['account_id'] = $userAccountMap[$userName];
            $userOnlineRecords[] = $record;
        }
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($userOnlineRecords),
        'records' => $userOnlineRecords
    ]);
    
} catch (Exception $e) {
    error_log("[GET_ONLINE_USERS] Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
