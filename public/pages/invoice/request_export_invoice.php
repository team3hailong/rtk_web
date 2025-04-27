<?php
session_start();
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = dirname(dirname($script_dir));
$base_url = rtrim($protocol . $domain . ($base_project_dir === '/' || $base_project_dir === '\\' ? '' : $base_project_dir), '/');
$project_root_path = dirname(dirname(dirname(__DIR__)));

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/pages/auth/login.php');
    exit;
}

require_once $project_root_path . '/private/classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tx_id = isset($_POST['tx_id']) ? intval($_POST['tx_id']) : 0;
    if ($tx_id <= 0) {
        http_response_code(400);
        exit('Thiếu hoặc sai tham số.');
    }
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('SELECT id FROM invoice WHERE transaction_history_id = ?');
    $stmt->execute([$tx_id]);
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        $stmt2 = $conn->prepare('INSERT INTO invoice (transaction_history_id, status, created_at) VALUES (?, "pending", NOW())');
        $stmt2->execute([$tx_id]);
    }
    header('Location: ' . $base_url . '/pages/transaction.php?invoice=success');
    exit;
}

$tx_id = isset($_GET['tx_id']) ? intval($_GET['tx_id']) : 0;
if ($tx_id <= 0) {
    die('Tham số không hợp lệ.');
}

$db = new Database();
$conn = $db->getConnection();

// Lấy thông tin giao dịch, gói, user, registration
$stmt = $conn->prepare('
    SELECT th.id as transaction_id, th.created_at, 
           p.name as package_name, r.num_account, r.total_price, 
           u.company_name, u.tax_code, u.email
    FROM transaction_history th
    LEFT JOIN registration r ON th.registration_id = r.id
    LEFT JOIN package p ON r.package_id = p.id
    LEFT JOIN user u ON th.user_id = u.id
    WHERE th.id = ?
');
$stmt->execute([$tx_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$info) {
    die('Không tìm thấy giao dịch.');
}

include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/pages/invoice/request_export_invoice.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper" style="padding-top: 1rem;">
        <div class="invoice-request-wrapper">
            <h2>Thông tin xuất hóa đơn</h2>
            <div class="invoice-section">
                <h4>Thông tin giao dịch</h4>
                <table class="info-table">
                    <tr><td>ID giao dịch</td><td>:</td><td>GD<?php echo str_pad($info['transaction_id'], 5, '0', STR_PAD_LEFT); ?></td></tr>
                    <tr><td>Thời gian</td><td>:</td><td><?php echo htmlspecialchars($info['created_at']); ?></td></tr>
                    <tr><td>Tên gói</td><td>:</td><td><?php echo htmlspecialchars($info['package_name']); ?></td></tr>
                    <tr><td>Số lượng</td><td>:</td><td><?php echo htmlspecialchars($info['num_account']); ?></td></tr>
                    <tr><td>Giá</td><td>:</td><td><?php echo number_format($info['total_price'], 0, ',', '.'); ?> đ</td></tr>
                </table>
            </div>
            <div class="invoice-section">
                <h4>Thông tin xuất hóa đơn</h4>
                <table class="info-table">
                    <tr><td>Tên công ty</td><td>:</td><td><?php echo htmlspecialchars($info['company_name']); ?></td></tr>
                    <tr><td>Mã số thuế</td><td>:</td><td><?php echo htmlspecialchars($info['tax_code']); ?></td></tr>
                    <tr><td>Email</td><td>:</td><td><?php echo htmlspecialchars($info['email']); ?></td></tr>
                </table>
            </div>
            <form method="post" action="" style="margin-top: 18px; text-align: center;">
                <input type="hidden" name="tx_id" value="<?php echo $info['transaction_id']; ?>">
                <button type="submit" class="btn btn-primary">Yêu cầu xuất hóa đơn</button>
                <a href="<?php echo $base_url; ?>/pages/transaction.php" class="btn btn-cancel">Hủy</a>
            </form>
        </div>
    </div>
</div>
<?php include $project_root_path . '/private/includes/footer.php'; ?>