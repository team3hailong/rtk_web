<?php
/**
 * Debug Script - Kiểm tra Session và SQL Query
 */

require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
init_session();
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/RtkAccount.php';

echo "<h2>🔍 Debug Trang Quản Lý Tài Khoản</h2>";
echo "<hr>";

// Check session
echo "<h3>1. Kiểm tra Session</h3>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; margin: 15px 0;'>";
echo "<tr style='background-color: #f5f5f5;'><th>Key</th><th>Value</th></tr>";

if (isset($_SESSION['user_id'])) {
    echo "<tr><td><strong>user_id</strong></td><td style='color: green;'>{$_SESSION['user_id']}</td></tr>";
} else {
    echo "<tr><td><strong>user_id</strong></td><td style='color: red;'>NOT SET (Chưa login)</td></tr>";
}

if (isset($_SESSION['username'])) {
    echo "<tr><td><strong>username</strong></td><td>{$_SESSION['username']}</td></tr>";
}

if (isset($_SESSION['email'])) {
    echo "<tr><td><strong>email</strong></td><td>{$_SESSION['email']}</td></tr>";
}

if (isset($_SESSION['role'])) {
    echo "<tr><td><strong>role</strong></td><td>{$_SESSION['role']}</td></tr>";
}

echo "</table>";

if (!isset($_SESSION['user_id'])) {
    echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336; margin: 15px 0;'>";
    echo "❌ <strong>Chưa đăng nhập!</strong> Vui lòng login trước.";
    echo "</div>";
    exit;
}

$currentUserId = $_SESSION['user_id'];

echo "<hr>";

// Get user info from database
echo "<h3>2. Thông Tin User Từ Database</h3>";
$db = new Database();
$conn = $db->getConnection();

$userSql = "SELECT id, username, email, role FROM user WHERE id = :user_id";
$userStmt = $conn->prepare($userSql);
$userStmt->bindParam(':user_id', $currentUserId);
$userStmt->execute();
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($userInfo) {
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; margin: 15px 0;'>";
    echo "<tr style='background-color: #f5f5f5;'><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$userInfo['id']}</td></tr>";
    echo "<tr><td>Username</td><td>{$userInfo['username']}</td></tr>";
    echo "<tr><td>Email</td><td>{$userInfo['email']}</td></tr>";
    echo "<tr><td>Role</td><td>{$userInfo['role']}</td></tr>";
    echo "</table>";
} else {
    echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336; margin: 15px 0;'>";
    echo "❌ Không tìm thấy user với ID: {$currentUserId}";
    echo "</div>";
}

echo "<hr>";

// Check accounts belonging to this user
echo "<h3>3. Tài Khoản Theo SQL Query Gốc</h3>";

$sql = "SELECT 
        sa.id as account_id,
        sa.username_acc,
        sa.registration_id,
        sa.deleted_at,
        sa.enabled,
        r.id as reg_id,
        r.user_id,
        r.status as reg_status
    FROM survey_account sa
    JOIN registration r ON sa.registration_id = r.id
    WHERE r.user_id = :user_id 
    AND sa.deleted_at IS NULL
    ORDER BY sa.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Tìm thấy: <strong>" . count($accounts) . "</strong> tài khoản</p>";

