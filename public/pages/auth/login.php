<?php
// filepath: e:\Application\laragon\www\surveying_account\public\pages\auth\login.php
session_start();

// Hiển thị thông báo lỗi nếu có từ process_login.php
$error_message = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']); // Xóa thông báo lỗi sau khi hiển thị

// Nếu người dùng đã đăng nhập, chuyển hướng họ đi
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php"); // Chuyển đến trang dashboard hoặc trang chính
    exit();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px; /* Hẹp hơn form đăng ký một chút */
        }
        .login-container h2 {
            color: #2e7d32; /* Green color */
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input[type="email"],
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
            border-color: #4caf50; /* Green color on focus */
            outline: none;
        }
        .btn-login {
            background-color: #4caf50; /* Green background */
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background-color: #388e3c; /* Darker green on hover */
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        .register-link a {
            color: #2e7d32; /* Green link */
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #ef9a9a;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Đăng Nhập</h2>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="../../../../private/action/auth/process_login.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Đăng Nhập</button>
        </form>
        <div class="register-link">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>
        </div>
</body>
</html>