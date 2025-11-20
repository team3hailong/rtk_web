<?php
/**
 * Auto Account Creator
 * Tự động tạo tài khoản RTK khi đơn hàng được duyệt tự động
 */

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/classes/Database.php';
require_once dirname(dirname(__DIR__)) . '/api/rtk_system/account_api.php';

class AutoAccountCreator {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Tạo tài khoản tự động cho registration đã được duyệt
     * @param int $registration_id
     * @return array ['success' => bool, 'accounts' => array, 'error' => string|null]
     */
    public function createAccountsForRegistration($registration_id) {
        try {
            error_log("[AUTO_ACCOUNT] Starting createAccountsForRegistration for registration_id: $registration_id");
            $this->conn->beginTransaction();

            // 1. Lấy thông tin registration
            $registration = $this->getRegistrationInfo($registration_id);
            if (!$registration) {
                error_log("[AUTO_ACCOUNT] Registration not found: $registration_id");
                throw new Exception('Registration not found.');
            }
            error_log("[AUTO_ACCOUNT] Registration info: " . json_encode($registration));

            // 2. Lấy thông tin package
            $package = $this->getPackageInfo($registration['package_id']);
            if (!$package) {
                error_log("[AUTO_ACCOUNT] Package not found: " . $registration['package_id']);
                throw new Exception('Package not found.');
            }
            error_log("[AUTO_ACCOUNT] Package info: " . json_encode($package));

            // 3. Lấy danh sách location_id từ selected_provinces
            // Handle cases where selected_provinces may be NULL or invalid JSON.
            $selected_provinces_raw = $registration['selected_provinces'] ?? null;
            $selected_provinces = [];

            if (!empty($selected_provinces_raw)) {
                $decoded = json_decode($selected_provinces_raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                    $selected_provinces = $decoded;
                } else {
                    error_log("[AUTO_ACCOUNT] selected_provinces present but json_decode failed: " . json_last_error_msg() . ". Raw: " . var_export($selected_provinces_raw, true));
                }
            }

            // If still empty, fall back to single location_id from registration
            if (empty($selected_provinces)) {
                if (!empty($registration['location_id'])) {
                    $selected_provinces = [(int)$registration['location_id']];
                    error_log("[AUTO_ACCOUNT] Fallback to location_id: " . $registration['location_id']);
                } else {
                    error_log("[AUTO_ACCOUNT] No provinces selected and no location_id available in registration");
                    throw new Exception('No provinces selected.');
                }
            }

            error_log("[AUTO_ACCOUNT] Selected provinces: " . json_encode($selected_provinces));

            // 4. Lấy tất cả mount_point.id cho các location_id đã chọn (loại trùng)
            $mountIds = $this->getMountPointIds($selected_provinces);
            error_log("[AUTO_ACCOUNT] Mount IDs: " . json_encode($mountIds));

            // 5. Lấy province_code của location đầu tiên
            $province_code = $this->getProvinceCode($selected_provinces[0]);
            if (!$province_code) {
                error_log("[AUTO_ACCOUNT] Province code not found for location_id: " . $selected_provinces[0]);
                throw new Exception('Province code not found for location_id: ' . $selected_provinces[0]);
            }
            error_log("[AUTO_ACCOUNT] Province code: $province_code");

            // 6. Tạo các tài khoản
            $num_accounts = (int)$registration['num_account'];
            $created_accounts = [];
            
            // Lấy thông tin user
            $user_info = $this->getUserInfo($registration['user_id']);
            $temp_phone = $user_info['phone'];
            $customer_name = $user_info['name'];
            $customer_phone = $user_info['phone'];

            for ($i = 0; $i < $num_accounts; $i++) {
                // Tạo username tự động
                $username = $this->generateNextUsername($province_code);
                
                // Mật khẩu = số điện thoại người dùng
                $password = $customer_phone;
                
                // Tính thời gian bắt đầu và kết thúc
                $start_time = $registration['start_time'] ?? date('Y-m-d H:i:s');
                $end_time = $registration['end_time'] ?? $this->calculateEndTime($start_time, $package['duration_text']);
                
                // Convert sang timestamp milliseconds cho RTK API
                $start_timestamp = strtotime($start_time) * 1000;
                $end_timestamp = strtotime($end_time) * 1000;

                // Chuẩn bị dữ liệu để gọi API RTK
                $accountData = [
                    'name' => $username,
                    'userPwd' => $password,
                    'enabled' => 1,
                    'numOnline' => 1, // Mặc định 1 concurrent user
                    'customerBizType' => 1,
                    'customerCompany' => '',
                    'customerName' => $customer_name,
                    'customerPhone' => $customer_phone,
                    'startTime' => $start_timestamp,
                    'endTime' => $end_timestamp,
                    'casterIds' => [],
                    'regionIds' => [],
                    'mountIds' => $mountIds
                ];
                
                error_log("[AUTO_ACCOUNT] Account data: username=$username, password=$password (phone), customerName=$customer_name, customerPhone=$customer_phone, startTime=$start_timestamp ($start_time), endTime=$end_timestamp ($end_time)");

                // Gọi API tạo tài khoản RTK
                $api_result = createRtkAccount($accountData);
                error_log("[AUTO_ACCOUNT] RTK API Response: " . json_encode($api_result));
                
                if (!$api_result['success']) {
                    error_log("[AUTO_ACCOUNT] RTK API Error: " . ($api_result['error'] ?? 'Unknown error'));
                    error_log("[AUTO_ACCOUNT] RTK API Data: " . json_encode($api_result['data']));
                    throw new Exception('Failed to create RTK account: ' . ($api_result['error'] ?? 'Unknown error'));
                }

                $rtk_user_id = $api_result['data']['id'] ?? null;
                if (!$rtk_user_id) {
                    error_log("[AUTO_ACCOUNT] RTK API did not return user ID. Full response: " . json_encode($api_result));
                    throw new Exception('RTK API did not return user ID.');
                }
                error_log("[AUTO_ACCOUNT] RTK User ID created: $rtk_user_id");

                // Lưu vào bảng survey_account (start_time và end_time đã tính ở trên)
                $sql_insert_account = "INSERT INTO survey_account 
                    (id, registration_id, start_time, end_time, username_acc, password_acc, 
                     concurrent_user, enabled, customerBizType, temp_phone, created_at, updated_at) 
                    VALUES 
                    (:id, :registration_id, :start_time, :end_time, :username, :password, 
                     1, 1, 1, :temp_phone, NOW(), NOW())";
                
                $stmt = $this->conn->prepare($sql_insert_account);
                $stmt->bindParam(':id', $rtk_user_id, PDO::PARAM_STR);
                $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
                $stmt->bindParam(':start_time', $start_time, PDO::PARAM_STR);
                $stmt->bindParam(':end_time', $end_time, PDO::PARAM_STR);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                $stmt->bindParam(':temp_phone', $temp_phone, PDO::PARAM_STR);
                $stmt->execute();

                // Lưu vào account_groups
                $sql_insert_group = "INSERT INTO account_groups (registration_id, survey_account_id) 
                                     VALUES (:registration_id, :survey_account_id)";
                $stmt_group = $this->conn->prepare($sql_insert_group);
                $stmt_group->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
                $stmt_group->bindParam(':survey_account_id', $rtk_user_id, PDO::PARAM_STR);
                $stmt_group->execute();

                $created_accounts[] = [
                    'username' => $username,
                    'password' => $password,
                    'rtk_user_id' => $rtk_user_id
                ];

                error_log("[AUTO_ACCOUNT] Created account: $username for registration_id: $registration_id");
            }

            // Cập nhật registration status thành 'active'
            $sql_update_reg = "UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = :registration_id";
            $stmt_update = $this->conn->prepare($sql_update_reg);
            $stmt_update->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
            $stmt_update->execute();

            $this->conn->commit();

            return [
                'success' => true,
                'accounts' => $created_accounts,
                'error' => null
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("[AUTO_ACCOUNT] Error: " . $e->getMessage());
            return [
                'success' => false,
                'accounts' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin registration
     */
    private function getRegistrationInfo($registration_id) {
        $sql = "SELECT id, user_id, package_id, location_id, selected_provinces, num_account, 
                       start_time, end_time, status 
                FROM registration 
                WHERE id = :registration_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thông tin package
     */
    private function getPackageInfo($package_id) {
        $sql = "SELECT id, duration_text FROM package WHERE id = :package_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tất cả mount_point.id cho các location_id (loại trùng)
     */
    private function getMountPointIds(array $location_ids) {
        if (empty($location_ids)) {
            return [];
        }

        // Lấy mount_point.id dựa trên bảng liên kết mount_point_location
        $placeholders = implode(',', array_fill(0, count($location_ids), '?'));
        $sql = "SELECT DISTINCT mp.id 
                FROM mount_point mp
                JOIN mount_point_location mpl ON mp.id = mpl.mount_point_id
                WHERE mpl.location_id IN ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($location_ids);

        $mount_ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mount_ids[] = $row['id'];
        }

        return $mount_ids;
    }

    /**
     * Lấy province_code từ location_id
     */
    private function getProvinceCode($location_id) {
        $sql = "SELECT province_code FROM location WHERE id = :location_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':location_id', $location_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['province_code'] : null;
    }

    /**
     * Tạo username tiếp theo dựa trên province_code
     * Ví dụ: AGG198 -> AGG199, AGG001 -> AGG002
     * Format: province_code + 3 chữ số (001, 002, 003...)
     */
    private function generateNextUsername($province_code) {
        // Tìm tài khoản mới nhất với province_code này
        $sql = "SELECT username_acc FROM survey_account 
                WHERE username_acc LIKE :pattern 
                  AND username_acc REGEXP :regex
                ORDER BY username_acc DESC 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $pattern = $province_code . '%';
        $regex = '^' . $province_code . '[0-9]{3}$'; // Chỉ lấy username có đúng 3 chữ số
        $stmt->bindParam(':pattern', $pattern, PDO::PARAM_STR);
        $stmt->bindParam(':regex', $regex, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Lấy 3 chữ số cuối từ username cũ và tăng lên 1
            $last_username = $result['username_acc'];
            $number = (int)substr($last_username, -3); // Lấy 3 ký tự cuối
            $next_number = $number + 1;
            error_log("[AUTO_ACCOUNT] Last username: $last_username, Next number: $next_number");
        } else {
            // Nếu chưa có tài khoản nào, bắt đầu từ 1
            $next_number = 1;
            error_log("[AUTO_ACCOUNT] No existing username found, starting from 001");
        }

        // Format: province_code + 3 chữ số (pad với 0)
        $username = $province_code . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        error_log("[AUTO_ACCOUNT] Generated username: $username");
        
        return $username;
    }

    /**
     * Tạo password ngẫu nhiên
     */
    private function generateRandomPassword($length = 12) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%';
        $password = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }
        
        return $password;
    }

    /**
     * Lấy số điện thoại user
     */
    private function getUserPhone($user_id) {
        $sql = "SELECT phone FROM user WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['phone'] : null;
    }

    /**
     * Lấy thông tin user (username và số điện thoại)
     */
    private function getUserInfo($user_id) {
        $sql = "SELECT username, phone FROM user WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? [
            'name' => $result['username'],
            'phone' => $result['phone']
        ] : ['name' => null, 'phone' => null];
    }

    /**
     * Tính end_time từ start_time và duration_text
     * @param string $start_time
     * @param string $duration_text (ví dụ: "3 Tháng", "1 Năm", "7 Ngày")
     * @return string
     */
    private function calculateEndTime($start_time, $duration_text) {
        $start = new DateTime($start_time, new DateTimeZone('Asia/Ho_Chi_Minh'));
        $end = clone $start;
        
        if (preg_match('/(\d+)\s*(Năm|Tháng|Ngày)/iu', $duration_text, $matches)) {
            $num = (int)$matches[1];
            $unit = strtolower($matches[2]);
            $interval_spec = '';
            
            if ($unit === 'năm') {
                $interval_spec = "P{$num}Y";
            } elseif ($unit === 'tháng') {
                $interval_spec = "P{$num}M";
            } elseif ($unit === 'ngày') {
                $interval_spec = "P{$num}D";
            }

            if ($interval_spec) {
                $end->add(new DateInterval($interval_spec));
            } else {
                error_log("Could not parse duration '{$duration_text}'. Defaulting to 1 month.");
                $end->add(new DateInterval('P1M'));
            }
        } else {
            error_log("Could not parse duration '{$duration_text}'. Defaulting to 1 month.");
            $end->add(new DateInterval('P1M'));
        }
        
        return $end->format('Y-m-d H:i:s');
    }
}
