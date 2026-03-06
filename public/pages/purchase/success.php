<?php


// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

init_session();
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

// Note: We don't need to fetch detailed data for simple success messages
// The page can work with just transaction_id or registration_id parameters
// Only fetch if explicitly needed and data is not in session
?>

<!-- CSS cho trang thành công -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/success.css">

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>    
    
    <?php
    // Xác định loại giao dịch và trạng thái
    $is_renewal = isset($_SESSION['is_renewal']) && $_SESSION['is_renewal'] === true;
    $is_trial = isset($_GET['is_trial']) && $_GET['is_trial'] == '1';
    $is_auto_approved = false;
    $transaction_type = 'purchase'; // Mặc định là mua mới
    
    if ($is_renewal) {
        $transaction_type = 'renewal';
    } elseif ($is_trial) {
        $transaction_type = 'trial';
    }
    
    // Kiểm tra xem có phải voucher auto_approve không
    if (isset($_SESSION['purchase_details']['auto_approved']) && $_SESSION['purchase_details']['auto_approved']) {
        $is_auto_approved = true;
    }
    
    // Kiểm tra từ URL parameter
    if (isset($_GET['auto_approved']) && $_GET['auto_approved'] == '1') {
        $is_auto_approved = true;
    }
    ?>
    
    <div class="content-wrapper">
        <div class="success-container">
            <div class="success-checkmark">
                <i class="fas fa-check"></i>
            </div>
            
            <?php if(!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) && isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
                <!-- Upload minh chứng thành công -->
                <h2>Upload minh chứng thành công!</h2>
                <p>Cảm ơn bạn đã tải lên minh chứng thanh toán! Chúng tôi đã ghi nhận thông tin và sẽ xác nhận giao dịch của bạn trong thời gian sớm nhất.</p>
                
            <?php elseif ($is_auto_approved): ?>
                <!-- Giao dịch với voucher auto_approve hoặc trial -->
                <h2>
                    <?php 
                    if ($transaction_type === 'trial') {
                        echo 'Kích hoạt dùng thử thành công!';
                    } elseif ($transaction_type === 'renewal') {
                        echo 'Gia hạn thành công!';
                    } else {
                        echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Đăng ký tài khoản thành công!' : 'Mua tài khoản thành công!';
                    }
                    ?>
                </h2>
                <div class="success-message-box auto-approved">
                    <i class="fas fa-bolt" style="color: #f59e0b; font-size: 1.2em;"></i>
                    <div>
                        <strong>Tài khoản đã được tạo tự động!</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95em;">
                            <?php if ($transaction_type === 'trial'): ?>
                                Tài khoản dùng thử RTK của bạn đã được tạo và kích hoạt ngay lập tức. 
                                Bạn có thể đăng nhập và sử dụng ngay bây giờ!
                            <?php elseif ($transaction_type === 'renewal'): ?>
                                Tài khoản của bạn đã được gia hạn và kích hoạt ngay lập tức. 
                                Bạn có thể sử dụng ngay bây giờ!
                            <?php else: ?>
                                Tài khoản RTK của bạn đã được tạo và kích hoạt ngay lập tức. 
                                Bạn có thể đăng nhập và sử dụng ngay bây giờ!
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Giao dịch bình thường (cần admin duyệt) -->
                <h2>
                    <?php
                    if ($transaction_type === 'renewal') {
                        echo 'Đăng ký gia hạn thành công!';
                    } else {
                        echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Đăng ký tài khoản thành công!' : 'Đăng ký mua tài khoản thành công!';
                    }
                    ?>
                </h2>
                <div class="success-message-box pending">
                    <i class="fas fa-clock" style="color: #3b82f6; font-size: 1.2em;"></i>
                    <div>
                        <strong>Đăng ký đang chờ xử lý</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95em;">
                            <?php if ($transaction_type === 'renewal'): ?>
                                Yêu cầu gia hạn của bạn đã được ghi nhận.
                                <?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)
                                    ? 'Chúng tôi sẽ xử lý và gia hạn tài khoản cho bạn trong thời gian sớm nhất.'
                                    : 'Chúng tôi sẽ xác nhận thanh toán và gia hạn tài khoản trong thời gian sớm nhất.'; ?>
                            <?php else: ?>
                                Đơn đăng ký của bạn đã được ghi nhận.
                                <?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)
                                    ? 'Chúng tôi sẽ xử lý và tạo tài khoản RTK cho bạn trong thời gian sớm nhất.'
                                    : 'Chúng tôi sẽ xác nhận thanh toán và tạo tài khoản cho bạn trong thời gian sớm nhất.'; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
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
                    <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                    <div class="detail-row">
                        <span class="detail-label">Số tiền:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['price']) ? number_format($purchase_details['price']) . ' VND' : 'N/A'; ?></span>
                    </div>
                    <?php endif; ?>
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
                <?php if ($is_auto_approved): ?>
                    <!-- Giao dịch auto_approve: Ưu tiên xem tài khoản -->
                    <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-primary">
                        <i class="fas fa-user-circle"></i> Xem tài khoản ngay
                    </a>
                    <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                    <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="btn btn-outline">
                        <i class="fas fa-history"></i> Lịch sử giao dịch
                    </a>
                    <?php endif; ?>
                <?php elseif(isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
                    <!-- Sau khi upload minh chứng -->
                    <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                    <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="btn btn-primary">
                        <i class="fas fa-history"></i> Xem trạng thái đơn hàng
                    </a>
                    <?php else: ?>
                    <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-primary">
                        <i class="fas fa-user-circle"></i> Xem tài khoản ngay
                    </a>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Giao dịch bình thường: Ưu tiên xem giao dịch -->
                    <?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
                    <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="btn btn-primary">
                        <i class="fas fa-history"></i> Xem trạng thái đơn hàng
                    </a>
                    <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-outline">
                        <i class="fas fa-user-circle"></i> Quản lý tài khoản
                    </a>
                    <?php else: ?>
                    <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-primary">
                        <i class="fas fa-user-circle"></i> Xem tài khoản ngay
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Hướng dẫn sử dụng tài khoản RTK -->
            <div class="rtk-guide-section">
                <div class="guide-header">
                    <h3>Cách sử dụng tài khoản RTK</h3>
                </div>
                <p class="guide-description">
                    Xem hướng dẫn chi tiết sau để bắt đầu sử dụng tài khoản RTK
                </p>
                <a href="<?php echo $base_url; ?>/public/pages/support/guide.php?topic=&keyword=sử+dụng+máy" class="btn btn-guide">
                    <i class="fas fa-book-open"></i> Xem hướng dẫn sử dụng
                </a>
            </div>
        </div>
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
