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
require_once $project_root_path . '/private/classes/RtkAccount.php';

// Chỉ include file CSS/JS tối ưu (đã gộp, không include file cũ)
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/rtk/rtk_accountmanagement.css">';
echo '<script src="' . $base_url . '/public/assets/js/pages/rtk/rtk_accountmanagement.js"></script>';

// --- Xử lý tham số từ URL cho phân trang ---
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
// Chỉ cho phép các giá trị cụ thể cho per_page
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10; // Mặc định
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
// Chỉ cho phép các filter hợp lệ
if (!in_array($filter, ['all', 'active', 'expired', 'locked'])) {
    $filter = 'all'; // Mặc định
}

// ===============================================
// == FETCH ACCOUNT DATA FROM DATABASE WITH PAGINATION ==
// ===============================================
$db = new Database();
$rtkAccountManager = new RtkAccount($db); // Instantiate RtkAccount
$userId = $_SESSION['user_id']; // Get user ID from session

// Lấy dữ liệu với phân trang
$result = $rtkAccountManager->getAccountsByUserIdWithPagination($userId, $currentPage, $perPage, $filter);
$accounts = $result['accounts'];
$pagination = $result['pagination'];

// Đóng kết nối database sau khi lấy dữ liệu (nếu class hỗ trợ)
if (method_exists($db, 'close')) { $db->close(); }

