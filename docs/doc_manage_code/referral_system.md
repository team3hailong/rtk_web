# Tài liệu Hệ thống Giới thiệu (Referral)

## 1. Chi tiết chức năng và trang

### 1.1. Trang Quản lý Giới thiệu
- **Tệp tin UI**: `public/pages/referral/dashboard_referal.php`
- **Lớp xử lý**: `private/classes/Referral.php`
- **Chức năng**: Hiển thị và quản lý hệ thống giới thiệu người dùng, bao gồm tạo và chia sẻ mã giới thiệu, theo dõi người dùng đã giới thiệu, quản lý hoa hồng và rút tiền.

### 1.2. Chức năng tạo và chia sẻ mã giới thiệu
- Tự động tạo mã giới thiệu duy nhất cho mỗi người dùng
- Hiển thị và cho phép sao chép mã giới thiệu
- Tạo liên kết giới thiệu để người dùng có thể chia sẻ

### 1.3. Chức năng theo dõi người dùng đã giới thiệu
- Hiển thị danh sách những người đã đăng ký thông qua mã giới thiệu
- Hiển thị thông tin như tên người dùng, email, ngày đăng ký

### 1.4. Chức năng quản lý hoa hồng
- Hiển thị tổng hoa hồng đã kiếm được, số dư khả dụng, số tiền đã rút
- Hiển thị chi tiết giao dịch của người được giới thiệu và hoa hồng tương ứng
- Hiển thị trạng thái hoa hồng (đã duyệt, đang duyệt, đang xử lý)

### 1.5. Chức năng yêu cầu rút tiền
- Cho phép người dùng gửi yêu cầu rút tiền hoa hồng
- Nhập thông tin ngân hàng và số tiền muốn rút
- Hiển thị lịch sử yêu cầu rút tiền và trạng thái

## 2. Điểm quan trọng hình thành chức năng

### 2.1. Mô hình dữ liệu và cơ sở dữ liệu

#### 2.1.1. Bảng referral
Lưu trữ thông tin mã giới thiệu của người dùng.

```sql
CREATE TABLE IF NOT EXISTS `referral` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `referral_code` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_id` (`user_id`),
  UNIQUE KEY `unique_referral_code` (`referral_code`),
  CONSTRAINT `fk_referral_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2.1.2. Bảng referred_user
Lưu trữ thông tin về mối quan hệ giữa người giới thiệu và người được giới thiệu.

```sql
CREATE TABLE IF NOT EXISTS `referred_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_referred_user` (`referred_user_id`),
  CONSTRAINT `fk_referred_user_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_referred_user_referred` FOREIGN KEY (`referred_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2.1.3. Bảng referral_commission
Lưu trữ thông tin về hoa hồng từ các giao dịch của người được giới thiệu.

```sql
CREATE TABLE IF NOT EXISTS `referral_commission` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `transaction_id` INT NOT NULL,
  `commission_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_commission_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commission_referred_user` FOREIGN KEY (`referred_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commission_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2.1.4. Bảng withdrawal_request
Lưu trữ thông tin về các yêu cầu rút tiền từ hoa hồng.

```sql
CREATE TABLE IF NOT EXISTS `withdrawal_request` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_holder` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_withdrawal_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2. Lớp Referral

Lớp `Referral` xử lý tất cả các tác vụ liên quan đến hệ thống giới thiệu:

```php
class Referral {
    private $db;
    private $conn;
    private $commission_rate = 0.05; // 5% commission rate

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    // Tạo hoặc lấy mã giới thiệu của người dùng
    public function getUserReferralCode($userId) {
        // Kiểm tra và trả về mã giới thiệu hoặc tạo mã mới
    }

    // Tạo mã giới thiệu duy nhất
    private function generateUniqueReferralCode($length = 8) {
        // Tạo mã ngẫu nhiên và kiểm tra tính duy nhất
    }

    // Theo dõi khi có người dùng mới đăng ký qua mã giới thiệu
    public function trackReferral($referredUserId, $referralCode) {
        // Ghi nhận mối quan hệ giữa người giới thiệu và người được giới thiệu
    }

    // Tính toán và ghi nhận hoa hồng khi có giao dịch hoàn thành
    public function calculateCommission($transactionId) {
        // Tính toán và lưu trữ hoa hồng dựa trên giao dịch
    }

    // Lấy danh sách người dùng đã giới thiệu
    public function getReferredUsers($userId) {
        // Truy vấn và trả về danh sách người dùng đã giới thiệu
    }

    // Lấy tổng hoa hồng đã kiếm được
    public function getTotalCommissionEarned($userId) {
        // Tính tổng số tiền hoa hồng đã kiếm được
    }

    // Lấy tổng hoa hồng đã thanh toán
    public function getTotalCommissionPaid($userId) {
        // Tính tổng số tiền hoa hồng đã thanh toán
    }

    // Tạo yêu cầu rút tiền
    public function createWithdrawalRequest($userId, $amount, $bankName, $accountNumber, $accountHolder) {
        // Tạo và lưu yêu cầu rút tiền
    }

    // Lấy tổng số tiền đang chờ rút
    public function getTotalPendingWithdrawals($userId) {
        // Tính tổng số tiền đang chờ trong các yêu cầu rút tiền
    }

    // Lấy lịch sử rút tiền
    public function getWithdrawalHistory($userId) {
        // Truy vấn và trả về lịch sử rút tiền
    }

    // Lấy chi tiết giao dịch hoa hồng
    public function getCommissionTransactions($userId) {
        // Truy vấn và trả về chi tiết giao dịch hoa hồng
    }
}
```

### 2.3. Quản lý phiên và xác thực

Trang quản lý giới thiệu yêu cầu người dùng phải đăng nhập:

```php
// Kiểm tra xác thực người dùng
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . $base_path . "/pages/auth/login.php");
    exit();
}
```

### 2.4. Tạo token CSRF

Đảm bảo an toàn cho các form gửi đi:

```php
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
```

### 2.5. Xử lý JavaScript phía client

File JavaScript `dashboard_referal.js` xử lý các tương tác phía client:

```javascript
// Khởi tạo hệ thống giới thiệu
function initializeReferralSystem(config) {
    // Xử lý sao chép mã giới thiệu và liên kết
    // Xử lý gửi form yêu cầu rút tiền
    // Xử lý chuyển tab
}

