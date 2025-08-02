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

// Check for voucher code in URL
$voucherCode = $_GET['voucher'] ?? '';
if($voucherCode) {
    $formData['voucher_code'] = $voucherCode;
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/auth/register.css">
    <!-- Script thu thập vân tay thiết bị -->
    <script src="<?php echo $base_url; ?>/public/assets/js/device_fingerprint.js"></script>
</head>
<body>
    <div class="container">
        <div class="login-info-section">
            <h1>TRUY CẬP <br> TÀI KHOẢN <br> CỦA BẠN</h1>
            <p>Đăng nhập để tiếp tục quản lý và sử dụng dịch vụ dễ dàng.</p>
            <a href="login.php" class="login-button">ĐĂNG NHẬP</a>
        </div>
        <div class="register-form-section">
            <h2>ĐĂNG KÝ</h2>

            <?php if ($success_message): ?>
                <div class="success-message" id="successMessage"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
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
                    <input type="text" id="username" name="username" placeholder="Username / Company" value="<?= htmlspecialchars($formData['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <input type="tel" id="phone" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" pattern="[0-9]{10,11}" title="Số điện thoại gồm 10 hoặc 11 chữ số" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="form-group">
                    <input type="text" id="referral_code" name="referral_code" placeholder="Referral Code (Optional)" value="<?= htmlspecialchars($formData['referral_code'] ?? '') ?>">
                </div>
                <?php if(isset($formData['voucher_code']) && !empty($formData['voucher_code'])): ?>
                <input type="hidden" id="voucher_code" name="voucher_code" value="<?= htmlspecialchars($formData['voucher_code']) ?>">
                <?php endif; ?>
                <button type="submit" class="btn-register">ĐĂNG KÝ</button>
            </form>
        </div>
    </div>
    <script src="<?php echo $base_url; ?>/public/assets/js/pages/auth/register.js"></script>
</body>
</html>