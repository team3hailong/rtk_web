# CƠ CHẾ TẠO TÀI KHOẢN TỰ ĐỘNG

## 📝 TỔNG QUAN

Hệ thống đã được nâng cấp để **TỰ ĐỘNG TẠO TÀI KHOẢN RTK** ngay sau khi đơn hàng được duyệt, không cần gọi đến admin site hay CRON job.

---

## 🔧 CÁC THÀNH PHẦN CHÍNH

### 1. **AutoAccountCreator.php**
**Đường dẫn:** `private/classes/purchase/AutoAccountCreator.php`

**Chức năng:**
- Tạo tài khoản RTK tự động cho registration đã duyệt
- Xử lý logic đặt tên tài khoản theo province_code
- Lấy mount_point IDs từ selected_provinces
- Gọi API RTK để tạo tài khoản
- Lưu thông tin vào database

**Các method quan trọng:**

#### `createAccountsForRegistration($registration_id)`
```php
// Tạo tài khoản cho một registration
$accountCreator = new AutoAccountCreator();
$result = $accountCreator->createAccountsForRegistration($registration_id);

// Kết quả trả về:
[
    'success' => true/false,
    'accounts' => [
        ['username' => 'TNN199', 'password' => 'xxx', 'rtk_user_id' => 'xxx'],
        ['username' => 'TNN200', 'password' => 'xxx', 'rtk_user_id' => 'xxx']
    ],
    'error' => null/string
]
```

#### Logic đặt tên tài khoản:
```php
// Ví dụ: province_code = 'AGG'
// Format: province_code + 3 chữ số (001, 002, 003...)
// 1. Tìm tài khoản mới nhất: AGG198
// 2. Tăng số lên 1: 199
// 3. Format với 3 chữ số: AGG199
// 4. Tài khoản tiếp theo: AGG200, AGG201, ...
// 5. Nếu chưa có tài khoản: AGG001
```

#### Xử lý mountIds:
```php
// Lấy tất cả mount_point.id từ selected_provinces
// Ví dụ: selected_provinces = [21, 30, 42]
// Query: SELECT DISTINCT id FROM mount_point WHERE location_id IN (21, 30, 42)
// Kết quả: ['mp_id_1', 'mp_id_2', 'mp_id_3', ...]
```

---

### 2. **complete_order_without_proof.php**
**Đường dẫn:** `private/action/purchase/complete_order_without_proof.php`

**Thay đổi:**

#### Trước:
```php
if ($auto_approve) {
    $status = 'approved';
    $payment_confirmed = 1;
    $payment_confirmed_at = date('Y-m-d H:i:s');
}

// Chỉ cập nhật registration status
UPDATE registration SET status = 'approved' WHERE id = :registration_id
```

#### Sau:
```php
if ($auto_approve) {
    $status = 'completed'; // ✅ Đổi thành 'completed'
    $payment_confirmed = 1;
    $payment_confirmed_at = date('Y-m-d H:i:s');
}

// Commit transaction trước
$conn->commit();

// ✅ TỰ ĐỘNG TẠO TÀI KHOẢN
$accountCreator = new AutoAccountCreator();
$result = $accountCreator->createAccountsForRegistration($registration_id);

if ($result['success']) {
    // Tài khoản đã được tạo thành công
    // registration.status = 'active' (tự động set trong AutoAccountCreator)
} else {
    // Log lỗi, admin có thể tạo thủ công sau
    error_log("Failed to create accounts: " . $result['error']);
}
```

---

### 3. **update_status.php**
**Đường dẫn:** `private/action/transaction/update_status.php`

**Thay đổi:**

#### Trước:
```php
if ($new_status === 'completed') {
    // Chỉ cập nhật registration status
    UPDATE registration SET status = 'active' WHERE id = :id
}
```

