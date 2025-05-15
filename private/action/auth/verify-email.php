<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/error_handler.php';


$token = $_GET['token'] ?? '';
$message = '';
$status = 'error';

if (empty($token)) {
    $message = 'Token xác thực không hợp lệ.';
} else {
    try {
        // Tìm user với token này và chưa xác thực email
        $stmt = $conn->prepare("SELECT id, email, email_verified FROM user WHERE email_verify_token = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['email_verified']) {
                $message = 'Email này đã được xác thực trước đó.';
                
                // Log trường hợp token đã được sử dụng
                $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, old_values) 
                        VALUES (?, 'email_verification_attempted', 'user', ?, ?, ?)";
                $stmt_log = $conn->prepare($sql);
                if ($stmt_log) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $log_data = json_encode([
                        'status' => 'already_verified',
                        'email' => $user['email'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    $stmt_log->bind_param("iiss", $user['id'], $user['id'], $ip, $log_data);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
            } else {
                // Cập nhật trạng thái xác thực và xóa token
                $update = $conn->prepare("UPDATE user SET email_verified = 1, email_verify_token = NULL WHERE id = ?");
                $update->bind_param("i", $user['id']);
                
                if ($update->execute()) {
                    $status = 'success';
                    $message = 'Xác thực email thành công! Bạn có thể đăng nhập ngay bây giờ.';
                    
                    // Log xác thực thành công
                    $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values) 
                            VALUES (?, 'email_verified', 'user', ?, ?, ?)";
                    $stmt_log = $conn->prepare($sql);
                    if ($stmt_log) {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $log_data = json_encode([
                            'status' => 'verified',
                            'email' => $user['email'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        $stmt_log->bind_param("iiss", $user['id'], $user['id'], $ip, $log_data);
                        $stmt_log->execute();
                        $stmt_log->close();
                    }
                } else {
                    $message = 'Không thể cập nhật trạng thái xác thực.';
                    // Log lỗi cập nhật
                    $sql = "INSERT INTO error_logs (error_type, error_message, user_id, ip_address) 
                            VALUES (?, ?, ?, ?)";
                    $stmt_error = $conn->prepare($sql);
                    if ($stmt_error) {
                        $error_type = 'email_verification_update';
                        $error_message = "Failed to update verification status for user ID: " . $user['id'];
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $stmt_error->bind_param("ssis", $error_type, $error_message, $user['id'], $ip);
                        $stmt_error->execute();
                        $stmt_error->close();
                    }
                }
                $update->close();
            }
        } else {
            $message = 'Token xác thực không hợp lệ hoặc đã hết hạn.';
            // Log token không hợp lệ
            $sql = "INSERT INTO error_logs (error_type, error_message, ip_address) VALUES (?, ?, ?)";
            $stmt_error = $conn->prepare($sql);
            if ($stmt_error) {
                $error_type = 'invalid_verification_token';
                $error_message = "Invalid verification token attempted: " . substr($token, 0, 10) . '...';
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt_error->bind_param("sss", $error_type, $error_message, $ip);
                $stmt_error->execute();
                $stmt_error->close();
            }
        }
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
        $message = 'Đã có lỗi xảy ra trong quá trình xác thực email.';
        
        // Log lỗi hệ thống - Log generic message to DB
        $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'email_verification_system';
            $error_message_db = "System error during email verification for token starting with: " . substr($token, 0, 10);
            $stack_trace_db = "Details in server error log."; // Generic trace for DB
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
    }
}
?> 
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực Email</title>
    <link rel="stylesheet" href="/public/assets/css/base.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f7f6;
        }
        .verification-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .success {
            color: #2e7d32;
        }
        .error {
            color: #c62828;
        }
        .button {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #388e3c;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2 class="<?php echo $status ?>">
            <?php if ($status === 'success'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            <?php endif; ?>
        </h2>
        <p class="<?php echo $status ?>"><?php echo htmlspecialchars($message); ?></p>
        <a href="/public/pages/auth/login.php" class="button">Đăng nhập</a>
    </div>
</body>
</html>