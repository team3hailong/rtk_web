<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/pages/auth/login.php');
    exit;
}
// Get registration_id from query
$registrationId = isset($_GET['registration_id']) ? intval($_GET['registration_id']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận chuyển quyền sở hữu</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Xác nhận chuyển quyền sở hữu tài khoản</h2>
        <?php if ($registrationId): ?>
        <form id="confirm-transfer-form" method="post" action="<?php echo BASE_URL; ?>/public/handlers/confirm_transfer.php">
            <input type="hidden" name="registration_id" value="<?php echo $registrationId; ?>">
            <div class="form-group">
                <label for="otp">Nhập mã OTP đã gửi đến email của chủ tài khoản:</label>
                <input type="text" id="otp" name="otp" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Xác nhận</button>
        </form>
        <?php else: ?>
        <p>Thiếu mã đăng ký cần xác nhận.</p>
        <?php endif; ?>
    </div>
</body>
</html>
