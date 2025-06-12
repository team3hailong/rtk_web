# Logic xử lý mua tài khoản (Purchase)

## 1. Logic mua gói dịch vụ mới

### Quy trình mua gói dịch vụ mới

1. **Hiển thị danh sách gói dịch vụ**
   - Gọi `PurchaseService.getAllPackages()` để lấy danh sách gói
   - Kiểm tra người dùng đã có tài khoản nào chưa bằng `getUserSurveyAccountCount()`
   - Ẩn gói dùng thử nếu người dùng đã có tài khoản hoặc không đủ điều kiện
   - Hiển thị các gói theo thứ tự `display_order`

2. **Chọn gói và hiện trang chi tiết**
   - Người dùng chọn gói dịch vụ từ trang danh sách
   - Lấy `package_varchar_id` và `purchase_type` từ lựa chọn
   - Chuyển hướng đến trang `details.php` với các tham số
   - Gọi `PurchaseService.getPackageByVarcharId()` để lấy thông tin chi tiết gói

3. **Nhập thông tin đơn hàng**
   - Người dùng nhập số lượng tài khoản cần mua
   - Chọn tỉnh/thành phố sẽ sử dụng dịch vụ
   - Hệ thống tính toán giá tiền dựa trên số lượng tài khoản và giá gói
   - JavaScript tính toán và hiển thị giá trong giao diện

4. **Xử lý đơn hàng (process_order.php)**
   - **Nhận và xác thực dữ liệu đầu vào**
     - Kiểm tra người dùng đã đăng nhập
     - Xác thực các tham số: package_id, quantity, location_id
     - Kiểm tra quyền truy cập và tính hợp lệ

   - **Tính toán giá và thời gian**
     - Lấy thông tin gói từ database để xác nhận lại giá
     - Tính tổng giá dựa trên số lượng và đơn giá
     - Tính thời gian bắt đầu và kết thúc dựa trên `duration_text` của gói
     - Thêm VAT vào giá cuối cùng

   - **Lưu thông tin đơn hàng**
     - Bắt đầu giao dịch database transaction
     - Tạo bản ghi mới trong bảng `registration`
     - Tạo bản ghi giao dịch trong bảng `transaction_history`
     - Lưu type của đơn hàng (cá nhân/công ty)
     - Commit transaction

   - **Lưu session và chuyển hướng**
     - Lưu ID đăng ký và tổng tiền vào session
     - Chuyển hướng người dùng đến trang thanh toán

5. **Thanh toán đơn hàng**
   - Hiển thị thông tin đơn hàng từ session và xác thực với database
   - Tạo mã QR VietQR để thanh toán
   - Cung cấp thông tin ngân hàng và hướng dẫn thanh toán
   - Cho phép áp dụng voucher giảm giá

6. **Tải lên minh chứng thanh toán**
   - Người dùng tải lên ảnh minh chứng sau khi chuyển khoản
   - Hệ thống lưu ảnh và cập nhật bản ghi giao dịch

7. **Xử lý đơn hàng (backend)**
   - Admin xác nhận thanh toán
   - Tạo tài khoản RTK cho người dùng
   - Cập nhật trạng thái đăng ký từ 'pending' thành 'active'

## 2. Logic đăng ký dùng thử

### Quy trình đăng ký dùng thử

1. **Kiểm tra điều kiện dùng thử**
   - Kiểm tra người dùng chưa có tài khoản nào (hàm `getUserSurveyAccountCount()`)
   - Chỉ hiển thị gói dùng thử cho người dùng hợp lệ

2. **Chọn gói dùng thử**
   - Người dùng chọn gói với `package_id` là 'trial_7d'
   - Chuyển hướng đến trang chi tiết với tham số dùng thử

3. **Xử lý đơn hàng dùng thử**
   - Ghi nhận trong `process_order.php` rằng đây là gói dùng thử
   - Đặt giá bằng 0 và số lượng là 1
   - Lưu bản ghi trong `registration` và `transaction_history`
   - Đặt `$_SESSION['pending_is_trial'] = true`

