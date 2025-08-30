<?php


// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_path = PUBLIC_URL; // Use PUBLIC_URL constant for links
$project_root_path = PROJECT_ROOT_PATH;

// --- Include required classes ---
require_once $project_root_path . '/private/classes/purchase/PurchaseService.php';
require_once $project_root_path . '/private/classes/DeviceTracker.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . '/pages/auth/login');
    exit;
}

// --- User Info (Example) ---
$user_id = $_SESSION['user_id']; // Get user ID
$user_username = $_SESSION['username'] ?? 'Người dùng';

// Initialize PurchaseService
$service = new PurchaseService();
$user_has_registration = $service->userHasRegistration($user_id);
$survey_account_count = $service->getUserSurveyAccountCount($user_id);

// Kiểm tra thiết bị và IP đã được sử dụng trước đây hay chưa
$show_trial_package = true;
try {
    // Lấy thông tin vân tay thiết bị và IP từ session hoặc từ request
    $device_fingerprint = $_SESSION['device_fingerprint'] ?? $_POST['device_fingerprint'] ?? '';
    $ip_address = $_SESSION['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!empty($device_fingerprint) || !empty($ip_address)) {
        // Kết nối database để kiểm tra thiết bị
        $dsn = "mysql:host=".DB_SERVER.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Khởi tạo DeviceTracker và kiểm tra
        $deviceTracker = new DeviceTracker($pdo);
        $device_registered = $deviceTracker->isDeviceOrIPRegistered($device_fingerprint, $ip_address);
        
        // Nếu thiết bị hoặc IP đã tồn tại, ẩn gói trial
        if ($device_registered) {
            $show_trial_package = false;
        }
    }
} catch (Exception $e) {
    error_log("Error checking device fingerprint: " . $e->getMessage());
    // Để an toàn, vẫn hiển thị gói trial nếu có lỗi
    $show_trial_package = true;
}

// Lấy tất cả các gói dịch vụ
$all_packages = $service->getAllPackages();

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/packages.css">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Mua Gói Tài Khoản</h2>
        <p class="text-gray-600 mb-6">Chọn gói phù hợp với nhu cầu sử dụng của bạn.</p>

        <!-- Grid chứa các gói (Tạo bằng vòng lặp PHP) -->
        <div class="packages-grid">

            <?php if (empty($all_packages)): ?>
                <p class="text-center text-gray-500 col-span-full">Hiện tại không có gói dịch vụ nào.</p>
            <?php else: ?>
                <?php foreach ($all_packages as $package): ?>                    <?php                        // --- LOGIC ẨN GÓI DÙNG THỬ ---
                        // Ẩn gói trial trong các trường hợp:
                        // 1. Người dùng đã có tài khoản survey_account
                        // 2. Thiết bị hoặc IP đã được sử dụng trước đó (đã đăng ký)
                        if ($package['package_id'] === 'trial_7d' && ($survey_account_count > 0 || !$show_trial_package)) {
                            continue; // Bỏ qua gói dùng thử
                        }
                        // --- KẾT THÚC LOGIC ẨN ---

                        // Decode features JSON
                        $features = json_decode($package['features_json'], true); // true for associative array
                        if ($features === null) {
                            $features = []; // Handle potential JSON decode error
                        }

                        // Xác định class cho card (thêm 'recommended' nếu cần)
                        $card_classes = 'package-card';
                        if ($package['is_recommended']) {
                            $card_classes .= ' recommended';
                        }
                        // Tạo URL cho trang chi tiết - Use base_path
                        $details_url = $base_path . '/pages/purchase/details.php?package=' . htmlspecialchars($package['package_id']);
                        // Xác định class cho nút bấm (thêm 'contact' nếu là nút liên hệ)
                        $button_classes = 'btn-select-package';
                        $is_contact_button = ($package['button_text'] === 'Liên hệ mua');
                        if ($is_contact_button) {
                             $button_classes .= ' contact';
                        }
                    ?>
                    <div class="<?php echo $card_classes; ?>">
                        <?php if ($package['is_recommended']): ?>
                            <div class="recommended-badge">Phổ biến</div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($package['name']); ?></h3>

                        <div class="package-price">
                            <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
                            <span class="duration"><?php echo htmlspecialchars($package['duration_text']); ?></span>
                        </div>

                        <!-- Hiển thị text tiết kiệm nếu có -->
                        <span class="package-savings">
                            <?php echo isset($package['savings_text']) ? htmlspecialchars($package['savings_text']) : '&nbsp;'; ?>
                        </span>

                        <ul class="package-features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="fas <?php echo htmlspecialchars($feature['icon']); ?>" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($feature['text']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Thêm lựa chọn Purchase Type -->
                        <?php if ($package['package_id'] !== 'trial_7d'): ?>
                        <div class="purchase-type-selector" style="margin-top: 10px; margin-bottom:10px;">
                            <label style="margin-right: 10px;"><input type="radio" name="purchase_type_<?php echo htmlspecialchars($package['package_id']); ?>" value="individual" checked> Cá nhân</label>
                            <label><input type="radio" name="purchase_type_<?php echo htmlspecialchars($package['package_id']); ?>" value="company"> Công ty (+10% VAT)</label>
                        </div>
                        <?php endif; ?>

                        <!-- Nút bấm với link chính xác -->
                        <a href="#" data-package-id="<?php echo htmlspecialchars($package['package_id']); ?>" class="<?php echo $button_classes; ?> select-package-button">
                            <?php echo htmlspecialchars($package['button_text']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div> <!-- /.packages-grid -->

    </main>
</div>

<!-- Page-specific JS -->
<script src="<?php echo $base_path; ?>/assets/js/pages/purchase/packages.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const packageButtons = document.querySelectorAll('.select-package-button');
    packageButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const packageId = this.dataset.packageId;
            const purchaseTypeInput = document.querySelector('input[name="purchase_type_' + packageId + '"]:checked');
            const purchaseType = purchaseTypeInput ? purchaseTypeInput.value : 'individual';
            
            let detailsUrl = '<?php echo $base_path; ?>/pages/purchase/details.php?package=' + packageId + '&purchase_type=' + purchaseType;
            
            // For contact button, redirect to contact page or handle differently
            if (this.classList.contains('contact')) {
                // Example: Redirect to a contact page or open a modal
                // For now, let's assume it still goes to details but could be handled differently
                // detailsUrl = '<?php echo $base_path; ?>/pages/support/contact.php?package=' + packageId;
                // For the purpose of this task, we'll let it proceed to details page
                // to show how purchase_type is passed.
            }
            
            window.location.href = detailsUrl;
        });
    });
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>