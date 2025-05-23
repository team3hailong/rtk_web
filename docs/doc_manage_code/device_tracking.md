# Hệ thống Theo dõi Thiết bị và IP

Hệ thống này giúp theo dõi thiết bị và địa chỉ IP của người dùng để ngăn chặn việc lạm dụng gói dùng thử miễn phí.

## Cấu trúc và Thành phần

### 1. Cơ sở dữ liệu

Bảng `user_devices` lưu trữ thông tin về thiết bị và IP của người dùng:

```sql
CREATE TABLE IF NOT EXISTS `user_devices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `device_fingerprint` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login_count` INT DEFAULT 1,
  UNIQUE KEY `unique_device_fingerprint` (`device_fingerprint`),
  KEY `idx_user_devices_ip` (`ip_address`),
  KEY `idx_user_devices_user_id` (`user_id`),
  CONSTRAINT `fk_user_devices_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
);
```

### 2. Các tệp chính

1. **DeviceTracker.php**
   - Class PHP để quản lý thông tin thiết bị và IP
   - Cung cấp phương thức để kiểm tra và lưu thông tin thiết bị

2. **device_fingerprint.js**
   - Script JavaScript để thu thập vân tay thiết bị
   - Thu thập thông tin trình duyệt và tạo vân tay thiết bị duy nhất

3. **packages.php** (đã chỉnh sửa)
   - Kiểm tra vân tay thiết bị trước khi hiển thị gói dùng thử
   - Ẩn gói trial 7 ngày nếu thiết bị hoặc IP đã được sử dụng trước đó

4. **process_login.php** và **process_register.php** (đã chỉnh sửa)
   - Lưu trữ thông tin thiết bị và IP khi người dùng đăng nhập hoặc đăng ký

## Luồng Xử lý

### Thu thập vân tay thiết bị:
1. Script `device_fingerprint.js` được tải trên trang đăng nhập, đăng ký và trang chủ
2. Script thu thập thông tin thiết bị và tạo vân tay duy nhất
3. Vân tay được gửi cùng với form đăng nhập hoặc đăng ký

### Lưu trữ thông tin thiết bị:
1. Khi người dùng đăng nhập hoặc đăng ký, hệ thống lưu thông tin thiết bị và IP vào cơ sở dữ liệu
2. Nếu thiết bị đã tồn tại, cập nhật thông tin và tăng số lần đăng nhập

### Kiểm tra thiết bị khi hiển thị gói:
1. Khi người dùng xem trang packages.php, hệ thống kiểm tra thiết bị và IP
2. Nếu thiết bị hoặc IP đã tồn tại trong cơ sở dữ liệu, gói dùng thử sẽ bị ẩn

## Cài đặt và Triển khai

1. Chạy migration để tạo bảng `user_devices`
2. Đảm bảo các tệp PHP và JavaScript đã được thêm vào đúng vị trí

## Lưu ý

- Hệ thống không hoàn toàn bảo mật vì người dùng có thể thay đổi IP hoặc xóa cookie, nhưng nó giúp giảm thiểu việc lạm dụng gói dùng thử
- Vân tay thiết bị được tạo từ các thuộc tính trình duyệt, canvas và thông tin màn hình
- Lưu ý bảo vệ quyền riêng tư của người dùng khi thu thập và lưu trữ thông tin thiết bị