#### Sau:
```php
if ($new_status === 'completed') {
    // Kiểm tra xem đã có tài khoản chưa
    $account_exists = (COUNT(*) FROM account_groups WHERE registration_id = :id) > 0;
    
    if (!$account_exists) {
        // ✅ TỰ ĐỘNG TẠO TÀI KHOẢN
        $accountCreator = new AutoAccountCreator();
        $result = $accountCreator->createAccountsForRegistration($registration_id);
        
        if ($result['success']) {
            // Tài khoản đã được tạo, status = 'active'
        } else {
            // Tạo thủ công status
            UPDATE registration SET status = 'active' WHERE id = :id
        }
    } else {
        // Đã có tài khoản rồi, chỉ cập nhật status
        UPDATE registration SET status = 'active' WHERE id = :id
    }
}
```

---

## 🔄 QUY TRÌNH HOẠT ĐỘNG

### **Luồng A: Voucher Auto-Approve (need_upload_proof = 0, auto_approve = 1)**

```
1. User áp voucher auto-approve
   ↓
2. User ấn nút "Hoàn tất đơn hàng"
   ↓
3. complete_order_without_proof.php được gọi
   ↓
4. Tạo transaction_history với:
   • status = 'completed' ✅
   • payment_confirmed = 1 ✅
   • payment_confirmed_at = NOW() ✅
   ↓
5. Commit database transaction
   ↓
6. AutoAccountCreator.createAccountsForRegistration()
   ├── Lấy thông tin registration (selected_provinces, num_account)
   ├── Lấy mount_point IDs từ selected_provinces (DISTINCT)
   ├── Lấy province_code của location đầu tiên
   ├── Loop num_account lần:
   │   ├── generateNextUsername(province_code) → TNN199, TNN200, ...
   │   ├── generateRandomPassword() → random 12 ký tự
   │   ├── createRtkAccount() → Gọi API RTK
   │   ├── Lưu vào survey_account (id, username, password, etc.)
   │   └── Lưu vào account_groups (registration_id, survey_account_id)
   ├── UPDATE registration SET status = 'active'
   └── Return success + danh sách accounts
   ↓
7. Redirect đến success.php
```

### **Luồng B: Upload Proof → Admin Duyệt Thủ Công**

```
1. User upload minh chứng thanh toán
   ↓
2. upload_payment_proof.php tạo transaction với status = 'pending'
   ↓
3. Admin vào admin site, duyệt giao dịch
   ↓
4. Gọi update_status.php với status = 'completed'
   ↓
5. Kiểm tra xem đã có tài khoản chưa (account_groups)
   ↓
6. Nếu chưa có → AutoAccountCreator.createAccountsForRegistration()
   ↓
7. Tạo tài khoản tự động (giống luồng A)
   ↓
8. Return success
```

---

## 📊 DỮ LIỆU DATABASE

### **transaction_history**
```sql
-- Khi auto_approve = 1:
status = 'completed'              -- ✅ Đổi từ 'approved' thành 'completed'
payment_confirmed = 1             -- ✅
payment_confirmed_at = NOW()      -- ✅
```

### **registration**
```sql
-- Sau khi tạo tài khoản thành công:
status = 'active'
updated_at = NOW()
```

### **survey_account**
```sql
-- Mỗi tài khoản được tạo:
id = rtk_user_id                  -- ID từ RTK API
registration_id = xxx
username_acc = 'TNN199'           -- Tự động tăng
password_acc = 'random_12_chars'  -- Random password
concurrent_user = 1               -- Mặc định 1
enabled = 1
start_time = NOW()
end_time = start_time + duration  -- Tính từ package.duration_text
temp_phone = user.phone           -- Để trigger link với user
```

### **account_groups**
```sql
-- Liên kết registration với survey_account:
registration_id = xxx
survey_account_id = rtk_user_id
```

---

## 🎯 LƯU Ý QUAN TRỌNG

### 1. **mountIds - Lấy từ selected_provinces**
```php
// Input: selected_provinces = [21, 30, 42]
// Query: 
SELECT DISTINCT id 
FROM mount_point 
WHERE location_id IN (21, 30, 42)

// Output: ['mount_id_1', 'mount_id_2', 'mount_id_3', ...]
// Gửi đến RTK API trong accountData.mountIds
```

