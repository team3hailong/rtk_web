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

// --- Constants ---
// Define upload directory relative to the public folder
define('UPLOAD_DIR_RELATIVE', '/uploads/payment_proofs/');
// Define absolute path for file operations
define('UPLOAD_DIR_ABSOLUTE', $project_root_path . '/public' . UPLOAD_DIR_RELATIVE);

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
$destination_path = null; // Initialize destination path
try {
    // Validate Uploaded File
    if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $uploaded_file['error']);
    }

    if ($uploaded_file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File exceeds maximum size limit (' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB).');
    }

    $file_mime_type = mime_content_type($uploaded_file['tmp_name']);
    $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

    // Updated validation to use defined constants
    if (!in_array($file_mime_type, ALLOWED_MIME_TYPES) || !in_array($file_extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF are allowed.');
    }

    // Create Upload Directory if it doesn't exist
    if (!is_dir(UPLOAD_DIR_ABSOLUTE)) {
        if (!mkdir(UPLOAD_DIR_ABSOLUTE, 0755, true)) {
            throw new Exception('Server error: Could not create upload directory.');
        }
    }

    // Generate Unique Filename
    $unique_filename = sprintf('reg_%d_%s.%s',
        $registration_id,
        time(),
        $file_extension
    );
    $destination_path = UPLOAD_DIR_ABSOLUTE . $unique_filename; // Assign destination path

    // Move Uploaded File
    if (!move_uploaded_file($uploaded_file['tmp_name'], $destination_path)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // Database Interaction
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction(); // Start transaction    // Create PaymentProofService instance and check if registration belongs to the user
    $paymentProofService = new PaymentProofService();
    
    // Check if registration belongs to the user
    if (!$paymentProofService->registrationBelongsToUser($registration_id, $user_id)) {
        throw new Exception('Access denied. You do not own this registration.');
    }// Find transaction history record or create if not exists
    $sql_find_transaction = "SELECT id, voucher_id, status FROM transaction_history WHERE registration_id = :registration_id AND user_id = :user_id";
    $stmt_find = $conn->prepare($sql_find_transaction);
    $stmt_find->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_find->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_find->execute();
    $transaction = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        // Tạo transaction record mới khi upload proof (cho trường hợp cần upload proof)
        $sessionKey = (isset($_SESSION['is_renewal']) && $_SESSION['is_renewal']) ? 'renewal' : 'order';
        
        // Get registration details
        $sql_reg = "SELECT package_id, location_id, num_account, total_price FROM registration WHERE id = :registration_id AND user_id = :user_id";
        $stmt_reg = $conn->prepare($sql_reg);
        $stmt_reg->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt_reg->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_reg->execute();
        $registration = $stmt_reg->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            throw new Exception('Registration not found.');
        }
        
        // Calculate final amount (lấy từ session hoặc registration total_price)
        $total_price = (float)$registration['total_price'];
        
        // Lấy voucher info từ session (có thể null nếu không áp dụng voucher)
        $voucher_id = isset($_SESSION[$sessionKey]) && isset($_SESSION[$sessionKey]['voucher_id']) 
                      ? $_SESSION[$sessionKey]['voucher_id'] 
                      : null;
        
        $discount_amount = isset($_SESSION[$sessionKey]) && isset($_SESSION[$sessionKey]['voucher_discount']) 
                           ? $_SESSION[$sessionKey]['voucher_discount'] 
                           : 0;
        
        $final_amount = max(0, $total_price - $discount_amount);
        
        // Xác định transaction_type
        $transaction_type = $sessionKey === 'renewal' ? 'renewal' : 'purchase';
        $payment_method = 'Chuyển khoản ngân hàng';
        
        // Insert new transaction (chỉ các cột có trong bảng transaction_history)
        $sql_insert = "INSERT INTO transaction_history 
                       (user_id, registration_id, voucher_id, transaction_type, 
                        amount, status, payment_method, payment_confirmed, 
                        created_at, updated_at) 
                       VALUES 
                       (:user_id, :registration_id, :voucher_id, :transaction_type, 
                        :amount, 'pending', :payment_method, 0, 
                        NOW(), NOW())";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':transaction_type', $transaction_type, PDO::PARAM_STR);
        $stmt_insert->bindParam(':amount', $final_amount, PDO::PARAM_STR);
        $stmt_insert->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);
        $stmt_insert->execute();
        
        $transaction_id = $conn->lastInsertId();
    } else {
        $transaction_id = $transaction['id'];
        $voucher_id = $transaction['voucher_id'];
    }

    // Check if voucher has auto_approve enabled
    $auto_approve = false;
    if ($voucher_id) {
        $sql_voucher = "SELECT auto_approve FROM voucher WHERE id = :voucher_id";
        $stmt_voucher = $conn->prepare($sql_voucher);
        $stmt_voucher->bindParam(':voucher_id', $voucher_id, PDO::PARAM_INT);
        $stmt_voucher->execute();
        $voucher = $stmt_voucher->fetch(PDO::FETCH_ASSOC);
        $auto_approve = $voucher && $voucher['auto_approve'] == 1;
    }

    // Update transaction record with payment image
    // If auto_approve, set status='completed' and payment_confirmed=1
    if ($auto_approve) {
        $sql_update = "UPDATE transaction_history 
                       SET payment_image = :payment_image, 
                           status = 'completed',
                           payment_confirmed = 1, 
                           payment_confirmed_at = NOW(), 
                           updated_at = NOW() 
                       WHERE id = :transaction_id";
        error_log("[UPLOAD_PROOF] Auto-approve enabled for voucher $voucher_id, setting status=completed");
    } else {
        $sql_update = "UPDATE transaction_history 
                       SET payment_image = :payment_image, 
                           payment_confirmed = 0, 
                           payment_confirmed_at = NULL, 
                           updated_at = NOW() 
                       WHERE id = :transaction_id";
    }
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':payment_image', $unique_filename, PDO::PARAM_STR);
    $stmt_update->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt_update->execute();
    
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

    // If auto_approve, create accounts automatically
    if ($auto_approve) {
        error_log("[UPLOAD_PROOF] Starting auto account creation for registration $registration_id");
        
        require_once $project_root_path . '/private/classes/purchase/AutoAccountCreator.php';
        $accountCreator = new AutoAccountCreator();
        
        $result = $accountCreator->createAccountsForRegistration($registration_id);
        error_log("[UPLOAD_PROOF] createAccountsForRegistration result: " . json_encode($result));
        
        if (!$result['success']) {
            error_log("[AUTO_ACCOUNT] Failed to create accounts for registration $registration_id: " . $result['error']);
            // Không throw exception, vẫn cho hoàn tất upload
        } else {
            error_log("[AUTO_ACCOUNT] Successfully created " . count($result['accounts']) . " accounts for registration $registration_id");
        }
    }

    // Success Response
    $response['success'] = true;
    unset($response['error']);
    $response['message'] = 'Proof uploaded successfully.';
    $response['auto_approved'] = $auto_approve; // Thêm thông tin auto_approve

} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack(); // Roll back transaction on DB error
    }
    error_log("Database error uploading payment proof: " . $e->getMessage());
    $response['error'] = 'Database error occurred.';
    // Clean up uploaded file if DB operation failed
    if ($destination_path && file_exists($destination_path)) {
        unlink($destination_path);
    }
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack(); // Roll back transaction on general error if needed
    }
    error_log("General error uploading payment proof: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    // Clean up uploaded file if operation failed
    if ($destination_path && file_exists($destination_path)) {
        unlink($destination_path);
    }
}

// Output the JSON Response
echo json_encode($response);
exit;
