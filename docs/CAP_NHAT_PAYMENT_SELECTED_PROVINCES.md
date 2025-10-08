# CẬP NHẬT: Hiển thị & Validate tất cả tỉnh từ selected_provinces

## 📅 Ngày: 07/10/2025

## 🎯 Mục đích
- Hiển thị tất cả các tỉnh đã chọn trên trang payment
- Voucher validate với tất cả các tỉnh trong selected_provinces thay vì chỉ location_id

---

## 📝 Các thay đổi

### 1. PaymentService.php
**File:** `private/classes/purchase/PaymentService.php`

#### Thay đổi SQL query:
```php
// TRƯỚC
SELECT id, package_id, location_id, num_account, ...

// SAU
SELECT id, package_id, location_id, selected_provinces, num_account, ...
```

#### Lấy tên tất cả các tỉnh:
```php
// Lấy danh sách tỉnh từ selected_provinces JSON
$selected_provinces_json = $registration_details['selected_provinces'];

if (!empty($selected_provinces_json)) {
    $province_ids = json_decode($selected_provinces_json, true);
    if (is_array($province_ids) && !empty($province_ids)) {
        // Query tất cả tên tỉnh
        $placeholders = implode(',', array_fill(0, count($province_ids), '?'));
        $location_stmt = $this->conn->prepare(
            "SELECT province FROM location WHERE id IN ($placeholders) 
             ORDER BY FIELD(id, $placeholders)"
        );
        $params = array_merge($province_ids, $province_ids);
        $location_stmt->execute($params);
        $provinces = $location_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Join thành string
        $provinces_display = implode(', ', $provinces);
    }
}

// Fallback về location_id nếu selected_provinces rỗng
if (empty($provinces_display)) {
    // Query từ location_id như cũ
}
```

#### Return data bổ sung:
```php
return [
    'success' => true,
    'data' => [
        'province' => $provinces_display, // "Hà Nội, Đà Nẵng, TP.HCM"
        'selected_provinces' => $selected_provinces_json, // "[21,42,50]"
        'location_id' => $registration_details['location_id'], // 21
        // ... các field khác
    ]
];
```

---

### 2. Voucher.php - validateVoucher()
**File:** `private/classes/Voucher.php`

#### Validate location với selected_provinces:
```php
// TRƯỚC: Chỉ so sánh 1 location_id
if ($voucher['location_id'] !== null && $locationId !== null 
    && $voucher['location_id'] != $locationId) {
    return ['status' => false, 'message' => '...'];
}

// SAU: Kiểm tra voucher location_id có trong selected_provinces không
if ($voucher['location_id'] !== null && $locationId !== null) {
    // Parse locationId (có thể là JSON hoặc single ID)
    $selectedProvinceIds = [];
    if (is_string($locationId) && substr($locationId, 0, 1) === '[') {
        // It's JSON array
        $decoded = json_decode($locationId, true);
        if (is_array($decoded)) {
            $selectedProvinceIds = $decoded;
        }
    } else {
        // Single location ID
        $selectedProvinceIds = [$locationId];
    }
    
    // Kiểm tra voucher location_id có trong danh sách không
    $isValidLocation = in_array($voucher['location_id'], $selectedProvinceIds);
    
    if (!$isValidLocation) {
        return ['status' => false, 'message' => 'Mã voucher chỉ áp dụng cho...'];
    }
}
```

**Cách hoạt động:**
- Nếu voucher chỉ định `location_id = 21` (Hà Nội)
- User chọn tỉnh: `[21, 42, 50]` (Hà Nội, Đà Nẵng, TP.HCM)
- Check: `21 IN [21, 42, 50]` → ✅ Valid
- User chọn tỉnh: `[42, 50]` (Đà Nẵng, TP.HCM - không có Hà Nội)
- Check: `21 IN [42, 50]` → ❌ Invalid

---

### 3. apply_voucher.php
**File:** `private/action/purchase/apply_voucher.php`

#### Lấy selected_provinces từ registration:
```php
// TRƯỚC
$stmt = $conn->prepare("SELECT package_id, location_id, num_account FROM registration...");
...
$locationId = $regInfo['location_id'];

// SAU
$stmt = $conn->prepare("SELECT package_id, location_id, selected_provinces, num_account FROM registration...");
...
// Ưu tiên selected_provinces, fallback về location_id
$locationId = !empty($regInfo['selected_provinces']) 
    ? $regInfo['selected_provinces']  // JSON string
    : $regInfo['location_id'];        // Single ID
```

---

### 4. device_voucher_helper.php
**File:** `private/utils/device_voucher_helper.php`

Tương tự apply_voucher.php:
```php
$stmt = $conn->prepare("SELECT package_id, location_id, selected_provinces, num_account...");
...
$locationId = !empty($regInfo['selected_provinces']) 
    ? $regInfo['selected_provinces'] 
    : $regInfo['location_id'];
```

