# Báo cáo Sửa lỗi - Chức năng Cập nhật Sở hữu Tài khoản RTK

**Ngày:** 25-10-2025  
**Chức năng:** Cập nhật sở hữu tài khoản (Update Account Ownership)  
**Files đã sửa:** 2 files

---

## 🐛 Các Lỗi Đã Phát Hiện

### 1. **Lỗi json_decode() - Argument #1 must be of type string, array given**
- **File:** `public/handlers/rtk_account_handlers.php` (dòng 63)
- **Nguyên nhân:** 
  - Frontend gửi dữ liệu dưới dạng JSON (`JSON.stringify()`) với header `Content-Type: application/json`
  - PHP handler cố gắng đọc từ `$_POST['accounts']` nhưng khi gửi JSON, dữ liệu không tự động parse vào `$_POST`
  - Code cố gọi `json_decode()` trên một array thay vì string

### 2. **Lỗi Trying to access array offset on false**
- **File:** `public/handlers/rtk_account_handlers.php` (dòng 99)
- **Nguyên nhân:**
  - Khi `getAccountByCredentials()` không tìm thấy account, nó trả về `null` hoặc `false`
  - Code vẫn cố truy cập `$accountDetails['registration_id']` mà không kiểm tra xem `$accountDetails` có hợp lệ không

### 3. **Lỗi SQL Syntax Error**
- **File:** `private/utils/error_handler.php` (dòng 84-86)
- **Nguyên nhân:**
  - SQL query có ký tự backslash `\` ở cuối mỗi dòng
  - Trong PHP, ký tự `\` trong chuỗi thông thường (không phải heredoc/nowdoc) gây lỗi SQL syntax

---

## ✅ Các Sửa Đổi Đã Thực Hiện

### Sửa đổi 1: `public/handlers/rtk_account_handlers.php` (function validateAccountCredentials)

**Thay đổi cách đọc dữ liệu từ request:**

```php
// CŨ - Chỉ đọc từ $_POST
$accountsJson = $_POST['accounts'] ?? '';
$accounts = [];
if (!empty($accountsJson)) {
    $accounts = json_decode($accountsJson, true);
}

// MỚI - Đọc từ php://input trước, sau đó fallback sang $_POST
$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true);

$accounts = [];

// Try to get accounts from JSON body first, then fall back to $_POST
if (isset($requestData['accounts']) && is_array($requestData['accounts'])) {
    $accounts = $requestData['accounts'];
} elseif (isset($_POST['accounts'])) {
    if (is_array($_POST['accounts'])) {
        $accounts = $_POST['accounts'];
    } else {
        $accounts = json_decode($_POST['accounts'], true);
    }
}
```

**Lý do:** 
- Khi frontend gửi `Content-Type: application/json`, PHP không tự động parse vào `$_POST`
- Cần đọc từ `php://input` và decode JSON thủ công
- Vẫn giữ fallback cho trường hợp gửi form-data thông thường

---

### Sửa đổi 2: `public/handlers/rtk_account_handlers.php` (xử lý kết quả validation)

**Thay đổi cách chuẩn bị result item:**

```php
// CŨ - Luôn thêm registration_id (gây lỗi khi account không tìm thấy)
$resultsItem = [
    'registration_id'       => $accountDetails['registration_id'], // ❌ Lỗi nếu $accountDetails = false
    'username'              => $username,
    'valid'                 => $isValid,
    'updated'               => false,
    'requires_confirmation' => false
];

// MỚI - Chỉ thêm registration_id khi account hợp lệ
$resultsItem = [
    'username'              => $username,
    'valid'                 => $isValid,
    'updated'               => false,
    'requires_confirmation' => false
];

// Only add registration_id if account details exist
if ($isValid && isset($accountDetails['registration_id'])) {
    $resultsItem['registration_id'] = $accountDetails['registration_id'];
}
```

**Lý do:**
- Khi credentials không đúng, `$accountDetails` có thể là `false` hoặc `null`
- Truy cập `$accountDetails['registration_id']` khi `$accountDetails = false` gây lỗi
- Giải pháp: Chỉ thêm `registration_id` khi account thực sự tồn tại

---

### Sửa đổi 3: `private/utils/error_handler.php` (function log_user_activity)

**Loại bỏ backslash trong SQL query:**

```php
// CŨ - Có backslash gây lỗi syntax
$sql = "INSERT INTO activity_logs \
        (user_id, action, entity_type, entity_id, old_values, new_values, notify_content, ip_address, user_agent, created_at) \
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

// MỚI - Không có backslash
$sql = "INSERT INTO activity_logs 
        (user_id, action, entity_type, entity_id, old_values, new_values, notify_content, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
```

**Lý do:**
- Trong PHP, backslash `\` trong chuỗi thông thường không tự động nối dòng như Python
- SQL nhận được có chứa ký tự `\` gây lỗi syntax
- Chuỗi nhiều dòng trong PHP không cần backslash, chỉ cần đóng mở ngoặc kép

---

## 🧪 Cách Test

1. **Chạy file test:**
   ```
   http://localhost/02062025_user_web/test_rtk_fix.php
   ```

2. **Test thực tế:**
   - Vào trang quản lý tài khoản RTK
   - Thử cập nhật sở hữu với:
     - Tài khoản hợp lệ (username/password đúng)
     - Tài khoản không hợp lệ (username/password sai)
   - Kiểm tra không còn lỗi trong `error.log`

3. **Kiểm tra log:**
   ```
   private/logs/error.log
   ```
   - Không còn lỗi `json_decode()`
   - Không còn lỗi `array offset on false`
   - Không còn lỗi SQL syntax

---

## 📋 Checklist

- [x] Sửa lỗi JSON parsing (php://input)
- [x] Sửa lỗi array offset (kiểm tra trước khi truy cập)
- [x] Sửa lỗi SQL syntax (loại bỏ backslash)
- [x] Tạo file test để verify
- [x] Tạo documentation

---

## 🔍 Files Đã Thay Đổi

1. **`public/handlers/rtk_account_handlers.php`**
   - Function `validateAccountCredentials()` - 2 sửa đổi

2. **`private/utils/error_handler.php`**
   - Function `log_user_activity()` - 1 sửa đổi

---

## 📝 Ghi Chú

- Các sửa đổi tương thích ngược (backward compatible)
- Vẫn hỗ trợ cả JSON request và form-data request
- Đã thêm kiểm tra an toàn trước khi truy cập array
- Code rõ ràng và dễ maintain hơn

---

**Người thực hiện:** GitHub Copilot  
**Thời gian:** 25-10-2025
