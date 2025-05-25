<?php
session_start();

// --- Project Root Path ---
$project_root_path = dirname(dirname(dirname(__DIR__))); // Adjust path as needed

// --- Base URL ---
// (You might want to define a function or include a config file for base URL)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
// Basic assumption for base URL calculation
$script_dir = dirname($_SERVER['PHP_SELF']); // e.g., /private/action/purchase
$base_project_dir = '';
if (strpos($script_dir, '/private/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/private/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Package.php'; // Need Package class to verify price/duration
require_once $project_root_path . '/private/classes/Location.php'; // Add Location class for province name
require_once $project_root_path . '/private/utils/functions.php'; // For helper functions if any
require_once $project_root_path . '/private/classes/Voucher.php'; // Add Voucher class

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}

// --- Basic Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=invalid_request');
    exit;
}

// --- Get Data from POST ---
$user_id = $_SESSION['user_id'];
$package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$location_id = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);
$purchase_type = filter_input(INPUT_POST, 'purchase_type', FILTER_DEFAULT); // Lấy purchase_type, replaced FILTER_SANITIZE_STRING

// --- Validate Input ---
if (!$package_id || !$quantity || $quantity < 1 || !$location_id || !in_array($purchase_type, ['individual', 'company'])) { // Validate purchase_type
     // Log the specific missing fields if needed
     error_log("Process Order Error: Missing or invalid input. UserID: {$user_id}, PackageID: {$package_id}, Qty: {$quantity}, LocationID: {$location_id}, PurchaseType: {$purchase_type}");
     header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=missing_data');
     exit;
}

$db = null; // Initialize db variable

