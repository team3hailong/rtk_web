<?php
session_start();

// --- Base URL Configuration ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
// Giả sử file này nằm trong /pages/
$script_dir = dirname($_SERVER['PHP_SELF']); // Should be /pages
$base_project_dir = dirname($script_dir); // Lùi 1 cấp
$base_url = $protocol . $domain . ($base_project_dir === '/' || $base_project_dir === '\\' ? '' : $base_project_dir);

// --- Project Root Path for Includes ---
$project_root_path = dirname(dirname(__DIR__)); // Lùi 2 cấp từ thư mục chứa file này (pages) để đến gốc project

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/login.php');
    exit;
}

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';
$user_id = $_SESSION['user_id'];

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
// ====> INCLUDE DATABASE & TRANSACTION CLASS <====
include $project_root_path . '/private/config/database.php'; // Include DB config
include $project_root_path . '/private/classes/Database.php'; // Include Database class
include $project_root_path . '/private/classes/Transaction.php'; // Include Transaction class
// ====> THÊM DÒNG NÀY ĐỂ INCLUDE FILE HÀM <====
include $project_root_path . '/private/utils/functions.php'; // Sửa đường dẫn

// --- Fetch Real Transactions ---
$db = new Database(); // Create DB instance
$transactionHandler = new Transaction($db); // Create Transaction instance
$transactions = $transactionHandler->getTransactionsByUserId($user_id); // Fetch transactions for the user

// Hàm helper để lấy text và class cho status
function get_transaction_status_display($status) {
    switch (strtolower($status)) { // Convert to lowercase for case-insensitivity
        case 'completed':
            return ['text' => 'Hoàn thành', 'class' => 'status-completed'];
        case 'pending':
            return ['text' => 'Chờ xử lý', 'class' => 'status-pending'];
        case 'failed':
            return ['text' => 'Thất bại', 'class' => 'status-failed'];
        case 'cancelled': // Keep cancelled if used elsewhere, though DB uses 'refunded'
             return ['text' => 'Đã hủy', 'class' => 'status-cancelled'];
        case 'refunded': // Add refunded status from DB
            return ['text' => 'Đã hoàn tiền', 'class' => 'status-refunded']; // Use a new class or reuse an existing one
        default:
            return ['text' => 'Không xác định', 'class' => 'status-unknown'];
    }
}

?>

