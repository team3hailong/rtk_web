<?php
session_start();
$project_root_path = dirname(dirname(dirname(__DIR__)));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/classes/RtkAccount.php';

$base_url = BASE_URL;

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Nhận danh sách tài khoản được chọn từ POST
$selected_accounts = $_POST['selected_accounts'] ?? [];
if (empty($selected_accounts) || !is_array($selected_accounts)) {
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=no_account_selected');
    exit;
}

// Lấy thông tin tài khoản từ DB
$db = new Database();
$rtkAccount = new RtkAccount($db);
$accounts = $rtkAccount->getAccountsByIdsForRenewal($user_id, $selected_accounts); // Hàm này bạn cần thêm vào class RtkAccount

// Lấy danh sách gói gia hạn
$packageObj = new Package();
$packages = $packageObj->getAllPackagesForRenewal(); // Hàm này bạn cần thêm vào class Package

$db->close();
$packageObj->closeConnection();

if (empty($accounts)) {
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=invalid_account');
    exit;
}
if (empty($packages)) {
    echo '<div>Không có gói gia hạn khả dụng.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gia hạn tài khoản RTK</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/base.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .package-selection {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .package-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .package-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .package-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .package-card.selected {
            border-color: #4CAF50;
            background-color: #f0fff0;
        }
        .package-name {
            font-weight: bold;
            font-size: 16px;
        }
        .package-price {
            margin-top: 10px;
            font-size: 16px;
        }
        .package-duration {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .accounts-table th, .accounts-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .accounts-table th {
            background-color: #f2f2f2;
        }
        .total-section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            text-align: right;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Gia hạn tài khoản RTK</h2>
    
    <form method="post" action="<?php echo $base_url; ?>/private/action/purchase/process_renewal.php" id="renewal-form">
        <!-- Hiển thị giải thích về việc chỉ chọn 1 gói -->
        <div class="package-selection">
            <div class="package-title">Chọn một gói gia hạn cho tất cả tài khoản (<?php echo count($accounts); ?> tài khoản)</div>
            
            <div class="package-list">
                <?php foreach ($packages as $pkg): ?>
                <div class="package-card" data-package-id="<?php echo $pkg['id']; ?>" data-package-price="<?php echo $pkg['price']; ?>">
                    <div class="package-name"><?php echo htmlspecialchars($pkg['name']); ?></div>
                    <div class="package-price"><?php echo number_format($pkg['price']); ?> đ</div>
                    <div class="package-duration"><?php echo htmlspecialchars($pkg['duration_text']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Bảng tài khoản -->
        <h3>Danh sách tài khoản được chọn</h3>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>Tài khoản</th>
                    <th>Thời hạn hiện tại</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $acc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($acc['username_acc']); ?></td>
                    <td><?php echo htmlspecialchars($acc['end_time']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Hiển thị tính toán tổng tiền -->
        <div class="total-section">
            <div class="total-row">
                <div>Gói gia hạn:</div>
                <div id="selected-package-name">Chưa chọn gói</div>
            </div>
            <div class="total-row">
                <div>Số lượng tài khoản:</div>
                <div><?php echo count($accounts); ?></div>
            </div>
            <div class="total-row">
                <div>Giá gói:</div>
                <div id="package-price">0 đ</div>
            </div>
            <div class="grand-total">
                Tổng tiền: <span id="total-price">0 đ</span>
            </div>
        </div>
        
        <!-- Ẩn input để lưu trữ ID gói đã chọn -->
        <input type="hidden" name="package_id" id="package-id-input" value="">
        
        <!-- Thêm tài khoản vào form -->
        <?php foreach ($accounts as $acc): ?>
            <input type="hidden" name="selected_accounts[]" value="<?php echo $acc['id']; ?>">
        <?php endforeach; ?>
        
        <div>
            <button type="submit" class="btn btn-primary" id="submit-button" disabled>Xác nhận gia hạn</button>
            <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const packageCards = document.querySelectorAll('.package-card');
    const packageIdInput = document.getElementById('package-id-input');
    const selectedPackageName = document.getElementById('selected-package-name');
    const packagePrice = document.getElementById('package-price');
    const totalPrice = document.getElementById('total-price');
    const submitButton = document.getElementById('submit-button');
    const accountCount = <?php echo count($accounts); ?>;
    
    packageCards.forEach(card => {
        card.addEventListener('click', function() {
            // Bỏ chọn tất cả các thẻ
            packageCards.forEach(c => c.classList.remove('selected'));
            
            // Chọn thẻ hiện tại
            this.classList.add('selected');
            
            // Lấy thông tin gói
            const packageId = this.dataset.packageId;
            const price = parseFloat(this.dataset.packagePrice);
            
            // Cập nhật form và hiển thị
            packageIdInput.value = packageId;
            selectedPackageName.textContent = this.querySelector('.package-name').textContent;
            packagePrice.textContent = new Intl.NumberFormat('vi-VN').format(price) + ' đ';
            
            // Tính tổng giá trị
            const total = price * accountCount;
            totalPrice.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
            
            // Cho phép submit form
            submitButton.disabled = false;
        });
    });
});
</script>
</body>
</html>
