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
require_once $project_root_path . '/private/utils/csrf_helper.php';
require_once $project_root_path . '/private/utils/device_voucher_helper.php';
require_once $project_root_path . '/private/classes/purchase/PaymentService.php';

// --- VAT Rate ---
$vat_value = getenv('VAT_VALUE') !== false ? (float)getenv('VAT_VALUE') : 10;

// --- Authentication & Pending Order Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}
if (!isset($_SESSION['pending_registration_id']) || !isset($_SESSION['pending_total_price'])) {
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=no_pending_order');
    exit;
}

$user_id = $_SESSION['user_id'];
$registration_id = $_SESSION['pending_registration_id'];
$session_total_price = $_SESSION['pending_total_price'];
$is_trial = $_SESSION['pending_is_trial'] ?? false;
$is_renewal = $_SESSION['is_renewal'] ?? false;

// Create order/renewal session object required by voucher system
$sessionKey = $is_renewal ? 'renewal' : 'order';
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

if (!isset($_SESSION['is_renewal'])) {
    $is_renewal = false;
    unset($_SESSION['renewal_account_ids']);
    unset($_SESSION['pending_renewal_details']);
}

// Check for device vouchers
if (isset($_SESSION['device_fingerprint']) && (!isset($_SESSION[$sessionKey]['voucher_code']) || empty($_SESSION[$sessionKey]['voucher_code']))) {
    if (!isset($_SESSION['payment_data'])) {
        $_SESSION['payment_data'] = ['base_price_from_registration' => $session_total_price, 'quantity' => 1];
    }
    $deviceVoucherResult = checkAndApplyDeviceVoucher($_SESSION['device_fingerprint'], $sessionKey);
    $autoAppliedVoucher = $deviceVoucherResult ? true : false;
    if ($deviceVoucherResult) {
        $original_price = ($_SESSION['payment_data']['base_price_from_registration'] * ($_SESSION['payment_data']['quantity'] ?? 1));
        $discount_amount = $deviceVoucherResult['discount_value'];
        $session_total_price = max(0, $original_price - $discount_amount);
        $_SESSION['pending_total_price'] = $session_total_price;
    }
}

// --- Fetch Payment Details using PaymentService ---
$paymentService = new PaymentService();
$payment_details_result = $paymentService->getPaymentPageDetails($registration_id, $user_id, $session_total_price);

if (!$payment_details_result['success']) {
    $error_code = $payment_details_result['error'];
    if ($error_code === 'invalid_order_state') {
        unset($_SESSION['pending_registration_id'], $_SESSION['pending_total_price']);
    }
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=' . $error_code);
    exit;
}

$payment_data = $payment_details_result['data'];
$verified_total_price = $payment_data['verified_total_price'];

// Store base price data in session
$_SESSION['payment_data'] = $payment_data;

// Check if current voucher has auto_approve enabled
$has_auto_approve_voucher = false;
if (isset($_SESSION[$sessionKey]['voucher_id'])) {
    try {
        require_once $project_root_path . '/private/classes/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT auto_approve FROM voucher WHERE id = :voucher_id AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':voucher_id', $_SESSION[$sessionKey]['voucher_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $voucher_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($voucher_data && $voucher_data['auto_approve'] == 1) {
            $has_auto_approve_voucher = true;
        }
    } catch (Exception $e) {
        error_log("Error checking auto-approve voucher: " . $e->getMessage());
    }
}

// Recalculate final price if a voucher is applied
if (isset($_SESSION[$sessionKey]['voucher_code'])) {
    $base_subtotal = $payment_data['base_price_from_registration'] * $payment_data['quantity'];
    $discountAmount = $_SESSION[$sessionKey]['voucher_discount'] ?? 0;
    $discounted_subtotal = max(0, $base_subtotal - $discountAmount);
    $vat_percent = $payment_data['vat_percent_from_registration'];
    $vat_on_discounted = round($discounted_subtotal * ($vat_percent / 100));
    $final_price = $discounted_subtotal + $vat_on_discounted;

    $sessionDataKey = $is_renewal ? 'amount' : 'total_price';
    $_SESSION[$sessionKey][$sessionDataKey] = $final_price;
    $_SESSION[$sessionKey]['discounted_subtotal'] = $discounted_subtotal;
    // Ensure auto-approve flow & other actions see the discounted total instead of the original price
    if (isset($_SESSION['payment_data'])) {
        $_SESSION['payment_data']['verified_total_price'] = $final_price;
    }
    
    $verified_total_price = $final_price;
}

