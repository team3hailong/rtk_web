<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';


init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Required Files ---
require_once $project_root_path . '/private/classes/purchase/PurchaseService.php';
require_once $project_root_path . '/private/utils/csrf_helper.php'; // Include CSRF Helper

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Initialize PurchaseService and fetch package details ---
$service = new PurchaseService();
$selected_package_varchar_id = $_GET['package'] ?? null;
$selected_package = $service->getPackageByVarcharId($selected_package_varchar_id);

// --- Validate Selected Package ---
if (!$selected_package) {
    // If package not found, redirect back to packages page
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=invalid_package');
    exit;
}

// --- Determine package types ---
$is_trial_7d_package = ($selected_package_varchar_id === 'trial_7d');

// --- Check if it's a "Contact Us" package ---
$is_contact_package = ($selected_package['button_text'] === 'Liên hệ mua');
if ($is_contact_package) {
    // Redirect or display contact information - For now, redirect back with a message
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?info=contact_required&package_name=' . urlencode($selected_package['name']));
    exit;
    // Alternatively, you could display a contact message on this page itself
    // and disable the form.
}

$base_price = $selected_package['price']; // Get price from DB

// --- Fetch locations via service ---
$provinces = $service->getAllProvinces();

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';

?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : $base_url; ?>/assets/css/pages/purchase/details.css">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Chi tiết mua hàng</h2>

        <!-- Thay đổi action để trỏ đến action_handler.php thay vì trực tiếp vào process_order.php -->
        <form action="/public/handlers/action_handler.php?module=purchase&action=process_order" method="POST" class="purchase-details-form" id="details-form">
            <!-- Thông tin gói đã chọn -->
            <div class="selected-package-info">
                Bạn đang chọn: <strong><?php echo htmlspecialchars($selected_package['name']); ?></strong>
                 (<?php echo htmlspecialchars($selected_package['duration_text']); ?>)
            </div>

            <!-- CSRF Protection Token -->
            <?php echo generate_csrf_input(); ?>

            <!-- Input ẩn để gửi thông tin gói -->
            <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($selected_package['id']); ?>">
            <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($selected_package['name']); ?>">
            <input type="hidden" name="package_varchar_id" value="<?php echo htmlspecialchars($selected_package_varchar_id); ?>"> <!-- Add this line -->
            <input type="hidden" name="base_price" id="base_price" value="<?php echo $base_price; ?>"> <!-- Giá gốc để JS tính toán -->
            <!-- Giá tổng, sẽ được JS cập nhật hoặc giữ nguyên nếu là trial -->
            <input type="hidden" name="total_price" id="total_price_hidden" value="<?php echo $base_price; ?>">

            <?php if (!$is_trial_7d_package): // Only show quantity input if NOT the trial_7d package ?>
            <!-- Số lượng tài khoản -->
            <div class="form-group">
                <label for="quantity">Số lượng tài khoản:</label>
                <input type="number" id="quantity" name="quantity" class="form-control"
                       min="1" required
                       placeholder="Nhập số lượng (tối thiểu 1)"
                       >
            </div>
            <?php else: // If it IS the trial_7d package, add hidden input with quantity 1 ?>
            <input type="hidden" name="quantity" value="1">
            <?php endif; ?>

            <!-- Chọn Tỉnh/Thành phố -->
            <div class="form-group">
                <label for="location_id">Tỉnh/Thành phố sử dụng:</label>
                <select id="location_id" name="location_id" class="form-control" required>
                    <option value="" disabled selected>-- Chọn Tỉnh/Thành phố --</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province['id']); ?>">
                            <?php echo htmlspecialchars($province['province']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!$is_trial_7d_package): // Only show total price display if NOT the trial_7d package ?>
             <!-- Hiển thị tổng tiền (cập nhật bằng JS) -->
            <div class="total-price-display">
                Tổng cộng: <span id="total-price-view"><?php echo number_format($base_price, 0, ',', '.'); ?>đ</span>
            </div>
            <?php endif; ?>

            <!-- Nút chuyển đến thanh toán -->
            <div class="form-group" style="margin-top: 2rem; margin-bottom: 0;">
                <button type="submit" class="btn-submit">Tiếp tục đến Thanh toán</button>
            </div>
        </form>

    </main>
</div>

<!-- Page-specific JS -->
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : $base_url; ?>/assets/js/pages/purchase/details.js"></script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>