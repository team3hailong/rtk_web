<?php
// filepath: c:\laragon\www\rtk_web\public\pages\auth\forgot_password.php
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/action/auth/forgot_password_handler.php';

$base_url = BASE_URL;
$data = handle_forgot_password_request();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/auth/forgot-password.css">
</head>
<body>
    <div class="container">
        <div class="forgot-password-container">
            <h2>QUÊN MẬT KHẨU</h2>
            
            <?php if ($data['message']): ?>
                <div class="message <?php echo $data['message_type']; ?>">
                    <?php echo htmlspecialchars($data['message']); ?>
                </div>
            <?php endif; ?>
            
            <p>Nhập địa chỉ email của bạn, chúng tôi sẽ gửi cho bạn OTP 6 số để đặt lại mật khẩu</p>
            
            <form action="/public/handlers/action_handler.php?module=auth&action=process_forgot_password" method="POST">
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <button type="submit" class="btn-submit">GỬI MÃ OTP</button>
            </form>
            <div class="login-link">
                <a href="login.php">Quay lại đăng nhập</a>
            </div>
        </div>
    </div>
</body>
</html>
