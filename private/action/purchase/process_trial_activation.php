<?php
session_start();
// --- Base URL và Path chuẩn như map_display.php ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = dirname($script_dir);
$base_url = rtrim($protocol . $domain . ($base_project_dir === '/' || $base_project_dir === '\\' ? '' : $base_project_dir), '/');
$project_root_path = dirname(dirname(dirname(__DIR__))); // private/action/purchase -> project root

require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/config/database.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/api/rtk_system/account_api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/pages/auth/login.php');
    exit;
}

$registration_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
if (!$registration_id) {
    $_SESSION['error'] = 'Registration ID không hợp lệ';
    header('Location: ' . $base_url . '/pages/purchase/payment.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    $sql = "SELECT r.*, u.phone, u.username as customer_name, p.price, p.name as package_name 
            FROM registration r 
            JOIN user u ON r.user_id = u.id 
            JOIN package p ON r.package_id = p.id
            WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$registration_id, $_SESSION['user_id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        throw new Exception("Đăng ký không tồn tại hoặc đã được xử lý");
    }
    if ($data['price'] > 0) {
        throw new Exception("Gói này không phải gói dùng thử");
    }
    $sql_check = "SELECT COUNT(*) FROM survey_account WHERE registration_id = ? AND deleted_at IS NULL";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$registration_id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("Đã tồn tại tài khoản RTK cho đăng ký này");
    }
    // Lấy province_code
    $stmt_province = $conn->prepare("SELECT province_code FROM location WHERE id = ? LIMIT 1");
    $stmt_province->execute([$data['location_id']]);
    $province_code = $stmt_province->fetchColumn();
    if (!$province_code) throw new Exception("Không tìm thấy mã tỉnh cho location_id: " . $data['location_id']);
    // Tìm số thứ tự lớn nhất của tài khoản TRIAL_{province_code}NNN
    $stmt_trial = $conn->prepare("SELECT username_acc FROM survey_account WHERE username_acc LIKE ? ORDER BY username_acc DESC LIMIT 1");
    $like_pattern = 'TRIAL_' . $province_code . '%';
    $stmt_trial->execute([$like_pattern]);
    $last_trial = $stmt_trial->fetchColumn();
    $next_number = 1;
    if ($last_trial && preg_match('/TRIAL_' . $province_code . '(\\d{3})$/', $last_trial, $m)) {
        $next_number = intval($m[1]) + 1;
    }
    $username = sprintf('TRIAL_%s%03d', $province_code, $next_number);
    $password = $data['phone'];
    // Lấy danh sách mountIds là ID (số) trong bảng mount_point
    $stmt_mount = $conn->prepare("SELECT id FROM mount_point WHERE location_id = ?");
    $stmt_mount->execute([$data['location_id']]);
    $mountIds = [];
    while ($row = $stmt_mount->fetch(PDO::FETCH_ASSOC)) {
        if (is_numeric($row['id'])) $mountIds[] = (int)$row['id'];
    }
    if (empty($mountIds)) throw new Exception("Không tìm thấy mountpoint cho location_id: " . $data['location_id']);
    $accountData = [
        "name" => $username,
        "userPwd" => $password,
        "startTime" => strtotime($data['start_time']) * 1000,
        "endTime" => strtotime($data['end_time']) * 1000,
        "locationId" => $data['location_id'],
        "enabled" => 1,
        "numOnline" => $data['num_account'],
        "customerName" => $data['customer_name'],
        "customerPhone" => $data['phone'],
        "customerBizType" => 1,
        "customerCompany" => "",
        "casterIds" => [],
        "regionIds" => [],
        "mountIds" => $mountIds
    ];
    $result = createRtkAccount($accountData);
    if (!$result['success']) {
        throw new Exception("Không thể tạo tài khoản RTK: " . ($result['error'] ?? 'Lỗi không xác định'));
    }
    $account_id = 'RTK_' . $registration_id . '_' . time();
    $sql_insert = "INSERT INTO survey_account (id, registration_id, username_acc, password_acc, concurrent_user, enabled, customerBizType, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([
        $account_id,
        $registration_id,
        $username,
        $password,
        $data['num_account'],
        1,
        1
    ]);
    $stmt_update = $conn->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt_update->execute([$registration_id]);
    $stmt_trans = $conn->prepare("UPDATE transaction_history SET status = 'completed', updated_at = NOW() WHERE registration_id = ? AND status = 'pending'");
    $stmt_trans->execute([$registration_id]);
    $conn->commit();
    $_SESSION['rtk_account'] = [
        'username' => $username,
        'password' => $password
    ];
    unset($_SESSION['pending_registration_id'], $_SESSION['pending_total_price'], $_SESSION['pending_is_trial']);
    $_SESSION['success'] = "Kích hoạt thành công! Tài khoản RTK của bạn đã được tạo.";
    // Quay về trang quản lý tài khoản ở public/pages
    header('Location: /public/pages/rtk_accountmanagement.php');
    exit;
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . $base_url . '/pages/purchase/payment.php?error=1');
    exit;
} finally {
    if (isset($db)) $db->close();
}