# Kế hoạch ẩn thông tin giao dịch mua bán trên giao diện người dùng

**Mục tiêu:** Web trông như nền tảng cung cấp tài khoản RTK công khai, miễn phí. Người dùng đăng ký → nhận tài khoản, không thấy bất kỳ thông tin giá cả, thanh toán, hóa đơn nào.

**Chiến lược triển khai:** Sử dụng một hằng số config (`HIDE_PAYMENT_UI`) để kiểm soát toàn bộ. Cách này dễ bật/tắt lại sau, không xóa code gốc.

---

## Tổng quan các file bị ảnh hưởng

| # | File | Hành động | Ghi chú |
|---|------|-----------|---------|
| 1 | `private/config/config.php` | Thêm hằng số | Thêm `HIDE_PAYMENT_UI = true` |
| 2 | `private/includes/sidebar.php` | Ẩn menu items | Ẩn "Mua tài khoản", "Quản lý giao dịch", "Thông tin xuất hóa đơn" |
| 3 | `public/pages/dashboard.php` | Ẩn UI elements | Ẩn "Giao dịch đang xử lý", section "Giao dịch gần đây" |
| 4 | `public/pages/homepage.php` | Sửa text + ẩn giá | Ẩn giá gói, sửa CTA |
| 5 | `public/pages/purchase/packages.php` | Sửa text + ẩn giá | Ẩn giá, VAT, savings; đổi label nút |
| 6 | `public/pages/purchase/details.php` | Sửa text + ẩn giá | Ẩn giá, VAT, đổi tiêu đề |
| 7 | `public/pages/purchase/payment.php` | Redirect hoặc ẩn UI | Ẩn toàn bộ thông tin thanh toán |
| 8 | `public/pages/purchase/renewal.php` | Sửa text + ẩn giá | Ẩn giá, VAT |
| 9 | `public/pages/purchase/success.php` | Sửa text | Đổi "Mua thành công" → "Đăng ký thành công" |
| 10 | `public/pages/purchase/upload_proof.php` | Redirect | Redirect về dashboard |
| 11 | `public/pages/transaction.php` | Redirect | Redirect về dashboard |
| 12 | `public/pages/invoice/request_export_invoice.php` | Redirect | Redirect về dashboard |
| 13 | `public/pages/invoice/completed_export_invoice.php` | Redirect | Redirect về dashboard |
| 14 | `public/pages/setting/invoice.php` | Redirect hoặc ẩn | Ẩn hoặc redirect về profile |
| 15 | `public/pages/rtk_accountmanagement.php` | Sửa text + ẩn | Ẩn nút "Gia hạn", link "Mua Tài Khoản Ngay" |
| 16 | `public/pages/referral/dashboard_referal.php` | Ẩn tabs | Ẩn tab "Hoa hồng", "Rút tiền", sửa text chính sách |
| 17 | `public/handlers/export_retail_invoice.php` | Redirect | Redirect về dashboard |

---

## Bước 1 — Thêm config flag

**File:** `private/config/config.php`

Tìm vị trí sau khi các hằng số khác được định nghĩa, thêm vào:

```php
// ============================================================
// UI MODE: Ẩn toàn bộ giao diện liên quan đến thanh toán
// Đặt thành false để hiển thị lại giao diện thanh toán đầy đủ
// ============================================================
define('HIDE_PAYMENT_UI', true);
```

---

## Bước 2 — Sidebar (`private/includes/sidebar.php`)

**Vị trí:** Mảng `$nav_items` (khoảng dòng 4–17)

**Thay đổi:**  
Bọc 3 mục sau bằng `HIDE_PAYMENT_UI`:
- `Mua tài khoản` → đổi label thành `Đăng ký tài khoản`
- `Quản lý giao dịch` → ẩn hoàn toàn khi flag bật
- `Thông tin xuất hóa đơn` → ẩn hoàn toàn khi flag bật

