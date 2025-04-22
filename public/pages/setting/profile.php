
<?php
// filepath: c:\laragon\www\rtk_web\public\pages\setting\profile.php
session_start();

// --- Project Root Path for Includes ---
$project_root_path = dirname(dirname(dirname(__DIR__)));

// --- Base URL Calculation ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');

// --- Check Authentication ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Load User Data ---
if (!isset($_SESSION['user_data'])) {
    // Redirect to processing file to fetch user data
    header('Location: ' . $base_url . '/private/action/setting/process_profile_update.php');
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
                <!-- Update form action -->
                <form action="<?php echo $base_url; ?>/private/action/setting/process_profile_update.php" method="POST">
                    <div class="form-group">
                        <label for="username">Tên người dùng / Công ty:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly class="form-control readonly">
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" pattern="[0-9]{10,11}" title="Số điện thoại gồm 10 hoặc 11 chữ số" class="form-control">
                    </div>
                    <div class="form-group checkbox-group">
                        <label for="is_company">
                            <!-- Removed inline onchange, handled by profile.js -->
                            <input type="checkbox" id="is_company" name="is_company" value="1" <?php echo ($user['is_company'] ?? 0) ? 'checked' : ''; ?>>
                            Đăng ký với tư cách công ty?
                        </label>
                    </div>

                    <div id="company_details" style="display: <?php echo ($user['is_company'] ?? 0) ? 'block' : 'none'; ?>;" class="company-details-group">
                        <div class="form-group">
                            <label for="company_name">Tên công ty (nếu khác tên người dùng):</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="tax_code">Mã số thuế:</label>
                            <input type="text" id="tax_code" name="tax_code" value="<?php echo htmlspecialchars($user['tax_code'] ?? ''); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-actions">
                         <button type="submit" name="update_profile" class="btn btn-primary">Cập nhật Hồ sơ</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="form-section">
                <h3>Đổi Mật khẩu</h3>
                 <!-- Update form action -->
                <form action="<?php echo $base_url; ?>/private/action/setting/process_password_change.php" method="POST">
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

