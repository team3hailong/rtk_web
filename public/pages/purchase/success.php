<?php
session_start();

// --- Project Root Path for Includes ---
$project_root_path = dirname(dirname(dirname(dirname(__FILE__))));

// --- Base URL Configuration ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/utils/functions.php';
// Include database connection if needed to fetch order details
// require_once $project_root_path . '/private/db/db_connection.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

// --- Get Registration ID ---
$registration_id = $_GET['registration_id'] ?? null;

if (!$registration_id) {
    // Redirect if no registration ID is provided
    header('Location: ' . $base_url . '/public/dashboard.php?error=missing_order_id'); // Or redirect to history
    exit;
}

// --- Optional: Fetch Order Details ---
// You might want to fetch details based on $registration_id to display more info
// Example:
/*
$conn = connect_db();
$stmt = $conn->prepare("SELECT package_name, quantity FROM registrations r JOIN packages p ON r.package_id = p.id WHERE r.id = ? AND r.user_id = ?");
$stmt->bind_param("ii", $registration_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order_details = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$order_details) {
    // Handle case where order doesn't belong to user or doesn't exist
    header('Location: ' . $base_url . '/public/dashboard.php?error=invalid_order');
    exit;
}
$package_name = $order_details['package_name'] ?? 'Gói dịch vụ';
*/

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<style>
    .success-container {
        max-width: 600px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: white;
        border-radius: var(--rounded-lg);
        border: 1px solid var(--gray-200);
        text-align: center;
        box-shadow: var(--shadow-md);
    }
    .success-icon {
        font-size: 3rem; /* Adjust size as needed */
        color: var(--success-500);
        margin-bottom: 1rem;
        /* Example using a simple checkmark character, replace with SVG or icon font if available */
        content: '✔'; /* Simple fallback */
        display: inline-block;
        width: 60px;
        height: 60px;
        line-height: 60px;
        border-radius: 50%;
        background-color: var(--success-100);
    }
    .success-container h2 {
        font-size: var(--font-size-xl);
        font-weight: var(--font-semibold);
        color: var(--gray-800);
        margin-bottom: 0.5rem;
    }
    .success-container p {
        font-size: var(--font-size-base);
        color: var(--gray-600);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }
    .success-container .btn {
        /* Use your existing button styles */
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background-color: var(--primary-600);
        color: white;
        border-radius: var(--rounded-md);
        text-decoration: none;
        font-weight: var(--font-medium);
        transition: background-color 0.2s;
    }
    .success-container .btn:hover {
        background-color: var(--primary-700);
    }
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <div class="success-container">
            <div class="success-icon">
                <!-- You can place an SVG or icon font here -->
                &#10004; <!-- HTML checkmark entity -->
            </div>
            <h2>Thanh toán thành công!</h2>

            <!-- CUSTOMIZE: Modify this message as needed -->
            <p>
                Cảm ơn bạn đã mua hàng! Đơn hàng mã <strong><?php echo htmlspecialchars($registration_id); ?></strong> của bạn đã được ghi nhận.
                <?php // if (isset($package_name)) { echo " Gói: " . htmlspecialchars($package_name) . "."; } ?>
                Tài khoản của bạn sẽ sớm được kích hoạt. Bạn có thể kiểm tra trạng thái trong Lịch sử giao dịch.
            </p>
            <!-- END CUSTOMIZE -->

            <a href="<?php echo $base_url; ?>/public/pages/history/transaction_history.php" class="btn">Xem lịch sử giao dịch</a>
            <a href="<?php echo $base_url; ?>/public/dashboard.php" class="btn" style="margin-left: 1rem; background-color: var(--gray-500);">Về trang chủ</a>

        </div>
    </main>
</div>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>
