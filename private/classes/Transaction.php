<?php

class Transaction {
    private $db; // Database connection object

    /**
     * Constructor
     * @param Database $db An instance of the Database class.
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Fetches transaction history for a specific user.
     *
     * @param int $userId The ID of the user whose transactions to fetch.
     * @return array An array of transactions, or an empty array if none found or on error.
     */
    public function getTransactionsByUserId(int $userId): array {
        if ($userId <= 0) {
            // Optionally log an error or return empty array
            return [];
        }

        $pdo = $this->db->getConnection(); // Get the PDO connection object
        if (!$pdo) {
            error_log("Database connection failed in getTransactionsByUserId.");
            return []; // Return empty if connection failed
        }

        $sql = "SELECT
                    th.id,
                    th.registration_id,
                    th.user_id,
                    th.transaction_type,
                    th.amount,
                    th.status,
                    th.payment_method,
                    th.created_at,
                    th.updated_at,
                    r.rejection_reason
                FROM
                    transaction_history th
                LEFT JOIN
                    registration r ON th.registration_id = r.id
                WHERE
                    th.user_id = :user_id
                ORDER BY
                    th.created_at DESC"; // Order by most recent first

        try {
            $stmt = $pdo->prepare($sql); // Use PDO's prepare method
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT); // Use PDOStatement's bindParam
            $stmt->execute(); // Execute the prepared statement
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Use PDOStatement's fetchAll
            return $results ? $results : []; // Return results or empty array
        } catch (PDOException $e) {
            // Log the error (implement proper logging)
            error_log("Database Error in getTransactionsByUserId: " . $e->getMessage());
            return []; // Return empty array on error
        } catch (Exception $e) {
            // Log other potential errors
            error_log("Error in getTransactionsByUserId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches transaction history for a specific user with pagination.
     *
     * @param int $userId The ID of the user whose transactions to fetch.
     * @param int $currentPage The current page number (default: 1).
     * @param int $perPage The number of items per page (default: 10).
     * @param string $filter The filter to apply ('all', 'completed', 'pending', 'failed').
     * @return array An array containing transactions and pagination metadata.
     */
    public function getTransactionsByUserIdWithPagination(int $userId, int $currentPage = 1, int $perPage = 10, string $filter = 'all'): array {
        if ($userId <= 0) {
            return ['transactions' => [], 'pagination' => $this->createEmptyPaginationData($currentPage, $perPage)];
        }

        $pdo = $this->db->getConnection();
        if (!$pdo) {
            error_log("Database connection failed in getTransactionsByUserIdWithPagination.");
            return ['transactions' => [], 'pagination' => $this->createEmptyPaginationData($currentPage, $perPage)];
        }

        // Calculate offset
        $offset = ($currentPage - 1) * $perPage;

        // Base SQL for both count and data queries
        $baseSql = "FROM
                    transaction_history th
                LEFT JOIN
                    registration r ON th.registration_id = r.id
                WHERE
                    th.user_id = :user_id";

        // Add filter condition if not 'all'
        if ($filter !== 'all') {
            $baseSql .= " AND th.status = :status";
        }

        // First, get total count for pagination
        $countSql = "SELECT COUNT(*) as total " . $baseSql;
        
        try {
            $stmt = $pdo->prepare($countSql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($filter !== 'all') {
                $stmt->bindParam(':status', $filter, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calculate pagination metadata
            $totalPages = ceil($totalCount / $perPage);
            
            // Ensure current page is valid
            if ($currentPage > $totalPages && $totalPages > 0) {
                $currentPage = $totalPages;
                $offset = ($currentPage - 1) * $perPage;
            }
              // Now get the actual data with limit and offset
            $dataSql = "SELECT
                    th.id,
                    th.registration_id,
                    th.user_id,
                    th.transaction_type,
                    th.amount,
                    th.status,
                    th.payment_method,
                    th.payment_image,
                    th.created_at,
                    th.updated_at,
                    r.rejection_reason,
                    r.invoice_allowed
                " . $baseSql . "
                ORDER BY
                    th.created_at DESC
                LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($dataSql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($filter !== 'all') {
                $stmt->bindParam(':status', $filter, PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create pagination metadata
            $pagination = [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'total_pages' => $totalPages
            ];
            
            return [
                'transactions' => $transactions ?: [],
                'pagination' => $pagination
            ];
            
        } catch (PDOException $e) {
            error_log("Database Error in getTransactionsByUserIdWithPagination: " . $e->getMessage());
            return ['transactions' => [], 'pagination' => $this->createEmptyPaginationData($currentPage, $perPage)];
        } catch (Exception $e) {
            error_log("Error in getTransactionsByUserIdWithPagination: " . $e->getMessage());
            return ['transactions' => [], 'pagination' => $this->createEmptyPaginationData($currentPage, $perPage)];
        }
    }

    /**
     * Creates empty pagination data structure
     * 
     * @param int $currentPage The current page
     * @param int $perPage Items per page
     * @return array Empty pagination data
     */
    private function createEmptyPaginationData($currentPage, $perPage): array {
        return [
            'total' => 0,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => 0
        ];
    }

    /**
     * Helper: Get status display text and class for a transaction status
     * @param string $status
     * @return array
     */
    public static function getTransactionStatusDisplay($status) {
        switch (strtolower($status)) {
            case 'completed':
                return ['text' => 'Hoàn thành', 'class' => 'status-completed'];
            case 'pending':
                return ['text' => 'Chờ xử lý', 'class' => 'status-pending'];
            case 'failed':
                return ['text' => 'Thất bại', 'class' => 'status-failed'];
            case 'cancelled':
                return ['text' => 'Đã hủy', 'class' => 'status-cancelled'];
            case 'refunded':
                return ['text' => 'Đã hoàn tiền', 'class' => 'status-refunded'];
            default:
                return ['text' => 'Không xác định', 'class' => 'status-unknown'];
        }
    }

    /**
     * Update transaction status and handle related operations like voucher usage
     * 
     * @param int $transactionId ID of the transaction to update
     * @param string $status New status (completed, failed, cancelled, etc)
     * @param bool $updateVoucher Whether to update voucher usage count when status is completed
     * @return bool Success/failure of the operation
     */
    public function updateTransactionStatus($transactionId, $status, $updateVoucher = true) {
        if (!in_array(strtolower($status), ['completed', 'pending', 'failed', 'cancelled', 'refunded'])) {
            return false;
        }
        
        try {
            $pdo = $this->db->getConnection();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Get transaction details first to check for voucher_id
            $stmt = $pdo->prepare("SELECT voucher_id FROM transaction_history WHERE id = :id");
            $stmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update transaction status
            $updateStmt = $pdo->prepare("
                UPDATE transaction_history 
                SET 
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
            $updateStmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
            $updated = $updateStmt->execute();
              // If transaction is completed and has a voucher, increase voucher usage count
            if ($status == 'completed' && $updateVoucher && !empty($transaction['voucher_id'])) {
                require_once dirname(__FILE__) . '/Voucher.php';
                $voucher = new Voucher($this->db);
                $voucher->incrementUsage($transaction['voucher_id']);
            }
            
            // When status is completed, calculate referral commission
            if ($status == 'completed') {
                require_once dirname(__FILE__) . '/Referral.php';
                $referral = new Referral($this->db);
                $referral->calculateCommission($transactionId);
            }
            
            // Commit transaction
            $pdo->commit();
            
            return $updated;
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error updating transaction status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a transaction by ID and user ID
     * @param int $transactionId
     * @param int $userId
     * @return array|false
     */
    public function getTransactionByIdAndUser(int $transactionId, int $userId) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT th.id, th.registration_id, th.user_id, th.transaction_type, th.amount, th.status, th.payment_method, th.created_at, th.updated_at FROM transaction_history th WHERE th.id = :id AND th.user_id = :user_id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- Add other transaction-related methods as needed ---
    // Example: getTransactionById, createTransaction, updateTransactionStatus, etc.

}

?>
