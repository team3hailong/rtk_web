<?php
/**
 * Environment Variable Loader
 * Tải các biến môi trường từ file .env
 */

// Đường dẫn đến thư mục gốc của dự án
$project_root_path = dirname(dirname(__DIR__)); // Từ /private/config/ lên 2 cấp

// Biến để kiểm soát xem Dotenv có thành công không
$dotenv_loaded = false;

// Kiểm tra và tải thư viện Dotenv
if (file_exists($project_root_path . '/vendor/autoload.php')) {
    require_once $project_root_path . '/vendor/autoload.php';
    
    // Tải các biến môi trường từ file .env
    if (file_exists($project_root_path . '/.env') && class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($project_root_path);
            $dotenv->load();
            $dotenv_loaded = true;
            
            // Kiểm tra các biến môi trường bắt buộc
            // $dotenv->required(['DB_SERVER', 'DB_USERNAME', 'DB_NAME']);
        } catch (Exception $e) {
            // Ghi log lỗi nhưng không dừng ứng dụng
            error_log('Error loading environment variables: ' . $e->getMessage());
        }
    } else {
        if (!file_exists($project_root_path . '/.env')) {
            error_log('Warning: .env file not found. Using default configuration.');
        }
        if (!class_exists('Dotenv\Dotenv')) {
            error_log('Warning: Dotenv class not found. Please run "composer require vlucas/phpdotenv". Using default configuration.');
        }
    }
} else {
    error_log('Warning: Composer autoloader not found. Using hardcoded configuration.');
}

/**
 * Hàm lấy giá trị biến môi trường với giá trị mặc định an toàn
 *
 * @param string $key Tên biến môi trường
 * @param mixed $default Giá trị mặc định nếu không tìm thấy
 * @return mixed
 */
function env($key, $default = null) {
    global $dotenv_loaded;
    
    // Nếu Dotenv đã được tải thành công, sử dụng các biến môi trường
    if ($dotenv_loaded) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
    
    // Fallback: Nếu Dotenv không được tải, luôn trả về giá trị mặc định
    return $default;
}