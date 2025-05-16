<?php
/**
 * Error Handler Utility
 * This file provides functions for logging errors and activities consistently
 * across the application. It handles both database and file logging.
 */

/**
 * Log an error to both the error_logs database table and the error.log file
 * 
 * @param string $error_type The type of error (e.g., 'auth', 'database', 'api')
 * @param string $error_message The error message
 * @param string|null $stack_trace The stack trace (optional)
 * @param int|null $user_id The user ID (if available)
 * @return bool True if successful, false otherwise
 */
function log_error($conn, $error_type, $error_message, $stack_trace = null, $user_id = null) {
    // Always log to error.log file
    $log_message = date('[Y-m-d H:i:s]') . " [{$error_type}] " . $error_message;
    if ($stack_trace) {
        $log_message .= "\nStack Trace: " . $stack_trace;
    }
    if ($user_id) {
        $log_message .= " [User ID: {$user_id}]";
    }
    $log_message .= " [IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "]";
    
    error_log($log_message);
    
    // Log to database if connection is provided
    if ($conn) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, user_id, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssss", $error_type, $error_message, $stack_trace, $user_id, $ip_address);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } catch (Exception $e) {
            // If we can't log to the database, at least log to the file
            error_log("Failed to log error to database: " . $e->getMessage());
        }
    }
    
    return false;
}

/**
 * Log an activity to the activity_logs database table
 * 
 * @param object $conn Database connection
 * @param int $user_id The user ID
 * @param string $action The action performed (e.g., 'login', 'register', 'password_reset')
 * @param string $entity_type The type of entity (e.g., 'user', 'registration')
 * @param string $entity_id The ID of the entity
 * @param array|null $old_values Old values before the action (for updates)
 * @param array|null $new_values New values after the action (for updates)
 * @param string|null $notify_content Nội dung thông báo tiếng Việt
 * @return bool True if successful, false otherwise
 */
function log_activity($conn, $user_id, $action, $entity_type, $entity_id, $old_values = null, $new_values = null, $notify_content = null) {
    if (!$conn) {
        error_log("No database connection provided for activity logging");
        return false;
    }
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        // Convert arrays to JSON for storage with proper Unicode support for Vietnamese characters
        $old_values_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
        $new_values_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
        // Nếu notify_content chưa có, tự động sinh nội dung tiếng Việt từ new_values
        if ($notify_content === null && $new_values_json) {
            $notify_content = log_activity_generate_notify_content($action, $entity_type, $new_values);
        }
        $sql = "INSERT INTO activity_logs \
                (user_id, action, entity_type, entity_id, old_values, new_values, notify_content, ip_address, user_agent, created_at) \
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issssssss", 
                $user_id, 
                $action, 
                $entity_type, 
                $entity_id, 
                $old_values_json, 
                $new_values_json, 
                $notify_content, 
                $ip_address, 
                $user_agent
            );
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
    return false;
}

/**
 * Hàm sinh nội dung notify_content tiếng Việt từ action, entity_type, new_values
 * @param string $action
 * @param string $entity_type
 * @param array|null $new_values
 * @return string
 */
function log_activity_generate_notify_content($action, $entity_type, $new_values) {
    // Ví dụ mẫu, có thể mở rộng thêm các case khác
    if (!$new_values || !is_array($new_values)) return '';
    switch ($action) {
        case 'register':
            return 'Đăng ký tài khoản mới: ' . ($new_values['email'] ?? '');
        case 'renewal_request':
            return 'Yêu cầu gia hạn gói dịch vụ cho đăng ký #' . ($new_values['registration_id'] ?? '');
        case 'purchase':
            return 'Mua gói dịch vụ: ' . ($new_values['package'] ?? '') . ' - Số lượng: ' . (isset($new_values['selected_accounts']) ? count($new_values['selected_accounts']) : '');
        case 'referral':
            return 'Giới thiệu người dùng: ' . ($new_values['email'] ?? '');
        case 'password_reset_requested':
            return 'Yêu cầu đặt lại mật khẩu cho email: ' . ($new_values['email'] ?? '');
        case 'email_verified':
            return 'Xác thực email thành công cho: ' . ($new_values['email'] ?? '');
        case 'verification_email_sent':
            return 'Đã gửi email xác thực cho: ' . ($new_values['email'] ?? '');
        case 'create_support_request':
            return 'Tạo yêu cầu hỗ trợ mới: ' . ($new_values['subject'] ?? '');
        // ...bổ sung thêm các case khác nếu cần...
        default:
            // Nếu không có case cụ thể, mô tả chung
            return 'Thực hiện hành động: ' . $action;
    }
}