# Tài liệu Hệ thống Mua hàng (Purchase)

## 1. Chi tiết chức năng và trang

### 1.1. Trang danh sách gói dịch vụ (Packages)
- **Tệp tin UI**: `public/pages/purchase/packages.php`
- **Lớp xử lý**: `private/classes/purchase/PurchaseService.php`
- **Chức năng**: Hiển thị danh sách các gói dịch vụ để người dùng lựa chọn, bao gồm thông tin về giá, thời gian sử dụng và các tính năng của từng gói.

### 1.2. Trang chi tiết gói dịch vụ (Details)
- **Tệp tin UI**: `public/pages/purchase/details.php`
- **Lớp xử lý**: `private/classes/purchase/PurchaseService.php`
- **Chức năng**: Hiển thị thông tin chi tiết về gói dịch vụ đã chọn và cho phép người dùng nhập các thông tin cần thiết để tiến hành đặt hàng (số lượng, vị trí...).

### 1.3. Trang thanh toán (Payment)
- **Tệp tin UI**: `public/pages/purchase/payment.php`
- **Lớp xử lý**: `private/classes/purchase/PaymentService.php`
- **Chức năng**: Xử lý thông tin thanh toán, tạo mã QR chuyển khoản VietQR, hiển thị thông tin ngân hàng, mã giảm giá (voucher) và hướng dẫn thanh toán.

### 1.4. Trang tải lên minh chứng thanh toán (Upload Proof)
- **Tệp tin UI**: `public/pages/purchase/upload_proof.php`
- **Lớp xử lý**: `private/classes/purchase/PaymentProofService.php`
- **Chức năng**: Cho phép người dùng tải lên ảnh minh chứng thanh toán sau khi đã chuyển khoản.

### 1.5. Trang gia hạn dịch vụ (Renewal)
- **Tệp tin UI**: `public/pages/purchase/renewal.php`
- **Lớp xử lý**: `private/classes/purchase/RenewalService.php`
- **Chức năng**: Cho phép người dùng gia hạn các tài khoản dịch vụ đã mua trước đó.

### 1.6. Trang thành công (Success)
- **Tệp tin UI**: `public/pages/purchase/success.php`
- **Lớp xử lý**: `private/classes/purchase/SuccessService.php`
- **Chức năng**: Hiển thị thông báo đặt hàng thành công và hướng dẫn tiếp theo.

## 2. Điểm quan trọng hình thành chức năng

### 2.1. Mô hình dữ liệu và cơ sở dữ liệu

#### 2.1.1. Bảng package
Lưu trữ thông tin về các gói dịch vụ có sẵn trong hệ thống.

```sql
CREATE TABLE `package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` varchar(50) NOT NULL COMMENT 'Định danh gói, ví dụ: basic_1m, premium_1y, trial_7d',
  `name` varchar(100) NOT NULL COMMENT 'Tên gói dịch vụ',
  `price` decimal(15,2) NOT NULL COMMENT 'Giá gói',
  `duration_text` varchar(50) NOT NULL COMMENT 'Chuỗi hiển thị thời gian, ví dụ: 1 Tháng, 1 Năm',
  `duration_days` int(11) NOT NULL COMMENT 'Thời gian sử dụng tính bằng ngày',
  `features_json` text DEFAULT NULL COMMENT 'Danh sách tính năng dạng JSON',
  `is_recommended` tinyint(1) DEFAULT 0 COMMENT 'Có được đề xuất không',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Trạng thái kích hoạt',
  `button_text` varchar(50) DEFAULT 'Chọn gói' COMMENT 'Nội dung nút trên UI',
  `savings_text` varchar(100) DEFAULT NULL COMMENT 'Nội dung tiết kiệm, ví dụ: Tiết kiệm 20%',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết về gói',
  `display_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.2. Bảng registration
