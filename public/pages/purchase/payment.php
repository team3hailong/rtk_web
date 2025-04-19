<?php
session_start();

// --- Project Root Path for Includes ---
// Assumes this file is in /public/pages/purchase/
$project_root_path = dirname(dirname(dirname(dirname(__FILE__)))); // Adjust if structure differs

// --- Base URL Configuration ---
// A more robust way might be needed depending on server setup
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
// Basic assumption: project is directly under web root or in a known subdir
// If project is in 'test_web-main', adjust accordingly.
// Example: If URL is http://localhost/test_web-main/public/...
$script_dir = dirname($_SERVER['PHP_SELF']); // e.g., /test_web-main/public/pages/purchase
// Find the base path relative to the domain
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');


// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/classes/Location.php';
require_once $project_root_path . '/private/utils/functions.php'; // For CRC function if moved there

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
$verified_total_price = $_SESSION['pending_total_price']; // Get total price from session

// --- Database Operations ---
$db = null; // Initialize variables
$package_obj = null;
$location_obj = null;
$registration_details = null;
$package_details = null;
$location_details = null;

try {
    $db = new Database();
    $conn = $db->connect();
    $package_obj = new Package($conn); // Pass connection
    $location_obj = new Location($conn); // Pass connection

    // --- Fetch Registration Details ---
    $stmt = $conn->prepare("SELECT package_id, location_id, num_account, total_price FROM registration WHERE id = :id AND user_id = :user_id AND status = 'pending'");
    $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $registration_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registration_details) {
        // Registration not found, invalid, or already processed
        unset($_SESSION['pending_registration_id'], $_SESSION['pending_total_price']);
        error_log("Pending registration ID $registration_id not found for user $user_id or status not pending.");
        header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=invalid_order_state'); // Adjusted path
        exit;
    }

    // --- Verify Session Price with DB Price (Optional but recommended) ---
    if (abs($registration_details['total_price'] - $verified_total_price) > 0.01) {
        error_log("Price mismatch between session ({$verified_total_price}) and DB ({$registration_details['total_price']}) for registration ID $registration_id.");
        // Use the price from the database as the source of truth
        $verified_total_price = $registration_details['total_price'];
        $_SESSION['pending_total_price'] = $verified_total_price; // Update session if needed
    }

    // --- Fetch Package and Location Details ---
    $package_details = $package_obj->getPackageById($registration_details['package_id']);
    $location_details = $location_obj->getLocationById($registration_details['location_id']); // Assumes getLocationById exists

    // *** ADDED CHECK ***
    if (!$package_details || !$location_details) {
        error_log("Could not fetch package or location details for registration ID $registration_id. Package found: " . ($package_details ? 'Yes' : 'No') . ", Location found: " . ($location_details ? 'Yes' : 'No'));
        header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=data_fetch_error'); // Adjusted path
        exit;
    }

    $package_name = $package_details['name'];
    $quantity = $registration_details['num_account'];
    $province = $location_details['province']; // Assuming the column name is 'province'

} catch (PDOException $e) {
    error_log("Database error on payment page: " . $e->getMessage());
    // Show a generic error page or redirect
    die("Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau."); // Simple error message
} catch (Exception $e) {
    error_log("General error on payment page: " . $e->getMessage());
    die("Đã xảy ra lỗi không mong muốn. Vui lòng thử lại sau."); // Simple error message
} finally {
    // Close connections if needed (depends on class implementation)
    if ($package_obj) $package_obj->closeConnection(); // Assuming closeConnection exists
    if ($location_obj) $location_obj->closeConnection(); // Assuming closeConnection exists
    if ($db) $db->close();
}


// --- Thông tin Ngân hàng và VietQR ---
// !!! THAY THẾ BẰNG THÔNG TIN THẬT CỦA BẠN hoặc load từ config !!!
define('BANK_ID', '970418');      // Ví dụ: VietinBank BIN
define('ACCOUNT_NO', '112233445566'); // Số tài khoản thật
define('ACCOUNT_NAME', 'NGUYEN VAN A'); // Tên chủ tài khoản thật
define('BANK_NAME', 'VietinBank'); // Tên ngân hàng để hiển thị
// Template VietQR chuẩn
define('QR_TEMPLATE', '00020101021238570010A00000072701270006%s0115%s0208QRIBFTTA530370454%.0f5802VN62%d%s6304');

