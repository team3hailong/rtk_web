<?php
$base_path = '/public'; // Retain base path from original file

// Simplified navigation items array with active_check added (URLs from original file)
$nav_items = [
    // Main Navigation
    ['label' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'url' => '/pages/dashboard.php', 'active_check' => 'dashboard.php'],
    ['label' => 'Map hiển thị', 'icon' => 'fa-map-marked-alt', 'url' => '/pages/map_display.php', 'active_check' => 'map_display.php'], // Corrected URL
    ['label' => 'Mua tài khoản', 'icon' => 'fa-shopping-cart', 'url' => '/pages/purchase/packages.php', 'active_check' => 'packages.php'],
    ['label' => 'Quản lý tài khoản', 'icon' => 'fa-tasks', 'url' => '/pages/rtk_accountmanagement.php', 'active_check' => 'rtk_accountmanagement.php'], // Assuming this file exists or will be created
    ['label' => 'Quản lý giao dịch', 'icon' => 'fa-file-invoice-dollar', 'url' => '/pages/transaction.php', 'active_check' => 'transaction.php'], // Assuming this file exists or will be created
    ['label' => 'Chương trình giới thiệu', 'icon' => 'fa-users', 'url' => '/pages/referral/dashboard_referal.php', 'active_check' => 'dashboard_referal.php'],

    // Trợ giúp section
    ['type' => 'section', 'label' => 'Trợ giúp'],
    ['label' => 'Hướng dẫn sử dụng', 'icon' => 'fa-book-open', 'url' => '/pages/support/guide.php', 'active_check' => 'guide.php'], // Assuming this file exists or will be created
    ['label' => 'Hỗ trợ', 'icon' => 'fa-headset', 'url' => '/pages/support/contact.php', 'active_check' => 'contact.php'], // Assuming this file exists or will be created

    // Cài đặt section
    ['type' => 'section', 'label' => 'Cài đặt'],
    ['label' => 'Thông tin cá nhân', 'icon' => 'fa-user-circle', 'url' => '/pages/setting/profile.php', 'active_check' => 'profile.php'],
    ['label' => 'Thông tin thanh toán', 'icon' => 'fa-credit-card', 'url' => '/pages/setting/payment.php', 'active_check' => 'payment.php'],
    ['label' => 'Thông tin xuất hóa đơn', 'icon' => 'fa-file-alt', 'url' => '/pages/setting/invoice.php', 'active_check' => 'invoice.php'],

    // Logout
    ['type' => 'section', 'label' => 'Tài khoản'],
    ['label' => 'Đăng xuất', 'icon' => 'fa-sign-out-alt', 'url' => '/pages/auth/logout.php', 'class' => 'logout-link'] // Corrected URL
];

// Function to check if current page is active
// Include sidebar CSS (using correct base path)
echo '<link rel="stylesheet" href="' . $base_path . '/assets/css/layouts/sidebar.css">'; // Use correct path
// Include base styles (optional, if not loaded globally)
// echo '<link rel="stylesheet" href="' . $base_path . '/assets/css/base.css">';

function is_current_page($page_name) {
    // More robust check considering potential subdirectories
    $current_script_path = $_SERVER['SCRIPT_NAME'];
    // Check if the page name exists at the end of the script path
    return str_ends_with($current_script_path, '/' . $page_name);
}

$user_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Khách hàng';
?>

<!-- Hamburger button for mobile -->
<button id="hamburger-btn" class="hamburger-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="sidebar">
    <!-- Logo & Toggle -->
    <div class="sidebar-header">
        <a href="<?php echo $base_path; ?>/pages/dashboard.php" class="logo-link">
            <i class="logo-icon fas fa-ruler-combined"></i>
            <span class="logo-text"><b>Tài khoản đo đạc</b></span>
        </a>
        <button class="close-button" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- User Info -->
    <div class="user-info-container">
        <div class="user-info">
            <div class="user-icon-wrapper">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-text">
                <span class="user-name"><?php echo $user_username; ?></span>
                <span class="user-role">Khách hàng</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($nav_items as $item): ?>
                <?php if (isset($item['type']) && $item['type'] === 'section'): ?>
                    <li class="nav-section-title-li">
                        <p class="nav-section-title"><?php echo htmlspecialchars($item['label']); ?></p>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo $base_path . htmlspecialchars($item['url']); ?>"
                           class="nav-item <?php echo isset($item['class']) ? $item['class'] : ''; ?>
                                  <?php echo isset($item['active_check']) && is_current_page($item['active_check']) ? 'active' : ''; ?>">
                            <i class="icon fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>