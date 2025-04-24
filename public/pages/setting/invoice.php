<?php
session_start();
include '../../../private/config/database.php';
include '../../../private/includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kết nối database và lấy thông tin user
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM user WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    throw new Exception("Không tìm thấy người dùng");
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Hóa Đơn</title>
    <link rel="stylesheet" href="../../assets/css/pages/settings/invoice.css">
</head>
<body>
<div class="main-container">
    <?php include '../../../private/includes/sidebar.php'; ?>
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

            <form action="../../../private/action/setting/update_invoice.php" method="POST">
                <div class="form-group">
                    <label for="username">Tên người dùng:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="is_company" name="is_company" value="1" <?php echo ($user['is_company'] ?? 0) ? 'checked' : ''; ?>>
                        Đây là tài khoản doanh nghiệp
                    </label>
                </div>
                <div id="company-fields">
                    <div class="form-group">
                        <label for="company_name">Tên công ty:</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tax_code">Mã số thuế:</label>
                        <input type="text" id="tax_code" name="tax_code" value="<?php echo htmlspecialchars($user['tax_code'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" class="btn">CẬP NHẬT THÔNG TIN</button>
            </form>
        </div>
    </div>
</div>

<script>
    const isCompanyCheckbox = document.getElementById('is_company');
    const companyFields = document.getElementById('company-fields');
    const form = document.querySelector('form');
    const clientError = document.getElementById('client-error');

    function toggleCompanyFields() {
        companyFields.style.display = isCompanyCheckbox.checked ? 'block' : 'none';
    }

    isCompanyCheckbox.addEventListener('change', toggleCompanyFields);
    toggleCompanyFields();

    form.addEventListener('submit', function (e) {
        if (isCompanyCheckbox.checked) {
            const companyName = document.getElementById('company_name').value.trim();
            const taxCode = document.getElementById('tax_code').value.trim();

            if (companyName === '' || taxCode === '') {
                e.preventDefault();
                clientError.textContent = 'Vui lòng nhập đầy đủ TÊN CÔNG TY và MÃ SỐ THUẾ.';
                clientError.style.display = 'block';
            } else {
                clientError.style.display = 'none';
            }
        }
    });
</script>