```php
// Thay dòng:
['label' => 'Mua tài khoản', 'icon' => 'fa-shopping-cart', ...]
// Thành:
[
    'label' => defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI ? 'Đăng ký tài khoản' : 'Mua tài khoản',
    'icon'  => defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI ? 'fa-user-plus' : 'fa-shopping-cart',
    'url'   => '/pages/purchase/packages.php',
    'active_check' => 'packages.php'
],

// Bọc "Quản lý giao dịch" và "Thông tin xuất hóa đơn":
...(!( defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI ) ? [
    ['label' => 'Quản lý giao dịch',       'icon' => 'fa-file-invoice-dollar', 'url' => '/pages/transaction.php',        'active_check' => 'transaction.php'],
    ['label' => 'Thông tin xuất hóa đơn',   'icon' => 'fa-file-alt',            'url' => '/pages/setting/invoice.php',    'active_check' => 'invoice.php'],
] : []),
```

---

## Bước 3 — Dashboard (`public/pages/dashboard.php`)

### 3a. Ẩn stat card "Giao dịch đang xử lý"
**Vị trí:** Dòng ~52–56
```php
// Bọc stat card vào điều kiện:
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<div class="stat-card">
    <i class="icon fas fa-sync warning"></i>
    <h3>Giao dịch đang xử lý</h3>
    <p class="value" id="pending-transactions"><?php echo htmlspecialchars($pending_transactions); ?></p>
</div>
<?php endif; ?>
```

### 3b. Ẩn section "Giao dịch gần đây"
**Vị trí:** `.dashboard-box.transactions-box` (dòng ~100–160)
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<div class="dashboard-box transactions-box">
    ... <!-- toàn bộ nội dung box giao dịch -->
</div>
<?php endif; ?>
```

---

## Bước 4 — Trang chủ (`public/pages/homepage.php`)

### 4a. Section "Packages" — ẩn giá tiền, hiện "Miễn phí"
**Vị trí:** Vòng lặp `foreach ($all_packages ...)` trong section `#packages` (khoảng dòng 218–245)

```php
// Thay:
<div class="package-price">
    <?php echo number_format($package['price'] ?? 0, 0, ',', '.'); ?>đ
    <span class="duration"><?php echo htmlspecialchars($package['duration_text'] ?? ''); ?></span>
</div>

// Thành:
<div class="package-price">
    <?php if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI): ?>
        <span class="free-badge">Miễn phí</span>
        <span class="duration"><?php echo htmlspecialchars($package['duration_text'] ?? ''); ?></span>
    <?php else: ?>
        <?php echo number_format($package['price'] ?? 0, 0, ',', '.'); ?>đ
        <span class="duration"><?php echo htmlspecialchars($package['duration_text'] ?? ''); ?></span>
    <?php endif; ?>
</div>
```

### 4b. CTA section — đổi text
**Vị trí:** Section `.cta` (dòng ~260–270)

```php
// Thay text:
"Đăng ký tài khoản, mua gói dịch vụ và trải nghiệm..."
// Thành:
"Đăng ký tài khoản và trải nghiệm các dịch vụ đo đạc RTK chất lượng cao, hoàn toàn miễn phí."
```

---

## Bước 5 — Trang gói dịch vụ (`public/pages/purchase/packages.php`)

### 5a. Đổi tiêu đề trang
```php
// Thay:
<h2 class="text-2xl font-semibold mb-4">Mua Gói Tài Khoản</h2>
<p class="text-gray-600 mb-6">Chọn gói phù hợp với nhu cầu sử dụng của bạn.</p>

// Thành:
<h2 class="text-2xl font-semibold mb-4">
    <?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Đăng Ký Tài Khoản' : 'Mua Gói Tài Khoản'; ?>
</h2>
<p class="text-gray-600 mb-6">
    <?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)
        ? 'Chọn gói phù hợp với nhu cầu sử dụng của bạn - hoàn toàn miễn phí.'
        : 'Chọn gói phù hợp với nhu cầu sử dụng của bạn.'; ?>
</p>
```

