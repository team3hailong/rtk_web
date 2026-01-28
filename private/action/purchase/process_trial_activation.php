<?php
session_start();
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
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // 1. Lấy thông tin đăng ký
    $stmt = $conn->prepare("SELECT r.*, u.phone, u.username as customer_name, p.price, p.name as package_name FROM registration r JOIN user u ON r.user_id = u.id JOIN package p ON r.package_id = p.id WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'");
    $stmt->execute([$registration_id, $user_id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reg) throw new Exception('Đăng ký không tồn tại hoặc đã xử lý.');
    if ($reg['price'] > 0) throw new Exception('Gói này không phải dùng thử.');
    
    // 1.5. Kiểm tra xem người dùng đã từng dùng gói trial trước đó chưa
    require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/DeviceTracker.php';
    $deviceTracker = new DeviceTracker($conn);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $device_fingerprint = $_SESSION['device_fingerprint'] ?? '';
    
    // Kiểm tra cả thiết bị và user_id
    $trialStatus = $deviceTracker->getTrialStatus($device_fingerprint, $ip, $user_id);
    if ($trialStatus['trial_used']) {
        $reason = $trialStatus['reason'] ?? 'unknown';
        if ($reason == 'user') {
            throw new Exception('Tài khoản của bạn đã sử dụng gói dùng thử trước đây.');
        } else {
            throw new Exception('Thiết bị này đã được sử dụng để đăng ký gói dùng thử trước đây.');
        }
    }

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
        "customerBizType" => 1,
        "customerCompany" => "",
        "casterIds" => [],
        "regionIds" => [],
        "mountIds" => $mountIds
    ];    $api_result = createRtkAccount($accountData);
    if (empty($api_result['success'])) throw new Exception('Không thể tạo tài khoản RTK: ' . ($api_result['error'] ?? 'Lỗi không xác định'));
    
    // Lấy ID từ response API
    if (empty($api_result['data']['id'])) throw new Exception('Không tìm thấy ID tài khoản RTK trong response API');
    
    // 8. Lưu vào survey_account
    $account_id = $api_result['data']['id'];
    $stmt = $conn->prepare("INSERT INTO survey_account (id, registration_id, username_acc, password_acc, concurrent_user, enabled, customerBizType, start_time, end_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$account_id, $registration_id, $username, $password, $reg['num_account'], 1, 1, $start, $end]);
    if (!$ok) throw new Exception('Lỗi khi lưu tài khoản vào survey_account.');

    // 9. Thêm vào account_groups
    $stmt = $conn->prepare("INSERT INTO account_groups (registration_id, survey_account_id) VALUES (?, ?)");
    $stmt->execute([$registration_id, $account_id]);    // 10. Cập nhật trạng thái đăng ký và transaction_history
    $stmt = $conn->prepare("UPDATE registration SET status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$registration_id]);
    
    // Lấy transaction ID để cập nhật
    $stmt = $conn->prepare("SELECT id, voucher_id FROM transaction_history WHERE registration_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$registration_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {        // Cập nhật transaction_history
        $stmt = $conn->prepare("UPDATE transaction_history SET status = 'completed', payment_confirmed = 1, payment_confirmed_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$transaction['id']]);
        
        // Tính toán hoa hồng giới thiệu khi transaction hoàn thành
        require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Referral.php';
        require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';
        $db = new Database();
        $referralService = new Referral($db);
        $referralService->calculateCommission($transaction['id']);
        
        // Nếu có voucher_id, tăng số lần sử dụng voucher
        if (!empty($transaction['voucher_id'])) {
            require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Voucher.php';
            $voucherService = new Voucher($db);
            $voucherService->incrementUsage($transaction['voucher_id']);
        }
    } else {
        throw new Exception('Không tìm thấy giao dịch cần cập nhật.');
    }

    // 11. Đánh dấu thiết bị và tài khoản người dùng đã sử dụng gói trial
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Đánh dấu cả thiết bị và user_id
    $deviceTracker->markTrialUsed($device_fingerprint, $ip, $user_id);
    
    // Lưu thời điểm hết hạn vào session để hiển thị ngay
    $trialStatus = $deviceTracker->getTrialStatus($device_fingerprint, $ip, $user_id);
    $_SESSION['trial_status'] = $trialStatus;
    $_SESSION['just_activated_trial'] = true; // Mark that trial was just activated
    
    // Log về việc đánh dấu user đã dùng trial
    log_trial('Đã đánh dấu user_id=' . $user_id . ' và device=' . $device_fingerprint . ' đã dùng trial', 
              $user_id, $registration_id, 'info');

    // 12. Ghi log hoạt động - Transaction completed
    try {
        // Lấy thông tin registration và tài khoản được tạo
        $sql_reg_info = "SELECT r.package_id, r.num_account, r.total_price, p.name as package_name, l.province,
                         ra.username, ra.id as account_id
                         FROM registration r 
                         LEFT JOIN package p ON r.package_id = p.id 
                         LEFT JOIN location l ON r.location_id = l.id 
                         LEFT JOIN rtk_account ra ON ra.registration_id = r.id
                         WHERE r.id = ?";
        $stmt_reg_info = $conn->prepare($sql_reg_info);
        $stmt_reg_info->execute([$registration_id]);
        $account_info = $stmt_reg_info->fetch(PDO::FETCH_ASSOC);
        
        $notify_content = 'Giao dịch dùng thử hoàn thành, tạo tài khoản: ' . ($account_info['username'] ?? 'N/A');
        
        $log_data = [
            'transaction_id' => $transaction['id'],
            'registration_id' => $registration_id,
            'transaction_type' => 'trial',
            'package' => $account_info['package_name'] ?? '',
            'province' => $account_info['province'] ?? '',
            'amount' => 0,
            'created_accounts' => [$account_info['username'] ?? 'N/A'],
            'total_accounts' => 1,
            'trial_start' => $start,
            'trial_end' => $end,
            'auto_approved' => true
        ];
        
        $new_values = json_encode($log_data, JSON_UNESCAPED_UNICODE);
        
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at) 
                                VALUES (?, 'transaction_trial_completed', 'transaction', ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $transaction['id'], $ip, $ua, $new_values, $notify_content]);
    } catch (Exception $e) {
        error_log("Error logging trial activation: " . $e->getMessage());
    }

    $conn->commit();
    log_trial('Kích hoạt thành công', $user_id, $registration_id, 'success', ['username' => $username, 'start' => $start, 'end' => $end]);
    $_SESSION['success'] = "Kích hoạt thành công! Tài khoản RTK của bạn đã được tạo.";
    
    // Redirect to success page with trial flag and transaction ID
    $transaction_id = $transaction['id'] ?? null;
    if ($transaction_id) {
        header('Location: ' . BASE_URL . '/public/pages/purchase/success.php?transaction_id=' . $transaction_id . '&is_trial=1&auto_approved=1');
    } else {
        header('Location: ' . BASE_URL . '/public/pages/purchase/success.php?registration_id=' . $registration_id . '&is_trial=1&auto_approved=1');
    }
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    log_trial($e->getMessage(), $user_id ?? 'unknown', $registration_id ?? 'unknown', 'error');
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . BASE_URL . '/public/pages/purchase/payment.php?error=1');
    exit;
}