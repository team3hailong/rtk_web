<?php
// RetailInvoiceService.php
// Service class to handle retail invoices (Hóa đơn bán lẻ) data retrieval and processing

require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Transaction.php';

class RetailInvoiceService {
    private $db;
    private $pdo;
    private $transactionHandler;

    public function __construct() {
        $this->db = new Database();
        $this->pdo = $this->db->getConnection();
        $this->transactionHandler = new Transaction($this->db);
    }    /**
     * Get retail invoice data for transactions
     * @param array $tx_ids Array of transaction IDs
     * @param int $user_id User ID
     * @return array Array of retail invoice data
     */    public function getRetailInvoicesData(array $tx_ids, int $user_id): array {
        $retail_invoices = [];

        // Lấy thông tin người dùng/công ty
        $user_info = $this->getUserInfo($user_id);

        foreach ($tx_ids as $tx_id) {
            $tx = $this->transactionHandler->getTransactionByIdAndUser($tx_id, $user_id);
            // Chỉ xử lý các giao dịch đã hoàn thành (có status là 'completed')
            if (!$tx || strtolower($tx['status']) !== 'completed') continue;
            
            // Lấy thêm thông tin chi tiết từ bảng registration nếu có
            $registration_details = $this->getRegistrationDetails($tx['registration_id'] ?? null);            // Kiểm tra nếu có VAT hoặc là mua cho công ty thì không cho phép xuất hóa đơn bán lẻ
            $has_vat = false;
            if ($registration_details) {
                // Kiểm tra các điều kiện:
                // 1. Có % VAT > 0
                // 2. Loại mua hàng là 'company'
                // 3. Đã đánh dấu trong DB là giao dịch này được phép xuất hóa đơn VAT (invoice_allowed = 1)
                if ((isset($registration_details['vat_percent']) && $registration_details['vat_percent'] > 0) || 
                    (isset($registration_details['purchase_type']) && $registration_details['purchase_type'] === 'company') ||
                    (isset($registration_details['invoice_allowed']) && $registration_details['invoice_allowed'] == 1)) {
                    $has_vat = true;
                }
            }
            
            // Prepare invoice data (enhanced retail invoice)
            $retail_invoices[] = [
                'id' => $tx['id'],
                'created_at' => $tx['created_at'],
                'amount' => $tx['amount'],
                'type' => $tx['transaction_type'],
                'method' => $tx['payment_method'],
                'user_info' => $user_info,
                'registration_details' => $registration_details,
                'has_vat' => $has_vat // Thêm trường này để biết giao dịch có VAT hay không
            ];
        }
        
        return $retail_invoices;
    }

    /**
     * Get user information
     * @param int $user_id User ID
     * @return array User information
     */
    private function getUserInfo(int $user_id): array {
        $user_info = [];
        $user_stmt = $this->pdo->prepare("SELECT username, email, phone, is_company, company_name, tax_code, company_address FROM user WHERE id = :user_id");
        $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stmt->execute();
        $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user_info ?: [];
    }

    /**
     * Get registration details
     * @param int|null $registration_id Registration ID
     * @return array|null Registration details
     */    private function getRegistrationDetails(?int $registration_id): ?array {
        if (empty($registration_id)) {
            return null;
        }
        
        $reg_stmt = $this->pdo->prepare("SELECT r.*, p.name as package_name, p.duration_text as duration, l.province,
                                      r.vat_percent, r.vat_amount, r.purchase_type, r.invoice_allowed
                                      FROM registration r 
                                      LEFT JOIN package p ON r.package_id = p.id
                                      LEFT JOIN location l ON r.location_id = l.id
                                      WHERE r.id = :reg_id");
        $reg_stmt->bindParam(':reg_id', $registration_id, PDO::PARAM_INT);
        $reg_stmt->execute();
        return $reg_stmt->fetch(PDO::FETCH_ASSOC);
    }
}
