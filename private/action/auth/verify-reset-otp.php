<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/otp_helper.php';
require_once __DIR__ . '/../../utils/error_handler.php';

// Lấy email từ session và OTP từ form
$email = $_SESSION['reset_email'] ?? '';
$otp_code = $_POST['otp_code'] ?? '';

// Process OTP verification

// Validate input
if (empty($email)) {
    $_SESSION['reset_otp_error'] = 'Phiên đặt lại mật khẩu không hợp lệ. Vui lòng thử lại.';
    header('Location: /public/pages/auth/forgot_password.php');
    exit();
}

// Ensure OTP is properly formatted
if (empty($otp_code)) {
    $_SESSION['reset_otp_error'] = 'Mã OTP không được để trống. Vui lòng nhập mã OTP.';
    header('Location: /public/pages/auth/reset-password-otp.php');
    exit();
}

// Clean and normalize the OTP
$otp_code = trim($otp_code);
$otp_code = preg_replace('/[^0-9]/', '', $otp_code); // Remove any non-numeric characters

if (strlen($otp_code) !== 6) {
    $_SESSION['reset_otp_error'] = 'Mã OTP không hợp lệ. Vui lòng nhập đúng 6 chữ số.';
    header('Location: /public/pages/auth/reset-password-otp.php');
    exit();
}

try {
    // Verify the OTP
    $result = verify_password_reset_otp($conn, $email, $otp_code);
    
    if ($result['success']) {
        // OTP is valid, store a session token for resetting password
        $user_id = $result['user_id'];
        
        // Generate a session-based reset token
        $reset_session_token = bin2hex(random_bytes(32));
        
        // Store token in session
        $_SESSION['password_reset_token'] = $reset_session_token;
        $_SESSION['password_reset_user_id'] = $user_id;
        $_SESSION['password_reset_email'] = $email;
        $_SESSION['password_reset_expiry'] = time() + 1800; // 30 minutes
        
        // Log successful OTP verification
        $notify_content = 'Xác thực OTP đặt lại mật khẩu thành công cho: ' . $email;
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                VALUES (?, 'password_reset_otp_verified', 'user', ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql);
        if ($stmt_log) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'email' => $email,
                'verification_method' => 'otp',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt_log->bind_param("iisss", $user_id, $user_id, $ip, $log_data, $notify_content);
            $stmt_log->execute();
            $stmt_log->close();
        }
        
        // Redirect to password reset form
        header('Location: /public/pages/auth/new_password.php');
        exit();
    } else {
        // OTP verification failed
        switch ($result['error']) {
            case 'invalid_otp':
                $_SESSION['reset_otp_error'] = 'Mã OTP không đúng. Vui lòng kiểm tra lại.';
                break;
            case 'expired_otp':
                $_SESSION['reset_otp_error'] = 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi mã mới.';
                break;
            case 'email_not_found':
                $_SESSION['reset_otp_error'] = 'Email không tồn tại trong hệ thống.';
                break;
            default:
                $_SESSION['reset_otp_error'] = 'Đã có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại sau.';
        }
        
        // Log lỗi xác thực
        $sql = "INSERT INTO error_logs (error_type, error_message, ip_address) VALUES (?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'password_reset_otp_verification_failed';
            $error_message = "OTP verification failed for email: $email with error: " . $result['error'];
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("sss", $error_type, $error_message, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
        
        header('Location: /public/pages/auth/reset-password-otp.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Password reset OTP verification error: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
    
    // Log lỗi hệ thống
    $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
    $stmt_error = $conn->prepare($sql);
    if ($stmt_error) {
        $error_type = 'password_reset_otp_verification_system';
        $error_message_db = "System error during OTP verification for email: " . $email;
        $stack_trace_db = "Details in server error log."; // Generic trace for DB
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
        $stmt_error->execute();
        $stmt_error->close();
    }
    
    $_SESSION['reset_otp_error'] = 'Đã có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại sau.';
    header('Location: /public/pages/auth/reset-password-otp.php');
    exit();
}
?>
