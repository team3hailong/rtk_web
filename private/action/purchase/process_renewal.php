<?php
session_start();
$project_root_path = dirname(dirname(dirname(__DIR__)));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/utils/functions.php';

$base_url = BASE_URL;

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$selected_accounts = $_POST['selected_accounts'] ?? [];
$package_ids = $_POST['package_id'] ?? [];
if (empty($selected_accounts) || !is_array($selected_accounts) || empty($package_ids)) {
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=invalid_renewal_data');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$packageObj = new Package();

try {
    $conn->beginTransaction();
    $registration_ids = [];
    foreach ($selected_accounts as $account_id) {
        $pkg_id = $package_ids[$account_id] ?? null;
        if (!$pkg_id) continue;
        $package = $packageObj->getPackageById((int)$pkg_id);
        if (!$package) continue;
        // Lấy thông tin account cũ
        $stmt = $conn->prepare("SELECT sa.*, r.location_id, r.num_account FROM survey_account sa JOIN registration r ON sa.registration_id = r.id WHERE sa.id = ? AND sa.deleted_at IS NULL");
        $stmt->execute([$account_id]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$acc) continue;
        // Tính thời gian bắt đầu: lấy end_time cũ hoặc NOW nếu đã hết hạn
        $now = new DateTime();
        $old_end = new DateTime($acc['end_time']);
        $start_time = ($old_end > $now) ? $old_end : $now;
        $end_time = calculateEndTime($start_time->format('Y-m-d H:i:s'), $package['duration_text']);
        // 1. Insert registration
        $sql_reg = "INSERT INTO registration (user_id, package_id, location_id, num_account, start_time, end_time, base_price, vat_percent, vat_amount, total_price, status, created_at, updated_at) VALUES (:user_id, :package_id, :location_id, :num_account, :start_time, :end_time, :base_price, 0, 0, :total_price, 'pending', NOW(), NOW())";
        $stmt_reg = $conn->prepare($sql_reg);
        $stmt_reg->execute([
            ':user_id' => $user_id,
            ':package_id' => $package['id'],
            ':location_id' => $acc['location_id'],
            ':num_account' => $acc['num_account'],
            ':start_time' => $start_time->format('Y-m-d H:i:s'),
            ':end_time' => $end_time,
            ':base_price' => $package['price'],
            ':total_price' => $package['price'],
        ]);
        $registration_id = $conn->lastInsertId();
        if (!$registration_id) throw new Exception('Không thể tạo đăng ký mới.');
        // 2. Insert account_groups
        $stmt_ag = $conn->prepare("INSERT INTO account_groups (registration_id, survey_account_id) VALUES (?, ?)");
        $stmt_ag->execute([$registration_id, $account_id]);
        // 3. Insert transaction_history
        $stmt_th = $conn->prepare("INSERT INTO transaction_history (registration_id, user_id, transaction_type, amount, status, payment_method, created_at, updated_at) VALUES (?, ?, 'renewal', ?, 'pending', NULL, NOW(), NOW())");
        $stmt_th->execute([$registration_id, $user_id, $package['price']]);
        $registration_ids[] = $registration_id;
    }
    $conn->commit();
    // Lưu registration_id cuối cùng vào session để chuyển sang payment
    if (!empty($registration_ids)) {
        $_SESSION['pending_registration_id'] = end($registration_ids);
        $_SESSION['pending_total_price'] = $package['price']; // Lấy giá gói cuối cùng (nếu nhiều gói thì cần xử lý lại)
        $_SESSION['pending_is_trial'] = false;
        header('Location: ' . $base_url . '/public/pages/purchase/payment.php');
        exit;
    } else {
        throw new Exception('Không có tài khoản nào được gia hạn thành công.');
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Renewal error: ' . $e->getMessage());
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=renewal_failed');
    exit;
} finally {
    $db->close();
    $packageObj->closeConnection();
}
?>
