<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Required Files ---
require_once $project_root_path . '/private/utils/functions.php'; // For CRC function if moved there
require_once $project_root_path . '/private/utils/csrf_helper.php'; // Include CSRF Helper
require_once $project_root_path . '/private/classes/purchase/PaymentService.php';

// --- VAT Rate ---
$vat_value = getenv('VAT_VALUE') !== false ? (float)getenv('VAT_VALUE') : 8; // Lấy từ .env hoặc mặc định 8%

// --- Authentication & Pending Order Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in'); // Adjusted path
    exit;
}
if (!isset($_SESSION['pending_registration_id']) || !isset($_SESSION['pending_total_price'])) {
    // If no pending registration found in session, redirect to packages
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=no_pending_order'); // Adjusted path
    exit;
}

$user_id = $_SESSION['user_id'];
$registration_id = $_SESSION['pending_registration_id'];
$session_total_price = $_SESSION['pending_total_price']; // Get total price from session
$is_trial = $_SESSION['pending_is_trial'] ?? false; // Check if it's a trial from session
$is_renewal = $_SESSION['is_renewal'] ?? false; // Check if it's a renewal process

// Create order/renewal session object required by voucher system
if ($is_renewal) {
    if (!isset($_SESSION['renewal'])) {
        $_SESSION['renewal'] = [
            'registration_ids' => isset($_SESSION['renewal_account_ids']) ? $_SESSION['renewal_account_ids'] : [$registration_id],
            'amount' => $session_total_price
        ];
    }
} else {
    if (!isset($_SESSION['order'])) {
        $_SESSION['order'] = [
            'registration_id' => $registration_id,
            'total_price' => $session_total_price
        ];
    }
}

// Reset biến renewal nếu đây là giao dịch mua mới
if (!isset($_SESSION['is_renewal'])) {
    $is_renewal = false;
    // Đảm bảo các biến liên quan đến renewal cũng được reset
    unset($_SESSION['renewal_account_ids']);
    unset($_SESSION['pending_renewal_details']);
}

// Lấy mảng các registration IDs cho trường hợp gia hạn nhiều tài khoản
$registration_ids = $is_renewal ? ($_SESSION['renewal_account_ids'] ?? [$registration_id]) : [$registration_id];
$renewal_details = $is_renewal ? ($_SESSION['pending_renewal_details'] ?? null) : null;

// --- Fetch Payment Details using PaymentService ---
$paymentService = new PaymentService();
$payment_details_result = $paymentService->getPaymentPageDetails($registration_id, $user_id, $session_total_price);

if (!$payment_details_result['success']) {
    // Handle errors reported by the helper function
    $error_code = $payment_details_result['error'];
    if ($error_code === 'invalid_order_state') {
        unset($_SESSION['pending_registration_id'], $_SESSION['pending_total_price']);
    }
    // Redirect back to packages page with the specific error
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=' . $error_code);
    exit;
}

// Extract data on success
$payment_data = $payment_details_result['data'];
$package_name = $payment_data['package_name'];
$quantity = $payment_data['quantity'];
$province = $payment_data['province'];
$verified_total_price = $payment_data['verified_total_price'];

// --- Tạo nội dung chuyển khoản (chỉ cần nếu không phải trial) ---
$order_description = "REG{$registration_id} MUA GOI"; // Keep it short

