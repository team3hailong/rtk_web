<?php
session_start();

// --- Project Root Path ---
$project_root_path = dirname(dirname(dirname(__FILE__)));

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
require_once $project_root_path . '/config/config.php';
require_once $project_root_path . '/classes/Database.php';

// --- Constants ---
// Define upload directory relative to the public folder
define('UPLOAD_DIR_RELATIVE', '/uploads/payment_proofs/');
// Define absolute path for file operations - Corrected path
define('UPLOAD_DIR_ABSOLUTE', dirname($project_root_path) . '/public' . UPLOAD_DIR_RELATIVE);
// Allowed file types and max size (e.g., 5MB)
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// --- IMPORTANT: Set Content-Type to JSON ---
header('Content-Type: application/json');

// --- Initialize Response Array ---
$response = ['success' => false, 'error' => 'An unknown error occurred.'];

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

    if (!in_array($file_mime_type, ALLOWED_MIME_TYPES) || !in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
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
    }

    // Check if a payment record already exists
    $sql_find_payment = "SELECT id FROM payment WHERE registration_id = :registration_id";
    $stmt_find = $conn->prepare($sql_find_payment);
    $stmt_find->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_find->execute();
    $existing_payment_id = $stmt_find->fetchColumn();

    if ($existing_payment_id) {
        // Update existing payment record
        $sql_update = "UPDATE payment SET payment_image = :payment_image, confirmed = 0, confirmed_at = NULL, updated_at = NOW() WHERE id = :payment_id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':payment_image', $unique_filename, PDO::PARAM_STR);
        $stmt_update->bindParam(':payment_id', $existing_payment_id, PDO::PARAM_INT);
        $stmt_update->execute();
    } else {
        // Insert new payment record
        $sql_insert = "INSERT INTO payment (registration_id, payment_image, confirmed, created_at, updated_at)
                       VALUES (:registration_id, :payment_image, 0, NOW(), NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':payment_image', $unique_filename, PDO::PARAM_STR);
        $stmt_insert->execute();
    }

    // --- NEW: Update transaction_history ---
    $sql_update_transaction = "UPDATE transaction_history
                               SET updated_at = NOW()
                               WHERE registration_id = :registration_id
                               AND status = 'pending'"; // Only update pending transactions
    $stmt_update_trans = $conn->prepare($sql_update_transaction);
    $stmt_update_trans->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
    $stmt_update_trans->execute();
    // --- END NEW ---

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

?>