// Tạo nội dung chuyển khoản (Ngắn gọn, dễ nhập, chứa thông tin định danh)
// Sử dụng Registration ID làm mã định danh duy nhất
$order_description = "REG{$registration_id} MUA GOI"; // Keep it short
// Xử lý cho vào QR Payload (xóa dấu, viết hoa, bỏ khoảng trắng, giới hạn độ dài nếu cần)
$qr_description_raw = preg_replace('/[^A-Z0-9]/', '', strtoupper(str_replace(' ', '', $order_description)));
// Giới hạn độ dài mô tả cho QR nếu cần (VietQR có giới hạn tổng payload)
$qr_description = substr($qr_description_raw, 0, 50); // Ví dụ giới hạn 50 ký tự
// Format tham số 08 cho VietQR (mô tả)
$qr_description_param = '08' . str_pad(strlen($qr_description), 2, '0', STR_PAD_LEFT) . $qr_description;

// Format tham số 62 cho VietQR (tên chủ tài khoản)
$account_name_param = '00' . str_pad(strlen(ACCOUNT_NAME), 2, '0', STR_PAD_LEFT) . ACCOUNT_NAME;


// Tạo payload VietQR (Dùng %.0f cho số tiền để đảm bảo là số nguyên)
$qr_payload = sprintf(
    QR_TEMPLATE,
    BANK_ID,                     // %s: Bank BIN
    ACCOUNT_NO,                  // %s: Account Number
    $verified_total_price,       // %.0f: Amount (đảm bảo là số nguyên dạng float)
    strlen($account_name_param), // %d: Length of Account Name Parameter (bao gồm cả 00xx)
    str_replace(' ','%20', $account_name_param), // %s: Account Name Param URL Encoded (00xx...)
    $qr_description_param        // %s: Description parameter (08xx...)
);

// --- Hàm tính CRC16 cho VietQR (Có thể đặt trong functions.php) ---
if (!function_exists('crc16')) {
    function crc16($data) {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : $crc << 1;
            }
        }
        return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
    }
}

$crc_value = crc16($qr_payload); // Tính CRC
$final_qr_payload = $qr_payload . $crc_value; // Payload hoàn chỉnh cho QR Code

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
// Use correct path relative to project root
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS cho Trang Thanh Toán -->
<style>
    /* ... (Existing CSS styles from prompt remain unchanged) ... */
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
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; // Adjusted path ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-6">Thanh toán đơn hàng</h2>

        <div class="payment-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">

            <!-- Cột Tóm tắt đơn hàng -->
            <section class="payment-summary">
                <h3>Thông tin đơn hàng</h3>
                 <div class="summary-item" style="font-size: var(--font-size-sm);">
                    <span>Mã đơn hàng:</span>
                    <strong><?php echo htmlspecialchars($registration_id); ?></strong>
                 </div>
                <div class="summary-item">
                    <span>Gói dịch vụ:</span>
                    <strong><?php echo htmlspecialchars($package_name); ?></strong>
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

            <!-- Cột Mã QR và Hướng dẫn -->
            <section class="payment-qr-section">
                <h3>Quét mã để thanh toán</h3>
                <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: 1rem;">Sử dụng ứng dụng ngân hàng hoặc ví điện tử hỗ trợ VietQR.</p>
                <!-- Div để hiển thị QR Code -->
                <div id="qrcode">
                     <p style="font-size: var(--font-size-sm); color: var(--gray-500);">Đang tạo mã QR...</p>
                </div>

                <div class="bank-details">
                    <p><strong>Thông tin chuyển khoản thủ công:</strong></p>
                    <p>Ngân hàng: <strong><?php echo defined('BANK_NAME') ? BANK_NAME : BANK_ID; ?></strong></p>
                    <p>Số tài khoản: <strong id="account-number"><?php echo ACCOUNT_NO; ?></strong> <code title="Sao chép số tài khoản" data-copy-target="#account-number">Copy</code></p>
                    <p>Chủ tài khoản: <strong><?php echo ACCOUNT_NAME; ?></strong></p>
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
            </section>

        </div>

    </main>
</div>

