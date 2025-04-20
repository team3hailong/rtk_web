<?php
session_start();

// Base URL configuration
$base_path = '/'; // Use this for links

// Define the path to the private includes directory relative to this file
$private_includes_path = __DIR__ . '/../../private/includes/';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page relative to the base URL
    header('Location: /public/pages/auth/login.php'); // Corrected path from root
    exit;
}

// Include header from the private directory (assume it provides $pdo)
include $private_includes_path . 'header.php';

// --- START: Fetch Dashboard Data ---
$user_id = $_SESSION['user_id'];
$active_registrations = 0;
$pending_transactions = 0;
$approved_collaborators = 0; // Assuming this is a system-wide stat or needs adjustment for user-specific view
$recent_activities = [];
$user_display_name = $_SESSION['username'] ?? 'Người dùng'; // Get username from session

// Ensure $pdo is available (from header.php or other include)
if (isset($pdo)) {
    try {
        // Active Registrations for the user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM registration WHERE user_id = :user_id AND status = 'active' AND deleted_at IS NULL");
        $stmt->execute(['user_id' => $user_id]);
        $active_registrations = $stmt->fetchColumn();

        // Pending Transactions for the user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaction_history WHERE user_id = :user_id AND status = 'pending'");
        $stmt->execute(['user_id' => $user_id]);
        $pending_transactions = $stmt->fetchColumn();

        // Approved Collaborators (System-wide) - Consider if this should be user-specific
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM collaborator WHERE status = 'approved'");
        $stmt->execute();
        $approved_collaborators = $stmt->fetchColumn();

        // Recent Activity Logs for the user
        $stmt = $pdo->prepare("SELECT action, entity_type, entity_id, created_at FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
        $stmt->execute(['user_id' => $user_id]);
        $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Log error or handle gracefully
        error_log("Dashboard data fetch error: " . $e->getMessage());
        // Optionally set flags or messages to display errors in the UI
    }
} else {
    // Handle case where $pdo is not available
    error_log("Database connection (\$pdo) not available in dashboard.php");
    // Display an error message or default values
}
// --- END: Fetch Dashboard Data ---

?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $private_includes_path . 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <!-- Header Section within Content -->
        <div class="content-header">
             <h2 class="text-2xl font-semibold">Dashboard</h2>
             <div class="user-info">
                 <span>Chào mừng, <span class="highlight"><?php echo htmlspecialchars($user_display_name); ?></span>!</span>
                 <!-- Link to Profile Settings -->
                 <a href="<?php echo $base_path; ?>/pages/setting/profile.php" style="margin-left: 15px; color: var(--primary-600); text-decoration: none;">Hồ sơ</a>
                 <!-- Logout Link -->
                 <a href="<?php echo $base_path; ?>/pages/auth/logout.php" style="margin-left: 15px; color: var(--gray-600); text-decoration: none;">Đăng xuất</a>
             </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Active Registrations Card -->
            <div class="stat-card">
                <i class="icon fas fa-check-circle success"></i>
                <h3>Đăng ký hoạt động</h3>
                <p class="value" id="active-accounts"><?php echo htmlspecialchars($active_registrations); ?></p>
            </div>

            <!-- Pending Transactions Card -->
            <div class="stat-card">
                <i class="icon fas fa-sync warning"></i>
                <h3>Giao dịch đang xử lý</h3>
                <p class="value" id="pending-transactions"><?php echo htmlspecialchars($pending_transactions); ?></p>
            </div>

            <!-- Approved Collaborators Card -->
            <div class="stat-card">
                <i class="icon fas fa-users-cog info"></i> <!-- Changed icon and title -->
                <h3>Cộng tác viên</h3> <!-- Assuming this is a relevant stat for the user -->
                <p class="value" id="referral-count"><?php echo htmlspecialchars($approved_collaborators); ?></p>
            </div>
        </div>

        <!-- Recent Activity -->
        <section class="recent-activity">
            <h3>Hoạt động gần đây</h3>
            <div class="activity-list" id="activity-list">
                <?php if (isset($pdo) && !empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <p>
                                <?php
                                // Build a descriptive message
                                $description = 'Thực hiện: ' . htmlspecialchars(ucfirst($activity['action'])); // e.g., Login, Update
                                if (!empty($activity['entity_type'])) {
                                    // Translate common entity types if needed
                                    $entity_type_display = $activity['entity_type'];
                                    if ($entity_type_display === 'user' && $activity['action'] === 'login') {
                                        $entity_type_display = 'đăng nhập';
                                    } elseif ($entity_type_display === 'user' && $activity['action'] === 'logout') {
                                         $entity_type_display = 'đăng xuất';
                                    } elseif ($entity_type_display === 'profile') {
                                         $entity_type_display = 'hồ sơ';
                                    }
                                    $description .= ' ' . htmlspecialchars($entity_type_display);
                                }
                                // Avoid showing user ID for login/logout actions
                                if (!empty($activity['entity_id']) && !($activity['entity_type'] === 'user' && ($activity['action'] === 'login' || $activity['action'] === 'logout'))) {
                                    $description .= ' #' . htmlspecialchars($activity['entity_id']);
                                }
                                echo $description;
                                ?>
                            </p>
                            <small><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($activity['created_at']))); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!isset($pdo)): ?>
                     <p>Lỗi kết nối cơ sở dữ liệu.</p>
                <?php else: ?>
                    <p>Không có hoạt động nào gần đây.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php include $private_includes_path . 'footer.php'; ?>