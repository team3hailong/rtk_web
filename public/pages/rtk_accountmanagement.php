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
    header('Location: ' . $base_url . '/pages/auth/login.php');
    exit;
}

// --- Include Header ---
// Giả sử header.php nằm trong thư mục private/includes ở gốc dự án
include $project_root_path . '/private/includes/header.php';

// --- Include Database and Repository ---
require_once $project_root_path . '/private/config/config.php'; // Thêm config.php trước
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
                    <a href="<?php echo $base_url; ?>/pages/purchase/packages.php" class="buy-now-btn">Mua Tài Khoản Ngay</a>
                </div>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                    <?php
                        $status_class = 'status-' . $account['status'];
                        $days_diff = calculate_days_diff($account['effective_end_time']);
                        $account_id_display = $account['id'];
                        
                        // Tạo chuỗi search terms an toàn
                        $search_terms = [];
                        $search_terms[] = $account['id'] ?? '';
                        $search_terms[] = $account['username_acc'] ?? '';
                        $search_terms[] = $account['province'] ?? '';
                        if (!empty($account['mountpoints'])) {
                            foreach ($account['mountpoints'] as $mp) {
                                $search_terms[] = $mp['mountpoint'] ?? '';
                            }
                        }
                        $search_terms = array_filter($search_terms); // Loại bỏ các giá trị rỗng
                        $search_terms_string = htmlspecialchars(strtolower(implode(' ', $search_terms)));
                    ?>
                    <div class="account-card <?php echo $status_class; ?>" data-status="<?php echo $account['status']; ?>" data-search-terms="<?php echo $search_terms_string; ?>">
                        <!-- Section 1: Thông tin cơ bản -->
                        <div class="card-section">
                            <strong>Tài khoản <?php echo htmlspecialchars(str_replace('RTK_', '#', $account['id'] ?? 'N/A')); ?></strong>
                            <p>Tên đăng nhập: <?php echo htmlspecialchars($account['username_acc'] ?? 'N/A'); ?></p>
                            <p>Mật khẩu: <?php echo htmlspecialchars($account['password_acc'] ?? 'N/A'); ?></p>
                        </div>

                        <!-- Section 2: Trạng thái & Thời hạn -->
                        <div class="card-section">
                            <p>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($account['enabled_status']); ?>
                                </span>
                            </p>
                            <p>Bắt đầu: <?php echo date('d/m/Y H:i', strtotime($account['effective_start_time'])); ?></p>
                            <p>Kết thúc: <?php echo date('d/m/Y H:i', strtotime($account['effective_end_time'])); ?></p>
                        </div>

                        <!-- Section 3: Thông tin Trạm -->
                        <div class="card-section">
                            <p>Tỉnh/TP: <?php echo htmlspecialchars($account['province'] ?? 'N/A'); ?></p>
                            <?php if (!empty($account['mountpoints'])): ?>
                                <p>Mountpoints:</p>
                                <ul class="mountpoint-list">
                                    <?php foreach ($account['mountpoints'] as $mp): ?>
                                        <li><?php echo htmlspecialchars($mp['mountpoint']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Mountpoints: Chưa có dữ liệu</p>
                            <?php endif; ?>
                        </div>

                        <!-- Section 4: Hành động -->
                        <div class="card-actions">
                            <button type="button" class="btn-view" 
                                data-account-id="<?php echo $account['id']; ?>" 
                                data-username="<?php echo htmlspecialchars($account['username_acc']); ?>"
                                data-password="<?php echo htmlspecialchars($account['password_acc']); ?>"
                                data-start="<?php echo htmlspecialchars($account['effective_start_time']); ?>"
                                data-end="<?php echo htmlspecialchars($account['effective_end_time']); ?>"
                                data-status="<?php echo htmlspecialchars($account['enabled_status']); ?>"
                                data-province="<?php echo htmlspecialchars($account['province']); ?>"
                                data-mountpoints='<?php echo htmlspecialchars(json_encode($account['mountpoints'])); ?>'
                            >
                                Xem chi tiết
                            </button>
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