# Hướng Dẫn Debug - Tài Khoản Không Hiển Thị Sau Khi Cập Nhật Ownership

## ❗ Vấn Đề

Sau khi cập nhật quyền sở hữu tài khoản thành công, tài khoản vẫn **không hiển thị** trong danh sách quản lý tài khoản của user mới.

---

## 🔍 Nguyên Nhân

Tài khoản bị **soft-delete** (cột `deleted_at` không NULL trong bảng `survey_account`).

Query lấy danh sách tài khoản có điều kiện:
```sql
WHERE r.user_id = :user_id 
AND sa.deleted_at IS NULL
```

→ Tài khoản có `deleted_at` không NULL sẽ **BỊ LỌC BỎ** khỏi danh sách.

---

## ✅ Giải Pháp Đã Áp Dụng

### 1. Cập nhật method `updateAccountOwnership()`

**File:** `private/classes/RtkAccount.php`

**Thay đổi:**
- Sử dụng **transaction** để đảm bảo tính toàn vẹn dữ liệu
- Update bảng `registration` → set `user_id` mới
- Update bảng `survey_account` → **set `deleted_at = NULL`** để restore tài khoản
- Thêm error logging

```php
public function updateAccountOwnership($registrationId, $userId) {
    try {
        $this->conn->beginTransaction();
        
        // Update registration table with new owner
        $sql = "UPDATE registration 
               SET user_id = :user_id, updated_at = NOW() 
               WHERE id = :registration_id";
        // ... execute ...
        
        // Restore account if it was soft-deleted
        $sql2 = "UPDATE survey_account 
                SET deleted_at = NULL, updated_at = NOW() 
                WHERE registration_id = :registration_id";
        // ... execute ...
        
        $this->conn->commit();
        return true;
    } catch (PDOException $e) {
        $this->conn->rollBack();
        error_log("Error: " . $e->getMessage());
        return false;
    }
}
```

### 2. Cải thiện thông báo response

**File:** `public/handlers/rtk_account_handlers.php`

Thêm thông tin chi tiết về:
- Số tài khoản đã cập nhật (`updated_count`)
- Số tài khoản cần xác nhận (`confirmation_count`)
- Số tài khoản không hợp lệ (`invalid_count`)

### 3. Cải thiện UI feedback

**File:** `public/assets/js/pages/rtk/rtk_accountmanagement.js`

Hiển thị thông báo chi tiết hơn, bao gồm danh sách tài khoản không hợp lệ.

---

## 🧪 Cách Kiểm Tra

### Bước 1: Kiểm tra trạng thái tài khoản

Truy cập:
```
http://localhost:3000/public/test_ownership_debug.php?username=test3ngay
```

Xem:
- ✅ `deleted_at` = NULL → Tài khoản sẽ hiển thị
- ❌ `deleted_at` = không NULL → Tài khoản bị ẩn

### Bước 2: Thử cập nhật ownership

1. Vào trang quản lý tài khoản
2. Click "Cập Nhật Quyền Sở Hữu"
3. Nhập username/password
4. Click "Xác nhận"

### Bước 3: Kiểm tra kết quả

**Trường hợp 1: Tài khoản chưa có owner**
```
✅ "Đã cập nhật quyền sở hữu cho 1 tài khoản"
→ Reload trang → Tài khoản xuất hiện
```

**Trường hợp 2: Tài khoản đã có owner**
```
ℹ️ "Yêu cầu xác nhận đã được gửi cho 1 tài khoản"
→ Chủ cũ phải xác nhận OTP
```

**Trường hợp 3: Username/Password sai**
```
❌ "Có 1 tài khoản với thông tin đăng nhập không đúng"
→ Kiểm tra lại credentials
```

---

## 📊 Debug Logs

Kiểm tra logs tại:
```
private/logs/error.log
```

Sau khi cập nhật ownership thành công, sẽ thấy:
```
Successfully updated ownership for registration_id=XXX to user_id=YYY
```

---

## 🔧 Test Manual qua Database

### Kiểm tra tài khoản bị soft-delete:

```sql
SELECT 
    sa.id,
    sa.username_acc,
    sa.deleted_at,
    r.user_id
FROM survey_account sa
LEFT JOIN registration r ON sa.registration_id = r.id
WHERE sa.username_acc = 'test3ngay';
```

### Restore tài khoản thủ công (nếu cần):

```sql
UPDATE survey_account 
SET deleted_at = NULL, updated_at = NOW()
WHERE username_acc = 'test3ngay';
```

---

## ✨ Tóm Tắt

| Vấn đề | Nguyên nhân | Giải pháp |
|--------|-------------|-----------|
| Tài khoản không hiện sau update | `deleted_at` không NULL | Auto set `deleted_at = NULL` khi update ownership |
| Thiếu thông tin phản hồi | Message không rõ ràng | Thêm `confirmation_count`, `invalid_count` vào response |
| Không biết tài khoản nào lỗi | Alert chung chung | Hiển thị danh sách tài khoản không hợp lệ |

---

## 📝 Files Đã Thay Đổi

1. ✅ `private/classes/RtkAccount.php` - Method `updateAccountOwnership()`
2. ✅ `public/handlers/rtk_account_handlers.php` - Response messages
3. ✅ `public/assets/js/pages/rtk/rtk_accountmanagement.js` - UI feedback
4. ✅ `public/test_ownership_debug.php` - Debug tool (mới)

---

**Ngày cập nhật:** 25-10-2025
