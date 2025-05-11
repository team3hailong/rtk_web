<?php
function handle_forgot_password_request() {
    session_start();

    // Nếu người dùng đã đăng nhập, chuyển hướng họ đi
    if (isset($_SESSION['user_id'])) {
        header("Location: ../dashboard.php");
        exit();
    }

    $data = [
        'message' => $_SESSION['reset_message'] ?? null,
        'message_type' => $_SESSION['reset_message_type'] ?? 'error'
    ];

    unset($_SESSION['reset_message'], $_SESSION['reset_message_type']); // Xóa thông báo sau khi hiển thị

    return $data;
}