<!-- Nhúng thư viện qrcode.min.js (Tải về hoặc dùng CDN) -->
<!-- Ví dụ dùng CDN: -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecJvqEspN2r0ZWCkUranLMijLqtDbBeel7NDGceUPGURAuBrwnqJBZAumCiHgNZeVScBMA/5PkqG8UAg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- Hoặc tải về và đặt link đúng vào /public/assets/js/ chẳng hạn: -->
<!-- <script src="<?php echo $base_url; ?>/public/assets/js/qrcode.min.js"></script> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Tạo QR Code ---
    const qrCodeElement = document.getElementById('qrcode');
    // Use PHP to safely embed the final payload into JavaScript
    const qrData = <?php echo json_encode($final_qr_payload); ?>;

    if (qrCodeElement && qrData && typeof QRCode !== 'undefined') {
        try {
            qrCodeElement.innerHTML = ''; // Xóa placeholder
            new QRCode(qrCodeElement, {
                text: qrData,
                width: 250, // Nên đồng bộ với CSS
                height: 250,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M // Mức sửa lỗi M (Medium)
            });
            // console.log("QR Code Generated for payload:", qrData);
        } catch (error) {
            console.error("Error generating QR Code:", error);
            qrCodeElement.innerHTML = '<p style="color: red; font-size: var(--font-size-sm);">Lỗi tạo mã QR.</p>';
        }
    } else {
         console.error("QR Code element, data, or QRCode library not found.");
         if(qrCodeElement) qrCodeElement.innerHTML = '<p style="color: red; font-size: var(--font-size-sm);">Không thể tải thư viện QR.</p>';
    }

    // --- Chức năng Copy ---
    const copyButtons = document.querySelectorAll('.bank-details code[data-copy-target]');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetSelector = this.getAttribute('data-copy-target');
            const targetElement = document.querySelector(targetSelector);
            if (targetElement) {
                // Get text, remove currency symbols, dots, commas for amount
                let textToCopy = targetElement.innerText.trim();
                if (targetSelector === '#payment-amount') {
                    textToCopy = textToCopy.replace(/đ|\.|,/g, '');
                }

                 navigator.clipboard.writeText(textToCopy)
                    .then(() => {
                        // Tạm thời thay đổi text của nút copy
                        const originalText = this.innerText;
                        this.innerText = 'Đã chép!';
                        this.style.backgroundColor = 'var(--success-100, #D1FAE5)'; // Use success color
                        this.style.borderColor = 'var(--success-300, #6EE7B7)';
                        setTimeout(() => {
                            this.innerText = originalText;
                             this.style.backgroundColor = ''; // Reset background
                             this.style.borderColor = ''; // Reset border
                        }, 1500); // Hiện trong 1.5 giây
                    })
                    .catch(err => {
                        console.error('Lỗi sao chép: ', err);
                        // Fallback or alert
                        prompt('Không thể tự động sao chép. Vui lòng sao chép thủ công:', textToCopy);
                    });
            }
        });
    });

    // --- (Tùy chọn) Logic kiểm tra trạng thái thanh toán bằng AJAX ---
    // This requires a backend endpoint (e.g., /api/check-payment.php)
    // that checks the status of the registration_id in the database.
    /*
    const registrationId = <?php echo json_encode($registration_id); ?>;
    const successUrl = <?php echo json_encode($base_url . '/public/pages/purchase/success.php?order=' . $registration_id); ?>; // Example success page

    const checkPaymentStatus = () => {
        fetch(`<?php echo $base_url; ?>/api/check-payment.php?registration_id=${registrationId}`) // Replace with your actual API endpoint
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
             })
            .then(data => {
                if (data.status === 'completed' || data.status === 'paid') { // Adjust status names based on your system
                    console.log('Payment confirmed!');
                    // Clear session variables if desired upon confirmation
                    // fetch('<?php echo $base_url; ?>/api/clear-pending-session.php'); // Optional: Endpoint to clear session vars
                    window.location.href = successUrl; // Redirect to success page
                } else {
                    console.log('Payment status:', data.status); // Keep checking
                }
            })
            .catch(error => console.error('Error checking payment status:', error));
    };

    // Start checking after 15 seconds, then check every 20 seconds
    // Adjust intervals as needed - avoid overwhelming the server
    const paymentCheckInterval = setInterval(checkPaymentStatus, 20000); // Check every 20s
    setTimeout(() => {
        checkPaymentStatus(); // Initial check after 15s
    }, 15000);

    // Optional: Stop checking after some time (e.g., 10 minutes)
    setTimeout(() => {
        clearInterval(paymentCheckInterval);
        console.log("Stopped automatic payment check.");
    }, 10 * 60 * 1000); // Stop after 10 minutes
    */

});
</script>

<?php
// --- Include Footer ---
// Use correct path relative to project root
include $project_root_path . '/private/includes/footer.php';
?>
