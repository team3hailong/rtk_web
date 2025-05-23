<?php
// filepath: e:\Application\laragon\www\surveying_account\public\pages\auth\login.php
// Require config để có thể sử dụng middleware session
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
init_session();

// Hiển thị thông báo lỗi nếu có từ process_login.php
$error_message = $_SESSION['login_error'] ?? null;
// Hiển thị thông báo khi session hết hạn và tự động đăng xuất
$login_message = $_SESSION['login_message'] ?? null;

unset($_SESSION['login_error']); // Xóa thông báo lỗi sau khi hiển thị
unset($_SESSION['login_message']); // Xóa thông báo session sau khi hiển thị

// Nếu người dùng đã đăng nhập, chuyển hướng họ đi
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php"); // Chuyển đến trang dashboard hoặc trang chính
    exit();
}

// Get base URL for assets
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));
require_once $project_root_path . '/private/config/config.php';
$base_url = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/auth/login.css">
    <!-- Script thu thập vân tay thiết bị -->
    <script src="<?php echo $base_url; ?>/public/assets/js/device_fingerprint.js"></script>
</head>
<body>
    <div class="login-container">
        <h2>Đăng Nhập</h2>        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($login_message): ?>
            <div class="info-message"><?php echo htmlspecialchars($login_message); ?></div>
        <?php endif; ?>

        <!-- Cập nhật form action để sử dụng file trung gian thay vì trực tiếp truy cập file private -->
        <form action="/public/handlers/action_handler.php?module=auth&action=process_login" method="POST">
            <?php 
            // Thêm CSRF token vào form đăng nhập
            require_once $project_root_path . '/private/utils/csrf_helper.php';
            echo generate_csrf_input();
            ?>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>            <div class="forgot-password">
                <a href="forgot_password.php">Quên mật khẩu?</a>
            </div>
            <button type="submit" class="btn-login">Đăng Nhập</button>
        </form>
        <div class="register-link">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>
    </div>
</body>
</html>