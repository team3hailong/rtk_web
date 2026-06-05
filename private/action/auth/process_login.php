<?php
// Kiểm tra xem session đã được start chưa trước khi gọi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session_config.php';
require_once __DIR__ . '/../../utils/error_handler.php';
require_once __DIR__ . '/../../classes/DeviceTracker.php';

// Tạo kết nối PDO duy nhất cho toàn bộ quá trình
$dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối PDO thất bại: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
    $device_fingerprint = $_POST['device_fingerprint'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $login_error = null;

    // --- Validation ---
    if (empty($email)) {
        $login_error = "Email không được để trống.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = "Định dạng email không hợp lệ.";
    } elseif (empty($password)) {
        $login_error = "Mật khẩu không được để trống.";
    }

    // --- Nếu không có lỗi validation cơ bản ---
    if ($login_error === null) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, email_verified FROM user WHERE email = :email AND deleted_at IS NULL");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Xác thực mật khẩu
                if (password_verify($password, $user['password'])) {
                    // Kiểm tra xem email đã được xác thực chưa
                    if (!$user['email_verified']) {
                        $login_error = "Vui lòng xác thực email của bạn trước khi đăng nhập. Kiểm tra hộp thư đến của bạn.";
                    } else {
                        // Đăng nhập thành công
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['last_activity'] = time();

                        // Xử lý chức năng ghi nhớ đăng nhập
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $hash = password_hash($token, PASSWORD_DEFAULT);
                            $expiry = date('Y-m-d H:i:s', time() + REMEMBER_ME_DURATION);
                            $remember_stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expiry) VALUES (:user_id, :token, :expiry)");
                            $remember_stmt->bindParam(':user_id', $user['id']);
                            $remember_stmt->bindParam(':token', $hash);
                            $remember_stmt->bindParam(':expiry', $expiry);
                            $remember_stmt->execute();
                            // Lưu token vào cookie
                            setcookie('remember_token', $user['id'] . ':' . $token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
                        }

                        // Ghi log hoạt động đăng nhập
                        $notify_content = 'Người dùng ' . $user['username'] . ' đã đăng nhập vào hệ thống';
                        log_activity($pdo, $user['id'], 'login', 'user', $user['id'], null, [
                            'login_time' => date('Y-m-d H:i:s'),
                            'user_agent' => $user_agent
                        ], $notify_content);

                        // Lưu thông tin thiết bị và IP
                        try {
                            $deviceTracker = new DeviceTracker($pdo);
                            $deviceTracker->trackUserDevice($user['id'], $device_fingerprint, $ip_address, $user_agent);
                            $_SESSION['device_fingerprint'] = $device_fingerprint;
                            $_SESSION['ip_address'] = $ip_address;
                        } catch (Exception $e) {
                            error_log("Error tracking device: " . $e->getMessage());
                        }

                        // Đóng statement
                        $stmt = null;
                        $remember_stmt = null;

                        header("Location: ../../../public/pages/dashboard.php");
                        exit();
                    }
                } else {
                    $login_error = "Email hoặc mật khẩu không chính xác.";
                }
            } else {
                $login_error = "Email hoặc mật khẩu không chính xác.";
            }
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $login_error = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
        }
    }

    if ($login_error !== null) {
        $_SESSION['login_error'] = $login_error;
        header("Location: ../../../public/pages/auth/login.php");
        exit();
    }
} else {
    header("Location: ../../../public/pages/auth/login.php");
    exit();
}