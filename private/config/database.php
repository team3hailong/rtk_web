<?php
// Tải env_loader trước tiên để có thể sử dụng hàm env()
require_once __DIR__ . '/env_loader.php';

// Lấy thông tin kết nối từ biến môi trường, có giá trị mặc định là các giá trị hiện tại
define('DB_SERVER', env('DB_SERVER', 'localhost'));
define('DB_USERNAME', env('DB_USERNAME', 'root')); 
define('DB_PASSWORD', env('DB_PASSWORD', '')); 
define('DB_NAME', env('DB_NAME', 'sa3'));

// Tạo kết nối
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Đặt bộ ký tự thành utf8mb4
$conn->set_charset("utf8mb4");

?>