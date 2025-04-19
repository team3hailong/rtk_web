<?php
// filepath: e:\Application\laragon\www\surveying_account\private\action\auth\process_login.php
session_start();
require_once __DIR__ . '/../../config/database.php'; 

// Hàm ghi log hoạt động (ví dụ đơn giản)
function log_activity($conn, $user_id, $action, $entity_type, $entity_id) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $action, $entity_type, $entity_id, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    } else {
        // Xử lý lỗi nếu không chuẩn bị được câu lệnh (ví dụ: ghi vào file log lỗi)
        error_log("Failed to prepare statement for activity log: " . $conn->error);
    }
}


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
        $sql = "SELECT id, username, password FROM user WHERE email = ? AND deleted_at IS NULL"; // Chỉ lấy user chưa bị xóa
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            // Xử lý lỗi nghiêm trọng (ví dụ: log lỗi, hiển thị trang lỗi chung)
             error_log("Login prepare statement failed: " . $conn->error);
             $login_error = "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Xác thực mật khẩu
                if (password_verify($password, $user['password'])) {
                    // Đăng nhập thành công
                    session_regenerate_id(true); // Bảo mật: tạo session ID mới

                    // Lưu thông tin cần thiết vào session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    // Có thể lưu thêm thông tin khác nếu cần (ví dụ: role)

                    // Ghi log hoạt động đăng nhập
                    log_activity($conn, $user['id'], 'login', 'user', $user['id']);

                    // Đóng statement và kết nối
                    $stmt->close();
                    $conn->close();

                    // Chuyển hướng đến trang dashboard hoặc trang chính
                    header("Location: ../../../public/pages/dashboard.php"); // Thay đổi đường dẫn nếu cần
                    exit();
                } else {
                    // Sai mật khẩu
                    $login_error = "Email hoặc mật khẩu không chính xác.";
                }
            } else {
                // Không tìm thấy user với email này
                $login_error = "Email hoặc mật khẩu không chính xác.";
            }
            $stmt->close();
        }
    }

    // --- Nếu có lỗi hoặc đăng nhập thất bại ---
    if ($login_error !== null) {
        $_SESSION['login_error'] = $login_error;
        $conn->close();
        header("Location: ../../../public/pages/auth/login.php"); // Chuyển hướng về trang đăng nhập
        exit();
    }

    $conn->close();

} else {
    // Nếu không phải POST request, chuyển hướng về trang đăng nhập
    header("Location: ../../../public/pages/auth/login.php");
    exit();
}
?>