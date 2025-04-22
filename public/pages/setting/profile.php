<<<<<<< HEAD
<?php
// filepath: c:\laragon\www\rtk_web\public\pages\setting\profile.php
session_start();

// --- Project Root Path for Includes ---
// This file is in public/pages/setting/, so root is 3 levels up.
$project_root_path = dirname(dirname(dirname(__DIR__))); // Corrected path calculation

// --- Base URL Calculation ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
// Calculate base directory relative to document root
$script_dir = dirname($_SERVER['PHP_SELF']); // e.g., /public/pages/setting
// Find the part before '/public/' to get the base project directory in the URL
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');


// --- Include Required Files ---
require_once $project_root_path . '/private/classes/Database.php'; // Include Database class
// --- Database Connection using Database Class ---
$db = null;
$pdo = null;
$error = ''; // Initialize error message

try {
    $db = new Database();
    $pdo = $db->getConnection(); // Get PDO connection
    if (!$pdo) {
        throw new Exception("Database connection failed.");
    }
} catch (Exception $e) {
    // Log the error in a real application
    error_log("Database connection error in profile.php: " . $e->getMessage());
    $error = "Error connecting to the database. Please try again later.";
    // Display error and exit or handle gracefully
}


// --- Check if user is logged in ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php'); // Redirect to login page using base URL
    exit;
}

$user_id = $_SESSION['user_id'];
$message = ''; // For success/error messages
$user = []; // Initialize user array

// --- Fetch current user data (only if PDO connection is successful) ---
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, phone, is_company, company_name, tax_code FROM user WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            // Handle case where user is not found
            session_destroy();
            header('Location: ' . $base_url . '/public/pages/auth/login.php?error=user_not_found');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error fetching user data: " . $e->getMessage();
        // $user remains empty
    }
}


// --- Handle Profile Update (only if PDO connection is successful) ---
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $is_company = isset($_POST['is_company']) ? 1 : 0;
    $company_name = $is_company ? trim($_POST['company_name'] ?? '') : null;
    $tax_code = $is_company ? trim($_POST['tax_code'] ?? '') : null;

    // If company name is empty but is_company is checked, use username
    if ($is_company && empty($company_name)) {
        $company_name = $username;
    }

    // Basic Validation (Add more as needed)
    if (empty($username)) {
        $error = "Tên người dùng không được để trống.";
    } elseif ($is_company && empty($tax_code)) {
        $error = "Mã số thuế không được để trống nếu đăng ký là công ty.";
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ (phải có 10-11 chữ số).";
    } else {
        try {
            $sql = "UPDATE user SET username = ?, phone = ?, is_company = ?, company_name = ?, tax_code = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $phone, $is_company, $company_name, $tax_code, $user_id]);
            $message = "Cập nhật hồ sơ thành công!";

            // Update username in session if it changed
            if (isset($_SESSION['username']) && $_SESSION['username'] !== $username) {
                 $_SESSION['username'] = $username;
            }

            // Re-fetch user data to display updated info
            $stmt = $pdo->prepare("SELECT username, email, phone, is_company, company_name, tax_code FROM user WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
             // Check for duplicate phone error (adjust error code if needed for your DB)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'phone') !== false) {
                 $error = "Lỗi cập nhật hồ sơ: Số điện thoại đã tồn tại.";
            } else {
                 $error = "Lỗi cập nhật hồ sơ: " . $e->getMessage();
            }
        }
    }
}

// --- Handle Password Change (only if PDO connection is successful) ---
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Vui lòng điền đầy đủ các trường mật khẩu.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } elseif (strlen($new_password) < 6) { // Match registration minimum length
         $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM user WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$user_id]);
            $userData = $stmt->fetch();

            if ($userData && password_verify($current_password, $userData['password'])) {
                // Hash new password
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in DB
                $sql = "UPDATE user SET password = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$hashed_new_password, $user_id]);
                $message = "Đổi mật khẩu thành công!";
            } else {
                $error = "Mật khẩu hiện tại không chính xác.";
            }
        } catch (PDOException $e) {
            $error = "Lỗi đổi mật khẩu: " . $e->getMessage();
        }
    }
}

// --- Include Header ---
// Assuming header.php sets up necessary HTML structure, CSS links etc.
// We need to pass the base URL to the header if it uses it for links/CSS
$_SESSION['base_url'] = $base_url; // Store base_url in session for header/footer
include $project_root_path . '/private/includes/header.php';

