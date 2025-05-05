<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// Khởi tạo kết nối database
$db = new Database();
$conn = $db->getConnection();

// Retrieve transactions for the current user
$stmt = $conn->prepare("
    SELECT 
        th.id, 
        th.registration_id, 
        th.transaction_type, 
        th.amount, 
        th.status, 
        th.payment_method,
        th.payment_image,
        th.export_invoice,
        th.payment_confirmed,
        th.payment_confirmed_at,
        th.created_at AS time, 
        th.updated_at,
        r.package_id,
        p.name AS package_name
    FROM 
        transaction_history th
    LEFT JOIN 
        registration r ON th.registration_id = r.id
    LEFT JOIN 
        package p ON r.package_id = p.id
    WHERE 
        th.user_id = :user_id
    ORDER BY 
        th.created_at DESC
");

$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process transaction data for display
foreach ($transactions as &$tx) {
    // Format amount with thousand separators
    $tx['amount_formatted'] = number_format($tx['amount'], 0, ',', '.') . ' đ';
    
    // Format date - fix null parameter issue with strtotime()
    $tx['time'] = !empty($tx['time']) ? date('d/m/Y H:i', strtotime($tx['time'])) : 'N/A';
    $tx['updated_at'] = !empty($tx['updated_at']) ? date('d/m/Y H:i', strtotime($tx['updated_at'])) : 'N/A';
    
    // Set method display text
    $tx['method'] = $tx['payment_method'] ?? 'Chuyển khoản ngân hàng';
    
    // Set type display text
    switch($tx['transaction_type']) {
        case 'purchase':
            $tx['type'] = 'Mua gói';
            break;
        case 'renewal':
            $tx['type'] = 'Gia hạn';
            break;
        case 'refund':
            $tx['type'] = 'Hoàn tiền';
            break;
        default:
            $tx['type'] = $tx['transaction_type'];
    }
    
    // Set status class and text
    switch($tx['status']) {
        case 'pending':
            $tx['status_class'] = 'status-pending';
            $tx['status_text'] = 'Chờ xác nhận';
            break;
        case 'completed':
            $tx['status_class'] = 'status-success';
            $tx['status_text'] = 'Hoàn thành';
            break;
        case 'failed':
            $tx['status_class'] = 'status-failed';
            $tx['status_text'] = 'Thất bại';
            break;
        case 'refunded':
            $tx['status_class'] = 'status-refunded';
            $tx['status_text'] = 'Đã hoàn tiền';
            break;
        default:
            $tx['status_class'] = '';
            $tx['status_text'] = $tx['status'];
    }
}
?>

<!-- CSS styles for the transaction page -->
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/transaction/transaction.css">

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="content-header">
            <h1>LỊCH SỬ GIAO DỊCH</h1>
        </div>
        
        <?php if(isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Minh chứng thanh toán đã được tải lên thành công và đang chờ xác nhận.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['invoice']) && $_GET['invoice'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Yêu cầu xuất hóa đơn đã được ghi nhận. Bạn sẽ nhận được email khi hóa đơn được xử lý.
            </div>
        <?php endif; ?>
        
        <div class="transactions-container">
            <?php if(empty($transactions)): ?>
                <div class="no-transactions">
                    <p>Bạn chưa có giao dịch nào.</p>
                    <a href="<?php echo $base_url; ?>/public/pages/purchase/packages.php" class="btn btn-primary">Mua gói ngay</a>
                </div>
            <?php else: ?>
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Mã GD</th>
                            <th>Thời gian</th>
                            <th>Loại</th>
                            <th>Gói dịch vụ</th>
                            <th>Trạng thái</th>
                            <th>Số tiền</th>
                            <th class="action-column">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $tx): ?>
                            <tr>
                                <td>#<?php echo $tx['id']; ?></td>
                                <td><?php echo $tx['time']; ?></td>
                                <td><?php echo $tx['type']; ?></td>
                                <td><?php echo $tx['package_name'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $tx['status_class']; ?>">
                                        <?php echo $tx['status_text']; ?>
                                    </span>
                                </td>
                                <td class="amount"><?php echo $tx['amount_formatted']; ?></td>
                                <td class="actions">
                                    <button class="action-button btn-view" 
                                            onclick='showTransactionDetails(<?php echo json_encode($tx); ?>)' 
                                            title="Xem chi tiết">
                                        <i class="fas fa-eye"></i> <span class="action-text">Chi tiết</span>
                                    </button>
                                    
                                    <?php if($tx['status'] === 'pending'): ?>
                                        <!-- Upload proof button only for pending transactions -->
                                        <a href="<?php echo $base_url; ?>/public/pages/purchase/upload_proof.php?reg_id=<?php echo htmlspecialchars($tx['registration_id']); ?>"
                                           class="action-button btn-upload-proof"
                                           title="Gửi minh chứng cho GD này (ĐK: <?php echo htmlspecialchars($tx['registration_id']); ?>)">
                                            <i class="fas fa-upload"></i> <span class="action-text">Gửi MC</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if($tx['status'] === 'completed'): ?>
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
                                        <?php if($has_invoice && $invoice_id): ?>
                                            <!-- Invoice status button -->
                                            <a href="<?php echo $base_url; ?>/public/pages/invoice/completed_export_invoice.php?tx_id=<?php echo $tx['id']; ?>"
                                               class="action-button btn-invoice" 
                                               title="Xem thông tin hóa đơn">
                                                <i class="fas fa-file-invoice"></i> <span class="action-text">HD: <?php echo $invoice_id; ?></span>
                                            </a>
                                        <?php elseif($tx['export_invoice']): ?>
                                            <!-- Invoice requested but not yet processed -->
                                            <button class="action-button btn-invoice disabled" disabled 
                                                    title="Yêu cầu xuất hóa đơn đang được xử lý">
                                                <i class="fas fa-file-invoice"></i> <span class="action-text">Đang xử lý</span>
                                            </button>
                                        <?php else: ?>
                                            <!-- Request invoice button -->
                                            <a href="<?php echo $base_url; ?>/public/pages/invoice/request_export_invoice.php?tx_id=<?php echo $tx['id']; ?>"
                                               class="action-button btn-invoice-request"
                                               title="Yêu cầu xuất hóa đơn cho giao dịch này">
                                                <i class="fas fa-file-invoice"></i> <span class="action-text">Xuất HD</span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Mã giao dịch:</div>
                    <div class="detail-value" id="modal-tx-id"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Thời gian tạo:</div>
                    <div class="detail-value" id="modal-tx-time"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Cập nhật lần cuối:</div>
                    <div class="detail-value" id="modal-tx-updated"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Loại giao dịch:</div>
                    <div class="detail-value" id="modal-tx-type"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Số tiền:</div>
                    <div class="detail-value" id="modal-tx-amount"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phương thức:</div>
                    <div class="detail-value" id="modal-tx-method"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Trạng thái:</div>
                    <div class="detail-value"><span id="modal-tx-status-badge" class="status-badge"></span></div>
                </div>
                <div class="detail-item" id="payment-proof-section" style="display:none;">
                    <div class="detail-label">Minh chứng:</div>
                    <div class="detail-value">
                        <a href="#" id="modal-payment-proof-link" target="_blank">Xem minh chứng</a>
                    </div>
                </div>
                <div class="detail-item" id="rejection-reason-section" style="display:none;">
                    <div class="detail-label">Lý do từ chối:</div>
                    <div class="detail-value" id="modal-tx-rejection-reason"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for the transaction page -->
<script src="<?php echo $base_url; ?>/public/assets/js/pages/transaction.js"></script>
<script>
// Additional JS for handling payment proof in modal
function showTransactionDetails(txData) {
    if (!modalOverlay || !txData) return;
    
    // Set basic transaction details using existing function
    modalTitle.textContent = `Chi Tiết Giao Dịch #${txData.id}`;
    modalTxId.textContent = txData.id;
    modalTxTime.textContent = txData.time;
    modalTxUpdated.textContent = txData.updated_at;
    modalTxType.textContent = txData.type;
    modalTxAmount.textContent = txData.amount_formatted;
    modalTxMethod.textContent = txData.method;
    modalTxStatusBadge.className = 'status-badge status-badge-modal ' + txData.status_class;
    modalTxStatusBadge.textContent = txData.status_text;
    
    // Handle payment proof if present
    const paymentProofSection = document.getElementById('payment-proof-section');
    const paymentProofLink = document.getElementById('modal-payment-proof-link');
    
    if (txData.payment_image) {
        const proofUrl = '<?php echo $base_url; ?>/public/uploads/payment_proofs/' + txData.payment_image;
        paymentProofLink.href = proofUrl;
        paymentProofSection.style.display = 'block';
    } else {
        paymentProofSection.style.display = 'none';
    }
    
    // Only show rejection reason if the status is 'failed'
    if (txData.status_class === 'status-failed' && txData.rejection_reason) {
        modalTxRejectionReason.textContent = txData.rejection_reason;
        rejectionReasonSection.style.display = 'block';
    } else {
        rejectionReasonSection.style.display = 'none';
    }
    
    modalOverlay.classList.add('active');
}
</script>


<<<<<<< HEAD
<?php
=======

>>>>>>> fdb846ab7b7ee896ea5a7a023765246ce690ff39
include $project_root_path . '/private/includes/footer.php';
