<?php
// filepath: c:\laragon\www\rtk_web\private\action\auth\process_reset_password.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/error_handler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // Validate
    if (empty($token)) {
        $errors[] = "Token không hợp lệ.";
    }
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
            // Kiểm tra token có hợp lệ và chưa hết hạn
            $stmt = $conn->prepare("
                SELECT pr.user_id, u.username, u.email
                FROM password_resets pr
                JOIN user u ON pr.user_id = u.id
                WHERE pr.token = ? 
                AND pr.expires_at > NOW()
                AND u.deleted_at IS NULL
            ");
            
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user_id = $row['user_id'];
                $username = $row['username'];
                $email = $row['email'];
                
                // Hash mật khẩu mới
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Bắt đầu transaction
                $conn->begin_transaction();
                
                try {
                    // Cập nhật mật khẩu
                    $update = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
                    $update->bind_param("si", $hashed_password, $user_id);
                    
                    if ($update->execute()) {
                        // Xóa token đặt lại mật khẩu
                        $delete = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                        $delete->bind_param("i", $user_id);
                        $delete->execute();
                        $delete->close();
                        
                        // Log hoạt động using our utility function
                        log_activity($conn, $user_id, 'password_reset', 'user', $user_id, null, [
                            'email' => $email,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        
                        $conn->commit();
                        
                        // Thông báo thành công và chuyển hướng người dùng đến trang đăng nhập
                        $_SESSION['login_error'] = 'Mật khẩu của bạn đã được đặt lại thành công. Vui lòng đăng nhập bằng mật khẩu mới.';
                        header("Location: ../../../public/pages/auth/login.php");
                        exit();
                    } else {
                        throw new Exception("Không thể cập nhật mật khẩu.");
                    }
                    $update->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    
                    // Log error using our utility function
                    log_error($conn, 'auth', "Password reset failed: " . $e->getMessage(), $e->getTraceAsString(), $user_id);
                    
                    $_SESSION['reset_error'] = 'Đã xảy ra lỗi khi đặt lại mật khẩu. Vui lòng thử lại.';
                    header("Location: ../../../public/pages/auth/reset_password.php?token=" . urlencode($token));
                    exit();
                }
            } else {
                // Log invalid token attempt
                log_error($conn, 'auth', "Invalid or expired password reset token attempt", null, null);
                
                $_SESSION['reset_error'] = 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết đặt lại mật khẩu mới.';
                header("Location: ../../../public/pages/auth/forgot_password.php");
                exit();
            }
            $stmt->close();
        } catch (Exception $e) {
            // Log system error
            log_error($conn, 'auth', "Password reset system error: " . $e->getMessage(), $e->getTraceAsString(), null);
            
            $_SESSION['reset_error'] = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
            header("Location: ../../../public/pages/auth/forgot_password.php");
            exit();
        }
    } else {
        // Có lỗi validation, hiển thị lại form với thông báo lỗi
        $_SESSION['reset_error'] = implode(' ', $errors);
        header("Location: ../../../public/pages/auth/reset_password.php?token=" . urlencode($token));
        exit();
    }
} else {
    // Nếu không phải là POST request, chuyển hướng người dùng về trang đặt lại mật khẩu
    header("Location: ../../../public/pages/auth/forgot_password.php");
    exit();
}
?>
