# Hướng dẫn xử lý vấn đề hệ thống giới thiệu (Referral)

Tài liệu này cung cấp hướng dẫn xử lý các vấn đề thường gặp trong hệ thống giới thiệu, các nguyên nhân và cách giải quyết.

## 1. Vấn đề với mã giới thiệu

### 1.1. Mã giới thiệu không được tạo

**Triệu chứng:** Người dùng không thấy mã giới thiệu trên dashboard.

**Nguyên nhân có thể:**
- Lỗi trong quá trình tạo mã giới thiệu
- Database không lưu được mã

**Cách giải quyết:**
```php
// Kiểm tra bảng referral
$stmt = $conn->prepare("SELECT referral_code FROM referral WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không có, tạo mã mới
if (!$result) {
    $referralService = new Referral($db);
    $referralService->getUserReferralCode($user_id);
}
```

### 1.2. Mã giới thiệu bị trùng lặp

**Triệu chứng:** Log hiển thị lỗi trùng lặp trong bảng referral.

**Nguyên nhân có thể:**
- Hàm `generateUniqueReferralCode()` không hoạt động đúng
- Race condition khi có nhiều request cùng lúc

**Cách giải quyết:**
- Tăng độ dài mã giới thiệu từ 8 lên 10 ký tự
- Thêm index unique cho cột `referral_code` trong bảng referral
- Thêm try-catch để xử lý lỗi trùng lặp:
```php
try {
    $stmt->execute();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $referralCode = $this->generateUniqueReferralCode(10); // Tạo lại mã với độ dài lớn hơn
        $stmt->bindParam(':referral_code', $referralCode, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        throw $e;
    }
}
```

## 2. Vấn đề theo dõi người được giới thiệu

### 2.1. Mối quan hệ giới thiệu không được ghi nhận

**Triệu chứng:** Người dùng mới đăng ký với mã giới thiệu nhưng không xuất hiện trong danh sách người được giới thiệu.

**Nguyên nhân có thể:**
- Mã giới thiệu không hợp lệ
- Lỗi trong phương thức `trackReferral()`
- Bước xử lý giới thiệu bị bỏ qua trong quá trình đăng ký

**Cách giải quyết:**
- Kiểm tra log lỗi và dữ liệu đầu vào:
```php
error_log("Referral code: $referralCode, User ID: $referredUserId");
```
- Thêm logging chi tiết trong quá trình xử lý giới thiệu
- Kiểm tra trực tiếp trong database:
```sql
SELECT * FROM referral WHERE referral_code = 'CODE_IN_QUESTION';
SELECT * FROM referred_user WHERE referred_user_id = USER_ID;
```

### 2.2. Lỗi người dùng tự giới thiệu

**Triệu chứng:** Log hiển thị lỗi "User tried to refer themselves".

**Nguyên nhân có thể:**
- Người dùng cố gắng sử dụng mã giới thiệu của chính mình
- Race condition trong quá trình tạo tài khoản và xử lý giới thiệu

**Cách giải quyết:**
- Kiểm tra trường hợp này trong cả frontend và backend:
```php
// Trong form đăng ký có thể kiểm tra
if (isLoggedIn() && $referralCode === getCurrentUserReferralCode()) {
    showError("Bạn không thể sử dụng mã giới thiệu của chính mình");
}

// Trong backend đã có kiểm tra
if ($referrerId == $referredUserId) {
    return false;
}
```

## 3. Vấn đề về hoa hồng

### 3.1. Hoa hồng không được tính

**Triệu chứng:** Giao dịch đã hoàn thành nhưng không thấy hoa hồng được ghi nhận.

**Nguyên nhân có thể:**
- Người mua không phải người được giới thiệu
- Lỗi trong phương thức `calculateCommission()`
- Giao dịch chưa được đánh dấu là hoàn thành

**Cách giải quyết:**
- Chạy script tối ưu để kiểm tra và cập nhật:
```php
require_once 'private/action/referral/optimize_referral_system.php';
```
- Kiểm tra trạng thái giao dịch:
```sql
SELECT * FROM transaction_history WHERE id = TRANSACTION_ID;
```
- Kiểm tra mối quan hệ giới thiệu:
```sql
SELECT * FROM referred_user WHERE referred_user_id = (
    SELECT user_id FROM transaction_history WHERE id = TRANSACTION_ID
);
```

### 3.2. Hoa hồng bị tính trùng

**Triệu chứng:** Log hiển thị lỗi hoặc thông báo về việc hoa hồng đã được ghi nhận trước đó.

**Nguyên nhân có thể:**
- Race condition khi có nhiều request cùng lúc
- Lỗi xử lý transaction trong database

**Cách giải quyết:**
- Thêm kiểm tra trước khi tính hoa hồng (đã được triển khai):
```php
$stmt = $this->conn->prepare("
    SELECT COUNT(*) FROM referral_commission 
    WHERE transaction_id = :transaction_id
");
$stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    $this->conn->rollBack();
    return true; // Already recorded, consider it a success
}
```
- Thêm index unique cho cột `transaction_id` trong bảng `referral_commission`

## 4. Vấn đề về rút tiền

### 4.1. Số dư không chính xác

**Triệu chứng:** Người dùng báo cáo số dư khả dụng không chính xác.