### 5b. Ẩn giá tiền, đổi thành "Miễn phí"
**Vị trí:** Block `.package-price` trong vòng lặp
```php
// Thay:
<div class="package-price">
    <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
    <span class="duration"><?php echo htmlspecialchars($package['duration_text']); ?></span>
</div>

// Thành:
<div class="package-price">
    <?php if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI): ?>
        <span style="font-size:1.5rem;font-weight:700;color:var(--primary-color);">Miễn phí</span>
        <span class="duration"><?php echo htmlspecialchars($package['duration_text']); ?></span>
    <?php else: ?>
        <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
        <span class="duration"><?php echo htmlspecialchars($package['duration_text']); ?></span>
    <?php endif; ?>
</div>
```

### 5c. Ẩn savings text
```php
// Bọc lại:
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<span class="package-savings">
    <?php echo isset($package['savings_text']) ? htmlspecialchars($package['savings_text']) : '&nbsp;'; ?>
</span>
<?php endif; ?>
```

### 5d. Ẩn VAT selector (Cá nhân/Công ty)
```php
// Bọc lại:
<?php if ($package['package_id'] !== 'trial_7d' && !(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<div class="purchase-type-selector">
    ...
</div>
<?php endif; ?>
```

### 5e. Ẩn Global Discount Banner
```php
// Bọc lại:
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) && defined('SHOW_GLOBAL_DISCOUNT') && SHOW_GLOBAL_DISCOUNT === 'yes'): ?>
    <div id="globalDiscountBanner" ...>...</div>
<?php endif; ?>
```

---

## Bước 6 — Chi tiết đặt hàng (`public/pages/purchase/details.php`)

### 6a. Đổi tiêu đề
```php
// Thay:
<h2 class="text-2xl font-semibold mb-4">Chi tiết mua hàng</h2>

// Thành:
<h2 class="text-2xl font-semibold mb-4">
    <?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Thông tin đăng ký' : 'Chi tiết mua hàng'; ?>
</h2>
```

### 6b. Ẩn thông tin giá và tổng tiền
**Vị trí:** Phần hiển thị `$display_price`, `$vat_text`, tổng số tiền  
Bọc toàn bộ phần giá tiền trong điều kiện:
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
    <!-- Block hiển thị giá, VAT, tổng tiền -->
<?php else: ?>
    <div class="selected-package-info">
        Bạn đang đăng ký: <strong><?php echo htmlspecialchars($selected_package['name']); ?></strong>
        (<?php echo htmlspecialchars($selected_package['duration_text']); ?>) — <span style="color:green;font-weight:600;">Miễn phí</span>
    </div>
<?php endif; ?>
```

### 6c. Ẩn lựa chọn loại hình (Cá nhân/Công ty VAT)
Bọc purchase_type selector vào điều kiện `HIDE_PAYMENT_UI`.

---

## Bước 7 — Trang thanh toán (`public/pages/purchase/payment.php`)

Đây là file phức tạp nhất (QR code VietQR, upload bank slip, voucher...).

**Chiến lược:** Khi `HIDE_PAYMENT_UI = true`, **bỏ qua bước thanh toán, redirect thẳng tới `success.php`** với `auto_approved=1`.

Thêm vào ngay sau các check session/auth (khoảng dòng 22–25):

```php
// Bỏ qua thanh toán khi ở chế độ công khai miễn phí
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    // Đánh dấu auto approved để trang success hiển thị đúng
    $_SESSION['purchase_details']['auto_approved'] = true;
    $redirect_url = $base_url . '/public/pages/purchase/success.php?auto_approved=1';
    if (isset($_SESSION['pending_is_trial']) && $_SESSION['pending_is_trial']) {
        $redirect_url .= '&is_trial=1';
    }
    if (isset($_SESSION['is_renewal']) && $_SESSION['is_renewal']) {
        $redirect_url .= '&renewal=1';
    }
    header('Location: ' . $redirect_url);
    exit;
}
```

---

## Bước 8 — Trang gia hạn (`public/pages/purchase/renewal.php`)

### 8a. Đổi tiêu đề
```php
// Thay:
<h2>Gia hạn tài khoản RTK</h2>

