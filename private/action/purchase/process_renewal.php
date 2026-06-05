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

// --- Kiểm tra số điện thoại (security check) ---
$db_check = new Database();
$conn_check = $db_check->getConnection();
$stmt_check = $conn_check->prepare("SELECT phone FROM user WHERE id = :user_id");
$stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_check->execute();
$user_phone = $stmt_check->fetchColumn();

if (empty($user_phone)) {
    $_SESSION['error_message'] = 'Vui lòng cập nhật số điện thoại trước khi gia hạn gói dịch vụ.';
    header('Location: ' . $base_url . '/public/pages/setting/profile.php?require_phone=1');
    exit;
}

// $purchase_type = filter_input(INPUT_POST, 'purchase_type', FILTER_SANITIZE_STRING);
$purchase_type = filter_input(INPUT_POST, 'purchase_type', FILTER_DEFAULT);
if (!in_array($purchase_type, ['individual', 'company'])) {
    $purchase_type = 'individual'; // Default to individual if invalid
}

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
    $base_price_per_account = $package['price'];
    $sub_total = $base_price_per_account * $total_accounts;
      // Kiểm tra nếu có voucher giảm giá cần áp dụng vào giá gốc (trước VAT)
    $discounted_sub_total = $sub_total;
    $discount_amount = 0;
    
    if (isset($_SESSION['renewal']['voucher_id']) && isset($_SESSION['renewal']['voucher_discount'])) {
        $discount_amount = $_SESSION['renewal']['voucher_discount'];
        $discounted_sub_total = $sub_total - $discount_amount;
        
        // Đảm bảo giá sau giảm giá không âm
        if ($discounted_sub_total < 0) {
            $discounted_sub_total = 0;
        }
        
        // Lưu thông tin giá gốc và giảm giá để hiển thị trên trang thanh toán
        $_SESSION['renewal']['original_subtotal'] = $sub_total;
        $_SESSION['renewal']['discounted_subtotal'] = $discounted_sub_total;
    }

    $vat_percent = 0;
    $vat_amount = 0;
    $invoice_allowed = 0;

    if ($purchase_type === 'company') {
        $vat_percent = getenv('VAT_VALUE') !== false ? (float)getenv('VAT_VALUE') : 10;
        // Tính VAT dựa trên giá đã giảm giá
        $vat_amount = round($discounted_sub_total * ($vat_percent / 100));
        $invoice_allowed = 1;
    }
    $total_price = $discounted_sub_total + $vat_amount;
    
    // 1. Tạo một đăng ký mới cho tất cả tài khoản
    $sql_reg = "INSERT INTO registration (user_id, package_id, location_id, num_account, start_time, end_time, base_price, vat_percent, vat_amount, total_price, status, purchase_type, invoice_allowed, created_at, updated_at) 
               VALUES (:user_id, :package_id, :location_id, :num_account, :start_time, :end_time, :base_price, :vat_percent, :vat_amount, :total_price, 'pending', :purchase_type, :invoice_allowed, NOW(), NOW())";
    $stmt_reg = $conn->prepare($sql_reg);
    $stmt_reg->execute([
        ':user_id' => $user_id,
        ':package_id' => $package['id'],
        ':location_id' => $location_id,
        ':num_account' => $total_accounts,
        ':start_time' => $first_acc['start_time'], 
        ':end_time' => $end_time,
        ':base_price' => $base_price_per_account, // Store base price per account
        ':vat_percent' => $vat_percent,
        ':vat_amount' => $vat_amount,
        ':total_price' => $total_price, // This is the final total price including VAT
        ':purchase_type' => $purchase_type,
        ':invoice_allowed' => $invoice_allowed
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
    
    // NOTE: Transaction History sẽ được tạo sau khi:
    // - Upload proof thành công (upload_payment_proof.php)
    // - Hoặc hoàn tất đơn hàng không cần proof (complete_order_without_proof.php)
    // Không tạo transaction ở đây để tránh tạo giao dịch khi user chưa hoàn tất thanh toán
    
    $conn->commit();
    
    // Lưu thông tin vào session để xử lý ở trang thanh toán
    $_SESSION['pending_registration_id'] = $registration_id;
    $_SESSION['pending_total_price'] = $total_price; // Ensure this is the final total price
    $_SESSION['pending_is_trial'] = false;
    $_SESSION['is_renewal'] = true; 
    $_SESSION['renewal_account_ids'] = $selected_accounts;
    $_SESSION['purchase_type'] = $purchase_type; // Pass purchase type to payment page
      // Lưu thêm thông tin chi tiết cho trang thanh toán hiển thị
    $_SESSION['pending_renewal_details'] = [
        'total_accounts' => $total_accounts,
        'sub_total' => $sub_total,
        'discounted_sub_total' => $discounted_sub_total,
        'discount_amount' => $discount_amount,
        'vat_percent' => $vat_percent,
        'vat_amount' => $vat_amount,
        'total_price' => $total_price,
        'timestamp' => time(),
        'package_name' => $package['name'],
        'purchase_type' => $purchase_type
    ];

    // NOTE: Activity log sẽ được ghi khi giao dịch hoàn thành (transaction completed)
    // Không ghi log ở đây để tránh ghi log khi user chưa hoàn tất thanh toán
    
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
