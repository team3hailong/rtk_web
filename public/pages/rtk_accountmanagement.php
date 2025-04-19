<?php
session_start();

// --- Base URL và Path (Cần điều chỉnh cho phù hợp) ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
// File này nằm trong /pages/ => cần lùi lại 1 cấp để đến gốc dự án
$script_dir = dirname($_SERVER['PHP_SELF']); // Should be /pages
$base_project_dir = dirname($script_dir); // Lùi 1 cấp
// Ensure base_url doesn't have double slashes if base_project_dir is '/'
$base_url = rtrim($protocol . $domain . ($base_project_dir === '/' || $base_project_dir === '\\' ? '' : $base_project_dir), '/');
$project_root_path = dirname(dirname(__DIR__)); // Lùi 2 cấp từ /pages -> project root

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về login (giả sử login ở gốc)
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// --- Include Header ---
// Giả sử header.php nằm trong thư mục private/includes ở gốc dự án
include $project_root_path . '/private/includes/header.php';

// --- Include Database and Repository ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/RtkAccount.php'; // Include the RtkAccount class

// Add CSS Link (Ideally should be inside <head> in header.php)
// Update the path to include the 'rtk' folder
echo '<link rel="stylesheet" href="' . $base_url . '/assets/css/pages/rtk/rtk_accountmanagement.css">';


// ===============================================
// == FETCH ACCOUNT DATA FROM DATABASE ==
// ===============================================
$db = new Database();
$rtkAccountManager = new RtkAccount($db); // Instantiate RtkAccount
$userId = $_SESSION['user_id']; // Get user ID from session
$accounts = $rtkAccountManager->getAccountsByUserId($userId); // Fetch data using RtkAccount

// Close the database connection if necessary (optional, depends on Database class implementation)
// $db->close();

// Hàm tính toán ngày còn lại/quá hạn (ví dụ)
function calculate_days_diff($end_date_str) {
    if (!$end_date_str) return ['remaining' => null, 'expired' => null];
    try {
        $end_date = new DateTime($end_date_str);
        $now = new DateTime();
        $interval = $now->diff($end_date);
        $days = (int)$interval->format('%r%a'); // %r gives sign, %a total days

        if ($days >= 0) {
            return ['remaining' => $days, 'expired' => null];
        } else {
            return ['remaining' => null, 'expired' => abs($days)];
        }
    } catch (Exception $e) {
        return ['remaining' => null, 'expired' => null]; // Lỗi nếu ngày không hợp lệ
    }
}

