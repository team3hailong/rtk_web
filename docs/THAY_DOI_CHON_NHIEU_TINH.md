# Tài liệu: Chức năng chọn nhiều tỉnh khi đăng ký RTK

## Tổng quan
Chức năng cho phép người dùng chọn nhiều tỉnh/thành phố khi đăng ký gói dịch vụ RTK. Tỉnh đầu tiên được chọn sẽ được lưu vào `location_id` (tỉnh chính), và tất cả các tỉnh được chọn sẽ được lưu dưới dạng JSON array vào cột `selected_provinces`.

## Ngày thực hiện
07/10/2025

## Các file đã thay đổi

### 1. Database Migration
**File:** `db/migrations/migration_add_selected_provinces.sql`

```sql
-- Thêm cột selected_provinces vào bảng registration
ALTER TABLE `registration`
ADD COLUMN `selected_provinces` JSON DEFAULT NULL 
COMMENT 'Danh sách location_id các tỉnh được chọn, VD: [21, 30, 40, 42]'
AFTER `location_id`;

-- Cập nhật dữ liệu cũ
UPDATE `registration`
SET `selected_provinces` = JSON_ARRAY(`location_id`)
WHERE `location_id` IS NOT NULL AND `selected_provinces` IS NULL;
```

### 2. Frontend - Trang details.php
**File:** `public/pages/purchase/details.php`

**Thay đổi:**
- Đổi `<select>` từ single select sang multiple select
- Thêm attribute `multiple` và `name="location_id[]"` để nhận mảng
- Thêm hướng dẫn người dùng cách chọn nhiều tỉnh
- Tăng chiều cao select box lên 200px

**Code:**
```php
<select id="location_id" name="location_id[]" class="form-control" multiple required style="height: 200px;">
    <?php foreach ($provinces as $province): ?>
        <option value="<?php echo htmlspecialchars($province['id']); ?>">
            <?php echo htmlspecialchars($province['province']); ?>
        </option>
    <?php endforeach; ?>
</select>
```

### 3. JavaScript Validation
**File:** `public/assets/js/pages/purchase/details.js`

**Thay đổi:**
- Cập nhật validation để kiểm tra xem có chọn ít nhất 1 tỉnh không
- Sử dụng `selectedOptions` để lấy danh sách các tỉnh đã chọn

**Code:**
```javascript
const selectedOptions = Array.from(locationSelect.selectedOptions);
if (selectedOptions.length === 0) {
    alert('Vui lòng chọn ít nhất 1 Tỉnh/Thành phố sử dụng.');
    event.preventDefault();
    locationSelect.focus();
    return;
}
```

### 4. Backend - Xử lý đơn hàng
**File:** `private/action/purchase/process_order.php`

**Thay đổi:**
- Nhận mảng `location_id[]` từ POST request
- Lọc và validate mảng location IDs
- Tỉnh đầu tiên trong mảng = `location_id` (tỉnh chính)
- Tất cả các tỉnh = `selected_provinces` (JSON array)
- Cập nhật câu SQL INSERT để thêm cột `selected_provinces`

**Code:**
```php
// Nhận mảng location_id
$location_ids = isset($_POST['location_id']) && is_array($_POST['location_id']) 
    ? $_POST['location_id'] 
    : [];

// Lọc và chuyển sang số nguyên
$location_ids = array_filter(
    array_map('intval', $location_ids), 
    function($id) { return $id > 0; }
);

// Tỉnh đầu tiên là tỉnh chính
$location_id = !empty($location_ids) ? $location_ids[0] : null;

// JSON encode tất cả các tỉnh
$selected_provinces_json = !empty($location_ids) 
    ? json_encode(array_values($location_ids)) 
    : null;
```

### 5. CSS Styling
**File:** `public/assets/css/pages/purchase/details.css`

**Thay đổi:**
- Thêm style cho multiple select
- Style cho các option đã chọn
- Thêm hover effect

