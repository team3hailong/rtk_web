<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

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
$approved_collaborators = $dashboard->getApprovedCollaborators();
$recent_activities = $dashboard->getRecentActivities(5);
$user_display_name = $_SESSION['username'] ?? 'Người dùng';
?>
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/dashboard.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Tổng quan tài khoản</h2>
        <p class="text-gray-600 mb-4">Các số liệu và hoạt động mới nhất của bạn</p>
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
            </div>
            <div class="stat-card">
                <i class="icon fas fa-users-cog info"></i>
                <h3>Cộng tác viên</h3>
                <p class="value" id="referral-count"><?php echo htmlspecialchars($approved_collaborators); ?></p>
            </div>
        </div>

        <div id="details-popup" class="popup hidden">
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
    </main>
</div>
<?php
include $project_root_path . '/private/includes/footer.php';
?>
<!-- Page-specific JS -->
<script src="<?php echo $base_path; ?>/assets/js/pages/dashboard.js"></script>