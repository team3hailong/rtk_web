<?php
session_start();
$project_root_path = dirname(dirname(dirname(__DIR__)));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/purchase/RenewalService.php';

$base_url = BASE_URL;

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Nhận danh sách tài khoản được chọn từ POST
$selected_accounts = $_POST['selected_accounts'] ?? [];
if (empty($selected_accounts) || !is_array($selected_accounts)) {
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=no_account_selected');
    exit;
}

// Lấy thông tin tài khoản và gói qua RenewalService
$renewalService = new RenewalService();
$accounts = $renewalService->getAccountsByIdsForRenewal($user_id, $selected_accounts);
$packages = $renewalService->getAllPackagesForRenewal();
$renewalService->close();

// Include CSRF helper and generate token
require_once $project_root_path . '/private/utils/csrf_helper.php';
$csrf_token = generate_csrf_token();

if (empty($accounts)) {
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=invalid_account');
    exit;
}
if (empty($packages)) {
    echo '<div>Không có gói gia hạn khả dụng.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gia hạn tài khoản RTK</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : $base_url; ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : $base_url; ?>/assets/css/layouts/sidebar.css">
    <link rel="stylesheet" href="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : $base_url; ?>/assets/css/pages/purchase/renewal.css">
</head>
<body>
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <div class="container">
            <h2>Gia hạn tài khoản RTK</h2>
    
    <form method="post" action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=process_renewal" id="renewal-form">
        <!-- inject CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <!-- Hiển thị giải thích về việc chỉ chọn 1 gói -->
        <div class="package-selection">
            <div class="package-title">Chọn một gói gia hạn cho tất cả tài khoản (<?php echo count($accounts); ?> tài khoản)</div>
            
            <div class="package-list">
                <?php foreach ($packages as $pkg): ?>
                <div class="package-card" data-package-id="<?php echo $pkg['id']; ?>" data-package-price="<?php echo $pkg['price']; ?>">
                    <div class="package-name"><?php echo htmlspecialchars($pkg['name']); ?></div>
                    <div class="package-price"><?php echo number_format($pkg['price']); ?> đ</div>
                    <div class="package-duration"><?php echo htmlspecialchars($pkg['duration_text']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Bảng tài khoản -->
        <h3>Danh sách tài khoản được chọn</h3>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>Tài khoản</th>
                    <th>Thời hạn hiện tại</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $acc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($acc['username_acc']); ?></td>
                    <td><?php echo htmlspecialchars($acc['end_time']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Hiển thị tính toán tổng tiền -->
        <div class="total-section">
            <div class="total-row">
                <div>Gói gia hạn:</div>
                <div id="selected-package-name">Chưa chọn gói</div>
            </div>
            <div class="total-row">
                <div>Số lượng tài khoản:</div>
                <div><?php echo count($accounts); ?></div>
            </div>
            <div class="total-row">
                <div>Giá gói:</div>
                <div id="package-price">0 đ</div>
            </div>
            <div class="grand-total">
                Tổng tiền: <span id="total-price">0 đ</span>
            </div>
        </div>
        
        <!-- Ẩn input để lưu trữ ID gói đã chọn -->
        <input type="hidden" name="package_id" id="package-id-input" value="">
        
        <!-- Thêm tài khoản vào form -->
        <?php foreach ($accounts as $acc): ?>
            <input type="hidden" name="selected_accounts[]" value="<?php echo $acc['id']; ?>">
        <?php endforeach; ?>
          <div>
            <button type="submit" class="btn btn-primary" id="submit-button" disabled>Xác nhận gia hạn</button>
            <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>
        </div>
    </main>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
window.RENEWAL_PAGE_DATA = { accountCount: <?php echo count($accounts); ?> };
</script>
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : $base_url; ?>/assets/js/pages/purchase/renewal.js"></script>

<!-- Sidebar Toggle Script -->
<script>
    // Basic Sidebar Toggle for Mobile
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const hamburger = document.getElementById('hamburger-btn');

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
        // Optional: Hide hamburger when sidebar is open
        if (hamburger) {
            hamburger.style.visibility = sidebar.classList.contains('open') ? 'hidden' : 'visible';
        }
    }

    // Close sidebar if window is resized from mobile to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            if (sidebar && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
                if (hamburger) hamburger.style.visibility = 'visible';
            }
        }
    });
</script>
</body>
</html>
