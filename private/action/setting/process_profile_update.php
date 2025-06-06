<?php
// --- Project Root Path ---
$project_root_path = dirname(dirname(dirname(__DIR__))); // Adjust path as needed

// --- Base URL ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']);
$base_project_dir = '';
if (strpos($script_dir, '/private/') !== false) {
    $base_project_dir = substr($script_dir, 0, strpos($script_dir, '/private/'));
}
$base_url = rtrim($protocol . $domain . $base_project_dir, '/');
$profile_page_url = $base_url . '/public/pages/setting/profile.php';

// --- Include Required Files ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';

// --- Database Connection & User Data Fetch ---
function fetchUserData($user_id) {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        return ['error' => 'Lỗi kết nối cơ sở dữ liệu.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT username, email, phone 
                              FROM user 
                              WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['error' => 'Không tìm thấy thông tin người dùng.'];
        }
        
        return ['data' => $user];
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return ['error' => 'Lỗi khi truy vấn thông tin người dùng.'];
    } finally {
        $db->close();
    }
}

// Fetch user data if not processing form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $result = fetchUserData($_SESSION['user_id']);
    if (isset($result['data'])) {
        $_SESSION['user_data'] = $result['data'];
    }
    if (isset($result['error'])) {
        $_SESSION['profile_error'] = $result['error'];
    }
    header('Location: ' . $profile_page_url);
    exit;
}

// --- Security Checks ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['profile_error'] = 'User not authenticated.';
    header('Location: ' . $base_url . '/public/pages/auth/login.php'); // Redirect to login
    exit;
}

// --- Get Data from POST ---
$user_id = $_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
// Removed is_company and company fields since they've been removed from the form

// --- Basic Validation ---
$errors = [];
if (empty($username)) {
    $errors[] = "Tên người dùng không được để trống.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Địa chỉ email không hợp lệ.";
}
if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
    $errors[] = "Số điện thoại không hợp lệ (phải có 10-11 chữ số).";
}

if (!empty($errors)) {
    $_SESSION['profile_error'] = implode('<br>', $errors);
    header('Location: ' . $profile_page_url);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }        // Check if email is already taken by another user
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception("Địa chỉ email này đã được sử dụng bởi tài khoản khác.");
            }
        }

        // Check if phone is already taken by another user
        if (!empty($phone)) {
            $stmt = $pdo->prepare("SELECT id FROM user WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception("Số điện thoại này đã được sử dụng bởi tài khoản khác.");
            }
        }        // Prepare the update statement
        $sql = "UPDATE user SET
                    username = :username,
                    email = :email,
                    phone = :phone,
                    updated_at = NOW()
                WHERE id = :user_id AND deleted_at IS NULL";

        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        // Execute the update
        $stmt->execute();
          // Log activity
        $notify_content = 'Cập nhật thông tin hồ sơ người dùng: ' . $username;
        $sql_log = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, new_values, notify_content, created_at) 
                   VALUES (:user_id, 'update', 'user', :entity_id, :new_values, :notify_content, NOW())";
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_log->bindParam(':entity_id', $user_id, PDO::PARAM_INT);
        $profile_details = json_encode(["username" => $username, "email" => $email, "phone" => $phone], JSON_UNESCAPED_UNICODE);
        $stmt_log->bindParam(':new_values', $profile_details, PDO::PARAM_STR);
        $stmt_log->bindParam(':notify_content', $notify_content, PDO::PARAM_STR);
        $stmt_log->execute();

        // After successful update, fetch fresh user data
        $result = fetchUserData($user_id);
        if (isset($result['data'])) {
            $_SESSION['user_data'] = $result['data']; // Update session data
            $_SESSION['profile_message'] = "Hồ sơ đã được cập nhật thành công.";
        } else {
            // Handle case where fetching updated data fails, though unlikely after successful update
             $_SESSION['profile_error'] = $result['error'] ?? 'Không thể tải lại dữ liệu người dùng sau khi cập nhật.';
        }

    } catch (PDOException $e) {
        error_log("Profile update PDO error: " . $e->getMessage());
        // Check for duplicate phone number error (MySQL error code 1062)
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'phone') !== false) {
             $_SESSION['profile_error'] = "Số điện thoại này đã được sử dụng bởi tài khoản khác.";
        } else {
             $_SESSION['profile_error'] = "Có lỗi xảy ra khi cập nhật hồ sơ (DB).";
        }
    } catch (Exception $e) {
        // Log detailed error to server log
        error_log("Profile update general error: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString());
        // Show generic error to user
        $_SESSION['profile_error'] = "Đã xảy ra lỗi không mong muốn khi cập nhật hồ sơ. Vui lòng thử lại.";
    } finally {
        if (isset($db)) {
            $db->close();
        }
    }
}

header('Location: ' . $profile_page_url);
exit;
?>