---

## 🎨 Hiển thị trên UI

### Trang Payment (payment.php)

**Trước:**
```php
<div class="summary-item">
    <span>Tỉnh/Thành phố:</span>
    <strong>Hà Nội</strong> <!-- Chỉ 1 tỉnh -->
</div>
```

**Sau:**
```php
<div class="summary-item">
    <span>Tỉnh/Thành phố:</span>
    <strong>Hà Nội, Đà Nẵng, TP. Hồ Chí Minh</strong> <!-- Tất cả tỉnh -->
</div>
```

Dữ liệu `$payment_data['province']` bây giờ chứa chuỗi tất cả tên tỉnh, ngăn cách bởi dấu phẩy.

---

## 📊 Flow hoạt động

### 1. User chọn nhiều tỉnh:
```
User chọn: Hà Nội (21), Đà Nẵng (42), TP.HCM (50)
              ↓
Registration table:
  - location_id = 21
  - selected_provinces = "[21,42,50]"
```

### 2. Hiển thị trên Payment:
```
PaymentService.getPaymentPageDetails()
              ↓
Parse JSON: [21, 42, 50]
              ↓
Query location table: WHERE id IN (21, 42, 50)
              ↓
Get names: ["Hà Nội", "Đà Nẵng", "TP. Hồ Chí Minh"]
              ↓
Join: "Hà Nội, Đà Nẵng, TP. Hồ Chí Minh"
              ↓
Display: <strong>Hà Nội, Đà Nẵng, TP. Hồ Chí Minh</strong>
```

### 3. Apply Voucher:
```
User apply voucher có location_id = 42 (Đà Nẵng only)
              ↓
apply_voucher.php lấy selected_provinces = "[21,42,50]"
              ↓
Pass vào validateVoucher(..., locationId = "[21,42,50]")
              ↓
Voucher.php parse: [21, 42, 50]
              ↓
Check: 42 IN [21, 42, 50]? → YES ✅
              ↓
Voucher VALID!
```

---

## ✅ Ví dụ thực tế

### Case 1: Voucher áp dụng cho Hà Nội
```
Voucher: location_id = 21 (Hà Nội)
User chọn: [21, 42, 50] (Hà Nội, Đà Nẵng, TP.HCM)
Result: ✅ VALID (vì có Hà Nội trong danh sách)

User chọn: [42, 50] (Đà Nẵng, TP.HCM)
Result: ❌ INVALID (không có Hà Nội)
```

### Case 2: Voucher không giới hạn location
```
Voucher: location_id = NULL (áp dụng toàn quốc)
User chọn: Bất kỳ tỉnh nào
Result: ✅ VALID (luôn luôn)
```

### Case 3: Backward compatibility
```
Registration cũ (chưa có selected_provinces):
  - location_id = 21
  - selected_provinces = NULL

PaymentService sẽ fallback:
  - Nếu selected_provinces NULL → Lấy từ location_id
  - Display: "Hà Nội" (như cũ)
```

---

## 🔍 Testing

### Test 1: Hiển thị nhiều tỉnh
1. Tạo đơn hàng mới, chọn 3 tỉnh
2. Vào trang payment
3. ✅ Check: "Tỉnh/Thành phố" hiển thị đủ 3 tỉnh

### Test 2: Voucher valid cho 1 trong nhiều tỉnh
1. Tạo voucher chỉ áp dụng cho Hà Nội
2. Đơn hàng chọn: Hà Nội, Đà Nẵng
3. Apply voucher
4. ✅ Check: Voucher được apply thành công

### Test 3: Voucher invalid
1. Tạo voucher chỉ áp dụng cho Hà Nội
2. Đơn hàng chọn: Đà Nẵng, TP.HCM (không có Hà Nội)
3. Apply voucher
4. ✅ Check: Hiện lỗi "Voucher chỉ áp dụng cho khu vực: Hà Nội"

### Test 4: Backward compatibility
1. Lấy 1 registration cũ (chưa có selected_provinces)
2. Vào trang payment
3. ✅ Check: Vẫn hiển thị tỉnh từ location_id bình thường

---

## 📌 Lưu ý

1. **Không breaking change**: Dữ liệu cũ vẫn hoạt động bình thường
2. **location_id vẫn giữ**: Để backward compatibility
3. **selected_provinces là source of truth**: Cho tất cả logic mới
4. **Voucher validation**: Flexible - chỉ cần 1 tỉnh match là OK

---

## 🔧 Files đã sửa

1. ✅ `private/classes/purchase/PaymentService.php`
2. ✅ `private/classes/Voucher.php`
3. ✅ `private/action/purchase/apply_voucher.php`
4. ✅ `private/utils/device_voucher_helper.php`

---

**Cập nhật bởi:** GitHub Copilot  
**Ngày:** 07/10/2025
