# Hotfix cho Payment Page Wizard

## Ngày: 01/11/2025

## Các lỗi đã sửa:

### 1. ✅ Wizard Steps - Completed steps mất CSS số bước

**Vấn đề:** 
- Khi chuyển sang bước mới, các bước đã hoàn thành (completed) bị mất hiển thị số
- Không có icon checkmark cho bước đã hoàn thành

**Giải pháp:**
- Thêm CSS `::before` pseudo-element để hiển thị icon checkmark (✓)
- Sử dụng FontAwesome icon `\f00c` 
- Ẩn số gốc bằng `font-size: 0` và hiển thị icon với `font-size: 1rem`
- Background color vẫn giữ màu success-600

**File sửa:** `public/assets/css/pages/purchase/payment.css`

```css
.wizard-step.completed .step-number {
    background-color: var(--success-600);
    color: white;
    position: relative;
    font-size: 0; /* Hide number */
}

.wizard-step.completed .step-number::before {
    content: '\f00c'; /* FontAwesome checkmark */
    font-family: 'Font Awesome 5 Free', 'FontAwesome';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1rem; /* Show icon */
}
```

---

### 2. ✅ Upload Section - Thiếu nút tải lên và logic upload

**Vấn đề:**
- Section 3 (Upload ảnh minh chứng) không có text "Tải lên" trên nút
- Chưa có logic upload thực sự
- Thiếu progress bar hiển thị tiến trình upload

**Giải pháp:**

#### A. Cập nhật nút upload (HTML)
- Thay đổi text nút từ "Hoàn tất" → "Tải lên minh chứng"
- Thêm icon upload `<i class="fas fa-upload"></i>`

**File:** `public/pages/purchase/payment.php`

```html
<button type="button" id="upload-submit-btn" class="btn btn-success btn-wizard" onclick="submitProof()" disabled>
    <i class="fas fa-upload"></i> Tải lên minh chứng
</button>
```

#### B. Thêm Progress Bar (HTML)
Thêm element hiển thị tiến trình upload:

```html
<div id="upload-progress-container" class="upload-progress-container" style="display: none;">
    <div class="progress-bar-wrapper">
        <div id="upload-progress-bar" class="progress-bar-fill"></div>
    </div>
    <p id="upload-progress-text" class="progress-text">Đang tải lên: 0%</p>
</div>
```

#### C. CSS cho Progress Bar
**File:** `public/assets/css/pages/purchase/payment.css`

```css
.upload-progress-container {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: var(--rounded-md);
    border: 1px solid var(--gray-200);
}

.progress-bar-wrapper {
    width: 100%;
    height: 20px;
    background-color: var(--gray-200);
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-500), var(--primary-700));
    transition: width 0.3s ease;
    width: 0%;
}
```

#### D. Logic Upload với XMLHttpRequest
**File:** `public/assets/js/pages/purchase/payment_wizard.js`

**Tính năng:**
- Sử dụng XMLHttpRequest thay vì Fetch API để track progress
- Upload event listener để cập nhật progress bar realtime
- Upload file với tên field đúng: `payment_proof_image`
- Gọi đúng endpoint: `/public/handlers/action_handler.php?module=purchase&action=upload_payment_proof`
- Xử lý cả JSON response và HTML response
- Redirect về `/public/pages/transaction.php?success=proof_uploaded` khi thành công

**Flow:**
1. User chọn file → Preview hiện ra
2. Click "Tải lên minh chứng" → Disable button, show progress
3. Upload với progress tracking (0% → 100%)
4. Nhận response → Parse JSON hoặc HTML
5. Thành công → Redirect
6. Lỗi → Show message, reset button

---

## Files đã sửa đổi:

### 1. `public/assets/css/pages/purchase/payment.css`
- Sửa `.wizard-step.completed .step-number` styling
- Thêm progress bar styles
- Thêm progress text styles

### 2. `public/pages/purchase/payment.php`
- Thêm progress bar container HTML
- Cập nhật text nút upload
- Thêm icon upload

### 3. `public/assets/js/pages/purchase/payment_wizard.js`
- Viết lại function `submitProof()` với XMLHttpRequest
- Thêm function `resetUploadButton()`
- Cập nhật `displayFilePreview()` để update button text
- Track upload progress realtime
- Handle response và redirect

---

## Testing Checklist:

- [x] Wizard steps hiển thị đúng (1, 2, 3)
- [x] Completed step có icon checkmark ✓
- [x] Active step có màu primary
- [x] Upload button có text "Tải lên minh chứng"
- [x] Upload button có icon upload
- [x] Chọn file → Button enable
- [x] Click upload → Progress bar hiện
- [x] Progress bar update 0% → 100%
- [x] Upload thành công → Redirect
- [x] Upload lỗi → Show error message
- [ ] Test trên production

---

## Endpoint sử dụng:

**Upload Proof:**
- Method: POST
- URL: `/public/handlers/action_handler.php?module=purchase&action=upload_payment_proof`
- Fields:
  - `payment_proof_image` (file)
  - `registration_id` (string)
  - `csrf_token` (string)
- Max size: 15MB
- Allowed types: JPG, PNG, GIF

**Success Redirect:**
- URL: `/public/pages/transaction.php?success=proof_uploaded`

---

## Notes:

- Action handler `upload_payment_proof.php` đã tồn tại và hoạt động
- CSRF validation đã được handle trong action_handler.php
- File size limit: 15MB (được set trong upload_payment_proof.php)
- Progress tracking chỉ hoạt động với XMLHttpRequest, không hoạt động với Fetch API
- FontAwesome cần được include trong header để icon checkmark hiển thị

---

## Browser Support:

- ✅ Chrome/Edge (Latest) - XMLHttpRequest supported
- ✅ Firefox (Latest) - XMLHttpRequest supported
- ✅ Safari (Latest) - XMLHttpRequest supported
- ✅ Mobile browsers - XMLHttpRequest supported

---

## Potential Issues & Solutions:

**Issue:** Icon checkmark không hiển thị
- **Cause:** FontAwesome chưa được include
- **Solution:** Check header.php có include FontAwesome CSS không

**Issue:** Upload không hoạt động
- **Cause:** CSRF token invalid hoặc session expired
- **Solution:** Refresh page để generate CSRF token mới

**Issue:** Progress bar không update
- **Cause:** Server response quá nhanh (file nhỏ, localhost)
- **Solution:** Bình thường, trên production sẽ thấy rõ progress

**Issue:** Redirect không hoạt động
- **Cause:** Response không parse được JSON
- **Solution:** Đã handle fallback parse HTML response
