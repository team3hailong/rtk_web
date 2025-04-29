<?php
/**
 * Tiện ích xử lý đường dẫn trong ứng dụng
 * File này cung cấp các hàm để lấy base URL và project root path
 */

if (!function_exists('get_project_root_path')) {
    /**
     * Lấy đường dẫn tuyệt đối đến thư mục gốc của dự án
     * Hàm này hoạt động ở mọi file trong dự án, bất kể độ sâu của thư mục
     * 
     * @return string Đường dẫn tuyệt đối đến thư mục gốc
     */
    function get_project_root_path() {
        // Đường dẫn thực đến file hiện tại
        $current_file_path = debug_backtrace()[0]['file'];
        
        // Tìm vị trí của "/rtk_web/" trong đường dẫn
        $pos = stripos($current_file_path, '/rtk_web/');
        if ($pos === false) {
            $pos = stripos($current_file_path, '\\rtk_web\\');
        }
        
        if ($pos !== false) {
            // Trả về đường dẫn đến thư mục gốc dự án
            return substr($current_file_path, 0, $pos + 9); // +9 cho "/rtk_web/"
        }
        
        // Fallback - nếu không tìm thấy thì lùi về 3 cấp từ vị trí hiện tại
        // Giả sử điểm gọi sâu nhất là từ /private/action/subfolder/file.php
        return dirname(dirname(dirname(dirname(__FILE__)))); 
    }
}

if (!function_exists('get_base_url')) {
    /**
     * Lấy base URL của ứng dụng
     * 
     * @param bool $include_protocol Có bao gồm protocol (http/https) hay không
     * @return string Base URL của ứng dụng
     */
    function get_base_url($include_protocol = true) {
        // Trước tiên kiểm tra nếu SITE_URL đã được định nghĩa trong config
        if (defined('SITE_URL')) {
            return rtrim(SITE_URL, '/');
        }
        
        $protocol = $include_protocol ? 
                  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") : 
                  '';
        
        $domain = $_SERVER['HTTP_HOST'];
        
        // Lấy đường dẫn script hiện tại (tương đối với domain)
        $script_path = $_SERVER['PHP_SELF']; // Ví dụ: /rtk_web/public/pages/dashboard.php
        $script_dir = dirname($script_path);
        
        // Xác định đường dẫn cơ sở của dự án
        $base_project_dir = '';
        
        // Kiểm tra và xác định base project dir
        if (strpos($script_dir, '/public/') !== false) {
            // Nếu file nằm trong /public/
            $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
        } elseif (strpos($script_dir, '/private/') !== false) {
            // Nếu file nằm trong /private/
            $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/private/'));
        } else {
            // Giả sử ứng dụng nằm trực tiếp dưới domain root hoặc subfolder
            $parts = explode('/', trim($script_dir, '/'));
            if (!empty($parts[0]) && $parts[0] === 'rtk_web') {
                $base_project_dir = '/' . $parts[0];
            }
        }
        
        return rtrim($protocol . $domain . $base_project_dir, '/');
    }
}

if (!function_exists('get_public_url')) {
    /**
     * Lấy URL đến thư mục public
     * 
     * @return string URL đến thư mục public
     */
    function get_public_url() {
        return get_base_url() . '/public';
    }
}

if (!function_exists('path_to_url')) {
    /**
     * Chuyển đổi đường dẫn tệp thành URL
     * 
     * @param string $path Đường dẫn tệp cần chuyển đổi
     * @return string URL tương ứng
     */
    function path_to_url($path) {
        $project_root = get_project_root_path();
        $path = str_replace('\\', '/', $path); // Chuẩn hóa dấu '\'
        $project_root = str_replace('\\', '/', $project_root);
        
        if (strpos($path, $project_root) === 0) {
            $relative_path = substr($path, strlen($project_root));
            if (strpos($relative_path, '/public/') === 0) {
                return get_base_url() . $relative_path;
            }
        }
        
        return $path; // Trả về nguyên path nếu không thể chuyển đổi
    }
}