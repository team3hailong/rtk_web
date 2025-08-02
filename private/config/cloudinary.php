<?php
/**
 * Cloudinary Configuration
 * Cấu hình để sử dụng Cloudinary cho việc lưu trữ và quản lý hình ảnh
 */

// Tải autoload để sử dụng Cloudinary SDK
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

// Import Cloudinary classes
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

// Cấu hình Cloudinary từ biến môi trường
$cloudinary_config = [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'dlv6xgmri'),
    'api_key' => env('CLOUDINARY_API_KEY', '418762272845821'),
    'api_secret' => env('CLOUDINARY_API_SECRET', 'RxTtLIOaSf18apO-LP9XUsVBHgM'),
    'secure' => true, // Luôn sử dụng HTTPS
];

// Kiểm tra xem có đủ thông tin cấu hình không
if (empty($cloudinary_config['cloud_name']) || 
    empty($cloudinary_config['api_key']) || 
    empty($cloudinary_config['api_secret'])) {
    
    error_log("Cloudinary configuration is incomplete. Please check .env file.");
    define('CLOUDINARY_ENABLED', false);
} else {
    // Khởi tạo cấu hình Cloudinary
    Configuration::instance($cloudinary_config);
    define('CLOUDINARY_ENABLED', true);
}

// Định nghĩa các hằng số cho Cloudinary
define('CLOUDINARY_FOLDER_PAYMENT_PROOFS', 'rtk_web/payment_proofs');
define('CLOUDINARY_TRANSFORMATION_THUMB', 'w_300,h_300,c_limit,q_auto,f_auto');
define('CLOUDINARY_TRANSFORMATION_FULL', 'w_1200,h_1200,c_limit,q_auto,f_auto');

/**
 * Upload file lên Cloudinary
 * @param string $file_path Đường dẫn file cần upload
 * @param string $public_id Public ID cho file (optional)
 * @param array $options Các tùy chọn upload khác
 * @return array Kết quả upload
 */
function cloudinary_upload($file_path, $public_id = null, $options = []) {
    if (!CLOUDINARY_ENABLED) {
        throw new Exception('Cloudinary is not properly configured');
    }
    
    try {
        // Default upload options for Cloudinary
        $upload_options = array_merge([
            'folder' => CLOUDINARY_FOLDER_PAYMENT_PROOFS,
            'resource_type' => 'image',
        ], $options);
        
        if ($public_id) {
            $upload_options['public_id'] = $public_id;
        }
        
        $uploadApi = new UploadApi();
        // Perform upload and return as array
        $apiResponse = $uploadApi->upload($file_path, $upload_options);
        // Convert ApiResponse object to associative array
        return json_decode(json_encode($apiResponse), true);
        
    } catch (Exception $e) {
        error_log("Cloudinary upload error: " . $e->getMessage());
        throw new Exception('Failed to upload image to Cloudinary: ' . $e->getMessage());
    }
}

/**
 * Xóa file trên Cloudinary
 * @param string $public_id Public ID của file cần xóa
 * @return array Kết quả xóa
 */
function cloudinary_delete($public_id) {
    if (!CLOUDINARY_ENABLED) {
        throw new Exception('Cloudinary is not properly configured');
    }
    
    try {
        $uploadApi = new UploadApi();
        // Perform deletion and return as array
        $apiResponse = $uploadApi->destroy($public_id);
        // Convert ApiResponse object to associative array
        return json_decode(json_encode($apiResponse), true);
    } catch (Exception $e) {
        error_log("Cloudinary delete error: " . $e->getMessage());
        throw new Exception('Failed to delete image from Cloudinary: ' . $e->getMessage());
    }
}

/**
 * Tạo URL từ public_id với transformation
 * @param string $public_id Public ID của file
 * @param string $transformation Transformation string
 * @return string URL đầy đủ
 */
function cloudinary_url($public_id, $transformation = '') {
    if (!CLOUDINARY_ENABLED) {
        return '';
    }
    
    $cloud_name = env('CLOUDINARY_CLOUD_NAME');
    $base_url = "https://res.cloudinary.com/{$cloud_name}/image/upload/";
    
    if ($transformation) {
        $base_url .= $transformation . '/';
    }
    
    return $base_url . $public_id;
}

/**
 * Kiểm tra xem Cloudinary có được cấu hình đúng không
 * @return bool
 */
function is_cloudinary_configured() {
    return CLOUDINARY_ENABLED;
}
