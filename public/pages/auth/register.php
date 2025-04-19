<?php
// filepath: e:\Application\laragon\www\surveying_account\public\pages\auth\register.php
session_start(); // Bắt đầu session để lưu trữ thông báo

// Hiển thị thông báo lỗi nếu có
$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['errors']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản</title>
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
        .register-container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }
        .register-container h2 {
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
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
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
        .form-group.checkbox-group {
            display: flex;
            align-items: center;
        }
        .form-group.checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: auto;
            accent-color: #4caf50; /* Green checkbox */
        }
        .company-info {
            border-left: 3px solid #4caf50;
            padding-left: 15px;
            margin-top: 15px;
            display: none; /* Hide by default */
        }
        .btn-register {
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
        .btn-register:hover {
            background-color: #388e3c; /* Darker green on hover */
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        .login-link a {
            color: #2e7d32; /* Green link */
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
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
        }
         .error-message ul {
            margin: 0;
            padding-left: 20px;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #a5d6a7;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Đăng Ký Tài Khoản</h2>

        <?php if ($success_message): ?>
            <div class="success-message" id="successMessage"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong>Vui lòng sửa các lỗi sau:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="../../../../private/action/auth/process_register.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="username">Tên người dùng / Tên công ty:</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($formData['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại:</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" pattern="[0-9]{10,11}" title="Số điện thoại gồm 10 hoặc 11 chữ số" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-register">Đăng Ký</button>
        </form>
        <div class="login-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </div>
    </div>

    <script>
        // Gọi hàm khi trang tải để đảm bảo trạng thái đúng nếu có dữ liệu cũ
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                setTimeout(() => {
                    window.location.href = 'login.php'; // Chuyển hướng đến trang đăng nhập
                }, 1000); // 1000 milliseconds = 1 giây
            }

            // Client-side validation for password match
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    alert('Mật khẩu và xác nhận mật khẩu không khớp!');
                    confirmPassword.focus();
                    event.preventDefault(); // Ngăn form gửi đi
                }
                // Thêm các kiểm tra khác nếu cần
            });
        });
    </script>
</body>
</html>