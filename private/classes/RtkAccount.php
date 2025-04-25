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
            $sql = "SELECT 
                    sa.id,
                    sa.username_acc,
                    sa.password_acc,
                    sa.enabled,
                    CASE 
                        WHEN sa.enabled = 1 THEN 'Đang hoạt động'
                        ELSE 'Đã khóa'
                    END as enabled_status,
                    r.start_time,
                    r.end_time,
                    r.status as reg_status,
                    p.name as package_name,
                    p.duration_text,
                    DATEDIFF(r.end_time, r.start_time) as duration_days,
                    l.province,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'mountpoint', mp.mountpoint,
                            'ip', mp.ip,
                            'port', mp.port
                        ) SEPARATOR '|'
                    ) as mountpoints_json,
                    pay.confirmed_at
                FROM survey_account sa
                JOIN registration r ON sa.registration_id = r.id
                JOIN package p ON r.package_id = p.id
                JOIN location l ON r.location_id = l.id
                LEFT JOIN mount_point mp ON l.id = mp.location_id
                LEFT JOIN payment pay ON r.id = pay.registration_id
                WHERE r.user_id = :user_id 
                AND sa.deleted_at IS NULL
                GROUP BY sa.id, sa.username_acc, sa.password_acc, sa.enabled, 
                         r.start_time, r.end_time, r.status, p.name, 
                         p.duration_text, l.province, pay.confirmed_at, r.location_id
                ORDER BY sa.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($accounts as &$account) {
                // Parse mountpoints from JSON string
                $account['mountpoints'] = [];
                if (!empty($account['mountpoints_json'])) {
                    $mountpoints_array = explode('|', $account['mountpoints_json']);
                    foreach ($mountpoints_array as $mp_json) {
                        if ($mp_json !== 'null' && $mp_json !== null) {
                            $mp_data = json_decode($mp_json, true);
                            if ($mp_data && !empty($mp_data['mountpoint'])) {
                                $account['mountpoints'][] = $mp_data;
                            }
                        }
                    }
                }
                unset($account['mountpoints_json']); // Remove the raw JSON string

                // Adjust start_time based on package type
                if (strpos(strtolower($account['package_name']), 'dùng thử') !== false) {
                    $account['effective_start_time'] = $account['start_time'];
                } else {
                    $account['effective_start_time'] = $account['confirmed_at'] ?? $account['start_time'];
                }
                // --- Set timezone to Asia/Ho_Chi_Minh (UTC+7) for display ---
                $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
                if ($account['confirmed_at']) {
                    $start = new DateTime($account['effective_start_time']);
                    $start->setTimezone($tz);
                    $start->add(new DateInterval('P' . $account['duration_days'] . 'D'));
                    $account['effective_end_time'] = $start->format('Y-m-d H:i:s');
                } else {
                    $end = new DateTime($account['end_time']);
                    $end->setTimezone($tz);
                    $account['effective_end_time'] = $end->format('Y-m-d H:i:s');
                }
                $start_disp = new DateTime($account['effective_start_time']);
                $start_disp->setTimezone($tz);
                $account['effective_start_time'] = $start_disp->format('Y-m-d H:i:s');
                // Set account status
                $account['status'] = $this->calculateAccountStatus($account);
            }
            
            return $accounts;

        } catch (PDOException $e) {
            error_log("Error fetching RTK accounts: " . $e->getMessage());
            return [];
        }
    }

    private function calculateAccountStatus($account) {
        $now = new DateTime();
        $endDate = new DateTime($account['effective_end_time']);
        
        if ($account['reg_status'] === 'pending') {
            return 'pending';
        }
        
        if ($now > $endDate) {
            return 'expired';
        }
        
        if ($account['enabled'] == 0) {
            return 'locked';
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
            $stmt->bindParam(':password', $newPassword); // Store plain password without hashing
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