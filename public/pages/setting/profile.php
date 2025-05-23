<?php
// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// Sử dụng middleware session thay vì session_start thông thường
init_session();

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Check Authentication ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Load User Data ---
if (!isset($_SESSION['user_data'])) {
    // Chuyển hướng đến file trung gian để lấy dữ liệu người dùng
    header('Location: ' . $base_url . '/public/handlers/action_handler.php?module=setting&action=process_profile_update');
    exit;
}

$user = $_SESSION['user_data'];
$message = $_SESSION['profile_message'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_message'], $_SESSION['profile_error']);

// --- Include Header ---
$_SESSION['base_url'] = $base_url;
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/settings/profile.css">';
include $project_root_path . '/private/includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <div class="container"> <!-- Adjusted container -->

            <h2 class="profile-title">Cài đặt Hồ sơ Người dùng</h2>

            <?php if ($message): ?>
                <div class="message success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Profile Information Form -->
            <div class="form-section">
                <h3>Thông tin Hồ sơ</h3>
                <!-- Cập nhật form để sử dụng file trung gian -->
                <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=setting&action=process_profile_update" method="POST">
                    <?php 
                    // Thêm CSRF token vào form cập nhật hồ sơ
                    require_once $project_root_path . '/private/utils/csrf_helper.php';
                    echo generate_csrf_input(); 
                    ?>
                    <div class="form-group">
                        <label for="username">Tên người dùng / Công ty:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required class="form-control">
                    </div>                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" pattern="[0-9]{10,11}" title="Số điện thoại gồm 10 hoặc 11 chữ số" class="form-control">
                    </div>                    <!-- Removed company registration checkbox and company details section -->

                    <div class="form-actions">
                         <button type="submit" name="update_profile" class="btn btn-primary">Cập nhật Hồ sơ</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="form-section">
                <h3>Đổi Mật khẩu</h3>
                <!-- Cập nhật form để sử dụng file trung gian -->
                <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=setting&action=process_password_change" method="POST">
                    <?php 
                    // Thêm CSRF token vào form đổi mật khẩu
                    echo generate_csrf_input();
                    ?>
                    <div class="form-group">
                        <label for="current_password">Mật khẩu hiện tại:</label>
                        <input type="password" id="current_password" name="current_password" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu mới:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
                    </div>
                     <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-primary">Đổi Mật khẩu</button>
                    </div>
                </form>
            </div>

             <!-- Removed nav links here as they should be in sidebar/header -->

        </div> <!-- End container -->
    </main>
</div>

<!-- REMOVED inline <script> block -->

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';

// --- Include the external JavaScript file ---
// Use base_url to ensure the path is correct regardless of deployment structure
echo '<script src="' . $base_url . '/public/assets/js/pages/profile.js"></script>';

// Close database connection at the end of the file
if (isset($db)) {
    $db->close();
}
?>

