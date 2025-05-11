<?php
// filepath: c:\laragon\www\rtk_web\public\pages\auth\forgot_password.php
session_start();

// Nếu người dùng đã đăng nhập, chuyển hướng họ đi
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Hiển thị thông báo nếu có từ process_forgot_password.php
$message = $_SESSION['reset_message'] ?? null;
$message_type = $_SESSION['reset_message_type'] ?? 'error';
unset($_SESSION['reset_message'], $_SESSION['reset_message_type']); // Xóa thông báo sau khi hiển thị

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
    <title>Quên Mật Khẩu</title>
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
        .forgot-password-container {
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
        .form-group input[type="email"] {
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
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .info {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h2>Quên Mật Khẩu</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
          <p>Nhập địa chỉ email của bạn và chúng tôi sẽ gửi cho bạn một liên kết để đặt lại mật khẩu.</p>
        
        <form action="/public/handlers/action_handler.php?module=auth&action=process_forgot_password" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn-submit">Gửi Liên Kết Đặt Lại</button>
        </form>
        <div class="login-link">
            <a href="login.php">Quay lại đăng nhập</a>
        </div>
    </div>
</body>
</html>