// --- Create transfer content and QR code if needed ---
$order_description = "REG{$registration_id} MUA GOI";
$vietqr_image_url = null;
if (!$is_trial && $verified_total_price > 0) {
    $qr = $paymentService->generateVietQR($verified_total_price, $order_description);
    $vietqr_image_url = $qr['image_url'];
    $paymentService->updateTransactionHistoryAmount($registration_id, $user_id, $verified_total_price);
}

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS cho Trang Thanh Toán -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/payment.css">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

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
                    <!-- Renewal info display (giữ nguyên) -->
                <?php else: ?>
                    <!-- Purchase info display -->
                    <div class="summary-item"><span>Mã đăng ký:</span><strong><?php echo htmlspecialchars($registration_id); ?></strong></div>
                    <div class="summary-item"><span>Gói dịch vụ:</span><strong><?php echo htmlspecialchars($payment_data['package_name']); ?> <?php echo $is_trial ? '(Dùng thử)' : ''; ?></strong></div>
                    <div class="summary-item"><span>Số lượng:</span><strong><?php echo htmlspecialchars($payment_data['quantity']); ?> tài khoản</strong></div>
                    <div class="summary-item"><span>Tỉnh/Thành phố:</span><strong><?php echo htmlspecialchars($payment_data['province']); ?></strong></div>
                    
                    <?php if (!$is_trial): ?>
                    <div class="voucher-section">
                        <h4>Mã giảm giá</h4>
                        <div class="voucher-form">
                            <input type="text" id="voucher-code" class="voucher-input" placeholder="Nhập mã giảm giá">
                            <button type="button" id="apply-voucher" class="voucher-btn">Áp dụng</button>
                        </div>
                        <div id="voucher-status" class="voucher-status"></div>
                        <div id="voucher-info" class="voucher-info" style="display: <?php echo isset($_SESSION[$sessionKey]['voucher_id']) ? 'block' : 'none'; ?>">
                            <div>Mã giảm giá: <strong id="applied-voucher-code"><?php echo htmlspecialchars($_SESSION[$sessionKey]['voucher_code'] ?? ''); ?></strong>
                                <button type="button" id="remove-voucher" class="voucher-remove">Xóa</button>
                            </div>
                            <div id="discount-info">
                                <?php
                                if (isset($_SESSION[$sessionKey]['voucher_discount'])) {
                                    echo 'Giảm giá: ' . number_format($_SESSION[$sessionKey]['voucher_discount'], 0, ',', '.') . ' đ';
                                }
                                if (isset($_SESSION[$sessionKey]['additional_months']) && $_SESSION[$sessionKey]['additional_months'] > 0) {
                                    echo 'Tăng thêm ' . $_SESSION[$sessionKey]['additional_months'] . ' tháng sử dụng';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="summary-item"><span>Giá gốc:</span><strong><?php echo number_format($payment_data['base_price_from_registration'] * $payment_data['quantity'], 0, ',', '.'); ?> đ</strong></div>
                    
                    <?php if (isset($_SESSION[$sessionKey]['voucher_discount']) && $_SESSION[$sessionKey]['voucher_discount'] > 0): ?>
                        <div class="summary-item" style="color: #e53e3e; font-weight: bold;">
                            <span>Giảm giá (<?php echo htmlspecialchars($_SESSION[$sessionKey]['voucher_code']); ?>):</span>
                            <strong>-<?php echo number_format($_SESSION[$sessionKey]['voucher_discount'], 0, ',', '.'); ?> đ</strong>
                        </div>
                        <div class="summary-item"><span>Giá sau giảm giá:</span><strong><?php echo number_format($_SESSION[$sessionKey]['discounted_subtotal'] ?? 0, 0, ',', '.'); ?> đ</strong></div>
                    <?php endif; ?>
                    
                    <div class="summary-item">
                        <span>Thuế VAT (<?php echo $payment_data['vat_percent_from_registration']; ?>%):</span>
                        <strong><?php
                        $vat_to_display = isset($_SESSION[$sessionKey]['voucher_code'])
                            ? round(($_SESSION[$sessionKey]['discounted_subtotal'] ?? 0) * ($payment_data['vat_percent_from_registration'] / 100))
                            : $payment_data['vat_amount_from_registration'];
                        echo number_format($vat_to_display, 0, ',', '.');
                        ?> đ</strong>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Tổng thanh toán:</span>
                        <strong id="total-price-display"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($is_trial): ?>
                <!-- Trial Activation Section -->
                <section class="payment-qr-section" style="text-align: center;">
                    <h3>Kích hoạt gói dùng thử</h3>
                    <p style="margin-bottom: 1.5rem; color: var(--gray-600);">Gói dùng thử của bạn sẽ được kích hoạt ngay lập tức.</p>
                    <form id="trialActivationForm" action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=process_trial_activation" method="POST">
                        <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                        <?php echo generate_csrf_input(); ?>
                        <button type="submit" class="btn btn-success" style="padding: 0.8rem 1.5rem;">Xác nhận kích hoạt</button>
                    </form>
                </section>
            <?php else: ?>
                <!-- Payment Section: Logic hiển thị động -->
                <div id="payment-method-container">

                    <!-- Giao diện cho đơn hàng được duyệt tự động-->
                    <section id="free-order-confirmation-section" class="payment-qr-section" style="display: <?php echo ($verified_total_price <= 0) ? 'block' : 'none'; ?>;">
                        <h3>Hoàn tất đơn hàng</h3>
                        <div class="free-order-notice">
                            <p>Do áp dụng mã giảm giá, tổng thanh toán của bạn là <strong style="color: var(--success-600);"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>.</p>
                            <?php if ($has_auto_approve_voucher): ?>
                                <p>Voucher của bạn hỗ trợ <strong style="color: var(--success-600);">duyệt tự động</strong>. Vui lòng nhấn nút bên dưới để hoàn tất đăng ký và kích hoạt tài khoản ngay lập tức.</p>
                            <?php else: ?>
                                <p>Vui lòng nhấn nút bên dưới để hoàn tất đăng ký và kích hoạt tài khoản của bạn ngay lập tức.</p>
                            <?php endif; ?>
                        </div>
                        <?php
                        // Determine which action to use based on voucher type
                        $action_to_use = $has_auto_approve_voucher ? 'process_auto_approve_order' : 'process_free_order';
                        $button_text = $has_auto_approve_voucher ? 'Hoàn tất đăng ký (Duyệt tự động)' : 'Hoàn tất đăng ký miễn phí';
                        ?>
                        <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=<?php echo $action_to_use; ?>" method="POST" style="margin-top: 2rem;">
                            <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                            <?php echo generate_csrf_input(); ?>
                            <button type="submit" class="btn btn-complete-free-order">
                                <?php echo htmlspecialchars($button_text); ?>
                            </button>
                        </form>
                    </section>

                    <!-- Giao diện cho đơn hàng CÓ TÍNH PHÍ (giá > 0) -->
                    <section id="payment-qr-code-section" class="payment-qr-section" style="display: <?php echo ($verified_total_price > 0) ? 'block' : 'none'; ?>;">
                        <h3>Quét mã để thanh toán</h3>
                        <?php if ($has_auto_approve_voucher): ?>
                            <div style="background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px; padding: 1rem; margin-bottom: 1rem;">
                                <p style="margin: 0; color: #2d5a2d; font-weight: 500;">
                                    <i class="fas fa-check-circle" style="color: var(--success-600);"></i>
                                    Voucher của bạn hỗ trợ <strong>duyệt tự động</strong>. 
                                    Sau khi thanh toán, đơn hàng sẽ được duyệt tự động trong vòng vài phút.
                                </p>
                            </div>
                        <?php endif; ?>
                        <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: 1rem;">Sử dụng ứng dụng ngân hàng hoặc ví điện tử hỗ trợ VietQR.</p>
                        <div id="qrcode">
                            <img src="<?php echo htmlspecialchars($vietqr_image_url ?? ''); ?>" alt="VietQR Code" style="display: block; width: 100%; height: auto; object-fit: contain;">
                        </div>
                        <div class="bank-details">
                            <p><strong>Thông tin chuyển khoản thủ công:</strong></p>
                            <p>Ngân hàng: <strong><?php echo defined('VIETQR_BANK_NAME') ? VIETQR_BANK_NAME : 'N/A'; ?></strong></p>
                            <p>Số tài khoản: <strong id="account-number"><?php echo defined('VIETQR_ACCOUNT_NO') ? VIETQR_ACCOUNT_NO : 'N/A'; ?></strong> <code title="Sao chép" data-copy-target="#account-number">Copy</code></p>
                            <p>Chủ tài khoản: <strong><?php echo defined('VIETQR_ACCOUNT_NAME') ? VIETQR_ACCOUNT_NAME : 'N/A'; ?></strong></p>
                            <p>Số tiền: <strong id="payment-amount"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong> <code title="Sao chép" data-copy-target="#payment-amount">Copy</code></p>
                            <p>Nội dung: <strong id="payment-description"><?php echo htmlspecialchars($order_description); ?></strong> <code title="Sao chép" data-copy-target="#payment-description">Copy</code></p>
                        </div>
                        <p class="payment-instructions"><strong>Lưu ý:</strong> Vui lòng nhập <strong>chính xác</strong> nội dung chuyển khoản.</p>
                        <div style="text-align: center; margin-top: 2rem;">
                            <button data-href="<?php echo $base_url; ?>/public/pages/purchase/upload_proof.php?reg_id=<?php echo htmlspecialchars($registration_id); ?>" class="btn btn-primary btn-payment-confirm">
                                Tôi đã thanh toán - Tải lên minh chứng
                            </button>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- JavaScript variables -->
<script>
    const JS_IS_TRIAL = <?php echo $is_trial ? 'true' : 'false'; ?>;
    const JS_IS_RENEWAL = <?php echo $is_renewal ? 'true' : 'false'; ?>;
    const JS_BASE_URL = "<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>";
    const JS_CSRF_TOKEN = "<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
</script>

<!-- Scripts -->
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/payment_voucher.js"></script>
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/auto_voucher_notification.js"></script>
<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>