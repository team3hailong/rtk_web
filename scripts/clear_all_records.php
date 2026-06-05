<?php
/**
 * Script xóa toàn bộ record trong database
 * Sử dụng: php scripts/clear_all_records.php
 * 
 * CẢNH BÁO: Script này sẽ XÓA TẤT CẢ DỮ LIỆU trong các bảng được chỉ định!
 * Chỉ sử dụng trong môi trường development/testing!
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../private/config/database.php';

// Danh sách các bảng cần xóa dữ liệu (theo thứ tự xóa - quan trọng để tránh foreign key constraint)
$tables = [
    // Bảng phụ thuộc - xóa trước
    'activity_logs',
    'referral_commissions',
    'referral_relationships',
    'user_rankings',
    'user_devices',
    'remember_tokens',
    'password_resets',
    'support_requests',
    'voucher_usage',
    'vouchers',
    'bank_info',
    'transactions',
    'selected_provinces',
    'mocqg',
    'survey_account',
    
    // Bảng chính - xóa sau
    'users',
];

function clearAllRecords($pdo, $tables) {
    try {
        echo "=== BẮT ĐẦU XÓA DỮ LIỆU ===\n\n";
        
        // Tắt foreign key checks để tránh lỗi ràng buộc
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        echo "✓ Đã tắt foreign key checks\n\n";
        
        $totalDeleted = 0;
        
        foreach ($tables as $table) {
            try {
                // Đếm số record trước khi xóa
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    // Xóa toàn bộ record
                    $pdo->exec("DELETE FROM `{$table}`");
                    
                    // Reset auto_increment
                    $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                    
                    echo "✓ Đã xóa {$count} record từ bảng '{$table}'\n";
                    $totalDeleted += $count;
                } else {
                    echo "- Bảng '{$table}' đã trống\n";
                }
            } catch (PDOException $e) {
                // Bảng không tồn tại hoặc lỗi khác
                echo "✗ Lỗi khi xóa bảng '{$table}': " . $e->getMessage() . "\n";
            }
        }
        
        // Bật lại foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "\n✓ Đã bật lại foreign key checks\n";
        
        echo "\n=== HOÀN THÀNH ===\n";
        echo "Tổng số record đã xóa: {$totalDeleted}\n";
        
    } catch (Exception $e) {
        echo "\n✗ LỖI: " . $e->getMessage() . "\n";
        // Đảm bảo bật lại foreign key checks ngay cả khi có lỗi
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $e2) {
            // Ignore
        }
    }
}

// Xác nhận trước khi thực hiện
echo "CẢNH BÁO: Script này sẽ XÓA TOÀN BỘ DỮ LIỆU trong database!\n";
echo "Database: " . DB_NAME . "\n";
echo "Host: " . DB_SERVER . "\n\n";
echo "Các bảng sẽ bị xóa dữ liệu:\n";
foreach ($tables as $table) {
    echo "  - {$table}\n";
}
echo "\nBạn có chắc chắn muốn tiếp tục? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) === 'yes') {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        
        clearAllRecords($pdo, $tables);
        
    } catch (PDOException $e) {
        echo "✗ Lỗi kết nối database: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "\nĐã hủy thao tác.\n";
}
