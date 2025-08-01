<?php
// #uploadMC - Tăng giới hạn kích thước tải lên PHP (chỉ có hiệu lực nếu server cho phép)
@ini_set('upload_max_filesize', '15M');
@ini_set('post_max_size', '15M');
@ini_set('memory_limit', '128M');
@ini_set('max_execution_time', '300'); // 5 phút

// Không cần session_start() vì session đã được khởi tạo trong action_handler.php
// Đặt project_root_path trước khi sử dụng trong error_log
$project_root_path = dirname(dirname(dirname(__DIR__))); // Lấy đường dẫn gốc

// --- Base URL ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/public/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/public/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/purchase/PaymentProofService.php';
require_once $project_root_path . '/private/classes/Voucher.php';
require_once $project_root_path . '/private/classes/CloudinaryService.php';

// --- Constants ---
// #uploadMC - Tăng giới hạn kích thước tải lên thành 15MB (từ 5MB)
// Ensure consistency: Only allow image MIME types and extensions
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_FILE_SIZE', 15 * 1024 * 1024);

// --- IMPORTANT: Set Content-Type to JSON ---
header('Content-Type: application/json');

// --- Initialize Response Array ---
$response = ['success' => false, 'error' => 'An unknown error occurred.'];

// #uploadMC - Kiểm tra kích thước file và báo lỗi nếu vượt quá
if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > MAX_FILE_SIZE) {
    $response['error'] = 'File quá lớn. Giới hạn tải lên là ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
    echo json_encode($response);
    exit;
}

// --- Basic Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['registration_id']) || !isset($_FILES['payment_proof_image'])) {
    $response['error'] = 'Missing required data (registration_id or image).';
    echo json_encode($response);
    exit;
}

$registration_id = (int)$_POST['registration_id'];
$user_id = $_SESSION['user_id'];
$uploaded_file = $_FILES['payment_proof_image'];

// --- File Upload Logic ---
$cloudinary_result = null; // Initialize cloudinary result
try {
    // Initialize CloudinaryService
    $cloudinaryService = new CloudinaryService();
    
    // Validate uploaded file
    $cloudinaryService->validateUploadedFile($uploaded_file);

    // Database Interaction
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction(); // Start transaction

    // Create PaymentProofService instance and check if registration belongs to the user
    $paymentProofService = new PaymentProofService();
    
    // Check if registration belongs to the user
    if (!$paymentProofService->registrationBelongsToUser($registration_id, $user_id)) {
        throw new Exception('Access denied. You do not own this registration.');
    }

    // Find and update the transaction history record instead of the payment table
    $sql_find_transaction = "SELECT id, voucher_id, payment_image FROM transaction_history WHERE registration_id = :registration_id AND user_id = :user_id AND status = 'pending'";
    $stmt_find = $conn->prepare($sql_find_transaction);
    $stmt_find->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_find->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_find->execute();
    $transaction = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('No pending transaction found for this registration.');
    }

    $transaction_id = $transaction['id'];
    $voucher_id = $transaction['voucher_id'];
    $old_payment_image = $transaction['payment_image'];

    // Upload to Cloudinary
    $metadata = [
        'registration_id' => $registration_id,
        'user_id' => $user_id,
        'transaction_id' => $transaction_id
    ];
    
    $cloudinary_result = $cloudinaryService->uploadPaymentProof($uploaded_file['tmp_name'], $metadata);
    
    if (!$cloudinary_result['success']) {
        throw new Exception('Failed to upload image to Cloudinary');
    }

    // Update transaction record with Cloudinary URL and public_id
    $sql_update = "UPDATE transaction_history 
                   SET payment_image = :payment_image, 
                       payment_image_public_id = :public_id,
                       payment_confirmed = 0, 
                       payment_confirmed_at = NULL, 
                       updated_at = NOW() 
                   WHERE id = :transaction_id";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':payment_image', $cloudinary_result['secure_url'], PDO::PARAM_STR);
    $stmt_update->bindParam(':public_id', $cloudinary_result['public_id'], PDO::PARAM_STR);
    $stmt_update->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt_update->execute();

    // Delete old image from Cloudinary if exists
    if (!empty($old_payment_image) && strpos($old_payment_image, 'cloudinary.com') !== false) {
        try {
            $old_public_id = CloudinaryService::extractPublicIdFromUrl($old_payment_image);
            if ($old_public_id) {
                $cloudinaryService->deletePaymentProof($old_public_id);
            }
        } catch (Exception $e) {
            // Log error but don't fail the process
            error_log("Error deleting old Cloudinary image: " . $e->getMessage());
        }
    }
    
    // Mark device voucher as used if present
    if (isset($_SESSION['device_fingerprint']) && isset($_SESSION['order']['voucher_code'])) {
        try {
            $voucherService = new Voucher($db);
            $deviceFingerprint = $_SESSION['device_fingerprint'];
            $voucherCode = $_SESSION['order']['voucher_code'];
            
            // Mark the device voucher as used
            $voucherService->markDeviceVoucherUsed($deviceFingerprint, $voucherCode);
        } catch (Exception $e) {
            // Log error but don't stop the process
            error_log("Error marking device voucher as used: " . $e->getMessage());
        }
    }

    $conn->commit(); // Commit transaction

    // Success Response
    $response['success'] = true;
    unset($response['error']);
    $response['message'] = 'Proof uploaded successfully.';
    $response['image_url'] = $cloudinary_result['secure_url'];

} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack(); // Roll back transaction on DB error
    }
    error_log("Database error uploading payment proof: " . $e->getMessage());
    $response['error'] = 'Database error occurred.';
    
    // Clean up Cloudinary image if DB operation failed
    if ($cloudinary_result && isset($cloudinary_result['public_id'])) {
        try {
            $cloudinaryService->deletePaymentProof($cloudinary_result['public_id']);
        } catch (Exception $cleanup_error) {
            error_log("Error cleaning up Cloudinary image: " . $cleanup_error->getMessage());
        }
    }
    
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack(); // Roll back transaction on general error if needed
    }
    error_log("General error uploading payment proof: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    
    // Clean up Cloudinary image if operation failed
    if ($cloudinary_result && isset($cloudinary_result['public_id'])) {
        try {
            $cloudinaryService->deletePaymentProof($cloudinary_result['public_id']);
        } catch (Exception $cleanup_error) {
            error_log("Error cleaning up Cloudinary image: " . $cleanup_error->getMessage());
        }
    }
}

// Output the JSON Response
echo json_encode($response);
exit;
