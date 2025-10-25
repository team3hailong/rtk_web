<?php
/**
 * Debug Script - Kiểm tra Registration và các JOIN
 */

require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>🔍 Debug Registration Joins</h2>";
echo "<hr>";

// Get registration_id from URL or default
$regId = $_GET['reg_id'] ?? 1;

echo "<h3>Kiểm tra Registration ID: <strong>{$regId}</strong></h3>";

// Check registration details
$sql = "SELECT 
        r.id,
        r.user_id,
        r.package_id,
        r.location_id,
        r.status,
        r.start_time,
        r.end_time,
        p.id as package_exists,
        p.name as package_name,
        l.id as location_exists,
        l.province as location_province
    FROM registration r
    LEFT JOIN package p ON r.package_id = p.id
    LEFT JOIN location l ON r.location_id = l.id
    WHERE r.id = :reg_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':reg_id', $regId);
$stmt->execute();
$reg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reg) {
    echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336;'>";
    echo "❌ Không tìm thấy registration với ID: {$regId}";
    echo "</div>";
    exit;
}

echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f5f5f5;'><th>Field</th><th>Value</th><th>Status</th></tr>";

// Registration ID
echo "<tr><td>ID</td><td>{$reg['id']}</td><td>-</td></tr>";

// User ID
$hasUser = !empty($reg['user_id']);
echo "<tr><td>User ID</td><td>" . ($reg['user_id'] ?? 'NULL') . "</td>";
echo "<td style='color: " . ($hasUser ? 'green' : 'red') . ";'>" . ($hasUser ? '✅ OK' : '❌ NULL') . "</td></tr>";

// Package ID
$hasPackage = !empty($reg['package_id']);
$packageExists = !empty($reg['package_exists']);
echo "<tr><td>Package ID</td><td>" . ($reg['package_id'] ?? 'NULL') . "</td>";
echo "<td style='color: " . ($hasPackage ? ($packageExists ? 'green' : 'orange') : 'red') . ";'>";
if (!$hasPackage) {
    echo "❌ NULL - INNER JOIN sẽ loại bỏ record";
} elseif (!$packageExists) {
    echo "⚠️ Package không tồn tại - INNER JOIN sẽ loại bỏ record";
} else {
    echo "✅ OK - Package: {$reg['package_name']}";
}
echo "</td></tr>";

// Location ID
$hasLocation = !empty($reg['location_id']);
$locationExists = !empty($reg['location_exists']);
echo "<tr><td>Location ID</td><td>" . ($reg['location_id'] ?? 'NULL') . "</td>";
echo "<td style='color: " . ($hasLocation ? ($locationExists ? 'green' : 'orange') : 'red') . ";'>";
if (!$hasLocation) {
    echo "❌ NULL - INNER JOIN sẽ loại bỏ record";
} elseif (!$locationExists) {
    echo "⚠️ Location không tồn tại - INNER JOIN sẽ loại bỏ record";
} else {
    echo "✅ OK - Location: {$reg['location_province']}";
}
echo "</td></tr>";

// Status
echo "<tr><td>Status</td><td>{$reg['status']}</td><td>-</td></tr>";

// Dates
echo "<tr><td>Start Time</td><td>{$reg['start_time']}</td><td>-</td></tr>";
echo "<tr><td>End Time</td><td>{$reg['end_time']}</td><td>-</td></tr>";

echo "</table>";

echo "<hr>";

// Diagnosis
echo "<h3>📊 Chẩn Đoán</h3>";

if (!$hasPackage || !$packageExists || !$hasLocation || !$locationExists) {
    echo "<div style='padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336;'>";
    echo "<strong>❌ VẤN ĐỀ NGHIÊM TRỌNG:</strong><br><br>";
    
    if (!$hasPackage || !$packageExists) {
        echo "🔴 <strong>package_id</strong> " . (!$hasPackage ? "là NULL" : "không tồn tại") . "<br>";
        echo "→ Query sử dụng <code>JOIN package p</code> sẽ <strong>LOẠI BỎ</strong> tất cả tài khoản liên quan đến registration này!<br><br>";
    }
    
    if (!$hasLocation || !$locationExists) {
        echo "🔴 <strong>location_id</strong> " . (!$hasLocation ? "là NULL" : "không tồn tại") . "<br>";
        echo "→ Query sử dụng <code>JOIN location l</code> sẽ <strong>LOẠI BỎ</strong> tất cả tài khoản liên quan đến registration này!<br><br>";
    }
    
    echo "<strong>GIẢI PHÁP:</strong><br>";
    echo "1. Đổi <code>JOIN package</code> → <code>LEFT JOIN package</code><br>";
    echo "2. Đổi <code>JOIN location</code> → <code>LEFT JOIN location</code><br>";
    echo "3. Hoặc cập nhật registration để có package_id và location_id hợp lệ<br>";
    echo "</div>";
} else {
    echo "<div style='padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50;'>";
    echo "✅ Registration này có đầy đủ thông tin, query JOIN sẽ hoạt động bình thường.";
    echo "</div>";
}

echo "<hr>";

// Test the actual query
echo "<h3>🧪 Test Query Với INNER JOIN (Hiện tại)</h3>";

$testSql1 = "SELECT sa.id, sa.username_acc
    FROM survey_account sa
    JOIN registration r ON sa.registration_id = r.id
    JOIN package p ON r.package_id = p.id
    JOIN location l ON r.location_id = l.id
    WHERE sa.registration_id = :reg_id AND sa.deleted_at IS NULL";

$stmt1 = $conn->prepare($testSql1);
$stmt1->bindParam(':reg_id', $regId);
$stmt1->execute();
$result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Kết quả: <strong>" . count($result1) . "</strong> tài khoản</p>";
if (count($result1) == 0) {
    echo "<div style='color: red;'>❌ KHÔNG TÌM THẤY - Do INNER JOIN loại bỏ</div>";
}

echo "<hr>";

echo "<h3>🧪 Test Query Với LEFT JOIN (Đề xuất)</h3>";

$testSql2 = "SELECT sa.id, sa.username_acc
    FROM survey_account sa
    JOIN registration r ON sa.registration_id = r.id
    LEFT JOIN package p ON r.package_id = p.id
    LEFT JOIN location l ON r.location_id = l.id
    WHERE sa.registration_id = :reg_id AND sa.deleted_at IS NULL";

$stmt2 = $conn->prepare($testSql2);
$stmt2->bindParam(':reg_id', $regId);
$stmt2->execute();
$result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Kết quả: <strong>" . count($result2) . "</strong> tài khoản</p>";
if (count($result2) > 0) {
    echo "<div style='color: green;'>✅ TÌM THẤY - LEFT JOIN giữ lại records</div>";
    echo "<ul>";
    foreach ($result2 as $acc) {
        echo "<li>Account ID: {$acc['id']}, Username: <strong>{$acc['username_acc']}</strong></li>";
    }
    echo "</ul>";
}

$db->close();
?>