// Hàm tính toán ngày còn lại/quá hạn (ví dụ)
function calculate_days_diff($end_date_str) {
    if (!$end_date_str) return ['remaining' => null, 'expired' => null];
    try {
        $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        $end_date = new DateTime($end_date_str, $tz);
        $now = new DateTime('now', $tz);
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
        $date = new DateTime($date_str, new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $date->format('d-m-Y'); // Định dạng dd-mm-yyyy
    } catch (Exception $e) {
        return 'N/A';
    }
}

// Hàm tạo URL phân trang với các tham số hiện tại
function getPaginationUrl($page, $perPage, $filter) {
    $params = [];
    $params['page'] = $page;
    $params['per_page'] = $perPage;
    if ($filter !== 'all') {
        $params['filter'] = $filter;
    }
    return '?' . http_build_query($params);
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
                    <button class="filter-button <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all">Tất cả</button>
                    <button class="filter-button <?php echo $filter === 'active' ? 'active' : ''; ?>" data-filter="active">Hoạt động</button>
                    <button class="filter-button <?php echo $filter === 'expired' ? 'active' : ''; ?>" data-filter="expired">Hết hạn</button>
                    <button class="filter-button <?php echo $filter === 'locked' ? 'active' : ''; ?>" data-filter="locked">Đã khóa</button>
                </div>
                <div class="search-and-per-page">
                    <div class="per-page-selector">
                        <label for="per-page">Hiển thị:</label>
                        <select id="per-page" class="per-page-select">
                            <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                        </select>
                    </div>
                    <input type="text" class="search-box" placeholder="Tìm kiếm theo ID, Tên TK, Tên trạm...">
                </div>
            </div>
            
            <!-- Thêm nút Export Excel -->
            <div class="export-section">
                <div class="export-section-buttons">
                    <button id="export-excel" class="export-button" disabled>
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                    <form id="renewal-form" method="post" action="<?php echo $base_url; ?>/public/pages/purchase/renewal.php" style="display:inline; flex: 1;">
                        <button type="submit" id="renewal-btn" class="export-button" style="background:#1976D2;" disabled>
                            <i class="fas fa-redo"></i> Gia hạn
                        </button>
                    </form>
                </div>
                <button id="select-all-accounts" class="select-all-button">
                    <i class="fas fa-check-square"></i> Chọn tất cả
                </button>
                <span class="export-info">Đã chọn: <span id="selected-count">0</span> tài khoản</span>
            </div>
            
            <form id="export-form" method="post" action="<?php echo $base_url; ?>/public/handlers/export_rtk_accounts.php">
                <div class="accounts-table-wrapper">
                    <table class="accounts-table">
                        <thead>
                            <tr>
                                <th class="select-column">Chọn</th>
                                <th>Tên đăng nhập</th>
                                <th>Mật khẩu</th>
                                <th>Tỉnh/TP</th>
                                <th>Thời gian bắt đầu</th>
                                <th>Thời hạn đến</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="8">
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
                                        
                                        // Đảm bảo data-status phải khớp với giá trị filter trong JS và nút filter trên UI
                                        $data_status = $account['status']; // Lấy từ hàm calculateAccountStatus() trong class RtkAccount
                                        
                                        // Hiển thị văn bản trạng thái cho người dùng
                                        if ($data_status === 'active') {
                                            $status_text = 'Hoạt động';
                                        } elseif ($data_status === 'expired') {
                                            $status_text = 'Hết hạn';
                                        } elseif ($data_status === 'pending' || $data_status === 'locked') {
                                            $status_text = 'Đã khóa';
                                            // Đảm bảo tất cả các trạng thái không hoạt động đều hiển thị là 'locked'
                                            $data_status = 'locked';
                                        } else {
                                            $status_text = 'Không xác định';
                                        }
                                        
                                        // Chuỗi search terms
                                        $search_terms = [];
                                        $search_terms[] = $account['id'] ?? '';
                                        $search_terms[] = $account['username_acc'] ?? '';
                                        $search_terms[] = $account['province'] ?? '';
                                        $search_terms[] = $status_text ?? '';  // Thêm status text vào search terms
                                        if (!empty($account['mountpoints'])) {
                                            foreach ($account['mountpoints'] as $mp) {
                                                $search_terms[] = $mp['mountpoint'] ?? '';
                                            }
                                        }
                                        $search_terms = array_filter($search_terms); 
                                        $search_terms_string = htmlspecialchars(strtolower(implode(' ', $search_terms)));
                                        
                                        // JSON data cho modal và export
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
                                    <tr data-status="<?php echo $data_status; ?>" data-search-terms="<?php echo $search_terms_string; ?>">
                                        <td class="select-column">
                                            <input type="checkbox" name="selected_accounts[]" value="<?php echo $account['id']; ?>" class="account-checkbox">
                                        </td>
                                        <td><?php echo htmlspecialchars($account['username_acc'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($account['password_acc'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($account['province'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($account['effective_start_time'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($account['effective_end_time'])); ?></td>
                                        <td class="status">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status_text); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button type="button" class="action-button btn-details" title="Xem chi tiết" 
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
            </form>
            
            <!-- Pagination controls -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-controls">
                <div class="pagination-info">
                    Hiển thị <?php echo (($pagination['current_page'] - 1) * $pagination['per_page'] + 1); ?> 
                    đến <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?> 
                    trong tổng số <?php echo $pagination['total']; ?> tài khoản
                </div>
                <div class="pagination-buttons">
                    <?php if ($pagination['current_page'] > 1): ?>
                        <a href="<?php echo getPaginationUrl(1, $perPage, $filter); ?>" class="pagination-button">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?php echo getPaginationUrl($pagination['current_page'] - 1, $perPage, $filter); ?>" class="pagination-button">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-button disabled">
                            <i class="fas fa-angle-double-left"></i>
                        </span>
                        <span class="pagination-button disabled">
                            <i class="fas fa-angle-left"></i>
                        </span>
                    <?php endif; ?>
                    
                    <?php
                    // Display pagination numbers with ellipsis for large page counts
                    $start = max(1, $pagination['current_page'] - 2);
                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                    
                    if ($start > 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i == $pagination['current_page']): ?>
                            <span class="pagination-button active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo getPaginationUrl($i, $perPage, $filter); ?>" class="pagination-button"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; 
                    
                    if ($end < $pagination['total_pages']) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    ?>
                    
                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="<?php echo getPaginationUrl($pagination['current_page'] + 1, $perPage, $filter); ?>" class="pagination-button">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?php echo getPaginationUrl($pagination['total_pages'], $perPage, $filter); ?>" class="pagination-button">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-button disabled">
                            <i class="fas fa-angle-right"></i>
                        </span>
                        <span class="pagination-button disabled">
                            <i class="fas fa-angle-double-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Tài khoản:</div>
                    <div class="detail-value-container">
                        <div class="detail-value" id="modal-username"></div>
                        <button class="copy-btn" data-copy-target="modal-username" title="Sao chép tài khoản">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Mật khẩu:</div>
                    <div class="detail-value-container">
                        <div class="detail-value" id="modal-password"></div>
                        <button class="copy-btn" data-copy-target="modal-password" title="Sao chép mật khẩu">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Thời gian bắt đầu:</div>
                    <div class="detail-value" id="modal-start-time"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Thời hạn đến:</div>
                    <div class="detail-value" id="modal-end-time"></div>
                </div>
            </div>
            
            <div class="mountpoints-section" id="mountpoints-section">
                <h5>Danh sách các trạm (Mountpoints)</h5>
                <div class="mountpoint-list-container">
                    <table class="mountpoint-table">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Port</th>
                                <th>Trạm</th>
                            </tr>
                        </thead>
                        <tbody id="modal-mountpoints-list">
                            <!-- Mountpoints will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?php echo $base_url; ?>';
    // Thêm biến cấu hình cho phân trang
    const paginationConfig = {
        currentPage: <?php echo $pagination['current_page']; ?>,
        perPage: <?php echo $perPage; ?>,
        totalPages: <?php echo $pagination['total_pages']; ?>,
        totalRecords: <?php echo $pagination['total']; ?>,
        currentFilter: '<?php echo $filter; ?>'
    };

    // All other JavaScript logic has been moved to rtk_accountmanagement.js
    // This script block now only contains PHP-generated variables for the external JS file.
</script>

<?php
include $project_root_path . '/private/includes/footer.php';
?>