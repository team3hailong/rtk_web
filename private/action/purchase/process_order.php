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
require_once $project_root_path . '/private/utils/functions.php'; // For helper functions if any

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

// --- Validate Input ---
if (!$package_id || !$quantity || $quantity < 1 || !$location_id) {
     // Log the specific missing fields if needed
     error_log("Process Order Error: Missing or invalid input. UserID: {$user_id}, PackageID: {$package_id}, Qty: {$quantity}, LocationID: {$location_id}");
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

    // --- Server-side Price Calculation ---
    $base_price = (float)$package['price'];
    $calculated_total_price = $base_price * $quantity;
    // Optional: Add VAT calculation here if needed
    $vat_percent = 0; // Example: Get from config or package details
    $vat_amount = $calculated_total_price * ($vat_percent / 100);
    $final_total_price = $calculated_total_price + $vat_amount;

    // --- Calculate Start and End Dates (Example: using package duration text) ---
    $start_time = new DateTime();
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

    $start_time_str = $start_time->format('Y-m-d H:i:s');
    $end_time_str = $end_time->format('Y-m-d H:i:s');

    // --- Database Interaction ---
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // 1. Insert into Registration
    $sql_reg = "INSERT INTO registration (user_id, package_id, location_id, num_account, start_time, end_time, base_price, vat_percent, vat_amount, total_price, status, created_at, updated_at)
                VALUES (:user_id, :package_id, :location_id, :num_account, :start_time, :end_time, :base_price, :vat_percent, :vat_amount, :total_price, 'pending', NOW(), NOW())";
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
    $stmt_reg->bindParam(':total_price', $final_total_price);
    $stmt_reg->execute();

    $registration_id = $conn->lastInsertId();
    if (!$registration_id) {
        throw new Exception("Failed to create registration record.");
    }

    // 2. Insert into Transaction History
    $sql_trans = "INSERT INTO transaction_history (registration_id, user_id, transaction_type, amount, status, payment_method, created_at, updated_at)
                  VALUES (:registration_id, :user_id, 'purchase', :amount, 'pending', NULL, NOW(), NOW())"; // Payment method set later or upon confirmation
    $stmt_trans = $conn->prepare($sql_trans);
    $stmt_trans->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_trans->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_trans->bindParam(':amount', $final_total_price); // Use the final calculated price
    $stmt_trans->execute();

    if ($stmt_trans->rowCount() == 0) {
         throw new Exception("Failed to create transaction history record.");
    }

    // Commit Transaction
    $conn->commit();

    // --- Store registration ID in session for payment page ---
    $_SESSION['pending_registration_id'] = $registration_id;
    $_SESSION['pending_total_amount'] = $final_total_price; // Store amount for payment page

    // --- Redirect to Payment Instructions Page ---
    header('Location: ' . $base_url . '/public/pages/purchase/payment.php?reg_id=' . $registration_id);
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
    error_log("Error processing order: " . $e->getMessage());
    $error_query = http_build_query(['error' => urlencode($e->getMessage()), 'package' => $package['package_id'] ?? $package_id]);
    header('Location: ' . $base_url . '/public/pages/purchase/details.php?' . $error_query);
    exit;
} finally {
    if ($db) {
        $db->close();
    }
}
?>