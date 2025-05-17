# Tài liệu Hệ thống Xác thực (Authentication)

## 1. Chi tiết chức năng và trang

### 1.1. Đăng nhập (Login)
- **Tệp tin UI**: `public/pages/auth/login.php`
- **Tệp tin xử lý**: `private/action/auth/process_login.php`
- **Chức năng**: Xác thực người dùng thông qua email và mật khẩu, tạo phiên đăng nhập

### 1.2. Đăng ký (Register)
- **Tệp tin UI**: `public/pages/auth/register.php`
- **Tệp tin xử lý**: `private/action/auth/process_register.php`
- **Chức năng**: Tạo tài khoản mới với thông tin người dùng, hỗ trợ tài khoản cá nhân và công ty, tích hợp với hệ thống giới thiệu (referral)

### 1.3. Xác thực Email
- **Tệp tin UI**: `public/pages/auth/verify-email.php`
- **Tệp tin xử lý**: `private/action/auth/verify-email.php`
- **Chức năng**: Xác thực địa chỉ email của người dùng sau khi đăng ký

### 1.4. Quên mật khẩu
- **Tệp tin UI**: `public/pages/auth/forgot_password.php`, `public/pages/auth/reset_password.php`
- **Tệp tin xử lý**: `private/action/auth/process_forgot_password.php`, `private/action/auth/process_reset_password.php`
- **Chức năng**: Cho phép người dùng đặt lại mật khẩu thông qua liên kết gửi đến email

### 1.5. Đăng xuất
- **Tệp tin UI**: `public/pages/auth/logout.php`
- **Tệp tin xử lý**: `private/action/auth/process_logout.php`
- **Chức năng**: Kết thúc phiên làm việc của người dùng, xóa dữ liệu phiên

## 2. Điểm quan trọng hình thành chức năng

### 2.1. Bảo mật
- **CSRF Protection**: Tất cả form đều được bảo vệ bằng CSRF token (`private/utils/csrf_helper.php`)
  ```php
  // Tạo CSRF token và đưa vào form
  function generate_csrf_input() {
      $token = generate_csrf_token();
      return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
  }
  
  // Xác thực CSRF token
  function validate_csrf_token($token) {
      if (empty($token) || !isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
          return false;
      }
      
      if (!isset($_SESSION['csrf_tokens'][$token])) {
          return false;
      }
      
      // Kiểm tra thời gian hết hạn
      if ($_SESSION['csrf_tokens'][$token] < time()) {
          unset($_SESSION['csrf_tokens'][$token]);
          return false;
      }
      
      // Token hợp lệ, xóa token để tránh sử dụng lại (one-time use)
      unset($_SESSION['csrf_tokens'][$token]);
      return true;
  }
  ```

- **Mã hóa mật khẩu**: Sử dụng `password_hash()` và `password_verify()` để lưu trữ và xác thực mật khẩu an toàn
  ```php
  // Khi đăng ký - mã hóa mật khẩu
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  
  // Khi đăng nhập - xác thực mật khẩu
  if (password_verify($password, $user['password'])) {
      // Mật khẩu chính xác
  }
  ```

- **Prepared Statements**: Sử dụng để ngăn chặn SQL Injection
  ```php
  $stmt = $conn->prepare("SELECT id, username, password, email_verified FROM user WHERE email = ? AND deleted_at IS NULL");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  ```

- **Xác thực Email**: Yêu cầu người dùng xác thực email trước khi cho phép đăng nhập
  ```php
  // Kiểm tra trạng thái xác thực email
  if (!$user['email_verified']) {
      $login_error = "Vui lòng xác thực email của bạn trước khi đăng nhập. Kiểm tra hộp thư đến của bạn.";
  }
  ```

