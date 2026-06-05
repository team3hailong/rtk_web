# Giải thích cấu trúc code mua tài khoản (Purchase)

## Cấu trúc tổng quan

Hệ thống mua hàng trong dự án được tổ chức theo mô hình phân tách UI và logic xử lý:

1. **UI (User Interface)**: Nằm trong thư mục `public/pages/purchase/`
2. **Xử lý (Backend)**: Nằm trong thư mục `private/action/purchase/`
3. **Service Classes**: Nằm trong thư mục `private/classes/purchase/`
4. **Utility Functions**: Các hàm tiện ích trong `private/utils/`

### Luồng xử lý yêu cầu mua hàng

```
[Giao diện người dùng] -> [Action Handler] -> [Xử lý Backend] -> [Cơ sở dữ liệu]
   (public/pages)       (public/handlers)   (private/action)      (Database)
```

## Files quan trọng và chức năng

### 1. Trang giao diện người dùng

- **`public/pages/purchase/packages.php`**: Hiển thị danh sách các gói dịch vụ
  - Gọi `PurchaseService` để lấy thông tin các gói dịch vụ
  - Kiểm tra người dùng có đủ điều kiện để đăng ký dùng thử không
  - Hiển thị các gói theo thứ tự `display_order`

- **`public/pages/purchase/details.php`**: Chi tiết gói dịch vụ và form đặt hàng
  - Hiển thị thông tin chi tiết về gói đã chọn
  - Form cho phép nhập số lượng tài khoản và tỉnh/thành sử dụng

- **`public/pages/purchase/payment.php`**: Trang thanh toán
  - Hiển thị thông tin đơn hàng, số tiền, mã QR thanh toán
  - Cho phép áp dụng voucher giảm giá
  - Giao diện tải lên minh chứng thanh toán

- **`public/pages/purchase/renewal.php`**: Trang gia hạn tài khoản
  - Cho phép chọn các tài khoản cần gia hạn
  - Chọn gói gia hạn và thanh toán

- **`public/pages/purchase/success.php`**: Trang thành công
  - Hiển thị thông báo đơn hàng thành công
  - Cung cấp thông tin tiếp theo cho người dùng

### 2. Classes xử lý mua hàng

- **`private/classes/purchase/PurchaseService.php`**
  - Quản lý các thao tác cơ bản của quá trình mua hàng
  - Lấy thông tin gói dịch vụ
  - Kiểm tra tình trạng tài khoản của người dùng

- **`private/classes/purchase/PaymentService.php`**
  - Xử lý thanh toán và thông tin thanh toán
  - Tạo mã QR cho thanh toán
  - Xác thực thông tin đơn hàng

- **`private/classes/purchase/RenewalService.php`**
  - Xử lý gia hạn tài khoản
  - Lấy thông tin tài khoản cần gia hạn
  - Lấy danh sách gói gia hạn

- **`private/classes/Package.php`**
  - Quản lý thông tin gói dịch vụ
  - Lấy chi tiết gói dịch vụ theo ID

- **`private/classes/Location.php`**
  - Quản lý thông tin địa điểm
  - Lấy danh sách các tỉnh/thành phố

- **`private/classes/RtkAccount.php`**
  - Quản lý tài khoản RTK
  - Lấy thông tin tài khoản của người dùng

### 3. Files xử lý Backend

- **`private/action/purchase/process_order.php`**
  - Xử lý đơn hàng mới
  - Tạo bản ghi trong bảng `registration`
  - Tạo giao dịch trong bảng `transaction_history`
  - Lưu thông tin thanh toán

- **`private/action/purchase/process_renewal.php`**
  - Xử lý yêu cầu gia hạn tài khoản
  - Tạo đăng ký mới cho tài khoản đã chọn
  - Liên kết tài khoản với đăng ký mới

- **`private/action/purchase/process_trial_activation.php`**
  - Xử lý kích hoạt gói dùng thử
  - Tạo tài khoản RTK mới
  - Cập nhật trạng thái đăng ký

- **`private/action/purchase/apply_voucher.php`**
  - Xử lý áp dụng mã giảm giá
  - Kiểm tra tính hợp lệ của voucher
  - Áp dụng giảm giá vào đơn hàng

### 4. Utilities và Functions

- **`private/utils/payment_helper.php`**
  - Các hàm hỗ trợ thanh toán
  - Tạo thông tin cho trang thanh toán
  - Tạo mã QR VietQR

- **`private/utils/functions.php`**
  - Các hàm tiện ích dùng chung

## Các bảng dữ liệu liên quan

1. **`package`**: Lưu thông tin các gói dịch vụ
   - ID, tên gói, giá, thời hạn, mô tả

2. **`registration`**: Lưu thông tin đăng ký dịch vụ
   - User ID, package ID, location ID, số lượng tài khoản, thời gian bắt đầu/kết thúc
   - Trạng thái: 'pending', 'active', 'expired', 'cancelled'

3. **`survey_account`**: Lưu thông tin tài khoản dịch vụ RTK
   - Username, password, registration ID, trạng thái kích hoạt

4. **`transaction_history`**: Lưu thông tin giao dịch
   - Registration ID, user ID, loại giao dịch, số tiền
   - Trạng thái: 'pending', 'confirmed', 'cancelled'

5. **`account_groups`**: Liên kết giữa đăng ký và tài khoản
   - Registration ID, survey_account_id

6. **`location`**: Lưu thông tin địa điểm sử dụng
   - ID, tỉnh/thành phố, mã tỉnh/thành phố

7. **`voucher`**: Lưu thông tin mã giảm giá
   - Mã, loại giảm giá, giá trị, điều kiện áp dụng
