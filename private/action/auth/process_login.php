<?php
// Kiểm tra xem session đã được start chưa trước khi gọi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/error_handler.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
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
        // Chuẩn bị câu lệnh để lấy thông tin user dựa trên email
        $sql = "SELECT id, username, password, email_verified FROM user WHERE email = ? AND deleted_at IS NULL";        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            log_error($conn, 'auth', "Login prepare statement failed: " . $conn->error, null, null);
            $login_error = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

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

                        // Ghi log hoạt động đăng nhập
                        // Log successful login
                        $notify_content = 'Người dùng ' . $user['username'] . ' đã đăng nhập vào hệ thống';
                        log_activity($conn, $user['id'], 'login', 'user', $user['id'], null, [
                            'login_time' => date('Y-m-d H:i:s'),
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                        ], $notify_content);

                        // Đóng statement và kết nối
                        $stmt->close();
                        $conn->close();

                        header("Location: ../../../public/pages/dashboard.php");
                        exit();
                    }
                } else {
                    $login_error = "Email hoặc mật khẩu không chính xác.";
                }
            } else {
                $login_error = "Email hoặc mật khẩu không chính xác.";
            }
            $stmt->close();
        }
    }

    if ($login_error !== null) {
        $_SESSION['login_error'] = $login_error;
        $conn->close();
        header("Location: ../../../public/pages/auth/login.php");
        exit();
    }

    $conn->close();
} else {
    header("Location: ../../../public/pages/auth/login.php");
    exit();
}