4. **Kích hoạt tài khoản dùng thử (process_trial_activation.php)**
   - **Kiểm tra thông tin**
     - Xác minh registration_id và user_id
     - Đảm bảo trạng thái đăng ký là 'pending'
     - Kiểm tra điều kiện dùng thử
     
   - **Tạo tài khoản RTK**
     - Tạo username và password tự động cho tài khoản dùng thử
     - Thiết lập thời gian bắt đầu và kết thúc (thường là 7 ngày)
     - Lưu vào bảng `survey_account`
     
   - **Cập nhật trạng thái**
     - Liên kết tài khoản với đăng ký trong bảng `account_groups`
     - Cập nhật trạng thái đăng ký thành 'active'
     - Cập nhật trạng thái giao dịch thành 'confirmed'
     
   - **Hoàn tất**
     - Ghi log vào bảng `activity_logs`
     - Lưu thông tin tài khoản dùng thử vào session
     - Chuyển hướng đến trang thành công

## 3. Logic gia hạn tài khoản

### Quy trình gia hạn tài khoản

1. **Hiển thị danh sách tài khoản**
   - Gọi `RtkAccount.getActiveAccounts()` để lấy tài khoản của người dùng
   - Hiển thị thông tin tài khoản: username, thời gian hết hạn, vị trí

2. **Chọn tài khoản và gói gia hạn**
   - Người dùng chọn tài khoản cần gia hạn
   - Chọn gói gia hạn từ danh sách
   - Hiển thị tổng giá dựa trên số lượng tài khoản và giá gói

3. **Xử lý gia hạn (process_renewal.php)**
   - **Nhận và xác thực dữ liệu**
     - Kiểm tra người dùng đã đăng nhập
     - Xác thực thông tin: selected_accounts, package_id, purchase_type
     
   - **Tính toán giá và thời gian**
     - Lấy thông tin chi tiết của tài khoản và gói
     - Tính giá dựa trên số lượng tài khoản và đơn giá gói
     - Xác định thời gian mới dựa trên ngày hết hạn hiện tại
     - Thêm VAT vào giá cuối cùng
     
   - **Lưu thông tin gia hạn**
     - Bắt đầu giao dịch database transaction
     - Tạo bản ghi mới trong `registration` cho gia hạn
     - Liên kết tài khoản hiện có với đăng ký mới trong `account_groups`
     - Tạo bản ghi giao dịch trong bảng `transaction_history`
     - Commit transaction
     
   - **Lưu session và chuyển hướng**
     - Lưu ID đăng ký gia hạn và tổng tiền vào session
     - Lưu thông tin gia hạn chi tiết vào session
     - Đặt flag `$_SESSION['is_renewal'] = true`
     - Chuyển hướng người dùng đến trang thanh toán

4. **Thanh toán và xác nhận gia hạn**
   - Xử lý thanh toán giống như mua gói mới
   - Admin xác nhận thanh toán
   - Hệ thống cập nhật thời gian hết hạn mới cho tài khoản
   - Cập nhật trạng thái đăng ký từ 'pending' thành 'active'

## 4. Logic xử lý voucher

### Quy trình áp dụng voucher

1. **Nhập mã voucher**
   - Người dùng nhập mã voucher trên trang thanh toán
   - JavaScript gửi AJAX request đến `apply_voucher.php`

2. **Xác thực và áp dụng voucher (apply_voucher.php)**
   - **Kiểm tra tính hợp lệ**
     - Xác thực voucher tồn tại trong hệ thống
     - Kiểm tra thời hạn sử dụng của voucher
     - Kiểm tra giới hạn sử dụng
     - Kiểm tra điều kiện áp dụng (tổng giá trị đơn hàng, loại gói, v.v.)
     
   - **Tính giảm giá**
     - Tính toán số tiền giảm dựa trên loại voucher (phần trăm hoặc giảm cố định)
     - Đảm bảo số tiền giảm không vượt quá giới hạn
     - Cập nhật tổng giá trong session
     
   - **Lưu thông tin**
     - Ghi nhận sử dụng voucher trong bảng `voucher_usages`
     - Tăng số lần sử dụng của voucher
     - Cập nhật giao dịch với thông tin voucher
     - Ghi log vào bảng `activity_logs`

3. **Hiển thị kết quả**
   - Trả về kết quả áp dụng voucher (thành công/thất bại)
   - Cập nhật giao diện hiển thị giảm giá và tổng tiền mới
