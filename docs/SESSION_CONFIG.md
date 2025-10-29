# Cấu hình phiên đăng nhập (Session Configuration)

## Tổng quan

Hệ thống đã được cập nhật để kéo dài thời gian phiên đăng nhập, giúp người dùng không bị logout liên tục và phải đăng nhập lại.

## Thay đổi chính

### 1. **Thời gian tồn tại Session**
- **Trước đây**: 2 giờ (7,200 giây)
- **Hiện tại**: **30 ngày** (2,592,000 giây)

### 2. **Thời gian không hoạt động (Inactive Timeout)**
- **Trước đây**: 30 phút (1,800 giây)
- **Hiện tại**: **30 ngày** (2,592,000 giây)

### 3. **Thời gian Remember Me**
- Giữ nguyên: 30 ngày

## Ý nghĩa

### Session Lifetime (Thời gian tồn tại Session)
- Session sẽ tồn tại tối đa **30 ngày** kể từ lần đăng nhập
- Sau 30 ngày, người dùng sẽ phải đăng nhập lại bất kể có hoạt động hay không

### Inactive Timeout (Thời gian không hoạt động)
- Người dùng chỉ bị logout nếu **không có hoạt động nào trong 30 ngày liên tục**
- Mỗi lần truy cập/thao tác sẽ reset lại bộ đếm thời gian
- Nếu trong 30 ngày có bất kỳ hoạt động nào, phiên đăng nhập sẽ được duy trì

### Remember Me
- Nếu người dùng chọn "Ghi nhớ đăng nhập", họ sẽ tự động đăng nhập lại trong vòng 30 ngày
- Ngay cả khi đóng trình duyệt hoặc tắt máy tính

## Lợi ích

1. ✅ **Trải nghiệm người dùng tốt hơn**: Không bị logout giữa chừng khi đang làm việc
2. ✅ **Tiện lợi**: Không phải đăng nhập lại liên tục
3. ✅ **Linh hoạt**: Có thể điều chỉnh thời gian dễ dàng trong file cấu hình
4. ✅ **Bảo mật**: Vẫn giữ được độ bảo mật với timeout 7 ngày không hoạt động

## Điều chỉnh cấu hình

Nếu muốn thay đổi thời gian, chỉnh sửa file:
```
private/config/session_config.php
```

### Các giá trị có thể điều chỉnh:

```php
// Thời gian tồn tại session (mặc định: 30 ngày)
define('SESSION_LIFETIME', 30 * 24 * 60 * 60);

// Thời gian không hoạt động trước khi logout (mặc định: 7 ngày)
define('SESSION_INACTIVE_TIMEOUT', 7 * 24 * 60 * 60);

// Thời gian tồn tại Remember Me token (mặc định: 30 ngày)
define('REMEMBER_ME_DURATION', 30 * 24 * 60 * 60);
```

## Các trường hợp sử dụng đề xuất

### Development (Phát triển)
```php
define('SESSION_LIFETIME', 7 * 24 * 60 * 60);      // 7 ngày
define('SESSION_INACTIVE_TIMEOUT', 2 * 24 * 60 * 60); // 2 ngày
```

### Production (Sản xuất) - **Đang áp dụng**
```php
define('SESSION_LIFETIME', 30 * 24 * 60 * 60);     // 30 ngày
define('SESSION_INACTIVE_TIMEOUT', 30 * 24 * 60 * 60);  // 30 ngày
```

### High Security (Bảo mật cao)
```php
define('SESSION_LIFETIME', 1 * 24 * 60 * 60);      // 1 ngày
define('SESSION_INACTIVE_TIMEOUT', 2 * 60 * 60);   // 2 giờ
```

## Bảo mật

Hệ thống đã được cấu hình với các biện pháp bảo mật:

1. **HttpOnly Cookie**: Cookie không thể truy cập từ JavaScript (chống XSS)
2. **SameSite Policy**: Bảo vệ chống CSRF attacks
3. **Token Hashing**: Remember Me token được hash trước khi lưu database
4. **Session Regeneration**: Session ID được tạo mới sau khi đăng nhập thành công

## Kiểm tra

Sau khi cập nhật, bạn có thể kiểm tra:

1. Đăng nhập vào hệ thống
2. Để máy không hoạt động vài giờ
3. Quay lại và kiểm tra - bạn vẫn đăng nhập
4. Chỉ logout nếu không hoạt động trong 30 ngày

## Files đã cập nhật

1. `private/config/session_config.php` - File cấu hình mới
2. `private/utils/session_middleware.php` - Middleware quản lý session
3. `private/action/auth/process_login.php` - Xử lý đăng nhập

## Ngày cập nhật

29/10/2025
