<?php

// InvoiceService.php
// Service class to handle database operations for completed_export_invoice page

require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

class InvoiceService {
    public $conn; // Changed to public to allow direct access

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Fetch transaction and user registration info by transaction ID
     * @param int $tx_id
     * @return array|false
     */
    public function getTransactionInfo(int $tx_id) {
        $stmt = $this->conn->prepare(
            'SELECT th.id as transaction_id, th.created_at,
                    p.name as package_name, r.num_account, r.total_price,
                    u.company_name, u.tax_code, u.company_address, u.email
             FROM transaction_history th
             LEFT JOIN registration r ON th.registration_id = r.id
             LEFT JOIN package p ON r.package_id = p.id
             LEFT JOIN user u ON th.user_id = u.id
             WHERE th.id = ?'
        );
        $stmt->execute([$tx_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch invoice record by transaction ID
     * @param int $tx_id
     * @return array|false
     */
    public function getInvoice(int $tx_id) {
        $stmt = $this->conn->prepare('SELECT * FROM invoice WHERE transaction_history_id = ?');
        $stmt->execute([$tx_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a transaction belongs to a user
     * @param int $tx_id
     * @param int $user_id
     * @return bool
     */
    public function checkOwnership(int $tx_id, int $user_id): bool {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM transaction_history WHERE id = ? AND user_id = ?');
        $stmt->execute([$tx_id, $user_id]);
        return $stmt->fetchColumn() > 0;
    }    /**
     * Get company info for a user
     * @param int $user_id
     * @return array|false
     */
    public function getUserInfo(int $user_id) {
        $stmt = $this->conn->prepare('SELECT company_name, tax_code FROM user WHERE id = ?');
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a transaction has the status 'completed'
     * @param int $tx_id
     * @return bool
     */
    public function isTransactionCompleted(int $tx_id): bool {
        $stmt = $this->conn->prepare('SELECT status FROM transaction_history WHERE id = ?');
        $stmt->execute([$tx_id]);
        $status = $stmt->fetchColumn();
        return strtolower($status) === 'completed';
    }

    /**
     * Check if invoice exists for a transaction
     * @param int $tx_id
     * @return bool
     */
    public function invoiceExists(int $tx_id): bool {
        $stmt = $this->conn->prepare('SELECT id FROM invoice WHERE transaction_history_id = ?');
        $stmt->execute([$tx_id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if invoice is allowed for the registration associated with a transaction
     * @param int $tx_id
     * @return bool
     */
    public function isInvoiceAllowedForTransaction(int $tx_id): bool {
        $stmt = $this->conn->prepare(
            'SELECT r.invoice_allowed
             FROM transaction_history th
             JOIN registration r ON th.registration_id = r.id
             WHERE th.id = ?'
        );
        $stmt->execute([$tx_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['invoice_allowed'] == 1;
    }

    /**
     * Create a new pending invoice for a transaction
     * @param int $tx_id
     * @return void
     */
    public function createInvoice(int $tx_id): void {
        $stmt = $this->conn->prepare('INSERT INTO invoice (transaction_history_id, status, created_at) VALUES (?, "pending", NOW())');
        $stmt->execute([$tx_id]);
        $invoice_id = $this->conn->lastInsertId();
        // --- Ghi log vào activity_logs ---
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $new_values = json_encode([
                'transaction_history_id' => $tx_id
            ], JSON_UNESCAPED_UNICODE);
            $notify_content = 'Yêu cầu xuất hóa đơn cho giao dịch #' . $tx_id;
            $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at)
                        VALUES (:user_id, 'request_invoice', 'invoice', :entity_id, :ip_address, :user_agent, :new_values, :notify_content, NOW())";
            $stmt_log = $this->conn->prepare($sql_log);
            $stmt_log->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_log->bindParam(':entity_id', $invoice_id, PDO::PARAM_INT);
            $stmt_log->bindParam(':ip_address', $ip_address);
            $stmt_log->bindParam(':user_agent', $user_agent);
            $stmt_log->bindParam(':new_values', $new_values);
            $stmt_log->bindParam(':notify_content', $notify_content);
            $stmt_log->execute();
        } catch (Exception $e) {
            error_log('Lỗi ghi activity_logs khi xuất hóa đơn: ' . $e->getMessage());
        }
    }
}
