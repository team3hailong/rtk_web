# Logic xử lý xác thực (Authentication)

## 1. Logic đăng nhập (Login)

### Quy trình xử lý đăng nhập
1. **Nhận thông tin đầu vào**
   - Email và mật khẩu từ form đăng nhập
   - CSRF token để bảo mật form

2. **Kiểm tra dữ liệu đầu vào**
   - Xác nhận email không trống và đúng định dạng
   - Xác nhận mật khẩu không trống

3. **Xác thực người dùng**
   - Tìm người dùng trong cơ sở dữ liệu theo email
   - Kiểm tra mật khẩu đã nhập với mật khẩu đã băm trong DB
   - Kiểm tra trạng thái xác thực email (email_verified)

4. **Tạo phiên đăng nhập**
   - Nếu xác thực thành công, tạo mới session ID (`session_regenerate_id()`)
   - Lưu thông tin người dùng vào session (`$_SESSION['user_id']`, `$_SESSION['username']`)
   - Ghi log hoạt động đăng nhập thành công

5. **Xử lý lỗi**
   - Nếu thông tin không chính xác, lưu thông báo lỗi vào session
   - Nếu email chưa được xác thực, hiển thị thông báo tương ứng
   - Chuyển hướng người dùng về trang đăng nhập với thông báo lỗi

## 2. Logic đăng ký (Register)

### Quy trình xử lý đăng ký
1. **Thu thập và xác thực dữ liệu**
   - Xác thực email, username, mật khẩu
   - Kiểm tra mật khẩu độ dài tối thiểu (6 ký tự)
   - Kiểm tra email đã tồn tại trong hệ thống chưa

2. **Xử lý dữ liệu**
   - Băm mật khẩu bằng `password_hash()` với thuật toán mặc định
   - Tạo token xác thực email ngẫu nhiên
   - Đặt thời gian hết hạn cho token (thường là 24 giờ)

3. **Lưu trữ dữ liệu**
   - Thực hiện transaction để đảm bảo tính toàn vẹn dữ liệu
   - Lưu thông tin người dùng vào bảng `user`
   - Lưu token xác thực email

4. **Gửi email xác thực**
   - Tạo liên kết xác thực email với token
   - Gửi email xác thực tới địa chỉ email đăng ký

5. **Xử lý mã giới thiệu (nếu có)**
   - Kiểm tra mã giới thiệu có hợp lệ không
   - Tạo liên kết giới thiệu trong hệ thống

6. **Phản hồi người dùng**
   - Hiển thị thông báo đăng ký thành công
   - Hướng dẫn kiểm tra email để xác thực tài khoản

## 3. Logic xác thực email

### Quy trình xử lý xác thực email
1. **Xác thực token**
   - Xác nhận token từ liên kết trong email
   - Kiểm tra token có hợp lệ và chưa hết hạn

2. **Cập nhật trạng thái xác thực**
   - Đặt cột `email_verified` = 1 cho người dùng
   - Xóa token đã sử dụng

3. **Ghi log hoạt động**
   - Lưu thông tin xác thực email vào bảng `activity_logs`

4. **Phản hồi người dùng**
   - Hiển thị thông báo xác thực thành công
   - Cung cấp liên kết để đăng nhập

## 4. Logic đặt lại mật khẩu

### 4.1. Yêu cầu đặt lại mật khẩu (forgot password)
1. **Xác nhận email tồn tại**
   - Kiểm tra email có trong hệ thống không
   - Không tiết lộ thông tin email tồn tại hay không cho bảo mật

2. **Tạo và lưu OTP**
   - Tạo mã OTP ngẫu nhiên (thường 6 chữ số)
   - Lưu vào database với thời gian hết hạn (15 phút)

3. **Gửi email với OTP**
   - Gửi email chứa mã OTP đến người dùng
   - Hướng dẫn nhập OTP để tiếp tục đặt lại mật khẩu

### 4.2. Xác thực OTP đặt lại mật khẩu
1. **Kiểm tra OTP**
   - So sánh OTP người dùng nhập với OTP trong database
   - Xác nhận OTP chưa hết hạn

2. **Tạo phiên đặt lại mật khẩu**
   - Tạo token phiên (`password_reset_token`)
   - Lưu vào session user_id và thời gian hết hạn (30 phút)

3. **Chuyển hướng người dùng**
   - Điều hướng đến trang đặt mật khẩu mới

### 4.3. Đặt mật khẩu mới
1. **Xác thực phiên**
   - Kiểm tra token phiên hợp lệ
   - Kiểm tra phiên chưa hết hạn

2. **Xác thực mật khẩu mới**
   - Kiểm tra độ dài tối thiểu (6 ký tự)
   - Xác nhận mật khẩu mới và xác nhận mật khẩu khớp nhau

3. **Cập nhật mật khẩu**
   - Băm mật khẩu mới
   - Cập nhật vào database
   - Xóa OTP và thông tin đặt lại mật khẩu

4. **Hoàn tất quy trình**
   - Ghi log hoạt động đặt lại mật khẩu
   - Điều hướng đến trang đăng nhập với thông báo thành công

## 5. Logic đăng xuất

1. **Ghi log đăng xuất**
   - Lưu thông tin đăng xuất vào bảng `activity_logs`

2. **Xóa session**
   - Xóa toàn bộ dữ liệu session: `$_SESSION = array();`
   - Hủy session: `session_destroy();`

3. **Xóa cookie session (nếu có)**
   - Đặt thời gian hết hạn cookie về quá khứ

4. **Chuyển hướng người dùng**
   - Điều hướng người dùng đến trang chủ
