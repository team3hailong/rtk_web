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
        // Chọn phương thức bảo mật phù hợp dựa trên cổng
        if (SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL cổng 465
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS cổng 587 
        }
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0; // Tắt debug mode

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
        $notify_content = 'Đã gửi email xác thực cho: ' . $userEmail;
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                SELECT id, 'verification_email_sent', 'user', id, ?, ?, ? 
                FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'email' => $userEmail,
                'verification_token' => substr($verificationToken, 0, 10) . '...',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt->bind_param("ssss", $ip, $log_data, $notify_content, $userEmail);
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
        // Log detailed error to server log        error_log("Failed to send verification email to: $userEmail. Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false; // Indicate failure
    }
}

/**
 * Gửi email đặt lại mật khẩu với token đặt lại
 * 
 * @param string $userEmail Email của người dùng
 * @param string $username Tên người dùng
 * @param string $resetToken Token đặt lại mật khẩu
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendPasswordResetEmail($userEmail, $username, $resetToken) {
    global $conn;
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Sử dụng SSL thay vì STARTTLS
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0; // Tắt debug mode

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($userEmail, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Yêu Cầu Đặt Lại Mật Khẩu - RTK Web';
        
        // Link đặt lại mật khẩu
        $resetLink = SITE_URL . "/public/pages/auth/reset_password.php?token=" . $resetToken;
        
        $mail->Body = <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #2e7d32;">Xin chào {$username}!</h2>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Để đặt lại mật khẩu, vui lòng nhấp vào nút bên dưới:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{$resetLink}" style="background-color: #4caf50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        Đặt Lại Mật Khẩu
                    </a>
                </div>
                
                <p>Hoặc bạn có thể copy và paste đường link sau vào trình duyệt:</p>
                <p style="word-break: break-all; color: #666;">{$resetLink}</p>
                
                <p>Link đặt lại mật khẩu này sẽ hết hạn sau 24 giờ.</p>
                
                <p style="color: #666; font-size: 0.9em; margin-top: 30px;">
                    Nếu bạn không yêu cầu đặt lại mật khẩu, bạn có thể bỏ qua email này. Tài khoản của bạn vẫn an toàn.
                </p>
            </div>
HTML;

        $mail->AltBody = "Xin chào {$username}!\n\n"
            . "Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Để đặt lại mật khẩu, vui lòng truy cập link sau:\n"
            . $resetLink
            . "\n\nLink này sẽ hết hạn sau 24 giờ.\n\n"
            . "Nếu bạn không yêu cầu đặt lại mật khẩu, bạn có thể bỏ qua email này. Tài khoản của bạn vẫn an toàn.";

        // Gửi email
        $mail->send();        // Log thành công vào activity_logs
        $notify_content = 'Đã gửi email đặt lại mật khẩu cho: ' . $userEmail;
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                SELECT id, 'password_reset_email_sent', 'user', id, ?, ?, ? 
                FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'email' => $userEmail,
                'reset_token' => substr($resetToken, 0, 10) . '...',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt->bind_param("ssss", $ip, $log_data, $notify_content, $userEmail);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    } catch (Exception $e) {
        // Log lỗi vào error_logs
        $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'password_reset_email_failed';
            // Log thông điệp chung vào DB, giữ thông điệp chi tiết trong server logs
            $error_message_db = "Failed to send password reset email to " . $userEmail;
            $stack_trace_db = "Error details logged in server error log."; // Tránh ghi đầy đủ stack trace
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
        // Ghi chi tiết lỗi vào server log
        error_log("Failed to send password reset email to: $userEmail. Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false; // Báo lỗi
    }
}

/**
 * Generates a random 6-digit OTP code
 * 
 * @return string 6-digit OTP code
 */
function generateOTP() {
    return str_pad(strval(mt_rand(0, 999999)), 6, '0', STR_PAD_LEFT);
}

