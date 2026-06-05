# 🗺️ Chức năng Chọn Nhiều Tỉnh - Quick Start

## 📖 Tổng quan

Chức năng cho phép người dùng chọn **nhiều tỉnh/thành phố** khi đăng ký gói dịch vụ RTK.

- **Tỉnh đầu tiên** → `location_id` (tỉnh chính, dùng cho username)
- **Tất cả tỉnh đã chọn** → `selected_provinces` (JSON array, dùng cho mountpoint)

---

## 🚀 Quick Start

### 1. Chạy Migration SQL (BẮT BUỘC)

```sql
-- File: db/migrations/migration_add_selected_provinces.sql
ALTER TABLE `registration`
ADD COLUMN `selected_provinces` JSON DEFAULT NULL 
COMMENT 'Danh sách location_id các tỉnh được chọn'
AFTER `location_id`;

UPDATE `registration`
SET `selected_provinces` = JSON_ARRAY(`location_id`)
WHERE `location_id` IS NOT NULL AND `selected_provinces` IS NULL;
```

### 2. Test ngay

Truy cập: `http://localhost/02062025_user_web/public/tools/test_multiple_provinces.html`

### 3. Sử dụng trong code

```php
// Include helper
require_once 'private/utils/selected_provinces_helper.php';

// Lấy tên các tỉnh
$names = get_selected_provinces_names($pdo, $registration['selected_provinces']);
echo $names; // "Hà Nội, Đà Nẵng, TP.HCM"

// Lấy thông tin đầy đủ
$provinces = get_selected_provinces_details($pdo, $registration['selected_provinces']);
foreach ($provinces as $p) {
    echo $p['province'] . "\n";
}
```

---

## 📁 Files quan trọng

| File | Mô tả |
|------|-------|
| `db/migrations/migration_add_selected_provinces.sql` | SQL migration |
| `public/pages/purchase/details.php` | Form chọn tỉnh |
| `private/action/purchase/process_order.php` | Xử lý đơn hàng |
| `private/utils/selected_provinces_helper.php` | Helper functions |
| `docs/THAY_DOI_CHON_NHIEU_TINH.md` | Tài liệu chi tiết |
| `docs/CHECKLIST_TRIEN_KHAI.md` | Checklist triển khai |
| `docs/examples/selected_provinces_examples.php` | 8 ví dụ sử dụng |

---

## 🎯 Cách sử dụng cho người dùng

1. Vào trang chọn gói dịch vụ
2. Giữ **Ctrl** (Windows) hoặc **Cmd** (Mac)
3. Click chọn nhiều tỉnh
4. Tỉnh đầu tiên = tỉnh chính

![Demo](https://via.placeholder.com/800x400/667eea/ffffff?text=Multiple+Province+Selection)

---

## 💾 Dữ liệu trong Database

**Ví dụ:**

```
| id | location_id | selected_provinces |
|----|-------------|--------------------|
| 1  | 21          | [21, 42, 50]      |
```

**Giải thích:**
- `location_id = 21` → Tỉnh chính là **Hà Nội**
- `selected_provinces = [21, 42, 50]` → Chọn **Hà Nội, Đà Nẵng, TP.HCM**

---

## 🔧 Helper Functions

### Lấy danh sách IDs
```php
$ids = get_selected_province_ids($json);
// [21, 42, 50]
```

### Lấy tên tỉnh
```php
$names = get_selected_provinces_names($pdo, $json);
// "Hà Nội, Đà Nẵng, TP.HCM"
```

### Lấy thông tin đầy đủ
```php
$provinces = get_selected_provinces_details($pdo, $json);
// [['id'=>21, 'province'=>'Hà Nội', ...], ...]
```

### Kiểm tra tỉnh đã chọn
```php
$has = is_province_selected($json, 21);
// true/false
```

### Format hiển thị
```php
$html = format_provinces_display($pdo, $location_id, $json);
// "<strong>Hà Nội</strong> (Chính), Đà Nẵng, TP.HCM"
```

**[Xem 8 ví dụ khác →](docs/examples/selected_provinces_examples.php)**

---

## ✅ Checklist

- [ ] Đã chạy migration SQL?
- [ ] Test trang details.php?
- [ ] Test trang test tool?
- [ ] Test tạo đơn hàng mới?
- [ ] Cập nhật phần tạo mountpoint?

**[Checklist đầy đủ →](docs/CHECKLIST_TRIEN_KHAI.md)**

---

## 📚 Documentation

- **Chi tiết:** [THAY_DOI_CHON_NHIEU_TINH.md](docs/THAY_DOI_CHON_NHIEU_TINH.md)
- **Tóm tắt:** [TOM_TAT_THAY_DOI.md](docs/TOM_TAT_THAY_DOI.md)
- **Checklist:** [CHECKLIST_TRIEN_KHAI.md](docs/CHECKLIST_TRIEN_KHAI.md)
- **Examples:** [selected_provinces_examples.php](docs/examples/selected_provinces_examples.php)

---

## ❓ Troubleshooting

### Không thấy multiple select?
- Clear cache: `Ctrl + F5`
- Check file CSS đã load?

### Submit form không hoạt động?
- Mở Console (F12) → Check lỗi JS
- Check network tab → Request có gửi không?

### Database không lưu selected_provinces?
- Chạy migration SQL chưa?
- Check error log PHP

### Validation lỗi?
- Phải chọn ít nhất 1 tỉnh
- Check backend có nhận được `location_id[]` array không?

---

## 📞 Support

Có vấn đề? Check:
1. Console log (F12)
2. PHP error log
3. Network tab
4. Database structure

---

## 📝 Version History

- **v1.0** (07/10/2025) - Initial release
  - Multiple province selection
  - Helper functions
  - Documentation
  - Test tools

---

**Made with ❤️ by GitHub Copilot**
