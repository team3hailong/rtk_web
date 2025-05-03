<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Include Required Files ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php'; // Assuming Package class exists
require_once $project_root_path . '/private/classes/Location.php'; // Assuming Location class exists
require_once $project_root_path . '/private/utils/csrf_helper.php'; // Include CSRF Helper

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Get Selected Package ID (varchar) from URL ---
$selected_package_varchar_id = $_GET['package'] ?? null;

// --- Fetch Package Details from Database ---
$package_obj = new Package();
$selected_package = $package_obj->getPackageByVarcharId($selected_package_varchar_id);
$package_obj->closeConnection(); // Close connection after fetching package

// --- Validate Selected Package ---
if (!$selected_package) {
    // If package not found, redirect back to packages page
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=invalid_package');
    exit;
}

// --- Check if it's the specific trial package from URL ---
$is_trial_7d_package = ($selected_package_varchar_id === 'trial_7d');

// --- Check if it's a "Contact Us" package ---
$is_contact_package = ($selected_package['button_text'] === 'Liên hệ mua');
if ($is_contact_package) {
    // Redirect or display contact information - For now, redirect back with a message
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?info=contact_required&package_name=' . urlencode($selected_package['name']));
    exit;
    // Alternatively, you could display a contact message on this page itself
    // and disable the form.
}

$base_price = $selected_package['price']; // Get price from DB

// --- Fetch List of Provinces/Cities from Database ---
$location_obj = new Location();
$provinces = $location_obj->getAllProvinces(); // Assumes a method getAllProvinces() exists
$location_obj->closeConnection(); // Close connection after fetching locations

// --- User Info ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';

?>

