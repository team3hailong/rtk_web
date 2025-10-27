<?php
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/utils/session_middleware.php';
init_session();

$base_url = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực email</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/auth/register.css">
</head>
<body>
    <div class="container">
        <div class="register-form-section">
            <h2>Đăng ký thành công!</h2>
            <div class="success-message" style="margin-bottom: 24px;">
                Vui lòng kiểm tra email của bạn để xác thực tài khoản.<br>
                Hệ thống đã gửi một email chứa link xác thực tới địa chỉ bạn đăng ký.<br>
                <b>Hãy nhấn vào link trong email để hoàn tất kích hoạt tài khoản.</b>
            </div>
            <a href="login.php" class="login-button">Quay lại đăng nhập</a>
        </div>
    </div>
</body>
</html>