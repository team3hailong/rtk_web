# Các vấn đề thường gặp và cách giải quyết

## 1. Vấn đề đăng nhập

### 1.1. Người dùng không thể đăng nhập

#### Nguyên nhân thường gặp
1. **Email chưa được xác thực**
   - Hệ thống yêu cầu xác thực email trước khi đăng nhập
   
2. **Mật khẩu không đúng**
   - Người dùng nhập sai mật khẩu
   - Mật khẩu đã được đặt lại mà người dùng không biết

3. **Tài khoản bị khóa hoặc xóa**
   - `deleted_at` không phải NULL

#### Cách giải quyết
1. **Email chưa xác thực**:
   - Kiểm tra bảng `user` với cột `email_verified`
   - Gửi lại email xác thực:
   ```php
   // Code gửi lại email xác thực
   require_once 'path/to/email_helper.php';
   $token = bin2hex(random_bytes(32));
   // Lưu token mới và gửi email
   ```

2. **Mật khẩu không đúng**:
   - Hướng dẫn người dùng đến trang quên mật khẩu
   - Kiểm tra lịch sử đặt lại mật khẩu trong bảng `activity_logs`

3. **Tài khoản bị xóa**:
   - Kiểm tra trạng thái `deleted_at` trong bảng `user`
   - Phục hồi tài khoản nếu cần:
   ```sql
   UPDATE user SET deleted_at = NULL WHERE email = 'user@example.com';
   ```

## 2. Vấn đề đặt lại mật khẩu

### 2.1. Không nhận được email đặt lại mật khẩu

#### Nguyên nhân thường gặp
1. **Email không tồn tại trong hệ thống**
   - Email người dùng nhập không khớp với bản ghi nào trong DB
   
2. **Vấn đề với hệ thống gửi email**
   - Server SMTP không phản hồi
   - Cấu hình email không chính xác
   
3. **Email bị chặn hoặc vào thư mục spam**
   - Email bị bộ lọc spam chặn

#### Cách giải quyết
1. **Kiểm tra email trong hệ thống**:
   ```sql
   SELECT * FROM user WHERE email = 'user@example.com';
   ```

2. **Kiểm tra log lỗi gửi email**:
   ```sql
   SELECT * FROM error_logs WHERE error_type LIKE '%email%' ORDER BY created_at DESC LIMIT 10;
   ```

3. **Kiểm tra cấu hình SMTP**:
   - Xem file `private/config/email_config.php` hoặc biến môi trường
   - Thử gửi email test thông qua công cụ debug

4. **Cung cấp phương pháp thay thế**:
   - Đặt lại mật khẩu trực tiếp bởi admin
   - Sử dụng hệ thống xác thực OTP qua tin nhắn thay vì email

### 2.2. Mã OTP đặt lại mật khẩu không hoạt động

#### Nguyên nhân thường gặp
1. **OTP đã hết hạn**
   - Vượt quá thời gian 15 phút
   
2. **OTP nhập không chính xác**
   - Người dùng nhập sai mã
   
3. **OTP đã được sử dụng**
   - Người dùng đã sử dụng OTP này rồi

#### Cách giải quyết
1. **Kiểm tra trạng thái OTP**:
   ```sql
   SELECT * FROM user 
   WHERE email = 'user@example.com' 
   AND password_reset_otp_expires_at > NOW();
   ```

2. **Gửi lại OTP mới**:
   - Kích hoạt chức năng gửi lại OTP
   - Đặt thời gian chờ (cooldown) để tránh spam

3. **Hướng dẫn người dùng**:
   - Cung cấp thông tin về thời hạn OTP
   - Kiểm tra kỹ mã nhập vào

## 3. Vấn đề xác thực email

### 3.1. Liên kết xác thực email không hoạt động

#### Nguyên nhân thường gặp
1. **Token đã hết hạn**
   - Quá 24 giờ kể từ khi đăng ký
   
2. **Token đã được sử dụng**
   - Email đã được xác thực trước đó
   
3. **Token không hợp lệ**
   - Sai định dạng hoặc đã bị thay đổi

