<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/otp_helper.php';
require_once __DIR__ . '/../../utils/email_helper.php';

// Lấy email từ session
$email = $_SESSION['reset_email'] ?? '';

// Validate input
if (empty($email)) {
    $_SESSION['reset_otp_error'] = 'Phiên đặt lại mật khẩu không hợp lệ. Vui lòng thử lại.';
    header('Location: /public/pages/auth/forgot_password.php');
    exit();
}

// Kiểm tra thời gian gửi lại OTP
$lastOtpSentTime = $_SESSION['last_reset_otp_sent'] ?? 0;
$currentTime = time();
$cooldownPeriod = 30; // 30 giây

if (($currentTime - $lastOtpSentTime) < $cooldownPeriod) {
    $timeLeft = $cooldownPeriod - ($currentTime - $lastOtpSentTime);
    $_SESSION['reset_otp_error'] = "Vui lòng đợi {$timeLeft} giây trước khi gửi lại mã.";
    header('Location: /public/pages/auth/reset-password-otp.php');
    exit();
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, username FROM user WHERE email = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['reset_otp_error'] = 'Email không tồn tại trong hệ thống.';
        header('Location: /public/pages/auth/forgot_password.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Generate and store new OTP
    $otpResult = create_password_reset_otp($conn, $email);
      if ($otpResult['success']) {
        // Send email with OTP
        $emailSent = sendPasswordResetOTP($email, $user['username'], $otpResult['otp']);
        
        if ($emailSent) {
            // Lưu thời gian gửi OTP vào session
            $_SESSION['last_reset_otp_sent'] = time();
            $_SESSION['reset_otp_error'] = 'Mã xác thực mới đã được gửi đến email của bạn.';
        } else {
            $_SESSION['reset_otp_error'] = 'Không thể gửi email xác thực. Vui lòng thử lại sau.';
            
            // Log lỗi gửi email
            $sql = "INSERT INTO error_logs (error_type, error_message, user_id, ip_address) VALUES (?, ?, ?, ?)";
            $stmt_error = $conn->prepare($sql);
            if ($stmt_error) {
                $error_type = 'resend_password_reset_email_failed';
                $error_message = "Failed to resend password reset email to: $email";
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt_error->bind_param("ssis", $error_type, $error_message, $user['id'], $ip);
                $stmt_error->execute();
                $stmt_error->close();
            }
        }
    } else {
        $_SESSION['reset_otp_error'] = 'Không thể tạo mã xác thực mới. Vui lòng thử lại sau.';
        
        // Log lỗi tạo OTP
        $sql = "INSERT INTO error_logs (error_type, error_message, user_id, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'create_password_reset_otp_failed';
            $error_message = "Failed to create password reset OTP for: $email - " . ($otpResult['message'] ?? 'Unknown error');
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssis", $error_type, $error_message, $user['id'], $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
    }
    
    header('Location: /public/pages/auth/reset-password-otp.php');
    exit();
} catch (Exception $e) {
    error_log("Resend password reset OTP error: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
    
    $_SESSION['reset_otp_error'] = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
    header('Location: /public/pages/auth/reset-password-otp.php');
    exit();
}
?>
