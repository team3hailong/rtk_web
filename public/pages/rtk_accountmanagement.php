<?php

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';

// --- Include Database and Repository ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/RtkAccount.php';

// --- OPTIMIZED: Include a single, consolidated CSS and JS file ---
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/rtk/rtk_accountmanagement.css?v=' . time() . '">';
echo '<script src="' . $base_url . '/public/assets/js/pages/rtk/rtk_accountmanagement.js?v=' . time() . '"></script>';

// --- Xử lý tham số từ URL cho phân trang ---
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if (!in_array($filter, ['all', 'active', 'expired', 'locked'])) {
    $filter = 'all';
}

// ===============================================
// == FETCH ACCOUNT DATA FROM DATABASE WITH PAGINATION ==
// ===============================================
$db = new Database();
$rtkAccountManager = new RtkAccount($db);
$userId = $_SESSION['user_id'];

$result = $rtkAccountManager->getAccountsByUserIdWithPagination($userId, $currentPage, $perPage, $filter);
$accounts = $result['accounts'];
$pagination = $result['pagination'];

if (method_exists($db, 'close')) { $db->close(); }

// Helper functions
function calculate_days_diff($end_date_str) {
    if (!$end_date_str) return ['remaining' => null, 'expired' => null];
    try {
        $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        $end_date = new DateTime($end_date_str, $tz);
        $now = new DateTime('now', $tz);
        $interval = $now->diff($end_date);
        $days = (int)$interval->format('%r%a');
        return ($days >= 0) ? ['remaining' => $days, 'expired' => null] : ['remaining' => null, 'expired' => abs($days)];
    } catch (Exception $e) {
        return ['remaining' => null, 'expired' => null];
    }
}

function format_date_display($date_str) {
    if (!$date_str) return 'N/A';
    try {
        return (new DateTime($date_str, new DateTimeZone('Asia/Ho_Chi_Minh')))->format('d-m-Y');
    } catch (Exception $e) {
        return 'N/A';
    }
}

