<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về login
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

// --- Include Required Files ---
include $project_root_path . '/private/includes/header.php';

// If no success data in session and registration_id provided, fetch it
if ((!isset($_SESSION['purchase_success']) || !isset($_SESSION['purchase_details'])) && isset($_GET['registration_id'])) {
    $registration_id = intval($_GET['registration_id']);
    if ($registration_id > 0) {
        // Redirect to handler to fetch data
        header('Location: ' . $base_url . '/public/handlers/action_handler.php?module=purchase&action=success&sub_action=get_details&registration_id=' . $registration_id);
        exit;
    }
}
?>

<!-- CSS cho trang thành công -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/success.css">

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>    <div class="content-wrapper">
        <div class="success-container">
            <div class="success-checkmark">
                <i class="fas fa-check"></i>
            </div>
            <?php if(isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
                <h2>Upload minh chứng thành công!</h2>
                <p>Cảm ơn bạn đã tải lên minh chứng thanh toán! Chúng tôi đã ghi nhận thông tin và sẽ xác nhận giao dịch của bạn trong thời gian sớm nhất.</p>
            <?php else: ?>
                <h2>Đăng ký thành công!</h2>
                <p>Cảm ơn bạn đã hoàn thành đăng ký! Chúng tôi đã ghi nhận thông tin của bạn và sẽ xử lý trong thời gian sớm nhất.</p>
            <?php endif; ?>

            <?php 
            // Nếu có thông tin đơn hàng từ session, hiển thị chi tiết
            if (isset($_SESSION['purchase_success']) && isset($_SESSION['purchase_details'])) {
                $purchase_details = $_SESSION['purchase_details'];
                ?>
                <div class="order-details">
                    <h3>Thông tin đơn hàng</h3>
                    <div class="detail-row">
                        <span class="detail-label">Mã đăng ký:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['registration_id']) ? 'REG' . str_pad($purchase_details['registration_id'], 5, '0', STR_PAD_LEFT) : 'N/A'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Gói đăng ký:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['package_name']) ? htmlspecialchars($purchase_details['package_name']) : 'N/A'; ?></span>
                    </div>                    <div class="detail-row">
                        <span class="detail-label">Số lượng:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['quantity']) ? htmlspecialchars($purchase_details['quantity']) . ' tài khoản' : 'N/A'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Số tiền:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['price']) ? number_format($purchase_details['price']) . ' VND' : 'N/A'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Trạng thái:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['payment_status']) ? htmlspecialchars($purchase_details['payment_status']) : 'Đang xử lý'; ?></span>
                    </div>
                    <?php if (isset($purchase_details['created_at'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Ngày đăng ký:</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($purchase_details['created_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                // Don't clear session data after displaying
                // Users may need to refresh the page
            }
            ?>            <div class="button-group">
                <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="btn btn-primary">
                    <i class="fas fa-history"></i> Quản Lý Giao Dịch
                </a>
                <?php if(!isset($_GET['upload'])): ?>
                <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-outline">
                    <i class="fas fa-user-circle"></i> Quản lý tài khoản
                </a>
                <?php endif; ?>
            </div>        </div>
    </div>
</div>

<!-- Optional: Add JavaScript if needed -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Code for any success page specific functionality
    console.log('Success page loaded');
});
</script>

<?php
include $project_root_path . '/private/includes/footer.php';
?>
