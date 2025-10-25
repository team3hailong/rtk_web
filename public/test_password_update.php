<?php
/**
 * Test Script - Kiểm tra chức năng đổi mật khẩu RTK
 */

require_once dirname(__DIR__) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/RtkAccount.php';

echo "<h2>🔐 Test Chức Năng Đổi Mật Khẩu RTK</h2>";
echo "<hr>";

// Get parameters
$accountId = $_GET['account_id'] ?? '';
$testMode = $_GET['test'] ?? ''; // 'dry-run' for testing

if (empty($accountId)) {
    echo "<div style='padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;'>";
    echo "⚠️ Vui lòng cung cấp account_id<br>";
    echo "Ví dụ: ?account_id=123";
    echo "</div>";
    exit;
}

echo "<h3>1. Thông Tin Tài Khoản</h3>";

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT id, username_acc, password_acc, enabled FROM survey_account WHERE id = :account_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':account_id', $accountId);
$stmt->execute();
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336;'>";
    echo "❌ Không tìm thấy tài khoản với ID: {$accountId}";
    echo "</div>";
    exit;
}

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Account ID</strong></td><td>{$account['id']}</td></tr>";
echo "<tr><td><strong>Username</strong></td><td>{$account['username_acc']}</td></tr>";
echo "<tr><td><strong>Current Password</strong></td><td>{$account['password_acc']}</td></tr>";
echo "<tr><td><strong>Enabled</strong></td><td>" . ($account['enabled'] ? '✅ Yes' : '❌ No') . "</td></tr>";
echo "</table>";

echo "<hr>";

echo "<h3>2. Flow Đổi Mật Khẩu</h3>";

echo "<div style='padding: 15px; background-color: #e3f2fd; border-left: 4px solid #2196f3;'>";
echo "<strong>Quy trình hiện tại:</strong><br><br>";
echo "1. ✅ <strong>API RTK System</strong><br>";
echo "   → Gọi API PUT /openapi/broadcast/users<br>";
echo "   → Cập nhật mật khẩu trên hệ thống CORS/Caster<br><br>";
echo "2. ✅ <strong>Local Database</strong><br>";
echo "   → Cập nhật password_acc trong bảng survey_account<br>";
echo "   → CHỈ cập nhật nếu API thành công<br><br>";
echo "<strong>⚠️ Lưu ý:</strong> Nếu API fail → Local DB KHÔNG được update (đồng bộ)<br>";
echo "</div>";

echo "<hr>";

echo "<h3>3. API Configuration</h3>";

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><td><strong>API URL</strong></td><td>" . RTK_API_URL . "</td></tr>";
echo "<tr><td><strong>Access Key</strong></td><td>" . RTK_API_ACCESS_KEY . "</td></tr>";
echo "<tr><td><strong>Sign Method</strong></td><td>" . RTK_API_SIGN_METHOD . "</td></tr>";
echo "</table>";

echo "<hr>";

echo "<h3>4. Test Form</h3>";

echo "<form method='POST' style='padding: 20px; background-color: #f5f5f5; border-radius: 8px;'>";
echo "<input type='hidden' name='account_id' value='{$accountId}'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label><strong>Username:</strong></label><br>";
echo "<input type='text' value='{$account['username_acc']}' readonly style='width: 300px; padding: 8px;'>";
echo "</div>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label><strong>Mật khẩu hiện tại:</strong></label><br>";
echo "<input type='text' value='{$account['password_acc']}' readonly style='width: 300px; padding: 8px;'>";
echo "</div>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label><strong>Mật khẩu mới:</strong></label><br>";
echo "<input type='text' name='new_password' placeholder='Nhập mật khẩu mới' required style='width: 300px; padding: 8px;'>";
echo "</div>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label><input type='checkbox' name='test_mode' value='1'> Test mode (không thực sự gọi API)</label>";
echo "</div>";
echo "<button type='submit' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Đổi Mật Khẩu</button>";
echo "</form>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<hr>";
    echo "<h3>5. Kết Quả</h3>";
    
    $newPassword = $_POST['new_password'] ?? '';
    $testMode = isset($_POST['test_mode']);
    
    if (empty($newPassword)) {
        echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336;'>";
        echo "❌ Vui lòng nhập mật khẩu mới";
        echo "</div>";
    } else {
        if ($testMode) {
            echo "<div style='padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;'>";
            echo "🧪 <strong>TEST MODE</strong> - Không thực sự gọi API<br><br>";
            echo "Username: {$account['username_acc']}<br>";
            echo "New Password: {$newPassword}<br><br>";
            echo "Trong production, sẽ gọi:<br>";
            echo "<code>updateRtkAccountPassword('{$account['username_acc']}', '{$newPassword}')</code>";
            echo "</div>";
        } else {
            $rtkAccountManager = new RtkAccount($db);
            $success = $rtkAccountManager->updatePassword($accountId, $newPassword);
            
            if ($success) {
                echo "<div style='padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50;'>";
                echo "✅ <strong>Đổi mật khẩu thành công!</strong><br><br>";
                echo "Mật khẩu đã được cập nhật trên:<br>";
                echo "1. ✅ RTK API System (CORS/Caster)<br>";
                echo "2. ✅ Local Database<br><br>";
                echo "Kiểm tra logs tại: <code>private/logs/error.log</code>";
                echo "</div>";
            } else {
                echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336;'>";
                echo "❌ <strong>Đổi mật khẩu thất bại!</strong><br><br>";
                echo "Có thể do:<br>";
                echo "- API RTK không phản hồi<br>";
                echo "- Credentials không đúng<br>";
                echo "- Lỗi kết nối<br><br>";
                echo "Kiểm tra logs tại: <code>private/logs/error.log</code>";
                echo "</div>";
            }
        }
    }
}

echo "<hr>";
echo "<h3>📋 Ghi Chú</h3>";
echo "<ul>";
echo "<li>Mật khẩu phải có ít nhất 6 ký tự</li>";
echo "<li>Kiểm tra logs để debug: <code>private/logs/error.log</code></li>";
echo "<li>API endpoint: <code>PUT /openapi/broadcast/users</code></li>";
echo "<li>Nếu API fail, local DB sẽ KHÔNG được update</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>🔗 Test URLs</h3>";
echo "<ul>";
echo "<li><a href='?account_id=1'>Test với Account ID = 1</a></li>";
echo "<li><a href='?account_id=2'>Test với Account ID = 2</a></li>";
echo "<li>Hoặc thêm ?account_id=YOUR_ID vào URL</li>";
echo "</ul>";

$db->close();
?>
