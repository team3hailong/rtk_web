<?php
require_once __DIR__ . '/../../config/database.php'; 
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        die('Vui lòng nhập email.');
    }

    // Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die('Email không tồn tại.');
    }

    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Tạo token reset
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", time() + 3600); // Token hết hạn sau 1 giờ

    // Lưu token vào database
    $stmt = $conn->prepare("UPDATE user SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expiry, $user_id);
    $stmt->execute();
    $stmt->close();

    // Gửi email
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Email của bạn
        $mail->Password = 'your_app_password'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'RTK System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Yêu cầu đặt lại mật khẩu';
        $mail->Body = "Nhấn vào liên kết dưới đây để đặt lại mật khẩu:<br><a href='http://localhost/rtk_web/public/pages/auth/reset_password.php?token=$token'>Đặt lại mật khẩu</a>";

        $mail->send();
        echo "Liên kết đặt lại mật khẩu đã được gửi tới email của bạn.";
    } catch (Exception $e) {
        echo "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
    }
}
