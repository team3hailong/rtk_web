<?php
/**
 * CloudinaryService
 * Service để xử lý upload, xóa và quản lý hình ảnh trên Cloudinary
 */

require_once dirname(__DIR__) . '/config/cloudinary.php';

class CloudinaryService {
    
    /**
     * Upload file lên Cloudinary
     * @param string $file_path Đường dẫn đến file cần upload
     * @param array $metadata Metadata bổ sung (registration_id, user_id, etc.)
     * @return array Thông tin file đã upload
     */
    public function uploadPaymentProof($file_path, $metadata = []) {
        if (!is_cloudinary_configured()) {
            throw new Exception('Cloudinary is not configured properly');
        }
        
        // Validate file
        if (!file_exists($file_path)) {
            throw new Exception('File does not exist: ' . $file_path);
        }
        
        // Generate unique public_id
        $registration_id = $metadata['registration_id'] ?? 'unknown';
        $timestamp = time();
        $public_id = "payment_proof_reg_{$registration_id}_{$timestamp}";
        
        try {
            // Prepare upload options: resize with limit and automatic format/quality
            // Prepare upload options: store original image; transformations applied on retrieval
            $upload_options = [
                'public_id' => $public_id,
                'folder' => CLOUDINARY_FOLDER_PAYMENT_PROOFS,
                'resource_type' => 'image',
                'context' => $this->buildContext($metadata)
            ];
            
            $result = cloudinary_upload($file_path, $public_id, $upload_options);
            
            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'url' => $result['url'],
                'bytes' => $result['bytes'],
                'format' => $result['format'],
                'width' => $result['width'],
                'height' => $result['height'],
                'created_at' => $result['created_at']
            ];
            
        } catch (Exception $e) {
            error_log("CloudinaryService upload error: " . $e->getMessage());
            throw new Exception('Failed to upload to Cloudinary: ' . $e->getMessage());
        }
    }
    
    /**
     * Xóa file trên Cloudinary
     * @param string $public_id Public ID của file cần xóa
     * @return bool Kết quả xóa
     */
    public function deletePaymentProof($public_id) {
        if (!is_cloudinary_configured()) {
            throw new Exception('Cloudinary is not configured properly');
        }
        
        if (empty($public_id)) {
            return false;
        }
        
        try {
            $result = cloudinary_delete($public_id);
            return isset($result['result']) && $result['result'] === 'ok';
        } catch (Exception $e) {
            error_log("CloudinaryService delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tạo URL với transformation
     * @param string $public_id Public ID của file
     * @param string $size Size preset ('thumb', 'medium', 'full')
     * @return string URL
     */
    public function getImageUrl($public_id, $size = 'medium') {
        if (!is_cloudinary_configured() || empty($public_id)) {
            return '';
        }
        
        $transformations = [
            'thumb' => 'w_300,h_300,c_limit,q_auto,f_auto',
            'medium' => 'w_600,h_600,c_limit,q_auto,f_auto',
            'full' => 'w_1200,h_1200,c_limit,q_auto,f_auto'
        ];
        
        $transformation = $transformations[$size] ?? $transformations['medium'];
        return cloudinary_url($public_id, $transformation);
    }
    
    /**
     * Xây dựng context metadata cho Cloudinary
     * @param array $metadata
     * @return string
     */
    private function buildContext($metadata) {
        $context_parts = [];
        
        if (isset($metadata['registration_id'])) {
            $context_parts[] = "registration_id={$metadata['registration_id']}";
        }
        
        if (isset($metadata['user_id'])) {
            $context_parts[] = "user_id={$metadata['user_id']}";
        }
        
        if (isset($metadata['transaction_id'])) {
            $context_parts[] = "transaction_id={$metadata['transaction_id']}";
        }
        
        $context_parts[] = "upload_time=" . date('Y-m-d_H:i:s');
        
        return implode('|', $context_parts);
    }
    
    /**
     * Validate file trước khi upload
     * @param array $uploaded_file $_FILES array
     * @return bool
     */
    public function validateUploadedFile($uploaded_file) {
        // Kiểm tra lỗi upload
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $uploaded_file['error']);
        }
        
        // Kiểm tra kích thước
        if ($uploaded_file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File exceeds maximum size limit (' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB).');
        }
        
        // Kiểm tra MIME type
        $file_mime_type = mime_content_type($uploaded_file['tmp_name']);
        if (!in_array($file_mime_type, ALLOWED_MIME_TYPES)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF are allowed.');
        }
        
        // Kiểm tra extension
        $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
            throw new Exception('Invalid file extension. Only JPG, PNG, GIF are allowed.');
        }
        
        return true;
    }
    
    /**
     * Lấy public_id từ Cloudinary URL
     * @param string $cloudinary_url URL từ Cloudinary
     * @return string|null Public ID
     */
    public static function extractPublicIdFromUrl($cloudinary_url) {
        if (empty($cloudinary_url)) {
            return null;
        }
        
        // Pattern để extract public_id từ Cloudinary URL
        $pattern = '/\/v\d+\/(.+?)(\.[^.]+)?$/';
        if (preg_match($pattern, $cloudinary_url, $matches)) {
            return $matches[1];
        }
        
        // Pattern backup cho URL không có version
        $pattern2 = '/\/upload\/(?:[^\/]+\/)*(.+?)(\.[^.]+)?$/';
        if (preg_match($pattern2, $cloudinary_url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