### 2.2. Cơ sở dữ liệu
- **Bảng user**: Lưu thông tin người dùng, mật khẩu đã mã hóa, trạng thái xác thực email
  ```sql
  CREATE TABLE `user` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `is_company` tinyint(1) DEFAULT 0,
    `company_name` varchar(100) DEFAULT NULL,
    `tax_code` varchar(20) DEFAULT NULL,
    `tax_registered` tinyint(1) DEFAULT NULL,
    `email_verified` tinyint(1) DEFAULT 0,
    `email_verify_token` varchar(100) DEFAULT NULL,
    `referral_code` varchar(20) DEFAULT NULL,
    `referred_by` int(11) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `phone` (`phone`),
    UNIQUE KEY `referral_code` (`referral_code`),
    KEY `referred_by` (`referred_by`),
    CONSTRAINT `user_ibfk_1` FOREIGN KEY (`referred_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
  ```

- **Bảng password_resets**: Lưu token đặt lại mật khẩu và thời gian hết hạn
  ```sql
  CREATE TABLE `password_resets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `token` varchar(255) NOT NULL,
    `expires_at` timestamp NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
  ```

### 2.3. Quản lý phiên
- Sử dụng `session_start()` để khởi tạo và duy trì phiên làm việc
  ```php
  // Kiểm tra xem session đã được start chưa trước khi gọi
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  ```

- Tạo mới ID phiên khi đăng nhập thành công (`session_regenerate_id()`) để ngăn chặn session fixation
  ```php
  // Đăng nhập thành công
  session_regenerate_id(true); // Tạo mới session ID và xóa session cũ
  
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['username'] = $user['username'];
  ```

### 2.4. Ghi log
- Ghi log tất cả hoạt động xác thực vào bảng `activity_logs`
  ```php
  // Log successful login
  $notify_content = 'Người dùng ' . $user['username'] . ' đã đăng nhập vào hệ thống';
  log_activity($conn, $user['id'], 'login', 'user', $user['id'], null, [
      'login_time' => date('Y-m-d H:i:s'),
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
  ], $notify_content);
  ```

- Ghi log lỗi vào bảng `error_logs` để theo dõi và khắc phục sự cố
  ```php
  // Log error
  log_error($conn, 'auth', "Failed to send password reset email to: $email", null, $user_id);
  ```
  
- Cấu trúc bảng `activity_logs`
  ```sql
  CREATE TABLE `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `entity_type` varchar(50) NOT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `old_values` text DEFAULT NULL,
    `new_values` text DEFAULT NULL,
    `notify_content` text DEFAULT NULL,
    `has_read` tinyint(1) DEFAULT 0,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
  ```
  
- Cấu trúc bảng `error_logs`
  ```sql
  CREATE TABLE `error_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `error_type` varchar(50) NOT NULL,
    `error_message` text NOT NULL,
    `stack_trace` text DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
  ```

## 3. Các luồng xử lý của chức năng

### 3.1. Luồng đăng nhập
1. Người dùng nhập email và mật khẩu
2. Hệ thống kiểm tra tính hợp lệ của dữ liệu đầu vào
   ```php
   // Validation
   if (empty($email)) {
       $login_error = "Email không được để trống.";
   } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $login_error = "Định dạng email không hợp lệ.";
   } elseif (empty($password)) {
       $login_error = "Mật khẩu không được để trống.";
   }
   ```
3. Tìm kiếm người dùng trong cơ sở dữ liệu bằng email
   ```php
   $sql = "SELECT id, username, password, email_verified FROM user WHERE email = ? AND deleted_at IS NULL";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("s", $email);
   $stmt->execute();
   $result = $stmt->get_result();
   
   if ($result->num_rows === 1) {
       $user = $result->fetch_assoc();
       // Tiếp tục xử lý
   }
   ```
4. Xác thực mật khẩu bằng `password_verify()`
   ```php
   if (password_verify($password, $user['password'])) {
       // Mật khẩu đúng, tiếp tục kiểm tra
   } else {
       $login_error = "Email hoặc mật khẩu không chính xác.";
   }
   ```
5. Kiểm tra email đã được xác thực chưa
   ```php
   if (!$user['email_verified']) {
       $login_error = "Vui lòng xác thực email của bạn trước khi đăng nhập. Kiểm tra hộp thư đến của bạn.";
   } else {
       // Email đã xác thực, tiếp tục đăng nhập
   }
   ```
