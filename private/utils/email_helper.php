<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

function sendVerificationEmail($userEmail, $username, $verificationToken) {
    global $conn;
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($userEmail, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Xác nhận Email - RTK Web';
        
        // Sửa lại link để trỏ đến file auth folder
        $verificationLink = SITE_URL . "/public/pages/auth/verify-email.php?token=" . $verificationToken;
        
        $mail->Body = <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #2e7d32;">Xin chào {$username}!</h2>
                <p>Cảm ơn bạn đã đăng ký tài khoản tại RTK Web. Để hoàn tất quá trình đăng ký, vui lòng xác nhận địa chỉ email của bạn bằng cách nhấp vào nút bên dưới:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{$verificationLink}" style="background-color: #4caf50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        Xác nhận Email
                    </a>
                </div>
                
                <p>Hoặc bạn có thể copy và paste đường link sau vào trình duyệt:</p>
                <p style="word-break: break-all; color: #666;">{$verificationLink}</p>
                
                <p>Link xác nhận này sẽ hết hạn sau 24 giờ.</p>
                
                <p style="color: #666; font-size: 0.9em; margin-top: 30px;">
                    Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email này.
                </p>
            </div>
HTML;

        $mail->AltBody = "Xin chào {$username}!\n\n"
            . "Vui lòng xác nhận email của bạn bằng cách truy cập link sau:\n"
            . $verificationLink
            . "\n\nLink này sẽ hết hạn sau 24 giờ.";

        // Gửi email
        $mail->send();

        // Log thành công vào activity_logs
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values) 
                SELECT id, 'verification_email_sent', 'user', id, ?, ? 
                FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'email' => $userEmail,
                'verification_token' => substr($verificationToken, 0, 10) . '...',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt->bind_param("sss", $ip, $log_data, $userEmail);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    } catch (Exception $e) {
        // Log lỗi vào error_logs
        $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'email_sending_failed';
            // Log a generic message to DB, keep detailed message in server logs
            $error_message_db = "Failed to send email to " . $userEmail;
            $stack_trace_db = "Error details logged in server error log."; // Avoid logging full trace to DB
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
        // Log detailed error to server log
        error_log("Failed to send verification email to: $userEmail. Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false; // Indicate failure
    }
}