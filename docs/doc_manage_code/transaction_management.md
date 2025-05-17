# Tài liệu Hệ thống Quản lý Giao dịch

## 1. Chi tiết chức năng và trang

### 1.1. Trang Quản lý Giao dịch
- **Tệp tin UI**: `public/pages/transaction.php`
- **Lớp xử lý**: `private/classes/Transaction.php`
- **Chức năng**: Hiển thị, quản lý và thao tác với lịch sử giao dịch của người dùng, bao gồm xem chi tiết giao dịch, lọc theo trạng thái, tìm kiếm, xuất hóa đơn và theo dõi trạng thái thanh toán.

### 1.2. Chức năng xem danh sách giao dịch
- Hiển thị danh sách giao dịch với thông tin cơ bản (ID, thời gian, số tiền, phương thức, trạng thái)
- Hỗ trợ phân trang và lọc theo trạng thái (tất cả, hoàn thành, chờ xử lý, thất bại)
- Tìm kiếm theo ID giao dịch hoặc loại giao dịch

### 1.3. Chức năng xem chi tiết giao dịch
- Xem thông tin chi tiết từng giao dịch bao gồm ID, thời gian tạo, cập nhật cuối, loại giao dịch, số tiền, phương thức thanh toán, trạng thái
- Xem lý do từ chối đối với giao dịch thất bại
- Xem ảnh minh chứng thanh toán nếu có

### 1.4. Chức năng yêu cầu xuất hóa đơn
- Cho phép người dùng yêu cầu xuất hóa đơn VAT cho giao dịch đã hoàn thành
- Xác thực thông tin công ty và mã số thuế trước khi yêu cầu xuất hóa đơn
- Theo dõi trạng thái yêu cầu hóa đơn

### 1.5. Chức năng xuất hóa đơn bán lẻ
- Cho phép người dùng chọn và xuất nhiều hóa đơn bán lẻ cùng lúc
- Giới hạn số lượng hóa đơn có thể xuất cùng lúc (tối đa 5)
- Tạo file PDF hoặc ZIP chứa các hóa đơn

## 2. Điểm quan trọng hình thành chức năng

### 2.1. Mô hình dữ liệu và cơ sở dữ liệu

#### 2.1.1. Bảng transaction_history
Lưu trữ thông tin về các giao dịch của người dùng.

```sql
CREATE TABLE `transaction_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(50) NOT NULL COMMENT 'purchase, renewal, refund, etc',
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, completed, failed, cancelled, refunded',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_image` varchar(255) DEFAULT NULL COMMENT 'Path to payment proof image',
  `voucher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `registration_id` (`registration_id`),
  KEY `voucher_id` (`voucher_id`),
  CONSTRAINT `transaction_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_history_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaction_history_ibfk_3` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.2. Bảng registration
Lưu trữ thông tin đăng ký dịch vụ liên kết với giao dịch.

```sql
CREATE TABLE `registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, active, expired, cancelled',
  `payment_status` varchar(50) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid, partially_paid, paid, refunded',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.3. Bảng invoice
Lưu trữ thông tin về các yêu cầu và trạng thái hóa đơn.

```sql
CREATE TABLE `invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_history_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, completed, failed',
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_file` varchar(255) DEFAULT NULL COMMENT 'Path to invoice PDF file',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `transaction_history_id` (`transaction_history_id`),
  CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`transaction_history_id`) REFERENCES `transaction_history` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2.2. Lớp Transaction

Lớp `Transaction` xử lý tất cả các tác vụ liên quan đến quản lý giao dịch:

```php
class Transaction {
    private $db; // Database connection object

    public function __construct(Database $db) {
        $this->db = $db;
    }

    // Lấy giao dịch của người dùng với phân trang và lọc
    public function getTransactionsByUserIdWithPagination(int $userId, int $currentPage = 1, int $perPage = 10, string $filter = 'all'): array {
        // Xây dựng truy vấn với các điều kiện lọc
        // Thực hiện truy vấn và xử lý kết quả
        // Trả về thông tin giao dịch và thông tin phân trang
    }

    // Lấy thông tin chi tiết giao dịch theo ID
    public function getTransactionByIdAndUser(int $transactionId, int $userId) {
        // Truy vấn và trả về thông tin chi tiết giao dịch
    }

    // Cập nhật trạng thái giao dịch
    public function updateTransactionStatus($transactionId, $status, $updateVoucher = true) {
        // Cập nhật trạng thái giao dịch trong cơ sở dữ liệu
        // Xử lý các tác vụ liên quan như cập nhật voucher nếu cần
    }

    // Lấy thông tin hiển thị trạng thái giao dịch
    public static function getTransactionStatusDisplay($status) {
        // Trả về văn bản và lớp CSS cho mỗi trạng thái
    }
}
```

