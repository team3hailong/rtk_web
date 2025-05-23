<?php
// filepath: e:\Application\laragon\www\surveying_account\public\pages\auth\register.php
session_start(); // Bắt đầu session để lưu trữ thông báo

// Hiển thị thông báo lỗi nếu có
$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

// Check for referral code in URL
$referralCode = $_GET['ref'] ?? '';
if($referralCode) {
    $formData['referral_code'] = $referralCode;
}

unset($_SESSION['errors']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);

// Get base URL for assets
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));
require_once $project_root_path . '/private/config/config.php';
$base_url = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/auth/register.css">
    <!-- Script thu thập vân tay thiết bị -->
    <script src="<?php echo $base_url; ?>/public/assets/js/device_fingerprint.js"></script>
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

        <!-- Cập nhật form action để sử dụng file trung gian thay vì trực tiếp truy cập file private -->
        <form action="/public/handlers/action_handler.php?module=auth&action=process_register" method="POST" id="registerForm">
            <?php
            // Thêm CSRF token vào form đăng ký
            require_once $project_root_path . '/private/utils/csrf_helper.php';
            echo generate_csrf_input();
            ?>
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
            </div>            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="referral_code">Mã giới thiệu (không bắt buộc):</label>
                <input type="text" id="referral_code" name="referral_code" value="<?= htmlspecialchars($formData['referral_code'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-register">Đăng Ký</button>
        </form>
        <div class="login-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </div>
    </div>

    <script src="<?php echo $base_url; ?>/public/assets/js/pages/auth/register.js"></script>
</body>
</html>