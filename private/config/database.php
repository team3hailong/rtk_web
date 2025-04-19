<?php
// filepath: e:\Application\laragon\www\surveying_account\private\config\database.php
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root'); // Thay bằng username của bạn
define('DB_PASSWORD', ''); // Thay bằng password của bạn
define('DB_NAME', 'surveying_account');

// Tạo kết nối
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Đặt bộ ký tự thành utf8mb4
$conn->set_charset("utf8mb4");

?>