6. Nếu thành công:
   - Tạo phiên mới
   - Ghi log hoạt động
   - Chuyển hướng đến trang dashboard
   ```php
   // Đăng nhập thành công
   session_regenerate_id(true);

   $_SESSION['user_id'] = $user['id'];
   $_SESSION['username'] = $user['username'];

   // Ghi log hoạt động đăng nhập
   $notify_content = 'Người dùng ' . $user['username'] . ' đã đăng nhập vào hệ thống';
   log_activity($conn, $user['id'], 'login', 'user', $user['id'], null, [
       'login_time' => date('Y-m-d H:i:s'),
       'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
   ], $notify_content);

   header("Location: ../../../public/pages/dashboard.php");
   exit();
   ```
7. Nếu thất bại:
   - Hiển thị thông báo lỗi
   - Ghi log lỗi
   ```php
   if ($login_error !== null) {
       $_SESSION['login_error'] = $login_error;
       $conn->close();
       header("Location: ../../../public/pages/auth/login.php");
       exit();
   }
   ```

### 3.2. Luồng đăng ký
1. Người dùng nhập thông tin tài khoản
2. Hệ thống kiểm tra tính hợp lệ của dữ liệu đầu vào
   ```php
   // Validation
   if (empty($username)) {
       $errors[] = "Tên người dùng không được để trống.";
   }
   if (empty($email)) {
       $errors[] = "Email không được để trống.";
   } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $errors[] = "Định dạng email không hợp lệ.";
   }
   if (empty($phone)) {
       $errors[] = "Số điện thoại không được để trống.";
   } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Số điện thoại phải có 10 hoặc 11 chữ số.";
   }
   if (empty($password)) {
       $errors[] = "Mật khẩu không được để trống.";
   } elseif (strlen($password) < 6) {
       $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
   }
   if ($password !== $confirm_password) {
       $errors[] = "Mật khẩu và xác nhận mật khẩu không khớp.";
   }
   if ($is_company && empty($tax_code)) {
        $errors[] = "Mã số thuế không được để trống đối với công ty.";
   }
   ```
3. Kiểm tra email và số điện thoại chưa được sử dụng
   ```php
   // Kiểm tra Email
   $stmt_check = $conn->prepare("SELECT id FROM user WHERE email = ?");
   $stmt_check->bind_param("s", $email);
   $stmt_check->execute();
   $stmt_check->store_result();
   if ($stmt_check->num_rows > 0) {
       $errors[] = "Email này đã được sử dụng.";
   }
   
   // Kiểm tra Số điện thoại
   $stmt_check = $conn->prepare("SELECT id FROM user WHERE phone = ?");
   $stmt_check->bind_param("s", $phone);
   $stmt_check->execute();
   $stmt_check->store_result();
   if ($stmt_check->num_rows > 0) {
       $errors[] = "Số điện thoại này đã được sử dụng.";
   }
   ```
4. Mã hóa mật khẩu
   ```php
   $hashed_password = password_hash($password, PASSWORD_DEFAULT);
   ```
5. Tạo token xác thực email
   ```php
   $verification_token = bin2hex(random_bytes(32));
   ```
6. Bắt đầu transaction
   ```php
   $conn->begin_transaction();
   ```
7. Lưu thông tin người dùng vào cơ sở dữ liệu
   ```php
   $sql = "INSERT INTO user (username, email, password, phone, is_company, company_name, tax_code, tax_registered, 
                         referral_code, referred_by, email_verify_token) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("ssssissssis", $username, $email, $hashed_password, $phone, $is_company, 
                    $company_name, $tax_code, $tax_registered, $generated_referral_code, 
                    $referrer_id, $verification_token);
   $stmt->execute();
   ```
