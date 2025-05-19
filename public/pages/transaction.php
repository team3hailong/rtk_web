<?php
session_start();
// Require file cấu hình - đã bao gồm các tiện ích đường dẫn
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// Sử dụng các hằng số được định nghĩa từ path_helpers
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';
$user_id = $_SESSION['user_id'];

// --- Include Required Files ---
// Không cần require config.php một lần nữa vì đã được require ở trên
include $project_root_path . '/private/includes/header.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Transaction.php';
require_once $project_root_path . '/private/utils/functions.php';

// --- Pagination parameters ---
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
// Chỉ cho phép các giá trị cụ thể cho per_page
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10; // Mặc định
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
// Chỉ cho phép các filter hợp lệ
if (!in_array($filter, ['all', 'completed', 'pending', 'failed', 'cancelled'])) {
    $filter = 'all'; // Mặc định
}

// Khởi tạo kết nối DB cho các truy vấn trực tiếp
$db = new Database();
$conn = $db->getConnection();

// --- Fetch Transactions with pagination ---
$transactionHandler = new Transaction($db);
$result = $transactionHandler->getTransactionsByUserIdWithPagination(
    $user_id, $currentPage, $perPage, $filter
); 
$transactions = $result['transactions'];
$pagination = $result['pagination'];

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
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/transaction/transaction.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper transactions-content-wrapper">
        <div class="transactions-wrapper">            <h2 class="text-2xl font-semibold mb-5">Quản Lý Giao Dịch</h2>
            <div class="filter-container">
                <div class="filter-group-header">
                    <span class="filter-group-title">Bộ lọc</span>
                </div>
                <div class="filter-group-content">
                    <div class="filter-row">
                        <!-- Lọc theo trạng thái -->
                        <div class="filter-group-item">
                            <div class="filter-label">Trạng thái:</div>
                            <div class="filter-buttons-group">
                                <button class="filter-button <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all">Tất cả</button>
                                <button class="filter-button <?php echo $filter === 'completed' ? 'active' : ''; ?>" data-filter="completed">Hoàn thành</button>
                                <button class="filter-button <?php echo $filter === 'pending' ? 'active' : ''; ?>" data-filter="pending">Chờ xử lý</button>
                                <button class="filter-button <?php echo $filter === 'failed' ? 'active' : ''; ?>" data-filter="failed">Thất bại</button>
                            </div>
                        </div>

                        <!-- Lọc theo số tiền -->
                        <div class="filter-group-item">
                            <div class="filter-label">Số tiền:</div>
                            <div class="filter-dropdown-group">
                                <select id="amount-filter" class="filter-select">
                                    <option value="all">Tất cả</option>
                                    <option value="less-than-500k">Dưới 500.000 đ</option>
                                    <option value="500k-to-1m">500.000 đ - 1.000.000 đ</option>
                                    <option value="1m-to-5m">1.000.000 đ - 5.000.000 đ</option>
                                    <option value="more-than-5m">Trên 5.000.000 đ</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="filter-row">
                        <!-- Lọc theo thời gian -->
                        <div class="filter-group-item">
                            <div class="filter-label">Thời gian:</div>
                            <div class="filter-dropdown-group">
                                <select id="time-filter" class="filter-select">
                                    <option value="all">Tất cả</option>
                                    <option value="today">Hôm nay</option>
                                    <option value="last-week">Tuần trước</option>
                                    <option value="last-month">Tháng trước</option>
                                    <option value="custom">Tùy chỉnh</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Tùy chỉnh thời gian (hiển thị khi chọn tùy chỉnh) -->
                        <div class="filter-group-item time-custom-filter" style="display: none;">
                            <div class="filter-label">Từ ngày:</div>
                            <div class="filter-input-group">
                                <input type="date" id="date-from" class="filter-date-input">
                            </div>
                        </div>
                        
                        <div class="filter-group-item time-custom-filter" style="display: none;">
                            <div class="filter-label">Đến ngày:</div>
                            <div class="filter-input-group">
                                <input type="date" id="date-to" class="filter-date-input">
                            </div>
                        </div>
                    </div>

                    <div class="filter-row">
                        <!-- Tìm kiếm -->
                        <div class="filter-group-item search-container">
                            <div class="filter-label">Tìm kiếm:</div>
                            <div class="search-group">
                                <input type="text" class="search-box" id="search-input" placeholder="Tìm theo ID, Loại GD...">
                                <button type="button" id="search-button" class="search-button">
                                    <i class="fas fa-search"></i> <span>Tìm kiếm</span>
                                </button>
                                <button type="button" id="reset-button" class="reset-button">
                                    <i class="fas fa-redo"></i> <span>Đặt lại</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="filter-group-item" style="margin-left: auto;">
                            <div class="filter-label">Hiển thị:</div>
                            <div class="per-page-selector">
                                <select id="per-page" class="filter-select">
                                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div><div class="export-invoice-section">
                <button id="export-retail-invoice-btn" class="btn-retail-invoice" disabled>
                    <i class="fas fa-file-invoice"></i> Xuất HĐ bán lẻ
                </button>
                <span id="retail-invoice-msg" style="color: #e74c3c; margin-left: 10px;"></span>
            </div>
            <div class="transactions-table-wrapper">
                <table class="transactions-table">                    <thead>
                        <tr>
                            <th>Chọn</th>
                            <th>ID Giao dịch</th>
                            <th>Thời gian</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $tx): ?>
                                <?php $status_display = Transaction::getTransactionStatusDisplay($tx['status']); ?>
                                <?php
                                    $description = ucfirst($tx['transaction_type']);
                                    if (!empty($tx['registration_id'])) {
                                        $description .= ' (ĐK: ' . htmlspecialchars($tx['registration_id']) . ')';
                                    }
                                    $display_id = 'GD' . str_pad($tx['id'], 5, '0', STR_PAD_LEFT);                                    $tx_details_for_modal = [
                                        'id' => $display_id,
                                        'raw_id' => $tx['id'],
                                        'time' => $tx['created_at'],
                                        'type' => $description,
                                        'amount' => number_format($tx['amount'], 0, ',', '.') . ' đ',
                                        'method' => $tx['payment_method'] ?? 'N/A',
                                        'status_text' => $status_display['text'],
                                        'status_class' => $status_display['class'],
                                        'updated_at' => $tx['updated_at'],
                                        'rejection_reason' => $tx['rejection_reason'] ?? null,
                                        'payment_image' => $tx['payment_image'] ?? null
                                    ];
                                    $tx_details_json = htmlspecialchars(json_encode($tx_details_for_modal), ENT_QUOTES, 'UTF-8');
                                ?>                                <tr data-status="<?php echo strtolower($tx['status']); ?>">
                                    <td class="checkbox-column"><input type="checkbox" class="retail-invoice-checkbox" value="<?php echo $tx['id']; ?>" /></td>
                                    <td><strong><?php echo htmlspecialchars($display_id); ?></strong></td>
                                    <td><?php echo htmlspecialchars($tx['created_at']); ?></td>
                                    <td class="amount"><?php echo number_format($tx['amount'], 0, ',', '.'); ?> đ</td>
                                    <td><?php echo htmlspecialchars($tx['payment_method'] ?? 'N/A'); ?></td>
                                    <td class="status">
                                        <span class="status-badge <?php echo $status_display['class']; ?>">
                                            <?php echo $status_display['text']; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button class="action-button btn-details" title="Xem chi tiết" onclick='showTransactionDetails(<?php echo $tx_details_json; ?>)'>
                                            <i class="fas fa-eye"></i> <span class="action-text">Chi tiết</span>
                                        </button>
                                        <?php $needs_proof = ($tx['status'] === 'pending' && !empty($tx['registration_id'])); ?>
                                        <?php if ($needs_proof): ?>
                                            <a href="<?php echo $base_url; ?>/public/pages/purchase/upload_proof.php?reg_id=<?php echo htmlspecialchars($tx['registration_id']); ?>"
                                               class="action-button btn-upload-proof"
                                               title="Gửi minh chứng cho GD này (ĐK: <?php echo htmlspecialchars($tx['registration_id']); ?>)">
                                                <i class="fas fa-upload"></i> <span class="action-text">Gửi MC</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php
