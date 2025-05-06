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
    <title>Gia hạn tài khoản RTK</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/assets/css/base.css">
</head>
<body>
<div class="container">
    <h2>Gia hạn tài khoản RTK</h2>
    <form method="post" action="<?php echo $base_url; ?>/private/action/purchase/process_renewal.php">
        <table border="1" cellpadding="6" style="width:100%;margin-bottom:1rem;">
            <thead>
                <tr>
                    <th>Tài khoản</th>
                    <th>Thời hạn hiện tại</th>
                    <th>Chọn gói gia hạn</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $acc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($acc['username_acc']); ?></td>
                    <td><?php echo htmlspecialchars($acc['end_time']); ?></td>
                    <td>
                        <select name="package_id[<?php echo $acc['id']; ?>]" required>
                            <option value="">-- Chọn gói --</option>
                            <?php foreach ($packages as $pkg): ?>
                                <option value="<?php echo $pkg['id']; ?>">
                                    <?php echo htmlspecialchars($pkg['name'] . ' (' . $pkg['duration_text'] . ') - ' . number_format($pkg['price']) . 'đ'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <input type="hidden" name="selected_accounts[]" value="<?php echo $acc['id']; ?>">
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit">Xác nhận gia hạn</button>
        <a href="<?php echo $base_url; ?>/public/pages/rtk_accountmanagement.php">Quay lại</a>
    </form>
</div>
</body>
</html>