function getPaginationUrl($page, $perPage, $filter) {
    $params = ['page' => $page, 'per_page' => $perPage];
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
            <h2 class="text-2xl font-semibold mb-5">Quản Lý Tài Khoản RTK</h2>

            <!-- OPTIMIZED: Filter container -->
            <div class="filter-container">
                <div class="filter-group-header">
                    <span class="filter-group-title">Bộ lọc</span>
                    <button type="button" class="filter-toggle-btn" aria-label="Toggle filters">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="filter-group-content" style="display: none;">
                    <div class="filter-row">
                        <!-- Lọc theo trạng thái -->
                        <div class="filter-group-item">
                            <div class="filter-label">Trạng thái:</div>
                            
                            <!-- Desktop buttons -->
                            <div class="status-buttons-desktop">
                                <div class="status-filter-group">
                                    <div class="status-buttons-row">
                                        <button class="filter-button <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all"><i class="fas fa-list-ul"></i> Tất cả</button>
                                        <button class="filter-button <?php echo $filter === 'active' ? 'active' : ''; ?>" data-filter="active"><i class="fas fa-check-circle"></i> Hoạt động</button>
                                    </div>
                                    <div class="status-buttons-row">
                                        <button class="filter-button <?php echo $filter === 'expired' ? 'active' : ''; ?>" data-filter="expired"><i class="fas fa-calendar-times"></i> Hết hạn</button>
                                        <button class="filter-button <?php echo $filter === 'locked' ? 'active' : ''; ?>" data-filter="locked"><i class="fas fa-lock"></i> Đã khóa</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile select dropdown -->
                            <div class="status-select-mobile">
                                <select id="status-filter-mobile" class="filter-select">
                                    <option value="all" <?php if ($filter === 'all') echo 'selected'; ?>>Tất cả</option>
                                    <option value="active" <?php if ($filter === 'active') echo 'selected'; ?>>Hoạt động</option>
                                    <option value="expired" <?php if ($filter === 'expired') echo 'selected'; ?>>Hết hạn</option>
                                    <option value="locked" <?php if ($filter === 'locked') echo 'selected'; ?>>Đã khóa</option>
                                </select>
                            </div>
                        </div>

                        <!-- Lọc theo thời hạn còn lại -->
                        <div class="filter-group-item">
                            <div class="filter-label">Thời hạn còn lại:</div>
                            <select id="remaining-time-filter" class="filter-select">
                                <option value="all">Tất cả</option>
                                <option value="less-than-7">Dưới 7 ngày</option>
                                <option value="7-to-30">7 - 30 ngày</option>
                                <option value="30-to-90">30 - 90 ngày</option>
                                <option value="more-than-90">Trên 90 ngày</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <!-- Tìm kiếm -->
                        <div class="filter-group-item">
                            <div class="filter-label">Tìm kiếm:</div>
                            <div class="search-group">
                                <input type="text" class="search-box" id="search-input" placeholder="Tên TK, Tỉnh, Trạm...">
                                <div class="search-buttons-container">
                                    <button type="button" id="search-button" class="search-button"><i class="fas fa-search"></i> <span>Tìm kiếm</span></button>
                                    <button type="button" id="reset-button" class="reset-button"><i class="fas fa-redo"></i> <span>Đặt lại</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Section -->
            <div class="export-section">
                <div class="export-section-buttons">
                    <button id="update-survey-accounts" class="export-button btn-update-ownership">
                        <i class="fas fa-sync"></i> Cập nhật quyền sở hữu
                    </button>
                    <button id="export-excel" class="export-button btn-export-excel" disabled>
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                    <form id="renewal-form" method="post" action="<?php echo $base_url; ?>/public/pages/purchase/renewal.php">
                        <button type="submit" id="renewal-btn" class="export-button btn-renewal" disabled>
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
                <!-- Scroll hint for narrow screens: informs users the table is horizontally scrollable -->
                <div class="table-scroll-hint" role="note" aria-hidden="false">Kéo sang phải để xem thông tin tài khoản</div>
                <div class="accounts-table-wrapper">
                    <div class="accounts-table-scrollbar" aria-hidden="true">
                        <div class="accounts-table-scrollbar-track">
                            <div class="accounts-table-scrollbar-thumb" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                        </div>
                    </div>
                    <table class="accounts-table">
                        <thead>
                            <tr>
                                <th class="select-column">Chọn</th>
                                <th>Tên tài khoản</th>
                                <th>Mật khẩu</th>
                                <th>IP/Tên miền</th>
                                <th>Port</th>
                                <th>Thời hạn đến</th>
                                <th>Trạm</th>
                                <th>Hướng dẫn</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($accounts)): ?>
                                <tr>
                                    <td colspan="10">
                                        <div class="empty-state">
                                            <i class="fas fa-user-circle"></i>
                                            <p>Chưa có tài khoản nào</p>
                                            <a href="<?php echo $base_url; ?>/public/pages/purchase/packages.php" class="buy-now-btn"><?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Đăng Ký Tài Khoản Ngay' : 'Mua Tài Khoản Ngay'; ?></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $account): ?>
                                    <?php
                                        $days_diff_data = calculate_days_diff($account['effective_end_time']);
                                        $remaining_days = $days_diff_data['remaining'] ?? ($days_diff_data['expired'] !== null ? -$days_diff_data['expired'] : -9999);
                                        $data_status = $account['status'];
                                        $status_text = 'Không xác định';
                                        if ($data_status === 'active') $status_text = 'Hoạt động';
                                        elseif ($data_status === 'expired') $status_text = 'Hết hạn';
                                        elseif (in_array($data_status, ['pending', 'locked'])) {
                                            $status_text = 'Đã khóa';
                                            $data_status = 'locked';
                                        }

                                        $search_terms = strtolower(implode(' ', array_filter([
                                            $account['id'] ?? '',
                                            $account['username_acc'] ?? '',
                                            $account['province'] ?? '',
                                            $status_text,
                                            isset($account['mountpoints']) ? implode(' ', array_column($account['mountpoints'], 'mountpoint')) : ''
                                        ])));

                                        $account_details = [
                                            'id' => $account['id'],
                                            'username' => $account['username_acc'],
                                            'password' => $account['password_acc'],
                                            'start_time' => date('d/m/Y H:i', strtotime($account['effective_start_time'])),
                                            'end_time' => date('d/m/Y H:i', strtotime($account['effective_end_time'])),
                                            'status' => $status_text,
                                            'province' => $account['province'] ?? 'N/A',
                                            'mountpoints' => $account['mountpoints'] ?? [],
                                            'package_id' => $account['package_id'] ?? 0
                                        ];
                                        $account_json = htmlspecialchars(json_encode($account_details), ENT_QUOTES, 'UTF-8');
                                        
                                        // Lấy tất cả mount points
                                        $mountpoints = $account['mountpoints'] ?? [];
                                        $mountpoints_count = count($mountpoints);
                                        
                                        // Lấy IP và Port từ mount point đầu tiên (vì tất cả đều giống nhau)
                                        $first_mp = $mountpoints_count > 0 ? $mountpoints[0] : null;
                                        $ip = $first_mp ? ($first_mp['ip'] ?? 'N/A') : 'N/A';
                                        $port = $first_mp ? ($first_mp['port'] ?? 'N/A') : 'N/A';
                                        
                                        // Gộp tất cả tên trạm
                                        $station_names = array_map(function($mp) {
                                            return $mp['mountpoint'] ?? '';
                                        }, $mountpoints);
                                        $station_names = array_filter($station_names);
                                        
                                        // Giới hạn hiển thị 3 trạm đầu tiên, phần còn lại hiển thị trong tooltip
                                        $stations_title = implode(', ', $station_names); // Full list for tooltip
                                        $total_stations = count($station_names);
                                        
                                        if ($total_stations > 3) {
                                            $visible_stations = array_slice($station_names, 0, 3);
                                            $stations_display = implode(', ', $visible_stations) . ' +' . ($total_stations - 3);
                                        } else {
                                            $stations_display = implode(', ', $station_names);
                                        }
                                    ?>
                                    <tr data-status="<?php echo htmlspecialchars($data_status); ?>" data-search-terms="<?php echo htmlspecialchars($search_terms); ?>" data-remaining-days="<?php echo $remaining_days; ?>">
                                        <td class="select-column">
                                            <input type="checkbox" name="selected_accounts[]" value="<?php echo $account['id']; ?>" class="account-checkbox" data-package-id="<?php echo $account['package_id']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($account['username_acc'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($account['password_acc'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($ip); ?></td>
                                        <td><?php echo htmlspecialchars($port); ?></td>
                                        <td>
                                            <?php echo format_date_display($account['effective_end_time']); ?>
                                            <?php if ($days_diff_data['remaining'] !== null): ?>
                                                <span class="time-remaining">(Còn <?php echo $days_diff_data['remaining']; ?> ngày)</span>
                                            <?php elseif ($days_diff_data['expired'] !== null): ?>
                                                <span class="time-expired">(Quá hạn <?php echo $days_diff_data['expired']; ?> ngày)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="stations-cell" title="<?php echo htmlspecialchars($stations_title); ?>">
                                            <?php if (!empty($stations_display)): ?>
                                                <?php echo htmlspecialchars($stations_display); ?>
                                            <?php else: ?>
                                                <span style="color: #999;">Chưa có trạm</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="guide-column">
                                            <a href="<?php echo $base_url; ?>/public/pages/support/guide.php?topic=&keyword=sử+dụng+máy" 
                                               class="btn-guide-link" 
                                               title="Xem hướng dẫn sử dụng máy">
                                                <i class="fas fa-book-open"></i> Xem
                                            </a>
                                        </td>
                                        <td class="status">
                                            <span class="status-badge status-<?php echo htmlspecialchars($data_status); ?>">
                                                <?php echo htmlspecialchars($status_text); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button type="button" class="action-button btn-details" onclick='showAccountDetails(<?php echo $account_json; ?>)'>
                                                <i class="fas fa-eye"></i> Xem thêm
                                            </button>
                                            <button type="button" class="action-button btn-change-password" onclick='showChangePasswordModal(<?php echo $account_json; ?>)'>
                                                <i class="fas fa-key"></i> Đổi MK
                                            </button>
                                            <button type="button" class="action-button btn-lock" data-account-id="<?php echo $account['id']; ?>" data-enabled="<?php echo $account['enabled']; ?>">
                                                <i class="fas fa-<?php echo $account['enabled'] ? 'lock' : 'unlock'; ?>"></i> 
                                                <?php echo $account['enabled'] ? 'Khóa' : 'Mở khóa'; ?>
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
            <div class="pagination-footer">
                <div class="per-page-container">
                    <label for="per-page">Hiển thị:</label>
                    <select id="per-page" class="per-page-select">
                        <option value="10" <?php if ($perPage == 10) echo 'selected'; ?>>10</option>
                        <option value="20" <?php if ($perPage == 20) echo 'selected'; ?>>20</option>
                        <option value="50" <?php if ($perPage == 50) echo 'selected'; ?>>50</option>
                    </select>
                    <span>bản ghi / trang</span>
                </div>
                
                <div class="pagination-controls">
                    <div class="pagination-info">
                        Hiển thị <?php echo (($pagination['current_page'] - 1) * $pagination['per_page'] + 1); ?> 
                        đến <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?> 
                        trong tổng số <?php echo $pagination['total']; ?> tài khoản
                    </div>
                    <div class="pagination-buttons">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="<?php echo getPaginationUrl(1, $perPage, $filter); ?>" class="pagination-button"><i class="fas fa-angle-double-left"></i></a>
                            <a href="<?php echo getPaginationUrl($pagination['current_page'] - 1, $perPage, $filter); ?>" class="pagination-button"><i class="fas fa-angle-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-button disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="pagination-button disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        if ($start > 1) echo '<span class="pagination-ellipsis">...</span>';
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?php echo getPaginationUrl($i, $perPage, $filter); ?>" class="pagination-button <?php if ($i == $pagination['current_page']) echo 'active'; ?>"><?php echo $i; ?></a>
                        <?php endfor;
                        if ($end < $pagination['total_pages']) echo '<span class="pagination-ellipsis">...</span>';
                        ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="<?php echo getPaginationUrl($pagination['current_page'] + 1, $perPage, $filter); ?>" class="pagination-button"><i class="fas fa-angle-right"></i></a>
                            <a href="<?php echo getPaginationUrl($pagination['total_pages'], $perPage, $filter); ?>" class="pagination-button"><i class="fas fa-angle-double-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-button disabled"><i class="fas fa-angle-right"></i></span>
                            <span class="pagination-button disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
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
            <button class="modal-close-btn" onclick="closeModal('account-details-modal')">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Tài khoản:</div>
                    <div class="detail-value-container">
                        <div class="detail-value" id="modal-username"></div>
                        <button class="copy-btn" data-copy-target="modal-username" title="Sao chép tài khoản"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Mật khẩu:</div>
                    <div class="detail-value-container">
                        <div class="detail-value" id="modal-password"></div>
                        <button class="copy-btn" data-copy-target="modal-password" title="Sao chép mật khẩu"><i class="fas fa-copy"></i></button>
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
                        <tbody id="modal-mountpoints-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Survey Account Modal -->