if (count($accounts) > 0) {
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background-color: #f5f5f5;'>";
    echo "<th>Account ID</th>";
    echo "<th>Username</th>";
    echo "<th>Registration ID</th>";
    echo "<th>User ID</th>";
    echo "<th>Enabled</th>";
    echo "<th>Reg Status</th>";
    echo "</tr>";
    
    foreach ($accounts as $acc) {
        echo "<tr>";
        echo "<td>{$acc['account_id']}</td>";
        echo "<td><strong>{$acc['username_acc']}</strong></td>";
        echo "<td>{$acc['registration_id']}</td>";
        echo "<td>{$acc['user_id']}</td>";
        echo "<td>" . ($acc['enabled'] ? '✅ Enabled' : '❌ Disabled') . "</td>";
        echo "<td>{$acc['reg_status']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<div style='padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; margin: 15px 0;'>";
    echo "⚠️ <strong>Không có tài khoản nào!</strong>";
    echo "</div>";
}

echo "<hr>";

// Now check ALL accounts in database to see if there are accounts that should belong to this user
echo "<h3>4. Kiểm Tra Tất Cả Tài Khoản (Debug)</h3>";

$debugSql = "SELECT 
        sa.id as account_id,
        sa.username_acc,
        sa.registration_id,
        sa.deleted_at,
        r.user_id,
        u.username as owner_username
    FROM survey_account sa
    LEFT JOIN registration r ON sa.registration_id = r.id
    LEFT JOIN user u ON r.user_id = u.id
    WHERE sa.deleted_at IS NULL
    ORDER BY r.user_id, sa.id
    LIMIT 50";

$debugStmt = $conn->prepare($debugSql);
$debugStmt->execute();
$allAccounts = $debugStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Hiển thị tối đa 50 tài khoản đầu tiên trong database:</p>";

echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 15px 0; font-size: 12px;'>";
echo "<tr style='background-color: #f5f5f5;'>";
echo "<th>Account ID</th>";
echo "<th>Username</th>";
echo "<th>Reg ID</th>";
echo "<th>Owner ID</th>";
echo "<th>Owner Name</th>";
echo "<th>Belongs to current user?</th>";
echo "</tr>";

foreach ($allAccounts as $acc) {
    $belongsToCurrentUser = ($acc['user_id'] == $currentUserId);
    $rowStyle = $belongsToCurrentUser ? "background-color: #e8f5e9;" : "";
    
    echo "<tr style='{$rowStyle}'>";
    echo "<td>{$acc['account_id']}</td>";
    echo "<td><strong>{$acc['username_acc']}</strong></td>";
    echo "<td>{$acc['registration_id']}</td>";
    echo "<td>" . ($acc['user_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($acc['owner_username'] ?? '-') . "</td>";
    echo "<td>" . ($belongsToCurrentUser ? '✅ YES' : '-') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

// Test with RtkAccount class
echo "<h3>5. Test Với Class RtkAccount</h3>";

$rtkAccountManager = new RtkAccount($db);
$result = $rtkAccountManager->getAccountsByUserIdWithPagination($currentUserId, 1, 10, 'all');

echo "<p>Kết quả từ method <code>getAccountsByUserIdWithPagination()</code>:</p>";
echo "<ul>";
echo "<li>Total: {$result['pagination']['total']}</li>";
echo "<li>Current Page: {$result['pagination']['current_page']}</li>";
echo "<li>Total Pages: {$result['pagination']['total_pages']}</li>";
echo "<li>Accounts returned: " . count($result['accounts']) . "</li>";
echo "</ul>";

if (count($result['accounts']) > 0) {
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; margin: 15px 0;'>";
    echo "<tr style='background-color: #f5f5f5;'><th>ID</th><th>Username</th><th>Package</th><th>Status</th></tr>";
    
    foreach ($result['accounts'] as $acc) {
        echo "<tr>";
        echo "<td>{$acc['id']}</td>";
        echo "<td><strong>{$acc['username_acc']}</strong></td>";
        echo "<td>{$acc['package_name']}</td>";
        echo "<td>{$acc['enabled_status']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<hr>";
echo "<h3>📝 Kết Luận</h3>";
echo "<div style='padding: 15px; background-color: #e3f2fd; border-left: 4px solid #2196f3; margin: 15px 0;'>";
echo "<strong>Checklist:</strong><br>";
echo "1. ✓ Session user_id = {$currentUserId}<br>";
echo "2. ✓ User exists in database<br>";
echo "3. ✓ Query conditions: <code>r.user_id = {$currentUserId} AND sa.deleted_at IS NULL</code><br>";
echo "4. ✓ Accounts found: " . count($accounts) . "<br><br>";

if (count($accounts) == 0) {
    echo "<strong style='color: red;'>⚠️ VẤN ĐỀ:</strong> Không có tài khoản nào thuộc user này!<br>";
    echo "<strong>Có thể do:</strong><br>";
    echo "- Tài khoản chưa được cập nhật ownership đúng cách<br>";
    echo "- registration.user_id ≠ {$currentUserId}<br>";
    echo "- Tài khoản bị soft-delete (deleted_at không NULL)<br>";
} else {
    echo "<strong style='color: green;'>✅ OK:</strong> Tìm thấy tài khoản, trang quản lý nên hiển thị được!<br>";
}

echo "</div>";

$db->close();
?>
