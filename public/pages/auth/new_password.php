<?php
session_start();
require_once __DIR__ . '/../../../private/config/config.php';

// Check if user has valid password reset session
if (!isset($_SESSION['password_reset_token']) || 
    !isset($_SESSION['password_reset_user_id']) || 
    !isset($_SESSION['password_reset_email']) || 
    !isset($_SESSION['password_reset_expiry']) ||
    time() > $_SESSION['password_reset_expiry']) {
    
    // Invalid or expired session, redirect to forgot password page
    $_SESSION['reset_message'] = 'Phiên đặt lại mật khẩu đã hết hạn hoặc không hợp lệ. Vui lòng thực hiện lại quy trình đặt lại mật khẩu.';
    $_SESSION['reset_message_type'] = 'error';
    header('Location: /public/pages/auth/forgot_password.php');
    exit();
}

$email = $_SESSION['password_reset_email'];
$error = $_SESSION['password_reset_error'] ?? '';
unset($_SESSION['password_reset_error']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Mật Khẩu Mới - RTK Web</title>
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
        .form-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 90%;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #4caf50;
            outline: none;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }
        .button {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            margin-top: 10px;
            transition: background-color 0.3s;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }
        .button:hover {
            background-color: #388e3c;
        }
        .error {
            color: #c62828;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .success {
            color: #2e7d32;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .password-rules {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .password-rules h4 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #333;
        }
        .password-rules ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-rules li {
            margin-bottom: 5px;
            color: #555;
        }
        .password-rule {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .rule-icon {
            margin-right: 8px;
            color: #ccc;
            font-size: 1rem;
        }
        .rule-valid {
            color: #4caf50;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Đặt Mật Khẩu Mới</h2>
        <p>Xin chào, vui lòng đặt mật khẩu mới cho tài khoản <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <div class="password-rules">
            <h4>Yêu cầu mật khẩu:</h4>
            <div class="password-rule">
                <span class="rule-icon" id="rule-length">&#x2713;</span>
                <span>Ít nhất 6 ký tự</span>
            </div>
            <div class="password-rule">
                <span class="rule-icon" id="rule-match">&#x2713;</span>
                <span>Mật khẩu và xác nhận mật khẩu phải khớp nhau</span>
            </div>
        </div>
        
        <form action="/public/handlers/action_handler.php?module=auth&action=process_reset_password_otp" method="POST">
            <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($_SESSION['password_reset_token']); ?>">
            
            <div class="form-group">
                <label for="new_password">Mật khẩu mới:</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238C4.292 11.294 6.016 12 8 12s3.708-.706 5.359-1.762zM8 10c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/>
                            <path id="eye-closed-icon" d="M13.359 11.238l-.707.707-11-11 .707-.707 11 11zM6.5 6.935l.7071-.7071 3.5 3.5-.7071.7071-3.5-3.5z" style="display: none;"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238C4.292 11.294 6.016 12 8 12s3.708-.706 5.359-1.762zM8 10c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"/>
                            <path id="eye-closed-icon2" d="M13.359 11.238l-.707.707-11-11 .707-.707 11 11zM6.5 6.935l.7071-.7071 3.5 3.5-.7071.7071-3.5-3.5z" style="display: none;"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="button">Đặt Lại Mật Khẩu</button>
            </div>
        </form>
        
        <p><a href="/public/pages/auth/login.php">Quay lại trang đăng nhập</a></p>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = event.currentTarget.querySelector('svg path:last-child');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'none';
            }
        }
        
        // Password validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const ruleLength = document.getElementById('rule-length');
        const ruleMatch = document.getElementById('rule-match');
        
        function validatePassword() {
            // Check length rule
            if (newPassword.value.length >= 6) {
                ruleLength.classList.add('rule-valid');
            } else {
                ruleLength.classList.remove('rule-valid');
            }
            
            // Check match rule
            if (newPassword.value && confirmPassword.value && 
                newPassword.value === confirmPassword.value) {
                ruleMatch.classList.add('rule-valid');
            } else {
                ruleMatch.classList.remove('rule-valid');
            }
        }
        
        newPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    </script>
</body>
</html>