// --- Generate VietQR Payload (chỉ cần nếu không phải trial) ---
$final_qr_payload = null;
$vietqr_image_url = null;
if (!$is_trial) {
    $base_price = $verified_total_price;
    $session_key = $is_renewal ? 'renewal' : 'order';
    if (isset($_SESSION[$session_key]['voucher_id']) && isset($_SESSION[$session_key]['voucher_discount'])) {
        if ($is_renewal && isset($_SESSION[$session_key]['amount'])) {
            $base_price = $_SESSION[$session_key]['amount'];
        } elseif (!$is_renewal && isset($_SESSION[$session_key]['total_price'])) {
            $base_price = $_SESSION[$session_key]['total_price'];
        }
    }    $vat_amount = $base_price * ($vat_value / 100);
    $final_price = $base_price + $vat_amount;
    $qr = $paymentService->generateVietQR($final_price, $order_description); // <-- Số tiền QR là tổng thanh toán
    $final_qr_payload = $qr['payload'];
    $vietqr_image_url = $qr['image_url'];
      // Cập nhật giá trị tổng thanh toán vào transaction_history (bao gồm VAT)
    if ($is_renewal) {
        // For renewal, update transaction_history for primary registration_id
        // System should have already created appropriate transaction records for each registration
        $update_result = $paymentService->updateTransactionHistoryAmount($registration_id, $user_id, $final_price);
        if ($update_result) {
            error_log("Payment Page: Updated renewal transaction_history amount to {$final_price} for registration ID {$registration_id}");
        }
    } else {
        // For single purchase
        $update_result = $paymentService->updateTransactionHistoryAmount($registration_id, $user_id, $final_price);
        if ($update_result) {
            error_log("Payment Page: Updated purchase transaction_history amount to {$final_price} for registration ID {$registration_id}");
        }
    }
}

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
// Use correct path relative to project root
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS cho Trang Thanh Toán -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/payment.css">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; // Adjusted path ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-6">
            <?php echo $is_trial ? 'Xác nhận kích hoạt dùng thử' : 'Thanh toán đơn hàng'; ?>
        </h2>

        <div class="payment-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">

            <!-- Cột Tóm tắt đơn hàng (Luôn hiển thị) -->
            <section class="payment-summary">
                <h3>Thông tin đăng ký</h3>
                <?php if ($is_renewal): ?>
                <!-- Hiển thị thông tin gia hạn nhiều tài khoản -->
                <div class="summary-item" style="font-size: var(--font-size-sm);">
                    <span>Loại giao dịch:</span>
                    <strong>Gia hạn tài khoản</strong>
                </div>
                <div class="summary-item">
                    <span>Số lượng tài khoản:</span>
                    <strong><?php echo $renewal_details ? $renewal_details['total_accounts'] : count($registration_ids); ?> tài khoản</strong>
                </div>
                  <?php if (count($registration_ids) > 0 && !$is_trial): ?>
                <!-- Phần áp dụng voucher cho gia hạn -->
                <div class="voucher-section">
                    <h4>Mã giảm giá</h4>
                    <div class="voucher-form">
                        <input type="text" id="voucher-code" class="voucher-input" placeholder="Nhập mã giảm giá">
                        <button type="button" id="apply-voucher" class="voucher-btn">Áp dụng</button>
                    </div>
                    <div id="voucher-status" class="voucher-status"></div>
                    <div id="voucher-info" class="voucher-info" style="display: <?php echo isset($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_id']) ? 'block' : 'none'; ?>">
                        <div>Mã giảm giá: <strong id="applied-voucher-code"><?php echo isset($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_code']) ? htmlspecialchars($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_code']) : ''; ?></strong> 
                            <button type="button" id="remove-voucher" class="voucher-remove">Xóa</button>
                        </div>
                        <div id="discount-info">
                            <?php 
                            if (isset($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_discount'])) {
                                echo 'Giảm giá: ' . number_format($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_discount'], 0, ',', '.') . ' đ';
                            }
                            if (isset($_SESSION[$is_renewal ? 'renewal' : 'order']['additional_months']) && $_SESSION[$is_renewal ? 'renewal' : 'order']['additional_months'] > 0) {
                                echo 'Tăng thêm ' . $_SESSION[$is_renewal ? 'renewal' : 'order']['additional_months'] . ' tháng sử dụng';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                  <div class="summary-item">
                    <span>Giá trị đơn hàng:</span>
                    <strong><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>
                </div>
                <div class="summary-item">
                    <span>Thuế VAT (<?php echo $vat_value; ?>%):</span>
                    <strong><?php echo number_format($verified_total_price * ($vat_value / 100), 0, ',', '.'); ?> đ</strong>
                </div>
                <div class="summary-item summary-total" style="margin-top: 1.5rem;">
                    <span>Tổng thanh toán:</span>
                    <strong><?php echo number_format($verified_total_price + ($verified_total_price * ($vat_value / 100)), 0, ',', '.'); ?> đ</strong>
                </div>
                
                <?php else: ?>
                <!-- Hiển thị thông tin đơn đăng ký thường -->
                <div class="summary-item" style="font-size: var(--font-size-sm);">
                    <span>Mã đăng ký:</span>
                    <strong><?php echo htmlspecialchars($registration_id); ?></strong>
                 </div>
                <div class="summary-item">
                    <span>Gói dịch vụ:</span>
                    <strong><?php echo htmlspecialchars($package_name); ?> <?php echo $is_trial ? '(Dùng thử)' : ''; ?></strong>
                </div>
                <div class="summary-item">
                    <span>Số lượng:</span>
                    <strong><?php echo htmlspecialchars($quantity); ?> tài khoản</strong>
                </div>
                <div class="summary-item">
                    <span>Tỉnh/Thành phố:</span>
                    <strong><?php echo htmlspecialchars($province); ?></strong>
                </div>                <!-- Phần áp dụng voucher -->
                <?php if (!$is_trial): ?>
                <div class="voucher-section">
                    <h4>Mã giảm giá</h4>
                    <div class="voucher-form">
                        <input type="text" id="voucher-code" class="voucher-input" placeholder="Nhập mã giảm giá">
                        <button type="button" id="apply-voucher" class="voucher-btn">Áp dụng</button>
                    </div>
                    <div id="voucher-status" class="voucher-status"></div>
                    <div id="voucher-info" class="voucher-info" style="display: <?php echo isset($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_id']) ? 'block' : 'none'; ?>">
                        <div>Mã giảm giá: <strong id="applied-voucher-code"><?php echo isset($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_code']) ? htmlspecialchars($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_code']) : ''; ?></strong> 
                            <button type="button" id="remove-voucher" class="voucher-remove">Xóa</button>
                        </div>
                        <div id="discount-info">
                            <?php 
                            if (isset($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_discount'])) {
                                echo 'Giảm giá: ' . number_format($_SESSION[$is_renewal ? 'renewal' : 'order']['voucher_discount'], 0, ',', '.') . ' đ';
                            }
                            if (isset($_SESSION[$is_renewal ? 'renewal' : 'order']['additional_months']) && $_SESSION[$is_renewal ? 'renewal' : 'order']['additional_months'] > 0) {
                                echo 'Tăng thêm ' . $_SESSION[$is_renewal ? 'renewal' : 'order']['additional_months'] . ' tháng sử dụng';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>                <div class="summary-item">
                    <span>Giá trị đơn hàng:</span>
                    <strong><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>
                </div>
                <div class="summary-item">
                    <span>Thuế VAT (<?php echo $vat_value; ?>%):</span>
                    <strong><?php echo number_format($verified_total_price * ($vat_value / 100), 0, ',', '.'); ?> đ</strong>
                </div>
                <div class="summary-item summary-total">
                    <span>Tổng thanh toán:</span>
                    <strong id="total-price-display"><?php echo number_format($verified_total_price + ($verified_total_price * ($vat_value / 100)), 0, ',', '.'); ?> đ</strong>
                </div>
                <?php endif; ?>
            </section>

            <?php if ($is_trial): ?>
            <!-- Cột Xác nhận Dùng thử -->
            <section class="payment-qr-section" style="text-align: center;">
                <h3>Kích hoạt gói dùng thử</h3>
                <p style="margin-bottom: 1.5rem; color: var(--gray-600);">Gói dùng thử của bạn sẽ được kích hoạt ngay lập tức.</p>                <form id="trialActivationForm" action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=process_trial_activation" method="POST">
                    <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                    <!-- CSRF Protection Token -->
                    <?php echo generate_csrf_input(); ?>
                    <button type="submit" class="btn btn-success" style="padding: 0.8rem 1.5rem; font-size: var(--font-size-base); background-color: var(--success-500); border-color: var(--success-500);">
                        Xác nhận kích hoạt
                    </button>
                </form>
                 <p class="payment-instructions" style="margin-top: 1rem;">
                     Sau khi xác nhận, bạn có thể bắt đầu sử dụng dịch vụ.
                 </p>
            </section>

            <?php else: ?>
            <!-- Cột Mã QR và Hướng dẫn (Chỉ hiển thị nếu không phải trial) -->
            <section class="payment-qr-section">
                <h3>Quét mã để thanh toán</h3>
                <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: 1rem;">Sử dụng ứng dụng ngân hàng hoặc ví điện tử hỗ trợ VietQR.</p>
                <!-- Div để hiển thị QR Code -->
                <div id="qrcode">
                     <img src="<?php echo htmlspecialchars($vietqr_image_url); ?>" alt="VietQR Code" style="display: block; width: 100%; height: auto; object-fit: contain;">
                </div>

                <div class="bank-details">
                    <p><strong>Thông tin chuyển khoản thủ công:</strong></p>
                    <p>Ngân hàng: <strong><?php echo defined('VIETQR_BANK_NAME') ? VIETQR_BANK_NAME : (defined('VIETQR_BANK_ID') ? VIETQR_BANK_ID : 'N/A'); ?></strong></p>                    <p>Số tài khoản: <strong id="account-number"><?php echo defined('VIETQR_ACCOUNT_NO') ? VIETQR_ACCOUNT_NO : 'N/A'; ?></strong> <code title="Sao chép số tài khoản" data-copy-target="#account-number">Copy</code></p>
                    <p>Chủ tài khoản: <strong><?php echo defined('VIETQR_ACCOUNT_NAME') ? VIETQR_ACCOUNT_NAME : 'N/A'; ?></strong></p>
                    <p>Số tiền: <strong id="payment-amount">
                        <?php 
                        // Hiển thị tổng thanh toán (đã bao gồm VAT)
                        $display_price = $verified_total_price;
                        $session_key = $is_renewal ? 'renewal' : 'order';
                        if (isset($_SESSION[$session_key]['voucher_id'])) {
                            if ($is_renewal && isset($_SESSION[$session_key]['amount'])) {
                                $display_price = $_SESSION[$session_key]['amount'];
                            } elseif (!$is_renewal && isset($_SESSION[$session_key]['total_price'])) {
                                $display_price = $_SESSION[$session_key]['total_price'];
                            }
                        }
                        $display_price_with_vat = $display_price + ($display_price * ($vat_value / 100));
                        echo number_format($display_price_with_vat, 0, ',', '.'); 
                        ?> đ</strong> <code title="Sao chép số tiền" data-copy-target="#payment-amount">Copy</code></p>
                    <p>Nội dung: <strong id="payment-description"><?php echo htmlspecialchars($order_description); ?></strong> <code title="Sao chép nội dung" data-copy-target="#payment-description">Copy</code></p>
                </div>

                <p class="payment-instructions">
                    <strong>Lưu ý:</strong> Vui lòng nhập <strong>chính xác</strong> nội dung chuyển khoản <code><?php echo htmlspecialchars($order_description); ?></code>.
                    Lưu lại ảnh minh chứng chuyển khoản thành công để đội ngũ chúng tôi nhận thông tin và duyệt tạo tài khoản đo đạc cho bạn. 
                </p>
                 <p class="payment-instructions" style="margin-top: 0.5rem;">
                     Nếu gặp sự cố, vui lòng liên hệ bộ phận hỗ trợ.
                 </p>                <!-- === Nút xác nhận và chuyển hướng === -->
                <div style="text-align: center; margin-top: 2rem;">
                    <button data-href="<?php echo $base_url; ?>/public/pages/purchase/upload_proof.php?reg_id=<?php echo htmlspecialchars($registration_id); ?>" class="btn btn-primary btn-payment-confirm" style="padding: 0.8rem 1.5rem; font-size: var(--font-size-base);">
                        Tôi đã thanh toán - Tải lên minh chứng
                    </button>
                    <p style="font-size: var(--font-size-sm); color: var(--gray-500); margin-top: 0.8rem;">
                        (Bạn hãy tải lên minh chứng sau khi chuyển khoản thành công)
                    </p>
                </div>
                 <!-- === Kết thúc Nút xác nhận === -->

            </section>
            <?php endif; ?>

        </div>

    </main>
</div>

<!-- JavaScript variables needed for payment scripts -->
<script>
    // Define variables needed by payment_data.js and payment_voucher.js
    const JS_IS_TRIAL = <?php echo $is_trial ? 'true' : 'false'; ?>;
    const JS_IS_RENEWAL = <?php echo $is_renewal ? 'true' : 'false'; ?>;
    const JS_BASE_PRICE = <?php echo $verified_total_price; ?>;
    const JS_VAT_VALUE = <?php echo $vat_value; ?>;
    const JS_CURRENT_PRICE = <?php echo $verified_total_price + ($verified_total_price * ($vat_value / 100)); ?>;
    const JS_ORDER_DESCRIPTION = "<?php echo htmlspecialchars($order_description, ENT_QUOTES, 'UTF-8'); ?>";
    const JS_BASE_URL = "<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>";
    const JS_CSRF_TOKEN = "<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
    const JS_VIETQR_BANK_ID = "<?php echo defined('VIETQR_BANK_ID') ? htmlspecialchars(VIETQR_BANK_ID, ENT_QUOTES, 'UTF-8') : ''; ?>";
    const JS_VIETQR_ACCOUNT_NO = "<?php echo defined('VIETQR_ACCOUNT_NO') ? htmlspecialchars(VIETQR_ACCOUNT_NO, ENT_QUOTES, 'UTF-8') : ''; ?>";
    const JS_VIETQR_IMAGE_TEMPLATE = "<?php echo defined('VIETQR_IMAGE_TEMPLATE') ? htmlspecialchars(VIETQR_IMAGE_TEMPLATE, ENT_QUOTES, 'UTF-8') : 'compact'; ?>";
    const JS_VIETQR_ACCOUNT_NAME = "<?php echo defined('VIETQR_ACCOUNT_NAME') ? htmlspecialchars(VIETQR_ACCOUNT_NAME, ENT_QUOTES, 'UTF-8') : ''; ?>";
</script>

<!-- Script cho quá trình kích hoạt gói dùng thử -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Không cần xử lý modal kích hoạt trial nữa
    });
</script>


<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/payment_data.js"></script>
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/payment_voucher.js"></script>

<?php
// --- Include Footer ---
// Use correct path relative to project root
include $project_root_path . '/private/includes/footer.php';
?>