### 2. **Username Generation - Tự động tăng**
```php
// Format: province_code + 3 chữ số (001, 002, 003...)
// province_code của location đầu tiên: 'AGG'
// Tìm username mới nhất: 'AGG198'
// Tách 3 chữ số cuối: 198
// Tăng lên: 199
// Format với 3 chữ số: '199' -> '199'
// Username mới: 'AGG199'
// Tiếp theo: 'AGG200', 'AGG201', ...
// Nếu chưa có: 'AGG001', 'AGG002', ...
```

### 3. **Transaction Status**
```
'pending'    → Chờ duyệt (upload proof xong)
'completed'  → Đã duyệt, kích hoạt tài khoản ✅
'approved'   → Không dùng nữa (đổi thành 'completed')
```

### 4. **Error Handling**
```php
// Nếu tạo tài khoản thất bại:
// - Log error
// - KHÔNG throw exception
// - Đơn hàng vẫn hoàn tất
// - Admin có thể tạo tài khoản thủ công sau
```

---

## ✅ TEST CASES

### Test Case 1: Voucher Auto-Approve
```
1. Tạo voucher với:
   - need_upload_proof = 0
   - auto_approve = 1
2. User mua gói, áp voucher
3. User ấn "Hoàn tất đơn hàng"
4. Kiểm tra:
   ✓ transaction_history.status = 'completed'
   ✓ transaction_history.payment_confirmed = 1
   ✓ registration.status = 'active'
   ✓ survey_account có bản ghi mới
   ✓ account_groups có liên kết
   ✓ Username theo format province_code + số tăng dần
```

### Test Case 2: Admin Duyệt Thủ Công
```
1. User upload minh chứng
2. Admin duyệt giao dịch (status = 'completed')
3. Kiểm tra:
   ✓ Tài khoản được tạo tự động
   ✓ registration.status = 'active'
   ✓ survey_account và account_groups có dữ liệu
```

### Test Case 3: Multiple Provinces
```
1. User chọn nhiều tỉnh: [21, 30, 42]
2. Tạo đơn hàng với 2 tài khoản
3. Kiểm tra:
   ✓ mountIds chứa tất cả mount_point từ 3 tỉnh (DISTINCT)
   ✓ Username lấy province_code của location đầu tiên (id=21)
   ✓ 2 tài khoản: TNN199, TNN200
```

---

## 🚫 KHÔNG CÒN SỬ DỤNG

### ❌ Đã loại bỏ:
- `process_auto_approve_order.php` - Không cần gọi CRON admin
- CRON job: `cron_auto_approve.php` - Không cần nữa
- cURL request đến admin site - Không cần nữa
- `status = 'approved'` - Đổi thành `'completed'`

---

## 📞 TROUBLESHOOTING

### Lỗi: "Failed to create RTK account"
```
- Kiểm tra RTK_API_URL, RTK_API_ACCESS_KEY, RTK_API_SECRET_KEY trong config
- Kiểm tra API RTK có hoạt động không
- Xem error log: /private/logs/
```

### Lỗi: "Province code not found"
```
- Kiểm tra bảng location có province_code không
- Kiểm tra selected_provinces có dữ liệu không
```

### Lỗi: "Mount points not found"
```
- Kiểm tra bảng mount_point có dữ liệu không
- Kiểm tra location_id có tồn tại không
```

---

## 📝 CHANGELOG

### v2.0 - Auto Account Creator
- ✅ Tạo class `AutoAccountCreator.php`
- ✅ Tích hợp vào `complete_order_without_proof.php`
- ✅ Tích hợp vào `update_status.php`
- ✅ Xử lý mountIds từ selected_provinces (DISTINCT)
- ✅ Tự động đặt tên username theo province_code + số tăng dần
- ✅ transaction_history.status = 'completed' (thay vì 'approved')
- ✅ payment_confirmed = 1, payment_confirmed_at = NOW()
- ✅ Loại bỏ dependency với admin site CRON
