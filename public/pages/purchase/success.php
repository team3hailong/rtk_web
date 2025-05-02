<?php
session_start();
// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng về login
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Include Required Files ---
// Không cần require config.php một lần nữa vì đã được require ở trên
include $project_root_path . '/private/includes/header.php';
require_once $project_root_path . '/private/classes/Transaction.php';
// Include more if needed...
?>

<style>
    .success-icon {
        /* Example using a simple checkmark character, replace with SVG or icon font if available */
        content: '✔'; /* Simple fallback */
        display: inline-block;
        width: 60px;
        height: 60px;
        line-height: 60px;
        border-radius: 50%;
        background-color: var(--success-100);
    }
    .success-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 2rem;
        background-color: #fff;
        border-radius: var(--rounded-lg);
        box-shadow: var(--shadow-md);
        text-align: center;
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
    }
    /* Animation for the checkmark */
    @keyframes checkmark {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    .success-checkmark {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem auto;
        border-radius: 50%;
        background-color: var(--success-100, #d1fae5);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: checkmark 0.5s ease-in-out;
    }
    .success-checkmark i {
        font-size: 40px;
        color: var(--success-600, #059669);
    }
    
    /* Order details styling */
    .order-details {
        background-color: var(--gray-50);
        border-radius: var(--rounded-md);
        padding: 1.5rem;
        margin: 1.5rem 0;
        text-align: left;
    }
    .order-details h3 {
        font-size: var(--font-size-lg);
        font-weight: var(--font-medium);
        color: var(--gray-700);
        margin-bottom: 1rem;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--gray-200);
    }
    .detail-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .detail-label {
        color: var(--gray-600);
        font-weight: var(--font-medium);
    }
    .detail-value {
        color: var(--gray-800);
        font-weight: var(--font-semibold);
    }
    
    /* Button group styling */
    .button-group {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }
    .btn-primary {
        background-color: var(--primary-600);
    }
    .btn-secondary {
        background-color: var(--gray-600);
    }
    .btn-outline {
        background-color: transparent;
        border: 1px solid var(--primary-600);
        color: var(--primary-600);
    }
    .btn-outline:hover {
        background-color: var(--primary-50);
    }
    
    /* Responsive adjustments for mobile */
    @media (max-width: 768px) {
        .success-container {
            padding: 1.5rem;
            margin: 1rem;
        }
        
        .success-checkmark {
            width: 70px;
            height: 70px;
        }
        
        .success-checkmark i {
            font-size: 35px;
        }
        
        .success-container h2 {
            font-size: var(--font-size-lg, 1.125rem);
        }
        
        .success-container p {
            font-size: var(--font-size-sm, 0.875rem);
        }
        
        .button-group {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .success-container .btn {
            display: block;
            width: 100%;
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .success-container {
            padding: 1rem;
            margin: 0.5rem;
        }
        
        .order-details {
            padding: 1rem;
        }
        
        .detail-row {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .success-checkmark {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
        }
        
        .success-checkmark i {
            font-size: 30px;
        }
    }
</style>

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="success-container">
            <div class="success-checkmark">
                <i class="fas fa-check"></i>
            </div>
            <h2>Đăng ký thành công!</h2>
            <p>Cảm ơn bạn đã hoàn thành đăng ký! Chúng tôi đã ghi nhận thông tin của bạn và sẽ xử lý trong thời gian sớm nhất.</p>

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
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Số tiền:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['price']) ? number_format($purchase_details['price']) . ' VND' : 'N/A'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Trạng thái:</span>
                        <span class="detail-value"><?php echo isset($purchase_details['payment_status']) ? htmlspecialchars($purchase_details['payment_status']) : 'Đang xử lý'; ?></span>
                    </div>
                </div>
                <?php
                // Clear session data after displaying
                //unset($_SESSION['purchase_success']);
                //unset($_SESSION['purchase_details']);
            }
            ?>

            <div class="button-group">
                <a href="<?php echo $base_url; ?>/public/pages/transaction.php" class="btn btn-primary">
                    <i class="fas fa-history"></i> Lịch sử giao dịch
                </a>
                <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-outline">
                    <i class="fas fa-user-circle"></i> Quản lý tài khoản
                </a>
            </div>
        </div>
    </div>
</div>

<?php
include $project_root_path . '/private/includes/footer.php';
?>
