<?php
// filepath: c:\laragon\www\rtk_web\public\pages\auth\reset_password.php
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/action/auth/reset_password_handler.php';

$base_url = BASE_URL;
$data = handle_reset_password_request();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/base.css">    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/auth/reset-password.css">
</head>
<body>
    <div class="reset-password-container">
        <h2>Đặt Lại Mật Khẩu</h2>
          <?php if ($data['message']): ?>
            <div class="message <?php echo $data['status']; ?>">
                <?php echo htmlspecialchars($data['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$data['show_form']): ?>
            <p class="no-token">Token không hợp lệ. Vui lòng kiểm tra lại email của bạn hoặc yêu cầu gửi lại link đặt lại mật khẩu mới.</p>
            <div class="login-link">
                <a href="forgot_password.php">Quên mật khẩu</a> | 
                <a href="login.php">Đăng nhập</a>
            </div>
        <?php else: ?>            <form action="/public/handlers/action_handler.php?module=auth&action=process_reset_password" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($data['token']); ?>">
                
                <div class="form-group">
                    <label for="new_password">Mật khẩu mới:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu mới:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn-submit">Đặt lại mật khẩu</button>
            </form>
            <div class="login-link">
                <a href="login.php">Quay lại đăng nhập</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