$has_invoice = false;
$invoice_id = null;
$stmt_invoice = $conn->prepare('SELECT id FROM invoice WHERE transaction_history_id = ?');
$stmt_invoice->execute([$tx['id']]);
$invoice_row = $stmt_invoice->fetch(PDO::FETCH_ASSOC);
if ($invoice_row) {
    $has_invoice = true;
    $invoice_id = 'HD' . $invoice_row['id'];
}
?>
<?php if ($tx['status'] === 'completed'): ?>
    <?php if ($has_invoice && $invoice_id): ?>
        <a href="<?php echo $base_url; ?>/public/pages/invoice/completed_export_invoice.php?tx_id=<?php echo htmlspecialchars($tx['id']); ?>" class="action-button btn-invoice-success" title="Xem hóa đơn <?php echo htmlspecialchars($invoice_id); ?>">
            <i class="fas fa-check-circle"></i> <span class="action-text">Xem HĐ</span>
        </a>
    <?php else: ?>
        <a href="<?php echo $base_url; ?>/public/pages/invoice/request_export_invoice.php?tx_id=<?php echo htmlspecialchars($tx['id']); ?>" class="action-button btn-invoice" title="Yêu cầu xuất hóa đơn">
            <i class="fas fa-file-invoice-dollar"></i> <span class="action-text">Hóa đơn</span>
        </a>
    <?php endif; ?>
