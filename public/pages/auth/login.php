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
    <link rel="stylesheet" href="../../assets/css/pages/auth/login.css"> <!-- Đường dẫn tới file CSS -->
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