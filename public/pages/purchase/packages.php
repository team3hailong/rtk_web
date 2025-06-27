<?php


// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_path = PUBLIC_URL; // Use PUBLIC_URL constant for links
$project_root_path = PROJECT_ROOT_PATH;

// --- Include required classes ---
require_once $project_root_path . '/private/classes/purchase/PurchaseService.php';
require_once $project_root_path . '/private/classes/DeviceTracker.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . '/pages/auth/login');
    exit;
}

// --- User Info (Example) ---
$user_id = $_SESSION['user_id']; // Get user ID
$user_username = $_SESSION['username'] ?? 'Người dùng';

// Initialize PurchaseService
$service = new PurchaseService();
$user_has_registration = $service->userHasRegistration($user_id);
$survey_account_count = $service->getUserSurveyAccountCount($user_id);

// Kiểm tra thiết bị và IP đã sử dụng gói trial hay chưa và trạng thái còn lại
$show_trial_package = true;
$trial_button_disabled = false;
$trial_days_remaining = 0;
$trial_expire_date = null;

try {
    // Lấy thông tin vân tay thiết bị và IP từ session hoặc từ request
    $device_fingerprint = $_SESSION['device_fingerprint'] ?? $_POST['device_fingerprint'] ?? '';
    $ip_address = $_SESSION['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!empty($device_fingerprint) || !empty($ip_address)) {
        // Kết nối database để kiểm tra thiết bị
        $dsn = "mysql:host=".DB_SERVER.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Khởi tạo DeviceTracker và kiểm tra trạng thái trial
        $deviceTracker = new DeviceTracker($pdo);
        
        // Luôn hiển thị gói trial nhưng disable nút nếu đã sử dụng
        $trialStatus = $deviceTracker->getTrialStatus($device_fingerprint, $ip_address);
        
        if ($trialStatus['trial_used']) {
            $trial_button_disabled = true;
            $trial_days_remaining = $trialStatus['days_remaining'];
            $trial_expire_date = $trialStatus['trial_expire_date'];
            
            // Lưu vào session để có sẵn cho các trang khác
            $_SESSION['trial_status'] = $trialStatus;
        }
    }
} catch (Exception $e) {
    error_log("Error checking trial status: " . $e->getMessage());
}

// Lấy tất cả các gói dịch vụ
$all_packages = $service->getAllPackages();

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/purchase/packages.css">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Mua Gói Tài Khoản</h2>
        <p class="text-gray-600 mb-6">Chọn gói phù hợp với nhu cầu sử dụng của bạn.</p>

        <!-- Grid chứa các gói (Tạo bằng vòng lặp PHP) -->
        <div class="packages-grid">

            <?php if (empty($all_packages)): ?>
                <p class="text-center text-gray-500 col-span-full">Hiện tại không có gói dịch vụ nào.</p>
            <?php else: ?>
                <?php foreach ($all_packages as $package): ?>                    <?php                        // --- LOGIC GÓI DÙNG THỬ ---
                        // Luôn hiển thị gói dùng thử 7 ngày (đã bỏ điều kiện ẩn)
                        // --- KẾT THÚC LOGIC ---

                        // Decode features JSON
                        $features = json_decode($package['features_json'], true); // true for associative array
                        if ($features === null) {
                            $features = []; // Handle potential JSON decode error
                        }

                        // Xác định class cho card (thêm 'recommended' nếu cần)
                        $card_classes = 'package-card';
                        if ($package['is_recommended']) {
                            $card_classes .= ' recommended';
                        }
                        // Tạo URL cho trang chi tiết - Use base_path
                        $details_url = $base_path . '/pages/purchase/details.php?package=' . htmlspecialchars($package['package_id']);
                        // Xác định class cho nút bấm (thêm 'contact' nếu là nút liên hệ)
                        $button_classes = 'btn-select-package';
                        $is_contact_button = ($package['button_text'] === 'Liên hệ mua');
                        if ($is_contact_button) {
                             $button_classes .= ' contact';
                        }
                    ?>
                    <div class="<?php echo $card_classes; ?>">
                        <?php if ($package['is_recommended']): ?>
                            <div class="recommended-badge">Phổ biến</div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($package['name']); ?></h3>

                        <div class="package-price">
                            <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
                            <span class="duration"><?php echo htmlspecialchars($package['duration_text']); ?></span>
                        </div>

                        <!-- Hiển thị text tiết kiệm nếu có -->
                        <span class="package-savings">
                            <?php echo isset($package['savings_text']) ? htmlspecialchars($package['savings_text']) : '&nbsp;'; ?>
                        </span>                        <ul class="package-features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="fas <?php echo htmlspecialchars($feature['icon']); ?>" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($feature['text']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Thêm div flex-spacer để đẩy các phần tử xuống cuối -->
                        <div class="flex-spacer"></div>
                        
                        <!-- Thêm lựa chọn Purchase Type (chỉ hiển thị khi không phải gói dùng thử) -->
                        <?php if ($package['package_id'] !== 'trial_7d'): ?>
                        <div class="purchase-type-selector">
                            <label><input type="radio" name="purchase_type_<?php echo htmlspecialchars($package['package_id']); ?>" value="individual" checked> Cá nhân</label>
                            <label><input type="radio" name="purchase_type_<?php echo htmlspecialchars($package['package_id']); ?>" value="company"> Công ty (+10% VAT)</label>
                        </div>
                        <?php endif; ?>

                        <!-- Nút bấm với link chính xác -->
                        <?php if ($package['package_id'] === 'trial_7d' && $trial_button_disabled): ?>
                        <button class="<?php echo $button_classes; ?> disabled" disabled>
                            <?php echo htmlspecialchars($package['button_text']); ?> 
                            <span class="countdown">(<?php echo $trial_days_remaining; ?> ngày nữa)</span>
                        </button>
                        <?php else: ?>
                        <a href="#" data-package-id="<?php echo htmlspecialchars($package['package_id']); ?>" class="<?php echo $button_classes; ?> select-package-button">
                            <?php echo htmlspecialchars($package['button_text']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div> <!-- /.packages-grid -->

    </main>
</div>

<!-- Page-specific JS -->
<script src="<?php echo $base_path; ?>/assets/js/pages/purchase/packages.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const packageButtons = document.querySelectorAll('.select-package-button');
    packageButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const packageId = this.dataset.packageId;
            
            // Xác định URL chi tiết dựa vào loại gói
            let detailsUrl = '<?php echo $base_path; ?>/pages/purchase/details.php?package=' + packageId;
            
            // Chỉ thêm purchase_type nếu không phải gói dùng thử
            if (packageId !== 'trial_7d') {
                const purchaseTypeInput = document.querySelector('input[name="purchase_type_' + packageId + '"]:checked');
                const purchaseType = purchaseTypeInput ? purchaseTypeInput.value : 'individual';
                detailsUrl += '&purchase_type=' + purchaseType;
            }
            
            // For contact button, redirect to contact page or handle differently
            if (this.classList.contains('contact')) {
                // Example: Redirect to a contact page or open a modal
                // For now, let's assume it still goes to details but could be handled differently
                // detailsUrl = '<?php echo $base_path; ?>/pages/support/contact.php?package=' + packageId;
                // For the purpose of this task, we'll let it proceed to details page
                // to show how purchase_type is passed.
            }
            
            window.location.href = detailsUrl;
        });
    });
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>

