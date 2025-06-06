<?php


// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';


init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;
$admin_site = ADMIN_SITE;

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

require_once $project_root_path . '/private/classes/invoice/InvoiceService.php';

$tx_id = isset($_GET['tx_id']) ? intval($_GET['tx_id']) : 0;
if ($tx_id <= 0) {
    die('Tham số không hợp lệ.');
}

// Initialize InvoiceService and fetch data
$service = new InvoiceService();
$info = $service->getTransactionInfo($tx_id);
if (!$info) {
    die('Không tìm thấy giao dịch.');
}

// Kiểm tra xem giao dịch có trạng thái hoàn thành không
if (!$service->isTransactionCompleted($tx_id)) {
    die('Chỉ có thể xuất hóa đơn với các giao dịch đã hoàn thành.');
}

$invoice = $service->getInvoice($tx_id);
if (!$invoice) {
    die('Không tìm thấy yêu cầu xuất hóa đơn cho giao dịch này.');
}

include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/invoice/request_export_invoice.css" />
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/invoice/completed_export_invoice.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper invoice-content-wrapper">
        <div class="invoice-request-wrapper">
            <h2>Thông tin xuất hóa đơn</h2>
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
                <h4>Thông tin xuất hóa đơn</h4>                <table class="info-table">
                    <tr><td>Tên công ty</td><td>:</td><td><?php echo htmlspecialchars($info['company_name'] ?? ''); ?></td></tr>
                    <tr><td>Mã số thuế</td><td>:</td><td><?php echo htmlspecialchars($info['tax_code'] ?? ''); ?></td></tr>
                    <tr><td>Địa chỉ công ty</td><td>:</td><td><?php echo htmlspecialchars($info['company_address'] ?? ''); ?></td></tr>
                    <tr><td>Email</td><td>:</td><td><?php echo htmlspecialchars($info['email'] ?? ''); ?></td></tr>
                </table>
            </div>
            <div class="invoice-section invoice-section-spaced">
                <h4>Trạng thái xuất hóa đơn</h4>
                <?php if ($invoice['status'] === 'approved'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle success-icon"></i>
                        Yêu cầu xuất hóa đơn đã được chấp thuận.
                    </div>
                    <?php if (!empty($invoice['invoice_file'])): ?>
                        <button type="button" class="btn btn-primary download-btn" onclick="downloadInvoiceFile('<?php echo $admin_site . '/public/uploads/invoice/' . urlencode($invoice['invoice_file']); ?>')">
                            <i class="fas fa-download"></i> Tải hóa đơn
                        </button>
                        <script>
                        function downloadInvoiceFile(url) {
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = url.split('/').pop();
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        }
                        </script>
                    <?php endif; ?>
                <?php elseif ($invoice['status'] === 'rejected'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle error-icon"></i>
                        Yêu cầu xuất hóa đơn bị từ chối.<br>
                        <strong>Lý do:</strong> <?php echo nl2br(htmlspecialchars($invoice['rejected_reason'] ?? '')); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle info-icon"></i>
                        Yêu cầu xuất hóa đơn đang chờ xử lý.
                    </div>
                <?php endif; ?>
                <button type="button" class="btn btn-cancel back-btn" onclick="window.location.href='<?php echo $base_url; ?>/public/pages/transaction.php'">Quay lại giao dịch</button>
            </div>
        </div>
    </div>
</div>
<?php include $project_root_path . '/private/includes/footer.php'; ?>
