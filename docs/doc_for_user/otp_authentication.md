# OTP-based Authentication Migration

## Mục đích
Dự án này triển khai chuyển đổi toàn bộ hệ thống xác thực từ dùng link URL sang dùng mã OTP 6 chữ số cho các hành động xác thực email và đặt lại mật khẩu.

## Tính năng mới
1. **Xác thực email bằng OTP**: Thay thế link email bằng mã OTP 6 chữ số
2. **Đặt lại mật khẩu bằng OTP**: Thay thế link reset password bằng mã OTP 6 chữ số
3. **Trải nghiệm người dùng cải tiến**: Giao diện thân thiện với người dùng cho quá trình xác thực OTP
4. **Tăng cường bảo mật**: Mã OTP có thời hạn 15 phút và được lưu trữ an toàn

## Thay đổi cơ sở dữ liệu
File migration: `Nguyen_30052025_add_otp_verification.sql`

Các thay đổi bao gồm:
- Thêm cột lưu trữ OTP xác thực email
- Thêm cột lưu trữ thời hạn của OTP xác thực email
- Thêm cột lưu trữ OTP đặt lại mật khẩu
- Thêm cột lưu trữ thời hạn của OTP đặt lại mật khẩu
- Thêm index để tối ưu hóa truy vấn

## Quy trình xác thực mới

### Xác thực email
1. Người dùng đăng ký tài khoản
2. Hệ thống tạo mã OTP 6 chữ số và lưu vào DB
3. Email chứa mã OTP được gửi đến người dùng
4. Người dùng nhập mã OTP vào form xác thực
5. Hệ thống kiểm tra tính hợp lệ và kích hoạt tài khoản

### Đặt lại mật khẩu
1. Người dùng yêu cầu đặt lại mật khẩu
2. Hệ thống tạo mã OTP 6 chữ số và lưu vào DB
3. Email chứa mã OTP được gửi đến người dùng
4. Người dùng nhập mã OTP vào form xác thực
5. Sau khi xác thực thành công, người dùng được chuyển đến trang đặt mật khẩu mới

## Cách kiểm thử
1. Đăng ký tài khoản mới và kiểm tra xem quy trình xác thực email OTP có hoạt động không
2. Yêu cầu đặt lại mật khẩu và kiểm tra quy trình đặt lại mật khẩu bằng OTP
3. Kiểm tra các trường hợp lỗi khác nhau:
   - Nhập sai mã OTP
   - Mã OTP hết hạn
   - Nhiều lần thử xác thực không thành công

## Lưu ý triển khai
1. Chạy script migration để cập nhật cấu trúc cơ sở dữ liệu
2. Đảm bảo cấu hình email server hoạt động chính xác
3. Kiểm tra đầy đủ các tính năng trước khi triển khai lên môi trường production
