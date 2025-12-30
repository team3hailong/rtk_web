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

    <!-- link font poppins -->

        <script src="<?php echo $base_url; ?>/public/assets/js/pages/auth/login.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Script thu thập vân tay thiết bị -->

    <script src="<?php echo $base_url; ?>/public/assets/js/device_fingerprint.js"></script>

</head>

<body>

    <div class="login-container">

        <div class="login-form">

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

                    <input type="email" id="email" name="email" placeholder="Email" required>

                </div>

                <div class="form-group">

                    <input type="password" id="password" name="password" placeholder="Password" required>

                    <span class="toggle-password" onclick="togglePasswordVisibility()">

                        <i id="password-toggle-icon" data-feather="eye-off"></i>

                    </span>

                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot_password.php">Quên mật khẩu?</a>
                    </div>
                </div>

                <button type="submit" class="btn-login">Đăng Nhập</button>

            </form>

            <div class="social-login">
                <div class="social-separator">Hoặc tiếp tục với</div>
                <div class="social-buttons">
                    <a href="<?php echo $base_url; ?>/public/handlers/auth_callback.php?provider=google" class="btn-social">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                        Google
                    </a>
                    <a href="<?php echo $base_url; ?>/public/handlers/auth_callback.php?provider=facebook" class="btn-social">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Facebook_Logo_%282019%29.png" alt="Facebook">
                        Facebook
                    </a>
                </div>
            </div>

        </div>

        <div class="register-link">

            <h1 class="title">Bắt đầu <br> hành trình <br> của bạn</h1>

            <p>Nếu bạn chưa có tài khoản, hãy tham gia cùng chúng tôi và bắt đầu trải nghiệm.</p>

            <a href="register.php">Đăng ký</a>

        </div>

    </div>

</body>

</html>