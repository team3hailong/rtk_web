<?php
/**
 * SupportRequest Class
 * Handles support request operations including creation, retrieval, and update
 */
class SupportRequest {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    /**
     * Create a new support request
     * 
     * @param int $userId User ID submitting the request
     * @param string $subject Request subject
     * @param string $message Request message content
     * @param string $category Request category
     * @return array Result with success status and request ID or error message
     */
    public function createRequest($userId, $subject, $message, $category = 'other') {
        try {
            $sql = "INSERT INTO support_requests (user_id, subject, message, category, status)
                    VALUES (:user_id, :subject, :message, :category, 'pending')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            
            $stmt->execute();
            $requestId = $this->conn->lastInsertId();
            
            // Log the activity
            $this->logSupportActivity($userId, 'create_support_request', 'support_requests', $requestId);
            
            return ['success' => true, 'request_id' => $requestId];
        } catch (PDOException $e) {
            error_log("Error creating support request: " . $e->getMessage());
            return ['success' => false, 'error' => 'Đã xảy ra lỗi khi gửi yêu cầu hỗ trợ.'];
        }
    }
    
    /**
     * Get support requests by user ID
     * 
     * @param int $userId User ID
     * @return array List of support requests
     */
    public function getRequestsByUser($userId) {
        try {
            $sql = "SELECT id, subject, message, category, status, admin_response, 
                           created_at, updated_at
                    FROM support_requests 
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $requests;
        } catch (PDOException $e) {
            error_log("Error fetching support requests: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get company information
     * 
     * @return array|null Company information or null if not found
     */
    public function getCompanyInfo() {
        try {
            $sql = "SELECT * FROM company_info ORDER BY id DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $companyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            return $companyInfo ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching company info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log support activity
     * 
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     */
    private function logSupportActivity($userId, $action, $entityType, $entityId) {
        try {
            // Tạo nội dung thông báo dựa theo action
            $notify_content = '';
            switch ($action) {
                case 'create_support_request':
                    $notify_content = 'Tạo yêu cầu hỗ trợ mới #' . $entityId;
                    break;
                case 'update_support_request':
                    $notify_content = 'Cập nhật yêu cầu hỗ trợ #' . $entityId;
                    break;
                case 'add_support_response':
                    $notify_content = 'Thêm phản hồi cho yêu cầu hỗ trợ #' . $entityId;
                    break;
                default:
                    $notify_content = 'Hoạt động liên quan đến hỗ trợ #' . $entityId;
            }
            
            $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, notify_content, created_at)
                    VALUES (:user_id, :action, :entity_type, :entity_id, :notify_content, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt->bindParam(':entity_type', $entityType, PDO::PARAM_STR);
            $stmt->bindParam(':entity_id', $entityId, PDO::PARAM_STR);
            $stmt->bindParam(':notify_content', $notify_content, PDO::PARAM_STR);
            
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error logging support activity: " . $e->getMessage());
        }
    }
}
