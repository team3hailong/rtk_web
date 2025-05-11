<?php
// filepath: c:\laragon\www\rtk_web\private\action\auth\process_forgot_password.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/email_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $error = null;
    
    // Validate email
    if (empty($email)) {
        $error = "Email không được để trống.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Định dạng email không hợp lệ.";
    }
    
    if ($error === null) {
        // Kiểm tra xem email có tồn tại trong hệ thống không
        $stmt = $conn->prepare("SELECT id, username FROM user WHERE email = ? AND deleted_at IS NULL");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $username = $user['username'];
                
                // Tạo token ngẫu nhiên
                $token = bin2hex(random_bytes(32));
                
                // Thiết lập thời gian hết hạn (24 giờ từ hiện tại)
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Bắt đầu transaction
                $conn->begin_transaction();
                
                try {
                    // Xóa bất kỳ token đặt lại nào hiện có cho user này
                    $stmt_delete = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $stmt_delete->bind_param("i", $user_id);
                    $stmt_delete->execute();
                    $stmt_delete->close();
                    
                    // Lưu token mới vào database
                    $stmt_insert = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("iss", $user_id, $token, $expires_at);
                    
                    if ($stmt_insert->execute()) {
                        // Gửi email với link đặt lại mật khẩu
                        $emailSent = sendPasswordResetEmail($email, $username, $token);
                        
                        if ($emailSent) {
                            $conn->commit();
                            
                            // Log thành công vào activity_logs
                            $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values) 
                                    VALUES (?, 'password_reset_requested', 'user', ?, ?, ?)";
                            $stmt_log = $conn->prepare($sql);
                            if ($stmt_log) {
                                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                                $log_data = json_encode([
                                    'email' => $email,
                                    'timestamp' => date('Y-m-d H:i:s')
                                ]);
                                $stmt_log->bind_param("iiss", $user_id, $user_id, $ip, $log_data);
                                $stmt_log->execute();
                                $stmt_log->close();
                            }
                            
                            $_SESSION['reset_message'] = 'Liên kết đặt lại mật khẩu đã được gửi đến địa chỉ email của bạn. Vui lòng kiểm tra hộp thư đến của bạn.';
                            $_SESSION['reset_message_type'] = 'success';
                        } else {
                            $conn->rollback();
                            $_SESSION['reset_message'] = 'Không thể gửi email đặt lại mật khẩu. Vui lòng thử lại sau.';
                            $_SESSION['reset_message_type'] = 'error';
                        }
                    } else {
                        $conn->rollback();
                        $_SESSION['reset_message'] = 'Đã xảy ra lỗi. Vui lòng thử lại sau.';
                        $_SESSION['reset_message_type'] = 'error';
                        
                        // Log lỗi
                        error_log("Password reset token insert failed: " . $stmt_insert->error);
                    }
                    
                    $stmt_insert->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['reset_message'] = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
                    $_SESSION['reset_message_type'] = 'error';
                    
                    // Log lỗi
                    error_log("Password reset error: " . $e->getMessage());
                    
                    // Log lỗi vào database
                    $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
                    $stmt_error = $conn->prepare($sql);
                    if ($stmt_error) {
                        $error_type = 'password_reset_request';
                        // Log an abbreviated version of the error to the DB
                        $error_message_db = "Failed to process password reset for: " . $email;
                        $stack_trace_db = "Error details in server log"; // Avoid logging full stack trace
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
                        $stmt_error->execute();
                        $stmt_error->close();
                    }
                }
            } else {                // Không hiển thị lỗi cụ thể để bảo mật (không cho biết email tồn tại hay không)
                $_SESSION['reset_message'] = 'Nếu địa chỉ email này tồn tại trong hệ thống, bạn sẽ nhận được email hướng dẫn đặt lại mật khẩu.';
                $_SESSION['reset_message_type'] = 'info';
                  // Log việc có người thử reset password cho email không tồn tại
                $sql = "INSERT INTO activity_logs (action, entity_type, entity_id, ip_address, new_values) VALUES (?, ?, ?, ?, ?)";
                $stmt_log = $conn->prepare($sql);
                if ($stmt_log) {
                    $action = 'password_reset_nonexistent_email';
                    $entity_type = 'user'; // Thêm entity_type
                    $entity_id = 0; // Thêm entity_id (0 vì không có user cụ thể)
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $log_data = json_encode([
                        'email' => $email,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    $stmt_log->bind_param("ssiss", $action, $entity_type, $entity_id, $ip, $log_data);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
            }
            
            $stmt->close();
        } else {
            $_SESSION['reset_message'] = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
            $_SESSION['reset_message_type'] = 'error';
            error_log("Password reset prepare statement failed: " . $conn->error);
        }
    } else {
        $_SESSION['reset_message'] = $error;
        $_SESSION['reset_message_type'] = 'error';
    }
    
    $conn->close();
    header("Location: ../../../public/pages/auth/forgot_password.php");
    exit();
} else {
    // Nếu không phải là POST request, chuyển hướng người dùng về trang forgot_password
    header("Location: ../../../public/pages/auth/forgot_password.php");
    exit();
}
?>
