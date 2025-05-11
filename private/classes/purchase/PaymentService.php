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
        // ...
        // (Bạn có thể copy logic từ payment_helper.php vào đây nếu muốn gom về service)
        return getPaymentPageDetails($registration_id, $user_id, $session_total_price);
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
    }
}