Lưu trữ thông tin đăng ký sử dụng dịch vụ của người dùng.

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registration_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `registration_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.3. Bảng transaction
Lưu trữ thông tin giao dịch thanh toán.

```sql
CREATE TABLE `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` varchar(50) NOT NULL COMMENT 'purchase, renewal, refund, etc.',
  `payment_method` varchar(50) NOT NULL COMMENT 'bank_transfer, cash, etc.',
  `payment_proof` varchar(255) DEFAULT NULL COMMENT 'Path to proof image',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, verified, cancelled, etc.',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### 2.1.4. Bảng voucher
Lưu trữ thông tin mã giảm giá.

```sql
CREATE TABLE `voucher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` varchar(20) NOT NULL COMMENT 'percent, fixed_amount',
  `discount_value` decimal(15,2) NOT NULL,
  `min_purchase` decimal(15,2) DEFAULT NULL,
  `max_discount` decimal(15,2) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `limit_usage` int(11) DEFAULT NULL COMMENT 'Số lần sử dụng tối đa',
  `current_usage` int(11) DEFAULT 0 COMMENT 'Số lần đã sử dụng',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, inactive, expired',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2.2. Xử lý luồng mua hàng

#### 2.2.1. Class PurchaseService
Xử lý các thao tác cơ bản liên quan đến mua hàng.

```php
class PurchaseService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Kiểm tra người dùng đã có đăng ký nào chưa
    public function userHasRegistration(int $user_id): bool {
        $stmt = $this->conn->prepare('SELECT 1 FROM registration WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        return (bool) $stmt->fetchColumn();
    }

    // Lấy tất cả các gói dịch vụ đang active
    public function getAllPackages(): array {
        $stmt = $this->conn->prepare('SELECT package_id, name, price, duration_text, features_json, is_recommended, button_text, savings_text FROM package WHERE is_active = 1 ORDER BY display_order ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy thông tin gói dịch vụ theo package_id
    public function getPackageByVarcharId(?string $varchar_id) {
        $stmt = $this->conn->prepare('SELECT * FROM package WHERE package_id = ? LIMIT 1');
        $stmt->execute([$varchar_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
```

#### 2.2.2. Class PaymentService
Xử lý các thao tác liên quan đến thanh toán.

```php
class PaymentService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Lấy thông tin thanh toán cho trang payment
    public function getPaymentPageDetails($registration_id, $user_id, $session_total_price) {
        // Logic kiểm tra và lấy thông tin thanh toán
        return getPaymentPageDetails($registration_id, $user_id, $session_total_price);
    }

    // Tạo VietQR payload và URL ảnh QR
    public function generateVietQR($amount, $order_description) {
        $payload = generate_vietqr_payload($amount, $order_description);
        $image_url = sprintf(
            "https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s",
            VIETQR_BANK_ID,
            VIETQR_ACCOUNT_NO,
            defined('VIETQR_IMAGE_TEMPLATE') ? VIETQR_IMAGE_TEMPLATE : 'compact2',
            $amount,
            urlencode($order_description),
            urlencode(VIETQR_ACCOUNT_NAME)
        );
        return ['payload' => $payload, 'image_url' => $image_url];
    }
}
```

### 2.3. Xử lý bảo mật

#### 2.3.1. Xác thực người dùng
Mọi trang trong luồng mua hàng đều yêu cầu người dùng phải đăng nhập. Nếu chưa đăng nhập, hệ thống sẽ chuyển hướng về trang đăng nhập.

```php
// Kiểm tra xác thực người dùng
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . '/public/pages/auth/login.php?error=not_logged_in');
    exit;
}
```

#### 2.3.2. CSRF Protection
Sử dụng CSRF token để bảo vệ các form submit.

```php
require_once $project_root_path . '/private/utils/csrf_helper.php';
// In form
echo generate_csrf_input();
// Kiểm tra token
validate_csrf_token($_POST['csrf_token']);
```

#### 2.3.3. Xác thực server-side
Tất cả dữ liệu từ client đều phải được kiểm tra lại ở server, không tin tưởng vào dữ liệu client gửi lên.

```php
// Server-side Price Calculation
$base_price = (float)$package['price'];
$calculated_total_price = $base_price * $quantity;
// So sánh với giá trị gửi từ client
if ($calculated_total_price != $posted_total_price) {
    throw new Exception("Price mismatch detected.");
}
```

### 2.4. Tích hợp với VietQR
Hệ thống sử dụng VietQR để tạo mã QR chuyển khoản, giúp người dùng dễ dàng thanh toán qua ứng dụng ngân hàng.

```php
// Tạo payload và URL ảnh QR
$vietqr_result = $paymentService->generateVietQR($final_amount, $order_description);
$final_qr_payload = $vietqr_result['payload'];
$vietqr_image_url = $vietqr_result['image_url'];
```

### 2.5. Xử lý mã giảm giá (Voucher)
Hệ thống hỗ trợ áp dụng và xóa mã giảm giá trong quá trình thanh toán.

```php
// Áp dụng mã giảm giá
public function applyVoucher($code, $total_price, $session_key = 'order') {
    // Tìm kiếm voucher theo mã
    $stmt = $this->conn->prepare('SELECT * FROM voucher WHERE code = ? AND status = "active" AND (end_date IS NULL OR end_date > NOW()) LIMIT 1');
    $stmt->execute([$code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$voucher) {
        return ['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'];
    }
    
    // Kiểm tra điều kiện sử dụng
    if ($voucher['min_purchase'] && $total_price < $voucher['min_purchase']) {
        return ['success' => false, 'message' => 'Đơn hàng không đủ điều kiện sử dụng mã này'];
    }
    
    // Tính toán giảm giá
    $discount = 0;
    if ($voucher['discount_type'] === 'percent') {
        $discount = $total_price * ($voucher['discount_value'] / 100);
        // Áp dụng giới hạn giảm giá nếu có
        if ($voucher['max_discount'] && $discount > $voucher['max_discount']) {
            $discount = $voucher['max_discount'];
        }
    } else { // fixed_amount
        $discount = $voucher['discount_value'];
        // Đảm bảo không giảm quá tổng đơn hàng
        if ($discount > $total_price) {
            $discount = $total_price;
        }
    }
    
    // Lưu thông tin vào session
    $_SESSION[$session_key]['voucher_id'] = $voucher['id'];
    $_SESSION[$session_key]['voucher_code'] = $voucher['code'];
    $_SESSION[$session_key]['voucher_discount'] = $discount;
    $_SESSION[$session_key]['total_price'] = $total_price - $discount;
    
    return [
        'success' => true,
        'voucher' => $voucher,
        'discount' => $discount,
        'new_total' => $total_price - $discount
    ];
}
```

## 3. Các luồng xử lý của chức năng

### 3.1. Luồng mua gói dịch vụ mới

1. **Hiển thị danh sách gói dịch vụ**:
   - Người dùng truy cập trang `packages.php`
   - Hệ thống lấy danh sách gói dịch vụ từ `PurchaseService.getAllPackages()`
   - Hiển thị các gói dịch vụ theo thứ tự display_order
   - Ẩn gói dùng thử nếu người dùng đã có tài khoản

2. **Xem chi tiết gói dịch vụ**:
   - Người dùng nhấp vào nút "Chọn gói" hoặc tương tự
   - Hệ thống chuyển hướng đến trang `details.php` với package_id là tham số
   - Lấy thông tin chi tiết gói từ `PurchaseService.getPackageByVarcharId()`
   - Hiển thị form đặt hàng với các trường: số lượng, tỉnh/thành phố

3. **Tạo đơn hàng**:
   - Người dùng điền thông tin và gửi form
   - Hệ thống gọi `process_order.php` để xử lý
   - Xác thực dữ liệu đầu vào ở server-side
   - Tính toán giá cuối cùng và thời gian sử dụng
   - Tạo bản ghi mới trong bảng `registration` với status='pending' và payment_status='unpaid'
   - Lưu ID registration vào session (`$_SESSION['pending_registration_id']`)
   - Chuyển hướng đến trang thanh toán `payment.php`

4. **Thanh toán**:
   - Hiển thị thông tin thanh toán: mã đơn hàng, số tiền, thông tin ngân hàng
   - Tạo mã QR VietQR cho chuyển khoản ngân hàng
   - Nếu là gói dùng thử, chuyển thẳng đến bước kích hoạt
   - Người dùng có thể áp dụng mã giảm giá (nếu có)
   - Sau khi chuyển khoản, người dùng nhấp vào nút "Tôi đã thanh toán"
   - Chuyển hướng đến trang tải lên minh chứng `upload_proof.php`

5. **Tải lên minh chứng thanh toán**:
   - Người dùng tải lên ảnh minh chứng thanh toán
   - Hệ thống lưu ảnh vào thư mục uploads
   - Cập nhật bản ghi trong bảng `transaction`
   - Chuyển hướng đến trang thông báo thành công `success.php`

6. **Hoàn tất đơn hàng**:
   - Hiển thị thông báo đơn hàng đã được ghi nhận
   - Thông báo thời gian dự kiến xác nhận thanh toán
   - Cung cấp liên kết đến trang quản lý tài khoản

### 3.2. Luồng gia hạn dịch vụ

1. **Chọn tài khoản gia hạn**:
   - Người dùng truy cập trang `renewal.php`
   - Hiển thị danh sách tài khoản có thể gia hạn
   - Người dùng chọn một hoặc nhiều tài khoản để gia hạn

2. **Chọn gói gia hạn**:
   - Người dùng chọn gói gia hạn cho các tài khoản đã chọn
   - Hệ thống tính toán giá gia hạn dựa trên số lượng tài khoản và gói được chọn

3. **Thanh toán gia hạn**:
   - Luồng thanh toán tương tự như mua mới
   - Lưu trữ thông tin gia hạn trong session với key 'renewal'
   - Bản ghi transaction được đánh dấu là 'renewal'

### 3.3. Luồng kích hoạt gói dùng thử

1. **Chọn gói dùng thử**:
   - Người dùng chọn gói "Dùng thử miễn phí"
   - Điền thông tin vị trí sử dụng

2. **Xử lý đơn hàng dùng thử**:
   - Tạo bản ghi trong `registration` với giá = 0
   - Đặt `$_SESSION['pending_is_trial'] = true`

3. **Kích hoạt trực tiếp**:
   - Bypass trang thanh toán và tải lên minh chứng
   - Gọi trực tiếp `process_trial_activation.php`
   - Cập nhật trạng thái registration thành 'active'
   - Tạo tài khoản RTK mới cho người dùng

## 4. Các lỗi có thể phát sinh và cách sửa

### 4.1. Lỗi tính toán giá

- **Triệu chứng**: Giá hiển thị không đúng, không khớp giữa các trang, hoặc không áp dụng mã giảm giá đúng.
- **Nguyên nhân**:
  - Lỗi trong logic tính toán ở phía server
  - Xung đột giữa tính toán client-side và server-side
  - Không làm tròn số đúng cách
- **Giải pháp**:
  - Kiểm tra lại logic tính toán trong `process_order.php` và `payment.php`
  - Đảm bảo sử dụng cùng một phương thức làm tròn số ở cả client và server
  - Kiểm tra lại logic áp dụng mã giảm giá trong `Voucher.php`
  - Thêm log chi tiết để theo dõi quá trình tính toán

```php
// Ví dụ: Làm tròn số tiền đồng nhất
function formatPrice($price) {
    return round($price, 2);
}
```

### 4.2. Lỗi tải lên minh chứng thanh toán

- **Triệu chứng**: Không thể tải lên ảnh minh chứng, lỗi khi xử lý file.
- **Nguyên nhân**:
  - Quyền truy cập thư mục upload không đúng
  - Giới hạn kích thước file quá nhỏ
  - Định dạng file không được hỗ trợ
- **Giải pháp**:
  - Kiểm tra quyền truy cập thư mục `public/uploads/payment_proofs`
  - Điều chỉnh giới hạn upload trong `php.ini` hoặc `.htaccess`
  - Mở rộng các định dạng file được chấp nhận
  - Thêm xử lý lỗi chi tiết

```php
// Ví dụ: Xử lý lỗi upload file
function validateAndUploadProof($file) {
    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Kích thước file vượt quá giới hạn php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Kích thước file vượt quá giới hạn MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File chỉ được tải lên một phần',
            UPLOAD_ERR_NO_FILE => 'Không có file nào được tải lên',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào đĩa',
            UPLOAD_ERR_EXTENSION => 'Một PHP extension đã dừng việc tải file'
        ];
        throw new Exception($errors[$file['error']] ?? 'Lỗi không xác định');
    }
    
    // Kiểm tra định dạng file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Định dạng file không được hỗ trợ. Chỉ chấp nhận JPEG, PNG, JPG, PDF.');
    }
    
    // Kiểm tra kích thước
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception('Kích thước file quá lớn. Tối đa 5MB.');
    }
    
    // Xử lý tên file
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file['name']);
    $upload_path = 'uploads/payment_proofs/' . $filename;
    
    // Di chuyển file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Không thể lưu file. Vui lòng kiểm tra quyền thư mục.');
    }
    
    return $upload_path;
}
```

### 4.3. Lỗi khi áp dụng voucher

- **Triệu chứng**: Không thể áp dụng mã giảm giá, giảm giá không đúng, hoặc lỗi sau khi áp dụng.
- **Nguyên nhân**:
  - Mã giảm giá đã hết hạn hoặc không tồn tại
  - Điều kiện áp dụng không thỏa mãn
  - Lỗi tính toán giảm giá
- **Giải pháp**:
  - Kiểm tra kỹ logic xác thực mã giảm giá trong `VoucherService`
  - Cải thiện thông báo lỗi chi tiết
  - Thêm log để theo dõi quá trình áp dụng voucher

```php
// Ví dụ: Cải thiện thông báo lỗi khi áp dụng voucher
public function getDetailedVoucherError($code, $total_price) {
    $stmt = $this->conn->prepare('SELECT * FROM voucher WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$voucher) {
        return 'Mã giảm giá không tồn tại';
    }
    
    if ($voucher['status'] !== 'active') {
        return 'Mã giảm giá đã bị vô hiệu hóa';
    }
    
    if ($voucher['end_date'] && strtotime($voucher['end_date']) < time()) {
        return 'Mã giảm giá đã hết hạn vào ' . date('d/m/Y', strtotime($voucher['end_date']));
    }
    
    if ($voucher['limit_usage'] && $voucher['current_usage'] >= $voucher['limit_usage']) {
        return 'Mã giảm giá đã đạt giới hạn sử dụng';
    }
    
    if ($voucher['min_purchase'] && $total_price < $voucher['min_purchase']) {
        return 'Đơn hàng tối thiểu phải từ ' . number_format($voucher['min_purchase'], 0, ',', '.') . 'đ để sử dụng mã này';
    }
    
    return 'Không thể áp dụng mã giảm giá vì lý do không xác định';
}
```

### 4.4. Lỗi session hết hạn

- **Triệu chứng**: Mất thông tin đơn hàng giữa các bước, bị chuyển về trang danh sách gói.
- **Nguyên nhân**:
  - Session timeout quá ngắn
  - Cookie session bị xóa
  - Xung đột session giữa các tab
- **Giải pháp**:
  - Tăng thời gian sống của session trong cấu hình PHP
  - Lưu thông tin quan trọng vào database thay vì chỉ dựa vào session
  - Cải thiện xử lý khi session bị mất

```php
// Ví dụ: Tăng thời gian sống của session
ini_set('session.gc_maxlifetime', 7200); // 2 giờ
session_set_cookie_params(7200);

