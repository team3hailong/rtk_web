<?php
/**
 * Script chạy migration để tạo bảng mocqg
 */

// Kết nối database
require_once dirname(__DIR__) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== BẮT ĐẦU CHẠY MIGRATION MOCQG ===\n\n";
    
    // Đọc file migration
    $migrationFile = __DIR__ . '/migrations/20251023_add_mocqg_table.sql';
    
    if (!file_exists($migrationFile)) {
        die("Lỗi: Không tìm thấy file migration tại $migrationFile\n");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        echo "Executing: " . substr($statement, 0, 100) . "...\n";
        $pdo->exec($statement);
        echo "✓ Success\n\n";
    }
    
    echo "=== HOÀN THÀNH MIGRATION ===\n";
    echo "Bảng mocqg đã được tạo thành công!\n\n";
    
    // Kiểm tra dữ liệu
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mocqg");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Số lượng mốc trong database: " . $result['total'] . "\n";
    
    // Hiển thị danh sách mốc
    $stmt = $pdo->query("SELECT * FROM mocqg");
    $mocqg_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($mocqg_list) > 0) {
        echo "\nDanh sách mốc:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($mocqg_list as $mocqg) {
            echo sprintf(
                "ID: %d | Tên: %s | Tọa độ: %.6f, %.6f | Status: %d\n",
                $mocqg['id'],
                $mocqg['ten_moc'],
                $mocqg['lat'],
                $mocqg['long'],
                $mocqg['status']
            );
        }
        echo str_repeat("-", 80) . "\n";
    }
    
    $db->close();
    
} catch (PDOException $e) {
    die("Lỗi database: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
