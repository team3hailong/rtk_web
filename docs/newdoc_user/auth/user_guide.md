# Hướng dẫn sử dụng hệ thống xác thực

## Giới thiệu

Hệ thống xác thực cung cấp các chức năng cần thiết để bạn có thể tạo tài khoản, đăng nhập, xác thực email và quản lý mật khẩu một cách an toàn.

## Tạo tài khoản mới

### Cách đăng ký tài khoản

1. Truy cập trang đăng ký tại: `/public/pages/auth/register.php`
2. Điền đầy đủ thông tin vào form:
   - Tên người dùng (Username)
   - Địa chỉ email
   - Mật khẩu (tối thiểu 6 ký tự)
   - Xác nhận mật khẩu
3. Nhập mã giới thiệu (referral code) nếu có
4. Nhấn nút "Đăng Ký" để hoàn tất

### Xác thực email

Sau khi đăng ký, bạn sẽ nhận được email xác thực:

1. Kiểm tra hộp thư đến (và thư mục spam nếu cần)
2. Mở email có tiêu đề "Xác thực tài khoản"
3. Nhấn vào liên kết xác thực trong email
4. Bạn sẽ được chuyển hướng đến trang xác nhận thành công

> **Lưu ý**: Liên kết xác thực có hiệu lực trong 24 giờ. Nếu hết hạn, bạn cần yêu cầu gửi lại email xác thực.

## Đăng nhập vào hệ thống

### Cách đăng nhập

1. Truy cập trang đăng nhập tại: `/public/pages/auth/login.php`
2. Nhập email và mật khẩu của bạn
3. Nhấn nút "Đăng Nhập"

### Nếu quên mật khẩu

Nếu không thể đăng nhập do quên mật khẩu:

1. Nhấn vào liên kết "Quên mật khẩu?" tại trang đăng nhập
2. Nhập địa chỉ email của tài khoản
3. Nhấn nút "Gửi yêu cầu"
4. Kiểm tra email để lấy mã OTP đặt lại mật khẩu

## Đặt lại mật khẩu

### Sử dụng mã OTP

1. Sau khi yêu cầu đặt lại mật khẩu, bạn sẽ nhận mã OTP qua email
2. Nhập mã OTP 6 chữ số vào các ô trên trang xác thực
3. Nhấn nút "Xác thực"
4. Sau khi xác thực thành công, bạn sẽ được chuyển đến trang đặt mật khẩu mới

### Tạo mật khẩu mới

1. Nhập mật khẩu mới (tối thiểu 6 ký tự)
2. Xác nhận mật khẩu mới
3. Nhấn nút "Đặt lại mật khẩu"
4. Sau khi hoàn tất, bạn có thể đăng nhập bằng mật khẩu mới

> **Lưu ý về mật khẩu**: Mật khẩu cần có ít nhất 6 ký tự để đảm bảo an toàn.

## Đăng xuất khỏi hệ thống

Để đăng xuất:

1. Nhấn vào tên người dùng hoặc biểu tượng tài khoản trên thanh điều hướng
2. Chọn "Đăng xuất" từ menu thả xuống
3. Bạn sẽ được chuyển hướng đến trang chủ sau khi đăng xuất thành công

## Các câu hỏi thường gặp

### Tôi không nhận được email xác thực

- Kiểm tra thư mục spam/junk trong hộp thư
- Đảm bảo địa chỉ email đã nhập chính xác
- Liên hệ hỗ trợ nếu vẫn không nhận được email sau 30 phút

### Email báo "tài khoản đã tồn tại"

- Email đã được sử dụng để đăng ký tài khoản trước đó
- Thử đăng nhập với email này, hoặc sử dụng chức năng quên mật khẩu

### Mã OTP không hoạt động

- Đảm bảo nhập đúng các chữ số OTP đã gửi qua email
- Kiểm tra xem OTP đã hết hạn chưa (có hiệu lực trong 15 phút)
- Nhấn vào "Gửi lại mã" để nhận mã OTP mới

### Tôi không thể đăng nhập dù đã nhập đúng thông tin

- Email chưa được xác thực, kiểm tra hộp thư để tìm email xác thực
- Mật khẩu không chính xác, thử sử dụng chức năng quên mật khẩu
- Tài khoản có thể bị khóa do nhiều lần đăng nhập thất bại

## Liên hệ hỗ trợ

Nếu bạn gặp bất kỳ vấn đề nào khi sử dụng hệ thống xác thực, vui lòng liên hệ:

- Email: support@example.com
- Điện thoại: 0123-456-789
- Hoặc sử dụng form liên hệ tại trang chủ
