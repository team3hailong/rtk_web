<?php
session_start();
$project_root_path = dirname(dirname(dirname(__DIR__)));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/utils/functions.php';
require_once $project_root_path . '/private/api/rtk_system/account_api.php';

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
    $total_price = 0;
    
    foreach ($selected_accounts as $account_id) {
        $pkg_id = $package_ids[$account_id] ?? null;
        if (!$pkg_id) continue;
        
        $package = $packageObj->getPackageById((int)$pkg_id);
        if (!$package) continue;
        
        // Lấy thông tin account cũ
        $stmt = $conn->prepare("SELECT sa.*, r.location_id, r.num_account, l.province_code FROM survey_account sa 
                              JOIN registration r ON sa.registration_id = r.id 
                              JOIN location l ON r.location_id = l.id 
                              WHERE sa.id = ? AND sa.deleted_at IS NULL");
        $stmt->execute([$account_id]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$acc) continue;
        
        // Tính thời gian bắt đầu: lấy end_time cũ hoặc NOW nếu đã hết hạn
        $now = new DateTime();
        $old_end = new DateTime($acc['end_time'] ?? 'now');
        $start_time = ($old_end > $now) ? $old_end : $now;
        
        // Tính thời gian kết thúc mới dựa trên gói đã chọn
        $end_time = calculateEndTime($start_time->format('Y-m-d H:i:s'), $package['duration_text']);
        if (!$end_time) {
            // Nếu không thể tính toán end_time, log lỗi và bỏ qua
            error_log("Failed to calculate end time for account {$account_id} with duration {$package['duration_text']}");
            continue;
        }
        
        // 1. Insert registration mới cho gói gia hạn
        $sql_reg = "INSERT INTO registration (user_id, package_id, location_id, num_account, start_time, end_time, base_price, vat_percent, vat_amount, total_price, status, created_at, updated_at) 
                   VALUES (:user_id, :package_id, :location_id, :num_account, :start_time, :end_time, :base_price, 0, 0, :total_price, 'pending', NOW(), NOW())";
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
        
        // 2. Insert account_groups - liên kết registration mới với account cũ
        $stmt_ag = $conn->prepare("INSERT INTO account_groups (registration_id, survey_account_id) VALUES (?, ?)");
        $stmt_ag->execute([$registration_id, $account_id]);
        
        // 3. Insert transaction_history cho việc gia hạn
        $stmt_th = $conn->prepare("INSERT INTO transaction_history (registration_id, user_id, transaction_type, amount, status, payment_method, created_at, updated_at) 
                                 VALUES (?, ?, 'renewal', ?, 'pending', NULL, NOW(), NOW())");
        $stmt_th->execute([$registration_id, $user_id, $package['price']]);
        
        // Cộng tổng giá tiền
        $total_price += $package['price'];
        $registration_ids[] = $registration_id;
    }
    
    $conn->commit();
    
    // Lưu tất cả registration_ids và thông tin chi tiết vào session để hiển thị đầy đủ trên trang thanh toán
    if (!empty($registration_ids)) {
        $_SESSION['pending_registration_ids'] = $registration_ids; // Lưu tất cả registration IDs
        $_SESSION['pending_registration_id'] = end($registration_ids); // Vẫn giữ lại để tương thích với code cũ
        $_SESSION['pending_total_price'] = $total_price; // Tổng giá trị thanh toán
        $_SESSION['pending_is_trial'] = false;
        $_SESSION['is_renewal'] = true; // Đánh dấu đây là gia hạn để xử lý riêng
        $_SESSION['renewal_account_ids'] = $selected_accounts; // Lưu lại danh sách account để sau khi thanh toán sẽ cập nhật
        
        // Lưu thêm thông tin chi tiết cho trang thanh toán hiển thị
        $_SESSION['pending_renewal_details'] = [
            'total_accounts' => count($selected_accounts),
            'total_price' => $total_price,
            'timestamp' => time()
        ];
        
        // Log renewal request
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $log_data = json_encode([
            'registration_ids' => $registration_ids,
            'selected_accounts' => $selected_accounts,
            'total_price' => $total_price
        ]);
        $stmt_log = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, created_at) 
                                  VALUES (?, 'renewal_request', 'registration', ?, ?, ?, ?, NOW())");
        $stmt_log->execute([$user_id, end($registration_ids), $ip, $ua, $log_data]);
        
        header('Location: ' . $base_url . '/public/pages/purchase/payment.php');
        exit;
    } else {
        throw new Exception('Không có tài khoản nào được gia hạn thành công.');
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('Renewal error: ' . $e->getMessage());
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=renewal_failed&message=' . urlencode($e->getMessage()));
    exit;
} finally {
    if (isset($db)) $db->close();
    if (isset($packageObj)) $packageObj->closeConnection();
}
?>
