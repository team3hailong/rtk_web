# Logic xử lý quản lý giao dịch

## 1. Logic hiển thị danh sách giao dịch

### Quy trình hiển thị giao dịch

1. **Xác thực người dùng**
   - Kiểm tra đăng nhập thông qua `$_SESSION['user_id']`
   - Chuyển hướng về trang đăng nhập nếu chưa đăng nhập

2. **Xử lý tham số URL**
   - Lấy trang hiện tại từ `$_GET['page']`, mặc định là 1
   - Lấy số lượng hiển thị mỗi trang từ `$_GET['per_page']`, chỉ cho phép các giá trị 10, 20, 50
   - Lấy bộ lọc trạng thái từ `$_GET['filter']`, chỉ cho phép các giá trị all, completed, pending, failed, cancelled

3. **Truy vấn dữ liệu**
   - Gọi `Transaction.getTransactionsByUserIdWithPagination()` để lấy dữ liệu
   - Áp dụng các điều kiện lọc và phân trang vào truy vấn SQL
   - Trả về mảng chứa danh sách giao dịch và thông tin phân trang

4. **Định dạng dữ liệu hiển thị**
   - Format thời gian, số tiền
   - Chuyển đổi trạng thái sang văn bản và lớp CSS tương ứng
   - Chuẩn bị dữ liệu JSON cho JavaScript

5. **Render giao diện**
   - Hiển thị bảng giao dịch với dữ liệu đã định dạng
   - Hiển thị phân trang
   - Render các điều khiển lọc và tìm kiếm

## 2. Logic xem chi tiết giao dịch

### Quy trình hiển thị chi tiết

1. **Bắt sự kiện hiển thị modal**
   - Người dùng nhấp vào nút "Chi tiết"
   - JavaScript bắt sự kiện và lấy dữ liệu giao dịch từ thuộc tính `data-transaction`
   - Parse dữ liệu JSON và điền vào modal

2. **Hiển thị thông tin**
   - Hiển thị ID giao dịch, thời gian tạo, cập nhật cuối
   - Hiển thị loại giao dịch, số tiền, phương thức thanh toán
   - Hiển thị trạng thái với badge màu tương ứng
   - Hiển thị lý do từ chối nếu giao dịch thất bại
   - Hiển thị ảnh minh chứng nếu có

3. **Tương tác**
   - Cho phép đóng modal bằng nút "X", nhấp bên ngoài hoặc phím ESC
   - Tùy theo trạng thái giao dịch, hiển thị các tùy chọn khác nhau (yêu cầu hóa đơn VAT, tải lên minh chứng)

## 3. Logic yêu cầu xuất hóa đơn VAT

### Quy trình yêu cầu xuất hóa đơn

1. **Kiểm tra điều kiện**
   - Giao dịch phải có trạng thái "completed"
   - Chưa có yêu cầu hóa đơn hoặc yêu cầu trước đó đã thất bại
   - Người dùng phải đã cập nhật đầy đủ thông tin công ty và mã số thuế

2. **Xử lý yêu cầu**
   - Người dùng nhấp vào nút "Hóa đơn" của một giao dịch
   - Chuyển hướng đến trang `request_export_invoice.php` với tham số ID giao dịch
   - Gọi `InvoiceService.getTransactionInfo()` để lấy thông tin giao dịch và đăng ký
   - Kiểm tra thông tin công ty và mã số thuế của người dùng

3. **Tạo yêu cầu hóa đơn**
   - Nếu đủ điều kiện, hiển thị form xác nhận yêu cầu
   - Khi người dùng xác nhận, gọi `InvoiceService.createInvoice()` để tạo yêu cầu mới
   - Lưu bản ghi trong bảng `invoice` với trạng thái "pending"
   - Ghi log hoạt động người dùng

4. **Thông báo kết quả**
   - Hiển thị thông báo thành công hoặc thất bại
   - Chuyển hướng về trang giao dịch