<div id="update-survey-account-modal" class="modal-overlay">
    <div class="modal-content account-update-modal">
        <div class="modal-header">
            <h4>Cập Nhật Quyền Sở Hữu Tài Khoản</h4>
            <button class="modal-close-btn" onclick="closeModal('update-survey-account-modal')">×</button>
        </div>
        <div class="modal-body">
            <p class="mb-3">Nhập tên đăng nhập và mật khẩu của các tài khoản cần chuyển quyền sở hữu về tài khoản của bạn:</p>
            <table class="account-form-table" id="update-accounts-table">
                <thead>
                    <tr>
                        <th>Tên đăng nhập</th>
                        <th>Mật khẩu</th>
                    </tr>
                </thead>
                <tbody id="update-accounts-tbody">
                    <?php for($i = 0; $i < 5; $i++): ?>
                    <tr>
                        <td><input type="text" class="form-input username-input" placeholder="Tên đăng nhập"></td>
                        <td><input type="text" class="form-input password-input" placeholder="Mật khẩu"></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <button id="add-account-row" class="add-row-button" title="Thêm dòng"><i class="fas fa-plus"></i></button>
        </div>
        <div class="modal-footer">
            <button class="cancel-button" onclick="closeModal('update-survey-account-modal')">Hủy</button>
            <button class="confirm-button" id="confirm-update-accounts">Xác nhận</button>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="change-password-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Đổi Mật Khẩu Tài Khoản</h4>
            <button class="modal-close-btn" onclick="closeModal('change-password-modal')">×</button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-row">
                    <label class="form-label">Tên đăng nhập:</label>
                    <input type="text" id="cp-username" class="form-input" readonly>
                </div>
                <div class="form-row">
                    <label class="form-label">Mật khẩu hiện tại:</label>
                    <input type="text" id="cp-current-password" class="form-input" readonly>
                </div>
                <div class="form-row">
                    <label class="form-label">Mật khẩu mới:</label>
                    <input type="password" id="cp-new-password" class="form-input" placeholder="Nhập mật khẩu mới">
                </div>
                <div class="form-row">
                    <label class="form-label">Xác nhận mật khẩu mới:</label>
                    <input type="password" id="cp-confirm-password" class="form-input" placeholder="Nhập lại mật khẩu mới">
                </div>
            </div>
            <input type="hidden" id="cp-account-id">
        </div>
        <div class="modal-footer">
            <button class="cancel-button" onclick="closeModal('change-password-modal')">Hủy</button>
            <button class="confirm-button" id="confirm-change-password">Xác nhận</button>
        </div>
    </div>
</div>

<!-- OTP Confirmation Modal -->
<div id="otp-confirm-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Xác nhận OTP chuyển quyền</h4>
            <button class="modal-close-btn" onclick="closeModal('otp-confirm-modal')">×</button>
        </div>
        <div class="modal-body">
            <p>Nhập mã OTP được gửi đến email của chủ sở hữu hiện tại:</p>
            <input type="text" id="otp-input" class="form-input" placeholder="Mã OTP">
            <input type="hidden" id="otp-registration-id">
        </div>
        <div class="modal-footer">
            <button class="cancel-button" onclick="closeModal('otp-confirm-modal')">Hủy</button>
            <button class="confirm-button" id="confirm-otp-btn">Xác nhận</button>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?php echo $base_url; ?>';
    const paginationConfig = {
        currentPage: <?php echo $pagination['current_page']; ?>,
        perPage: <?php echo $perPage; ?>,
        totalPages: <?php echo $pagination['total_pages']; ?>,
        totalRecords: <?php echo $pagination['total']; ?>,
        currentFilter: '<?php echo $filter; ?>'
    };
</script>

<?php
include $project_root_path . '/private/includes/footer.php';
?>