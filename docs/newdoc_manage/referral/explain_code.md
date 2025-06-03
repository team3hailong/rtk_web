# Giải thích cấu trúc code hệ thống giới thiệu (Referral)

## Cấu trúc tổng quan

Hệ thống giới thiệu (Referral) được tổ chức theo mô hình phân tách UI và logic xử lý:

1. **UI (User Interface)**: Nằm trong thư mục `public/pages/referral/dashboard_referal.php`
2. **Xử lý (Backend)**: Nằm trong thư mục `private/action/referral/`
3. **Service Classes**: Nằm trong thư mục `private/classes/Referral.php`
4. **Utility Functions**: Các hàm tiện ích trong `private/utils/`

### Luồng xử lý hệ thống giới thiệu

```
[Giao diện người dùng] -> [Action Handler] -> [Xử lý Backend] -> [Cơ sở dữ liệu]
   (dashboard_referal.php)  (private/action)  (Referral.php)      (Database)
```

## Files quan trọng và chức năng

### 1. Trang giao diện người dùng

- **`public/pages/referral/dashboard_referal.php`**: Hiển thị dashboard quản lý giới thiệu
  - Hiển thị và quản lý mã giới thiệu
  - Danh sách người dùng đã giới thiệu
  - Quản lý hoa hồng và thống kê
  - Yêu cầu rút tiền

- **`public/assets/js/pages/referral/dashboard_referal.js`**: JavaScript xử lý tương tác người dùng
  - Xử lý sao chép mã giới thiệu và liên kết
  - Xử lý gửi form yêu cầu rút tiền
  - Xử lý chuyển tab và hiển thị dữ liệu

### 2. Classes xử lý giới thiệu

- **`private/classes/Referral.php`**
  - Quản lý toàn bộ chức năng của hệ thống giới thiệu
  - Các phương thức chính:
    - `getUserReferralCode()`: Lấy hoặc tạo mã giới thiệu cho người dùng
    - `trackReferral()`: Theo dõi khi có người đăng ký qua mã giới thiệu
    - `calculateCommission()`: Tính toán hoa hồng khi có giao dịch hoàn thành
    - `getReferredUsers()`: Lấy danh sách người dùng đã giới thiệu
    - `getTotalCommissionEarned()`: Tính tổng hoa hồng đã kiếm được
    - `getCommissionTransactions()`: Lấy chi tiết các giao dịch hoa hồng
    - `createWithdrawalRequest()`: Tạo yêu cầu rút tiền
    - `getWithdrawalHistory()`: Lấy lịch sử rút tiền

### 3. Action handlers

- **`private/action/referral/process_withdrawal_request.php`**
  - Xử lý yêu cầu rút tiền hoa hồng
  - Kiểm tra số dư khả dụng
  - Lưu thông tin ngân hàng và số tiền rút

- **`private/action/referral/optimize_referral_system.php`**
  - Tự động cập nhật hoa hồng cho giao dịch đã hoàn thành nhưng chưa ghi nhận
  - Cập nhật trạng thái hoa hồng

- **`private/action/auth/process_register.php`**
  - Có thêm chức năng xử lý mã giới thiệu khi đăng ký
  - Gọi `trackReferral()` để ghi nhận mối quan hệ giới thiệu

### 4. Cơ sở dữ liệu

Hệ thống sử dụng 4 bảng chính:

- **`referral`**: Lưu trữ mã giới thiệu của mỗi người dùng
- **`referred_user`**: Lưu trữ mối quan hệ giữa người giới thiệu và người được giới thiệu
- **`referral_commission`**: Lưu trữ chi tiết hoa hồng từ các giao dịch
- **`withdrawal_request`**: Lưu trữ yêu cầu rút tiền

## Cấu trúc mã (Code Patterns)

### 1. Tạo mã giới thiệu duy nhất

```php
// Phương thức tạo mã giới thiệu duy nhất
private function generateUniqueReferralCode($length = 8) {
    $isUnique = false;
    $referralCode = '';
    
    while (!$isUnique) {
        // Tạo mã ngẫu nhiên
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $referralCode = '';
        for ($i = 0; $i < $length; $i++) {
            $referralCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Kiểm tra tính duy nhất
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM referral WHERE referral_code = :code");
        $stmt->bindParam(':code', $referralCode, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $isUnique = true;
        }
    }
    
    return $referralCode;
}
```

