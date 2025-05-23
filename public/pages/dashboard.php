<?php
// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// Sử dụng middleware session thay vì session_start thông thường
init_session();

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Khởi tạo Database và PDO giống map_display ---
require_once $project_root_path . '/private/classes/Database.php';
$db = new Database();
$pdo = $db->getConnection();

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';

// --- Dashboard Logic ---
$user_id = $_SESSION['user_id'];
require_once $project_root_path . '/private/classes/Dashboard.php';
$dashboard = new Dashboard($pdo, $user_id);
$survey_account_count = $dashboard->getSurveyAccountCount();
$pending_transactions = $dashboard->getPendingTransactions();
$referred_user_count = $dashboard->getReferredUserCount();
$recent_activities = $dashboard->getRecentActivities(5);
$recent_transactions = $dashboard->getRecentTransactions(5);
$unread_notifications_count = $dashboard->getUnreadNotificationsCount();
$user_display_name = $_SESSION['username'] ?? 'Người dùng';
?>
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/dashboard.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Tổng quan tài khoản</h2>
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="icon fas fa-database success"></i>
                <h3>Số lượng tài khoản</h3>
                <p class="value" id="survey-account-count"><?php echo htmlspecialchars($survey_account_count); ?></p>
            </div>
            <div class="stat-card">
                <i class="icon fas fa-sync warning"></i>
                <h3>Giao dịch đang xử lý</h3>
                <p class="value" id="pending-transactions"><?php echo htmlspecialchars($pending_transactions); ?></p>
            </div>            <div class="stat-card">
                <i class="icon fas fa-users-cog info"></i>
                <h3>Số người đã giới thiệu</h3>
                <p class="value" id="referral-count"><?php echo htmlspecialchars($referred_user_count); ?></p>
            </div>
        </div>        <div id="details-popup" class="popup hidden">
            <div class="popup-content">
                <h3>Chi tiết</h3>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>Tên trường</th>
                            <th>Giá trị</th>
                        </tr>
                    </thead>
                    <tbody id="details-table-body">
                        <!-- Nội dung sẽ được thêm bằng JS -->
                    </tbody>
                </table>
                <button id="close-popup" class="btn-close">Đóng</button>
            </div>
        </div>
        
        <!-- Recent Activities and Transactions Container -->
        <div class="dashboard-container">
            <!-- Recent Activities -->
            <div class="dashboard-box activities-box">
                <h3 class="box-title">Hoạt động gần đây</h3>
                <div class="activity-list">                    <?php if (empty($recent_activities)): ?>
                        <p class="empty-message">Không có hoạt động nào gần đây.</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item <?php echo (!$activity['has_read']) ? 'unread' : ''; ?>">
                                <div class="activity-content">
                                    <p><?php echo $activity['description']; ?></p>
                                    <small class="activity-time"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="dashboard-box transactions-box">
                <h3 class="box-title">Giao dịch gần đây</h3>
                <div class="transaction-list">
                    <?php if (empty($recent_transactions)): ?>
                        <p class="empty-message">Không có giao dịch nào gần đây.</p>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <div class="transaction-type">
                                        <?php 
                                            $type_label = '';
                                            $type_class = '';
                                            
                                            switch($transaction['transaction_type']) {
                                                case 'purchase':
                                                    $type_label = 'Mua gói';
                                                    $type_class = 'purchase';
                                                    break;
                                                case 'renewal':
                                                    $type_label = 'Gia hạn';
                                                    $type_class = 'renewal';
                                                    break;
                                                case 'withdrawal':
                                                    $type_label = 'Rút tiền';
                                                    $type_class = 'withdrawal';
                                                    break;
                                                default:
                                                    $type_label = ucfirst($transaction['transaction_type']);
                                                    $type_class = 'other';
                                            }
                                        ?>
                                        <span class="badge <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                                        <span class="status-badge <?php echo strtolower($transaction['status']); ?>">
                                            <?php
                                                $status_label = '';
                                                switch(strtolower($transaction['status'])) {
                                                    case 'completed':
                                                        $status_label = 'Hoàn thành';
                                                        break;
                                                    case 'pending':
                                                        $status_label = 'Đang xử lý';
                                                        break;
                                                    case 'failed':
                                                        $status_label = 'Thất bại';
                                                        break;
                                                    default:
                                                        $status_label = ucfirst($transaction['status']);
                                                }
                                                echo $status_label;
                                            ?>
                                        </span>
                                    </div>
                                    <div class="transaction-amount">
                                        <?php 
                                            $amount_prefix = in_array($transaction['transaction_type'], ['withdrawal']) ? '-' : '';
                                            echo $amount_prefix . number_format($transaction['amount'], 0, ',', '.') . '₫'; 
                                        ?>
                                    </div>
                                </div>
                                <div class="transaction-meta">
                                    <div class="transaction-date"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></div>
                                    <div class="transaction-method"><?php echo $transaction['payment_method'] ?? ''; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php
include $project_root_path . '/private/includes/footer.php';
?>
<!-- Page-specific JS -->
<script>
// Pass PHP variables to JavaScript
window.dashboardData = {
    unreadNotificationsCount: <?php echo $unread_notifications_count; ?>,
    recentActivities: <?php echo json_encode($recent_activities); ?>
};
</script>
<script src="<?php echo $base_path; ?>/assets/js/pages/dashboard.js"></script>