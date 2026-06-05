# ✅ CHECKLIST - Triển khai chức năng chọn nhiều tỉnh

## Ngày: 07/10/2025

---

## 📋 Phần 1: Database (BẮT BUỘC)

- [ ] **Chạy migration SQL**
  - File: `db/migrations/migration_add_selected_provinces.sql`
  - Cách chạy: 
    ```sql
    -- Mở phpMyAdmin hoặc MySQL client
    -- Copy và chạy nội dung file migration
    ```
  - Kiểm tra: 
    ```sql
    DESCRIBE registration;
    -- Phải thấy cột 'selected_provinces' kiểu JSON
    ```

- [ ] **Kiểm tra dữ liệu cũ đã được migrate**
  ```sql
  SELECT id, location_id, selected_provinces 
  FROM registration 
  LIMIT 10;
  -- Các bản ghi cũ phải có selected_provinces = [location_id]
  ```

---

## 📋 Phần 2: Testing Frontend

- [ ] **Clear cache trình duyệt**
  - Nhấn `Ctrl + F5` hoặc `Cmd + Shift + R`

- [ ] **Test trang details.php**
  - URL: `http://localhost/02062025_user_web/public/pages/purchase/details.php?package=trial_7d`
  - [ ] Có thấy select box chiều cao 200px không?
  - [ ] Có thấy text "Có thể chọn nhiều tỉnh" không?
  - [ ] Giữ Ctrl + Click vào nhiều tỉnh → tỉnh có màu tím không?
  - [ ] Chọn 0 tỉnh rồi submit → có hiện lỗi không?
  - [ ] Chọn 1 tỉnh rồi submit → có chuyển đến thanh toán không?
  - [ ] Chọn 3-4 tỉnh rồi submit → có chuyển đến thanh toán không?

- [ ] **Test trang test_multiple_provinces.html**
  - URL: `http://localhost/02062025_user_web/public/tools/test_multiple_provinces.html`
  - [ ] Chọn nhiều tỉnh
  - [ ] Nhấn "Xem kết quả"
  - [ ] Có hiện kết quả đúng không?
  - [ ] location_id = tỉnh đầu tiên?
  - [ ] selected_provinces = JSON array đầy đủ?

---

## 📋 Phần 3: Testing Backend

- [ ] **Test tạo đơn hàng mới**
  - Login vào hệ thống
  - Chọn 1 gói dịch vụ
  - Chọn nhiều tỉnh (vd: Hà Nội, Đà Nẵng, TP.HCM)
  - Submit form
  - [ ] Có chuyển đến trang thanh toán không?
  - [ ] Check database:
    ```sql
    SELECT id, location_id, selected_provinces 
    FROM registration 
    ORDER BY id DESC 
    LIMIT 1;
    ```
  - [ ] location_id = tỉnh đầu tiên đã chọn?
  - [ ] selected_provinces = JSON array đúng không?

- [ ] **Test validation backend**
  - Thử gửi POST request không có location_id
  - [ ] Có bị reject không?
  - [ ] Có redirect về trang error không?

---

## 📋 Phần 4: Testing Helper Functions

- [ ] **Test helper file**
  - File: `docs/examples/selected_provinces_examples.php`
  - Uncomment từng ví dụ và chạy
  - [ ] Ví dụ 1: Hiển thị tỉnh
  - [ ] Ví dụ 2: Kiểm tra tỉnh đã chọn
  - [ ] Ví dụ 3: Thêm/xóa tỉnh
  - [ ] Ví dụ 4: Validate JSON
  - [ ] Ví dụ 5: Xử lý form
  - [ ] Ví dụ 6: Lấy province codes
  - [ ] Ví dụ 7: Hiển thị bảng
  - [ ] Ví dụ 8: Query theo tỉnh

---

## 📋 Phần 5: Integration Testing

- [ ] **Flow đầy đủ: Từ chọn gói đến thanh toán**
  1. [ ] Login
  2. [ ] Vào trang packages
  3. [ ] Chọn 1 gói (không phải trial)
  4. [ ] Chọn 3 tỉnh
  5. [ ] Nhập số lượng tài khoản
  6. [ ] Submit
  7. [ ] Chuyển đến trang thanh toán
  8. [ ] Upload minh chứng
  9. [ ] Check database registration có đúng dữ liệu không

