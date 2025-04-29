<?php
session_start();

// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}

// --- Include Database and other required files ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/utils/csrf_helper.php';

// --- Get User Data ---
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT company_name, tax_code FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hóa đơn</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/pages/settings/invoice.css">
    <style>
.btn:focus {
    outline: 0;
    box-shadow: 0 0 0 0.25rem #4caf50; /* Focus glow consistent with inputs */
}

/* Alert Messages */
.alert {
    padding: 1rem 1rem; /* Bootstrap standard padding */
    margin-bottom: 1.5rem; /* Consistent spacing */
    border: 1px solid transparent;
    border-radius: 6px; /* Match other elements */
    font-size: 0.95rem;
}

.alert-success {
    color: #0f5132;
    background-color: #d1e7dd;
    border-color: #badbcc;
}

.alert-danger {
    color: #842029;
    background-color: #f8d7da;
    border-color: #f5c2c7;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem; /* Reduce padding on smaller screens */
    }

    .invoice-form {
        padding: 1.5rem; /* Reduce form padding */
    }

    .content-header h1 {
        font-size: 1.5rem; /* Slightly smaller heading */
    }
}

/* Ensure no accidental style leakage affects sidebar */
/* Sidebar styles should be defined separately if needed */
    </style>
</head>
<body>
<div class="main-container">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="content-header">
            <h1>THÔNG TIN HÓA ĐƠN</h1>
        </div>

        <div class="invoice-form">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Cập nhật thông tin thành công!</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger">Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.</div>
            <?php endif; ?>

            <div class="alert alert-danger" id="client-error" style="display: none;"></div>

            <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=setting&action=process_invoice_info" method="post" id="invoice-form">
                <?php echo generate_csrf_input(); ?>
                <div class="form-group">
                    <label for="company_name">Tên công ty</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="tax_code">Mã số thuế</label>
                    <input type="text" id="tax_code" name="tax_code" class="form-control" value="<?php echo htmlspecialchars($user['tax_code'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                </div>
            </form>
            <div class="invoice-note">
                <p><strong>Lưu ý:</strong> Thông tin này sẽ được sử dụng khi xuất hóa đơn cho các giao dịch.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('invoice-form').addEventListener('submit', function(e) {
    var companyName = document.getElementById('company_name').value.trim();
    var taxCode = document.getElementById('tax_code').value.trim();
    var errorElement = document.getElementById('client-error');
    
    // Reset error message
    errorElement.style.display = 'none';
    errorElement.textContent = '';
    
    // Basic validation
    if (companyName === '' && taxCode !== '') {
        e.preventDefault();
        errorElement.textContent = 'Vui lòng nhập tên công ty nếu bạn cung cấp mã số thuế.';
        errorElement.style.display = 'block';
    }
    
    // Tax code validation (simple pattern for Vietnam tax code)
    if (taxCode !== '' && !/^\d{10}(-\d{3})?$/.test(taxCode)) {
        e.preventDefault();
        errorElement.textContent = 'Mã số thuế không hợp lệ. Định dạng: 10 chữ số hoặc 10 chữ số-3 chữ số.';
        errorElement.style.display = 'block';
    }
});
</script>

<?php include $project_root_path . '/private/includes/footer.php'; ?>
</body>
</html>
