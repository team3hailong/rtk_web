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

// Check if current voucher has auto_approve enabled and need_upload_proof
$has_auto_approve_voucher = false;
$need_upload_proof = true; // Default: cần upload proof
if (isset($_SESSION[$sessionKey]['voucher_id'])) {
    try {
        require_once $project_root_path . '/private/classes/Database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT auto_approve, need_upload_proof FROM voucher WHERE id = :voucher_id AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':voucher_id', $_SESSION[$sessionKey]['voucher_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $voucher_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($voucher_data) {
            // YÊU CẦU MỚI: Chỉ cho phép auto-approve khi auto_approve = 1 VÀ verified_total_price = 0
            if ($voucher_data['auto_approve'] == 1 && $verified_total_price == 0) {
                $has_auto_approve_voucher = true;
            }
            // Lấy thông tin need_upload_proof từ voucher
            $need_upload_proof = isset($voucher_data['need_upload_proof']) ? (bool)$voucher_data['need_upload_proof'] : true;
        }
    } catch (Exception $e) {
        error_log("Error checking voucher properties: " . $e->getMessage());
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
        <div class="payment-wizard">
            <div class="payment-wizard-header">
                <h2><?php echo $is_trial ? 'Xác nhận kích hoạt dùng thử' : 'Thanh toán đơn hàng'; ?></h2>
                <?php if (!$is_trial): ?>
                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Thông tin</div>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Thanh toán</div>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Xác nhận</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="payment-wizard-content">
                <?php if ($is_trial): ?>
                    <!-- Trial Activation -->
                    <section class="wizard-section active" data-section="trial">
                        <div class="section-card">
                            <div class="section-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <h3>Kích hoạt gói dùng thử</h3>
                            <p class="section-description">Gói dùng thử của bạn sẽ được kích hoạt ngay lập tức.</p>
                            <form id="trialActivationForm" action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=process_trial_activation" method="POST">
                                <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                                <?php echo generate_csrf_input(); ?>
                                <button type="submit" class="btn btn-primary btn-wizard">Xác nhận kích hoạt</button>
                            </form>
                        </div>
                    </section>
                <?php else: ?>
                    <!-- Step 1: Thông tin đăng ký -->
                    <section class="wizard-section active" data-section="1">
                        <div class="section-card">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Thông tin đăng ký
                            </h3>
                            <div class="section-content">
                                <?php if ($is_renewal): ?>
                                    <!-- Renewal info display -->
                                    <div class="info-row"><span>Loại:</span><strong>Gia hạn dịch vụ</strong></div>
                                    <div class="info-row"><span>Số tài khoản:</span><strong><?php echo count($_SESSION['renewal_account_ids'] ?? []); ?> tài khoản</strong></div>
                                <?php else: ?>
                                    <div class="info-row"><span>Mã đăng ký:</span><strong><?php echo htmlspecialchars($registration_id); ?></strong></div>
                                    <div class="info-row"><span>Gói dịch vụ:</span><strong><?php echo htmlspecialchars($payment_data['package_name']); ?></strong></div>
                                    <div class="info-row"><span>Số lượng:</span><strong><?php echo htmlspecialchars($payment_data['quantity']); ?> tài khoản</strong></div>
                                    <div class="info-row"><span>Tỉnh/Thành phố:</span><strong><?php echo htmlspecialchars($payment_data['province']); ?></strong></div>
                                <?php endif; ?>
                                
                                <?php if (defined('SHOW_GLOBAL_DISCOUNT') && SHOW_GLOBAL_DISCOUNT === 'yes'): ?>
                                    <div class="global-discount-payment-note" role="note" aria-live="polite">
                                        <strong>Ưu đãi:</strong>
                                        Mỗi người sử dụng Voucher <strong><?php echo htmlspecialchars(GLOBAL_DISCOUNT_CODE); ?></strong>tối đa 1 lần, áp dụng giảm 100% cho gói 3 tháng. Hãy chia sẻ cho bạn bè, người thân để cùng nhận ưu đãi này
                                    </div>
                                <?php endif; ?>

                                <div class="voucher-section">
                                    <h4><i class="fas fa-ticket-alt"></i> Mã giảm giá</h4>
                                    <div class="voucher-form">
                                        <input type="text" id="voucher-code" class="voucher-input" placeholder="Nhập mã giảm giá (nếu có)">
                                        <button type="button" id="apply-voucher" class="voucher-btn">Áp dụng</button>
                                    </div>
                                    <div id="voucher-status" class="voucher-status"></div>
                                    <div id="voucher-info" class="voucher-info" style="display: <?php echo isset($_SESSION[$sessionKey]['voucher_id']) ? 'block' : 'none'; ?>">
                                        <div class="voucher-applied">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Mã: <strong id="applied-voucher-code"><?php echo htmlspecialchars($_SESSION[$sessionKey]['voucher_code'] ?? ''); ?></strong></span>
                                            <button type="button" id="remove-voucher" class="voucher-remove"><i class="fas fa-times"></i></button>
                                        </div>
                                        <div id="discount-info" class="discount-info">
                                            <?php
                                            if (isset($_SESSION[$sessionKey]['voucher_discount'])) {
                                                echo '<i class="fas fa-tag"></i> Giảm giá: ' . number_format($_SESSION[$sessionKey]['voucher_discount'], 0, ',', '.') . ' đ';
                                            }
                                            if (isset($_SESSION[$sessionKey]['additional_months']) && $_SESSION[$sessionKey]['additional_months'] > 0) {
                                                echo '<br><i class="fas fa-calendar-plus"></i> Tăng thêm ' . $_SESSION[$sessionKey]['additional_months'] . ' tháng sử dụng';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="price-summary">
                                    <div class="price-row"><span>Giá gốc:</span><strong><?php echo number_format($payment_data['base_price_from_registration'] * $payment_data['quantity'], 0, ',', '.'); ?> đ</strong></div>
                                    
                                    <?php if (isset($_SESSION[$sessionKey]['voucher_discount']) && $_SESSION[$sessionKey]['voucher_discount'] > 0): ?>
                                        <div class="price-row discount">
                                            <span><i class="fas fa-tag"></i> Giảm giá:</span>
                                            <strong>-<?php echo number_format($_SESSION[$sessionKey]['voucher_discount'], 0, ',', '.'); ?> đ</strong>
                                        </div>
                                        <div class="price-row"><span>Giá sau giảm:</span><strong><?php echo number_format($_SESSION[$sessionKey]['discounted_subtotal'] ?? 0, 0, ',', '.'); ?> đ</strong></div>
                                    <?php endif; ?>
                                    
                                    <div class="price-row">
                                        <span>VAT (<?php echo $payment_data['vat_percent_from_registration']; ?>%):</span>
                                        <strong><?php
                                        $vat_to_display = isset($_SESSION[$sessionKey]['voucher_code'])
                                            ? round(($_SESSION[$sessionKey]['discounted_subtotal'] ?? 0) * ($payment_data['vat_percent_from_registration'] / 100))
                                            : $payment_data['vat_amount_from_registration'];
                                        echo number_format($vat_to_display, 0, ',', '.');
                                        ?> đ</strong>
                                    </div>
                                    <div class="price-row total">
                                        <span>Tổng thanh toán:</span>
                                        <strong id="total-price-display"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="section-actions">
                                <button type="button" class="btn btn-primary btn-wizard btn-next" onclick="goToStep(2)">
                                    Tiếp tục <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Step 2: Quét mã thanh toán -->
                    <section class="wizard-section" data-section="2">
                        <div class="section-card">
                            <!-- Payment instructions (for all cases) -->
                            <div id="payment-qr-code-section">
                                <?php if ($verified_total_price <= 0): ?>
                                    <!-- Free order - skip payment -->
                                    <h3 class="section-title">
                                        <i class="fas fa-check-circle" style="color: var(--success-600);"></i>
                                        Xác nhận đơn hàng
                                    </h3>
                                    <div class="section-content">
                                        <div class="free-order-notice">
                                            <p>Do áp dụng mã giảm giá, tổng thanh toán của bạn là <strong style="color: var(--success-600);"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>.</p>
                                            <?php if ($has_auto_approve_voucher): ?>
                                                <p>Voucher của bạn hỗ trợ <strong style="color: var(--success-600);">duyệt tự động</strong>. Vui lòng tiếp tục để hoàn tất đăng ký.</p>
                                            <?php else: ?>
                                                <p>Vui lòng tiếp tục để hoàn tất đăng ký.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Paid order - show QR code -->
                                    <h3 class="section-title">
                                        <i class="fas fa-qrcode"></i>
                                        Quét mã để thanh toán
                                    </h3>
                                    <div class="section-content">
                                        <?php if ($has_auto_approve_voucher): ?>
                                            <div class="auto-approve-notice">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Voucher của bạn hỗ trợ <strong>duyệt tự động</strong>. Đơn hàng sẽ được duyệt tự động sau khi thanh toán.</span>
                                            </div>
                                        <?php endif; ?>
                                        <p class="section-description">Sử dụng ứng dụng ngân hàng hoặc ví điện tử hỗ trợ VietQR để quét mã thanh toán.</p>
                                        <div class="qr-container">
                                            <div id="qrcode">
                                                <img src="<?php echo htmlspecialchars($vietqr_image_url ?? ''); ?>" alt="VietQR Code">
                                            </div>
                                        </div>
                                        <div class="bank-details">
                                            <h4><i class="fas fa-university"></i> Thông tin chuyển khoản</h4>
                                            <div class="bank-info-grid">
                                                <div class="bank-info-item">
                                                    <span class="label">Ngân hàng:</span>
                                                    <strong><?php echo defined('VIETQR_BANK_NAME') ? VIETQR_BANK_NAME : 'N/A'; ?></strong>
                                                </div>
                                                <div class="bank-info-item">
                                                    <span class="label">Số tài khoản:</span>
                                                    <div class="copyable-field">
                                                        <strong id="account-number"><?php echo defined('VIETQR_ACCOUNT_NO') ? VIETQR_ACCOUNT_NO : 'N/A'; ?></strong>
                                                        <button class="copy-btn" data-copy-target="#account-number"><i class="fas fa-copy"></i></button>
                                                    </div>
                                                </div>
                                                <div class="bank-info-item">
                                                    <span class="label">Chủ tài khoản:</span>
                                                    <strong><?php echo defined('VIETQR_ACCOUNT_NAME') ? VIETQR_ACCOUNT_NAME : 'N/A'; ?></strong>
                                                </div>
                                                <div class="bank-info-item">
                                                    <span class="label">Số tiền:</span>
                                                    <div class="copyable-field">
                                                        <strong id="payment-amount" class="highlight-amount"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>
                                                        <button class="copy-btn" data-copy-target="#payment-amount"><i class="fas fa-copy"></i></button>
                                                    </div>
                                                </div>
                                                <div class="bank-info-item full-width">
                                                    <span class="label">Nội dung CK:</span>
                                                    <div class="copyable-field">
                                                        <strong id="payment-description"><?php echo htmlspecialchars($order_description); ?></strong>
                                                        <button class="copy-btn" data-copy-target="#payment-description"><i class="fas fa-copy"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="payment-note"><i class="fas fa-exclamation-circle"></i> Vui lòng nhập <strong>chính xác</strong> nội dung chuyển khoản để được xử lý tự động.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="section-actions">
                                    <button type="button" class="btn btn-secondary btn-wizard btn-prev" onclick="goToStep(1)">
                                        <i class="fas fa-arrow-left"></i> Quay lại
                                    </button>
                                    
                                    <?php if ($verified_total_price <= 0 && !$need_upload_proof): ?>
                                        <!-- Giao dịch = 0 đồng VÀ voucher không cần upload proof → Submit trực tiếp -->
                                        <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=complete_order_without_proof" method="POST" style="display: inline;">
                                            <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registration_id); ?>">
                                            <?php echo generate_csrf_input(); ?>
                                            <button type="submit" class="btn btn-success btn-wizard">
                                                <i class="fas fa-check"></i> Hoàn tất đăng ký
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Tất cả trường hợp khác: giao dịch > 0 HOẶC cần upload proof → Chuyển sang step 3 -->
                                        <button type="button" class="btn btn-primary btn-wizard btn-next" onclick="goToStep(3)">
                                            <?php if ($verified_total_price <= 0): ?>
                                                Tiếp tục <i class="fas fa-arrow-right"></i>
                                            <?php else: ?>
                                                Đã thanh toán <i class="fas fa-arrow-right"></i>
                                            <?php endif; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Step 3: Upload ảnh minh chứng -->
                    <!-- Hiển thị cho TẤT CẢ trường hợp: giao dịch > 0 hoặc cần upload proof -->
                    <section class="wizard-section" data-section="3">
                        <div class="section-card">
                            <h3 class="section-title">
                                <i class="fas fa-upload"></i>
                                Upload ảnh minh chứng
                            </h3>
                            <div class="section-content">
                                <p class="section-description">Vui lòng tải lên ảnh chụp màn hình hoặc ảnh xác nhận giao dịch chuyển khoản thành công.</p>
                                
                                <div class="upload-instruction">
                                    <i class="fas fa-info-circle"></i>
                                    <span><strong>Bước 1:</strong> Chọn ảnh minh chứng → <strong>Bước 2:</strong> Click nút "Tải lên minh chứng" bên dưới</span>
                                </div>
                                
                                <div class="upload-area">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">
                                        <p class="upload-title">Chọn hoặc kéo thả ảnh vào đây</p>
                                        <p class="upload-subtitle">Hỗ trợ: JPG, PNG, PDF (Tối đa 5MB)</p>
                                    </div>
                                    <input type="file" id="proof-file-input" accept="image/*,.pdf" style="display: none;">
                                    <button type="button" class="btn btn-outline btn-select-file" onclick="document.getElementById('proof-file-input').click()">
                                        <i class="fas fa-folder-open"></i> Chọn file
                                    </button>
                                </div>
                                <div id="file-preview" class="file-preview" style="display: none;">
                                    <div class="preview-header">
                                        <span id="file-name"></span>
                                        <button type="button" class="btn-remove-file" onclick="removeFile()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="preview-image" class="preview-image"></div>
                                </div>
                                <div id="upload-progress-container" class="upload-progress-container" style="display: none;">
                                    <div class="progress-bar-wrapper">
                                        <div id="upload-progress-bar" class="progress-bar-fill"></div>
                                    </div>
                                    <p id="upload-progress-text" class="progress-text">Đang tải lên: 0%</p>
                                </div>
                                <div id="upload-status" class="upload-status"></div>
                            </div>
                            <div class="section-actions">
                                <button type="button" class="btn btn-secondary btn-wizard btn-prev" onclick="goToStep(2)">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </button>
                                <button type="button" id="upload-submit-btn" class="btn btn-success btn-wizard" onclick="submitProof()" disabled>
                                    <i class="fas fa-upload"></i> Tải lên minh chứng
                                </button>
                            </div>
                        </div>
                    </section>
                <?php endif; // end if (!$is_trial) ?>
            </div>
        </div>
    </main>
</div>

<!-- JavaScript variables -->
<script>
    const JS_IS_TRIAL = <?php echo $is_trial ? 'true' : 'false'; ?>;
    const JS_IS_RENEWAL = <?php echo $is_renewal ? 'true' : 'false'; ?>;
    const JS_BASE_URL = "<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>";
    const JS_CSRF_TOKEN = "<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
    const JS_REGISTRATION_ID = "<?php echo htmlspecialchars($registration_id, ENT_QUOTES, 'UTF-8'); ?>";
</script>

<!-- Scripts -->
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/payment_voucher.js"></script>
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/auto_voucher_notification.js"></script>
<script src="<?php echo defined('PUBLIC_URL') ? PUBLIC_URL : '/public'; ?>/assets/js/pages/purchase/payment_wizard.js"></script>
<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>