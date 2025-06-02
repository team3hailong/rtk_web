<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/error_handler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if reset session is valid
    if (!isset($_SESSION['password_reset_token']) || 
        !isset($_SESSION['password_reset_user_id']) || 
        !isset($_SESSION['password_reset_email']) || 
        !isset($_SESSION['password_reset_expiry']) ||
        time() > $_SESSION['password_reset_expiry']) {
        
        // Invalid or expired session
        $_SESSION['reset_message'] = 'Phiên đặt lại mật khẩu đã hết hạn hoặc không hợp lệ. Vui lòng thực hiện lại quy trình đặt lại mật khẩu.';
        $_SESSION['reset_message_type'] = 'error';
        header("Location: ../../../public/pages/auth/forgot_password.php");
        exit();
    }
    
    $reset_token = $_POST['reset_token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['password_reset_user_id'];
    $email = $_SESSION['password_reset_email'];
    
    // Verify the session token matches
    if ($reset_token !== $_SESSION['password_reset_token']) {
        $_SESSION['password_reset_error'] = 'Token xác thực không hợp lệ. Vui lòng thử lại.';
        header("Location: ../../../public/pages/auth/new_password.php");
        exit();
    }
    
    // Validate passwords
    $errors = [];
    if (empty($new_password)) {
        $errors[] = "Mật khẩu mới không được để trống.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    }

    if (empty($errors)) {
        try {
            // Fetch user details to make sure they still exist
            $stmt = $conn->prepare("SELECT username FROM user WHERE id = ? AND email = ? AND deleted_at IS NULL");
            $stmt->bind_param("is", $user_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $username = $user['username'];
                
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Update password
                    $update = $conn->prepare("
                        UPDATE user 
                        SET password = ?,
                            password_reset_otp = NULL,
                            password_reset_otp_expires_at = NULL
                        WHERE id = ?
                    ");
                    $update->bind_param("si", $hashed_password, $user_id);
                    
                    if ($update->execute()) {
                        // Log the password reset activity
                        $notify_content = 'Mật khẩu đã được đặt lại thành công cho: ' . $email;
                        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                                VALUES (?, 'password_reset', 'user', ?, ?, ?, ?)";
                        $stmt_log = $conn->prepare($sql);
                        if ($stmt_log) {
                            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                            $log_data = json_encode([
                                'email' => $email,
                                'reset_method' => 'otp',
                                'timestamp' => date('Y-m-d H:i:s')
                            ]);
                            $stmt_log->bind_param("iisss", $user_id, $user_id, $ip, $log_data, $notify_content);
                            $stmt_log->execute();
                            $stmt_log->close();
                        }
                        
                        $conn->commit();
                        
                        // Clear the password reset session
                        unset($_SESSION['password_reset_token']);
                        unset($_SESSION['password_reset_user_id']);
                        unset($_SESSION['password_reset_email']);
                        unset($_SESSION['password_reset_expiry']);
                        
                        // Set success message and redirect to login
                        $_SESSION['login_error'] = 'Mật khẩu của bạn đã được đặt lại thành công. Vui lòng đăng nhập bằng mật khẩu mới.';
                        header("Location: ../../../public/pages/auth/login.php");
                        exit();
                    } else {
                        throw new Exception("Không thể cập nhật mật khẩu: " . $update->error);
                    }
                    
                    $update->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    
                    // Log error
                    $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, user_id, ip_address) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt_error = $conn->prepare($sql);
                    if ($stmt_error) {
                        $error_type = 'password_reset_update_failed';
                        $error_message = "Failed to update password for user ID: " . $user_id;
                        $stack_trace = $e->getTraceAsString();
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $stmt_error->bind_param("sssis", $error_type, $error_message, $stack_trace, $user_id, $ip);
                        $stmt_error->execute();
                        $stmt_error->close();
                    }
                    
                    $_SESSION['password_reset_error'] = 'Đã xảy ra lỗi khi đặt lại mật khẩu. Vui lòng thử lại.';
                    header("Location: ../../../public/pages/auth/new_password.php");
                    exit();
                }
            } else {
                // User not found or email doesn't match
                $_SESSION['reset_message'] = 'Người dùng không hợp lệ hoặc không tồn tại. Vui lòng thực hiện lại quy trình đặt lại mật khẩu.';
                $_SESSION['reset_message_type'] = 'error';
                
                // Clear the password reset session
                unset($_SESSION['password_reset_token']);
                unset($_SESSION['password_reset_user_id']);
                unset($_SESSION['password_reset_email']);
                unset($_SESSION['password_reset_expiry']);
                
                header("Location: ../../../public/pages/auth/forgot_password.php");
                exit();
            }
            
            $stmt->close();
        } catch (Exception $e) {
            error_log("Password reset system error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Log system error
            $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
            $stmt_error = $conn->prepare($sql);
            if ($stmt_error) {
                $error_type = 'password_reset_system';
                $error_message = "System error during password reset for user ID: " . $user_id;
                $stack_trace = "Details in server error log."; // Generic trace for DB
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt_error->bind_param("ssss", $error_type, $error_message, $stack_trace, $ip);
                $stmt_error->execute();
                $stmt_error->close();
            }
            
            $_SESSION['password_reset_error'] = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
            header("Location: ../../../public/pages/auth/new_password.php");
            exit();
        }
    } else {
        // Validation errors
        $_SESSION['password_reset_error'] = implode(' ', $errors);
        header("Location: ../../../public/pages/auth/new_password.php");
        exit();
    }
} else {
    // Not a POST request
    header("Location: ../../../public/pages/auth/forgot_password.php");
    exit();
}
?>
