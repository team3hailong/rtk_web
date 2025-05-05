<?php
session_start();
<<<<<<< HEAD
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/api/rtk_system/account_api.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function log_trial($msg, $user_id, $reg_id, $type = 'error', $details = null) {
    $log_file = dirname(dirname(dirname(__DIR__))) . '/private/logs/trial_activation_' . $type . '.log';
    $ts = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $log = "[$ts] [User: $user_id] [Reg: $reg_id] [IP: $ip] $msg";
    if ($details) $log .= ' | ' . json_encode($details, JSON_UNESCAPED_UNICODE);
    error_log($log . PHP_EOL, 3, $log_file);
}

// --- Kiểm tra đăng nhập ---
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/pages/auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$registration_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
if (!$registration_id) {
    $_SESSION['error'] = 'Thiếu mã đăng ký.';
    header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php');
=======
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
>>>>>>> fdb846ab7b7ee896ea5a7a023765246ce690ff39
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

<<<<<<< HEAD
    // 1. Lấy thông tin đăng ký
    $stmt = $conn->prepare("SELECT r.*, u.phone, u.username as customer_name, p.price, p.name as package_name FROM registration r JOIN user u ON r.user_id = u.id JOIN package p ON r.package_id = p.id WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'");
    $stmt->execute([$registration_id, $user_id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reg) throw new Exception('Đăng ký không tồn tại hoặc đã xử lý.');
    if ($reg['price'] > 0) throw new Exception('Gói này không phải dùng thử.');

    // 2. Kiểm tra đã có tài khoản RTK chưa
    $stmt = $conn->prepare("SELECT COUNT(*) FROM survey_account WHERE registration_id = ? AND deleted_at IS NULL");
    $stmt->execute([$registration_id]);
    if ($stmt->fetchColumn() > 0) throw new Exception('Đã tồn tại tài khoản RTK cho đăng ký này.');

    // 3. Lấy mã tỉnh
    $stmt = $conn->prepare("SELECT province_code FROM location WHERE id = ? LIMIT 1");
    $stmt->execute([$reg['location_id']]);
    $province_code = $stmt->fetchColumn();
    if (!$province_code) throw new Exception('Không tìm thấy mã tỉnh.');

    // 4. Sinh username
    $stmt = $conn->prepare("SELECT username_acc FROM survey_account WHERE username_acc LIKE ? ORDER BY username_acc DESC LIMIT 1");
    $like = 'TRIAL_' . $province_code . '%';
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();
    $next = 1;
    if ($last && preg_match('/TRIAL_' . $province_code . '(\d{3})$/', $last, $m)) $next = intval($m[1]) + 1;
    $username = sprintf('TRIAL_%s%03d', $province_code, $next);
    $password = $reg['phone'];

    // 5. Lấy mountIds
    $stmt = $conn->prepare("SELECT id FROM mount_point WHERE location_id = ?");
    $stmt->execute([$reg['location_id']]);
    $mountIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    if (empty($mountIds)) throw new Exception('Không tìm thấy mountpoint cho location_id: ' . $reg['location_id']);

    // 6. Chuẩn hóa thời gian
    $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
    $start = (new DateTime($reg['start_time'], $tz))->format('Y-m-d H:i:s');
    $end = (new DateTime($reg['end_time'], $tz))->format('Y-m-d H:i:s');
    $api_start = strtotime($start) * 1000;
    $api_end = strtotime($end) * 1000;

    // 7. Gọi API tạo tài khoản RTK
    $accountData = [
        "name" => $username,
        "userPwd" => $password,
        "startTime" => $api_start,
        "endTime" => $api_end,
        "locationId" => $reg['location_id'],
        "enabled" => 1,
        "numOnline" => $reg['num_account'],
        "customerName" => $reg['customer_name'],
        "customerPhone" => $reg['phone'],
=======
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
>>>>>>> fdb846ab7b7ee896ea5a7a023765246ce690ff39
        "customerBizType" => 1,
        "customerCompany" => "",
        "casterIds" => [],
        "regionIds" => [],
        "mountIds" => $mountIds
    ];
<<<<<<< HEAD
    $api_result = createRtkAccount($accountData);
    if (empty($api_result['success'])) throw new Exception('Không thể tạo tài khoản RTK: ' . ($api_result['error'] ?? 'Lỗi không xác định'));

    // 8. Lưu vào survey_account
    $account_id = 'RTK_' . $registration_id . '_' . time();
    $stmt = $conn->prepare("INSERT INTO survey_account (id, registration_id, username_acc, password_acc, concurrent_user, enabled, customerBizType, start_time, end_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$account_id, $registration_id, $username, $password, $reg['num_account'], 1, 1, $start, $end]);
    if (!$ok) throw new Exception('Lỗi khi lưu tài khoản vào survey_account.');

    // 9. Thêm vào account_groups
    $stmt = $conn->prepare("INSERT INTO account_groups (registration_id, survey_account_id) VALUES (?, ?)");
    $stmt->execute([$registration_id, $account_id]);

    // 10. Cập nhật trạng thái đăng ký và transaction_history
    $stmt = $conn->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$registration_id]);
    $stmt = $conn->prepare("UPDATE transaction_history SET status = 'completed', payment_confirmed = 1, payment_confirmed_at = NOW(), updated_at = NOW() WHERE registration_id = ? AND status = 'pending'");
    $stmt->execute([$registration_id]);

    // 11. Ghi log hoạt động
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, created_at) VALUES (?, 'trial_activation', 'registration', ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $registration_id, $ip, $ua]);

    $conn->commit();
    log_trial('Kích hoạt thành công', $user_id, $registration_id, 'success', ['username' => $username, 'start' => $start, 'end' => $end]);
    $_SESSION['success'] = "Kích hoạt thành công! Tài khoản RTK của bạn đã được tạo.";
    header('Location: ' . BASE_URL . '/public/pages/rtk_accountmanagement.php');
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    log_trial($e->getMessage(), $user_id ?? 'unknown', $registration_id ?? 'unknown', 'error');
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=1');
    exit;
=======
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
>>>>>>> fdb846ab7b7ee896ea5a7a023765246ce690ff39
}