/**
 * Sends an email verification OTP
 * 
 * @param string $userEmail Email của người dùng
 * @param string $username Tên người dùng
 * @param string $otpCode Mã OTP để xác thực
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendVerificationOTP($userEmail, $username, $otpCode) {
    global $conn;
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        // Chọn phương thức bảo mật phù hợp dựa trên cổng
        if (SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL cổng 465
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS cổng 587 
        }
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0; // Tắt debug mode

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($userEmail, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Mã Xác Thực Email - RTK Web';
        
        $mail->Body = <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #2e7d32;">Xin chào {$username}!</h2>
                <p>Cảm ơn bạn đã đăng ký tài khoản tại RTK Web. Để hoàn tất quá trình đăng ký, vui lòng sử dụng mã xác thực sau:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; font-size: 24px; font-weight: bold; letter-spacing: 5px;">
                        {$otpCode}
                    </div>
                </div>
                
                <p>Mã xác thực này sẽ hết hạn sau 15 phút.</p>
                
                <p style="color: #666; font-size: 0.9em; margin-top: 30px;">
                    Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email này.
                </p>
            </div>
HTML;

        $mail->AltBody = "Xin chào {$username}!\n\n"
            . "Vui lòng sử dụng mã xác thực sau để xác minh email của bạn: {$otpCode}\n\n"
            . "Mã này sẽ hết hạn sau 15 phút.";

        // Gửi email
        $mail->send();

        // Log thành công vào activity_logs
        $notify_content = 'Đã gửi mã xác thực OTP cho: ' . $userEmail;
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                SELECT id, 'verification_otp_sent', 'user', id, ?, ?, ? 
                FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'email' => $userEmail,
                'otp_sent' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt->bind_param("ssss", $ip, $log_data, $notify_content, $userEmail);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    } catch (Exception $e) {
        // Log lỗi vào error_logs
        $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'email_otp_sending_failed';
            // Log a generic message to DB, keep detailed message in server logs
            $error_message_db = "Failed to send OTP verification email to " . $userEmail;
            $stack_trace_db = "Error details logged in server error log."; // Avoid logging full trace to DB
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
        // Log detailed error to server log
        error_log("Failed to send OTP verification email to: $userEmail. Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false; // Indicate failure
    }
}

/**
 * Sends a password reset OTP
 * 
 * @param string $userEmail Email của người dùng
 * @param string $username Tên người dùng
 * @param string $otpCode Mã OTP để đặt lại mật khẩu
 * @return bool True nếu gửi thành công, False nếu thất bại
 */
function sendPasswordResetOTP($userEmail, $username, $otpCode) {
    global $conn;
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        // Chọn phương thức bảo mật phù hợp dựa trên cổng
        if (SMTP_PORT == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL cổng 465
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS cổng 587 
        }
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0; // Tắt debug mode

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($userEmail, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Mã Đặt Lại Mật Khẩu - RTK Web';
        
        $mail->Body = <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #2e7d32;">Xin chào {$username}!</h2>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Vui lòng sử dụng mã xác thực sau để đặt lại mật khẩu:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; font-size: 24px; font-weight: bold; letter-spacing: 5px;">
                        {$otpCode}
                    </div>
                </div>
                
                <p>Mã xác thực này sẽ hết hạn sau 15 phút.</p>
                
                <p style="color: #666; font-size: 0.9em; margin-top: 30px;">
                    Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này. Tài khoản của bạn vẫn an toàn.
                </p>
            </div>
HTML;

        $mail->AltBody = "Xin chào {$username}!\n\n"
            . "Vui lòng sử dụng mã xác thực sau để đặt lại mật khẩu của bạn: {$otpCode}\n\n"
            . "Mã này sẽ hết hạn sau 15 phút.";

        // Gửi email
        $mail->send();

        // Log thành công vào activity_logs
        $notify_content = 'Đã gửi mã OTP đặt lại mật khẩu cho: ' . $userEmail;
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                SELECT id, 'password_reset_otp_sent', 'user', id, ?, ?, ? 
                FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $log_data = json_encode([
                'email' => $userEmail,
                'otp_sent' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $stmt->bind_param("ssss", $ip, $log_data, $notify_content, $userEmail);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    } catch (Exception $e) {
        // Log lỗi vào error_logs
        $sql = "INSERT INTO error_logs (error_type, error_message, stack_trace, ip_address) VALUES (?, ?, ?, ?)";
        $stmt_error = $conn->prepare($sql);
        if ($stmt_error) {
            $error_type = 'password_reset_otp_failed';
            // Log thông điệp chung vào DB, giữ thông điệp chi tiết trong server logs
            $error_message_db = "Failed to send password reset OTP to " . $userEmail;
            $stack_trace_db = "Error details logged in server error log.";
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt_error->bind_param("ssss", $error_type, $error_message_db, $stack_trace_db, $ip);
            $stmt_error->execute();
            $stmt_error->close();
        }
        // Ghi chi tiết lỗi vào server log
        error_log("Failed to send password reset OTP to: $userEmail. Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false; // Báo lỗi
    }
}