<?php
session_start();
$project_root_path = dirname(dirname(dirname(__DIR__)));
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php';
require_once $project_root_path . '/private/classes/Location.php'; // Thêm Location class để lấy tên tỉnh/thành phố
require_once $project_root_path . '/private/classes/Voucher.php';
require_once $project_root_path . '/private/utils/functions.php';
require_once $project_root_path . '/private/api/rtk_system/account_api.php';

$base_url = BASE_URL;

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$selected_accounts = $_POST['selected_accounts'] ?? [];
$package_id = $_POST['package_id'] ?? null;
if (empty($selected_accounts) || !is_array($selected_accounts) || empty($package_id)) {
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=invalid_renewal_data');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$packageObj = new Package();

// Initialize Voucher object and reset voucher session data when starting a new renewal
$voucherObj = new Voucher($db);
$voucherObj->resetVoucherSession('renewal');

try {
    $conn->beginTransaction();
    
    // Xác thực dữ liệu đầu vào
    if (empty($selected_accounts) || empty($package_id)) {
        throw new Exception('Thông tin gia hạn không hợp lệ.');
    }
    
    // Lấy thông tin gói đã chọn
    $package = $packageObj->getPackageById((int)$package_id);
    if (!$package) {
        throw new Exception('Gói gia hạn không tồn tại.');
    }
    
    // Lấy thời điểm hiện tại
    $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $now_str = $now->format('Y-m-d H:i:s');
    
    // Tạo mảng lưu các account_id và location để kiểm tra
    $account_data = [];
    $location_id = null;
    $total_accounts = count($selected_accounts);
    
    // Xác định location_id và kiểm tra tính hợp lệ của các tài khoản
    foreach ($selected_accounts as $account_id) {
        $stmt = $conn->prepare("SELECT sa.*, r.location_id, r.num_account, l.province_code FROM survey_account sa 
                              JOIN registration r ON sa.registration_id = r.id 
                              JOIN location l ON r.location_id = l.id 
                              WHERE sa.id = ? AND sa.deleted_at IS NULL");
        $stmt->execute([$account_id]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$acc) {
            throw new Exception('Một hoặc nhiều tài khoản không tồn tại.');
        }
        
        // Nếu chưa có location_id, lấy từ tài khoản đầu tiên
        if ($location_id === null) {
            $location_id = $acc['location_id'];
        }
        
        // Tính thời gian bắt đầu: lấy end_time cũ hoặc NOW nếu đã hết hạn
        $old_end = new DateTime($acc['end_time'] ?? 'now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $start_time = ($old_end > $now) ? $old_end : $now;
        
        $account_data[$account_id] = [
            'acc_info' => $acc,
            'start_time' => $start_time->format('Y-m-d H:i:s')
        ];
    }
      // Tính thời gian kết thúc dựa trên gói đã chọn (sử dụng thời gian bắt đầu từ tài khoản đầu tiên)
    $first_acc = reset($account_data);
    $end_time = calculateEndTime($first_acc['start_time'], $package['duration_text']);
    if (!$end_time) {
        throw new Exception('Không thể tính toán thời gian kết thúc.');
    }
    
    // Kiểm tra nếu có thêm thời gian từ voucher
    $additional_months = 0;
    if (isset($_SESSION['renewal']['additional_months']) && $_SESSION['renewal']['additional_months'] > 0) {
        $additional_months = (int)$_SESSION['renewal']['additional_months'];
        // Cập nhật thời gian kết thúc với thời gian bổ sung từ voucher
        $end_date = new DateTime($end_time, new DateTimeZone('Asia/Ho_Chi_Minh'));
        $end_date->modify("+{$additional_months} months");
        $end_time = $end_date->format('Y-m-d H:i:s');
    }
    
    // Tính tổng giá trị cho toàn bộ giao dịch
    $total_price = $package['price'] * $total_accounts;
    
    // 1. Tạo một đăng ký mới cho tất cả tài khoản
    $sql_reg = "INSERT INTO registration (user_id, package_id, location_id, num_account, start_time, end_time, base_price, vat_percent, vat_amount, total_price, status, created_at, updated_at) 
               VALUES (:user_id, :package_id, :location_id, :num_account, :start_time, :end_time, :base_price, 0, 0, :total_price, 'pending', NOW(), NOW())";
    $stmt_reg = $conn->prepare($sql_reg);
    $stmt_reg->execute([
        ':user_id' => $user_id,
        ':package_id' => $package['id'],
        ':location_id' => $location_id,
        ':num_account' => $total_accounts,
        ':start_time' => $first_acc['start_time'], 
        ':end_time' => $end_time,
        ':base_price' => $package['price'],
        ':total_price' => $total_price
    ]);
    
    $registration_id = $conn->lastInsertId();
    if (!$registration_id) {
        throw new Exception('Không thể tạo đăng ký mới.');
    }
    
    // 2. Liên kết tất cả tài khoản với đăng ký mới
    foreach ($account_data as $account_id => $data) {
        $stmt_ag = $conn->prepare("INSERT INTO account_groups (registration_id, survey_account_id) VALUES (?, ?)");
        $stmt_ag->execute([$registration_id, $account_id]);
    }
      // 3. Tạo một giao dịch duy nhất cho việc gia hạn
    // Kiểm tra xem có voucher đã được áp dụng không
    $voucher_id = null;
    if (isset($_SESSION['renewal']['voucher_id'])) {
        $voucher_id = $_SESSION['renewal']['voucher_id'];
    }
      if ($voucher_id) {
        $stmt_th = $conn->prepare("INSERT INTO transaction_history (registration_id, user_id, voucher_id, transaction_type, amount, status, payment_method, created_at, updated_at) 
                                 VALUES (?, ?, ?, 'renewal', ?, 'pending', 'Chuyển khoản ngân hàng', NOW(), NOW())");
        $stmt_th->execute([$registration_id, $user_id, $voucher_id, $total_price]);
    } else {
        $stmt_th = $conn->prepare("INSERT INTO transaction_history (registration_id, user_id, transaction_type, amount, status, payment_method, created_at, updated_at) 
                                 VALUES (?, ?, 'renewal', ?, 'pending', 'Chuyển khoản ngân hàng', NOW(), NOW())");
        $stmt_th->execute([$registration_id, $user_id, $total_price]);
    }
    
    $transaction_id = $conn->lastInsertId();
    
    $conn->commit();
    
    // Lưu thông tin vào session để xử lý ở trang thanh toán
    $_SESSION['pending_registration_id'] = $registration_id;
    $_SESSION['pending_total_price'] = $total_price;
    $_SESSION['pending_is_trial'] = false;
    $_SESSION['is_renewal'] = true; 
    $_SESSION['renewal_account_ids'] = $selected_accounts; 
    
    // Lưu thêm thông tin chi tiết cho trang thanh toán hiển thị
    $_SESSION['pending_renewal_details'] = [
        'total_accounts' => $total_accounts,
        'total_price' => $total_price,
        'timestamp' => time(),
        'package_name' => $package['name']
    ];
      // Log renewal request với đầy đủ thông tin
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Lấy thông tin tỉnh/thành phố từ location_id
    $location_obj = new Location();
    $location_details = $location_obj->getLocationById($location_id);
    $province_name = $location_details ? $location_details['province'] : '';
    $location_obj->closeConnection();
    
    // Tạo log data với đầy đủ thông tin và đảm bảo không lỗi font tiếng Việt
    $log_data = json_encode([
        'registration_id' => $registration_id,
        'selected_accounts' => $selected_accounts,
        'total_price' => $total_price,
        'package' => $package['name'],
        'location' => $province_name // Thêm thông tin tỉnh/thành phố
    ], JSON_UNESCAPED_UNICODE); // Sử dụng JSON_UNESCAPED_UNICODE để tránh lỗi font tiếng Việt
    $notify_content = 'Yêu cầu gia hạn gói dịch vụ cho đăng ký #' . $registration_id . ' - Gói: ' . $package['name'];
    $stmt_log = $conn->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at) 
                              VALUES (?, 'renewal_request', 'registration', ?, ?, ?, ?, ?, NOW())");
    $stmt_log->execute([$user_id, $registration_id, $ip, $ua, $log_data, $notify_content]);
    
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php');
    exit;

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . $base_url . '/public/pages/rtk_accountmanagement.php?error=' . urlencode($e->getMessage()));
    exit;
} finally {
    if (isset($db)) $db->close();
    if (isset($packageObj)) $packageObj->closeConnection();
}
?>
