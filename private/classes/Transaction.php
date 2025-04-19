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
                    id,
                    registration_id,
                    user_id,
                    transaction_type,
                    amount,
                    status,
                    payment_method,
                    created_at,
                    updated_at
                FROM
                    transaction_history
                WHERE
                    user_id = :user_id
                ORDER BY
                    created_at DESC"; // Order by most recent first

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

    // --- Add other transaction-related methods as needed ---
    // Example: getTransactionById, createTransaction, updateTransactionStatus, etc.

}

?>
