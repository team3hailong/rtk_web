<?php
/**
 * Class DeviceTracker
 * 
 * Quản lý thông tin thiết bị và IP của người dùng đăng nhập
 */
class DeviceTracker {
    private $conn;
    
    /**
     * Khởi tạo DeviceTracker
     * 
     * @param PDO $conn Kết nối cơ sở dữ liệu PDO
     */
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Lưu hoặc cập nhật thông tin thiết bị và IP của người dùng
     *     * @param int $userId ID của người dùng
     * @param string $deviceFingerprint Vân tay thiết bị
     * @param string $ipAddress Địa chỉ IP
     * @param string $userAgent Thông tin User-Agent
     * @param string $voucherCode Mã voucher (nếu có)
     * @return bool True nếu thành công, ngược lại False
     */
    public function trackUserDevice($userId, $deviceFingerprint, $ipAddress, $userAgent, $voucherCode = null) {
        try {
            // Kiểm tra xem thiết bị đã tồn tại chưa
            $checkStmt = $this->conn->prepare("
                SELECT id, user_id, login_count, voucher_code FROM user_devices 
                WHERE device_fingerprint = :fingerprint
            ");
            $checkStmt->bindParam(':fingerprint', $deviceFingerprint);
            $checkStmt->execute();
            $device = $checkStmt->fetch(PDO::FETCH_ASSOC);
              if ($device) {
                // Thiết bị đã tồn tại, cập nhật thông tin
                // Nếu thiết bị đã có voucher_code, không ghi đè mã mới
                $updateSql = "
                    UPDATE user_devices SET
                    user_id = :user_id,
                    ip_address = :ip_address,
                    user_agent = :user_agent,
                    last_login_at = NOW(),
                    login_count = login_count + 1
                ";
                
                // Chỉ cập nhật voucher_code nếu thiết bị chưa có voucher và có voucher mới được cung cấp
                if ($voucherCode && empty($device['voucher_code'])) {
                    $updateSql .= ", voucher_code = :voucher_code";
                }
                
                $updateSql .= " WHERE id = :id";
                
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $updateStmt->bindParam(':ip_address', $ipAddress);
                
                if ($voucherCode && empty($device['voucher_code'])) {
                    $updateStmt->bindParam(':voucher_code', $voucherCode);
                }
                $updateStmt->bindParam(':user_agent', $userAgent);
                $updateStmt->bindParam(':id', $device['id'], PDO::PARAM_INT);
                return $updateStmt->execute();            } else {
                // Thiết bị mới, thêm vào cơ sở dữ liệu
                $insertSql = "
                    INSERT INTO user_devices 
                    (user_id, device_fingerprint, ip_address, user_agent";
                    
                if ($voucherCode) {
                    $insertSql .= ", voucher_code";
                }
                
                $insertSql .= ") VALUES (:user_id, :fingerprint, :ip_address, :user_agent";
                
                if ($voucherCode) {
                    $insertSql .= ", :voucher_code";
                }
                
                $insertSql .= ")";
                
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $insertStmt->bindParam(':fingerprint', $deviceFingerprint);
                $insertStmt->bindParam(':ip_address', $ipAddress);
                $insertStmt->bindParam(':user_agent', $userAgent);
                
                if ($voucherCode) {
                    $insertStmt->bindParam(':voucher_code', $voucherCode);
                }
                
                return $insertStmt->execute();
            }
        } catch (PDOException $e) {
            error_log("DeviceTracker error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kiểm tra xem thiết bị hoặc IP đã được sử dụng trước đó hay chưa
     * 
     * @param string $deviceFingerprint Vân tay thiết bị
     * @param string $ipAddress Địa chỉ IP
     * @return bool True nếu thiết bị hoặc IP đã được sử dụng trước đó, ngược lại False
     */
    public function isDeviceOrIPRegistered($deviceFingerprint, $ipAddress) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM user_devices 
                WHERE device_fingerprint = :fingerprint OR ip_address = :ip_address
            ");
            $stmt->bindParam(':fingerprint', $deviceFingerprint);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] > 0);
        } catch (PDOException $e) {
            error_log("DeviceTracker isDeviceOrIPRegistered error: " . $e->getMessage());
            return false; // Nếu có lỗi, trả về false để an toàn
        }
    }
    
    /**
     * Lấy danh sách thiết bị của một người dùng
     * 
     * @param int $userId ID của người dùng
     * @return array Danh sách thiết bị
     */
    public function getUserDevices($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM user_devices 
                WHERE user_id = :user_id
                ORDER BY last_login_at DESC
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DeviceTracker getUserDevices error: " . $e->getMessage());
            return [];
        }
    }
}
