<?php
session_start();

// --- Project Root Path ---
$project_root_path = dirname(dirname(dirname(__DIR__))); // Adjust path as needed

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';

// --- Đường dẫn khi quay lại ---
$invoice_page_url = BASE_URL . '/public/pages/setting/invoice.php';

// --- Security Checks ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Người dùng chưa đăng nhập.';
    header('Location: ' . BASE_URL . '/public/pages/auth/login.php'); // Redirect to login
    exit;
}

// --- Get Data from POST ---
$user_id = $_SESSION['user_id'];
$company_name = trim($_POST['company_name'] ?? '');
$tax_code = trim($_POST['tax_code'] ?? '');

// --- Basic Validation ---
if (!empty($tax_code) && empty($company_name)) {
    $_SESSION['error'] = "Vui lòng nhập tên công ty nếu bạn cung cấp mã số thuế.";
    header('Location: ' . $invoice_page_url);
    exit;
}

// Tax code validation - Vietnam tax code format
if (!empty($tax_code) && !preg_match('/^\d{10}(-\d{3})?$/', $tax_code)) {
    $_SESSION['error'] = "Mã số thuế không hợp lệ. Định dạng: 10 chữ số hoặc 10 chữ số-3 chữ số.";
    header('Location: ' . $invoice_page_url);
    exit;
}

// Process form submission
try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception("Lỗi kết nối cơ sở dữ liệu.");
    }

    // Prepare the update statement - chỉ cập nhật 2 trường cần thiết
    $sql = "UPDATE user SET
                company_name = :company_name,
                tax_code = :tax_code,
                updated_at = NOW()
            WHERE id = :user_id";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':company_name', $company_name, PDO::PARAM_STR);
    $stmt->bindParam(':tax_code', $tax_code, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    // Execute the update
    $stmt->execute();

    // Log activity (nếu cần)
    $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, created_at) 
               VALUES (:user_id, 'update_invoice_info', 'user', :entity_id, NOW())";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_log->bindParam(':entity_id', $user_id, PDO::PARAM_INT);
    $stmt_log->execute();

    $_SESSION['success'] = "Thông tin xuất hóa đơn đã được cập nhật thành công.";

} catch (PDOException $e) {
    error_log("Invoice update PDO error: " . $e->getMessage());
    $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật thông tin xuất hóa đơn.";
} catch (Exception $e) {
    error_log("Invoice update general error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
} finally {
    if (isset($db)) {
        $db->close();
    }
}

header('Location: ' . $invoice_page_url);
exit;