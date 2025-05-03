<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về login
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';

// --- Include Database and Repository ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/RtkAccount.php'; // Include the RtkAccount class

// Add CSS Link (Ideally should be inside <head> in header.php)
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/rtk/rtk_accountmanagement.css">';


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
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper accounts-content-wrapper">
        <div class="accounts-wrapper">
            <h2 class="text-2xl font-semibold mb-5">Quản Lý Tài Khoản</h2>
            
            <div class="filter-section">
                <div class="filter-buttons-group">
                    <button class="filter-button active" data-filter="all">Tất cả</button>
                    <button class="filter-button" data-filter="active">Hoạt động</button>
                    <button class="filter-button" data-filter="expired">Hết hạn</button>
                    <button class="filter-button" data-filter="pending">Đã khóa</button>
                </div>
                <input type="text" class="search-box" placeholder="Tìm kiếm theo ID, Tên TK, Tên trạm...">
            </div>
            
            <div class="accounts-table-wrapper">
                <table class="accounts-table">
                    <thead>
                        <tr>
                            <th>ID Tài khoản</th>
                            <th>Tên đăng nhập</th>
                            <th>Mật khẩu</th>
                            <th>Tỉnh/TP</th>
                            <th>Thời hạn</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-user-circle"></i>
                                        <p>Chưa có tài khoản nào</p>
                                        <a href="<?php echo $base_url; ?>/public/pages/purchase/packages.php" class="buy-now-btn">Mua Tài Khoản Ngay</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $account): ?>
                                <?php
                                    // Xử lý dữ liệu hiển thị
                                    $status_class = 'status-' . $account['status'];
                                    $days_diff = calculate_days_diff($account['effective_end_time']);
                                    $account_id_display = str_replace('RTK_', '#', $account['id'] ?? 'N/A');
                                    
                                    // Đổi tên trạng thái
                                    $status_text = $account['enabled_status'];
                                    if ($status_text === 'Đang hoạt động') {
                                        $status_text = 'Hoạt động';
                                    } elseif ($status_text === 'Đang xử lý') {
                                        $status_text = 'Đã khóa';
                                    }
                                    
                                    // Chuỗi search terms
                                    $search_terms = [];
                                    $search_terms[] = $account['id'] ?? '';
                                    $search_terms[] = $account['username_acc'] ?? '';
                                    $search_terms[] = $account['province'] ?? '';
                                    if (!empty($account['mountpoints'])) {
                                        foreach ($account['mountpoints'] as $mp) {
                                            $search_terms[] = $mp['mountpoint'] ?? '';
                                        }
                                    }
                                    $search_terms = array_filter($search_terms); 
                                    $search_terms_string = htmlspecialchars(strtolower(implode(' ', $search_terms)));
                                    
                                    // JSON data cho modal
                                    $account_details = [
                                        'id' => $account['id'],
                                        'username' => $account['username_acc'],
                                        'password' => $account['password_acc'],
                                        'start_time' => date('d/m/Y H:i', strtotime($account['effective_start_time'])),
                                        'end_time' => date('d/m/Y H:i', strtotime($account['effective_end_time'])),
                                        'status' => $status_text,
                                        'status_class' => $status_class,
                                        'province' => $account['province'] ?? 'N/A',
                                        'mountpoints' => $account['mountpoints'] ?? []
                                    ];
                                    $account_json = htmlspecialchars(json_encode($account_details), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr data-status="<?php echo $account['status']; ?>" data-search-terms="<?php echo $search_terms_string; ?>">
                                    <td><strong><?php echo htmlspecialchars($account_id_display); ?></strong></td>
                                    <td><?php echo htmlspecialchars($account['username_acc'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($account['password_acc'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($account['province'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($account['effective_end_time'])); ?></td>
                                    <td class="status">
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($status_text); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button class="action-button btn-details" title="Xem chi tiết" 
                                                onclick='showAccountDetails(<?php echo $account_json; ?>)'>
                                            <i class="fas fa-eye"></i> <span class="action-text">Chi tiết</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Account Details Modal -->
<div id="account-details-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="modal-title">Chi Tiết Tài Khoản</h4>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>ID:</strong> <span id="modal-account-id"></span></p>
            <p><strong>Tên đăng nhập:</strong> <span id="modal-username"></span></p>
            <p><strong>Mật khẩu:</strong> <span id="modal-password"></span></p>
            <p><strong>Thời gian bắt đầu:</strong> <span id="modal-start-time"></span></p>
            <p><strong>Thời gian kết thúc:</strong> <span id="modal-end-time"></span></p>
            <p><strong>Tỉnh/TP:</strong> <span id="modal-province"></span></p>
            <p><strong>Trạng thái:</strong>
                <span id="modal-status-badge" class="status-badge status-badge-modal"></span>
            </p>
            <div id="mountpoints-section">
                <p><strong>Mountpoints:</strong></p>
                <ul id="modal-mountpoints-list" class="mountpoint-list"></ul>
            </div>
        </div>
    </div>
</div>

<!-- Add script to define base URL for JS -->
<script>
    const baseUrl = '<?php echo $base_url; ?>';
</script>
<!-- Add link to external JS file -->
<script src="<?php echo $base_url; ?>/public/assets/js/pages/rtk/rtk_accountmanagement.js"></script>

<?php
include $project_root_path . '/private/includes/footer.php';
?>