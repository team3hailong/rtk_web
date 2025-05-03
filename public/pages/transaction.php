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

// Khởi tạo kết nối DB cho các truy vấn trực tiếp
$db = new Database();
$conn = $db->getConnection();

// --- Fetch Real Transactions ---
$transactionHandler = new Transaction($db);
$transactions = $transactionHandler->getTransactionsByUserId($user_id); // Fetch transactions for the user

?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/transaction/transaction.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper transactions-content-wrapper">
        <div class="transactions-wrapper">
            <h2 class="text-2xl font-semibold mb-5">Lịch Sử Giao Dịch</h2>
            <div class="filter-section">
                <div class="filter-buttons-group">
                    <button class="filter-button active" data-filter="all">Tất cả</button>
                    <button class="filter-button" data-filter="completed">Hoàn thành</button>
                    <button class="filter-button" data-filter="pending">Chờ xử lý</button>
                    <button class="filter-button" data-filter="failed">Thất bại</button>
                </div>
                <input type="text" class="search-box" placeholder="Tìm theo ID, Loại GD...">
            </div>
            <div class="transactions-table-wrapper">
                <table class="transactions-table">
                    <thead>
                        <tr>
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
                                    $display_id = 'GD' . str_pad($tx['id'], 5, '0', STR_PAD_LEFT);
                                    $tx_details_for_modal = [
                                        'id' => $display_id,
                                        'raw_id' => $tx['id'],
                                        'time' => $tx['created_at'],
                                        'type' => $description,
                                        'amount' => number_format($tx['amount'], 0, ',', '.') . ' đ',
                                        'method' => $tx['payment_method'] ?? 'N/A',
                                        'status_text' => $status_display['text'],
                                        'status_class' => $status_display['class'],
                                        'updated_at' => $tx['updated_at'],
                                        'rejection_reason' => $tx['rejection_reason'] ?? null
                                    ];
                                    $tx_details_json = htmlspecialchars(json_encode($tx_details_for_modal), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
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
                             <tr> <td colspan="6"> <div class="empty-state"> <i class="fas fa-receipt"></i> <p>Chưa có giao dịch nào.</p> </div> </td> </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
            <p><strong>Số tiền:</strong> <span id="modal-tx-amount"></span></p>
            <p><strong>Phương thức TT:</strong> <span id="modal-tx-method"></span></p>
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
        </div>
    </div>
</div>
<script src="<?php echo $base_url; ?>/public/assets/js/pages/transaction.js"></script>
<?php
include $project_root_path . '/private/includes/footer.php';