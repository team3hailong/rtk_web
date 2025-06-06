<?php


// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Required Files ---
require_once $project_root_path . '/private/utils/functions.php';
require_once $project_root_path . '/private/utils/csrf_helper.php'; // Include CSRF Helper
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/purchase/PaymentProofService.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . $base_path . '/pages/auth/login.php?error=not_logged_in');
    exit;
}

// Khởi tạo service
$paymentProofService = new PaymentProofService();

// --- Get Registration ID ---
// Get from URL parameter first, fallback to session if needed
$registration_id = null;
if (isset($_GET['reg_id']) && is_numeric($_GET['reg_id'])) {
    $registration_id = (int)$_GET['reg_id'];
} elseif (isset($_SESSION['pending_registration_id'])) {
    $registration_id = $_SESSION['pending_registration_id'];
}

if (!$registration_id) {
    // If no registration ID is found, redirect to packages or dashboard
    header('Location: ' . $base_url . $base_path . '/pages/purchase/packages.php?error=missing_order_id');
    exit;
}

// --- Fetch Existing Payment Proof ---
$proof_result = $paymentProofService->getPaymentProofByRegistrationId($registration_id, $_SESSION['user_id']);
$existing_proof_image = $proof_result['data']['existing_proof_image'];
$existing_proof_url = $proof_result['data']['existing_proof_url'];

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS for Upload Page (can reuse some styles or add specific ones) -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/upload_proof.css">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <a href="<?php echo $base_url . $base_path; ?>/pages/transaction.php" class="back-link">&larr; Quay lại Quản Lý Giao Dịch</a>

        <h2 class="text-2xl font-semibold mb-4">Tải lên minh chứng thanh toán</h2>
        <p class="text-sm text-gray-600 mb-6">Đơn hàng: <strong>REG<?php echo htmlspecialchars($registration_id); ?></strong></p>        <!-- === Hiển thị minh chứng đã tải lên (nếu có) === -->
        <?php if ($existing_proof_url): ?>
        <div class="existing-proof-section">
            <h4>Minh chứng đã tải lên:</h4>
            <div class="proof-image-container">
                <a href="javascript:void(0)" onclick="window.open('<?php echo $base_url . $base_path; ?>/pages/view_image.php?image=<?php echo urlencode($existing_proof_url); ?>', 'ImageViewer', 'width=800,height=600')">
                    <img src="<?php echo $existing_proof_url; ?>" alt="Minh chứng thanh toán hiện tại" style="max-width: 300px; cursor: pointer;">
                </a>
                <p class="text-sm text-gray-600 mt-2">Click vào ảnh để xem chi tiết</p>
            </div>
        </div>
        <?php endif; ?>
        <!-- === Kết thúc hiển thị === -->

        <!-- === Phần Tải Lên Minh Chứng === -->
        <div class="upload-section">
            <h3><?php echo $existing_proof_image ? 'Thay thế minh chứng thanh toán' : 'Tải lên ảnh chụp màn hình giao dịch'; ?></h3>
            <p>Trong một số trường hợp, ảnh minh chứng có thể tải lên lâu hơn bình thường. Hãy chờ đợi nhé❤️</p>
            
            <!-- Form thông thường (không dùng AJAX) sẽ được sử dụng nếu JavaScript bị tắt -->
            <form action="<?php echo $base_url . $base_path; ?>/handlers/action_handler.php?module=purchase&action=upload_payment_proof" 
                  method="post" 
                  enctype="multipart/form-data" 
                  id="upload-form">                <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                <!-- CSRF Token protection -->
                <?php echo generate_csrf_input(); ?>

                <input type="file" name="payment_proof_image" id="payment_proof_image" accept="image/png, image/jpeg, image/gif" required>

                <button type="submit" class="btn btn-upload" id="upload-button"><?php echo $existing_proof_image ? 'Gửi minh chứng mới' : 'Gửi minh chứng'; ?></button>
                <div id="upload-progress" style="margin-top: 0.5rem; font-size: var(--font-size-sm); display: none;">Đang tải lên...</div>
                <!-- Container for progress bar -->
                <div id="progress-bar-container" style="width: 100%; background-color: #f0f0f0; border-radius: 4px; margin: 10px 0; display: none;">
                    <div id="progress-bar-inner" style="height: 10px; background-color: var(--primary-600); border-radius: 4px; width: 0%; transition: width 0.2s;"></div>
                </div>
                <!-- Container for upload details (speed, time) -->
                <div id="upload-details" style="font-size: var(--font-size-xs); color: var(--gray-600); margin-top: 5px; display: none;">
                    <span id="upload-speed"></span> | <span id="upload-time-remaining"></span>
                </div>
                <div id="upload-status-js" class="mt-3" style="font-size: var(--font-size-sm); font-weight: var(--font-medium);"></div>
            </form>
        </div>
         <!-- === Kết thúc Phần Tải Lên Minh Chứng === -->

    </main>
</div>

<script>
// Định nghĩa các biến cần thiết cho file JS bên ngoài
const TRANSACTION_URL = '<?php echo $base_url . $base_path; ?>/pages/transaction.php';
const SUCCESS_URL = '<?php echo $base_url . $base_path; ?>/pages/purchase/success.php?upload=success';
</script>
<!-- Nhúng file JS đã tách -->
<script src="<?php echo $base_url . $base_path; ?>/assets/js/pages/purchase/upload_proof.js"></script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>
