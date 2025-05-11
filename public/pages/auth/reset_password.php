<?php
// filepath: c:\laragon\www\rtk_web\public\pages\auth\reset_password.php
session_start();

// Nếu người dùng đã đăng nhập, chuyển hướng họ đi
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Get token từ URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    // Nếu không có token, hiển thị thông báo lỗi
    $message = 'Token đặt lại mật khẩu không hợp lệ.';
    $status = 'error';
} else {
    // Chuyển token vào hidden input để gửi với form
    $reset_token = $token;
    $message = null;
    $status = null;
    
    // Nếu có thông báo lỗi từ process_reset_password.php
    if (isset($_SESSION['reset_error'])) {
        $message = $_SESSION['reset_error'];
        $status = 'error';
        unset($_SESSION['reset_error']);
    }
}

// Get base URL for assets
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));
require_once $project_root_path . '/private/config/config.php';
$base_url = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/base.css">
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
        .reset-password-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
        }
        h2 {
            color: #2e7d32;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #4caf50;
            outline: none;
        }
        .btn-submit {
            background-color: #4caf50;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #388e3c;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        .login-link a {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .no-token {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <h2>Đặt Lại Mật Khẩu</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $status; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($token)): ?>
            <p class="no-token">Token không hợp lệ. Vui lòng kiểm tra lại email của bạn hoặc yêu cầu gửi lại link đặt lại mật khẩu mới.</p>
            <div class="login-link">
                <a href="forgot_password.php">Quên mật khẩu</a> | 
                <a href="login.php">Đăng nhập</a>
            </div>
        <?php else: ?>            <form action="/public/handlers/action_handler.php?module=auth&action=process_reset_password" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                
                <div class="form-group">
                    <label for="new_password">Mật khẩu mới:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu mới:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn-submit">Đặt lại mật khẩu</button>
            </form>
            <div class="login-link">
                <a href="login.php">Quay lại đăng nhập</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
