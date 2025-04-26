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

    // --- Add other transaction-related methods as needed ---
    // Example: getTransactionById, createTransaction, updateTransactionStatus, etc.

}

?>