### 2.3. Quản lý phiên và xác thực

Trang quản lý giao dịch yêu cầu người dùng phải đăng nhập:

```php
// Kiểm tra xác thực người dùng
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php');
    exit;
}
```

### 2.4. Xử lý phân trang

Xử lý phân trang cho danh sách giao dịch, cho phép người dùng dễ dàng điều hướng qua các trang kết quả:

```php
// Xử lý tham số từ URL cho phân trang
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
// Chỉ cho phép các giá trị cụ thể cho per_page
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10; // Mặc định
}
```

### 2.5. Xử lý lọc dữ liệu

Cung cấp chức năng lọc để người dùng dễ dàng tìm kiếm giao dịch theo trạng thái:

```php
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
// Chỉ cho phép các filter hợp lệ
if (!in_array($filter, ['all', 'completed', 'pending', 'failed', 'cancelled'])) {
    $filter = 'all'; // Mặc định
}
```

### 2.6. Xử lý JavaScript phía client

File JavaScript `transaction.js` xử lý các tương tác phía client:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý hiển thị và đóng modal chi tiết giao dịch
    // Xử lý lọc và tìm kiếm dữ liệu
    // Xử lý xuất hóa đơn bán lẻ
});
```

### 2.7. Lớp InvoiceService

Lớp `InvoiceService` xử lý các yêu cầu xuất hóa đơn:

```php
class InvoiceService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy thông tin giao dịch và đăng ký
    public function getTransactionInfo(int $tx_id) {
        // Truy vấn và trả về thông tin giao dịch
    }

    // Kiểm tra quyền sở hữu giao dịch
    public function checkOwnership(int $tx_id, int $user_id): bool {
        // Xác minh người dùng có quyền truy cập giao dịch
    }

    // Tạo yêu cầu hóa đơn mới
    public function createInvoice(int $tx_id): void {
        // Tạo bản ghi hóa đơn mới với trạng thái "pending"
        // Ghi log hoạt động
    }
}
```

## 3. Các luồng xử lý của chức năng

### 3.1. Luồng xem danh sách giao dịch

1. **Truy cập trang quản lý**:
   - Người dùng truy cập trang `transaction.php`
   - Hệ thống kiểm tra xác thực người dùng (đã đăng nhập hay chưa)
   - Lấy các tham số phân trang và lọc từ URL

2. **Truy vấn và hiển thị dữ liệu**:
   - Hệ thống gọi `Transaction.getTransactionsByUserIdWithPagination()` để lấy dữ liệu
   - Xử lý dữ liệu trả về, định dạng thông tin hiển thị
   - Hiển thị danh sách giao dịch với thông tin cơ bản

3. **Tùy chỉnh hiển thị**:
   - Người dùng có thể thay đổi số lượng giao dịch hiển thị trên mỗi trang
   - Người dùng có thể lọc giao dịch theo trạng thái
   - Hệ thống cập nhật URL và tải lại dữ liệu khi thay đổi tùy chọn

### 3.2. Luồng xem chi tiết giao dịch

1. **Mở modal chi tiết**:
   - Người dùng nhấp vào nút "Chi tiết" của một giao dịch
   - JavaScript bắt sự kiện và hiển thị modal với thông tin chi tiết

2. **Hiển thị thông tin**:
   - Modal hiển thị ID giao dịch, thời gian tạo, cập nhật cuối, loại giao dịch, số tiền, phương thức thanh toán, trạng thái
   - Hiển thị lý do từ chối nếu giao dịch thất bại
   - Hiển thị ảnh minh chứng thanh toán nếu có

3. **Đóng modal**:
   - Người dùng có thể đóng modal bằng cách nhấp vào nút "Đóng", bấm phím ESC hoặc nhấp bên ngoài modal

### 3.3. Luồng yêu cầu xuất hóa đơn VAT

1. **Bắt đầu yêu cầu**:
   - Người dùng nhấp vào nút "Hóa đơn" của một giao dịch đã hoàn thành
   - Hệ thống chuyển hướng đến trang `request_export_invoice.php` với tham số giao dịch ID

2. **Kiểm tra điều kiện**:
   - Hệ thống xác minh người dùng có quyền truy cập giao dịch
   - Kiểm tra thông tin công ty và mã số thuế của người dùng đã đầy đủ chưa

3. **Xử lý yêu cầu**:
   - Nếu thiếu thông tin, hiển thị cảnh báo và liên kết đến trang cập nhật thông tin
   - Nếu đủ điều kiện, hiển thị form xác nhận yêu cầu
   - Khi người dùng xác nhận, gọi `InvoiceService.createInvoice()` để tạo yêu cầu hóa đơn mới
   - Ghi log hoạt động và chuyển hướng về trang giao dịch với thông báo thành công

### 3.4. Luồng xuất hóa đơn bán lẻ

1. **Chọn giao dịch**:
   - Người dùng chọn một hoặc nhiều giao dịch bằng cách đánh dấu vào ô kiểm
   - JavaScript kiểm tra số lượng đã chọn và cập nhật trạng thái nút "Xuất HĐ bán lẻ"
   - Hiển thị cảnh báo nếu chọn quá 5 giao dịch

2. **Xử lý yêu cầu xuất**:
   - Người dùng nhấp vào nút "Xuất HĐ bán lẻ"
   - JavaScript gửi request AJAX đến `export_retail_invoice.php` với danh sách ID giao dịch
   - Server xử lý yêu cầu, tạo file PDF hoặc ZIP chứa các hóa đơn

3. **Tải xuống file**:
   - Server trả về file PDF (một hóa đơn) hoặc ZIP (nhiều hóa đơn)
   - JavaScript xử lý response, tạo liên kết tải xuống và tự động tải file về máy người dùng

## 4. Các lỗi có thể phát sinh và cách sửa

### 4.1. Lỗi hiển thị danh sách giao dịch trống

- **Triệu chứng**: Không hiển thị bất kỳ giao dịch nào, dù người dùng đã có giao dịch.
- **Nguyên nhân**:
  - Lỗi trong truy vấn SQL
  - Tham số user_id không được truyền đúng
  - Lỗi kết nối database
- **Giải pháp**:
  - Kiểm tra lại truy vấn SQL trong phương thức `getTransactionsByUserIdWithPagination()`
  - Xác nhận rằng session `user_id` đang hoạt động chính xác
  - Kiểm tra logs lỗi database
  - Thêm xử lý lỗi chi tiết hơn và ghi log

```php
// Ví dụ: Thêm xử lý lỗi và logging
try {
    $result = $transactionHandler->getTransactionsByUserIdWithPagination($user_id, $currentPage, $perPage, $filter);
    $transactions = $result['transactions'];
    $pagination = $result['pagination'];
} catch (Exception $e) {
    error_log("Error retrieving transactions: " . $e->getMessage());
    $transactions = [];
    $pagination = ['total' => 0, 'per_page' => $perPage, 'current_page' => $currentPage, 'total_pages' => 0];
    $error_message = "Không thể tải danh sách giao dịch. Vui lòng thử lại sau.";
}
```

### 4.2. Lỗi phân trang

- **Triệu chứng**: Phân trang hiển thị không chính xác, thiếu trang hoặc hiển thị sai số lượng bản ghi.
- **Nguyên nhân**:
  - Lỗi tính toán số trang
  - Tham số phân trang không được xử lý đúng
  - Lỗi truy vấn LIMIT/OFFSET trong SQL
- **Giải pháp**:
  - Kiểm tra lại logic tính toán số trang
  - Đảm bảo các tham số phân trang được validate đúng
  - Kiểm tra lại truy vấn SQL, đặc biệt là phần LIMIT và OFFSET

```php
// Ví dụ: Xác thực tham số phân trang chặt chẽ hơn
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($perPage, [10, 20, 50])) {
    $perPage = 10; // Mặc định
}