?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <div class="container" style="max-width: 800px; margin: 20px auto; padding: 0;"> <!-- Adjusted container -->
            <h2 style="text-align: center; margin-bottom: 30px; color: #2e7d32;">Cài đặt Hồ sơ Người dùng</h2>

            <?php if ($message): ?>
                <div class="message" style="padding: 12px; background-color: #e8f5e9; border: 1px solid #a5d6a7; color: #2e7d32; margin-bottom: 20px; border-radius: 5px;"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error" style="padding: 12px; background-color: #ffebee; border: 1px solid #ef9a9a; color: #c62828; margin-bottom: 20px; border-radius: 5px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Profile Information Form -->
            <div class="form-section" style="background: #fff; padding: 25px; margin-bottom: 25px; border: 1px solid #e0e0e0; border-radius: 8px;">
                <h3>Thông tin Hồ sơ</h3>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"> <!-- Action to self -->
                    <div style="margin-bottom: 15px;">
                        <label for="username" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Tên người dùng / Công ty:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="email" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem; background-color: #f1f1f1; cursor: not-allowed; color: #777;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="phone" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Số điện thoại:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" pattern="[0-9]{10,11}" title="Số điện thoại gồm 10 hoặc 11 chữ số" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="is_company" style="display: inline-flex; align-items: center; font-weight: 500; color: #555;">
                            <input type="checkbox" id="is_company" name="is_company" value="1" <?php echo ($user['is_company'] ?? 0) ? 'checked' : ''; ?> onchange="toggleCompanyDetails(this.checked)" style="margin-right: 8px; accent-color: #4caf50;">
                            Đăng ký với tư cách công ty?
                        </label>
                    </div>
                    <div id="company_details" style="display: <?php echo ($user['is_company'] ?? 0) ? 'block' : 'none'; ?>; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc;">
                        <div style="margin-bottom: 15px;">
                            <label for="company_name" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Tên công ty (nếu khác tên người dùng):</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="tax_code" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Mã số thuế:</label>
                            <input type="text" id="tax_code" name="tax_code" value="<?php echo htmlspecialchars($user['tax_code'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                         <button type="submit" name="update_profile" style="background-color: #4caf50; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: 600;">Cập nhật Hồ sơ</button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="form-section" style="background: #fff; padding: 25px; margin-bottom: 25px; border: 1px solid #e0e0e0; border-radius: 8px;">
                <h3>Đổi Mật khẩu</h3>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"> <!-- Action to self -->
                    <div style="margin-bottom: 15px;">
                        <label for="current_password" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Mật khẩu hiện tại:</label>
                        <input type="password" id="current_password" name="current_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="new_password" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Mật khẩu mới:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="confirm_password" style="display: block; margin-bottom: 6px; font-weight: 500; color: #555;">Xác nhận mật khẩu mới:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 1rem;">
                    </div>
                     <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="change_password" style="background-color: #4caf50; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: 600;">Đổi Mật khẩu</button>
                    </div>
                </form>
            </div>

             <!-- Removed nav links here as they should be in sidebar/header -->

        </div> <!-- End container -->
    </main>
</div>

<script>
    function toggleCompanyDetails(isChecked) {
        const detailsDiv = document.getElementById('company_details');
        // const companyNameInput = document.getElementById('company_name'); // Not strictly needed for required logic here
        const taxCodeInput = document.getElementById('tax_code');

        detailsDiv.style.display = isChecked ? 'block' : 'none';
        // Make tax code required if checkbox is checked
        taxCodeInput.required = isChecked;

        // Optionally clear company fields if unchecked
        if (!isChecked) {
            taxCodeInput.value = '';
            // document.getElementById('company_name').value = ''; // Decide if you want to clear this
        }
    }
    // Initial call in case the page loads with the checkbox checked
    document.addEventListener('DOMContentLoaded', function() {
        const isCompanyCheckbox = document.getElementById('is_company');
        if (isCompanyCheckbox) {
             toggleCompanyDetails(isCompanyCheckbox.checked);
        }

        // Add focus style handling (optional enhancement)
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="tel"]');
        inputs.forEach(input => {
            input.addEventListener('focus', () => input.style.borderColor = '#4caf50');
            input.addEventListener('blur', () => input.style.borderColor = '#ccc');
        });
    });
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';

// --- Close connection if $db object was created ---
if ($db) {
    $db->close(); // Optional: Close connection at the end of the script
}
?>
=======

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

>>>>>>> 794f830fee977f27d7753cf4a8cfb1318901ebfd