### 2. Theo dõi người được giới thiệu

```php
// Phương thức theo dõi người được giới thiệu
public function trackReferral($referredUserId, $referralCode) {
    try {
        // Lấy ID người giới thiệu từ mã giới thiệu
        $stmt = $this->conn->prepare("SELECT user_id FROM referral WHERE referral_code = :code");
        $stmt->bindParam(':code', $referralCode, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false; // Mã giới thiệu không hợp lệ
        }
        
        $referrerId = $result['user_id'];
        
        // Kiểm tra người dùng không tự giới thiệu chính mình
        if ($referrerId == $referredUserId) {
            return false;
        }
        
        // Ghi nhận mối quan hệ giới thiệu
        $stmt = $this->conn->prepare("INSERT INTO referred_user (referrer_id, referred_user_id) VALUES (:referrer_id, :referred_id)");
        $stmt->bindParam(':referrer_id', $referrerId, PDO::PARAM_INT);
        $stmt->bindParam(':referred_id', $referredUserId, PDO::PARAM_INT);
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        return false;
    }
}
```

### 3. Tính toán hoa hồng

```php
// Phương thức tính toán hoa hồng
public function calculateCommission($transactionId) {
    try {
        $this->conn->beginTransaction();
        
        // Lấy thông tin giao dịch
        $stmt = $this->conn->prepare("SELECT user_id, amount, status FROM transaction_history WHERE id = :id");
        $stmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction || $transaction['status'] !== 'completed') {
            $this->conn->rollBack();
            return false;
        }
        
        $purchaserId = $transaction['user_id'];
        $transactionAmount = $transaction['amount'];
        
        // Kiểm tra người dùng có được giới thiệu không
        $stmt = $this->conn->prepare("SELECT referrer_id FROM referred_user WHERE referred_user_id = :user_id");
        $stmt->bindParam(':user_id', $purchaserId, PDO::PARAM_INT);
        $stmt->execute();
        $referral = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$referral) {
            $this->conn->rollBack();
            return true;
        }
        
        $referrerId = $referral['referrer_id'];
        $commissionAmount = $transactionAmount * $this->commission_rate;
        
        // Ghi nhận hoa hồng với trạng thái 'approved'
        $stmt = $this->conn->prepare("
            INSERT INTO referral_commission 
            (referrer_id, referred_user_id, transaction_id, commission_amount, status) 
            VALUES (:referrer_id, :referred_id, :transaction_id, :commission_amount, 'approved')
        ");
        $stmt->bindParam(':referrer_id', $referrerId, PDO::PARAM_INT);
        $stmt->bindParam(':referred_id', $purchaserId, PDO::PARAM_INT);
        $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
        $stmt->bindParam(':commission_amount', $commissionAmount, PDO::PARAM_STR);
        $stmt->execute();
        
        $this->conn->commit();
        return true;
        
    } catch (PDOException $e) {
        $this->conn->rollBack();
        return false;
    }
}
```

## Quy tắc thiết kế của hệ thống

1. **Separation of Concerns**: Tách biệt UI và xử lý logic
2. **Transaction Safety**: Sử dụng database transactions để đảm bảo tính nhất quán dữ liệu
3. **Secure Code Generation**: Tạo mã giới thiệu an toàn và duy nhất
4. **Proper Error Handling**: Xử lý lỗi và ghi log chi tiết
5. **Automatic Commission Calculation**: Tính hoa hồng tự động khi giao dịch hoàn thành
6. **Safe Money Handling**: Quản lý chặt chẽ các giao dịch tài chính

## Tương tác với các hệ thống khác

1. **Tương tác với hệ thống Authentication**: Khi người dùng đăng ký với mã giới thiệu
2. **Tương tác với hệ thống Transaction**: Khi có giao dịch hoàn thành để tính hoa hồng
3. **Tương tác với hệ thống User Management**: Quản lý thông tin người dùng trong hệ thống giới thiệu
4. **Tương tác với hệ thống Notification**: Gửi thông báo về các hoạt động giới thiệu
