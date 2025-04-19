<?php

// Ensure Database class is available if not autoloaded
// require_once __DIR__ . '/Database.php'; // Uncomment if needed

class RtkAccount {
    private $db;
    private $conn;
    private $log_file; // Add property for log file path

    public function __construct(Database $db) {
        // Define the log file path relative to this file's directory
        $this->log_file = dirname(__DIR__) . '/logs/app.log'; // Path: private/logs/app.log

        // Ensure the logs directory exists (basic check - Database class might also do this)
        $logDir = dirname($this->log_file);
        if (!is_dir($logDir)) {
             @mkdir($logDir, 0775, true);
        }

        $this->db = $db;
        $this->conn = $this->db->getConnection(); // getConnection already logs connection attempts/failures
        if ($this->conn === null) {
            // Log the specific failure point if constructor fails due to no connection
            $message = "[" . date('Y-m-d H:i:s') . "][RtkAccount Construct] Failed to get database connection.\n";
            error_log($message, 3, $this->log_file);
            throw new Exception("Failed to establish database connection in RtkAccount.");
        }
    }

    /**
     * Fetches simplified account details (ID, username, location, mountpoint name) for a given user ID.
     *
     * @param int $userId The ID of the user.
     * @return array An array of simplified account details.
     */
    public function getAccountsByUserId(int $userId): array {
        $accountsData = [];
        $logPrefix = "[" . date('Y-m-d H:i:s') . "][RtkAccount GetAccounts Simplified] "; // Updated prefix
        error_log($logPrefix . "Attempting to fetch simplified accounts for user ID: " . $userId . "\n", 3, $this->log_file); // Log start

        // Simplified SQL query joining necessary tables. Links user -> registration -> survey_account.
        $sql = "SELECT
                    sa.id AS survey_account_id,
                    sa.username_acc,
                    r.id AS registration_id,
                    r.status AS registration_status, -- Keep status for basic info
                    r.start_time, -- Keep for context
                    r.end_time,   -- Keep for context
                    l.province,
                    mp.mountpoint AS mountpoint_name
                    -- Removed mp.ip, mp.port for simplification
                FROM
                    registration r
                JOIN
                    survey_account sa ON r.id = sa.registration_id
                JOIN
                    location l ON r.location_id = l.id
                LEFT JOIN -- Use LEFT JOIN in case a location has no mount_point
                    mount_point mp ON l.id = mp.location_id
                WHERE
                    r.user_id = :user_id
                    AND r.deleted_at IS NULL -- Exclude soft-deleted registrations
                    AND sa.deleted_at IS NULL -- Exclude soft-deleted survey accounts
                ORDER BY
                    r.created_at DESC, sa.id"; // Simplified ORDER BY

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log($logPrefix . "Database query successful. Found " . count($results) . " rows for user ID: " . $userId . "\n", 3, $this->log_file);

            // Process results - simplified
            foreach ($results as $row) {
                 // Determine actual status based on dates and registration status
                 $status = $row['registration_status']; // 'pending', 'active', 'rejected'
                 $endDate = $row['end_time'] ? new DateTime($row['end_time']) : null;
                 $now = new DateTime();

                 if ($status === 'active' && $endDate && $endDate < $now) {
                     $status = 'expired'; // Override status if end_time is past
                 }

                $accountsData[] = [
                    'id' => $row['survey_account_id'],
                    'registration_id' => $row['registration_id'],
                    'username' => $row['username_acc'],
                    'status' => $status, // Keep basic status
                    'start_date' => $row['start_time'],
                    'end_date' => $row['end_time'],
                    'province' => $row['province'],
                    'mountpoint_name' => $row['mountpoint_name']
                    // Removed mountpoint_ip and mountpoint_port assignments
                ];
            }

            error_log($logPrefix . "Successfully processed data. Returning " . count($accountsData) . " simplified account(s) for user ID: " . $userId . "\n", 3, $this->log_file);

        } catch (PDOException $e) {
            error_log($logPrefix . "Database Error fetching accounts for user ID " . $userId . ": " . $e->getMessage() . "\n", 3, $this->log_file);
            return [];
        } catch (Exception $e) {
             error_log($logPrefix . "Date/Processing error for user ID " . $userId . ": " . $e->getMessage() . "\n", 3, $this->log_file);
             return [];
        }

        return $accountsData;
    }

    // Potential future methods related to RTK accounts could go here
    // e.g., getAccountDetails($accountId), updateAccountPassword($accountId, $newPassword), etc.
}