// Sao chép mã giới thiệu
function copyReferralCode() {
    // Sao chép mã giới thiệu vào clipboard và hiển thị thông báo
}

// Sao chép liên kết giới thiệu
function copyReferralLink() {
    // Sao chép liên kết giới thiệu vào clipboard và hiển thị thông báo
}
```

## 3. Các luồng xử lý của chức năng

### 3.1. Luồng tạo và chia sẻ mã giới thiệu

1. **Truy cập trang quản lý giới thiệu**:
   - Người dùng truy cập trang `dashboard_referal.php`
   - Hệ thống kiểm tra xác thực người dùng (đã đăng nhập hay chưa)

2. **Tạo mã giới thiệu**:
   - Hệ thống gọi `Referral.getUserReferralCode()` để lấy mã giới thiệu hiện có hoặc tạo mã mới
   - Nếu người dùng chưa có mã, hệ thống sẽ tạo mã giới thiệu duy nhất
   - Mã giới thiệu được lưu vào bảng `referral` và hiển thị cho người dùng

3. **Chia sẻ mã giới thiệu**:
   - Người dùng có thể sao chép mã giới thiệu hoặc liên kết giới thiệu
   - Liên kết giới thiệu được tạo theo định dạng: `[domain]/public/pages/auth/register.php?ref=[referral_code]`

### 3.2. Luồng theo dõi người dùng đã giới thiệu

1. **Người được giới thiệu đăng ký**:
   - Người được giới thiệu truy cập liên kết giới thiệu hoặc nhập mã giới thiệu khi đăng ký
   - Hệ thống xử lý đăng ký người dùng mới
   - Sau khi đăng ký thành công, hệ thống gọi `Referral.trackReferral()` để ghi nhận mối quan hệ giới thiệu

2. **Người giới thiệu xem danh sách**:
   - Người giới thiệu truy cập tab "Người đã giới thiệu" trên trang `dashboard_referal.php`
   - Hệ thống gọi `Referral.getReferredUsers()` để lấy danh sách người dùng đã giới thiệu
   - Hiển thị danh sách với thông tin như tên người dùng, email, ngày đăng ký

### 3.3. Luồng quản lý hoa hồng

1. **Người được giới thiệu hoàn thành giao dịch**:
   - Người được giới thiệu thanh toán thành công một giao dịch
   - Hệ thống gọi `Referral.calculateCommission()` để tính toán hoa hồng
   - Hoa hồng được tính bằng 5% giá trị giao dịch và lưu vào bảng `referral_commission`

2. **Người giới thiệu xem hoa hồng**:
   - Người giới thiệu truy cập tab "Hoa hồng nhận được" trên trang `dashboard_referal.php`
   - Hệ thống gọi `Referral.getTotalCommissionEarned()`, `Referral.getTotalCommissionPaid()` và `Referral.getTotalPendingWithdrawals()` để tính toán số liệu
   - Hệ thống gọi `Referral.getCommissionTransactions()` để lấy chi tiết giao dịch
   - Hiển thị tổng hoa hồng đã kiếm được, số dư khả dụng, số tiền đã rút và chi tiết giao dịch

### 3.4. Luồng yêu cầu rút tiền

1. **Gửi yêu cầu rút tiền**:
   - Người dùng truy cập tab "Yêu cầu rút tiền" trên trang `dashboard_referal.php`
   - Nhập số tiền muốn rút và thông tin ngân hàng
   - JavaScript kiểm tra dữ liệu đầu vào (số tiền tối thiểu, số dư khả dụng)
   - Gửi yêu cầu đến máy chủ qua AJAX

2. **Xử lý yêu cầu rút tiền**:
   - Máy chủ nhận yêu cầu và gọi `Referral.createWithdrawalRequest()` để tạo yêu cầu rút tiền
   - Hệ thống kiểm tra số dư khả dụng (tổng hoa hồng - đã rút - đang chờ rút)
   - Nếu đủ điều kiện, yêu cầu được lưu vào bảng `withdrawal_request` với trạng thái `pending`
   - Ghi log hoạt động vào bảng `activity_logs`
   - Trả về kết quả cho client

3. **Xem lịch sử rút tiền**:
   - Hệ thống gọi `Referral.getWithdrawalHistory()` để lấy lịch sử yêu cầu rút tiền
   - Hiển thị lịch sử với thông tin như ngày yêu cầu, số tiền, trạng thái

## 4. Các lỗi có thể phát sinh và cách sửa

### 4.1. Lỗi tạo mã giới thiệu

- **Triệu chứng**: Không thể tạo mã giới thiệu hoặc hiển thị thông báo lỗi.
- **Nguyên nhân**:
  - Lỗi trong truy vấn SQL
  - Vấn đề về quyền truy cập cơ sở dữ liệu
  - Mã ngẫu nhiên không duy nhất
- **Giải pháp**:
  - Kiểm tra lại logic tạo mã giới thiệu
  - Đảm bảo bảng `referral` đã được tạo đúng cách
  - Tăng độ dài mã giới thiệu nếu có quá nhiều xung đột

```php
// Ví dụ: Cải thiện phương thức tạo mã giới thiệu duy nhất
private function generateUniqueReferralCode($length = 10) {
    $maxAttempts = 10;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $referralCode = '';
        for ($i = 0; $i < $length; $i++) {
            $referralCode .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM referral WHERE referral_code = :code");
        $stmt->bindParam(':code', $referralCode, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            return $referralCode;
        }
        
        $attempts++;
    }
    
    // Nếu không thể tạo mã duy nhất sau nhiều lần thử, thêm timestamp
    return $referralCode . time();
}
```

### 4.2. Lỗi theo dõi người được giới thiệu

- **Triệu chứng**: Không ghi nhận mối quan hệ giới thiệu khi người mới đăng ký.
- **Nguyên nhân**:
  - Mã giới thiệu không hợp lệ
  - Lỗi trong quá trình đăng ký
  - Người dùng tự giới thiệu mình
- **Giải pháp**:
  - Kiểm tra lại quy trình xác thực mã giới thiệu
  - Thêm ghi log chi tiết
  - Đảm bảo mã giới thiệu được truyền đúng cách trong quá trình đăng ký

```php
// Ví dụ: Cải thiện phương thức theo dõi giới thiệu
public function trackReferral($referredUserId, $referralCode) {
    try {
        // Ghi log chi tiết hơn
        error_log("Tracking referral: User ID $referredUserId with code '$referralCode'");
        
        // Xác thực mã giới thiệu
        $stmt = $this->conn->prepare("SELECT user_id FROM referral WHERE referral_code = :code");
        $stmt->bindParam(':code', $referralCode, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            error_log("Referral tracking failed: Invalid referral code '$referralCode'");
            return false;
        }
        
        $referrerId = $result['user_id'];
        
        // Kiểm tra người dùng không tự giới thiệu chính mình
        if ($referrerId == $referredUserId) {
            error_log("Referral tracking failed: User $referredUserId tried to refer themselves");
            return false;
        }
        
        // Ghi log mối quan hệ giới thiệu
        // Phần còn lại của phương thức...
    } catch (PDOException $e) {
        error_log("Error tracking referral: " . $e->getMessage());
        return false;
    }
}
```

### 4.3. Lỗi tính toán hoa hồng

- **Triệu chứng**: Hoa hồng không được ghi nhận hoặc tính toán sai.
- **Nguyên nhân**:
  - Lỗi truy vấn giao dịch
  - Vấn đề về tính toán số tiền hoa hồng
  - Giao dịch không hợp lệ hoặc chưa hoàn thành
- **Giải pháp**:
  - Kiểm tra lại logic tính toán hoa hồng
  - Xác nhận mối quan hệ giữa người giới thiệu và người được giới thiệu
  - Thêm các kiểm tra trạng thái giao dịch

```php
// Ví dụ: Cải thiện phương thức tính toán hoa hồng
public function calculateCommission($transactionId) {
    try {
        $this->conn->beginTransaction();
        
        // Lấy thông tin giao dịch với kiểm tra kỹ lưỡng
        $stmt = $this->conn->prepare("
            SELECT th.id, th.user_id, th.amount, th.status, th.payment_confirmed 
            FROM transaction_history th
            WHERE th.id = :transaction_id
        ");
        $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            $this->conn->rollBack();
            error_log("Commission calculation failed: Transaction ID $transactionId not found");
            return false;
        }
        
        // Kiểm tra trạng thái giao dịch
        if (strtolower($transaction['status']) !== 'completed' || 
            !isset($transaction['payment_confirmed']) || 
            $transaction['payment_confirmed'] != 1) {
            $this->conn->rollBack();
            error_log("Commission calculation skipped: Transaction status is not valid");
            return false;
        }
        
        // Phần còn lại của phương thức...
    } catch (PDOException $e) {
        $this->conn->rollBack();
        error_log("Error calculating commission: " . $e->getMessage());
        return false;
    }
}
```

### 4.4. Lỗi yêu cầu rút tiền

- **Triệu chứng**: Không thể tạo yêu cầu rút tiền hoặc số dư không chính xác.
- **Nguyên nhân**:
  - Số dư không đủ
  - Thông tin ngân hàng không hợp lệ
  - Lỗi truy vấn cơ sở dữ liệu
- **Giải pháp**:
  - Kiểm tra lại logic tính toán số dư khả dụng
  - Xác thực đầu vào thông tin ngân hàng
  - Kiểm tra quyền truy cập và tính hợp lệ của người dùng

```php
// Ví dụ: Cải thiện phương thức tạo yêu cầu rút tiền
public function createWithdrawalRequest($userId, $amount, $bankName, $accountNumber, $accountHolder) {
    try {
        // Vệ sinh và xác thực dữ liệu đầu vào
        $amount = floatval($amount);
        $bankName = trim($bankName);
        $accountNumber = trim($accountNumber);
        $accountHolder = trim($accountHolder);
        
        if ($amount <= 0 || empty($bankName) || empty($accountNumber) || empty($accountHolder)) {
            return [
                'success' => false,
                'message' => 'Thông tin không hợp lệ. Vui lòng kiểm tra lại.'
            ];
        }
        
        // Kiểm tra số dư chi tiết hơn
        $totalCommission = $this->getTotalCommissionEarned($userId);
        $totalPaid = $this->getTotalCommissionPaid($userId);
        $pendingWithdrawals = $this->getTotalPendingWithdrawals($userId);
        
        $availableBalance = $totalCommission - $totalPaid - $pendingWithdrawals;
        
        // Thêm log debug
        error_log("Withdrawal request check: User ID: $userId, Amount: $amount, Available Balance: $availableBalance");
        
        if ($amount > $availableBalance) {
            return [
                'success' => false,
                'message' => 'Số dư không đủ để thực hiện yêu cầu rút tiền này.'
            ];
        }
        
        if ($amount < 100000) {
            return [
                'success' => false,
                'message' => 'Số tiền rút tối thiểu là 100.000 VNĐ.'
            ];
        }
        
        // Phần còn lại của phương thức...
    } catch (PDOException $e) {
        error_log("Error creating withdrawal request: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Đã xảy ra lỗi khi gửi yêu cầu rút tiền.'
        ];
    }
}
```

## 5. Các dự kiến phát triển trong tương lai

### 5.1. Chương trình giới thiệu nhiều cấp

- **Mô tả**: Mở rộng hệ thống giới thiệu để hỗ trợ nhiều cấp (multi-level referral), cho phép người dùng kiếm hoa hồng từ không chỉ người họ giới thiệu trực tiếp mà còn từ những người được giới thiệu tiếp.
- **Các thay đổi cần thiết**:
  - Mở rộng bảng `referred_user` để theo dõi cấp độ giới thiệu
  - Thêm logic tính toán hoa hồng cho nhiều cấp với tỷ lệ khác nhau
  - Cập nhật giao diện để hiển thị mạng lưới giới thiệu
  - Thêm báo cáo hiệu suất mạng lưới

### 5.2. Ưu đãi và phần thưởng đặc biệt

- **Mô tả**: Thêm các chương trình ưu đãi và phần thưởng đặc biệt cho người giới thiệu dựa trên số lượng người được giới thiệu hoặc doanh số.
- **Các thay đổi cần thiết**:
  - Thêm bảng mới để theo dõi các mốc thưởng và tiến độ
  - Thêm chức năng tự động phát hiện và cấp phần thưởng
  - Cập nhật giao diện để hiển thị tiến độ và phần thưởng có sẵn
  - Thêm thông báo khi người dùng đạt được mốc thưởng

### 5.3. Quản lý tài khoản hoa hồng và thanh toán tự động

- **Mô tả**: Cải thiện hệ thống quản lý tài khoản hoa hồng và thêm chức năng thanh toán tự động.
- **Các thay đổi cần thiết**:
  - Thêm tích hợp với các cổng thanh toán phổ biến (VNPay, MoMo)
  - Thêm lịch trình tự động xử lý thanh toán hoa hồng
  - Cung cấp nhiều phương thức rút tiền (ví điện tử, thẻ ngân hàng)
  - Cải thiện báo cáo tài chính và lịch sử giao dịch

### 5.4. Công cụ tiếp thị và theo dõi

- **Mô tả**: Thêm công cụ tiếp thị và theo dõi hiệu quả cho người giới thiệu.
- **Các thay đổi cần thiết**:
  - Thêm các liên kết giới thiệu với tham số theo dõi
  - Cung cấp thống kê về lượt truy cập, tỷ lệ chuyển đổi
  - Tạo các mẫu quảng cáo và nội dung chia sẻ sẵn có
  - Thêm tích hợp với mạng xã hội để chia sẻ dễ dàng

### 5.5. Tùy chỉnh tỷ lệ hoa hồng

- **Mô tả**: Cho phép quản trị viên tùy chỉnh tỷ lệ hoa hồng dựa trên loại gói dịch vụ, thời gian hoặc người dùng cụ thể.
- **Các thay đổi cần thiết**:
  - Thêm bảng cấu hình tỷ lệ hoa hồng
  - Cập nhật logic tính toán hoa hồng để sử dụng tỷ lệ động
  - Thêm giao diện quản trị để cấu hình tỷ lệ
  - Thêm thông báo khi tỷ lệ hoa hồng thay đổi

### 5.6. Hệ thống kiểm duyệt và chống gian lận

- **Mô tả**: Cải thiện hệ thống kiểm duyệt và thêm các biện pháp chống gian lận.
- **Các thay đổi cần thiết**:
  - Thêm các thuật toán phát hiện hoạt động đáng ngờ
  - Tạo quy trình xác minh tài khoản và giao dịch lớn
  - Giới hạn số lượng tài khoản có thể được giới thiệu trong một khoảng thời gian
  - Thêm chức năng báo cáo và xử lý hoạt động gian lận
