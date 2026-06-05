# Giải thích cấu trúc code xác thực (Authentication)

## Cấu trúc tổng quan

Hệ thống xác thực trong dự án được tổ chức theo mô hình phân tách UI và logic xử lý:

1. **UI (User Interface)**: Nằm trong thư mục `public/pages/auth/`
2. **Xử lý (Backend)**: Nằm trong thư mục `private/action/auth/`
3. **Utilities**: Các hàm tiện ích trong `private/utils/`

### Luồng xử lý yêu cầu xác thực

```
[Giao diện người dùng] -> [Action Handler] -> [Xử lý Backend] -> [Cơ sở dữ liệu]
   (public/pages)       (public/handlers)   (private/action)      (Database)
```

## Files quan trọng và chức năng

### 1. Cấu trúc trung gian bảo mật

- **`public/handlers/action_handler.php`**: File trung gian cho tất cả các yêu cầu từ frontend đến backend
  - Xác thực người dùng
  - Ngăn chặn truy cập trực tiếp đến private files
  - Kiểm tra CSRF protection
  - Thực hiện white-listing các action cho phép
  
### 2. Các file xử lý đăng nhập

- **`private/action/auth/process_login.php`**: Xử lý đăng nhập người dùng 
  - Xác thực thông tin đăng nhập
  - Kiểm tra trạng thái xác thực email
  - Tạo session khi đăng nhập thành công
  - Ghi log hoạt động đăng nhập

### 3. Các file xử lý đăng ký

- **`private/action/auth/process_register.php`**: Xử lý đăng ký tài khoản mới
  - Xác thực thông tin người dùng
  - Băm mật khẩu trước khi lưu vào database
  - Tạo token xác thực email
  - Gửi email xác thực
  - Xử lý mã giới thiệu (referral code) nếu có

### 4. Xác thực email

- **`private/action/auth/verify-email.php`**: Xử lý xác nhận email
  - Xác thực token từ liên kết email
  - Cập nhật trạng thái xác thực email
  - Ghi log hoạt động

### 5. Đặt lại mật khẩu

- **`private/action/auth/process_forgot_password.php`**: Xử lý yêu cầu đặt lại mật khẩu
  - Tạo và lưu OTP
  - Gửi email với mã OTP
  
- **`private/action/auth/verify-reset-otp.php`**: Xác nhận mã OTP đặt lại mật khẩu
  - Xác thực OTP người dùng nhập vào
  - Tạo session token cho đặt lại mật khẩu
  
- **`private/action/auth/process_reset_password_otp.php`**: Xử lý đặt mật khẩu mới
  - Xác thực session token
  - Cập nhật mật khẩu mới
  - Xóa OTP sau khi đặt lại mật khẩu thành công

### 6. Đăng xuất

- **`private/action/auth/process_logout.php`**: Xử lý đăng xuất
  - Ghi log hoạt động đăng xuất
  - Xóa session
  - Hủy cookie

## Các utilities được sử dụng

1. **Xử lý email**:
   - `private/utils/email_helper.php`: Chứa các hàm gửi email cho đăng ký, xác thực, đặt lại mật khẩu

2. **Xử lý OTP**:
   - `private/utils/otp_helper.php`: Các hàm tạo, lưu trữ và xác thực OTP cho đặt lại mật khẩu

3. **Xử lý CSRF**:
   - `private/utils/csrf_helper.php`: Ngăn chặn tấn công CSRF

4. **Xử lý lỗi và ghi log**:
   - `private/utils/error_handler.php`: Ghi log lỗi và hoạt động người dùng

## Các bảng dữ liệu liên quan

1. **`user`**: Lưu thông tin người dùng 
   - Bao gồm mật khẩu đã băm, trạng thái xác thực email

2. **`password_resets`**: Lưu token đặt lại mật khẩu
   - Token, thời gian hết hạn, user_id

3. **`activity_logs`**: Ghi log hoạt động người dùng
   - Đăng nhập, đăng xuất, đổi mật khẩu, xác thực email
   - Có cột `notify_content` để hiển thị thông báo
   - Có cột `has_read` để theo dõi trạng thái đọc
   
4. **`error_logs`**: Ghi log lỗi hệ thống
   - Lỗi gửi email, lỗi xử lý đặt lại mật khẩu, các lỗi hệ thống khác