<?php endif; ?>
                                        <?php $failure_reason = null; ?>
                                        <?php if ($tx['status'] === 'failed' && $failure_reason): ?>
                                            <button
                                                class="action-button btn-reason"
                                                title="Xem lý do thất bại"
                                                onclick="showFailureReason('<?php echo htmlspecialchars($display_id); ?>', '<?php echo htmlspecialchars($failure_reason); ?>')">
                                                <i class="fas fa-info-circle"></i> <span class="action-text">Lý do</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr> <td colspan="7"> <div class="empty-state"> <i class="fas fa-receipt"></i> <p>Chưa có giao dịch nào.</p> </div> </td> </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination controls -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-controls">
                <div class="pagination-info">
                    Hiển thị <?php echo (($pagination['current_page'] - 1) * $pagination['per_page'] + 1); ?> 
                    đến <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?> 
                    trong tổng số <?php echo $pagination['total']; ?> giao dịch
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
<!-- Transaction Details Modal -->
<div id="transaction-details-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="modal-title">Chi Tiết Giao Dịch</h4>
            <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>ID Giao dịch:</strong> <span id="modal-tx-id"></span></p>
            <p><strong>Thời gian tạo:</strong> <span id="modal-tx-time"></span></p>
            <p><strong>Cập nhật lần cuối:</strong> <span id="modal-tx-updated"></span></p>
            <p><strong>Loại giao dịch:</strong> <span id="modal-tx-type"></span></p>
            <p><strong>Số tiền:</strong> <span id="modal-tx-amount"></span></p>            <p><strong>Phương thức TT:</strong> <span id="modal-tx-method"></span></p>
            <p><strong>Trạng thái:</strong>
                <span id="modal-tx-status-badge" class="status-badge status-badge-modal">
                    <span id="modal-tx-status-text"></span>
                </span>
            </p>
            <div id="rejection-reason-section" style="display: none;">
                <p>
                    <i class="fas fa-exclamation-circle"></i> Lý do từ chối:
                </p>
                <p id="modal-tx-rejection-reason"></p>
            </div>
            <div id="payment-proof-section" style="display: none;">
                <p><strong>Ảnh minh chứng thanh toán:</strong></p>
                <div class="payment-proof-container">
                    <a id="modal-tx-payment-image-link" href="#" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-image"></i> Xem ảnh minh chứng
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Thêm biến cấu hình cho phân trang
    const paginationConfig = {
        currentPage: <?php echo $pagination['current_page']; ?>,
        perPage: <?php echo $perPage; ?>,
        totalPages: <?php echo $pagination['total_pages']; ?>,
        totalRecords: <?php echo $pagination['total']; ?>,
        currentFilter: '<?php echo $filter; ?>'
    };
</script>
<script src="<?php echo $base_url; ?>/public/assets/js/pages/transaction.js"></script>
<?php
include $project_root_path . '/private/includes/footer.php';
?>