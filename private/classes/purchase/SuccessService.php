<?php
/**
 * Success Service
 * 
 * Handles logic related to the purchase/payment success page
 */
class SuccessService {
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        require_once dirname(dirname(__DIR__)) . '/config/database.php';
        $this->db = new Database();
    }

    /**
     * Get success page details
     * 
     * Retrieves information to be displayed on the success page
     * based on registration ID or transaction
     * 
     * @param int $registration_id Registration ID
     * @return array Success page details
     */
    public function getSuccessPageDetails($registration_id) {
        try {
            // Connect to the database
            $conn = $this->db->getConnection();
            
            // Query to get registration details
            $sql = "SELECT r.registration_id, r.user_id, r.package_id, r.quantity, 
                r.province, r.status, r.created_at, r.total_price,
                p.name AS package_name
                FROM registrations r
                LEFT JOIN packages p ON r.package_id = p.id
                WHERE r.registration_id = :registration_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'error' => 'registration_not_found',
                    'message' => 'Không tìm thấy thông tin đăng ký'
                ];
            }
            
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if there's a related transaction
            $sql_transaction = "SELECT t.id AS transaction_id, t.amount, 
                t.status AS payment_status, t.created_at AS payment_date
                FROM transactions t
                WHERE t.registration_id = :registration_id
                ORDER BY t.created_at DESC LIMIT 1";
            
            $stmt_transaction = $conn->prepare($sql_transaction);
            $stmt_transaction->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt_transaction->execute();
            
            $transaction = null;
            if ($stmt_transaction->rowCount() > 0) {
                $transaction = $stmt_transaction->fetch(PDO::FETCH_ASSOC);
            }
            
            // Map status to human-readable text
            $status_map = [
                'pending' => 'Chờ thanh toán',
                'processing' => 'Đang xử lý',
                'completed' => 'Đã hoàn thành',
                'cancelled' => 'Đã hủy',
                'trial' => 'Dùng thử'
            ];
            
            // Return combined data
            return [
                'success' => true,
                'data' => [
                    'registration_id' => $registration['registration_id'],
                    'package_name' => $registration['package_name'],
                    'quantity' => $registration['quantity'],
                    'price' => $registration['total_price'],
                    'status' => $status_map[$registration['status']] ?? $registration['status'],
                    'created_at' => $registration['created_at'],
                    'province' => $registration['province'],
                    'payment_status' => $transaction ? ($status_map[$transaction['payment_status']] ?? $transaction['payment_status']) : 'Đang xử lý',
                    'payment_date' => $transaction ? $transaction['payment_date'] : null,
                    'transaction_id' => $transaction ? $transaction['transaction_id'] : null
                ]
            ];
        } catch (PDOException $e) {
            error_log("SuccessService::getSuccessPageDetails Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'database_error',
                'message' => 'Đã có lỗi xảy ra khi truy vấn dữ liệu'
            ];
        }
    }
    
    /**
     * Save success data to session
     * 
     * @param array $data Success page data to save
     * @return void
     */
    public function saveSuccessDataToSession($data) {
        $_SESSION['purchase_success'] = true;
        $_SESSION['purchase_details'] = $data;
    }
    
    /**
     * Clear success data from session
     * 
     * @return void
     */
    public function clearSuccessDataFromSession() {
        unset($_SESSION['purchase_success']);
        unset($_SESSION['purchase_details']);
    }
}
