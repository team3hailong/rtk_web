# Logic xử lý hệ thống giới thiệu

## 1. Logic quản lý mã giới thiệu

### Quy trình tạo và chia sẻ mã giới thiệu

1. **Tạo mã giới thiệu**
   - Khi người dùng truy cập dashboard giới thiệu lần đầu, hệ thống kiểm tra xem họ đã có mã giới thiệu chưa
   - Nếu chưa có, hệ thống tự động tạo mã ngẫu nhiên 8 ký tự (chữ và số)
   - Kiểm tra tính duy nhất của mã bằng cách truy vấn database
   - Lưu mã giới thiệu vào bảng `referral` với `user_id` tương ứng
   - Hiển thị mã và link giới thiệu cho người dùng

2. **Chia sẻ mã giới thiệu**
   - Người dùng có hai lựa chọn chia sẻ:
     - Chia sẻ mã giới thiệu trực tiếp (người dùng nhập mã khi đăng ký)
     - Chia sẻ liên kết giới thiệu (chứa mã giới thiệu trong tham số URL)
   - Liên kết giới thiệu được tạo theo dạng: `domain.com/register.php?ref=ABCD1234`
   - Người dùng có thể sao chép mã hoặc liên kết bằng cách nhấp vào nút "Sao chép"

3. **Xử lý frontend**
   - JavaScript xử lý sự kiện sao chép vào clipboard
   - Hiển thị thông báo khi sao chép thành công
   - Cung cấp các nút chia sẻ trực tiếp qua mạng xã hội (nếu có)

## 2. Logic theo dõi người được giới thiệu

### Quy trình ghi nhận người được giới thiệu

1. **Đăng ký qua mã giới thiệu**
   - Người dùng mới truy cập trang đăng ký thông qua liên kết giới thiệu hoặc nhập mã giới thiệu
   - Hệ thống ghi nhận mã giới thiệu vào form đăng ký (tự động điền nếu đến từ link giới thiệu)
   - Người dùng hoàn thành quy trình đăng ký

2. **Xử lý đăng ký với mã giới thiệu**
   - Trong `process_register.php`, sau khi tạo tài khoản thành công, hệ thống kiểm tra xem có mã giới thiệu không
   - Nếu có, hệ thống gọi `Referral.trackReferral()` với tham số là `user_id` mới và mã giới thiệu
   - Phương thức này:
     - Xác thực mã giới thiệu là hợp lệ
     - Tìm ID người giới thiệu từ mã
     - Kiểm tra người dùng không tự giới thiệu chính mình
     - Lưu mối quan hệ giới thiệu vào bảng `referred_user`
     - Ghi log hoạt động

3. **Hiển thị người được giới thiệu**
   - Khi người giới thiệu truy cập dashboard, hệ thống gọi `Referral.getReferredUsers()`
   - Lấy danh sách người dùng đã được giới thiệu từ bảng `referred_user` join với `user`
   - Hiển thị thông tin: tên người dùng, email, ngày đăng ký
   - Sắp xếp theo thứ tự thời gian giảm dần (mới nhất lên đầu)

## 3. Logic quản lý hoa hồng

### Quy trình tính toán và quản lý hoa hồng

1. **Tính toán hoa hồng tự động**
   - Khi một giao dịch được cập nhật thành `status='completed'`, `Transaction.updateStatus()` gọi `Referral.calculateCommission()`
   - Phương thức này:
     - Lấy thông tin giao dịch và số tiền
     - Kiểm tra người mua có phải người được giới thiệu không
     - Nếu có, tính hoa hồng bằng 5% giá trị giao dịch
     - Tạo bản ghi mới trong bảng `referral_commission` với trạng thái `approved`

2. **Quản lý trạng thái hoa hồng**
   - Hoa hồng có các trạng thái:
     - `approved`: Đã duyệt, có thể rút
     - `pending`: Đang chờ xử lý giao dịch
     - `paid`: Đã thanh toán (sau khi rút tiền thành công)
     - `cancelled`: Đã hủy (nếu giao dịch bị hủy/hoàn tiền)

3. **Hiển thị thông tin hoa hồng**
   - Dashboard hiển thị:
     - Tổng hoa hồng đã kiếm được (`Referral.getTotalCommissionEarned()`)
     - Số dư khả dụng (Tổng hoa hồng - Đã rút - Đang chờ rút)
     - Danh sách chi tiết giao dịch và hoa hồng tương ứng (`Referral.getCommissionTransactions()`)
     - Mỗi giao dịch hiển thị: người dùng, số tiền giao dịch, số tiền hoa hồng, trạng thái

## 4. Logic yêu cầu rút tiền

### Quy trình yêu cầu và xử lý rút tiền

1. **Gửi yêu cầu rút tiền**
   - Người dùng truy cập tab "Yêu cầu rút tiền" trên dashboard
   - Nhập thông tin: số tiền muốn rút, thông tin ngân hàng (tên ngân hàng, số tài khoản, chủ tài khoản)
   - JavaScript kiểm tra:
     - Số tiền rút phải >= 100,000 VNĐ
     - Số tiền rút phải <= số dư khả dụng
     - Thông tin ngân hàng không được bỏ trống
   - Gửi form với CSRF token đến `process_withdrawal_request.php`

2. **Xử lý yêu cầu rút tiền**
   - Server kiểm tra lại tất cả điều kiện về số tiền và xác thực
   - Gọi `Referral.createWithdrawalRequest()` để tạo yêu cầu mới
   - Lưu yêu cầu vào bảng `withdrawal_request` với trạng thái `pending`
   - Hiển thị thông báo thành công và cập nhật UI

3. **Xem lịch sử rút tiền**
   - Dashboard hiển thị tab "Lịch sử rút tiền"
   - Hiển thị danh sách các yêu cầu rút tiền với thông tin:
     - Thời gian yêu cầu
     - Số tiền
     - Thông tin ngân hàng
     - Trạng thái (Đang chờ, Đã thanh toán, Đã từ chối)
     - Ghi chú (nếu có)
   - Sắp xếp theo thứ tự thời gian giảm dần

## 5. Logic tối ưu hóa hiệu suất

### Quy trình tối ưu và duy trì hệ thống

1. **Tối ưu truy vấn database**
   - Sử dụng index cho các cột thường xuyên tìm kiếm (`user_id`, `referral_code`, etc.)
   - Sử dụng JOIN thay vì nhiều truy vấn riêng lẻ
   - Sử dụng transactions để đảm bảo tính nhất quán dữ liệu

2. **Cron job tự động kiểm tra**
   - Hệ thống chạy script `optimize_referral_system.php` định kỳ để:
     - Tìm giao dịch đã hoàn thành nhưng chưa tính hoa hồng
     - Tự động tính và ghi nhận hoa hồng
     - Cập nhật trạng thái hoa hồng nếu cần thiết

3. **Xử lý lỗi và logging**
   - Ghi chi tiết lỗi vào file log với thông tin:
     - Loại lỗi (tạo mã, theo dõi giới thiệu, tính hoa hồng, rút tiền)
     - ID người dùng liên quan
     - Thời gian xảy ra
     - Thông tin chi tiết về lỗi
   - Giám sát các lỗi thường xuyên để cải thiện hệ thống
