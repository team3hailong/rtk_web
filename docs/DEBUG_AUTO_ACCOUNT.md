# DEBUG CHECKLIST - TẠI SAO KHÔNG TẠO TÀI KHOẢN TỰ ĐỘNG?

## 📋 KIỂM TRA TỪNG BƯỚC

### ✅ Bước 1: Kiểm tra Voucher
```sql
-- Chạy query này để kiểm tra voucher
SELECT id, code, discount_value, discount_type, auto_approve, need_upload_proof, is_active
FROM voucher
WHERE is_active = 1
ORDER BY created_at DESC
LIMIT 10;
```

**Yêu cầu:**
- `need_upload_proof` = **0** (không cần upload proof)
- `auto_approve` = **1** (tự động duyệt)
- `is_active` = **1** (đang active)

**Nếu voucher chưa đúng:**
```sql
-- Cập nhật voucher để test
UPDATE voucher 
SET need_upload_proof = 0, 
    auto_approve = 1
WHERE code = 'MÃ_VOUCHER_CỦA_BẠN' 
  AND is_active = 1;
```

---

### ✅ Bước 2: Kiểm tra Flow Thanh Toán

#### Trường hợp A: Voucher giảm 100% (Tổng = 0đ)
```
1. Vào trang payment.php
2. Ở Step 2, bạn sẽ thấy:
   - "Xác nhận đơn hàng" (không phải "Quét mã để thanh toán")
   - Tổng thanh toán: 0đ
   - Nút: "Hoàn tất đăng ký" (không phải "Đã thanh toán")
3. Ấn nút "Hoàn tất đăng ký"
4. Hệ thống sẽ gọi complete_order_without_proof.php
```

#### Trường hợp B: Voucher giảm một phần (Tổng > 0đ)
```
1. Vào trang payment.php
2. Ở Step 2, bạn sẽ thấy:
   - "Quét mã để thanh toán"
   - Mã QR thanh toán
   - Nút: "Hoàn tất đăng ký" (không phải "Đã thanh toán")
3. Ấn nút "Hoàn tất đăng ký"
4. Hệ thống sẽ gọi complete_order_without_proof.php
```

**LƯU Ý QUAN TRỌNG:**
- Nếu thấy nút **"Đã thanh toán" → Step 3**, nghĩa là voucher có `need_upload_proof = 1` → KHÔNG gọi `complete_order_without_proof.php`
- Nếu thấy nút **"Hoàn tất đăng ký"**, nghĩa là voucher có `need_upload_proof = 0` → Gọi `complete_order_without_proof.php` ✅

---

### ✅ Bước 3: Kiểm tra Log

Sau khi ấn "Hoàn tất đăng ký", kiểm tra log:

```powershell
# Xem 100 dòng cuối của log
Get-Content "c:\laragon\www\02062025_user_web\private\logs\error.log" -Tail 100
```

**Log mong muốn:**
```
[COMPLETE_WITHOUT_PROOF] Starting process for registration_id: XXX, user_id: XXX
[COMPLETE_WITHOUT_PROOF] Voucher ID: XXX
[COMPLETE_WITHOUT_PROOF] Voucher: code=XXX, need_upload_proof=0, auto_approve=1
[COMPLETE_WITHOUT_PROOF] Auto-approve enabled, status=completed
[COMPLETE_WITHOUT_PROOF] Transaction created with ID: XXX, status: completed
[COMPLETE_WITHOUT_PROOF] Starting auto account creation for registration XXX
[COMPLETE_WITHOUT_PROOF] Database transaction committed
[COMPLETE_WITHOUT_PROOF] AutoAccountCreator instantiated
[AUTO_ACCOUNT] Starting createAccountsForRegistration for registration_id: XXX
[AUTO_ACCOUNT] Registration info: {...}
[AUTO_ACCOUNT] Package info: {...}
[AUTO_ACCOUNT] Selected provinces: [1,2,3,4,5]
[AUTO_ACCOUNT] Mount IDs: [...]
[AUTO_ACCOUNT] Province code: XXX
[AUTO_ACCOUNT] Created account: XXX199 for registration_id: XXX
[AUTO_ACCOUNT] Successfully created 2 accounts for registration XXX
```

**Nếu KHÔNG có log `[COMPLETE_WITHOUT_PROOF]`:**
- Voucher có `need_upload_proof = 1` → Cần upload proof
- Hoặc form không submit đúng

---

### ✅ Bước 4: Kiểm tra Database

#### Kiểm tra Registration:
```sql
SELECT id, user_id, package_id, location_id, selected_provinces, 
       num_account, total_price, status, created_at
FROM registration
WHERE id = YOUR_REGISTRATION_ID;
```

**Yêu cầu:**
- `selected_provinces` phải có giá trị JSON: `[1, 2, 3, 4, 5]` (ví dụ)
- `num_account` phải > 0