<!-- Trial Status Notification Modal -->
<div id="trialStatusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Thông báo về gói dùng thử</h3>
        <p>Bạn đã kích hoạt gói dùng thử 7 ngày. Bạn sẽ phải đợi <strong><span id="trialCooldownDays">90</span> ngày</strong> trước khi có thể đăng ký gói dùng thử khác.</p>
        <p>Gói dùng thử sẽ được mở lại vào: <strong><span id="trialExpireDate"></span></strong></p>
        <button id="closeTrialModal" class="btn btn-primary">Đã hiểu</button>
    </div>
</div>

<!-- Thêm CSS cho modal -->
<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    padding: 2rem;
    border-radius: var(--rounded-lg);
    max-width: 500px;
    width: 90%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.close-modal {
    float: right;
    font-size: 1.5rem;
    cursor: pointer;
}

.btn-primary {
    margin-top: 1rem;
    display: block;
    width: 100%;
    padding: 0.75rem;
    background-color: var(--primary-600);
    color: white;
    border: none;
    border-radius: var(--rounded-md);
    cursor: pointer;
    font-weight: var(--font-semibold);
}

.btn-primary:hover {
    background-color: var(--primary-700);
}
</style>

<!-- Script để hiển thị modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['trial_status']) && $_SESSION['trial_status']['trial_used']): ?>
    // Kiểm tra nếu người dùng vừa kích hoạt trial hoặc có thông tin trial đã sử dụng
    const modal = document.getElementById('trialStatusModal');
    const closeBtn = document.querySelector('.close-modal');
    const closeModalBtn = document.getElementById('closeTrialModal');
    const daysElement = document.getElementById('trialCooldownDays');
    const expireDateElement = document.getElementById('trialExpireDate');
    
    // Hiển thị số ngày và ngày hết hạn
    daysElement.textContent = '<?php echo $_SESSION['trial_status']['days_remaining']; ?>';
    
    // Format ngày hết hạn
    const expireDate = new Date('<?php echo $_SESSION['trial_status']['trial_expire_date']; ?>');
    const formattedDate = expireDate.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    expireDateElement.textContent = formattedDate;
    
    // Hiển thị modal nếu vừa kích hoạt
    <?php if (isset($_SESSION['just_activated_trial']) && $_SESSION['just_activated_trial']): ?>
    modal.style.display = 'flex';
    delete <?php unset($_SESSION['just_activated_trial']); ?>;
    <?php endif; ?>
    
    // Xử lý đóng modal
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }
    
    closeModalBtn.onclick = function() {
        modal.style.display = 'none';
    }
    
    // Đóng khi click bên ngoài
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    <?php endif; ?>
});
</script>