try {
    // --- Fetch Package Details (Server-side verification) ---
    $package_obj = new Package();
    $package = $package_obj->getPackageById($package_id); // Use getPackageById (fetches by INT PK)
    $package_obj->closeConnection();

    if (!$package) {
        throw new Exception("Invalid package selected.");
    }

    // --- Check if it's the trial package ---
    // Assuming the trial package has a specific ID or a unique identifier like 'trial_7d' in its varchar_id
    // Let's refine this based on how 'trial_7d' is identified. If it's the varchar_id passed from details:
    $selected_package_varchar_id_from_post = filter_input(INPUT_POST, 'package_varchar_id'); // Need to pass this from details.php form
    $is_trial_package = ($selected_package_varchar_id_from_post === 'trial_7d');

    // --- Server-side Price Calculation ---
    $base_price = (float)$package['price'];
    // Ensure quantity is 1 for trial package, regardless of input (security measure)
    if ($is_trial_package) {
        $quantity = 1;
        $base_price = 0; // Ensure price is 0 for trial
    }
    $calculated_subtotal = $base_price * $quantity;

    // VAT Calculation based on purchase_type
    $vat_percent = 0;
    $vat_amount = 0;
    $invoice_allowed = 0; // Mặc định không cho phép xuất hóa đơn

    if ($purchase_type === 'company' && !$is_trial_package) {
        $vat_percent = 10; // 10% VAT for company
        $vat_amount = round($calculated_subtotal * ($vat_percent / 100), 2);
        $invoice_allowed = 1; // Cho phép xuất hóa đơn cho công ty
    }

    $final_total_price = $calculated_subtotal + $vat_amount;

    // Ensure final price is 0 if it's a trial package
    if ($is_trial_package) {
        $final_total_price = 0;
    }    // --- Calculate Start and End Dates (Example: using package duration text) ---
    $start_time = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $end_time = clone $start_time;
    if (preg_match('/(\d+)\s*(Năm|Tháng|Ngày)/iu', $package['duration_text'], $matches)) {
        $num = (int)$matches[1];
        $unit = strtolower($matches[2]);
        $interval_spec = '';
        if ($unit === 'năm') $interval_spec = "P{$num}Y";
        elseif ($unit === 'tháng') $interval_spec = "P{$num}M";
        elseif ($unit === 'ngày') $interval_spec = "P{$num}D";

        if ($interval_spec) {
            $end_time->add(new DateInterval($interval_spec));
        } else {
             error_log("Could not parse duration '{$package['duration_text']}' for package ID {$package_id}. Defaulting to 1 month.");
             $end_time->add(new DateInterval('P1M'));
        }
    } else {
        error_log("Could not parse duration '{$package['duration_text']}' for package ID {$package_id}. Defaulting to 1 month.");
        $end_time->add(new DateInterval('P1M'));
    }
    
    // Kiểm tra nếu có thêm thời gian từ voucher (thêm tháng)
    $additional_months = 0;
    if (isset($_SESSION['order']['additional_months']) && $_SESSION['order']['additional_months'] > 0) {
        $additional_months = (int)$_SESSION['order']['additional_months'];
        // Cập nhật thời gian kết thúc với thời gian bổ sung từ voucher
        $end_time->modify("+{$additional_months} months");
    }

    $start_time_str = $start_time->format('Y-m-d H:i:s');
    $end_time_str = $end_time->format('Y-m-d H:i:s');

    // --- Database Interaction ---
    $db = new Database();
    $conn = $db->getConnection();
    
    // Reset any previously applied voucher session data
    $voucherObj = new Voucher($db);
    $voucherObj->resetVoucherSession('order');
    
    $conn->beginTransaction();

    // 1. Insert into Registration
    $sql_reg = "INSERT INTO registration (user_id, package_id, location_id, num_account, start_time, end_time, base_price, vat_percent, vat_amount, total_price, status, purchase_type, invoice_allowed, created_at, updated_at)
                VALUES (:user_id, :package_id, :location_id, :num_account, :start_time, :end_time, :base_price, :vat_percent, :vat_amount, :total_price, 'pending', :purchase_type, :invoice_allowed, NOW(), NOW())";
    $stmt_reg = $conn->prepare($sql_reg);
    $stmt_reg->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_reg->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt_reg->bindParam(':location_id', $location_id, PDO::PARAM_INT);
    $stmt_reg->bindParam(':num_account', $quantity, PDO::PARAM_INT);
    $stmt_reg->bindParam(':start_time', $start_time_str, PDO::PARAM_STR);
    $stmt_reg->bindParam(':end_time', $end_time_str, PDO::PARAM_STR);
    $stmt_reg->bindParam(':base_price', $base_price); // PDO detects type
    $stmt_reg->bindParam(':vat_percent', $vat_percent);
    $stmt_reg->bindParam(':vat_amount', $vat_amount);
    $stmt_reg->bindParam(':total_price', $final_total_price); // Use the potentially adjusted final price
    $stmt_reg->bindParam(':purchase_type', $purchase_type, PDO::PARAM_STR); // Bind purchase_type
    $stmt_reg->bindParam(':invoice_allowed', $invoice_allowed, PDO::PARAM_INT); // Bind invoice_allowed
    $stmt_reg->execute();

    $registration_id = $conn->lastInsertId();
    if (!$registration_id) {
        throw new Exception("Failed to create registration record.");
    }    // 2. Insert into Transaction History
    // Kiểm tra xem có voucher được áp dụng không
    $voucher_id = null;
    if (isset($_SESSION['order']['voucher_id'])) {
        $voucher_id = $_SESSION['order']['voucher_id'];
    }
      if ($voucher_id) {
        $sql_trans = "INSERT INTO transaction_history (registration_id, user_id, voucher_id, transaction_type, amount, status, payment_method, created_at, updated_at)
                      VALUES (:registration_id, :user_id, :voucher_id, 'purchase', :amount, 'pending', 'Chuyển khoản ngân hàng', NOW(), NOW())";
        $stmt_trans = $conn->prepare($sql_trans);
        $stmt_trans->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt_trans->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_trans->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $stmt_trans->bindParam(':amount', $final_total_price); // Use the potentially adjusted final price
    } else {
        $sql_trans = "INSERT INTO transaction_history (registration_id, user_id, transaction_type, amount, status, payment_method, created_at, updated_at)
                      VALUES (:registration_id, :user_id, 'purchase', :amount, 'pending', 'Chuyển khoản ngân hàng', NOW(), NOW())"; // Set default payment method
        $stmt_trans = $conn->prepare($sql_trans);
        $stmt_trans->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt_trans->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_trans->bindParam(':amount', $final_total_price); // Use the potentially adjusted final price
    }
    $stmt_trans->execute();

    if ($stmt_trans->rowCount() == 0) {
         throw new Exception("Failed to create transaction history record.");
    }

    // Commit Transaction
    $conn->commit();    // Log user purchase action with detailed information similar to renewal process
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Get location details to include province name instead of just ID
    $location_obj = new Location();
    $location_details = $location_obj->getLocationById($location_id);
    $province_name = $location_details ? $location_details['province'] : '';
    $location_obj->closeConnection();
    
    // Create detailed log data similar to renewal process
    $log_data = json_encode([
        'registration_id' => $registration_id,
        'selected_accounts' => [$quantity], // For new purchase, it's the quantity
        'total_price' => $final_total_price,
        'package' => $package['name'],
        'location' => $province_name // Include province name for better readability
    ], JSON_UNESCAPED_UNICODE); // Ensure proper Vietnamese character encoding
    
    $notify_content = 'Mua gói dịch vụ: ' . $package['name'] . ' - Số lượng: ' . $quantity;
    $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, new_values, notify_content, created_at) 
                VALUES (:user_id, 'purchase', 'registration', :registration_id, :ip_address, :user_agent, :new_values, :notify_content, NOW())";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_log->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_log->bindParam(':ip_address', $ip_address);
    $stmt_log->bindParam(':user_agent', $user_agent);
    $stmt_log->bindParam(':new_values', $log_data);
    $stmt_log->bindParam(':notify_content', $notify_content);
    $stmt_log->execute();

    // Store registration ID, total price, and trial status in session for payment page
    $_SESSION['pending_registration_id'] = $registration_id;
    $_SESSION['pending_total_price'] = $final_total_price;
    $_SESSION['pending_is_trial'] = $is_trial_package; // Store trial status

    // Đảm bảo xóa các biến session liên quan đến renewal
    unset($_SESSION['is_renewal']);
    unset($_SESSION['renewal_account_ids']);
    unset($_SESSION['pending_renewal_details']);
    unset($_SESSION['pending_registration_ids']);

    // Ensure session data is written before redirecting (optional but good practice)
    session_write_close();

    // --- Redirect to Payment Instructions Page ---
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php');
    exit;

} catch (PDOException $e) {
    // Rollback on database error
    if ($db && $db->getConnection() && $db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    error_log("Database Error processing order: " . $e->getMessage());
    header('Location: ' . $base_url . '/public/pages/purchase/packages.php?error=db_error');
    exit;
} catch (Exception $e) {
    // Rollback on general error
    if ($db && $db->getConnection() && $db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    // Log the detailed error
    error_log("Error processing order: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
    // Redirect with a generic error message, avoid exposing details
    $_SESSION['purchase_error'] = 'Đã xảy ra lỗi khi xử lý đơn hàng của bạn. Vui lòng thử lại.'; // Use session flash message
    // Redirect back to package selection or details page with a generic indicator
    $redirect_url = $base_url . '/public/pages/purchase/packages.php?error=processing_failed';
    header('Location: ' . $redirect_url);
    exit;
} finally {
    if ($db) {
        $db->close();
    }
}
?>