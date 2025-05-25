<?php
// PaymentService.php
// Tách logic xử lý database và tạo VietQR cho trang payment

require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/utils/vietqr_helper.php';
require_once PROJECT_ROOT_PATH . '/private/utils/payment_helper.php';

class PaymentService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Lấy thông tin thanh toán cho trang payment
     * @param int $registration_id
     * @param int $user_id
     * @param float $session_total_price
     * @return array
     */
    public function getPaymentPageDetails($registration_id, $user_id, $session_total_price) {
        // Gọi lại logic từ getPaymentPageDetails helper nếu có, hoặc chuyển logic vào đây
        // Ví dụ: kiểm tra trạng thái đơn hàng, lấy thông tin gói, số lượng, tỉnh thành, ...
        // Trả về mảng ['success' => true, 'data' => [...]] hoặc ['success' => false, 'error' => '...']
        
        // Chuyển logic từ payment_helper.php vào đây
        try {
            // --- Fetch Registration Details ---
            $stmt = $this->conn->prepare("SELECT id, package_id, location_id, num_account, total_price, base_price, vat_percent, vat_amount FROM registration WHERE id = :id AND user_id = :user_id AND status = 'pending'");
            $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $registration_details = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registration_details) {
                error_log("PaymentService: Pending registration ID $registration_id not found for user $user_id or status not pending.");
                return ['success' => false, 'error' => 'invalid_order_state'];
            }

            // --- Verify Session Price with DB Price ---
            $db_total_price = (float)$registration_details['total_price'];
            if (abs($db_total_price - $session_total_price) > 0.01) {
                error_log("PaymentService: Price mismatch session ({$session_total_price}) vs DB ({$db_total_price}) for registration ID $registration_id.");
                $verified_total_price = $db_total_price;
                $_SESSION['pending_total_price'] = $verified_total_price; // Update session
            } else {
                $verified_total_price = $session_total_price;
            }

            // --- Fetch Package and Location Details ---
            // Assuming Package and Location classes are available or their logic is integrated here
            // For simplicity, let's assume you have methods in this service or direct queries
            $package_stmt = $this->conn->prepare("SELECT name FROM package WHERE id = :id");
            $package_stmt->bindParam(':id', $registration_details['package_id'], PDO::PARAM_INT);
            $package_stmt->execute();
            $package_details = $package_stmt->fetch(PDO::FETCH_ASSOC);

            $location_stmt = $this->conn->prepare("SELECT province FROM location WHERE id = :id");
            $location_stmt->bindParam(':id', $registration_details['location_id'], PDO::PARAM_INT);
            $location_stmt->execute();
            $location_details = $location_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$package_details || !$location_details) {
                error_log("PaymentService: Could not fetch package or location details for registration ID $registration_id.");
                return ['success' => false, 'error' => 'data_fetch_error'];
            }

            return [
                'success' => true,
                'data' => [
                    'registration_id' => $registration_details['id'],
                    'package_name' => $package_details['name'],
                    'quantity' => $registration_details['num_account'],
                    'province' => $location_details['province'],
                    'verified_total_price' => $verified_total_price,
                    'base_price_from_registration' => $registration_details['base_price'],
                    'vat_percent_from_registration' => $registration_details['vat_percent'],
                    'vat_amount_from_registration' => $registration_details['vat_amount']
                ]
            ];

        } catch (PDOException $e) {
            error_log("PaymentService DB error: " . $e->getMessage());
            return ['success' => false, 'error' => 'database_error'];
        } catch (Exception $e) {
            error_log("PaymentService general error: " . $e->getMessage());
            return ['success' => false, 'error' => 'unknown_error'];
        }
    }

    /**
     * Tạo VietQR payload và URL ảnh QR
     * @param float $amount
     * @param string $order_description
     * @return array ['payload' => ..., 'image_url' => ...]
     */
    public function generateVietQR($amount, $order_description) {
        $payload = generate_vietqr_payload($amount, $order_description);
        $image_url = sprintf(
            "https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s",
            VIETQR_BANK_ID,
            VIETQR_ACCOUNT_NO,
            defined('VIETQR_IMAGE_TEMPLATE') ? VIETQR_IMAGE_TEMPLATE : 'compact2',
            $amount,
            urlencode($order_description),
            urlencode(VIETQR_ACCOUNT_NAME)
        );
        return [
            'payload' => $payload,
            'image_url' => $image_url
        ];
    }    /**
     * Cập nhật giá trị tổng thanh toán vào transaction_history
     * @param int $registration_id
     * @param int $user_id
     * @param float $total_amount Tổng thanh toán (bao gồm VAT)
     * @return bool
     */
    public function updateTransactionHistoryAmount($registration_id, $user_id, $total_amount) {
        try {
            // First check if this is related to a renewal where there might be multiple transactions
            $is_renewal = false;
            $check_sql = "SELECT transaction_type FROM transaction_history 
                         WHERE registration_id = :registration_id 
                         AND user_id = :user_id 
                         AND status = 'pending' 
                         LIMIT 1";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $transaction_info = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction_info && $transaction_info['transaction_type'] === 'renewal') {
                $is_renewal = true;
            }
            
            // Update transaction amount based on whether it's a renewal or purchase
            $sql = "UPDATE transaction_history 
                    SET amount = :amount, updated_at = NOW() 
                    WHERE registration_id = :registration_id 
                    AND user_id = :user_id 
                    AND status = 'pending'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':amount', $total_amount);
            $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("PaymentService Error: Could not update transaction_history amount: " . $e->getMessage());
            return false;
        }
    }
}
