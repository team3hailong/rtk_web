<?php
// Start session and include configuration
session_start();
require_once dirname(__DIR__, 3) . '/private/config/config.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/public/pages/auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Initialize necessary classes
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Referral.php';

$db = new Database();
$referralService = new Referral($db);

// Get user's referral code
$referralCodeResult = $referralService->getUserReferralCode($user_id);
$referralCode = $referralCodeResult['success'] ? $referralCodeResult['code'] : '';

// Get referred users
$referredUsers = $referralService->getReferredUsers($user_id);

// Get commission information
$totalCommissionEarned = $referralService->getTotalCommissionEarned($user_id);
$totalCommissionPaid = $referralService->getTotalCommissionPaid($user_id);
$pendingWithdrawals = $referralService->getTotalPendingWithdrawals($user_id);
$availableBalance = $totalCommissionEarned - $totalCommissionPaid - $pendingWithdrawals;

// Get withdrawal history
$withdrawalHistory = $referralService->getWithdrawalHistory($user_id);

// Get commission transactions
$commissionTransactions = $referralService->getCommissionTransactions($user_id);

// Include header and sidebar
$page_title = "Quản lý giới thiệu";
require_once PROJECT_ROOT_PATH . '/private/includes/header.php';
?>

<!-- Sử dụng CSS tự định nghĩa thay vì Bootstrap CDN -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<style>    /* CSS dành riêng cho trang referral */
    .referral-page {
        /* Biến chỉ sử dụng trong scope này */
        --primary-color: #007bff;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --info-color: #17a2b8;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        
        /* Đảm bảo font chữ nhất quán */
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
    }
      /* Grid system - chỉ áp dụng trong trang referral */
    .referral-page .container-fluid {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }
    
    .referral-page .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
    }
    
    .referral-page .col-md-4, 
    .referral-page .col-md-6 {
        position: relative;
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
    }
    
    @media (min-width: 768px) {
        .referral-page .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
        .referral-page .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
      /* Typography - phạm vi trong referral-page */
    .referral-page h1, 
    .referral-page h2, 
    .referral-page h3, 
    .referral-page h4, 
    .referral-page h5, 
    .referral-page h6 {
        margin-top: 0;
        margin-bottom: 0.5rem;
        font-weight: 500;
        line-height: 1.2;
    }
    
    /* Forms - phạm vi trong referral-page */
    .referral-page .form-group {
        margin-bottom: 1rem;
    }
    
    .referral-page .form-control {
        display: block;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
      /* Buttons - phạm vi trong referral-page */
    .referral-page .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .referral-page .btn-primary {
        color: #fff;
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .referral-page .btn-outline-secondary {
        color: var(--secondary-color);
        border-color: var(--secondary-color);
    }
    
    .referral-page .btn:hover {
        opacity: 0.85;
    }
    
    .referral-page .btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
      /* Tables - phạm vi trong referral-page */
    .referral-page .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
    }
    
    .referral-page .table th,
    .referral-page .table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    
    .referral-page .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
    }
    
    .referral-page .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .referral-page .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
      /* Alerts - phạm vi trong referral-page */
    .referral-page .alert {
        position: relative;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }
    
    .referral-page .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
    
    .referral-page .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    
    .referral-page .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeeba;
    }
    
    .referral-page .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
      /* Badges - phạm vi trong referral-page */
    .referral-page .badge {
        display: inline-block;
        padding: 0.25em 0.4em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }
    
    .referral-page .badge-primary {
        color: #fff;
        background-color: var(--primary-color);
    }
    
    .referral-page .badge-secondary {
        color: #fff;
        background-color: var(--secondary-color);
    }
    
    .referral-page .badge-success {
        color: #fff;
        background-color: var(--success-color);
    }
    
    .referral-page .badge-warning {
        color: #212529;
        background-color: var(--warning-color);
    }
    
    .referral-page .badge-danger {
        color: #fff;
        background-color: var(--danger-color);
    }
    
    .referral-page .badge-info {
        color: #fff;
        background-color: var(--info-color);
    }
      /* Tabs - phạm vi trong referral-page */
    .referral-page .nav {
        display: flex;
        flex-wrap: wrap;
        padding-left: 0;
        margin-bottom: 0;
        list-style: none;
    }
    
    .referral-page .nav-tabs {
        border-bottom: 1px solid #dee2e6;
    }
    
    .referral-page .nav-tabs .nav-item {
        margin-bottom: -1px;
    }
    
    .referral-page .nav-tabs .nav-link {
        display: block;
        padding: 0.5rem 1rem;
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        text-decoration: none;
    }
    
    .referral-page .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    
    .referral-page .tab-content > .tab-pane {
        display: none;
    }
    
    .referral-page .tab-content > .active {
        display: block;
    }
      /* Input group - phạm vi trong referral-page */
    .referral-page .input-group {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        width: 100%;
    }
    
    .referral-page .input-group > .form-control {
        position: relative;
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        margin-bottom: 0;
    }
    
    .referral-page .input-group-append {
        display: flex;
        margin-left: -1px;
    }
      /* Utilities - phạm vi trong referral-page */
    .referral-page .d-flex {
        display: flex !important;
    }
    
    .referral-page .justify-content-between {
        justify-content: space-between !important;
    }
    
    .referral-page .mb-0 {
        margin-bottom: 0 !important;
    }
    
    .referral-page .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .referral-page .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .referral-page .mt-4 {
        margin-top: 1.5rem !important;
    }
    
    .referral-page .p-3 {
        padding: 1rem !important;
    }
    
    .referral-page .text-danger {
        color: var(--danger-color) !important;
    }
    
    .referral-page .text-muted {
        color: #6c757d !important;
    }
    
    .referral-page .text-white {
        color: #fff !important;
    }
    
    .referral-page .bg-primary {
        background-color: var(--primary-color) !important;
    }
    
    .referral-page .bg-success {
        background-color: var(--success-color) !important;
    }
    
    .referral-page .bg-info {
        background-color: var(--info-color) !important;
    }
    
    .referral-page .bg-light {
        background-color: var(--light-color) !important;
    }
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e3e6f0;
    }
    .tab-content {
        padding: 20px 0;
    }
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        border-bottom: 3px solid #007bff;
        color: #007bff;
        font-weight: 600;
    }
    .table-responsive {
        margin-top: 15px;
    }
    .alert {
        margin-bottom: 20px;
    }
    .page-title {
        margin-bottom: 25px;
        color: #333;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .input-group {
        margin-bottom: 15px;
    }    .badge {
        padding: 5px 10px;
        font-size: 0.8rem;
    }    /* Ensure Bootstrap plays nicely with our layout */
    .container-fluid {
        padding: 0;
    }
    
    /* Make sure tabs don't overflow on mobile */
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    /* Ensure proper z-index for elements that need to be clickable */
    .input-group-append button {
        position: relative;
        z-index: 1;
    }
