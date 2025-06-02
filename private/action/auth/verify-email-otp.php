<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/otp_helper.php';
require_once __DIR__ . '/../../utils/error_handler.php';

// Lấy email từ session và OTP từ form
$email = $_SESSION['verify_email'] ?? '';
$otp_code = $_POST['otp_code'] ?? '';

// Validate input
if (empty($email)) {
    $_SESSION['verify_email_error'] = 'Phiên xác thực không hợp lệ. Vui lòng thử lại.';
    header('Location: /public/pages/auth/login.php');
    exit();
}

if (empty($otp_code) || strlen($otp_code) !== 6 || !is_numeric($otp_code)) {
    $_SESSION['verify_email_error'] = 'Mã OTP không hợp lệ. Vui lòng nhập đúng 6 chữ số.';
    header('Location: /public/pages/auth/verify-email-otp.php');
    exit();
}

try {
    // Verify the OTP
    $result = verify_email_otp($conn, $email, $otp_code);
    
    if ($result['success']) {
        // OTP is valid, update user record
        $user_id = $result['user_id'];
        
        // Log xác thực thành công
        $notify_content = 'Xác thực email thành công cho: ' . $email;
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                VALUES (?, 'email_verified', 'user', ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql);
        if ($stmt_log) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'status' => 'verified',
                'email' => $email,
                'verification_method' => 'otp',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt_log->bind_param("iisss", $user_id, $user_id, $ip, $log_data, $notify_content);
            $stmt_log->execute();
            $stmt_log->close();
        }
        
        // Set success message and clear session verify_email
        $_SESSION['verify_email_success'] = 'Xác thực email thành công! Bạn có thể đăng nhập ngay bây giờ.';
        
        // Show success message on the OTP page first, then let user go to login
        header('Location: /public/pages/auth/verify-email-otp.php');
        exit();
    } else {
        // OTP verification failed
        switch ($result['error']) {
            case 'already_verified':
                $_SESSION['verify_email_error'] = 'Email này đã được xác thực trước đó.';
                break;
            case 'invalid_otp':
                $_SESSION['verify_email_error'] = 'Mã OTP không đúng. Vui lòng kiểm tra lại.';
                break;
            case 'expired_otp':
                $_SESSION['verify_email_error'] = 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi mã mới.';
                break;
            case 'email_not_found':
                $_SESSION['verify_email_error'] = 'Email không tồn tại trong hệ thống.';
                break;
            default:
                $_SESSION['verify_email_error'] = 'Đã có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại sau.';
        }
        
        // Log lỗi xác thực
        $sql = "INSERT INTO error_logs (error_type, error_message, ip_address) VALUES (?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'email_otp_verification_failed';
            $error_message = "OTP verification failed for email: $email with error: " . $result['error'];
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("sss", $error_type, $error_message, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
        
        header('Location: /public/pages/auth/verify-email-otp.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Email OTP verification error: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
    
    // Log lỗi hệ thống
    $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
    $stmt_error = $conn->prepare($sql);
    if ($stmt_error) {
        $error_type = 'email_otp_verification_system';
        $error_message_db = "System error during OTP verification for email: " . $email;
        $stack_trace_db = "Details in server error log."; // Generic trace for DB
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
        $stmt_error->execute();
        $stmt_error->close();
    }
    
    $_SESSION['verify_email_error'] = 'Đã có lỗi xảy ra trong quá trình xác thực. Vui lòng thử lại sau.';
    header('Location: /public/pages/auth/verify-email-otp.php');
    exit();
}
?>
