<?php

// InvoiceService.php
// Service class to handle database operations for completed_export_invoice page

require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';

class InvoiceService {
    private $conn;

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
    }

    /**
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
     * Create a new pending invoice for a transaction
     * @param int $tx_id
     * @return void
     */
    public function createInvoice(int $tx_id): void {
        $stmt = $this->conn->prepare('INSERT INTO invoice (transaction_history_id, status, created_at) VALUES (?, "pending", NOW())');
        $stmt->execute([$tx_id]);
    }
}