// Ví dụ: Khôi phục session từ database nếu mất
if (!isset($_SESSION['pending_registration_id']) && isset($_GET['recover_id'])) {
    $reg_id = (int)$_GET['recover_id'];
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Tìm kiếm đơn hàng chưa hoàn tất của user
    $stmt = $conn->prepare('SELECT id, total_price FROM registration WHERE id = ? AND user_id = ? AND status = "pending" AND payment_status = "unpaid" LIMIT 1');
    $stmt->execute([$reg_id, $user_id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reg) {
        // Khôi phục session
        $_SESSION['pending_registration_id'] = $reg['id'];
        $_SESSION['pending_total_price'] = $reg['total_price'];
    }
}
```

### 4.5. Lỗi chuyển khoản và mã QR

- **Triệu chứng**: Mã QR không hoạt động, không thể quét bằng ứng dụng ngân hàng.
- **Nguyên nhân**:
  - Cấu hình VietQR không chính xác
  - Thông tin ngân hàng không đúng
  - Lỗi trong chuỗi payload QR
- **Giải pháp**:
  - Kiểm tra lại cấu hình VietQR trong file `.env`
  - Xác thực lại thông tin ngân hàng
  - Kiểm tra encoding của mô tả chuyển khoản

```php
// Ví dụ: Đảm bảo encoding chính xác cho VietQR
function sanitizeQRDescription($desc) {
    // Chỉ giữ lại chữ cái, số và khoảng trắng, loại bỏ các ký tự đặc biệt
    $desc = preg_replace('/[^\p{L}\p{N}\s]/u', '', $desc);
    // Giới hạn độ dài
    return mb_substr($desc, 0, 50, 'UTF-8');
}
```

## 5. Các dự kiến phát triển trong tương lai

### 5.1. Tích hợp cổng thanh toán trực tuyến

Thêm khả năng thanh toán trực tiếp qua các cổng thanh toán phổ biến như:
- VNPAY
- Momo
- ZaloPay
- PayOS

Việc này sẽ yêu cầu:
- Tạo các controller mới cho từng cổng thanh toán
- Xử lý webhook callback khi thanh toán hoàn tất
- Cập nhật UI để hiển thị các phương thức thanh toán mới
- Bổ sung xử lý lỗi và reconciliation

### 5.2. Hệ thống giảm giá nâng cao

Mở rộng hệ thống voucher hiện tại với các tính năng:
- Mã giảm giá dành riêng cho người dùng cụ thể
- Voucher áp dụng cho gói cụ thể
- Hệ thống khuyến mãi theo thời gian (flash sale)
- Giảm giá tự động dựa trên lịch sử mua hàng

### 5.3. Quản lý đơn hàng và lịch sử mua hàng

Phát triển giao diện quản lý đơn hàng cho người dùng:
- Xem tất cả đơn hàng và trạng thái
- In hóa đơn/biên nhận
- Xuất báo cáo chi tiêu
- Yêu cầu hoàn tiền/hủy đơn hàng

### 5.4. Tự động hóa quy trình xác nhận thanh toán

Cải thiện quy trình xác nhận thanh toán:
- Tích hợp API ngân hàng để tự động kiểm tra giao dịch
- Sử dụng AI để xác thực ảnh minh chứng thanh toán
- Tự động kích hoạt dịch vụ sau khi xác nhận thanh toán

### 5.5. Hệ thống giới thiệu và affiliate

Phát triển chương trình affiliate:
- Tạo liên kết giới thiệu với mã riêng cho mỗi người dùng
- Tính toán hoa hồng dựa trên đơn hàng của người được giới thiệu
- Cung cấp dashboard theo dõi hiệu quả giới thiệu
- Hệ thống thanh toán/rút tiền hoa hồng

### 5.6. Đăng ký định kỳ và tự động gia hạn

Triển khai hệ thống đăng ký định kỳ:
- Lưu thông tin thanh toán để tự động gia hạn
- Gửi thông báo trước khi tự động gia hạn
- Cung cấp tùy chọn hủy tự động gia hạn
- Xử lý các trường hợp thanh toán thất bại