<!-- CSS cho Trang Chi Tiết Mua Hàng (Keep existing styles) -->
<style>
    /* ... (Existing CSS styles remain unchanged) ... */
    .purchase-details-form {
        background-color: white;
        padding: 2rem;
        border-radius: var(--rounded-lg);
        border: 1px solid var(--gray-200);
        max-width: 600px; /* Giới hạn chiều rộng form */
        margin: 2rem auto; /* Căn giữa form */
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: var(--font-medium);
        color: var(--gray-700);
        margin-bottom: 0.5rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--rounded-md);
        font-size: var(--font-size-base);
        transition: border-color 0.2s ease;
        box-sizing: border-box; /* Ensure input fits within its container */
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary-500);
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
    }
    
    /* Style cho input[type=number] - Đã sửa lỗi cú pháp */
    input[type=number] {
        -moz-appearance: textfield; /* Firefox */
        appearance: textfield; /* Standard */
    }
    
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        appearance: none;
        margin: 0;
    }

    input[readonly] {
        background-color: var(--gray-100);
        cursor: not-allowed;
    }

    .selected-package-info {
        background-color: var(--gray-50);
        padding: 1rem 1.5rem;
        border-radius: var(--rounded-md);
        margin-bottom: 1.5rem;
        border: 1px dashed var(--gray-200);
    }
    .selected-package-info strong {
        color: var(--primary-600);
    }

    .total-price-display {
        font-size: 1.25rem;
        font-weight: var(--font-semibold);
        color: var(--gray-800);
        margin-top: 1rem;
        text-align: right;
    }
     .total-price-display span {
         color: var(--primary-600);
         font-weight: var(--font-bold);
     }

    .btn-submit {
        display: block;
        width: 100%;
        padding: 0.8rem 1.5rem;
        background-color: var(--success-500, #10B981); /* Green color, fallback hex */
        color: white;
        border: none;
        border-radius: var(--rounded-md);
        font-weight: var(--font-semibold);
        text-decoration: none;
        transition: background-color 0.2s ease;
        cursor: pointer;
        font-size: var(--font-size-base);
        text-align: center;
    }

    .btn-submit:hover {
        background-color: var(--success-600, #059669); /* Darker green on hover */
    }

     @media (max-width: 768px) {
        .content-wrapper {
            padding: 1rem !important;
        }
        .purchase-details-form {
            margin-top: 1rem;
            padding: 1.5rem;
        }
        .selected-package-info h3 {
            font-size: var(--font-size-base, 1rem);
        }
        .total-price-display {
            font-size: 1.1rem;
            text-align: center;
        }
        .total-price-display span {
            font-size: 1.3rem;
        }
    }
    
    @media (max-width: 480px) {
        .selected-package-info {
            padding: 1rem;
        }
        .btn-submit {
            padding: 0.75rem;
        }
    }
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Chi tiết mua hàng</h2>

        <!-- Thay đổi action để trỏ đến action_handler.php thay vì trực tiếp vào process_order.php -->
        <form action="/public/handlers/action_handler.php?module=purchase&action=process_order" method="POST" class="purchase-details-form" id="details-form">
            <!-- Thông tin gói đã chọn -->
            <div class="selected-package-info">
                Bạn đang chọn: <strong><?php echo htmlspecialchars($selected_package['name']); ?></strong>
                 (<?php echo htmlspecialchars($selected_package['duration_text']); ?>)
            </div>

            <!-- CSRF Protection Token -->
            <?php echo generate_csrf_input(); ?>

            <!-- Input ẩn để gửi thông tin gói -->
            <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($selected_package['id']); ?>">
            <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($selected_package['name']); ?>">
            <input type="hidden" name="package_varchar_id" value="<?php echo htmlspecialchars($selected_package_varchar_id); ?>"> <!-- Add this line -->
            <input type="hidden" name="base_price" id="base_price" value="<?php echo $base_price; ?>"> <!-- Giá gốc để JS tính toán -->
            <!-- Giá tổng, sẽ được JS cập nhật hoặc giữ nguyên nếu là trial -->
            <input type="hidden" name="total_price" id="total_price_hidden" value="<?php echo $base_price; ?>">

            <?php if (!$is_trial_7d_package): // Only show quantity input if NOT the trial_7d package ?>
            <!-- Số lượng tài khoản -->
            <div class="form-group">
                <label for="quantity">Số lượng tài khoản:</label>
                <input type="number" id="quantity" name="quantity" class="form-control"
                       min="1" required
                       placeholder="Nhập số lượng (tối thiểu 1)"
                       >
            </div>
            <?php else: // If it IS the trial_7d package, add hidden input with quantity 1 ?>
            <input type="hidden" name="quantity" value="1">
            <?php endif; ?>

            <!-- Chọn Tỉnh/Thành phố -->
            <div class="form-group">
                <label for="location_id">Tỉnh/Thành phố sử dụng:</label>
                <select id="location_id" name="location_id" class="form-control" required>
                    <option value="" disabled selected>-- Chọn Tỉnh/Thành phố --</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province['id']); ?>">
                            <?php echo htmlspecialchars($province['province']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!$is_trial_7d_package): // Only show total price display if NOT the trial_7d package ?>
             <!-- Hiển thị tổng tiền (cập nhật bằng JS) -->
            <div class="total-price-display">
                Tổng cộng: <span id="total-price-view"><?php echo number_format($base_price, 0, ',', '.'); ?>đ</span>
            </div>
            <?php endif; ?>

            <!-- Nút chuyển đến thanh toán -->
            <div class="form-group" style="margin-top: 2rem; margin-bottom: 0;">
                <button type="submit" class="btn-submit">Tiếp tục đến Thanh toán</button>
            </div>
        </form>

    </main>
</div>

<!-- JavaScript để cập nhật giá tiền (Keep existing script) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity'); // Might be null if trial
    const basePrice = parseFloat(document.getElementById('base_price').value);
    const totalPriceView = document.getElementById('total-price-view'); // Might be null if trial
    const totalPriceHidden = document.getElementById('total_price_hidden');
    const isTrial = <?php echo json_encode($is_trial_7d_package); ?>; // Use the correct variable

    function updateTotalPrice() {
        let quantity = 1; // Default to 1

        // Only calculate if quantity input exists (i.e., not trial)
        if (quantityInput) {
            quantity = parseInt(quantityInput.value);
            // Ensure quantity is valid (at least 1) for non-trial
            if (isNaN(quantity) || quantity < 1) {
                // For calculation, use 1 if invalid or empty
                quantity = 1;
            }
        }

        const total = basePrice * quantity;

        // Update display only if it exists (i.e., not trial)
        if (totalPriceView && quantityInput) {
            if (isNaN(parseInt(quantityInput.value))) {
                 totalPriceView.textContent = '--'; // Show placeholder if input is empty/invalid
            } else {
                totalPriceView.textContent = total.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
            }
        }

        // Always update the hidden total price field
        totalPriceHidden.value = total;
    }

    // Gọi hàm lần đầu khi tải trang để xử lý giá trị ban đầu
    updateTotalPrice();

    // Thêm sự kiện lắng nghe chỉ khi ô nhập tồn tại
    if (quantityInput) {
        quantityInput.addEventListener('input', updateTotalPrice);
    }

    // Ngăn chặn submit nếu chưa chọn tỉnh thành
    const form = document.getElementById('details-form');
    const locationSelect = document.getElementById('location_id');
    form.addEventListener('submit', function(event) {
        if (!locationSelect.value) {
            alert('Vui lòng chọn Tỉnh/Thành phố sử dụng.');
            event.preventDefault(); // Ngăn form gửi đi
            locationSelect.focus();
            return; // Dừng thực thi thêm
        }

        // Validate quantity before submit only if input exists (not trial)
        if (quantityInput) {
            const currentQuantity = parseInt(quantityInput.value);
            if (isNaN(currentQuantity) || currentQuantity < 1) {
                alert('Vui lòng nhập số lượng tài khoản hợp lệ (tối thiểu là 1).');
                event.preventDefault();
                quantityInput.focus();
                return;
            }
        }

        // Cập nhật giá lần cuối trước khi submit
        updateTotalPrice();
    });
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>