// Thành:
<h2><?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Gia hạn tài khoản' : 'Gia hạn tài khoản RTK'; ?></h2>
```

### 8b. Ẩn giá tiền trong bảng gói gia hạn
Các cột/dòng hiển thị `price`, `total_price`, VAT → bọc vào điều kiện `HIDE_PAYMENT_UI`.

### 8c. Ẩn VAT note và payment info
Bọc phần chọn VAT, phần giá tổng vào `!HIDE_PAYMENT_UI`.

---

## Bước 9 — Trang thành công (`public/pages/purchase/success.php`)

### 9a. Đổi text title
```php
// Thay:
echo 'Mua tài khoản thành công!';

// Thành:
echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Đăng ký tài khoản thành công!' : 'Mua tài khoản thành công!';
```

### 9b. Ẩn block "Upload minh chứng thành công"
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) && isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
    <!-- Block upload thành công -->
<?php endif; ?>
```

### 9c. Ẩn link "Quản lý giao dịch" trong nút điều hướng
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
    <a href=".../transaction.php">Xem lịch sử giao dịch</a>
<?php endif; ?>
```

---

## Bước 10 — Các trang redirect khi `HIDE_PAYMENT_UI = true`

**Thêm đoạn code sau vào đầu mỗi file (sau phần auth check):**

### `public/pages/purchase/upload_proof.php`
```php
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    header('Location: ' . $base_url . '/public/pages/dashboard.php');
    exit;
}
```

### `public/pages/transaction.php`
```php
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    header('Location: ' . $base_url . '/public/pages/dashboard.php');
    exit;
}
```

### `public/pages/invoice/request_export_invoice.php`
```php
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    header('Location: ' . $base_url . '/public/pages/dashboard.php');
    exit;
}
```

### `public/pages/invoice/completed_export_invoice.php`
```php
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    header('Location: ' . $base_url . '/public/pages/dashboard.php');
    exit;
}
```

### `public/pages/setting/invoice.php`
```php
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    header('Location: ' . $base_url . '/public/pages/setting/profile.php');
    exit;
}
```

### `public/handlers/export_retail_invoice.php`
```php
if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) {
    header('Location: ' . $base_url . '/public/pages/dashboard.php');
    exit;
}
```

---

## Bước 11 — Quản lý tài khoản RTK (`public/pages/rtk_accountmanagement.php`)

### 11a. Ẩn nút "Gia hạn"
**Vị trí:** Dòng ~173 (form với action `renewal.php`)
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<form id="renewal-form" method="post" action="...renewal.php">
    <button type="submit" id="renewal-btn" ...>
        <i class="fas fa-redo"></i> Gia hạn
    </button>
</form>
<?php endif; ?>
```

### 11b. Đổi link "Mua Tài Khoản Ngay"
**Vị trí:** Dòng ~216
```php
// Thay:
<a href=".../packages.php" class="buy-now-btn">Mua Tài Khoản Ngay</a>

// Thành:
<a href="...packages.php" class="buy-now-btn">
    <?php echo (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI) ? 'Đăng Ký Tài Khoản Ngay' : 'Mua Tài Khoản Ngay'; ?>
</a>
```

---

## Bước 12 — Trang giới thiệu (`public/pages/referral/dashboard_referal.php`)

### 12a. Ẩn tab "Hoa hồng" và "Rút tiền"
**Vị trí:** Phần `<ul class="nav nav-tabs ...">` (dòng ~59–64)
```php
// Bọc 2 tab items:
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<li class="nav-item"><a class="nav-link" id="commission-tab" ...>Hoa hồng</a></li>
<li class="nav-item"><a class="nav-link" id="withdrawal-tab" ...>Rút tiền</a></li>
<?php endif; ?>
```

### 12b. Ẩn nội dung tab Hoa hồng và Rút tiền
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
    <!-- Tab 3: Commission -->
    <div class="tab-pane fade" id="commission" ...>...</div>
    <!-- Tab 4: Withdrawal -->
    <div class="tab-pane fade" id="withdrawal" ...>...</div>
<?php endif; ?>
```

### 12c. Sửa chính sách giới thiệu
**Vị trí:** Dòng hướng dẫn "Khi họ đăng ký và thanh toán, bạn sẽ nhận được hoa hồng 5%."
```php
// Thay:
<li>Khi họ đăng ký và thanh toán, bạn sẽ nhận được hoa hồng 5%.</li>