8. Gửi email xác thực
   ```php
   $emailSent = sendVerificationEmail($email, $username, $verification_token);
   
   // Hàm gửi email xác thực (từ email_helper.php)
   function sendVerificationEmail($email, $username, $token) {
       global $mail, $config;
       $mail->clearAddresses();
       $mail->addAddress($email);
       $mail->Subject = "Xác thực Email";
       
       $verify_url = $config['base_url'] . "/public/pages/auth/verify-email.php?token=" . $token;
       
       $mail->Body = <<<HTML
       <p>Xin chào {$username},</p>
       <p>Cảm ơn bạn đã đăng ký tài khoản. Vui lòng nhấp vào liên kết dưới đây để xác thực email của bạn:</p>
       <p><a href="{$verify_url}">{$verify_url}</a></p>
       <p>Liên kết này có hiệu lực trong 24 giờ.</p>
       <p>Trân trọng,<br>Đội ngũ RTK Web</p>
       HTML;
       
       try {
           return $mail->send();
       } catch (Exception $e) {
           // Ghi log lỗi
           error_log("Không thể gửi email xác thực đến {$email}: " . $mail->ErrorInfo);
           return false;
       }
   }
   ```
9. Nếu thành công:
   - Commit transaction
   - Ghi log hoạt động
   - Hiển thị thông báo thành công
   ```php
   if ($stmt->execute() && $emailSent) {
       $new_user_id = $conn->insert_id;
       $conn->commit();
       
       // Ghi log
       $notify_content = 'Người dùng mới đã đăng ký: ' . $username;
       log_activity($conn, $new_user_id, 'registration', 'user', $new_user_id, null, [
           'username' => $username,
           'email' => $email,
           'phone' => $phone,
           'is_company' => $is_company,
           'registration_time' => date('Y-m-d H:i:s')
       ], $notify_content);
       
       $_SESSION['success_message'] = "Đăng ký thành công! Vui lòng kiểm tra email của bạn để xác thực tài khoản.";
   }
   ```
10. Nếu thất bại:
    - Rollback transaction
    - Ghi log lỗi
    - Hiển thị thông báo lỗi
    ```php
    } else {
        $conn->rollback();
        $errors[] = "Không thể đăng ký tài khoản. Vui lòng thử lại sau.";
        
        // Ghi log lỗi
        log_error($conn, 'auth', "Registration failed: " . $stmt->error, null, null);
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $formData;
        header("Location: ../../../public/pages/auth/register.php");
        exit();
    }
    ```

### 3.3. Luồng xác thực email
1. Người dùng nhấp vào liên kết từ email với token
   ```php
   $token = $_GET['token'] ?? '';
   ```
2. Hệ thống kiểm tra token trong cơ sở dữ liệu
   ```php
   $stmt = $conn->prepare("SELECT id, email, email_verified FROM user WHERE email_verify_token = ? AND deleted_at IS NULL");
   $stmt->bind_param("s", $token);
   $stmt->execute();
   $result = $stmt->get_result();
   
   if ($result->num_rows === 1) {
       $user = $result->fetch_assoc();
       // Tiếp tục xử lý
   } else {
       $message = 'Token xác thực không hợp lệ hoặc đã hết hạn.';
       // Log token không hợp lệ
   }
   ```
3. Nếu token hợp lệ:
   - Cập nhật trạng thái `email_verified` thành 1
   - Xóa token xác thực email
   - Ghi log hoạt động
   - Hiển thị thông báo thành công
   ```php
   if ($user['email_verified']) {
       $message = 'Email này đã được xác thực trước đó.';
       // Log trường hợp token đã được sử dụng
   } else {
       // Cập nhật trạng thái xác thực và xóa token
       $update = $conn->prepare("UPDATE user SET email_verified = 1, email_verify_token = NULL WHERE id = ?");
       $update->bind_param("i", $user['id']);
       
       if ($update->execute()) {
           $status = 'success';
           $message = 'Xác thực email thành công! Bạn có thể đăng nhập ngay bây giờ.';
           
           // Log xác thực thành công
           $notify_content = 'Xác thực email thành công cho: ' . $user['email'];
           $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, new_values, notify_content) 
                   VALUES (?, 'email_verified', 'user', ?, ?, ?, ?)";
           $stmt_log = $conn->prepare($sql);
           if ($stmt_log) {
               $ip = $_SERVER['REMOTE_ADDR'] ?? null;
               $log_data = json_encode([
                   'status' => 'verified',
                   'email' => $user['email'],
                   'timestamp' => date('Y-m-d H:i:s')
               ]);
               $stmt_log->bind_param("iisss", $user['id'], $user['id'], $ip, $log_data, $notify_content);
               $stmt_log->execute();
               $stmt_log->close();
           }
       } else {
           $message = 'Không thể cập nhật trạng thái xác thực.';
           // Log lỗi cập nhật
       }
   }
   ```
