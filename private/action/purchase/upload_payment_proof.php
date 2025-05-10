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
    $conn->beginTransaction(); // Start transaction

    // Check if the registration belongs to the current user
    $sql_check = "SELECT user_id, status FROM registration WHERE id = :registration_id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $registration_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$registration_data) {
        throw new Exception('Registration not found.');
    }
    if ($registration_data['user_id'] != $user_id) {
        throw new Exception('Access denied. You do not own this registration.');
    }    // Find and update the transaction history record instead of the payment table
    $sql_find_transaction = "SELECT id, voucher_id FROM transaction_history WHERE registration_id = :registration_id AND user_id = :user_id AND status = 'pending'";
    $stmt_find = $conn->prepare($sql_find_transaction);
    $stmt_find->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_find->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_find->execute();
    $transaction = $stmt_find->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('No pending transaction found for this registration.');
    }
    
    $transaction_id = $transaction['id'];

    // Update transaction record with payment image
    $sql_update = "UPDATE transaction_history 
                   SET payment_image = :payment_image, 
                       payment_confirmed = 0, 
                       payment_confirmed_at = NULL, 
                       updated_at = NOW() 
                   WHERE id = :transaction_id";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':payment_image', $unique_filename, PDO::PARAM_STR);
    $stmt_update->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt_update->execute();

    $conn->commit(); // Commit transaction

    // Success Response
    $response['success'] = true;
    unset($response['error']);
    $response['message'] = 'Proof uploaded successfully.';

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
