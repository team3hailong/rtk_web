# Tài liệu về Hệ thống Voucher

## Tổng quan

Hệ thống voucher cho phép tạo và quản lý các mã giảm giá cho người dùng. Voucher có thể áp dụng cho giao dịch mua mới hoặc gia hạn tài khoản.

## Các loại voucher

1. **Giảm giá cố định (fixed_discount)**: Giảm một số tiền cố định cho đơn hàng.
2. **Giảm giá theo phần trăm (percentage_discount)**: Giảm giá theo % trên tổng đơn hàng, có thể thiết lập mức giảm tối đa.
3. **Tăng thời hạn sử dụng (extend_duration)**: Tăng thêm số tháng sử dụng dịch vụ mà không giảm giá tiền.

## Cấu trúc bảng dữ liệu

Bảng voucher (`voucher`):
- `id`: Khóa chính
- `code`: Mã voucher duy nhất
- `voucher_type`: Loại voucher (fixed_discount, percentage_discount, extend_duration)
- `discount_value`: Giá trị giảm giá (số tiền cố định, % giảm hoặc số tháng tăng thêm)
- `max_discount`: Giới hạn giảm giá tối đa (cho voucher phần trăm)
- `min_order_value`: Giá trị đơn hàng tối thiểu để áp dụng
- `quantity`: Tổng số lượt sử dụng cho phép (NULL = không giới hạn)
- `used_quantity`: Số lượt đã sử dụng
- `start_date`: Ngày bắt đầu
- `end_date`: Ngày kết thúc
- `is_active`: Trạng thái kích hoạt
- `created_at`: Thời điểm tạo
- `updated_at`: Thời điểm cập nhật

Liên kết với bảng transaction_history:
- Thêm cột `voucher_id` trong bảng `transaction_history` trỏ đến `voucher.id`

## Quy trình hoạt động

### 1. Áp dụng voucher trong thanh toán
- Người dùng nhập mã voucher trên trang thanh toán
- Hệ thống kiểm tra tính hợp lệ của voucher (còn hạn, còn lượt sử dụng, đủ giá trị tối thiểu)
- Nếu hợp lệ, áp dụng voucher vào đơn hàng và lưu thông tin voucher vào session
- Hiển thị số tiền đã giảm và cập nhật tổng thanh toán
- Cập nhật mã QR và thông tin thanh toán

### 2. Lưu thông tin voucher vào giao dịch
- Khi người dùng tiến hành thanh toán, voucher_id được lưu vào bảng transaction_history
- Voucher_id được tự động cập nhật khi tạo giao dịch mua mới hoặc gia hạn

### 3. Cập nhật số lượt sử dụng
- Khi giao dịch được xác nhận hoàn tất (status = "completed"), hệ thống tự động tăng số lượt sử dụng của voucher (used_quantity + 1)
- Nếu giao dịch bị hủy hoặc thất bại, voucher không bị tính lượt sử dụng

## Cách thức thực hiện

### Frontend:
- Form nhập và xác nhận voucher trên trang thanh toán
- Hiển thị thông tin voucher đã áp dụng
- Cập nhật tổng tiền và mã QR thanh toán khi áp dụng/xóa voucher
- Xử lý AJAX để gửi/nhận thông tin voucher

### Backend:
- Lớp `Voucher`: Xử lý việc xác thực, áp dụng và quản lý voucher
- Lưu voucher_id vào transaction_history khi tạo giao dịch mới
- Cập nhật số lượt sử dụng khi giao dịch hoàn tất
- Xử lý voucher trong các quy trình thanh toán khác nhau (mua mới, gia hạn)

## Lưu ý
- Chỉ cho phép áp dụng một voucher cho mỗi giao dịch
- Voucher chỉ được tính là đã sử dụng khi giao dịch hoàn tất
- Người dùng có thể xóa voucher đã áp dụng để nhập voucher khác

## Các file có liên quan
- `private/classes/Voucher.php`: Lớp xử lý voucher
- `private/action/purchase/apply_voucher.php`: Action xử lý áp dụng voucher
- `private/action/purchase/remove_voucher.php`: Action xử lý xóa voucher
- `private/action/purchase/process_order.php`: Xử lý đơn hàng mới (lưu voucher_id)
- `private/action/purchase/process_renewal.php`: Xử lý gia hạn (lưu voucher_id)
- `private/action/purchase/process_trial_activation.php`: Xử lý kích hoạt dùng thử
- `private/action/transaction/update_status.php`: Cập nhật trạng thái giao dịch và xử lý voucher
- `public/pages/purchase/payment.php`: Trang thanh toán với UI voucher
- `db/migrations/Nguyen_10052025_add_voucher_table.sql`: Migration tạo bảng voucher

## Các cải tiến đã thực hiện
1. Cập nhật mã QR và thông tin thanh toán khi voucher được áp dụng/xóa
2. Lưu voucher_id vào transaction_history khi tạo giao dịch
3. Tăng số lượt sử dụng voucher khi giao dịch hoàn tất
4. Cải thiện xử lý lỗi và hiển thị thông báo
5. Tạo các phương thức hỗ trợ quản lý voucher

## Cách quản lý voucher
Quản trị viên có thể tạo và quản lý voucher thông qua giao diện quản trị (cần phát triển thêm). Mỗi voucher có thể được cấu hình với các thông số cụ thể như loại giảm giá, giá trị giảm giá, số lượt sử dụng và thời hạn hiệu lực.
