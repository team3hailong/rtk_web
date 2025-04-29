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
    <style>
/* ======================================== */
/* == CORE LAYOUT (DO NOT CHANGE HEAVILY) == */
/* ======================================== */
.main-container {
    display: flex;
    min-height: 100vh;
    /* Keep existing sidebar interaction CSS if any */
}

/* Assuming sidebar styles are in sidebar.php or its included CSS */
/* No changes needed here for the sidebar itself */

/* ======================================== */
/* == CONTENT AREA STYLES (Refined)     == */
/* ======================================== */
.content-wrapper {
    flex: 1; /* Takes remaining space */
    padding: 2rem; /* Increased padding */
    background-color: #f8f9fa; /* Lighter gray background */
    overflow-y: auto; /* Add scroll if content overflows */
}

/* Content Header */
.content-header {
    margin-bottom: 2rem; /* Increased spacing */
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6; /* Lighter border */
}

.content-header h1 {
    color: #343a40; /* Darker gray */
    font-size: 1.75rem; /* Slightly larger */
    font-weight: 600;
    margin: 0; /* Remove default margin */
}

/* Invoice Form Container */
.invoice-form {
    background: #ffffff; /* Pure white background */
    padding: 2rem; /* Consistent padding */
    border-radius: 12px; /* Softer corners */
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08); /* Softer shadow */
    max-width: 800px;
    margin: 0 auto; /* Center the form */
}

/* Form Group Styling */
.form-group {
    margin-bottom: 1.5rem; /* Consistent spacing */
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem; /* Spacing between label and input */
    font-weight: 600;
    color: #495057; /* Slightly muted text color */
    font-size: 0.9rem; /* Slightly smaller label */
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"] {
    width: 100%;
    padding: 12px 15px; /* Comfortable padding */
    border: 1px solid #ced4da; /* Standard border color */
    border-radius: 6px; /* Slightly rounded corners */
    font-size: 1rem; /* Standard font size */
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    box-sizing: border-box; /* Include padding and border in element's total width/height */
}

.form-group input:focus {
    border-color: #4caf50; /* Blue focus border */
    outline: 0;
    box-shadow: 0 0 0 0.25rem #4caf50(13, 110, 253, 0.25); /* Blue focus glow */
}

/* Checkbox Styles */
.checkbox-container {
    margin: 1.5rem 0;
    display: flex; /* Align checkbox and label easily */
    align-items: center;
}

.checkbox-container label {
    display: inline-flex; /* Use flex for label content */
    align-items: center;
    cursor: pointer;
    color: #495057;
    margin-bottom: 0; /* Reset margin */
    font-weight: normal; /* Regular weight for checkbox label */
    font-size: 1rem; /* Match input font size */
}

.checkbox-container input[type="checkbox"] {
    margin-right: 0.75rem; /* Space between checkbox and text */
    width: 1.1em; /* Slightly larger checkbox */
    height: 1.1em;
    cursor: pointer;
    vertical-align: middle; /* Align better with text */
    /* Basic custom styling (optional, can be more complex) */
    accent-color: #4caf50; /* Modern way to color checkbox */
}

/* Company Fields Section */
#company-fields {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef; /* Lighter separator line */
    /* display: none; // Managed by JS */
}

/* Button Styles */
.btn {
    background-color: #4caf50; /* Bootstrap primary blue */
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px; /* Match input radius */
    cursor: pointer;
    font-size: 1rem; /* Standard size */
    font-weight: 600;
    text-align: center;
    transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    width: 100%;
    margin-top: 1rem; /* Space above button */
    display: inline-block; /* Ensure it behaves like a block but respects padding */
    line-height: 1.5; /* Standard line height */
}

.btn:hover {
    background-color:#388e3c; /* Darker blue on hover */
    box-shadow: 0 2px 5px #4caf50(0, 0, 0, 0.1); /* Subtle hover shadow */
}

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

            <form action="<?php echo $base_url ?? ''; ?>/public/handlers/action_handler.php?module=setting&action=process_invoice_update" method="POST">
                <?php
                // Thêm CSRF token
                require_once $project_root_path . '/private/utils/csrf_helper.php';
                echo generate_csrf_input();
                ?>
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