// Thành:
<?php if (defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI): ?>
<li>Khi họ đăng ký tài khoản qua liên kết của bạn, số lượng giới thiệu của bạn sẽ tăng lên.</li>
<?php else: ?>
<li>Khi họ đăng ký và thanh toán, bạn sẽ nhận được hoa hồng 5%.</li>
<?php endif; ?>
```

### 12d. Ẩn `alert` thông tin chính sách hoa hồng 5%
```php
<?php if (!(defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI)): ?>
<div class="alert alert-info">
    <strong>Chính sách hoa hồng:</strong> Bạn sẽ nhận được 5% giá trị thanh toán...
</div>
<?php endif; ?>
```

### 12e. Ẩn cột "Hoa hồng" trong bảng xếp hạng
**Vị trí:** table `.table-ranking` trong tab ranking
```php
// Ẩn header <th>Hoa hồng</th> và <td>...commission...</td>
```

---

## Bước 13 — Trang `landing.php`

### 13a. Ẩn/đổi text voucher code
**File:** `public/landing.php` — dòng 2  
Voucher code `TNN3THANG` được dùng trong URL đăng ký, **không cần ẩn** (không hiển thị trực tiếp cho user).  
Tuy nhiên, nếu landing page có hiển thị giá hay ưu đãi tiền tệ, bọc vào `HIDE_PAYMENT_UI`.

---

## Thứ tự thực hiện (ưu tiên)

```
1. Bước 1 — config.php  (nền tảng cho mọi thứ)
2. Bước 2 — sidebar.php  (navigation)
3. Bước 10 — Redirect các trang nhạy cảm  (bảo vệ URL trực tiếp)
4. Bước 7 — payment.php  (bỏ qua bước thanh toán)
5. Bước 3 — dashboard.php
6. Bước 5 — packages.php
7. Bước 6 — details.php
8. Bước 8 — renewal.php
9. Bước 9 — success.php
10. Bước 11 — rtk_accountmanagement.php
11. Bước 12 — dashboard_referal.php
12. Bước 4 — homepage.php
```

---

## Ghi chú quan trọng

### ✅ KHÔNG thay đổi
- Toàn bộ backend logic (PHP classes, handlers, database)
- Quy trình xử lý đơn hàng vẫn chạy bình thường ở backend
- Voucher auto-approve vẫn hoạt động (đây là cơ chế để tài khoản được tạo tự động)
- CSRF protection, session management

### ⚠️ LƯU Ý khi implement
- Luôn dùng `defined('HIDE_PAYMENT_UI') && HIDE_PAYMENT_UI` để tránh lỗi khi constant chưa được define
- Trang `payment.php` cần xử lý cẩn thận vì nếu redirect không đúng sẽ gây lỗi flow đặt tài khoản
- Khi `HIDE_PAYMENT_UI = true`, flow mua hàng sẽ là: `packages → details → payment(redirect) → success` — bước payment sẽ tự động forward mà không cần user thao tác
- Đảm bảo backend xử lý order vẫn được gọi trước khi redirect từ payment.php (hoặc gọi action_handler trực tiếp từ details.php)

### 🔄 Rollback
Để hiển thị lại đầy đủ giao diện thanh toán: **chỉ cần đổi** `define('HIDE_PAYMENT_UI', true)` → `define('HIDE_PAYMENT_UI', false)` trong `config.php`.

---

## Kiểm tra sau khi triển khai

- [ ] Truy cập trực tiếp `/public/pages/transaction.php` → redirect về dashboard
- [ ] Truy cập trực tiếp `/public/pages/invoice/request_export_invoice.php` → redirect về dashboard
- [ ] Sidebar không còn "Quản lý giao dịch", "Thông tin xuất hóa đơn"
- [ ] Dashboard không còn card "Giao dịch đang xử lý"
- [ ] Trang packages hiển thị "Miễn phí" thay vì giá tiền
- [ ] Flow: packages → details → success (không qua trang payment)
- [ ] Trang referral không còn tab Hoa hồng và Rút tiền
- [ ] Trang RTK management không còn nút "Gia hạn"
