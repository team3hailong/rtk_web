# Hướng dẫn chuyển đổi sang Cloudinary

## Tổng quan
Hệ thống đã được cập nhật để sử dụng Cloudinary thay vì lưu trữ file tại local server. Cloudinary cung cấp:

- **Lưu trữ cloud**: An toàn và đáng tin cậy
- **Tối ưu hóa hình ảnh**: Tự động nén và chuyển đổi format
- **CDN**: Tải nhanh từ mọi nơi trên thế giới
- **Transformations**: Resize, crop, watermark tự động
- **Backup**: Dữ liệu được sao lưu tự động

## Cấu hình

### 1. Tạo tài khoản Cloudinary
1. Truy cập [cloudinary.com](https://cloudinary.com)
2. Đăng ký tài khoản miễn phí
3. Lấy thông tin: Cloud Name, API Key, API Secret

### 2. Cập nhật file .env
Thêm cấu hình Cloudinary vào file `.env`:

```bash
# Cấu hình Cloudinary
CLOUDINARY_CLOUD_NAME=your_cloud_name_here
CLOUDINARY_API_KEY=your_api_key_here
CLOUDINARY_API_SECRET=your_api_secret_here
```

### 3. Chạy migration database
Thêm cột `payment_image_public_id` vào bảng `transaction_history`:

```sql
-- Chạy file migration
source db/migrations/20250801_add_cloudinary_support.sql;
```

Hoặc chạy SQL trực tiếp:
```sql
ALTER TABLE transaction_history 
ADD COLUMN payment_image_public_id VARCHAR(255) NULL 
COMMENT 'Cloudinary public_id for payment proof image' 
AFTER payment_image;

CREATE INDEX idx_transaction_history_public_id ON transaction_history(payment_image_public_id);
```

## Migration dữ liệu cũ (tùy chọn)

### Cách 1: Tự động migration
Chạy script migration để upload tất cả ảnh local lên Cloudinary:

```bash
cd scripts/
php migrate_to_cloudinary.php
```

**Lưu ý**: 
- Sao lưu database trước khi chạy
- Script sẽ hỏi xác nhận trước khi thực hiện
- Không xóa file local tự động (cần uncomment code nếu muốn)

### Cách 2: Migration thủ công
Để giữ an toàn, có thể migration từng phần:

1. **Test với vài record đầu**:
   ```sql
   SELECT * FROM transaction_history 
   WHERE payment_image IS NOT NULL 
   AND payment_image_public_id IS NULL 
   LIMIT 5;
   ```

2. **Chạy script với limit nhỏ** (sửa trong script)

## Chạy trên Production (cPanel)

Nếu bạn đã deploy qua cPanel và không có truy cập SSH trực tiếp, bạn có thể thực hiện:

1. **Sử dụng cPanel Terminal** (nếu có):
   ```bash
   cd public_html/path/to/rtk_web/scripts/
   php migrate_to_cloudinary.php
   ```
   - Mở **Terminal** trong giao diện cPanel và chạy các lệnh trên.

2. **Sử dụng Cron Jobs** để chạy một lần hoặc theo lịch:
   - Đăng nhập vào cPanel, chọn **Cron Jobs**.
   - Tạo một **Cron Job** mới với thời gian bạn muốn (ví dụ: Once Per Day).
   - Ở mục **Command**, nhập:
     ```bash
     cd /home/<cpanel_user>/public_html/path/to/rtk_web/scripts && php migrate_to_cloudinary.php
     ```
   - Lưu lại. Kết quả chạy sẽ gửi về email cPanel hoặc ghi vào log tùy cấu hình.

## Thay đổi trong code

### Files đã được cập nhật:

1. **`private/config/cloudinary.php`** - Cấu hình Cloudinary
2. **`private/classes/CloudinaryService.php`** - Service xử lý Cloudinary
3. **`private/action/purchase/upload_payment_proof.php`** - Upload logic
4. **`private/classes/purchase/PaymentProofService.php`** - Hiển thị ảnh
5. **`public/assets/js/pages/transaction.js`** - Frontend handling
6. **`public/pages/purchase/upload_proof.php`** - UI hiển thị

### Các tính năng mới:

- **Tự động resize**: Ảnh được resize tối đa 1200x1200px
- **Format optimization**: Tự động chọn format tốt nhất (WebP, AVIF)
- **Quality optimization**: Tự động tối ưu chất lượng
- **Multiple sizes**: Hỗ trợ thumbnail, medium, full size
- **Metadata**: Lưu thông tin registration_id, user_id trong Cloudinary
- **Rollback**: Tự động xóa ảnh Cloudinary nếu database operation thất bại

## Fallback và tương thích

Hệ thống vẫn hỗ trợ hiển thị ảnh cũ từ local storage:

- **Ảnh mới**: Sẽ upload lên Cloudinary
- **Ảnh cũ**: Vẫn hiển thị từ local cho đến khi migration
- **Mixed environment**: Tự động detect và hiển thị đúng source

## Kiểm tra hoạt động

### 1. Test upload mới
1. Upload ảnh minh chứng mới
2. Kiểm tra database: `payment_image_public_id` phải có giá trị
3. Kiểm tra URL: phải chứa `cloudinary.com`

### 2. Test hiển thị
1. Vào trang transaction
2. Click xem ảnh minh chứng
3. Ảnh phải load từ Cloudinary CDN

### 3. Kiểm tra log
```bash
tail -f private/logs/error.log
```

## Troubleshooting

### Lỗi "Cloudinary is not configured"
- Kiểm tra file `.env` có đầy đủ 3 thông tin Cloudinary
- Kiểm tra vendor/autoload.php đã tồn tại
- Chạy `composer install` để cài đặt dependencies

### Upload thất bại
- Kiểm tra kết nối internet
- Kiểm tra API credentials
- Kiểm tra file size (limit 15MB)
- Xem error log

### Ảnh không hiển thị
- Kiểm tra URL trong database
- Kiểm tra Cloudinary account status
- Kiểm tra browser console errors

## Giới hạn và cân nhắc

### Cloudinary Free Plan:
- **Storage**: 25GB
- **Bandwidth**: 25GB/month
- **Transformations**: 25,000/month

### Để nâng cấp:
- Monitor usage trong Cloudinary dashboard
- Nâng cấp plan khi cần thiết
- Cân nhắc sử dụng Cloudinary optimization để giảm bandwidth

## Backup và restore

### Backup URLs
Tất cả Cloudinary URLs đã được lưu trong database, có thể export:

```sql
SELECT registration_id, payment_image, payment_image_public_id 
FROM transaction_history 
WHERE payment_image_public_id IS NOT NULL;
```

### Restore từ Cloudinary
Nếu cần restore, có thể download từ Cloudinary API hoặc sử dụng Cloudinary Admin API.

## Support

Nếu gặp vấn đề, kiểm tra:
1. Cloudinary documentation: https://cloudinary.com/documentation
2. PHP SDK docs: https://cloudinary.com/documentation/php_integration
3. Error logs trong `/private/logs/`