#### Kiểm tra Transaction:
```sql
SELECT id, registration_id, voucher_id, status, 
       payment_confirmed, payment_confirmed_at, created_at
FROM transaction_history
WHERE registration_id = YOUR_REGISTRATION_ID
ORDER BY created_at DESC
LIMIT 1;
```

**Yêu cầu (sau khi complete):**
- `status` = **'completed'** (không phải 'pending' hay 'approved')
- `payment_confirmed` = **1**
- `payment_confirmed_at` != NULL

#### Kiểm tra Accounts:
```sql
-- Kiểm tra survey_account
SELECT id, registration_id, username_acc, password_acc, 
       start_time, end_time, created_at
FROM survey_account
WHERE registration_id = YOUR_REGISTRATION_ID;

-- Kiểm tra account_groups
SELECT registration_id, survey_account_id
FROM account_groups
WHERE registration_id = YOUR_REGISTRATION_ID;
```

**Yêu cầu:**
- Phải có bản ghi trong cả 2 bảng
- Số lượng = `num_account`

---

### ✅ Bước 5: Kiểm tra Mount Points

```sql
-- Kiểm tra mount_point có dữ liệu không
SELECT mp.id, mp.location_id, l.province, l.province_code
FROM mount_point mp
JOIN location l ON mp.location_id = l.id
WHERE mp.location_id IN (1, 2, 3, 4, 5)
ORDER BY mp.location_id;
```

**Yêu cầu:**
- Phải có ít nhất 1 mount_point cho mỗi location_id

---

### ✅ Bước 6: Test Manually

Nếu vẫn không chạy, test thủ công:

```php
<?php
// Test file: test_auto_account.php
require_once __DIR__ . '/private/config/config.php';
require_once __DIR__ . '/private/classes/Database.php';
require_once __DIR__ . '/private/classes/purchase/AutoAccountCreator.php';

$registration_id = 10381; // Thay bằng registration_id thực tế

$accountCreator = new AutoAccountCreator();
$result = $accountCreator->createAccountsForRegistration($registration_id);

echo "<pre>";
print_r($result);
echo "</pre>";
?>
```

Chạy file này qua browser: `http://localhost/02062025_user_web/test_auto_account.php`

---

## 🔍 CÁC LỖI THƯỜNG GẶP

### Lỗi 1: "No voucher applied. Proof upload is required."
**Nguyên nhân:** Voucher không được áp dụng hoặc `need_upload_proof = 1`

**Giải pháp:**
1. Kiểm tra voucher có `need_upload_proof = 0`
2. Kiểm tra voucher đã được apply trong session

---

### Lỗi 2: "This voucher requires proof upload."
**Nguyên nhân:** Voucher có `need_upload_proof = 1`

**Giải pháp:**
```sql
UPDATE voucher SET need_upload_proof = 0 WHERE code = 'YOUR_CODE';
```

---

### Lỗi 3: "No provinces selected."
**Nguyên nhân:** `registration.selected_provinces` là NULL hoặc empty

**Giải pháp:**
```sql
-- Kiểm tra
SELECT id, selected_provinces FROM registration WHERE id = YOUR_ID;

-- Nếu NULL, cập nhật thủ công
UPDATE registration 
SET selected_provinces = '[1,2,3,4,5]' 
WHERE id = YOUR_ID;
```

---

### Lỗi 4: "Province code not found"
**Nguyên nhân:** Bảng `location` không có `province_code`

**Giải pháp:**
```sql
-- Kiểm tra
SELECT id, province, province_code FROM location WHERE id = 1;

-- Nếu province_code là NULL, cập nhật
UPDATE location SET province_code = 'HN' WHERE id = 1;
UPDATE location SET province_code = 'TNN' WHERE id = 2;
-- ...
```

---

### Lỗi 5: "Mount points not found"
**Nguyên nhân:** Bảng `mount_point` không có dữ liệu cho location_id

**Giải pháp:**
```sql
-- Kiểm tra
SELECT * FROM mount_point WHERE location_id IN (1,2,3,4,5);

-- Nếu không có, cần insert dữ liệu
INSERT INTO mount_point (id, location_id, ip, port, mountpoint) 
VALUES ('mp_1', 1, '192.168.1.1', 2101, '/MOUNT1');
```

---

## 📝 TỔNG KẾT

**Để tạo tài khoản tự động, CẦN:**

1. ✅ Voucher có `need_upload_proof = 0` và `auto_approve = 1`
2. ✅ Registration có `selected_provinces` JSON array
3. ✅ Location có `province_code`
4. ✅ Mount_point có dữ liệu cho các location_id
5. ✅ User ấn nút "Hoàn tất đăng ký" (không phải "Đã thanh toán")
6. ✅ File `complete_order_without_proof.php` được gọi
7. ✅ Transaction được tạo với status = 'completed'
8. ✅ AutoAccountCreator.createAccountsForRegistration() được gọi

**Nếu thiếu bất kỳ bước nào, tài khoản sẽ KHÔNG được tạo tự động!**
