<?php
/**
 * Debug Script - Kiểm tra vấn đề cập nhật ownership
 * Kiểm tra xem tài khoản có bị soft-delete không và kiểm tra owner hiện tại
 */

require_once dirname(__DIR__) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>🔍 Debug Ownership Update Issues</h2>";
echo "<hr>";

// Get test account username from query string
$testUsername = $_GET['username'] ?? 'test3ngay';

echo "<h3>Kiểm tra tài khoản: <strong>{$testUsername}</strong></h3>";

// Check if account exists and get details
$sql = "SELECT 
        sa.id as account_id,
        sa.username_acc,
        sa.password_acc,
        sa.registration_id,
        sa.deleted_at,
        sa.enabled,
        r.id as reg_id,
        r.user_id as current_owner_id,
        u.username as owner_username,
        u.email as owner_email
    FROM survey_account sa
    LEFT JOIN registration r ON sa.registration_id = r.id
    LEFT JOIN user u ON r.user_id = u.id
    WHERE sa.username_acc = :username";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':username', $testUsername);
$stmt->execute();
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336; margin: 15px 0;'>";
    echo "❌ <strong>Không tìm thấy tài khoản '{$testUsername}'</strong><br>";
    echo "Tài khoản này không tồn tại trong database.";
    echo "</div>";
} else {
    echo "<div style='padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50; margin: 15px 0;'>";
    echo "✅ <strong>Tìm thấy tài khoản</strong>";
    echo "</div>";
    
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background-color: #f5f5f5;'><th>Thông tin</th><th>Giá trị</th><th>Trạng thái</th></tr>";
    
    // Account ID
    echo "<tr>";
    echo "<td><strong>Account ID</strong></td>";
    echo "<td>{$account['account_id']}</td>";
    echo "<td>-</td>";
    echo "</tr>";
    
    // Registration ID
    echo "<tr>";
    echo "<td><strong>Registration ID</strong></td>";
    echo "<td>{$account['registration_id']}</td>";
    echo "<td>-</td>";
    echo "</tr>";
    
    // Deleted Status
    $isDeleted = !empty($account['deleted_at']);
    echo "<tr>";
    echo "<td><strong>Deleted At</strong></td>";
    echo "<td>" . ($account['deleted_at'] ?? 'NULL') . "</td>";
    echo "<td style='color: " . ($isDeleted ? 'red' : 'green') . ";'>";
    echo $isDeleted ? "❌ TÀI KHOẢN BỊ XÓA MỀM" : "✅ Bình thường";
    echo "</td>";
    echo "</tr>";
    
    // Enabled Status
    $isEnabled = $account['enabled'] == 1;
    echo "<tr>";
    echo "<td><strong>Enabled</strong></td>";
    echo "<td>{$account['enabled']}</td>";
    echo "<td style='color: " . ($isEnabled ? 'green' : 'orange') . ";'>";
    echo $isEnabled ? "✅ Đang kích hoạt" : "⚠️ Đã khóa";
    echo "</td>";
    echo "</tr>";
    
    // Current Owner
    $hasOwner = !empty($account['current_owner_id']);
    echo "<tr>";
    echo "<td><strong>Owner User ID</strong></td>";
    echo "<td>" . ($account['current_owner_id'] ?? 'NULL') . "</td>";
    echo "<td style='color: " . ($hasOwner ? 'blue' : 'gray') . ";'>";
    echo $hasOwner ? "👤 Có chủ sở hữu" : "⚪ Chưa có chủ";
    echo "</td>";
    echo "</tr>";
    
    if ($hasOwner) {
        echo "<tr>";
        echo "<td><strong>Owner Username</strong></td>";
        echo "<td>{$account['owner_username']}</td>";
        echo "<td>-</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td><strong>Owner Email</strong></td>";
        echo "<td>{$account['owner_email']}</td>";
        echo "<td>-</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Diagnosis
    echo "<h3>📊 Chẩn đoán</h3>";
    echo "<div style='padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; margin: 15px 0;'>";
    
    if ($isDeleted) {
        echo "⚠️ <strong>VẤN ĐỀ PHÁT HIỆN:</strong> Tài khoản bị soft-delete (deleted_at không NULL)<br>";
        echo "→ Tài khoản sẽ KHÔNG hiển thị trong danh sách mặc dù đã cập nhật ownership<br>";
        echo "→ <strong>Giải pháp:</strong> Method updateAccountOwnership() đã được cập nhật để tự động set deleted_at = NULL";
    } elseif (!$hasOwner) {
        echo "ℹ️ Tài khoản chưa có chủ sở hữu - sẵn sàng để claim ownership";
    } else {
        echo "✅ Tài khoản đang có chủ sở hữu: {$account['owner_username']} (ID: {$account['current_owner_id']})";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<h3>🧪 Test URLs</h3>";
echo "<ul>";
echo "<li><a href='?username=test3ngay'>Test: test3ngay</a></li>";
echo "<li><a href='?username=test_user1'>Test: test_user1</a></li>";
echo "<li>Hoặc thêm ?username=YOUR_USERNAME vào URL</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>📝 Ghi chú</h3>";
echo "<p><strong>Vấn đề đã sửa:</strong></p>";
echo "<ul>";
echo "<li>✅ Method <code>updateAccountOwnership()</code> giờ sẽ tự động set <code>deleted_at = NULL</code></li>";
echo "<li>✅ Sử dụng transaction để đảm bảo cả 2 bảng đều được update</li>";
echo "<li>✅ Thêm logging để dễ debug</li>";
echo "</ul>";

echo "<p><strong>Cách test:</strong></p>";
echo "<ol>";
echo "<li>Chạy script này với username của tài khoản cần kiểm tra</li>";
echo "<li>Xem xem <code>deleted_at</code> có NULL không</li>";
echo "<li>Nếu không NULL → tài khoản bị soft-delete → không hiện trong list</li>";
echo "<li>Thử cập nhật ownership lại → method mới sẽ tự động restore</li>";
echo "</ol>";
?>
