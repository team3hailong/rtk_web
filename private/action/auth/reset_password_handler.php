<?php
function handle_reset_password_request() {
    session_start();

    // Nếu người dùng đã đăng nhập, chuyển hướng họ đi
    if (isset($_SESSION['user_id'])) {
        header("Location: ../dashboard.php");
        exit();
    }

    // Get token từ URL
    $token = $_GET['token'] ?? '';
    $data = [
        'token' => '',
        'message' => '',
        'status' => '',
        'show_form' => false
    ];

    if (empty($token)) {
        // Nếu không có token, hiển thị thông báo lỗi
        $data['message'] = 'Token đặt lại mật khẩu không hợp lệ.';
        $data['status'] = 'error';
    } else {
        // Chuyển token vào hidden input để gửi với form
        $data['token'] = $token;
        $data['show_form'] = true;
        
        // Nếu có thông báo lỗi từ process_reset_password.php
        if (isset($_SESSION['reset_error'])) {
            $data['message'] = $_SESSION['reset_error'];
            $data['status'] = 'error';
            unset($_SESSION['reset_error']);
        }
    }

    return $data;
}
?>
