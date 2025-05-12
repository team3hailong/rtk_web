<?php
/**
 * PaymentProofService
 * 
 * Service class for handling payment proof uploads and retrievals
 */

// Include Database class
require_once dirname(dirname(__FILE__)) . '/Database.php';

class PaymentProofService {
    private $conn;
    private $base_url;
    private $project_root_path;

    /**
     * Constructor
     */
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->base_url = BASE_URL;
        $this->project_root_path = PROJECT_ROOT_PATH;
    }
    
    /**
     * Get Payment Proof Information by Registration ID
     * 
     * @param int $registration_id The registration ID
     * @param int $user_id The user ID
     * @return array Payment proof information [success, data[existing_proof_image, existing_proof_url]]
     */
    public function getPaymentProofByRegistrationId(int $registration_id, int $user_id): array {
        $result = [
            'success' => false,
            'data' => [
                'existing_proof_image' => null,
                'existing_proof_url' => null,
            ],
            'error' => null
        ];
        
        try {
            $sql_get_proof = "SELECT payment_image FROM transaction_history 
                             WHERE registration_id = :registration_id 
                             AND user_id = :user_id
                             LIMIT 1";
            $stmt_get_proof = $this->conn->prepare($sql_get_proof);
            $stmt_get_proof->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt_get_proof->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_get_proof->execute();
            $existing_proof_image = $stmt_get_proof->fetchColumn();

            if ($existing_proof_image) {
                $upload_dir_relative = '/uploads/payment_proofs/';
                $existing_proof_url = $this->base_url . '/public' . $upload_dir_relative . htmlspecialchars($existing_proof_image);
                
                $result['data']['existing_proof_image'] = $existing_proof_image;
                $result['data']['existing_proof_url'] = $existing_proof_url;
                $result['success'] = true;
            } else {
                $result['success'] = true; // Still successful, just no proof found
            }

        } catch (Exception $e) {
            error_log("Error fetching existing payment proof: " . $e->getMessage());
            $result['error'] = "Database error occurred: " . $e->getMessage();
        }
        
        return $result;
    }
      // Chúng ta không cần hàm validateRegistrationId nữa, vì chúng ta xử lý trực tiếp trong trang upload_proof.php
    
    /**
     * Check if registration belongs to user
     * 
     * @param int $registration_id Registration ID
     * @param int $user_id User ID
     * @return bool True if registration belongs to user
     */
    public function registrationBelongsToUser(int $registration_id, int $user_id): bool {
        try {
            $stmt = $this->conn->prepare("SELECT 1 FROM registration WHERE id = :registration_id AND user_id = :user_id");
            $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn() ? true : false;
        } catch (Exception $e) {
            error_log("Error checking registration ownership: " . $e->getMessage());
            return false;
        }
    }
}
