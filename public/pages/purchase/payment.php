<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Required Files ---
require_once $project_root_path . '/private/utils/functions.php'; // For CRC function if moved there
require_once $project_root_path . '/private/utils/vietqr_helper.php'; // Include the new VietQR helper
require_once $project_root_path . '/private/utils/payment_helper.php'; // Include the new payment helper
require_once $project_root_path . '/private/utils/csrf_helper.php'; // Include CSRF Helper

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

// --- Fetch Payment Details using Helper ---
$payment_details_result = getPaymentPageDetails($registration_id, $user_id, $session_total_price);

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
    $final_qr_payload = generate_vietqr_payload($verified_total_price, $order_description);
    // --- Generate img.vietqr.io URL ---
    $vietqr_image_url = sprintf(
        "https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s",
        VIETQR_BANK_ID, // Bank BIN/ID
        VIETQR_ACCOUNT_NO, // Account Number
        defined('VIETQR_IMAGE_TEMPLATE') ? VIETQR_IMAGE_TEMPLATE : 'compact2', // Template (e.g., compact2)
        $verified_total_price, // Amount
        urlencode($order_description), // URL Encoded Description
        urlencode(VIETQR_ACCOUNT_NAME) // URL Encoded Account Name
    );
}

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
// Use correct path relative to project root
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS cho Trang Thanh Toán -->
<style>
    .payment-summary, .payment-qr-section {
        background-color: white;
        padding: 2rem;
        border-radius: var(--rounded-lg);
        border: 1px solid var(--gray-200);
        margin-bottom: 2rem;
    }

    .payment-summary h3, .payment-qr-section h3 {
        font-size: var(--font-size-lg);
        font-weight: var(--font-semibold);
        color: var(--gray-800);
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        padding-bottom: 0.75rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center; /* Căn giữa nếu text dài */
        margin-bottom: 0.75rem;
        font-size: var(--font-size-base);
        color: var(--gray-700);
        gap: 1rem; /* Khoảng cách giữa label và value */
        flex-wrap: wrap; /* Cho phép xuống dòng nếu không đủ chỗ */
    }
    .summary-item span:first-child { flex-shrink: 0;} /* Không co label */
    .summary-item strong {
        font-weight: var(--font-semibold); /* Đậm hơn medium */
        color: var(--gray-900);
        text-align: right; /* Căn phải giá trị */
    }
    .summary-total {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--gray-300);
        font-size: 1.25rem; /* --font-size-xl */
        font-weight: var(--font-bold);
        color: var(--primary-600);
    }

    .payment-qr-section {
        text-align: center;
    }

    #qrcode {
        width: 250px; /* Kích thước QR */
        height: 250px;
        margin: 1rem auto 1.5rem auto; /* Căn giữa QR */
        border: 5px solid white; /* Khung trắng quanh QR */
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex; /* Để căn giữa placeholder nếu JS chưa chạy */
        align-items: center;
        justify-content: center;
        background-color: var(--gray-100); /* Nền chờ */
    }
    #qrcode img { /* Style cho thẻ img do thư viện JS tạo ra */
        display: block;
        width: 100% !important;
        height: 100% !important;
        object-fit: contain; /* Đảm bảo QR không bị méo */
    }

    .bank-details p {
        margin-bottom: 0.5rem;
        color: var(--gray-600);
        font-size: var(--font-size-sm); /* Chữ nhỏ hơn chút */
    }
     .bank-details strong {
         color: var(--gray-800);
         font-weight: var(--font-semibold);
     }
     .bank-details code {
        background-color: var(--gray-100);
        padding: 0.2em 0.5em;
        border-radius: var(--rounded-sm);
        font-family: monospace;
        color: var(--gray-700);
        cursor: pointer;
        border: 1px solid var(--gray-200);
        display: inline-block; /* Để có padding */
        margin-left: 5px;
        position: relative; /* Cho tooltip nếu muốn */
     }
    .bank-details code:hover::after {
        content: 'Sao chép';
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background-color: var(--gray-800);
        color: white;
        padding: 2px 6px;
        border-radius: var(--rounded-sm);
        font-size: 0.7rem;
        white-space: nowrap;
        margin-bottom: 4px;
    }


    .payment-instructions {
        margin-top: 1.5rem;
        font-size: var(--font-size-sm);
        color: var(--gray-500);
        line-height: 1.6;
    }


     @media (max-width: 768px) {
        .content-wrapper {
            padding: 1rem !important;
        }
        .payment-summary, .payment-qr-section {
            padding: 1.5rem;
        }
         #qrcode {
            width: 200px;
            height: 200px;
        }
        .payment-container {
             grid-template-columns: 1fr; /* Stack 2 cột trên mobile */
        }
    }

    .upload-section {
        margin-top: 2rem;
        padding: 1.5rem;
        background-color: var(--gray-50);
        border: 1px dashed var(--gray-300);
        border-radius: var(--rounded-md);
        text-align: center;
    }
    .upload-section h4 {
        font-size: var(--font-size-base);
        font-weight: var(--font-semibold);
        color: var(--gray-700);
        margin-bottom: 1rem;
    }
    .upload-section p {
        font-size: var(--font-size-sm);
        color: var(--gray-500);
        margin-bottom: 1rem;
    }
    .upload-section input[type="file"] {
        display: block;
        margin: 1rem auto;
        padding: 0.5rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--rounded-md);
        max-width: 300px; /* Giới hạn chiều rộng */
        cursor: pointer;
    }
     .upload-section .btn-upload {
        /* Style giống btn-primary hoặc btn-secondary */
        padding: 0.6rem 1.2rem;
        background-color: var(--primary-600);
        color: white;
        border: none;
        border-radius: var(--rounded-md);
        font-weight: var(--font-medium);
        cursor: pointer;
        transition: background-color 0.2s;
     }
     .upload-section .btn-upload:hover {
         background-color: var(--primary-700);
     }
     .upload-section .btn-upload:disabled {
         background-color: var(--gray-400);
         cursor: not-allowed;
     }
     #upload-status {
         margin-top: 1rem;
         font-size: var(--font-size-sm);
         font-weight: var(--font-medium);
     }
     .status-success { color: var(--success-600); }
     .status-error { color: var(--danger-600); }
     
    /* Responsive styles for mobile */
    @media (max-width: 480px) {
        .payment-summary, .payment-qr-section {
            padding: 1rem;
        }
        .summary-total {
            font-size: 1rem;
        }
        #qrcode {
            width: 150px;
            height: 150px;
        }
    }
