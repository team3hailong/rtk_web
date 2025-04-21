<?php
class RtkAccount {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    public function getAccountsByUserId($userId) {
        try {
            $sql = "SELECT sa.*, r.start_time as start_date, r.end_time as end_date, 
                          r.status as reg_status, p.name as package_name, 
                          DATEDIFF(r.end_time, r.start_time) as duration_days,
                          sa.username_acc as username
                   FROM survey_account sa
                   JOIN registration r ON sa.registration_id = r.id
                   JOIN package p ON r.package_id = p.id
                   JOIN user u ON r.user_id = u.id
                   WHERE r.user_id = :user_id AND sa.deleted_at IS NULL
                   ORDER BY sa.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process each account
            foreach ($accounts as &$account) {
                // Set account status based on dates and registration status
                $account['status'] = $this->calculateAccountStatus($account);
                
                // Format dates for display
                $account['start_date'] = $account['start_date'];
                $account['end_date'] = $account['end_date'];
                
                // Add placeholder for stations (can be enhanced later)
                $account['stations'] = $this->getStationsForAccount($account['id']);
            }
            
            return $accounts;

        } catch (PDOException $e) {
            error_log("Error fetching RTK accounts: " . $e->getMessage());
            return [];
        }
    }

    private function calculateAccountStatus($account) {
        $now = new DateTime();
        $endDate = new DateTime($account['end_date']);
        
        if ($account['reg_status'] === 'pending') {
            return 'pending';
        }
        
        if ($now > $endDate) {
            return 'expired';
        }
        
        return 'active';
    }

    private function getStationsForAccount($accountId) {
        // Placeholder - you can implement actual station fetching logic here
        // For now, return empty array
        return [];
    }

    public function getAccountById($accountId) {
        try {
            $sql = "SELECT sa.*, r.start_time as start_date, r.end_time as end_date,
                          r.status as reg_status, p.name as package_name,
                          DATEDIFF(r.end_time, r.start_time) as duration_days
                   FROM survey_account sa
                   JOIN registration r ON sa.registration_id = r.id
                   JOIN package p ON r.package_id = p.id
                   WHERE sa.id = :account_id AND sa.deleted_at IS NULL";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_STR);
            $stmt->execute();
            
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($account) {
                $account['status'] = $this->calculateAccountStatus($account);
                $account['stations'] = $this->getStationsForAccount($account['id']);
            }
            
            return $account;

        } catch (PDOException $e) {
            error_log("Error fetching RTK account details: " . $e->getMessage());
            return null;
        }
    }

    public function updatePassword($accountId, $newPassword) {
        try {
            $sql = "UPDATE survey_account 
                   SET password_acc = :password, updated_at = NOW() 
                   WHERE id = :account_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':password', password_hash($newPassword, PASSWORD_DEFAULT));
            $stmt->bindParam(':account_id', $accountId);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error updating RTK account password: " . $e->getMessage());
            return false;
        }
    }

    public function disableAccount($accountId) {
        try {
            $sql = "UPDATE survey_account 
                   SET enabled = 0, updated_at = NOW() 
                   WHERE id = :account_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':account_id', $accountId);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error disabling RTK account: " . $e->getMessage());
            return false;
        }
    }

    public function enableAccount($accountId) {
        try {
            $sql = "UPDATE survey_account 
                   SET enabled = 1, updated_at = NOW() 
                   WHERE id = :account_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':account_id', $accountId);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error enabling RTK account: " . $e->getMessage());
            return false;
        }
    }
}
?>