</style>

<div class="dashboard-wrapper">
    <?php include PROJECT_ROOT_PATH . '/private/includes/sidebar.php'; ?>    <main class="content-wrapper referral-page">

        <div class="container-fluid">
            <h1 class="page-title">Quản Lý Giới Thiệu</h1>
        
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="referralTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="referral-link-tab" data-toggle="tab" href="#referral-link" role="tab">Liên kết giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="referred-users-tab" data-toggle="tab" href="#referred-users" role="tab">Người đã giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="commission-tab" data-toggle="tab" href="#commission" role="tab">Hoa hồng nhận được</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="withdrawal-tab" data-toggle="tab" href="#withdrawal" role="tab">Yêu cầu rút tiền</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="referralTabsContent">
                    <!-- Tab 1: Referral Link -->
                    <div class="tab-pane fade show active" id="referral-link" role="tabpanel">
                        <h4 class="mb-4">Liên kết giới thiệu của bạn</h4>
                        <div class="alert alert-info">
                            <strong>Chính sách hoa hồng:</strong> Bạn sẽ nhận được 5% giá trị thanh toán từ người dùng mà bạn giới thiệu.
                        </div>
                        
                        <?php if ($referralCode): ?>
                            <div class="form-group">
                                <label>Mã giới thiệu của bạn:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="referral-code" value="<?php echo $referralCode; ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyReferralCode()">
                                            <i class="fas fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Liên kết giới thiệu:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="referral-link" 
                                           value="<?php echo BASE_URL; ?>/public/pages/auth/register.php?ref=<?php echo $referralCode; ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyReferralLink()">
                                            <i class="fas fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h5>Cách giới thiệu người dùng:</h5>
                                <ol>
                                    <li>Sao chép liên kết giới thiệu hoặc mã giới thiệu của bạn</li>
                                    <li>Chia sẻ liên kết này cho bạn bè, đồng nghiệp hoặc người quen</li>
                                    <li>Khi họ đăng ký và thanh toán, bạn sẽ nhận được hoa hồng 5%</li>
                                </ol>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Không thể tạo mã giới thiệu. Vui lòng thử lại sau hoặc liên hệ với quản trị viên.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Referred Users -->
                    <div class="tab-pane fade" id="referred-users" role="tabpanel">
                        <h4 class="mb-4">Danh sách người dùng đã giới thiệu</h4>
                        
                        <?php if (!empty($referredUsers)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Tên người dùng</th>
                                            <th>Email</th>
                                            <th>Ngày đăng ký</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($referredUsers as $index => $user): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($user['referred_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa giới thiệu người dùng nào. Hãy chia sẻ liên kết giới thiệu để bắt đầu kiếm hoa hồng!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 3: Commission -->
                    <div class="tab-pane fade" id="commission" role="tabpanel">
                        <h4 class="mb-4">Hoa hồng nhận được</h4>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Tổng hoa hồng đã kiếm được</h5>
                                        <h2><?php echo number_format($totalCommissionEarned, 0, ',', '.'); ?> VNĐ</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Số dư khả dụng</h5>
                                        <h2><?php echo number_format($availableBalance, 0, ',', '.'); ?> VNĐ</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Đã rút</h5>
                                        <h2><?php echo number_format($totalCommissionPaid, 0, ',', '.'); ?> VNĐ</h2>
                                    </div>
                                </div>
                            </div>
                        </div>                          <?php if (!empty($commissionTransactions)): ?>
                            <h5>Chi tiết giao dịch người được giới thiệu</h5>
                              <div class="alert alert-info mb-3">
                                <p><strong>Thông tin trạng thái hoa hồng:</strong></p>
                                <ul class="mb-0">
                                    <li><span class="badge badge-success">Đã duyệt</span> - Hoa hồng đã được tự động duyệt và sẵn sàng để rút</li>
                                    <li><span class="badge badge-info">Đang duyệt</span> - Hệ thống đang xử lý hoa hồng cho giao dịch thành công</li>
                                    <li><span class="badge badge-warning">Đang xử lý</span> - Giao dịch đang xử lý, hoa hồng sẽ được duyệt sau khi hoàn tất</li>
                                    <li><span class="badge badge-primary">Đã thanh toán</span> - Hoa hồng đã được thanh toán vào tài khoản của bạn</li>
                                    <li><span class="badge badge-secondary">Chưa đủ điều kiện</span> - Giao dịch không thành công hoặc chưa được xác nhận</li>
                                </ul>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th>Người được giới thiệu</th>
                                            <th>Số tiền giao dịch</th>
                                            <th>Hoa hồng (5%)</th>
                                            <th>Trạng thái giao dịch</th>
                                            <th>Trạng thái hoa hồng</th>
                                            <th>Ngày</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($commissionTransactions as $index => $transaction): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($transaction['referred_username']); ?></td>
                                                <td><?php echo number_format($transaction['transaction_amount'], 0, ',', '.'); ?> VNĐ</td>
                                                <td><?php echo number_format($transaction['commission_amount'], 0, ',', '.'); ?> VNĐ</td>
                                                <td>
                                                    <?php
                                                    // Trạng thái giao dịch
                                                    if (
                                                        strtolower($transaction['transaction_status']) === 'completed' &&
                                                        isset($transaction['payment_confirmed']) && $transaction['payment_confirmed'] == 1
                                                    ) {
                                                        echo '<span class="badge badge-success">Thành công</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">Đang chờ</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Trạng thái hoa hồng
                                                    if (
                                                        strtolower($transaction['transaction_status']) === 'completed' &&
                                                        isset($transaction['payment_confirmed']) && $transaction['payment_confirmed'] == 1
                                                    ) {
                                                        echo '<span class="badge badge-success">Đã duyệt</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">Đang chờ</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Bạn chưa có giao dịch hoa hồng nào. Hãy giới thiệu người dùng mới để bắt đầu kiếm hoa hồng!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 4: Withdrawal -->
                    <div class="tab-pane fade" id="withdrawal" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">                                <h4 class="mb-4">Yêu cầu rút tiền</h4>
                                
                                <div class="mb-4 card bg-light p-3">
                                    <div class="d-flex justify-content-between">
                                        <h5>Số dư khả dụng:</h5>
                                        <h5><?php echo number_format($availableBalance, 0, ',', '.'); ?> VNĐ</h5>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mb-4">
                                    <p><strong>Hệ thống hoa hồng tự động:</strong></p>
                                    <ul class="mb-0">
                                        <li>Khi người được bạn giới thiệu thanh toán thành công, hệ thống tự động duyệt hoa hồng 5%</li>
                                        <li>Hoa hồng được duyệt sẽ được cộng vào số dư khả dụng của bạn</li>
                                        <li>Bạn có thể yêu cầu rút tiền khi số dư từ 100,000 VNĐ trở lên</li>
                                        <li>Yêu cầu rút tiền sẽ được xử lý trong vòng 1-3 ngày làm việc</li>
                                    </ul>
                                </div>
                                
                                <div class="alert" id="withdrawal-message" style="display:none;"></div>
                                
                                <form id="withdrawal-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <div class="form-group">
                                        <label for="amount">Số tiền muốn rút (VNĐ) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               min="100000" max="<?php echo $availableBalance; ?>" step="10000" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                        <?php if ($availableBalance < 100000): ?>
                                            <small class="form-text text-muted">Số dư tối thiểu để rút tiền là 100.000 VNĐ</small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bank_name">Ngân hàng <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="account_number">Số tài khoản <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="account_number" name="account_number" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="account_holder">Tên chủ tài khoản <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="account_holder" name="account_holder" required
                                               <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                    </div>
                                    
                                    <button type="submit" name="submit_withdrawal" class="btn btn-primary" id="withdraw-btn"
                                            <?php echo $availableBalance < 100000 ? 'disabled' : ''; ?>>
                                        <span id="withdraw-btn-text">Gửi yêu cầu rút tiền</span>
                                        <span id="withdraw-btn-loading" style="display:none"><i class="fas fa-spinner fa-spin"></i> Đang gửi...</span>
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h4 class="mb-4">Lịch sử yêu cầu rút tiền</h4>
                                
                                <?php if (!empty($withdrawalHistory)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Ngày yêu cầu</th>
                                                    <th>Số tiền</th>
                                                    <th>Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($withdrawalHistory as $withdrawal): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($withdrawal['created_at'])); ?></td>
                                                        <td><?php echo number_format($withdrawal['amount'], 0, ',', '.'); ?> VNĐ</td>
                                                        <td>
                                                            <?php 
                                                            $status_class = '';
                                                            $status_text = '';
                                                            switch ($withdrawal['status']) {
                                                                case 'pending':
                                                                    $status_class = 'badge-warning';
                                                                    $status_text = 'Đang xử lý';
                                                                    break;
                                                                case 'completed':
                                                                    $status_class = 'badge-success';
                                                                    $status_text = 'Hoàn thành';
                                                                    break;
                                                                case 'rejected':
                                                                    $status_class = 'badge-danger';
                                                                    $status_text = 'Từ chối';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        Bạn chưa có yêu cầu rút tiền nào.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Đã thay thế Popper.js và Bootstrap JS bằng JavaScript tự viết -->

<script>
function copyReferralCode() {
    // Giới hạn phạm vi trong trang referral
    var referralPage = document.querySelector('.referral-page');
    if (!referralPage) return;
    
    var copyText = referralPage.querySelector("#referral-code");
    if (copyText) {
        copyText.select();
        document.execCommand("copy");
        if (confirm("Đã sao chép mã giới thiệu: " + copyText.value + "\nBạn có muốn chia sẻ ngay không?")) {
            // Có thể mở popup chia sẻ nếu muốn
        }
    }
}

function copyReferralLink() {
    // Giới hạn phạm vi trong trang referral
    var referralPage = document.querySelector('.referral-page');
    if (!referralPage) return;
    
    var copyText = referralPage.querySelector("#referral-link");
    if (copyText) {
        copyText.select();
        document.execCommand("copy");
        if (confirm("Đã sao chép liên kết giới thiệu!\nBạn có muốn chia sẻ ngay không?")) {
            // Có thể mở popup chia sẻ nếu muốn
        }
    }
}

// Script để xử lý các tab và hiệu ứng mà không cần Bootstrap JS
$(document).ready(function() {    // Custom tab implementation - chỉ trong phạm vi referral-page
    $('.referral-page #referralTabs a').on('click', function (e) {
        e.preventDefault();
        
        // Hide all tab panes trong phạm vi referral-page
        $('.referral-page .tab-pane').removeClass('show active');
        
        // Remove active class from all tabs trong phạm vi referral-page
        $('.referral-page #referralTabs a').removeClass('active');
        
        // Add active class to current tab
        $(this).addClass('active');
        
        // Show the corresponding tab pane
        $($(this).attr('href')).addClass('show active');
    });
    
    // Auto-dismiss alerts after 5 seconds - chỉ trong phạm vi referral-page
    setTimeout(function() {
        $('.referral-page .alert').fadeOut('slow');
    }, 5000);
      // Form validation & loading - chỉ trong phạm vi referral-page
    $('.referral-page #withdrawal-form').submit(function(event) {
        event.preventDefault();
        
        var form = $(this);
        var withdrawBtn = $('.referral-page #withdraw-btn');
        var btnText = $('.referral-page #withdraw-btn-text');
        var btnLoading = $('.referral-page #withdraw-btn-loading');
        var messageDiv = $('.referral-page #withdrawal-message');        var amount = parseFloat($('.referral-page #amount').val());
        var bankName = $('.referral-page #bank_name').val().trim();
        var accountNumber = $('.referral-page #account_number').val().trim();
        var accountHolder = $('.referral-page #account_holder').val().trim();
        var available = parseFloat(<?php echo (float)$availableBalance; ?>);
        var minWithdrawal = 100000;

        messageDiv.hide().removeClass('alert-success alert-danger');

        if (!amount || !bankName || !accountNumber || !accountHolder) {
            messageDiv.text('Vui lòng điền đầy đủ thông tin yêu cầu rút tiền.').addClass('alert-danger').show();
            return false;
        }
        if (isNaN(amount) || amount <= 0) {
            messageDiv.text('Số tiền không hợp lệ.').addClass('alert-danger').show();
            return false;
        }
        if (amount < minWithdrawal) {
            messageDiv.text('Số tiền rút tối thiểu là ' + minWithdrawal.toLocaleString('vi-VN') + ' VNĐ.').addClass('alert-danger').show();
            return false;
        }
        if (amount > available) {
            messageDiv.text('Số dư khả dụng không đủ!').addClass('alert-danger').show();
            return false;
        }

        btnText.hide();
        btnLoading.show();
        withdrawBtn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '<?php echo BASE_URL; ?>/private/action/referral/process_withdrawal.php',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.message).removeClass('alert-danger').addClass('alert-success').show();
                    setTimeout(function(){ location.reload(); }, 2000);
                } else {
                    messageDiv.text(response.message || 'Đã xảy ra lỗi không xác định.').removeClass('alert-success').addClass('alert-danger').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
                messageDiv.text('Lỗi khi gửi yêu cầu. Vui lòng thử lại.').removeClass('alert-success').addClass('alert-danger').show();
            },
            complete: function() {
                btnText.show();
                btnLoading.hide();
                withdrawBtn.prop('disabled', false);
            }
        });
    });
});
</script>

<?php
// Include footer
require_once PROJECT_ROOT_PATH . '/private/includes/footer.php';
?>