<style>
    /* --- Kế thừa các style trước --- */
     .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0.8rem 1.5rem; background: var(--gray-50); border-radius: var(--rounded-md); border: 1px solid var(--gray-200); }
     .user-info .highlight { color: var(--primary-600); font-weight: var(--font-semibold); }
    .transactions-wrapper { padding: 0rem 1rem 1rem 1rem; }
    .upload-proof-section { background: white; border: 1px solid var(--primary-200); border-radius: var(--rounded-lg); padding: 1.5rem; margin-bottom: 2rem; }
    .upload-proof-section h3 { font-size: var(--font-size-lg); font-weight: var(--font-semibold); color: var(--primary-700); margin-bottom: 1rem; }
    .upload-proof-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: start; }
    .form-group { margin-bottom: 0.75rem; margin-right: 1.25rem; }
    .form-group label { display: block; font-weight: var(--font-medium); color: var(--gray-700); margin-bottom: 0.5rem; font-size: var(--font-size-sm); }
    .form-control { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid var(--gray-300); border-radius: var(--rounded-md); font-size: var(--font-size-sm); transition: border-color 0.2s ease; }
    .form-control:focus { outline: none; border-color: var(--primary-500); box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2); }
    .form-control[type="file"] { padding: 0.4rem 0.8rem; }
    .btn-upload { padding: 0.65rem 1.5rem; background-color: var(--primary-500); color: white; border: none; border-radius: var(--rounded-md); font-weight: var(--font-semibold); cursor: pointer; transition: background-color 0.2s ease; font-size: var(--font-size-sm); white-space: nowrap; }
    .btn-upload:hover { background-color: var(--primary-600); }
    .filter-section { margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
    .filter-button { padding: 0.4rem 0.9rem; border: 1px solid var(--gray-300); border-radius: var(--rounded-full); background: white; cursor: pointer; transition: all 0.2s ease; font-size: var(--font-size-sm); color: var(--gray-700); }
    .filter-button.active { background: var(--primary-500); color: white; border-color: var(--primary-500); }
    .search-box { padding: 0.5rem 0.8rem; border: 1px solid var(--gray-300); border-radius: var(--rounded-md); width: 250px; max-width: 100%; font-size: var(--font-size-sm); margin-left: auto; }
     .search-box:focus { outline: none; border-color: var(--primary-500); }
    .transactions-table-wrapper { overflow-x: auto; background: white; border-radius: var(--rounded-lg); border: 1px solid var(--gray-200); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .transactions-table { width: 100%; border-collapse: collapse; }
    .transactions-table th, .transactions-table td { padding: 0.9rem 1rem; text-align: left; border-bottom: 1px solid var(--gray-200); font-size: var(--font-size-sm); vertical-align: middle; }
    .transactions-table th { background-color: var(--gray-50); font-weight: var(--font-semibold); color: var(--gray-600); white-space: nowrap; }
     .transactions-table tr:last-child td { border-bottom: none; }
     .transactions-table tr:hover { background-color: var(--gray-50); }
     .transactions-table td.amount { font-weight: var(--font-medium); color: var(--gray-800); white-space: nowrap; }
     .transactions-table td.status { text-align: center; }
     .transactions-table td.actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.3rem;
    }
    .status-badge { padding: 0.3rem 0.8rem; border-radius: var(--rounded-full); font-size: 0.8rem; display: inline-block; font-weight: var(--font-medium); text-align: center; min-width: 80px; }
    .status-completed { background: var(--badge-green-bg); color: var(--badge-green-text); }
    .status-pending { background: #fffbeb; /* Light yellow background */ color: #b45309; /* Dark yellow/orange text */ border: 1px solid #fde68a; /* Yellow border */ }
    .status-failed { background: var(--badge-red-bg); color: var(--badge-red-text); }
    .status-cancelled { background: var(--gray-200); color: var(--gray-600); }
    .status-unknown { background: var(--gray-100); color: var(--gray-500); }
    .status-refunded { background: var(--gray-200); color: var(--gray-600); }
    .action-button {
        padding: 0.5rem 0.5rem;
        border: none;
        border-radius: var(--rounded-md);
        cursor: pointer;
        font-size: var(--font-size-xs);
        transition: background 0.2s ease, opacity 0.2s ease;
        opacity: 0.9;
        text-decoration: none;
        color: white;
        display: block;
        width: 100px;
        box-sizing: border-box;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .action-button:hover { opacity: 1; }

    .btn-details { background: var(--gray-200); color: var(--gray-700); }
    .btn-details:hover { background: var(--gray-300); }
    .btn-upload-proof { background: #fef3c7; /* Lighter yellow background */ color: #92400e; /* Darker orange/brown text */ border: 1px solid #fcd34d; /* Yellow border */ width: calc(100px - 2px); }
    .btn-upload-proof:hover { background: #fcd34d; /* Yellow background on hover */ color: #78350f; /* Darker brown text on hover */ }
    .btn-reason { background: var(--badge-red-bg); color: var(--badge-red-text); }
    .btn-reason:hover { background: var(--badge-red-text); color: white; }
    .btn-invoice { background: var(--primary-500); color: white; }
    .btn-invoice:hover { background: var(--primary-600); }

    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--gray-500); background: white; border-radius: var(--rounded-lg); border: 1px dashed var(--gray-300); margin-top: 1.5rem; }
    .empty-state i { font-size: 2.5rem; color: var(--gray-400); margin-bottom: 1rem; display: block; }

    /* --- Modal Styles --- */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: var(--rounded-lg);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        width: 90%;
        max-width: 500px;
        position: relative;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-content {
        transform: scale(1);
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--gray-200);
        padding-bottom: 0.8rem;
        margin-bottom: 1rem;
    }
    .modal-header h4 {
        font-size: var(--font-size-lg);
        font-weight: var(--font-semibold);
        color: var(--gray-800);
    }
    .modal-close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--gray-500);
        padding: 0.2rem;
        line-height: 1;
    }
    .modal-close-btn:hover {
        color: var(--gray-700);
    }
    .modal-body p {
        margin-bottom: 0.75rem;
        font-size: var(--font-size-sm);
        color: var(--gray-700);
        line-height: 1.6;
    }
    .modal-body strong {
        font-weight: var(--font-semibold);
        color: var(--gray-900);
        min-width: 120px;
        display: inline-block;
    }
    .modal-body .status-badge-modal {
        margin-left: 5px;
        vertical-align: middle;
    }

    /* --- Responsive Adjustments --- */
    @media (max-width: 992px) {
        /* Hide less critical columns on medium screens if needed */
        /* Example: Hide 'Method' column */
        /* .transactions-table th:nth-child(5), .transactions-table td:nth-child(5) { display: none; } */
        .transactions-table th, .transactions-table td { padding: 0.8rem 0.6rem; }
        .action-button { padding: 0.4rem 0.6rem; width: 90px; /* Slightly smaller buttons */ }
    }

     @media (max-width: 768px) {
         .content-header { flex-direction: column; align-items: flex-start; gap: 0.5rem;}
         .filter-section { flex-direction: column; align-items: stretch; }
         .search-box { width: 100%; margin-left: 0; }
         /* Hide Time and Method columns */
         .transactions-table th:nth-child(2), .transactions-table td:nth-child(2),
         .transactions-table th:nth-child(5), .transactions-table td:nth-child(5) { display: none; }
         .transactions-table th, .transactions-table td { padding: 0.7rem 0.5rem; /* Reduced padding */ font-size: var(--font-size-xs, 0.75rem); /* Smaller font */ }
         .transactions-table td.actions {
             /* Stack buttons vertically */
             display: flex;
             flex-direction: column;
             align-items: stretch; /* Make buttons full width of the cell */
             gap: 0.4rem; /* Space between buttons */
             width: 100px; /* Adjust width as needed */
         }
         .action-button {
             width: 100%; /* Make buttons full width */
             margin-right: 0;
             margin-bottom: 0; /* Gap is handled by flex */
             padding: 0.5rem 0.5rem; /* Adjust padding */
             font-size: var(--font-size-xs, 0.75rem);
         }
         .status-badge { padding: 0.25rem 0.6rem; font-size: 0.7rem; min-width: 70px; }
         .modal-content {
             max-width: 95%;
             padding: 1.5rem;
         }
         .modal-header h4 { font-size: var(--font-size-base); }
         .modal-body p { font-size: var(--font-size-xs); }
         .modal-body strong { min-width: 100px; }
     }

     @media (max-width: 576px) {
         .transactions-wrapper { padding: 0 0.5rem 1rem 0.5rem; }
         .content-header { padding: 0.6rem 1rem; }
         h2 { font-size: 1.25rem; /* --font-size-xl */ margin-bottom: 1rem; }
         .filter-section { gap: 0.5rem; }
         .filter-button { padding: 0.3rem 0.7rem; font-size: var(--font-size-xs, 0.75rem); }
         .search-box { padding: 0.4rem 0.6rem; font-size: var(--font-size-xs, 0.75rem); }
         /* Hide Transaction Type column as well */
         .transactions-table th:nth-child(3), .transactions-table td:nth-child(3) { display: none; }
         .transactions-table th, .transactions-table td { padding: 0.6rem 0.4rem; }
         .transactions-table td.amount { font-size: var(--font-size-sm); }
         .transactions-table td.actions { width: 90px; } /* Further adjust action column width */
         .action-button { padding: 0.4rem; }
         .status-badge { padding: 0.2rem 0.5rem; font-size: 0.65rem; min-width: 60px; }
         .empty-state { padding: 2rem 1rem; }
         .empty-state i { font-size: 2rem; }
     }
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="content-wrapper" style="padding-top: 1rem;">
        <!-- Header nhỏ trong content -->
        <div class="content-header">
            <div class="user-info">
                <span>User ID: <span class="highlight"><?php echo htmlspecialchars($user_id); ?></span></span>
                <span>|</span>
                <span>Username: <span class="highlight"><?php echo htmlspecialchars($user_username); ?></span></span>
            </div>
             <span style="font-size: var(--font-size-sm); color: var(--gray-500);"><?php echo date('Y-m-d H:i:s'); ?> UTC</span>
        </div>

        <!-- Wrapper chính -->
        <div class="transactions-wrapper">
            <h2 class="text-2xl font-semibold mb-5">Lịch Sử Giao Dịch</h2>

            <!-- Bộ lọc và Tìm kiếm -->
            <div class="filter-section">
                 <button class="filter-button active" data-filter="all">Tất cả</button>
                <button class="filter-button" data-filter="completed">Hoàn thành</button>
                <button class="filter-button" data-filter="pending">Chờ xử lý</button>
                <button class="filter-button" data-filter="failed">Thất bại</button>
                <button class="filter-button" data-filter="refunded">Đã hoàn tiền</button>
                <input type="text" class="search-box" placeholder="Tìm theo ID, Loại GD...">
            </div>

            <!-- Bảng danh sách giao dịch -->
            <div class="transactions-table-wrapper">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>ID Giao dịch</th>
                            <th>Thời gian</th>
                            <th>Loại giao dịch</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th style="text-align: right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $tx): ?>
                                <?php $status_display = get_transaction_status_display($tx['status']); ?>
                                <?php
                                    $description = ucfirst($tx['transaction_type']);
                                    if (!empty($tx['registration_id'])) {
                                        $description .= ' (ĐK: ' . htmlspecialchars($tx['registration_id']) . ')';
                                    }
                                    $display_id = 'GD' . str_pad($tx['id'], 5, '0', STR_PAD_LEFT);

                                    // Prepare data for the modal
                                    $tx_details_for_modal = [
                                        'id' => $display_id,
                                        'raw_id' => $tx['id'],
                                        'time' => $tx['created_at'],
                                        'type' => $description,
                                        'amount' => number_format($tx['amount'], 0, ',', '.') . ' đ',
                                        'method' => $tx['payment_method'] ?? 'N/A',
                                        'status_text' => $status_display['text'],
                                        'status_class' => $status_display['class'],
                                        'updated_at' => $tx['updated_at']
                                    ];
                                    $tx_details_json = htmlspecialchars(json_encode($tx_details_for_modal), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($display_id); ?></strong></td>
                                    <td><?php echo htmlspecialchars($tx['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($description); ?></td>
                                    <td class="amount"><?php echo number_format($tx['amount'], 0, ',', '.'); ?> đ</td>
                                    <td><?php echo htmlspecialchars($tx['payment_method'] ?? 'N/A'); ?></td>
                                    <td class="status">
                                        <span class="status-badge <?php echo $status_display['class']; ?>">
                                            <?php echo $status_display['text']; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <button class="action-button btn-details" title="Xem chi tiết" onclick='showTransactionDetails(<?php echo $tx_details_json; ?>)'>
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </button>

                                        <?php
                                            // Show "Gửi MC" button only if status is pending AND there's a registration ID
                                            $needs_proof = ($tx['status'] === 'pending' && !empty($tx['registration_id']));
                                        ?>
                                        <?php if ($needs_proof): ?>
                                            <a href="<?php echo $base_url; ?>/pages/purchase/upload_proof.php?reg_id=<?php echo htmlspecialchars($tx['registration_id']); ?>"
                                               class="action-button btn-upload-proof"
                                               title="Gửi minh chứng cho GD này (ĐK: <?php echo htmlspecialchars($tx['registration_id']); ?>)">
                                                <i class="fas fa-upload"></i> Gửi MC
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                            $has_invoice = ($tx['status'] === 'completed' && !empty($tx['registration_id']));
                                            $invoice_id = $has_invoice ? 'HD' . $tx['registration_id'] : null;
                                        ?>
                                        <?php if ($has_invoice && $invoice_id): ?>
                                            <a href="/path/to/download/invoice.php?reg_id=<?php echo htmlspecialchars($tx['registration_id']); ?>" class="action-button btn-invoice" title="Tải hóa đơn <?php echo htmlspecialchars($invoice_id); ?>" download>
                                                <i class="fas fa-file-invoice-dollar"></i> Hóa đơn
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                            $failure_reason = null;
                                        ?>
                                        <?php if ($tx['status'] === 'failed' && $failure_reason): ?>
                                            <button
                                                class="action-button btn-reason"
                                                title="Xem lý do thất bại"
                                                onclick="showFailureReason('<?php echo htmlspecialchars($display_id); ?>', '<?php echo htmlspecialchars($failure_reason); ?>')">
                                                <i class="fas fa-info-circle"></i> Lý do
                                            </button>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr> <td colspan="7"> <div class="empty-state" style="border: none; margin: 0; padding: 2rem 1rem;"> <i class="fas fa-receipt"></i> <p>Chưa có giao dịch nào.</p> </div> </td> </tr>
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
            <!-- Add more details here if needed -->
        </div>
    </div>
</div>

<script>
// --- Modal Elements ---
const modalOverlay = document.getElementById('transaction-details-modal');
const modalTxId = document.getElementById('modal-tx-id');
const modalTxTime = document.getElementById('modal-tx-time');
const modalTxUpdated = document.getElementById('modal-tx-updated');
const modalTxType = document.getElementById('modal-tx-type');
const modalTxAmount = document.getElementById('modal-tx-amount');
const modalTxMethod = document.getElementById('modal-tx-method');
const modalTxStatusBadge = document.getElementById('modal-tx-status-badge');
const modalTxStatusText = document.getElementById('modal-tx-status-text');
const modalTitle = document.getElementById('modal-title');


// --- Function to show transaction details modal ---
function showTransactionDetails(txData) {
    if (!modalOverlay || !txData) return;

    // Populate modal content
    modalTitle.textContent = `Chi Tiết Giao Dịch #${txData.id}`;
    modalTxId.textContent = txData.id;
    modalTxTime.textContent = txData.time;
    modalTxUpdated.textContent = txData.updated_at;
    modalTxType.textContent = txData.type;
    modalTxAmount.textContent = txData.amount;
    modalTxMethod.textContent = txData.method;
    modalTxStatusText.textContent = txData.status_text;

    // Update status badge class
    modalTxStatusBadge.className = 'status-badge status-badge-modal ' + txData.status_class; // Reset classes and add new one

    // Show modal
    modalOverlay.classList.add('active');
}

// --- Function to close modal ---
function closeModal() {
    if (modalOverlay) {
        modalOverlay.classList.remove('active');
    }
}

// --- Close modal when clicking overlay ---
if (modalOverlay) {
    modalOverlay.addEventListener('click', function(event) {
        // Close only if clicked directly on the overlay, not the content
        if (event.target === modalOverlay) {
            closeModal();
        }
    });
}

// --- Close modal with Escape key ---
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('active')) {
        closeModal();
    }
});

