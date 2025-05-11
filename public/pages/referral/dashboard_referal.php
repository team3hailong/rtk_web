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

<!-- Sử dụng CSS mới để đồng bộ giao diện -->
<style>
    /* Variables CSS - Giữ nguyên và có thể bổ sung nếu cần */
    :root {
        --primary-color: #28a745; /* Green for primary actions */
        --primary-hover-color: #218838;
        --secondary-color: #6c757d; /* Gray for secondary elements */
        --light-gray-color: #f8f9fa;
        --border-color: #dee2e6;
        --text-color: #212529;
        --text-muted-color: #6c757d;
        --white-color: #fff;
        --blue-500: #2196F3;
        --blue-600: #1976D2;
        --green-500: #4CAF50;
        --green-600: #388E3C;
        --red-500: #F44336;
        --orange-500: #FF9800;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --rounded-sm: 0.25rem;
        --rounded-md: 0.375rem;
        --rounded-lg: 0.5rem;
        --font-size-xs: 0.75rem;
        --font-size-sm: 0.875rem;
        --font-size-base: 1rem;
        --font-size-lg: 1.125rem;
        --font-semibold: 600;
        --font-bold: 700;
    }

    /* General Layout - from rtk_accountmanagement.css */
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
        background-color: var(--light-gray-color); /* Consistent background */
    }

    .content-wrapper { /* Applied to referral-content-wrapper */
        flex: 1;
        padding: 1.5rem; /* Standard padding */
        background-color: var(--light-gray-color);
    }

    /* Page Title */
    h2.text-2xl.font-semibold.mb-4 { /* Already consistent */
        font-size: 1.5rem;
        font-weight: var(--font-semibold);
        color: var(--gray-800);
        margin-bottom: 1.5rem; /* Standardized margin */
    }
    
    h4 {
        font-size: 1.2rem;
        font-weight: var(--font-semibold);
        color: var(--gray-700);
        margin-bottom: 1rem;
    }

    h5 {
        font-size: 1rem;
        font-weight: var(--font-semibold);
        color: var(--gray-600);
        margin-bottom: 0.75rem;
    }

    /* Card Styling - Consistent with rtk_accountmanagement.php */
    .card {
        background-color: var(--white-color);
        border: 1px solid var(--border-color);
        border-radius: var(--rounded-lg);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background-color: var(--white-color);
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        border-top-left-radius: var(--rounded-lg);
        border-top-right-radius: var(--rounded-lg);
    }
    
    .card-header.p-2 { /* Override for tighter tab header */
        padding: 0.5rem;
    }

    .card-body {
        padding: 1.25rem;
    }

    /* Tab Styling - Clean and modern */
    .nav-tabs {
        border-bottom: 1px solid var(--border-color);
        display: flex;
        flex-wrap: wrap;
        list-style: none;
        padding-left: 0;
        margin-bottom: 0; /* Remove default margin */
    }

    .nav-tabs .nav-item {
        margin-bottom: -1px; /* Align with border */
    }

    .nav-tabs .nav-link {
        display: block;
        padding: 0.75rem 1rem;
        border: 1px solid transparent;
        border-top-left-radius: var(--rounded-md);
        border-top-right-radius: var(--rounded-md);
        color: var(--text-muted-color);
        font-weight: var(--font-semibold);
        text-decoration: none;
        font-size: var(--font-size-sm);
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }

    .nav-tabs .nav-link:hover,
    .nav-tabs .nav-link:focus {
        border-color: var(--gray-200) var(--gray-200) var(--border-color);
        color: var(--primary-color);
    }

    .nav-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: var(--white-color);
        border-color: var(--border-color) var(--border-color) var(--white-color);
        border-bottom: 2px solid var(--primary-color); /* Active indicator */
        font-weight: var(--font-bold);
    }
    
    .tab-content > .tab-pane {
        display: none;
    }
    .tab-content > .active {
        display: block;
    }

    /* Form Elements - Consistent Styling */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-control {
        display: block;
        width: 100%;
        padding: 0.6rem 0.75rem;
        font-size: var(--font-size-sm);
        font-weight: 400;
        line-height: 1.5;
        color: var(--text-color);
        background-color: var(--white-color);
        background-clip: padding-box;
        border: 1px solid var(--border-color);
        border-radius: var(--rounded-md);
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    .form-control[readonly] {
        background-color: var(--gray-100);
        opacity: 1;
    }

    label {
        font-weight: var(--font-semibold);
        margin-bottom: 0.5rem;
        display: inline-block;
        color: var(--gray-700);
        font-size: var(--font-size-sm);
    }

    /* Input Group Styling */
    .input-group {
        position: relative;
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        width: 100%;
    }

    .input-group > .form-control {
        position: relative;
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        margin-bottom: 0;
    }

    .input-group > .form-control:not(:last-child) {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-group > .form-control:not(:first-child) {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    
    .input-group-append {
        display: flex;
        margin-left: -1px;
    }

    .input-group-append .btn {
        position: relative;
        z-index: 2;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Button Styling */
    .btn {
        display: inline-block;
        font-weight: var(--font-semibold);
        color: var(--white-color);
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.6rem 1rem;
        font-size: var(--font-size-sm);
        line-height: 1.5;
        border-radius: var(--rounded-md);
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .btn-primary {
        color: var(--white-color);
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    .btn-primary:hover {
        background-color: var(--primary-hover-color);
        border-color: var(--primary-hover-color);
    }
    .btn-primary:disabled {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        opacity: 0.65;
    }

    .btn-outline-secondary {
        color: var(--secondary-color);
        border-color: var(--secondary-color);
    }
    .btn-outline-secondary:hover {
        color: var(--white-color);
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
    }
    .btn-outline-secondary i {
        margin-right: 0.35rem;
    }
    
    /* Table Styling - Consistent with rtk_accountmanagement.php */
    .table-responsive {
        overflow-x: auto;
        margin-bottom: 1rem; /* Add some space below table */
    }

    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: var(--text-color);
        border-collapse: collapse; /* Ensure borders are clean */
    }

    .table th,
    .table td {
        padding: 0.85rem 1rem; /* Adjusted padding */
        vertical-align: middle;
        border-top: 1px solid var(--border-color);
    }

    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid var(--border-color);
        background-color: var(--gray-100); /* Header background */
        color: var(--gray-700);
        font-weight: var(--font-bold);
        font-size: var(--font-size-sm);
        text-align: left; /* Ensure text alignment */
    }

    .table tbody tr:nth-of-type(odd) {
        background-color: rgba(0,0,0,0.02); /* Subtle striping */
    }
    .table tbody tr:hover {
        background-color: rgba(0,0,0,0.04);
    }

    /* Badge Styling */
    .badge {
        display: inline-block;
        padding: 0.35em 0.6em;
        font-size: 0.8em; /* Slightly larger for readability */
        font-weight: var(--font-bold);
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: var(--rounded-sm);
    }
    .badge-primary { background-color: var(--blue-500); color: white; }
    .badge-success { background-color: var(--green-500); color: white; }
    .badge-warning { background-color: var(--orange-500); color: white; }
    .badge-danger { background-color: var(--red-500); color: white; }
    .badge-info { background-color: var(--blue-500); color: white; } /* Using blue for info */
    .badge-secondary { background-color: var(--gray-500); color: white; }

    /* Alert Styling */
    .alert {
        position: relative;
        padding: 0.85rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: var(--rounded-md);
        font-size: var(--font-size-sm);
    }
    .alert-info { background-color: #e1f5fe; border-color: #b3e5fc; color: #01579b; }
    .alert-success { background-color: #e8f5e9; border-color: #c8e6c9; color: #1b5e20; }
    .alert-warning { background-color: #fff8e1; border-color: #ffecb3; color: #ff6f00; }
    .alert-danger { background-color: #ffebee; border-color: #ffcdd2; color: #b71c1c; }
    .alert strong { font-weight: var(--font-bold); }
    .alert ul { padding-left: 1.5rem; margin-bottom: 0; }
    .alert ul li { margin-bottom: 0.25rem; }

    /* Commission Summary Cards Styling */
    .commission-summary-card {
        color: var(--white-color);
        border-radius: var(--rounded-md);
        padding: 1.25rem;
        margin-bottom: 1rem; /* For spacing on mobile */
    }
    .commission-summary-card .card-title {
        font-size: var(--font-size-sm);
        font-weight: var(--font-semibold);
        margin-bottom: 0.5rem;
        opacity: 0.9;
    }
    .commission-summary-card h2 {
        font-size: 1.75rem;
        font-weight: var(--font-bold);
        margin: 0;
        color: var(--white-color); /* Ensure h2 inside is white */
    }
    .bg-card-primary { background-color: var(--blue-500); }
    .bg-card-success { background-color: var(--green-500); }
    .bg-card-info { background-color: var(--orange-500); } /* Using orange for "Đã rút" for variety */

    /* Grid system for layout (simplified) */
    .row { display: flex; flex-wrap: wrap; margin-right: -10px; margin-left: -10px; }
    .col-md-4, .col-md-6, .col-md-8, .col-md-12 {
        position: relative;
        width: 100%;
        padding-right: 10px;
        padding-left: 10px;
        margin-bottom: 1rem; /* Add default bottom margin for columns */
    }

    @media (min-width: 768px) {
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; }
        .col-md-4, .col-md-6, .col-md-8, .col-md-12 { margin-bottom: 0; } /* Reset on larger screens if row handles spacing */
    }
    
    /* Utilities */
    .mb-0 { margin-bottom: 0 !important; }
    .mb-3 { margin-bottom: 1rem !important; }
    .mb-4 { margin-bottom: 1.5rem !important; }
    .mt-4 { margin-top: 1.5rem !important; }
    .p-3 { padding: 1rem !important; }
    .text-danger { color: var(--red-500) !important; }
    .text-muted { color: var(--text-muted-color) !important; }
    .d-flex { display: flex !important; }
    .justify-content-between { justify-content: space-between !important; }
    .align-items-center { align-items: center !important; }

    /* Responsive Table (from existing, good to keep) */
    @media (max-width: 767.98px) {
        .content-wrapper { padding: 1rem; }
        h2.text-2xl.font-semibold.mb-4 { font-size: 1.25rem; margin-bottom: 1rem; }
        h4 { font-size: 1.1rem; }

        .nav-tabs .nav-link { padding: 0.6rem 0.5rem; font-size: 0.8rem;}
        .nav-tabs { justify-content: space-around; } /* Better for mobile */
        .nav-tabs .nav-item { flex-grow: 1; text-align: center; }


        .table-responsive { border: none; box-shadow: none; }
        .table, .table thead, .table tbody, .table th, .table td, .table tr { 
            display: block; 
        }
        .table thead tr { 
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        .table tr { 
            border: 1px solid var(--border-color); 
            margin-bottom: 0.75rem; 
            border-radius: var(--rounded-md);
            background-color: var(--white-color);
        }
        .table td { 
            border: none;
            border-bottom: 1px solid var(--gray-200); 
            position: relative;
            padding-left: 45% !important; 
            text-align: right;
            display: flex; /* For better alignment of content and pseudo-element */
            justify-content: space-between; /* Align label and value */
            align-items: center;
            min-height: 38px; /* Ensure consistent height */
        }
        .table td:before { 
            content: attr(data-label);
            position: absolute;
            left: 0.75rem;
            width: 40%; 
            white-space: nowrap;
            font-weight: var(--font-semibold);
            text-align: left;
            color: var(--gray-700);
        }
        .table td:last-child { border-bottom: none; }

        .input-group { flex-direction: column; }
        .input-group > .form-control { width: 100%; margin-bottom: 0.5rem; border-radius: var(--rounded-md) !important; }
        .input-group-append { margin-left: 0; width: 100%; }
        .input-group-append .btn { width: 100%; border-radius: var(--rounded-md) !important;}

        .row { margin-right: -5px; margin-left: -5px; }
        .col-md-4, .col-md-6, .col-md-8, .col-md-12 { padding-right: 5px; padding-left: 5px; margin-bottom: 1rem; }
    }

    /* Toast Message Styling (from existing, good to keep) */
    #toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
    }
    .toast-message {
        background-color: rgba(33, 150, 243, 0.9); /* var(--blue-500) with alpha */
        color: white;
        padding: 12px 20px;
        border-radius: var(--rounded-md);
        margin-top: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        min-width: 250px;
        font-size: var(--font-size-sm);
    }
</style>

<div class="dashboard-wrapper">
    <?php include PROJECT_ROOT_PATH . '/private/includes/sidebar.php'; ?>    <div class="content-wrapper referral-content-wrapper">
        <h2 class="text-2xl font-semibold mb-5">Chương trình giới thiệu</h2>
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-tabs card-header-tabs mb-0" id="referralTabs" role="tablist">
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
                    <!-- Tab 1: Referral Link -->                    <div class="tab-pane fade show active" id="referral-link" role="tabpanel">
                        <h4 class="mb-3">Liên kết giới thiệu của bạn</h4>
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
                                    <input type="text" class="form-control" id="referral-link-input" 
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
// Cải thiện chức năng copy với thông báo tốt hơn
function copyReferralCode() {
    var copyText = document.getElementById("referral-code");
    if (!copyText) return;
    
    try {
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        
        // Hiển thị toast thay vì confirm
        showToast("Đã sao chép mã giới thiệu: " + copyText.value);
    } catch (err) {
        alert("Không thể sao chép: " + err);
    }
}

function copyReferralLink() {
    var copyText = document.getElementById("referral-link-input");
    if (!copyText) return;
    
    try {
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand("copy");
        
        // Hiển thị toast thay vì confirm
        showToast("Đã sao chép liên kết giới thiệu!");
    } catch (err) {
        alert("Không thể sao chép: " + err);
    }
}

// Thêm chức năng toast message cho UX tốt hơn
function showToast(message) {
    // Kiểm tra nếu đã có toast container
    var toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Tạo toast message
    var toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.style.backgroundColor = 'rgba(33, 150, 243, 0.9)';
    toast.style.color = 'white';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '4px';
    toast.style.marginTop = '10px';
    toast.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    toast.style.minWidth = '250px';
    toast.innerText = message;
    
    toastContainer.appendChild(toast);
    
    // Auto remove sau 3 giây
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s ease';
        
        // Xóa khỏi DOM sau khi hiệu ứng fade out hoàn thành
        setTimeout(function() {
            toastContainer.removeChild(toast);
        }, 500);
    }, 3000);
}

// Script cải tiến để xử lý các tab và hiệu ứng người dùng
$(document).ready(function() {
    // Cải tiến tab implementation - đảm bảo hoạt động trong mọi trường hợp
    $('#referralTabs a').on('click', function (e) {
        e.preventDefault();
        
        // Ẩn tất cả tab panes
        $('.tab-pane').removeClass('show active');
        
        // Loại bỏ active class từ tất cả tabs
        $('#referralTabs a').removeClass('active');
        
        // Thêm active class cho tab hiện tại
        $(this).addClass('active');
        
        // Hiển thị tab pane tương ứng
        var target = $(this).attr('href');
        $(target).addClass('show active');
        
        // Lưu trạng thái tab vào localStorage
        localStorage.setItem('activeReferralTab', target);
    });
    
    // Khôi phục tab đã chọn từ localStorage
    var activeTab = localStorage.getItem('activeReferralTab');
    if (activeTab) {
        $('#referralTabs a[href="' + activeTab + '"]').click();
    }
    
    // Cải tiến auto-dismiss alerts với hiệu ứng mượt
    setTimeout(function() {
        $('.alert:not(#withdrawal-message)').fadeOut('slow');
    }, 5000);
    
    // Form validation & loading - được cải tiến với UX tốt hơn
    $('#withdrawal-form').submit(function(event) {
        event.preventDefault();
        
        var form = $(this);
        var withdrawBtn = $('#withdraw-btn');
        var btnText = $('#withdraw-btn-text');
        var btnLoading = $('#withdraw-btn-loading');
        var messageDiv = $('#withdrawal-message');
        
        var amount = parseFloat($('#amount').val());
        var bankName = $('#bank_name').val().trim();
        var accountNumber = $('#account_number').val().trim();
        var accountHolder = $('#account_holder').val().trim();
        var available = parseFloat(<?php echo (float)$availableBalance; ?>);
        var minWithdrawal = 100000;

        // Reset thông báo
        messageDiv.hide().removeClass('alert-success alert-danger');

        // Kiểm tra form
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

        // Hiển thị trạng thái loading
        btnText.hide();
        btnLoading.show();
        withdrawBtn.prop('disabled', true);

        // Gửi yêu cầu AJAX
        $.ajax({
            type: 'POST',
            url: '<?php echo BASE_URL; ?>/private/action/referral/process_withdrawal.php',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.message).removeClass('alert-danger').addClass('alert-success').show();
                    
                    // Hiển thị spinner trên toàn trang khi reload
                    var overlay = $('<div>').css({
                        'position': 'fixed',
                        'top': 0,
                        'left': 0,
                        'width': '100%',
                        'height': '100%',
                        'background-color': 'rgba(255,255,255,0.7)',
                        'z-index': 9999,
                        'display': 'flex',
                        'justify-content': 'center',
                        'align-items': 'center'
                    });
                    
                    var spinner = $('<div>').html('<i class="fas fa-spinner fa-spin fa-3x" style="color:#2196F3"></i>');
                    overlay.append(spinner);
                    $('body').append(overlay);
                    
                    // Reload sau 1.5 giây
                    setTimeout(function(){ location.reload(); }, 1500);
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
            }        });
    });
    
    // Thêm các tính năng responsive cho bảng
    function adjustTableResponsive() {
        if (window.innerWidth < 768) {
            $('.table-responsive').each(function() {
                var table = $(this).find('table');
                if (!table.hasClass('table-mobile-ready')) {
                    table.addClass('table-mobile-ready');
                    
                    // Thêm data-label attribute cho mỗi cell dựa trên header
                    // Đảm bảo bảng hiển thị tốt trên mobile
                    table.find('thead th').each(function(index) {
                        var headerText = $(this).text();
                        table.find('tbody tr').each(function() {
                            $(this).find('td:eq(' + index + ')').attr('data-label', headerText);
                        });
                    });
                }
            });
        }
    }
    
    // Gọi lần đầu và khi thay đổi kích thước màn hình
    adjustTableResponsive();
    $(window).on('resize', function() {
        adjustTableResponsive();
    });
    
    // Focus input khi click vào label để cải thiện UX
    $('label').on('click', function() {
        var forAttr = $(this).attr('for');
        if (forAttr) {
            $('#' + forAttr).focus();
        }
    });
    
    // Cải thiện UX cho form khi chuyển tab
    $('#withdrawal-tab').on('click', function() {
        setTimeout(function() {
            $('#amount').focus();
        }, 300);
    });
});
</script>

<?php
// Include footer
require_once PROJECT_ROOT_PATH . '/private/includes/footer.php';
?>