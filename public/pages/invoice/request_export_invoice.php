<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

require_once $project_root_path . '/private/classes/invoice/InvoiceService.php';
require_once $project_root_path . '/private/utils/csrf_helper.php';

// Logging helper for invoice errors
function log_invoice_error($userId, $txId, $message) {
    $context = json_encode(['user_id' => $userId, 'tx_id' => $txId]);
    error_log("[" . date('Y-m-d H:i:s') . "] [User: {$userId}] Invoice Request Error: {$message} | Context: {$context}");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    if (!validate_csrf_token(filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW))) {
        log_invoice_error($_SESSION['user_id'], filter_input(INPUT_POST, 'tx_id', FILTER_VALIDATE_INT), 'CSRF validation failed');
        $_SESSION['invoice_error'] = 'CSRF validation failed. Please try again.';
        header('Location: ' . $base_url . '/public/pages/transaction.php');
        exit;
    }

    $tx_id = isset($_POST['tx_id']) ? intval($_POST['tx_id']) : 0;
    if ($tx_id <= 0) {
        log_invoice_error($_SESSION['user_id'], $tx_id, 'Invalid transaction ID');
        http_response_code(400);
        exit('Thiếu hoặc sai tham số.');
    }
    $service = new InvoiceService();
    // Check ownership
    if (!$service->checkOwnership($tx_id, $_SESSION['user_id'])) {
        log_invoice_error($_SESSION['user_id'], $tx_id, 'Unauthorized access to transaction');
        header('Location: ' . $base_url . '/public/pages/transaction.php?error=unauthorized');
        exit;
    }
    // Check user company info
    $user_info = $service->getUserInfo($_SESSION['user_id']);
    if (empty($user_info['company_name']) || empty($user_info['tax_code'])) {
        log_invoice_error($_SESSION['user_id'], $tx_id, 'Missing company_name or tax_code');
        $_SESSION['invoice_error'] = 'Vui lòng cập nhật đầy đủ thông tin công ty và mã số thuế trước khi yêu cầu xuất hóa đơn.';
        header('Location: ' . $base_url . '/public/pages/setting/invoice.php?error=missing_info');
        exit;
    }
    // Create invoice if not exists
    if (!$service->invoiceExists($tx_id)) {
        $service->createInvoice($tx_id);
    }
    header('Location: ' . $base_url . '/public/pages/transaction.php?invoice=success');
    exit;
}

$tx_id = isset($_GET['tx_id']) ? intval($_GET['tx_id']) : 0;
if ($tx_id <= 0) {
    die('Tham số không hợp lệ.');
}

// Initialize InvoiceService and fetch info
$service = new InvoiceService();
if (!$service->checkOwnership($tx_id, $_SESSION['user_id'])) {
    // Ghi log cố gắng truy cập trái phép
    error_log("Security Warning: User {$_SESSION['user_id']} attempted to access transaction {$tx_id} that doesn't belong to them");
    header('Location: ' . $base_url . '/public/pages/transaction.php?error=unauthorized');
    exit;
}

// Fetch transaction info
$info = $service->getTransactionInfo($tx_id);
if (!$info) {
    die('Không tìm thấy giao dịch.');
}

// Kiểm tra xem thông tin xuất hóa đơn có đầy đủ không
$missing_invoice_info = empty($info['company_name']) || empty($info['tax_code']);

include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/invoice/request_export_invoice.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper invoice-content-wrapper">
        <div class="invoice-request-wrapper">
            <h2>Xuất Hóa Đơn VAT</h2>
            
            <?php if ($missing_invoice_info): ?>
            <div class="alert alert-warning">
                <strong>Thông tin chưa đầy đủ!</strong> Bạn cần cập nhật thông tin công ty và mã số thuế để có thể yêu cầu xuất hóa đơn.
                <br><br>
                <a href="<?php echo $base_url; ?>/public/pages/setting/invoice.php" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> Cập nhật thông tin ngay
                </a>
            </div>
            <?php endif; ?>
            
            <div class="invoice-section">
                <h4>Thông tin giao dịch</h4>
                <table class="info-table">
                    <tr><td>ID giao dịch</td><td>:</td><td>GD<?php echo str_pad($info['transaction_id'], 5, '0', STR_PAD_LEFT); ?></td></tr>
                    <tr><td>Thời gian</td><td>:</td><td><?php echo htmlspecialchars($info['created_at'] ?? ''); ?></td></tr>
                    <tr><td>Tên gói</td><td>:</td><td><?php echo htmlspecialchars($info['package_name'] ?? ''); ?></td></tr>
                    <tr><td>Số lượng</td><td>:</td><td><?php echo htmlspecialchars($info['num_account'] ?? ''); ?></td></tr>
                    <tr><td>Giá</td><td>:</td><td><?php echo number_format($info['total_price'] ?? 0, 0, ',', '.'); ?> đ</td></tr>
                </table>
            </div>
            <div class="invoice-section">
                <h4>Thông tin xuất hóa đơn</h4>
                <table class="info-table">
                    <tr><td>Tên công ty</td><td>:</td><td>
                        <?php if (!empty($info['company_name'])): ?>
                            <?php echo htmlspecialchars($info['company_name']); ?>
                        <?php else: ?>
                            <span class="text-danger">Chưa cung cấp</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><td>Mã số thuế</td><td>:</td><td>
                        <?php if (!empty($info['tax_code'])): ?>
                            <?php echo htmlspecialchars($info['tax_code']); ?>
                        <?php else: ?>
                            <span class="text-danger">Chưa cung cấp</span>
                        <?php endif; ?>                    </td></tr>
                    <tr><td>Địa chỉ công ty</td><td>:</td><td>
                        <?php if (!empty($info['company_address'])): ?>
                            <?php echo htmlspecialchars($info['company_address']); ?>
                        <?php else: ?>
                            <span class="text-danger">Chưa cung cấp</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><td>Email</td><td>:</td><td><?php echo htmlspecialchars($info['email'] ?? ''); ?></td></tr>
                </table>
            </div>
            <form method="post" action="" class="invoice-request-form">
                <input type="hidden" name="tx_id" value="<?php echo $info['transaction_id']; ?>">
                <!-- Thêm CSRF token -->
                <?php echo generate_csrf_input(); ?>
                <button type="submit" class="btn btn-primary" <?php echo $missing_invoice_info ? 'disabled' : ''; ?>>
                    Yêu cầu xuất hóa đơn
                </button>
                <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="btn btn-cancel">Hủy</a>
            </form>
            <div class="invoice-note">
                <?php if ($missing_invoice_info): ?>
                <p><strong>Lưu ý:</strong> Không thể yêu cầu xuất hóa đơn khi thông tin công ty hoặc mã số thuế còn trống. Vui lòng cập nhật thông tin trước khi tiếp tục.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include $project_root_path . '/private/includes/footer.php'; ?>