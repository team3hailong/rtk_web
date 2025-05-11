<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_path = PUBLIC_URL; // Use PUBLIC_URL constant for links
$project_root_path = PROJECT_ROOT_PATH;

// --- Include PurchaseService class ---
require_once $project_root_path . '/private/classes/purchase/PurchaseService.php';

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
                <?php foreach ($all_packages as $package): ?>
                    <?php
                        // --- BỎ LOGIC ẨN GÓI DÙNG THỬ TẠM THỜI ---
                        // if ($package['package_id'] === 'trial_7d' && $user_has_registration) {
                        //     continue;
                        // }
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

                        <!-- Nút bấm với link chính xác -->
                        <a href="<?php echo $details_url; ?>" class="<?php echo $button_classes; ?>">
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

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>