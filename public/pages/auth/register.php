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
    <link rel="stylesheet" href="../../assets/css/pages/auth/register.css"> <!-- Đường dẫn tới file CSS -->
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