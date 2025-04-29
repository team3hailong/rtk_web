<?php
/**
 * File bootstrap cho tiện ích xử lý đường dẫn
 * File này được thiết kế để được include từ các file configuration
 */

// Lấy đường dẫn thư mục hiện tại
$current_dir = __DIR__;

// Load file tiện ích đường dẫn
require_once $current_dir . '/path_utils.php';

// Định nghĩa các hằng số toàn cục để sử dụng
if (!defined('PROJECT_ROOT_PATH')) {
    define('PROJECT_ROOT_PATH', get_project_root_path());
}

if (!defined('BASE_URL')) {
    define('BASE_URL', get_base_url());
}

if (!defined('PUBLIC_URL')) {
    define('PUBLIC_URL', get_public_url());
}