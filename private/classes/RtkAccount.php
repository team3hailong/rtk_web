<?php
class RtkAccount {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    // Get all accounts for a user with pagination
    public function getAccountsByUserIdWithPagination($userId, $page = 1, $perPage = 10, $filter = 'all') {
        try {
            $this->conn->exec("SET SESSION group_concat_max_len = 1000000;"); // Increase group_concat_max_len
            $start = ($page - 1) * $perPage;
            
            $statusCondition = "";
            if ($filter !== 'all') {
                if ($filter === 'active') {
                    $statusCondition = "AND sa.end_time > NOW() AND sa.enabled = 1";
                } elseif ($filter === 'expired') {
                    $statusCondition = "AND sa.end_time <= NOW()";
                } elseif ($filter === 'locked') {
                    $statusCondition = "AND sa.enabled = 0";
                }
            }

            $countSql = "SELECT COUNT(*) as total
                        FROM survey_account sa
                        JOIN registration r ON sa.registration_id = r.id
                        WHERE r.user_id = :user_id 
                        AND sa.deleted_at IS NULL
                        $statusCondition";

            $countStmt = $this->conn->prepare($countSql);
            $countStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $countStmt->execute();
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $sql = "SELECT 
                    sa.id,
                    sa.username_acc,
                    sa.password_acc,
                    sa.enabled,
                    sa.start_time as sa_start_time,
                    sa.end_time as sa_end_time,
                    sa.concurrent_user,
                    sa.caster,
                    sa.user_type,
                    sa.regionIds,
                    sa.customerBizType,
                    sa.area,

                    CASE 
                        WHEN sa.enabled = 1 THEN 'Đang hoạt động'
                        ELSE 'Đã khóa'
                    END as enabled_status,
                    r.start_time,
                    r.end_time,
                    r.status as reg_status,
                    r.package_id,
                    p.name as package_name,
                    p.duration_text,
                    DATEDIFF(r.end_time, r.start_time) as duration_days,
                    l.province,
                    GROUP_CONCAT(
                        DISTINCT JSON_OBJECT(
                            'mountpoint', mp.mountpoint,
                            'ip', mp.ip,
                            'port', mp.port
                        ) SEPARATOR '|'
                    ) as mountpoints_json,
                    th.payment_confirmed_at as confirmed_at
                FROM survey_account sa
                JOIN registration r ON sa.registration_id = r.id
                JOIN package p ON r.package_id = p.id
                JOIN location l ON r.location_id = l.id
                LEFT JOIN mount_point mp ON l.id = mp.location_id
                LEFT JOIN transaction_history th ON r.id = th.registration_id AND th.status = 'completed'

                LEFT JOIN account_groups ag ON sa.id = ag.survey_account_id
                WHERE r.user_id = :user_id 
                AND sa.deleted_at IS NULL
                $statusCondition
                GROUP BY sa.id, sa.username_acc, sa.password_acc, sa.enabled, sa.start_time, sa.end_time, sa.concurrent_user,
                         r.start_time, r.end_time, r.status, r.package_id, p.name, 
                         p.duration_text, l.province, th.payment_confirmed_at, r.location_id
                ORDER BY sa.created_at DESC
                LIMIT :start, :per_page";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':start', $start, PDO::PARAM_INT);
            $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($accounts as &$account) {
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
                unset($account['mountpoints_json']);


                // Ưu tiên sử dụng thời gian từ bảng survey_account nếu có
                if (!empty($account['sa_start_time'])) {
                    $account['effective_start_time'] = $account['sa_start_time'];
                } else if (strpos(strtolower($account['package_name']), 'dùng thử') !== false) {

                    $account['effective_start_time'] = $account['start_time'];
                } else {
                    $account['effective_start_time'] = $account['confirmed_at'] ?? $account['start_time'];
                }


                $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
                
                // Ưu tiên sử dụng thời gian end từ bảng survey_account nếu có
                if (!empty($account['sa_end_time'])) {
                    $account['effective_end_time'] = $account['sa_end_time'];
                } else if ($account['confirmed_at']) {

                    $start = new DateTime($account['effective_start_time']);
                    $start->setTimezone($tz);
                    $start->add(new DateInterval('P' . $account['duration_days'] . 'D'));
                    $account['effective_end_time'] = $start->format('Y-m-d H:i:s');
                } else {
                    $end = new DateTime($account['end_time']);
                    $end->setTimezone($tz);
                    $account['effective_end_time'] = $end->format('Y-m-d H:i:s');
                }
                
                // Format start time
                $start_disp = new DateTime($account['effective_start_time']);
                $start_disp->setTimezone($tz);
                $account['effective_start_time'] = $start_disp->format('Y-m-d H:i:s');
                
                // Thêm thông tin bổ sung
                $account['concurrent_users'] = $account['concurrent_user'] ?? 1;
                $account['status'] = $this->calculateAccountStatus($account);
            }
            
            $totalPages = ceil($totalRecords / $perPage);

            return [
                'accounts' => $accounts,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => $totalPages
                ]
            ];

        } catch (PDOException $e) {
            error_log("Error fetching RTK accounts with pagination: " . $e->getMessage());
            return [
                'accounts' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => 0
                ]
            ];
        }
    }

    public function getAccountsByUserId($userId) {
        try {
            $this->conn->exec("SET SESSION group_concat_max_len = 1000000;"); // Increase group_concat_max_len
            $sql = "SELECT 
                    sa.id,
                    sa.username_acc,
                    sa.password_acc,
                    sa.enabled,

                    sa.start_time as sa_start_time,
                    sa.end_time as sa_end_time,
                    sa.concurrent_user,
                    sa.caster,
                    sa.user_type,
                    sa.regionIds,
                    sa.customerBizType,
                    sa.area,
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
                        DISTINCT JSON_OBJECT(
                            'mountpoint', mp.mountpoint,
                            'ip', mp.ip,
                            'port', mp.port
                        ) SEPARATOR '|'
                    ) as mountpoints_json,
                    th.payment_confirmed_at as confirmed_at
                FROM survey_account sa
                JOIN registration r ON sa.registration_id = r.id
                JOIN package p ON r.package_id = p.id
                JOIN location l ON r.location_id = l.id
                LEFT JOIN mount_point mp ON l.id = mp.location_id
                LEFT JOIN transaction_history th ON r.id = th.registration_id AND th.status = 'completed'
                LEFT JOIN account_groups ag ON sa.id = ag.survey_account_id
                WHERE r.user_id = :user_id 
                AND sa.deleted_at IS NULL
                GROUP BY sa.id, sa.username_acc, sa.password_acc, sa.enabled, sa.start_time, sa.end_time, sa.concurrent_user,
                         r.start_time, r.end_time, r.status, p.name, 
                         p.duration_text, l.province, th.payment_confirmed_at, r.location_id
                ORDER BY sa.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($accounts as &$account) {
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
                unset($account['mountpoints_json']);


                // Ưu tiên sử dụng thời gian từ bảng survey_account nếu có
                if (!empty($account['sa_start_time'])) {
                    $account['effective_start_time'] = $account['sa_start_time'];
                } else if (strpos(strtolower($account['package_name']), 'dùng thử') !== false) {
                    $account['effective_start_time'] = $account['start_time'];
                } else {
                    $account['effective_start_time'] = $account['confirmed_at'] ?? $account['start_time'];
                }


                $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
                
                // Ưu tiên sử dụng thời gian end từ bảng survey_account nếu có
                if (!empty($account['sa_end_time'])) {
                    $account['effective_end_time'] = $account['sa_end_time'];
                } else if ($account['confirmed_at']) {
                    $start = new DateTime($account['effective_start_time']);
                    $start->setTimezone($tz);
                    $start->add(new DateInterval('P' . $account['duration_days'] . 'D'));
                    $account['effective_end_time'] = $start->format('Y-m-d H:i:s');
                } else {
                    $end = new DateTime($account['end_time']);
                    $end->setTimezone($tz);
                    $account['effective_end_time'] = $end->format('Y-m-d H:i:s');
                }
                
                // Format start time
                $start_disp = new DateTime($account['effective_start_time']);
                $start_disp->setTimezone($tz);
                $account['effective_start_time'] = $start_disp->format('Y-m-d H:i:s');
                
                // Thêm thông tin bổ sung
                $account['concurrent_users'] = $account['concurrent_user'] ?? 1;
                $account['status'] = $this->calculateAccountStatus($account);
            }
            
            return $accounts;

        } catch (PDOException $e) {
            error_log("Error fetching RTK accounts: " . $e->getMessage());
            return [];
        }
    }

    private function calculateAccountStatus($account) {
        $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $endDate = new DateTime($account['effective_end_time'], new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        // Không còn sử dụng trạng thái 'pending' nữa
        if ($account['enabled'] == 0) {
            return 'locked';
        }
        
        if ($now > $endDate) {
            return 'expired';
        }
        
        return 'active';
    }

    private function getStationsForAccount($accountId) {
        try {
            $sql = "SELECT s.* 
                   FROM station s
                   JOIN mount_point mp ON s.mountpoint_id = mp.id
                   JOIN location l ON mp.location_id = l.id
                   JOIN registration r ON l.id = r.location_id
                   JOIN survey_account sa ON r.id = sa.registration_id
                   WHERE sa.id = :account_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching stations for account: " . $e->getMessage());
            return [];
        }
    }

    public function getAccountById($accountId) {
        try {
            $sql = "SELECT sa.*, r.start_time as reg_start_time, r.end_time as reg_end_time,
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
                // Ưu tiên sử dụng thời gian từ bảng survey_account
                if (!empty($account['start_time'])) {
                    $account['effective_start_time'] = $account['start_time'];
                } else {
                    $account['effective_start_time'] = $account['reg_start_time'];
                }
                
                if (!empty($account['end_time'])) {
                    $account['effective_end_time'] = $account['end_time'];
                } else {
                    $account['effective_end_time'] = $account['reg_end_time'];
                }
                
                $account['status'] = $this->calculateAccountStatus($account);
                $account['stations'] = $this->getStationsForAccount($account['id']);
                $account['concurrent_users'] = $account['concurrent_user'] ?? 1;
            }
            
            return $account;

        } catch (PDOException $e) {
            error_log("Error fetching RTK account details: " . $e->getMessage());
            return null;
        }
    }    public function updatePassword($accountId, $newPassword) {
        try {
            $sql = "UPDATE survey_account 
                   SET password_acc = :password, updated_at = NOW() 
                   WHERE id = :account_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':password', $newPassword);
            $stmt->bindParam(':account_id', $accountId);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error updating RTK account password: " . $e->getMessage());
            return false;
        }
    }
      /**
     * Get account details if username and password match the records in the database
     * 
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @return array|null Account details if credentials are valid, null otherwise
     */
    public function getAccountByCredentials($username, $password) {
        try {
            $sql = "SELECT sa.id, sa.registration_id 
                   FROM survey_account sa
                   WHERE sa.username_acc = :username AND sa.password_acc = :password 
                   AND sa.deleted_at IS NULL";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting RTK account by credentials: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update the ownership of an account by setting the user_id in the registration table
     * 
     * @param int $registrationId The registration ID to update
     * @param int $userId The new user ID (owner)
     * @return bool True if update succeeded, false otherwise
     */
    public function updateAccountOwnership($registrationId, $userId) {
        try {
            $sql = "UPDATE registration 
                   SET user_id = :user_id, updated_at = NOW() 
                   WHERE id = :registration_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':registration_id', $registrationId, PDO::PARAM_INT);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error updating RTK account ownership: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate if username and password match the records in the database
     * 
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @return bool True if credentials are valid, false otherwise
     */
    public function validateCredentials($username, $password) {
        try {
            $sql = "SELECT COUNT(*) as count FROM survey_account 
                   WHERE username_acc = :username AND password_acc = :password 
                   AND deleted_at IS NULL";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] > 0);

        } catch (PDOException $e) {
            error_log("Error validating RTK account credentials: " . $e->getMessage());
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
    
    public function getAccountsInGroup($registrationId) {
        try {
            $sql = "SELECT sa.* 
                  FROM survey_account sa
                  JOIN account_groups ag ON sa.id = ag.survey_account_id
                  WHERE ag.registration_id = :registration_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':registration_id', $registrationId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting accounts in group: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateAccountDetails($accountId, $data) {
        try {
            $validFields = ['username_acc', 'password_acc', 'enabled', 'concurrent_user', 
                            'start_time', 'end_time', 'caster', 'user_type', 
                            'regionIds', 'customerBizType', 'area'];
            
            $updates = [];
            $params = [':account_id' => $accountId];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $validFields)) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $updates[] = "updated_at = NOW()";
            $sql = "UPDATE survey_account SET " . implode(', ', $updates) . " WHERE id = :account_id";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $param => &$value) {
                $stmt->bindParam($param, $value);
            }
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error updating RTK account details: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin nhiều tài khoản theo danh sách ID, chỉ của user hiện tại (dùng cho gia hạn)
     */
    public function getAccountsByIdsForRenewal($userId, $accountIds) {
        if (empty($accountIds) || !is_array($accountIds)) return [];
        
        try {
            $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
            
            $sql = "SELECT sa.id, sa.username_acc, sa.password_acc, sa.concurrent_user, 
                          sa.enabled, sa.end_time, r.location_id, r.num_account, 
                          l.province, p.name as package_name
                   FROM survey_account sa
                   JOIN registration r ON sa.registration_id = r.id
                   JOIN location l ON r.location_id = l.id
                   JOIN package p ON r.package_id = p.id
                   WHERE sa.id IN ($placeholders) 
                   AND r.user_id = ? 
                   AND sa.deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($sql);
            
            // Binding all account IDs first
            $i = 1;
            foreach ($accountIds as $id) {
                $stmt->bindValue($i++, $id);
            }
            
            // Binding the user ID as the last parameter
            $stmt->bindValue($i, $userId, PDO::PARAM_INT);
            
            $stmt->execute();
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format end_time for display
            foreach ($accounts as &$account) {
                if (!empty($account['end_time'])) {
                    $end_date = new DateTime($account['end_time']);
                    $account['end_time'] = $end_date->format('d/m/Y H:i:s');
                }
            }
            
            return $accounts;
        } catch (PDOException $e) {
            error_log("Error in getAccountsByIdsForRenewal: " . $e->getMessage());
            return [];
        }
    }
}
?>