## 4. Logic xuất hóa đơn bán lẻ

### Quy trình xuất hóa đơn bán lẻ

1. **Chọn giao dịch**
   - Người dùng chọn một hoặc nhiều giao dịch bằng cách đánh dấu vào ô kiểm
   - JavaScript kiểm tra số lượng đã chọn và cập nhật trạng thái nút "Xuất HĐ bán lẻ"
   - Hiển thị cảnh báo nếu người dùng chọn quá 5 giao dịch

2. **Gửi yêu cầu xuất**
   - Người dùng nhấp vào nút "Xuất HĐ bán lẻ"
   - JavaScript thu thập ID của các giao dịch đã chọn và gửi yêu cầu AJAX đến `export_retail_invoice.php`
   - Server xác thực quyền sở hữu giao dịch và tình trạng hoàn thành

3. **Tạo file hóa đơn**
   - Server sử dụng MPDF để tạo file PDF cho mỗi giao dịch
   - Nếu chỉ một giao dịch, trả về file PDF đơn lẻ
   - Nếu nhiều giao dịch, tạo file ZIP chứa tất cả PDF

4. **Tải xuống file**
   - Server trả về file với các header phù hợp
   - JavaScript xử lý response, tạo link tải xuống tạm thời
   - Người dùng tự động được tải xuống file

## 5. Logic xử lý trạng thái giao dịch

### Quy trình cập nhật trạng thái

1. **Nhận thông tin cập nhật**
   - Lấy `transaction_id`, `status`, và `update_voucher` từ request
   - Xác thực các tham số: ID phải là số dương, trạng thái phải nằm trong danh sách cho phép

2. **Cập nhật trạng thái giao dịch**
   - Gọi `Transaction.updateTransactionStatus()` để cập nhật trong database
   - Bắt đầu transaction SQL để đảm bảo tính nhất quán của dữ liệu

3. **Xử lý tác vụ liên quan**
   - Nếu trạng thái mới là "completed":
     - Cập nhật trạng thái của registration liên quan thành "active"
     - Xử lý voucher (tăng số lần sử dụng)
     - Tính toán hoa hồng giới thiệu nếu có
   - Ghi log hoạt động

4. **Phản hồi kết quả**
   - Trả về kết quả thông qua JSON
   - Thông báo thành công hoặc thất bại cùng với chi tiết

### Xử lý với voucher

- Khi giao dịch được hoàn thành và có sử dụng voucher:
  - Gọi `Voucher.incrementUsage()` để tăng số lần sử dụng
  - Kiểm tra xem voucher còn đạt giới hạn sử dụng không
  - Cập nhật trạng thái voucher nếu đạt giới hạn

### Xử lý hoa hồng giới thiệu

- Khi giao dịch được hoàn thành:
  - Kiểm tra người dùng có được giới thiệu không
  - Gọi `Referral.calculateCommission()` để tính và ghi nhận hoa hồng
  - Xử lý các tình huống đặc biệt như gói dùng thử

## 6. Logic tải lên minh chứng thanh toán

### Quy trình tải lên minh chứng

1. **Kiểm tra điều kiện**
   - Giao dịch phải tồn tại
   - Người dùng phải là chủ sở hữu của giao dịch
   - Trạng thái giao dịch phải là "pending" hoặc "failed"

2. **Xử lý file tải lên**
   - Kiểm tra loại file (chỉ cho phép hình ảnh: JPG, JPEG, PNG)
   - Kiểm tra kích thước file (tối đa 5MB)
   - Tạo tên file duy nhất và lưu vào thư mục uploads

3. **Cập nhật thông tin giao dịch**
   - Lưu đường dẫn file vào trường `payment_image` của bảng `transaction_history`
   - Cập nhật trạng thái giao dịch thành "pending" nếu trước đó là "failed"
   - Ghi log hoạt động

4. **Thông báo kết quả**
   - Hiển thị thông báo thành công hoặc thất bại
   - Chuyển hướng về trang giao dịch