</style>

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
                </div>
                <div class="summary-item summary-total">
                    <span>Tổng thanh toán:</span>
                    <strong><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong>
                </div>
            </section>

            <?php if ($is_trial): ?>
            <!-- Cột Xác nhận Dùng thử -->
            <section class="payment-qr-section" style="text-align: center;">
                <h3>Kích hoạt gói dùng thử</h3>
                <p style="margin-bottom: 1.5rem; color: var(--gray-600);">Gói dùng thử của bạn sẽ được kích hoạt ngay lập tức.</p>
                <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=purchase&action=process_trial_activation" method="POST">
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
                    <p>Ngân hàng: <strong><?php echo defined('VIETQR_BANK_NAME') ? VIETQR_BANK_NAME : (defined('VIETQR_BANK_ID') ? VIETQR_BANK_ID : 'N/A'); ?></strong></p>
                    <p>Số tài khoản: <strong id="account-number"><?php echo defined('VIETQR_ACCOUNT_NO') ? VIETQR_ACCOUNT_NO : 'N/A'; ?></strong> <code title="Sao chép số tài khoản" data-copy-target="#account-number">Copy</code></p>
                    <p>Chủ tài khoản: <strong><?php echo defined('VIETQR_ACCOUNT_NAME') ? VIETQR_ACCOUNT_NAME : 'N/A'; ?></strong></p>
                    <p>Số tiền: <strong id="payment-amount"><?php echo number_format($verified_total_price, 0, ',', '.'); ?> đ</strong> <code title="Sao chép số tiền" data-copy-target="#payment-amount">Copy</code></p>
                    <p>Nội dung: <strong id="payment-description"><?php echo htmlspecialchars($order_description); ?></strong> <code title="Sao chép nội dung" data-copy-target="#payment-description">Copy</code></p>
                </div>

                <p class="payment-instructions">
                    <strong>Lưu ý:</strong> Vui lòng nhập <strong>chính xác</strong> nội dung chuyển khoản <code><?php echo htmlspecialchars($order_description); ?></code> để hệ thống tự động xử lý.
                    Sau khi chuyển khoản thành công, tài khoản của bạn sẽ được kích hoạt (thường trong vòng vài phút). Bạn có thể kiểm tra trạng thái trong mục "Lịch sử giao dịch".
                </p>
                 <p class="payment-instructions" style="margin-top: 0.5rem;">
                     Nếu gặp sự cố, vui lòng liên hệ bộ phận hỗ trợ.
                 </p>

                <!-- === Nút xác nhận và chuyển hướng === -->
                <div style="text-align: center; margin-top: 2rem;">
                    <button onclick="window.location.href='<?php echo $base_url; ?>/public/pages/purchase/upload_proof.php?reg_id=<?php echo htmlspecialchars($registration_id); ?>'" class="btn btn-primary" style="padding: 0.8rem 1.5rem; font-size: var(--font-size-base);">
                        Tôi đã thanh toán - Tải lên minh chứng
                    </button>
                    <p style="font-size: var(--font-size-sm); color: var(--gray-500); margin-top: 0.8rem;">
                        (Bạn có thể tải lên minh chứng sau nếu muốn)
                    </p>
                </div>
                 <!-- === Kết thúc Nút xác nhận === -->

            </section>
            <?php endif; ?>

        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Chức năng Copy (Chỉ cần nếu không phải trial) ---
    const isTrial = <?php echo json_encode($is_trial); ?>;
    if (!isTrial) {
        const copyButtons = document.querySelectorAll('.bank-details code[data-copy-target]');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetSelector = this.getAttribute('data-copy-target');
                const targetElement = document.querySelector(targetSelector);
                if (targetElement) {
                    let textToCopy = targetElement.innerText.trim();
                    if (targetSelector === '#payment-amount') {
                        textToCopy = textToCopy.replace(/đ|\.|,/g, '');
                    }

                     navigator.clipboard.writeText(textToCopy)
                        .then(() => {
                            const originalText = this.innerText;
                            this.innerText = 'Đã chép!';
                            this.style.backgroundColor = 'var(--success-100, #D1FAE5)';
                            this.style.borderColor = 'var(--success-300, #6EE7B7)';
                            setTimeout(() => {
                                this.innerText = originalText;
                                 this.style.backgroundColor = '';
                                 this.style.borderColor = '';
                            }, 1500);
                        })
                        .catch(err => {
                            console.error('Lỗi sao chép: ', err);
                            prompt('Không thể tự động sao chép. Vui lòng sao chép thủ công:', textToCopy);
                        });
                }
            });
        });
    }
});
</script>

<?php
// --- Include Footer ---
// Use correct path relative to project root
include $project_root_path . '/private/includes/footer.php';
?>