// Hàm định dạng ngày
function format_date_display($date_str) {
    if (!$date_str) return 'N/A';
    try {
        $date = new DateTime($date_str);
        return $date->format('d-m-Y'); // Định dạng dd-mm-yyyy
    } catch (Exception $e) {
        return 'N/A';
    }
}
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php // Giả sử sidebar.php nằm trong thư mục private/includes ở gốc
          include $project_root_path . '/private/includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="content-wrapper" style="padding: 1.5rem;"> <!-- Thêm padding trực tiếp nếu cần -->

        <div class="account-page-header">
             <h2>Quản Lý Tài Khoản</h2>
             <!-- Có thể thêm các thông tin khác hoặc nút hành động chung ở đây -->
        </div>


        <div class="filter-search-section">
            <div class="filter-tabs">
                <button class="filter-button active" data-filter="all">Tất cả</button>
                <button class="filter-button" data-filter="active">Đang hoạt động</button>
                <button class="filter-button" data-filter="expired">Hết hạn</button>
                <button class="filter-button" data-filter="pending">Đang xử lý</button>
            </div>
            <input type="text" class="search-input" id="account-search" placeholder="Tìm kiếm theo ID, Tên TK, Tên trạm...">
        </div>

        <div class="accounts-list" id="accounts-list-container">
            <?php if (empty($accounts)): ?>
                <div class="empty-state">
                    <h3>Chưa có tài khoản nào</h3>
                    <p>Bạn chưa đăng ký hoặc mua tài khoản nào. Hãy bắt đầu ngay!</p>
                    <a href="<?php echo $base_url; ?>/pages/purchase/package.php" class="buy-now-btn">Mua Tài Khoản Ngay</a>
                </div>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                    <?php
                        $status_class = 'status-' . $account['status']; // Ví dụ: status-active
                        $days_diff = calculate_days_diff($account['end_date']);
                        $account_id_display = $account['id']; // Hoặc 'Premium Account #'.$account['id']
                        $max_stations_visible = 3; // Số lượng trạm hiển thị ban đầu
                        // Safely handle stations: check if exists and is an array
                        $stations = isset($account['stations']) && is_array($account['stations']) ? $account['stations'] : [];
                        $total_stations = count($stations);
                        $needs_toggle = $total_stations > $max_stations_visible;
                        // Safely create search terms string
                        $station_names_string = implode(' ', $stations);
                    ?>
                    <div class="account-card <?php echo $status_class; ?>" data-status="<?php echo $account['status']; ?>" data-search-terms="<?php echo htmlspecialchars(strtolower($account['id'] . ' ' . $account['username'] . ' ' . $station_names_string)); ?>">
                        <!-- Section 1: Thông tin cơ bản -->
                        <div class="card-section">
                            <strong>Tài khoản #<?php echo htmlspecialchars($account['id'] ?? 'N/A'); ?></strong>
                            <p title="Tên đăng nhập">TK: <?php echo htmlspecialchars($account['username'] ?? 'N/A'); ?></p>
                            <div class="password-field">
                                <!-- Password display remains masked -->
                                <!-- Store the actual password in data-password but display asterisks -->
                                <!-- Use ?? '' to provide an empty string if password is null/missing -->
                                MK: <span data-password="<?php echo htmlspecialchars($account['password'] ?? ''); ?>">**********</span>
                                <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">Hiện</button>
                            </div>
                             <!-- Use ?? 'N/A' for package_name and duration_days -->
                             <p>Gói: <?php echo htmlspecialchars($account['package_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($account['duration_days'] ?? 'N/A'); ?> ngày)</p>
                        </div>

                        <!-- Section 2: Trạng thái & Ngày -->
                        <div class="card-section">
                             <strong>Trạng thái & Thời hạn</strong>
                            <p>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php
                                        switch ($account['status']) {
                                            case 'active': echo 'Đang hoạt động'; break;
                                            case 'expired': echo 'Đã hết hạn'; break;
                                            case 'pending': echo 'Đang xử lý'; break;
                                            default: echo ucfirst($account['status']); break;
                                        }
                                    ?>
                                </span>
                            </p>
                             <p>Bắt đầu: <?php echo format_date_display($account['start_date']); ?></p>
                             <p>Kết thúc: <?php echo format_date_display($account['end_date']); ?></p>
                            <?php if ($account['status'] === 'active' && $days_diff['remaining'] !== null): ?>
                                <p>Còn lại: <?php echo $days_diff['remaining']; ?> ngày</p>
                            <?php elseif ($account['status'] === 'expired' && $days_diff['expired'] !== null): ?>
                                <p style="color: var(--red-text-dark);">Quá hạn: <?php echo $days_diff['expired']; ?> ngày</p>
                             <?php elseif ($account['status'] === 'pending' && isset($account['pending_info']) && $account['pending_info']): ?>
                                <p style="color: var(--orange-text-dark);"><?php echo htmlspecialchars($account['pending_info']); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Section 3: Danh sách trạm -->
                        <div class="card-section">
                            <strong>Danh sách Trạm (<?php echo $total_stations; ?>)</strong>
                            <ul class="station-list" id="stations-<?php echo $account['id']; ?>">
                                <?php // Use the safe $stations variable ?>
                                <?php foreach (array_slice($stations, 0, $max_stations_visible) as $station): ?>
                                    <li><?php echo htmlspecialchars($station); ?></li>
                                <?php endforeach; ?>
                                <?php // Add hidden stations for expansion ?>
                                <?php if ($needs_toggle): ?>
                                    <?php // Use the safe $stations variable ?>
                                    <?php foreach (array_slice($stations, $max_stations_visible) as $station): ?>
                                        <li style="display: none;"><?php echo htmlspecialchars($station); ?></li>
                                     <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                             <?php if ($needs_toggle): ?>
                                <span class="toggle-stations" data-target="#stations-<?php echo $account['id']; ?>">Hiện thêm</span>
                            <?php endif; ?>
                        </div>

                         <!-- Section 4: Hành động -->
                         <div class="card-actions">
                              <!-- Changed button to a link for structural consistency, added btn-view class -->
                              <a href="#" class="btn-view" data-account-id="<?php echo $account['id']; ?>" role="button">Xem chi tiết</a>
                              <?php if ($account['status'] !== 'pending'): // Chỉ hiện nút gia hạn nếu không phải đang chờ ?>
                                <!-- Ensured class attribute is identical, added btn-renew class -->
                                <a href="<?php echo $base_url; ?>/pages/purchase/renew.php?account_id=<?php echo $account['id']; ?>" class="btn-renew">Gia hạn</a>
                             <?php endif; ?>
                         </div>

                    </div><!-- /.account-card -->
                <?php endforeach; ?>
            <?php endif; ?>
        </div><!-- /.accounts-list -->

    </main>
</div>

<!-- Add script to define base URL for JS -->
<script>
    const baseUrl = '<?php echo $base_url; ?>';
</script>
<!-- Add link to external JS file -->
<script src="<?php echo $base_url; ?>/assets/js/rtk_accountmanagement.js"></script>


<?php
// --- Include Footer ---
// Giả sử footer.php nằm trong thư mục private/includes ở gốc
include $project_root_path . '/private/includes/footer.php';
?>