**Nguyên nhân có thể:**
- Tính toán sai trong phương thức tính tổng hoa hồng
- Yêu cầu rút tiền bị trùng lặp
- Cập nhật trạng thái không đồng bộ

**Cách giải quyết:**
- Tính lại số dư từ database:
```php
// Tổng hoa hồng đã kiếm được
$totalEarned = $referralService->getTotalCommissionEarned($user_id);

// Tổng hoa hồng đã rút
$totalPaid = $referralService->getTotalCommissionPaid($user_id);

// Tổng đang chờ rút
$totalPending = $referralService->getTotalPendingWithdrawals($user_id);

// Số dư khả dụng
$availableBalance = $totalEarned - $totalPaid - $totalPending;
```
- Kiểm tra trạng thái các yêu cầu rút tiền:
```sql
SELECT * FROM withdrawal_request WHERE user_id = USER_ID ORDER BY created_at DESC;
```

### 4.2. Yêu cầu rút tiền bị treo

**Triệu chứng:** Yêu cầu rút tiền ở trạng thái "đang chờ" quá lâu.

**Nguyên nhân có thể:**
- Quá trình xử lý thủ công bị chậm
- Lỗi cập nhật trạng thái

**Cách giải quyết:**
- Kiểm tra trạng thái yêu cầu:
```sql
SELECT * FROM withdrawal_request WHERE id = REQUEST_ID;
```
- Cập nhật thủ công nếu cần:
```sql
UPDATE withdrawal_request SET status = 'completed', updated_at = NOW() 
WHERE id = REQUEST_ID;
```

## 5. Vấn đề về tối ưu và hiệu suất

### 5.1. Hiệu suất dashboard giảm

**Triệu chứng:** Trang dashboard giới thiệu tải chậm khi có nhiều dữ liệu.

**Nguyên nhân có thể:**
- Truy vấn không hiệu quả
- Thiếu index database
- Hiển thị quá nhiều dữ liệu cùng lúc

**Cách giải quyết:**
- Thêm index cho các cột thường truy vấn:
```sql
CREATE INDEX idx_referred_user_referrer ON referred_user (referrer_id);
CREATE INDEX idx_referral_commission_referrer ON referral_commission (referrer_id);
CREATE INDEX idx_withdrawal_request_user ON withdrawal_request (user_id);
```
- Phân trang cho các danh sách dài:
```php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $this->conn->prepare("
    SELECT * FROM referral_commission
    WHERE referrer_id = :user_id
    ORDER BY created_at DESC
    LIMIT :offset, :per_page
");
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
```

### 5.2. Dữ liệu giới thiệu không đồng bộ

**Triệu chứng:** Số liệu thống kê và thứ hạng hiển thị không đồng bộ hoặc không chính xác.

**Nguyên nhân có thể:**
- Cron job cập nhật thứ hạng không chạy
- Lỗi trong quá trình tính toán số liệu thống kê

**Cách giải quyết:**
- Chạy lại cron job cập nhật thống kê:
```php
require_once 'private/cron/update_user_rankings.php';
```
- Kiểm tra và cập nhật dữ liệu bảng `user_ranking`:
```sql
/* Cập nhật tổng số người giới thiệu */
UPDATE user_ranking ur
SET referral_count = (
    SELECT COUNT(*) FROM referred_user ru WHERE ru.referrer_id = ur.user_id
)
WHERE user_id = USER_ID;

/* Cập nhật tổng hoa hồng */
UPDATE user_ranking ur
SET total_commission = (
    SELECT COALESCE(SUM(commission_amount), 0)
    FROM referral_commission rc
    WHERE rc.referrer_id = ur.user_id AND rc.status IN ('approved', 'paid')
)
WHERE user_id = USER_ID;
```

## 6. Vấn đề bảo mật

### 6.1. Mã giới thiệu bị lạm dụng

**Triệu chứng:** Một mã giới thiệu có nhiều đăng ký bất thường trong thời gian ngắn.

**Nguyên nhân có thể:**
- Bot tạo tài khoản tự động
- Lạm dụng chương trình giới thiệu

**Cách giải quyết:**
- Thêm giới hạn số lần giới thiệu trong một khoảng thời gian:
```php
// Kiểm tra số lượng giới thiệu trong 24h qua
$stmt = $this->conn->prepare("
    SELECT COUNT(*) FROM referred_user 
    WHERE referrer_id = :referrer_id AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
");
$stmt->bindParam(':referrer_id', $referrerId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->fetchColumn() > MAX_DAILY_REFERRALS) {
    error_log("Warning: User $referrerId has exceeded maximum daily referrals");
    // Xử lý cảnh báo hoặc giới hạn
}
```
- Thêm xác thực CAPTCHA vào quy trình đăng ký
- Thêm cảnh báo khi phát hiện hành vi bất thường

### 6.2. Thông tin rút tiền bị thay đổi

**Triệu chứng:** Thông tin rút tiền khác với thông tin đã đăng ký.

**Nguyên nhân có thể:**
- Lỗ hổng bảo mật CSRF
- Lỗi xác thực dữ liệu đầu vào

**Cách giải quyết:**
- Đảm bảo token CSRF được sử dụng cho mọi form:
```php
// Trong form HTML
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Trong xử lý form
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```
- Ghi log mọi thay đổi thông tin rút tiền
- So sánh thông tin mới và cũ trước khi cập nhật