**Code:**
```css
.form-control[multiple] {
    height: 200px;
    padding: 0.5rem;
}

.form-control[multiple] option:checked {
    background-color: var(--primary-500);
    color: white;
    font-weight: 500;
}
```

## Cách sử dụng

### Cho người dùng cuối:
1. Truy cập trang chọn gói dịch vụ
2. Tại phần "Tỉnh/Thành phố sử dụng":
   - Giữ phím **Ctrl** (Windows) hoặc **Command** (Mac)
   - Click vào các tỉnh muốn chọn
3. Tỉnh đầu tiên được chọn sẽ là tỉnh chính (dùng cho username)
4. Tất cả các tỉnh được chọn sẽ được dùng cho mountpoint

### Cho developer:
**Đọc dữ liệu từ database:**
```php
// Lấy tỉnh chính
$location_id = $registration['location_id'];

// Lấy tất cả các tỉnh đã chọn
$selected_provinces = json_decode($registration['selected_provinces'], true);

// Ví dụ: [21, 30, 40, 42]
```

**Query các tỉnh:**
```sql
-- Lấy thông tin tất cả các tỉnh đã chọn
SELECT l.* 
FROM location l
WHERE l.id IN (
    SELECT JSON_EXTRACT(selected_provinces, CONCAT('$[', idx, ']'))
    FROM (SELECT 0 AS idx UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) AS indices
    CROSS JOIN registration r
    WHERE r.id = :registration_id
    AND JSON_EXTRACT(selected_provinces, CONCAT('$[', idx, ']')) IS NOT NULL
)
```

Hoặc đơn giản hơn trong PHP:
```php
$selected_provinces = json_decode($registration['selected_provinces'], true);
$placeholders = str_repeat('?,', count($selected_provinces) - 1) . '?';
$sql = "SELECT * FROM location WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($selected_provinces);
$provinces = $stmt->fetchAll();
```

## Lưu ý quan trọng

### 1. Backward Compatibility
- Cột `location_id` vẫn được giữ nguyên để tương thích ngược
- Dữ liệu cũ đã được migrate tự động bằng UPDATE query

### 2. Khi tạo tài khoản RTK
- **Username:** Lấy `province_code` từ `location_id` (tỉnh chính/đầu tiên)
- **Mountpoint:** Tạo cho TẤT CẢ các tỉnh trong `selected_provinces`

### 3. Validation
- Phải chọn ít nhất 1 tỉnh
- Tối đa không giới hạn (có thể thêm limit nếu cần)
- Frontend và backend đều validate

### 4. Security
- Tất cả location_id đều được filter và validate
- Chỉ chấp nhận số nguyên dương
- JSON encode an toàn trước khi lưu vào database

## Testing

### Test cases cần kiểm tra:
1. ✅ Chọn 1 tỉnh duy nhất
2. ✅ Chọn nhiều tỉnh (2-5 tỉnh)
3. ✅ Không chọn tỉnh nào (phải hiện lỗi)
4. ✅ Dữ liệu được lưu đúng vào database
5. ✅ Đọc lại dữ liệu từ database chính xác
6. ✅ Tương thích với dữ liệu cũ

## Ví dụ dữ liệu

**Trong database:**
```
| id | user_id | location_id | selected_provinces | package_id | ... |
|----|---------|-------------|--------------------|------------|-----|
| 1  | 123     | 21          | [21, 30, 40, 42]  | 5          | ... |
| 2  | 456     | 50          | [50]              | 3          | ... |
```

**Khi hiển thị:**
- Registration #1: Tỉnh chính = Hà Nội (21), Các tỉnh = Hà Nội, Hải Phòng, Nghệ An, Đà Nẵng
- Registration #2: Tỉnh chính = TP.HCM (50), Các tỉnh = TP.HCM

## Hỗ trợ

Nếu có vấn đề, vui lòng kiểm tra:
1. Migration SQL đã chạy thành công chưa
2. Cache trình duyệt (Ctrl+F5 để refresh)
3. Console log có lỗi JavaScript không
4. Error log PHP có lỗi không

---
**Cập nhật cuối:** 07/10/2025
