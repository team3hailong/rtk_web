<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/otp_helper.php';
require_once __DIR__ . '/../../utils/email_helper.php';

// Lấy email từ session
$email = $_SESSION['verify_email'] ?? '';

// Validate input
if (empty($email)) {
    $_SESSION['verify_email_error'] = 'Phiên xác thực không hợp lệ. Vui lòng thử lại.';
    header('Location: /public/pages/auth/login.php');
    exit();
}

// Kiểm tra thời gian gửi lại OTP
$lastOtpSentTime = $_SESSION['last_email_otp_sent'] ?? 0;
$currentTime = time();
$cooldownPeriod = 30; // 30 giây

if (($currentTime - $lastOtpSentTime) < $cooldownPeriod) {
    $timeLeft = $cooldownPeriod - ($currentTime - $lastOtpSentTime);
    $_SESSION['verify_email_error'] = "Vui lòng đợi {$timeLeft} giây trước khi gửi lại mã.";
    header('Location: /public/pages/auth/verify-email-otp.php');
    exit();
}

try {
    // Check if user exists and email is not already verified
    $stmt = $conn->prepare("SELECT id, username, email_verified FROM user WHERE email = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['verify_email_error'] = 'Email không tồn tại trong hệ thống.';
        header('Location: /public/pages/auth/login.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    if ($user['email_verified']) {
        $_SESSION['verify_email_error'] = 'Email này đã được xác thực trước đó.';
        header('Location: /public/pages/auth/verify-email-otp.php');
        exit();
    }
    
    // Generate and store new OTP
    $otpResult = create_email_verification_otp($conn, $email);
    
    if ($otpResult['success']) {        // Send email with OTP
        $emailSent = sendVerificationOTP($email, $user['username'], $otpResult['otp']);
        
        if ($emailSent) {
            // Lưu thời gian gửi OTP vào session
            $_SESSION['last_email_otp_sent'] = time();
            $_SESSION['verify_email_error'] = 'Mã xác thực mới đã được gửi đến email của bạn.';
        } else {
            $_SESSION['verify_email_error'] = 'Không thể gửi email xác thực. Vui lòng thử lại sau.';
            
            // Log lỗi gửi email
            $sql = "INSERT INTO error_logs (error_type, error_message, user_id, ip_address) VALUES (?, ?, ?, ?)";
            $stmt_error = $conn->prepare($sql);
            if ($stmt_error) {
                $error_type = 'resend_verification_email_failed';
                $error_message = "Failed to resend verification email to: $email";
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt_error->bind_param("ssis", $error_type, $error_message, $user['id'], $ip);
                $stmt_error->execute();
                $stmt_error->close();
            }
        }
    } else {
        $_SESSION['verify_email_error'] = 'Không thể tạo mã xác thực mới. Vui lòng thử lại sau.';
        
        // Log lỗi tạo OTP
        $sql = "INSERT INTO error_logs (error_type, error_message, user_id, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'create_verification_otp_failed';
            $error_message = "Failed to create verification OTP for: $email - " . ($otpResult['message'] ?? 'Unknown error');
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssis", $error_type, $error_message, $user['id'], $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
    }
    
    header('Location: /public/pages/auth/verify-email-otp.php');
    exit();
} catch (Exception $e) {
    error_log("Resend email verification OTP error: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
    
    $_SESSION['verify_email_error'] = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
    header('Location: /public/pages/auth/verify-email-otp.php');
    exit();
}
?>
