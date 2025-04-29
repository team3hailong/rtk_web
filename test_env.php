<?php
/**
 * Test file để kiểm tra hệ thống biến môi trường và kết nối database
 */

// Hiển thị lỗi để dễ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Kiểm tra hệ thống biến môi trường</h1>";
echo "<pre>";

// Thư mục gốc dự án
$project_root_path = __DIR__;

// 1. Kiểm tra file .env có tồn tại không
echo "1. Kiểm tra file .env:\n";
if (file_exists($project_root_path . '/.env')) {
    echo "✓ File .env tồn tại\n";
    echo "Nội dung file .env (10 dòng đầu tiên):\n";
    $env_content = file_get_contents($project_root_path . '/.env');
    $env_lines = explode("\n", $env_content);
    for ($i = 0; $i < min(10, count($env_lines)); $i++) {
        // Ẩn thông tin nhạy cảm
        $line = $env_lines[$i];
        if (preg_match('/(PASSWORD|KEY|TOKEN|SECRET)/', $line)) {
            $parts = explode('=', $line, 2);
            if (count($parts) > 1) {
                echo $parts[0] . "=[HIDDEN]\n";
            } else {
                echo $line . "\n";
            }
        } else {
            echo $line . "\n";
        }
    }
    echo "...\n";
} else {
    echo "✗ File .env không tồn tại\n";
}

// 2. Kiểm tra thư viện Dotenv
echo "\n2. Kiểm tra thư viện Dotenv:\n";
if (file_exists($project_root_path . '/vendor/autoload.php')) {
    require_once $project_root_path . '/vendor/autoload.php';
    
    if (class_exists('Dotenv\Dotenv')) {
        echo "✓ Thư viện Dotenv đã được cài đặt\n";
    } else {
        echo "✗ Thư viện Dotenv chưa được cài đặt\n";
        echo "Hãy chạy: composer require vlucas/phpdotenv\n";
    }
} else {
    echo "✗ Composer autoloader không tồn tại\n";
    echo "Hãy chạy: composer install\n";
}

// 3. Tải env_loader.php và kiểm tra hàm env()
echo "\n3. Tải env_loader.php:\n";
if (file_exists($project_root_path . '/private/config/env_loader.php')) {
    require_once $project_root_path . '/private/config/env_loader.php';
    echo "✓ File env_loader.php đã được tải\n";
    
    if (function_exists('env')) {
        echo "✓ Hàm env() tồn tại\n";
        
        // Kiểm tra các giá trị từ .env
        echo "\nCác giá trị biến môi trường:\n";
        echo "DB_SERVER = " . env('DB_SERVER', 'default_value') . "\n";
        echo "DB_USERNAME = " . env('DB_USERNAME', 'default_value') . "\n";
        echo "DB_NAME = " . env('DB_NAME', 'default_value') . "\n";
        echo "VIETQR_BANK_ID = " . env('VIETQR_BANK_ID', 'default_value') . "\n";
        echo "SMTP_HOST = " . env('SMTP_HOST', 'default_value') . "\n";
    } else {
        echo "✗ Hàm env() không tồn tại\n";
    }
} else {
    echo "✗ File env_loader.php không tồn tại\n";
}

// 4. Kiểm tra kết nối database sử dụng thông tin từ .env
echo "\n4. Kiểm tra kết nối database:\n";
require_once $project_root_path . '/private/config/database.php';

if (isset($conn)) {
    echo "✓ Kết nối database thành công\n";
    
    // Kiểm tra thông tin kết nối
    echo "Thông tin kết nối:\n";
    echo "- DB Server: " . DB_SERVER . "\n";
    echo "- DB Name: " . DB_NAME . "\n";
    
    // Thử query để xác nhận kết nối hoạt động
    try {
        $query = "SHOW TABLES";
        $result = $conn->query($query);
        
        if ($result) {
            echo "\nDanh sách các bảng trong database:\n";
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            echo implode(", ", $tables) . "\n";
            
            // Thử query một bảng cụ thể (ví dụ: bảng user)
            if (in_array('user', $tables)) {
                $query = "SELECT COUNT(*) as total FROM user";
                $result = $conn->query($query);
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "\nSố lượng người dùng trong hệ thống: " . $row['total'] . "\n";
                }
            }
        } else {
            echo "✗ Không thể truy vấn database\n";
        }
    } catch (Exception $e) {
        echo "✗ Lỗi truy vấn: " . $e->getMessage() . "\n";
    }
    
    $conn->close();
    echo "\n✓ Đã đóng kết nối database\n";
} else {
    echo "✗ Không thể kết nối đến database\n";
}

// 5. Tổng kết
echo "\n5. Tổng kết:\n";
echo "Hệ thống biến môi trường " . (function_exists('env') ? "đã" : "chưa") . " hoạt động\n";
echo "Kết nối database sử dụng thông tin từ .env " . (isset($conn) ? "đã" : "chưa") . " hoạt động\n";

echo "</pre>";
?>