4. Nếu token không hợp lệ:
   - Ghi log lỗi
   - Hiển thị thông báo lỗi
   ```php
   // Log token không hợp lệ
   $sql = "INSERT INTO error_logs (error_type, error_message, ip_address) VALUES (?, ?, ?)";
   $stmt_error = $conn->prepare($sql);
   if ($stmt_error) {
       $error_type = 'invalid_verification_token';
       $error_message = "Invalid verification token attempted: " . substr($token, 0, 10) . '...';
       $ip = $_SERVER['REMOTE_ADDR'] ?? null;
       $stmt_error->bind_param("sss", $error_type, $error_message, $ip);
       $stmt_error->execute();
       $stmt_error->close();
   }
   ```

### 3.4. Luồng quên mật khẩu
1. Người dùng nhập email
2. Hệ thống kiểm tra email có tồn tại trong cơ sở dữ liệu không
3. Tạo token đặt lại mật khẩu ngẫu nhiên và thời gian hết hạn
4. Bắt đầu transaction
5. Xóa token cũ nếu có
6. Lưu token mới vào bảng `password_resets`
7. Gửi email với liên kết đặt lại mật khẩu
8. Nếu thành công:
   - Commit transaction
   - Ghi log hoạt động
   - Hiển thị thông báo thành công
9. Nếu thất bại:
   - Rollback transaction
   - Ghi log lỗi
   - Hiển thị thông báo lỗi

### 3.5. Luồng đặt lại mật khẩu
1. Người dùng nhấp vào liên kết từ email với token
2. Hệ thống kiểm tra token trong cơ sở dữ liệu và thời gian hết hạn
3. Hiển thị form đặt lại mật khẩu
4. Người dùng nhập mật khẩu mới và xác nhận
5. Hệ thống kiểm tra tính hợp lệ của mật khẩu
6. Mã hóa mật khẩu mới
7. Bắt đầu transaction
8. Cập nhật mật khẩu người dùng
9. Xóa token đặt lại mật khẩu
10. Nếu thành công:
    - Commit transaction
    - Ghi log hoạt động
    - Hiển thị thông báo thành công
11. Nếu thất bại:
    - Rollback transaction
    - Ghi log lỗi
    - Hiển thị thông báo lỗi

## 4. Các lỗi có thể phát sinh và cách sửa

### 4.1. Lỗi cơ sở dữ liệu
- **Triệu chứng**: Thông báo lỗi SQL, không thể kết nối đến cơ sở dữ liệu
- **Nguyên nhân**: Cấu hình cơ sở dữ liệu không chính xác, lỗi cú pháp SQL
- **Giải pháp**:
  - Kiểm tra cấu hình kết nối trong `private/config/database.php`
  - Kiểm tra cú pháp SQL trong các prepared statements
  - Xem log lỗi trong bảng `error_logs` hoặc tệp `private/logs/error.log`

### 4.2. Lỗi không nhận được email xác thực/đặt lại mật khẩu
- **Triệu chứng**: Người dùng không nhận được email sau khi đăng ký hoặc yêu cầu đặt lại mật khẩu
- **Nguyên nhân**: Cấu hình email không chính xác, email bị chặn bởi spam filter
- **Giải pháp**:
  - Kiểm tra cấu hình email trong `private/utils/email_helper.php`
  - Kiểm tra lỗi gửi email trong logs
  - Nâng cấp hệ thống email để sử dụng dịch vụ SMTP chuyên nghiệp như SendGrid, Mailgun

