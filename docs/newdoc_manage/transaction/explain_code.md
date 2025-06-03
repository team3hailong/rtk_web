# Giải thích cấu trúc code quản lý giao dịch

## Cấu trúc tổng quan

Hệ thống quản lý giao dịch được tổ chức theo mô hình phân tách UI và logic xử lý:

1. **UI (User Interface)**: Nằm trong thư mục `public/pages/transaction.php`
2. **Xử lý (Backend)**: Nằm trong thư mục `private/action/transaction/`
3. **Service Classes**: Nằm trong thư mục `private/classes/Transaction.php`
4. **Utility Functions**: Các hàm tiện ích trong `private/utils/`

### Luồng xử lý yêu cầu giao dịch

```
[Giao diện người dùng] -> [Action Handler] -> [Xử lý Backend] -> [Cơ sở dữ liệu]
   (public/pages)       (public/handlers)   (private/action)      (Database)
```

## Files quan trọng và chức năng

### 1. Trang giao diện người dùng

- **`public/pages/transaction.php`**: Hiển thị danh sách giao dịch và các chức năng quản lý
  - Hiển thị bảng giao dịch với phân trang và bộ lọc
  - Modal xem chi tiết giao dịch
  - Chức năng yêu cầu xuất hóa đơn VAT
  - Chức năng xuất hóa đơn bán lẻ

- **`public/assets/js/pages/transaction.js`**: JavaScript xử lý tương tác người dùng
  - Xử lý hiển thị và đóng modal chi tiết giao dịch
  - Xử lý lọc và tìm kiếm dữ liệu
  - Xử lý xuất hóa đơn bán lẻ
  - Xử lý phân trang động

### 2. Classes xử lý giao dịch

- **`private/classes/Transaction.php`**
  - Quản lý các thao tác CRUD với giao dịch
  - Các phương thức chính:
    - `getTransactionsByUserIdWithPagination()`: Lấy danh sách giao dịch với phân trang và lọc
    - `getTransactionByIdAndUser()`: Lấy thông tin chi tiết giao dịch theo ID
    - `updateTransactionStatus()`: Cập nhật trạng thái giao dịch
    - `uploadPaymentProof()`: Tải lên minh chứng thanh toán
    - `getTransactionStatusDisplay()`: Lấy thông tin hiển thị trạng thái

- **`private/classes/InvoiceService.php`**
  - Xử lý các yêu cầu xuất hóa đơn
  - Các phương thức chính:
    - `getTransactionInfo()`: Lấy thông tin giao dịch và đăng ký
    - `checkOwnership()`: Kiểm tra quyền sở hữu giao dịch
    - `createInvoice()`: Tạo yêu cầu hóa đơn mới

### 3. Action handlers

- **`private/action/transaction/update_status.php`**
  - Cập nhật trạng thái giao dịch
  - Xử lý các tác vụ liên quan (cập nhật trạng thái registration, xử lý voucher, tính toán hoa hồng giới thiệu)

- **`private/action/transaction/upload_proof.php`**
  - Xử lý tải lên minh chứng thanh toán

- **`public/handlers/export_retail_invoice.php`**
  - Xử lý xuất hóa đơn bán lẻ
  - Tạo file PDF hoặc ZIP chứa các hóa đơn

### 4. Utilities và Functions

- **`private/utils/invoice_helper.php`**
  - Các hàm hỗ trợ xử lý hóa đơn
  - Tạo mẫu hóa đơn bán lẻ

- **`private/utils/functions.php`**
  - Các hàm tiện ích dùng chung

## Cơ sở dữ liệu

### Bảng chính

1. **`transaction_history`**: Lưu thông tin giao dịch
   - Khóa chính: `id`
   - Các trường chính: `user_id`, `registration_id`, `transaction_type`, `amount`, `status`, `payment_method`, `payment_image`, `voucher_id`
   - Tham chiếu: `user_id`, `registration_id`, `voucher_id`

2. **`registration`**: Lưu thông tin đăng ký dịch vụ
   - Khóa chính: `id`
   - Các trường chính: `user_id`, `package_id`, `location_id`, `status`, `payment_status`

3. **`invoice`**: Lưu thông tin hóa đơn
   - Khóa chính: `id`
   - Các trường chính: `transaction_history_id`, `status`, `invoice_number`, `invoice_date`, `invoice_file`
   - Tham chiếu: `transaction_history_id`

## Các mối quan hệ giữa các bảng

- Mỗi giao dịch (`transaction_history`) có thể liên kết với một đăng ký (`registration`)
- Mỗi giao dịch (`transaction_history`) có thể có nhiều yêu cầu hóa đơn (`invoice`)
- Mỗi giao dịch (`transaction_history`) thuộc về một người dùng (`user`)
- Mỗi giao dịch (`transaction_history`) có thể sử dụng một voucher (`voucher`)

## Công nghệ sử dụng

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: JavaScript, HTML5, CSS3
- **Thư viện PDF**: MPDF để tạo hóa đơn bán lẻ
- **AJAX**: Sử dụng fetch API để xử lý yêu cầu không đồng bộ
