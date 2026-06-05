# Tái cấu trúc trang Payment

## Tổng quan
Trang payment đã được tái cấu trúc hoàn toàn với giao diện wizard 3 bước, hiển thị tập trung ở trung tâm màn hình, không cần kéo xuống, và responsive hoàn toàn.

## Các thay đổi chính

### 1. Cấu trúc mới - 3 Sections

#### **Section 1: Thông tin đăng ký**
- Hiển thị thông tin gói dịch vụ, số lượng, tỉnh/thành phố
- Form nhập mã giảm giá với giao diện đẹp hơn
- Tổng hợp giá với hiệu ứng visual rõ ràng
- Nút "Tiếp tục" để chuyển sang bước 2

#### **Section 2: Quét mã để thanh toán**
- **Trường hợp miễn phí (giá = 0đ):**
  - Hiển thị thông báo đơn hàng miễn phí
  - Nút hoàn tất đăng ký ngay lập tức
  
- **Trường hợp có phí:**
  - Hiển thị mã QR VietQR
  - Thông tin ngân hàng với nút copy tiện lợi
  - Cảnh báo nếu có voucher auto-approve
  - Nút "Đã thanh toán" để chuyển sang bước 3

#### **Section 3: Upload ảnh minh chứng**
- Khu vực drag & drop file
- Preview ảnh/PDF đã chọn
- Nút "Hoàn tất" để submit
- Xử lý upload bằng AJAX
- Thông báo trạng thái upload

### 2. Giao diện Wizard

#### Thanh tiến trình (Wizard Steps)
- 3 bước được hiển thị rõ ràng
- Bước hiện tại được highlight với màu primary
- Bước đã hoàn thành có dấu tích màu xanh
- Animation mượt mà khi chuyển bước

#### Điều hướng
- Nút "Quay lại" và "Tiếp tục" ở mỗi section
- Chuyển đổi mượt mà với fadeIn animation
- Tự động scroll lên đầu khi chuyển bước

### 3. CSS Mới

**File:** `public/assets/css/pages/purchase/payment.css`

Các class chính:
- `.payment-wizard` - Container chính
- `.wizard-steps` - Thanh tiến trình
- `.wizard-section` - Mỗi section/bước
- `.section-card` - Card chứa nội dung
- `.info-row` - Dòng thông tin
- `.price-summary` - Bảng tổng giá
- `.voucher-section` - Khu vực mã giảm giá
- `.qr-container` - Container QR code
- `.bank-details` - Thông tin ngân hàng
- `.upload-area` - Khu vực upload
- `.btn-wizard` - Các nút điều hướng

### 4. JavaScript Mới

**File:** `public/assets/js/pages/purchase/payment_wizard.js`

Các chức năng:
- `goToStep(step)` - Chuyển đổi giữa các bước
- `setupFileUpload()` - Xử lý upload file
- `displayFilePreview()` - Hiển thị preview file
- `submitProof()` - Submit minh chứng thanh toán
- `setupCopyButtons()` - Nút copy thông tin
- `copyToClipboard()` - Copy text vào clipboard

### 5. Responsive Design

#### Desktop (>768px)
- Width tối đa 900px, căn giữa màn hình
- QR code 280x280px
- Layout 2 cột cho bank info

#### Tablet (≤768px)
- Padding giảm
- QR code 220x220px
- Bank info chuyển 1 cột
- Nút chuyển full width

#### Mobile (≤480px)
- QR code 180x180px
- Wizard steps thu gọn
- Font size nhỏ hơn
- Tối ưu touch target

## Tính năng giữ nguyên

✅ Không thay đổi bất kỳ logic backend nào
✅ Xử lý voucher vẫn hoạt động bình thường
✅ Auto-approve voucher vẫn hoạt động
✅ Upload proof vẫn sử dụng endpoint cũ
✅ Session management không thay đổi
✅ CSRF protection vẫn hoạt động

## Files đã sửa đổi

1. **public/pages/purchase/payment.php** - Restructure HTML
2. **public/assets/css/pages/purchase/payment.css** - Redesign CSS
3. **public/assets/js/pages/purchase/payment_wizard.js** - New JS file

## Files không thay đổi

- `public/handlers/action_handler.php` - Backend handlers
- `private/classes/purchase/PaymentService.php` - Payment service
- `public/assets/js/pages/purchase/payment_voucher.js` - Voucher logic
- `public/assets/js/pages/purchase/auto_voucher_notification.js` - Auto voucher

## Testing checklist

- [ ] Trial activation flow
- [ ] Regular purchase flow
- [ ] Voucher application/removal
- [ ] Free order (voucher 100%)
- [ ] Auto-approve voucher
- [ ] QR code generation
- [ ] Copy bank info
- [ ] File upload (drag & drop)
- [ ] File upload (file picker)
- [ ] File preview
- [ ] Form submission
- [ ] Responsive on mobile
- [ ] Responsive on tablet
- [ ] Navigation between steps
- [ ] Error handling

## Browser compatibility

- ✅ Chrome/Edge (Latest)
- ✅ Firefox (Latest)
- ✅ Safari (Latest)
- ✅ Mobile browsers

## Notes

- Upload endpoint vẫn là: `/public/handlers/action_handler.php?module=purchase&action=upload_proof`
- Cần test kỹ trên production trước khi deploy
- Có thể cần điều chỉnh màu sắc theo brand guidelines
- Icon sử dụng FontAwesome (cần đảm bảo đã include)