- [ ] **Flow trial package**
  1. [ ] Chọn gói trial_7d
  2. [ ] Chọn nhiều tỉnh
  3. [ ] Submit (không cần số lượng)
  4. [ ] Chuyển đến thanh toán
  5. [ ] Check total_price = 0

---

## 📋 Phần 6: Cập nhật code liên quan (QUAN TRỌNG)

- [ ] **Tìm và cập nhật các file sử dụng location_id**
  ```bash
  # Search trong codebase
  grep -r "location_id" --include="*.php"
  ```
  
- [ ] **Cập nhật trang hiển thị registration list**
  - Thêm cột hiển thị tất cả tỉnh đã chọn
  - Dùng `get_selected_provinces_names()`

- [ ] **Cập nhật phần tạo survey account**
  - File: Tìm file tạo survey account
  - Thay vì chỉ dùng `location_id`
  - Phải loop qua `selected_provinces` để tạo cho tất cả tỉnh

- [ ] **Cập nhật phần tạo mountpoint**
  - Phải tạo mountpoint cho TẤT CẢ tỉnh trong `selected_provinces`
  - Không chỉ tạo cho `location_id`

- [ ] **Cập nhật API/Export nếu có**
  - Thêm field `selected_provinces` vào response
  - Format lại output

---

## 📋 Phần 7: Documentation

- [x] Tạo file migration SQL
- [x] Cập nhật details.php
- [x] Cập nhật details.js
- [x] Cập nhật process_order.php
- [x] Cập nhật details.css
- [x] Tạo selected_provinces_helper.php
- [x] Tạo examples file
- [x] Tạo test page
- [x] Tạo documentation đầy đủ
- [x] Tạo tóm tắt thay đổi
- [x] Tạo checklist này

---

## 📋 Phần 8: Performance & Security

- [ ] **Security checks**
  - [ ] Input validation đầy đủ
  - [ ] SQL injection protection (dùng prepared statements)
  - [ ] XSS protection (htmlspecialchars)
  - [ ] CSRF token

- [ ] **Performance checks**
  - [ ] Query performance OK? (với JSON_CONTAINS)
  - [ ] Có cần thêm index cho selected_provinces không?
  - [ ] Page load time chấp nhận được?

---

## 📋 Phần 9: Backup & Rollback Plan

- [ ] **Backup trước khi deploy**
  ```sql
  -- Backup bảng registration
  CREATE TABLE registration_backup_20251007 
  AS SELECT * FROM registration;
  ```

- [ ] **Rollback plan nếu có vấn đề**
  ```sql
  -- Drop cột mới
  ALTER TABLE registration DROP COLUMN selected_provinces;
  
  -- Restore từ backup nếu cần
  -- (Chi tiết xem file backup)
  ```

---

## 📋 Phần 10: Deploy lên Production

- [ ] Test trên local hoàn toàn OK
- [ ] Backup database production
- [ ] Deploy code lên server
- [ ] Chạy migration SQL trên production
- [ ] Test lại trên production
- [ ] Monitor error logs trong 24h đầu
- [ ] Thông báo cho team về update

---

## 🎯 Priority

### HIGH (Phải làm ngay)
- ✅ Chạy migration SQL
- ⬜ Test frontend basic
- ⬜ Test backend basic
- ⬜ Cập nhật phần tạo survey account/mountpoint

### MEDIUM (Nên làm)
- ⬜ Test helper functions
- ⬜ Integration testing
- ⬜ Cập nhật các trang hiển thị

### LOW (Có thể làm sau)
- ⬜ Performance optimization
- ⬜ Add more features

---

## 📝 Notes

**Ghi chú trong quá trình test:**

```
[DD/MM/YYYY HH:MM] - Bước thực hiện
[  /  /      :  ] - 

[  /  /      :  ] - 

[  /  /      :  ] - 
```

**Issues phát hiện:**

```
1. 

2. 

3. 
```

---

**Người thực hiện:** _________________

**Ngày bắt đầu:** _________________

**Ngày hoàn thành:** _________________

**Signature:** _________________
