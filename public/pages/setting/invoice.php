<?php


// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
init_session();
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
$stmt = $conn->prepare("SELECT company_name, tax_code, company_address FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Include Header ---
// Moved CSS link before header include
echo '<link rel="stylesheet" href="' . $base_url . '/public/assets/css/pages/settings/invoice.css">';
include $project_root_path . '/private/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <div class="container">
            <h2 class="profile-title">Cài đặt Thông tin Hóa Đơn</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="message success-message"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>            
            <div class="alert alert-danger" id="client-error" style="display: none;"></div>

            <div class="form-section">
                <h3>Thông tin Xuất Hóa Đơn</h3>
                <form action="<?php echo $base_url; ?>/public/handlers/action_handler.php?module=setting&action=process_invoice_update" method="post" id="invoice-form">
                    <?php echo generate_csrf_input(); ?>
                    <div class="form-group">
                        <label for="company_name">Tên công ty:</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tax_code">Mã số thuế:</label>
                        <input type="text" id="tax_code" name="tax_code" class="form-control" value="<?php echo htmlspecialchars($user['tax_code'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_address">Địa chỉ công ty:</label>
                        <input type="text" id="company_address" name="company_address" class="form-control" value="<?php echo htmlspecialchars($user['company_address'] ?? ''); ?>">
                    </div>                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                    </div>
                </form>
                <div class="invoice-note">
                    <p><strong>Lưu ý:</strong> Thông tin này sẽ được sử dụng khi xuất hóa đơn cho các giao dịch.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('invoice-form').addEventListener('submit', function(e) {
    var companyName = document.getElementById('company_name').value.trim();
    var taxCode = document.getElementById('tax_code').value.trim();
    var companyAddress = document.getElementById('company_address').value.trim();
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
        errorElement.textContent = 'Mã số thuế không hợp lệ. Định dạng: 10 chữ số (VD: 0123456789) hoặc 10-3 chữ số (VD: 0123456789-001).';
        errorElement.style.display = 'block';
    }
    
    // Company address validation
    if (companyName !== '' && companyAddress === '') {
        e.preventDefault();
        errorElement.textContent = 'Vui lòng nhập địa chỉ công ty.';
        errorElement.style.display = 'block';
    }
});
</script>

<?php include $project_root_path . '/private/includes/footer.php'; ?>