### 4.3. Lỗi xác thực token
- **Triệu chứng**: Token không hợp lệ hoặc đã hết hạn khi xác thực email hoặc đặt lại mật khẩu
- **Nguyên nhân**: Token đã được sử dụng, đã hết hạn, hoặc bị sửa đổi
- **Giải pháp**:
  - Kiểm tra thời gian hết hạn của token trong cơ sở dữ liệu
  - Đảm bảo token được tạo và lưu trữ đúng cách
  - Cung cấp tùy chọn để tạo lại token mới

### 4.4. Lỗi CSRF
- **Triệu chứng**: Yêu cầu bị từ chối với thông báo "CSRF token không hợp lệ"
- **Nguyên nhân**: Token CSRF không tồn tại, không khớp, hoặc đã hết hạn
- **Giải pháp**:
  - Kiểm tra hàm `validate_csrf_token` trong `private/utils/csrf_helper.php`
  - Đảm bảo form bao gồm input CSRF token (`generate_csrf_input()`)
  - Tăng thời gian sống của token CSRF nếu cần (mặc định là 1 giờ)

### 4.5. Lỗi phiên làm việc
- **Triệu chứng**: Người dùng bị đăng xuất bất ngờ, phiên không duy trì
- **Nguyên nhân**: Cấu hình phiên không chính xác, thời gian sống phiên quá ngắn
- **Giải pháp**:
  - Kiểm tra cấu hình phiên trong `private/config/session_config.php`
  - Tăng thời gian sống của phiên nếu cần
  - Đảm bảo `session_start()` được gọi ở đầu mỗi tệp tin PHP cần sử dụng phiên

## 5. Các dự kiến phát triển trong tương lai

### 5.1. Xác thực hai yếu tố (2FA)
- Thêm lớp bảo mật thứ hai như SMS OTP hoặc ứng dụng Authenticator
- Cần bổ sung bảng `user_2fa` để lưu trữ cấu hình 2FA của người dùng
- Cập nhật luồng đăng nhập để kiểm tra và yêu cầu mã 2FA khi cần

### 5.2. Đăng nhập xã hội (Social Login)
- Tích hợp đăng nhập bằng tài khoản Google, Facebook
- Cần thêm bảng `social_accounts` để liên kết tài khoản xã hội với tài khoản người dùng
- Cập nhật UI đăng nhập/đăng ký để hiển thị nút đăng nhập xã hội

### 5.3. Quản lý phiên nâng cao
- Theo dõi và hiển thị các phiên đăng nhập hoạt động của người dùng
- Cho phép người dùng đăng xuất từ tất cả thiết bị hoặc từ một thiết bị cụ thể
- Cần thêm bảng `user_sessions` để lưu thông tin về phiên làm việc

### 5.4. Cải thiện quy trình đăng ký
- Thêm xác thực số điện thoại bằng SMS
- Bổ sung kiểm tra độ mạnh của mật khẩu
- Tích hợp CAPTCHA để ngăn chặn đăng ký tự động

### 5.5. Quản lý quyền hạn và vai trò
- Xây dựng hệ thống phân quyền chi tiết (RBAC - Role-Based Access Control)
- Cho phép gán nhiều vai trò cho mỗi người dùng
- Cần thêm các bảng `roles`, `permissions`, `user_roles`, `role_permissions`

### 5.6. Nâng cấp bảo mật
- Triển khai khóa API an toàn cho các yêu cầu từ ứng dụng di động hoặc dịch vụ bên thứ ba
- Thêm giới hạn số lần đăng nhập thất bại và cơ chế chặn tạm thời (rate limiting)
- Nâng cấp hệ thống ghi log để phát hiện và cảnh báo hoạt động đáng ngờ