#### Cách giải quyết
1. **Kiểm tra token và thời hạn**:
   ```sql
   SELECT * FROM user 
   WHERE verification_token = 'token_value' 
   AND verification_token_expires_at > NOW();
   ```

2. **Cung cấp chức năng gửi lại email xác thực**:
   - Tạo API endpoint hoặc trang để gửi lại email
   - Cập nhật token mới và thời hạn

3. **Kiểm tra trạng thái xác thực hiện tại**:
   ```sql
   SELECT email_verified FROM user WHERE email = 'user@example.com';
   ```

## 4. Vấn đề bảo mật

### 4.1. Phát hiện nhiều lần đăng nhập thất bại

#### Nguyên nhân thường gặp
1. **Tấn công brute force**
   - Tin tặc thử nhiều mật khẩu khác nhau
   
2. **Người dùng quên mật khẩu**
   - Tự thử nhiều mật khẩu khác nhau

#### Cách giải quyết
1. **Thêm cơ chế hạn chế số lần đăng nhập**:
   ```php
   // Kiểm tra và ghi nhận số lần đăng nhập thất bại
   function checkLoginAttempts($email, $conn) {
       // Đếm số lần đăng nhập thất bại trong 30 phút qua
       $stmt = $conn->prepare("
           SELECT COUNT(*) FROM activity_logs 
           WHERE action = 'login_failed' 
           AND entity_type = 'user' 
           AND ip_address = ? 
           AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
       ");
       $ip = $_SERVER['REMOTE_ADDR'] ?? null;
       $stmt->bind_param("s", $ip);
       $stmt->execute();
       $count = $stmt->get_result()->fetch_row()[0];
       
       // Nếu quá 5 lần, khóa tạm thời
       if ($count >= 5) {
           return false; // Khóa đăng nhập
       }
       return true; // Cho phép tiếp tục
   }
   ```

2. **Thêm CAPTCHA sau một số lần thất bại**:
   - Kích hoạt CAPTCHA sau 3 lần đăng nhập không thành công

3. **Gửi thông báo cho người dùng và admin**:
   - Email cảnh báo khi phát hiện nhiều lần đăng nhập thất bại

## 5. Vấn đề kỹ thuật

### 5.1. Lỗi mã hóa mật khẩu

#### Nguyên nhân thường gặp
1. **Phiên bản PHP không hỗ trợ thuật toán băm**
   - Server chạy phiên bản PHP cũ

2. **Sai cấu hình trong hàm `password_hash()`**
   - Tham số không chính xác

#### Cách giải quyết
1. **Kiểm tra phiên bản PHP và hỗ trợ**:
   ```php
   echo 'PHP version: ' . phpversion();
   echo 'Password hash available: ' . (function_exists('password_hash') ? 'Yes' : 'No');
   ```

2. **Thay đổi thuật toán băm nếu cần**:
   ```php
   // Sử dụng thuật toán tương thích
   $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
   ```

3. **Cập nhật PHP nếu có thể**:
   - Cập nhật lên phiên bản PHP mới nhất

### 5.2. Session bị mất đột ngột

#### Nguyên nhân thường gặp
1. **Cấu hình session không chính xác**
   - Thời gian session quá ngắn
   
2. **Vấn đề với cookie**
   - Cookie bị chặn hoặc xóa
   
3. **Vấn đề lưu trữ session**
   - Lỗi ghi/đọc session từ storage

#### Cách giải quyết
1. **Kiểm tra cấu hình session**:
   ```php
   // Kiểm tra và điều chỉnh thời gian sống của session
   ini_set('session.gc_maxlifetime', 3600); // 1 giờ
   ini_set('session.cookie_lifetime', 3600);
   ```

2. **Kiểm tra lưu trữ session**:
   - Đảm bảo thư mục session có quyền ghi
   - Xem xét sử dụng session lưu trữ trong database thay vì file

3. **Thêm cơ chế refresh token**:
   - Triển khai hệ thống token để duy trì session lâu hơn