// Kiểm tra logic tính tổng số trang
$totalPages = max(1, ceil($totalRecords / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages; // Điều chỉnh lại nếu vượt quá tổng số trang
}
```

### 4.3. Lỗi khi yêu cầu xuất hóa đơn

- **Triệu chứng**: Không thể yêu cầu xuất hóa đơn hoặc hệ thống hiển thị lỗi.
- **Nguyên nhân**:
  - Thiếu thông tin công ty hoặc mã số thuế
  - Lỗi khi tạo bản ghi hóa đơn
  - Lỗi quyền truy cập
- **Giải pháp**:
  - Kiểm tra đầy đủ thông tin người dùng trước khi cho phép yêu cầu hóa đơn
  - Thêm xử lý lỗi và ghi log chi tiết
  - Cải thiện thông báo lỗi cho người dùng

```php
// Ví dụ: Kiểm tra thông tin người dùng trước khi cho phép yêu cầu hóa đơn
$user_info = $service->getUserInfo($_SESSION['user_id']);
if (empty($user_info['company_name']) || empty($user_info['tax_code'])) {
    log_invoice_error($_SESSION['user_id'], $tx_id, 'Missing company_name or tax_code');
    $_SESSION['invoice_error'] = 'Vui lòng cập nhật đầy đủ thông tin công ty và mã số thuế trước khi yêu cầu xuất hóa đơn.';
    header('Location: ' . $base_url . '/public/pages/setting/invoice.php?error=missing_info');
    exit;
}
```

### 4.4. Lỗi khi xuất hóa đơn bán lẻ

- **Triệu chứng**: Không thể xuất hóa đơn bán lẻ hoặc file không tải xuống.
- **Nguyên nhân**:
  - Lỗi xử lý AJAX
  - Lỗi khi tạo file PDF hoặc ZIP
  - Vấn đề về quyền truy cập file
- **Giải pháp**:
  - Thêm xử lý lỗi chi tiết trong AJAX
  - Kiểm tra quyền truy cập và tạo thư mục
  - Cải thiện thông báo lỗi

```javascript
// Ví dụ: Cải thiện xử lý lỗi trong AJAX
fetch('/public/handlers/export_retail_invoice.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ transaction_ids: checked.map(cb => cb.value) })
})
.then(response => {
    if (!response.ok) throw response;
    return response.blob();
})
.then(blob => {
    // Xử lý tải xuống file
})
.catch(async err => {
    let errorMsg = 'Lỗi khi xuất hóa đơn bán lẻ.';
    if (err && err.json) {
        try {
            const data = await err.json();
            if (data && data.error) errorMsg = data.error;
        } catch (e) {
            console.error('Error parsing error response:', e);
        }
    }
    msg.textContent = errorMsg;
    console.error('Export error:', err);
})
```

## 5. Các dự kiến phát triển trong tương lai

### 5.1. Tích hợp thanh toán trực tuyến

- **Mô tả**: Tích hợp các cổng thanh toán trực tuyến (VNPAY, Momo, ZaloPay) để cho phép người dùng thanh toán trực tiếp từ trang web.
- **Các thay đổi cần thiết**:
  - Thêm các lớp xử lý cho từng cổng thanh toán
  - Mở rộng bảng `transaction_history` để lưu thêm thông tin thanh toán
  - Thêm luồng xử lý callback từ các cổng thanh toán
  - Cập nhật giao diện người dùng để hiển thị các phương thức thanh toán mới

### 5.2. Tính năng báo cáo và thống kê tài chính

- **Mô tả**: Thêm tính năng tạo báo cáo và thống kê tài chính cho người dùng, giúp họ theo dõi chi tiêu và lịch sử giao dịch tốt hơn.
- **Các thay đổi cần thiết**:
  - Tạo lớp mới `TransactionReporting` để xử lý việc tạo báo cáo
  - Thêm trang hiển thị biểu đồ và thống kê
  - Hỗ trợ xuất báo cáo dưới dạng PDF, Excel
  - Thêm bộ lọc và tùy chọn tùy chỉnh báo cáo

### 5.3. Tự động hóa quy trình xuất hóa đơn

- **Mô tả**: Tự động hóa quy trình xuất hóa đơn VAT thông qua tích hợp với các nhà cung cấp dịch vụ hóa đơn điện tử.
- **Các thay đổi cần thiết**:
  - Tích hợp API với nhà cung cấp dịch vụ hóa đơn điện tử
  - Tự động hóa việc tạo và gửi hóa đơn điện tử
  - Thêm chức năng theo dõi trạng thái hóa đơn điện tử
  - Cải thiện thông báo cho người dùng về tiến trình xuất hóa đơn

### 5.4. Cải thiện giao diện người dùng trên thiết bị di động

- **Mô tả**: Tối ưu hóa giao diện quản lý giao dịch cho thiết bị di động để cải thiện trải nghiệm người dùng.
- **Các thay đổi cần thiết**:
  - Thiết kế lại bảng giao dịch để hiển thị tốt hơn trên màn hình nhỏ
  - Thêm các tương tác touch-friendly
  - Tối ưu hóa modal chi tiết giao dịch cho thiết bị di động
  - Cải thiện hiệu suất tải trang

### 5.5. Hệ thống thông báo và nhắc nhở thanh toán

- **Mô tả**: Thêm hệ thống thông báo tự động để nhắc nhở người dùng về các giao dịch chờ thanh toán hoặc hóa đơn sắp phát hành.
- **Các thay đổi cần thiết**:
  - Tạo lớp `NotificationService` để xử lý thông báo
  - Thêm tùy chọn cho người dùng cài đặt thông báo
  - Tích hợp thông báo qua email và trình duyệt
  - Tự động hóa lịch trình gửi thông báo

### 5.6. Tích hợp với hệ thống kế toán

- **Mô tả**: Kết nối hệ thống giao dịch với các phần mềm kế toán phổ biến để giúp doanh nghiệp dễ dàng quản lý tài chính.
- **Các thay đổi cần thiết**:
  - Tạo API để xuất/nhập dữ liệu giao dịch
  - Hỗ trợ các định dạng file phổ biến cho kế toán (CSV, XML)
  - Tích hợp với các phần mềm kế toán như MISA, Fast, 3B
  - Thêm tính năng đối soát và kiểm tra dữ liệu