// --- Hàm hiển thị lý do thất bại ---
function showFailureReason(transactionId, reason) {
    alert(`Lý do thất bại cho GD #${transactionId}:

${reason || 'Không có thông tin lý do.'}`);
}

document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-button');
    const transactionRows = document.querySelectorAll('.transactions-table tbody tr');
    const searchBox = document.querySelector('.search-box');

    // --- Filter Logic ---
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            applyFiltersAndSearch();
        });
    });

    // --- Search Logic ---
    searchBox.addEventListener('input', function() {
        applyFiltersAndSearch();
    });

    // --- Combined Filter and Search Function ---
    function applyFiltersAndSearch() {
        const activeFilterButton = document.querySelector('.filter-button.active');
        const filterValue = activeFilterButton ? activeFilterButton.getAttribute('data-filter') : 'all';
        const searchTerm = searchBox.value.toLowerCase().trim();

        transactionRows.forEach(row => {
            if (row.querySelector('.empty-state')) {
                row.style.display = '';
                return;
            }

            const statusCell = row.querySelector('td.status .status-badge');
            let rowStatus = 'unknown';
            if (statusCell) {
                if (statusCell.classList.contains('status-completed')) rowStatus = 'completed';
                else if (statusCell.classList.contains('status-pending')) rowStatus = 'pending';
                else if (statusCell.classList.contains('status-failed')) rowStatus = 'failed';
                else if (statusCell.classList.contains('status-refunded')) rowStatus = 'refunded';
                else if (statusCell.classList.contains('status-cancelled')) rowStatus = 'cancelled';
            }
            const statusMatch = (filterValue === 'all' || rowStatus === filterValue);

            const idCell = row.cells[0]?.textContent.toLowerCase() || '';
            const typeCell = row.cells[2]?.textContent.toLowerCase() || '';
            const searchMatch = (searchTerm === '' || idCell.includes(searchTerm) || typeCell.includes(searchTerm));

            if (statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const visibleRows = Array.from(transactionRows).filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
        const emptyStateRow = document.querySelector('.transactions-table tbody .empty-state');
        if (visibleRows.length === 0 && !emptyStateRow) {
            console.log("No transactions match the current filter/search.");
        }
    }

    applyFiltersAndSearch();
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>