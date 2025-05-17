# Hệ thống Cài đặt - Tài liệu dành cho nhà phát triển

Tài liệu này cung cấp thông tin kỹ thuật chi tiết về hệ thống cài đặt của RTK Web, bao gồm cấu trúc, chức năng và chi tiết triển khai.

## Mục lục

1. [Tổng quan](#tổng-quan)
2. [Cấu trúc cơ sở dữ liệu](#cấu-trúc-cơ-sở-dữ-liệu)
3. [Tổ chức mã nguồn](#tổ-chức-mã-nguồn)
4. [Luồng xử lý](#luồng-xử-lý)
5. [Xử lý lỗi](#xử-lý-lỗi)
6. [Phát triển trong tương lai](#phát-triển-trong-tương-lai)

## Tổng quan

Hệ thống cài đặt của RTK Web cung cấp giao diện để người dùng quản lý thông tin cá nhân, mật khẩu và thông tin xuất hóa đơn. Hệ thống bao gồm hai trang chính:

1. **Cài đặt hồ sơ (`profile.php`)**: Cho phép người dùng cập nhật thông tin cá nhân và thay đổi mật khẩu
2. **Cài đặt hóa đơn (`invoice.php`)**: Cho phép người dùng cấu hình thông tin xuất hóa đơn cho các giao dịch

Tính năng chính bao gồm:
- Cập nhật thông tin hồ sơ người dùng (tên, email, số điện thoại)
- Thay đổi mật khẩu với xác thực mật khẩu hiện tại
- Cập nhật thông tin xuất hóa đơn (tên công ty, mã số thuế, địa chỉ)
- Ghi nhật ký hoạt động thông qua bảng `activity_logs`

## Cấu trúc cơ sở dữ liệu

Hệ thống cài đặt chủ yếu sử dụng bảng `user` trong cơ sở dữ liệu và ghi nhật ký thông qua bảng `activity_logs`. Dưới đây là mô tả cấu trúc bảng liên quan:

### 1. Bảng `user`

Bảng này chứa tất cả thông tin người dùng bao gồm cả dữ liệu cài đặt:

```sql
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_company` tinyint(1) DEFAULT '0',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Các trường quan trọng liên quan đến cài đặt:
- `username`: Tên người dùng hoặc tên công ty
- `email`: Địa chỉ email dùng để đăng nhập và liên hệ
- `password`: Mật khẩu đã được hash
- `phone`: Số điện thoại liên hệ
- `is_company`: Đánh dấu nếu tài khoản là tài khoản doanh nghiệp
- `company_name`: Tên công ty cho mục đích xuất hóa đơn
- `tax_code`: Mã số thuế của công ty
- `company_address`: Địa chỉ công ty cho mục đích xuất hóa đơn

### 2. Bảng `activity_logs`

Bảng này được sử dụng để ghi lại các hoạt động cài đặt:

```sql
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `new_values` text COLLATE utf8mb4_unicode_ci,
  `notify_content` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  KEY `idx_activity_logs_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Trường quan trọng liên quan đến cài đặt:
- `action`: Loại hành động (update, update_invoice_info, etc.)
- `entity_type`: Luôn là 'user' cho các tương tác cài đặt
- `entity_id`: ID của người dùng
- `new_values`: JSON chứa chi tiết các giá trị được cập nhật
- `notify_content`: Mô tả ngắn gọn về hành động đã thực hiện

## Tổ chức mã nguồn

Hệ thống cài đặt được tổ chức thành các file sau:

### 1. Trang cài đặt hồ sơ (`public/pages/setting/profile.php`)

- **Chức năng chính**: Hiển thị biểu mẫu để cập nhật thông tin hồ sơ và đổi mật khẩu
- **Các phần mã chính**:
  - Kiểm tra xác thực người dùng
  - Fetch dữ liệu người dùng hiện tại
  - Biểu mẫu cập nhật hồ sơ với CSRF protection
  - Biểu mẫu đổi mật khẩu với CSRF protection
  - Hiển thị thông báo lỗi/thành công từ session

### 2. Trang cài đặt hóa đơn (`public/pages/setting/invoice.php`)

- **Chức năng chính**: Hiển thị biểu mẫu để cập nhật thông tin xuất hóa đơn
- **Các phần mã chính**:
  - Kiểm tra xác thực người dùng
  - Fetch thông tin hóa đơn hiện tại
  - Biểu mẫu cập nhật thông tin xuất hóa đơn với CSRF protection
  - Xác thực client-side cho định dạng mã số thuế
  - Hiển thị thông báo lỗi/thành công từ session

### 3. Xử lý cập nhật hồ sơ (`private/action/setting/process_profile_update.php`)

- **Chức năng chính**: Xử lý cập nhật thông tin hồ sơ người dùng
- **Các phần mã chính**:
  - Validating input và bảo mật (unique email, phone)
  - Cập nhật thông tin người dùng trong cơ sở dữ liệu
  - Ghi log hoạt động
  - Xử lý lỗi và thông báo phản hồi

### 4. Xử lý đổi mật khẩu (`private/action/setting/process_password_change.php`)

- **Chức năng chính**: Xử lý thay đổi mật khẩu người dùng
- **Các phần mã chính**:
  - Validating input (password length, matching)
  - Xác thực mật khẩu hiện tại
  - Hashing mật khẩu mới
  - Cập nhật mật khẩu trong cơ sở dữ liệu
  - Xử lý lỗi và thông báo phản hồi

### 5. Xử lý cập nhật hóa đơn (`private/action/setting/process_invoice_update.php`)

- **Chức năng chính**: Xử lý cập nhật thông tin xuất hóa đơn
- **Các phần mã chính**:
  - Validating input (tax code format)
  - Cập nhật thông tin hóa đơn trong cơ sở dữ liệu
  - Ghi log hoạt động
  - Xử lý lỗi và thông báo phản hồi

### 6. CSS và JavaScript

- `public/assets/css/pages/settings/profile.css`: Định dạng cho trang cài đặt hồ sơ
- `public/assets/css/pages/settings/invoice.css`: Định dạng cho trang cài đặt hóa đơn
- `public/assets/js/pages/profile.js`: JavaScript cho chức năng trang cài đặt hồ sơ

## Luồng xử lý

### Luồng cập nhật hồ sơ

1. Người dùng truy cập `profile.php`
2. Hệ thống kiểm tra xác thực và fetch dữ liệu người dùng hiện tại
3. Người dùng điền vào biểu mẫu cập nhật và nhấn "Cập nhật hồ sơ"
4. Biểu mẫu được gửi đến `process_profile_update.php` với CSRF token
5. Server thực hiện các kiểm tra:
   - Validation cơ bản (required fields, email format)
   - Kiểm tra email và số điện thoại trùng lặp
6. Nếu validation thành công:
   - Thông tin người dùng được cập nhật trong cơ sở dữ liệu
   - Hoạt động được ghi lại trong bảng `activity_logs`
   - Dữ liệu session được cập nhật với thông tin mới
   - Người dùng được chuyển hướng về `profile.php` với thông báo thành công
7. Nếu validation thất bại:
   - Người dùng được chuyển hướng về `profile.php` với thông báo lỗi

### Luồng đổi mật khẩu

1. Người dùng truy cập `profile.php` và điền vào biểu mẫu đổi mật khẩu
2. Người dùng cung cấp mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu
3. Biểu mẫu được gửi đến `process_password_change.php` với CSRF token
4. Server thực hiện các kiểm tra:
   - Validation cơ bản (required fields, password length)
   - Mật khẩu mới và xác nhận khớp nhau
   - Xác thực mật khẩu hiện tại
5. Nếu validation thành công:
   - Mật khẩu mới được hash và lưu vào cơ sở dữ liệu
   - Người dùng được chuyển hướng về `profile.php` với thông báo thành công
6. Nếu validation thất bại:
   - Người dùng được chuyển hướng về `profile.php` với thông báo lỗi

### Luồng cập nhật thông tin hóa đơn

1. Người dùng truy cập `invoice.php`
2. Hệ thống kiểm tra xác thực và fetch thông tin hóa đơn hiện tại
3. Người dùng điền vào biểu mẫu thông tin hóa đơn và nhấn "Cập nhật thông tin"
4. JavaScript client-side thực hiện validation ban đầu
5. Biểu mẫu được gửi đến `process_invoice_update.php` với CSRF token
6. Server thực hiện các kiểm tra:
   - Validation cơ bản (company name required if tax code provided)
   - Validation định dạng mã số thuế
7. Nếu validation thành công:
   - Thông tin hóa đơn được cập nhật trong cơ sở dữ liệu
   - Hoạt động được ghi lại trong bảng `activity_logs`
   - Người dùng được chuyển hướng về `invoice.php` với thông báo thành công
8. Nếu validation thất bại:
   - Người dùng được chuyển hướng về `invoice.php` với thông báo lỗi

## Xử lý lỗi

Hệ thống cài đặt xử lý các loại lỗi sau:

### 1. Lỗi xác thực người dùng

- **Vấn đề**: Người dùng không đăng nhập hoặc phiên hết hạn
- **Xử lý**: Chuyển hướng đến trang đăng nhập với thông báo phù hợp
- **Mã nguồn**:
  ```php
  if (!isset($_SESSION['user_id'])) {
      header('Location: ' . $base_url . '/public/pages/auth/login.php');
      exit;
  }
  ```

### 2. Lỗi dữ liệu đầu vào

- **Vấn đề**: Dữ liệu không hợp lệ (email sai định dạng, mật khẩu quá ngắn)
- **Xử lý**: Validation, tạo thông báo lỗi và hiển thị lại form
- **Mã nguồn**:
  ```php
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Địa chỉ email không hợp lệ.";
  }
  ```

### 3. Lỗi dữ liệu trùng lặp

- **Vấn đề**: Email hoặc số điện thoại đã được sử dụng
- **Xử lý**: Kiểm tra trước khi cập nhật và thông báo lỗi cụ thể
- **Mã nguồn**:
  ```php
  $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
  $stmt->execute([$email, $user_id]);
  if ($stmt->fetch()) {
      throw new Exception("Địa chỉ email này đã được sử dụng bởi tài khoản khác.");
  }
  ```

### 4. Lỗi xác thực mật khẩu

- **Vấn đề**: Mật khẩu hiện tại không chính xác
- **Xử lý**: Xác minh mật khẩu và thông báo lỗi cụ thể
- **Mã nguồn**:
  ```php
  if (!password_verify($current_password, $user_data['password'])) {
      $_SESSION['profile_error'] = "Mật khẩu hiện tại không chính xác.";
      header('Location: ' . $profile_page_url);
      exit;
  }
  ```

### 5. Lỗi truy vấn cơ sở dữ liệu

- **Vấn đề**: Lỗi kết nối hoặc thất bại khi thực hiện truy vấn
- **Xử lý**: Try-catch khối, ghi log lỗi chi tiết, hiển thị thông báo lỗi thân thiện
- **Mã nguồn**:
  ```php
  try {
      // Database operations
  } catch (PDOException $e) {
      error_log("Profile update PDO error: " . $e->getMessage());
      $_SESSION['profile_error'] = "Có lỗi xảy ra khi cập nhật hồ sơ (DB).";
  }
  ```

## Phát triển trong tương lai

Các cải tiến tiềm năng cho hệ thống cài đặt:

1. **Nâng cao bảo mật**:
   - Thêm xác thực hai yếu tố (2FA)
   - Quản lý các phiên đăng nhập và thiết bị
   - Lịch sử đăng nhập với chi tiết vị trí/thiết bị

2. **Quản lý hồ sơ nâng cao**:
   - Tải lên và quản lý ảnh đại diện
   - Tích hợp với mạng xã hội
   - Cài đặt thông báo và email

3. **Cài đặt riêng tư và chia sẻ**:
   - Kiểm soát dữ liệu nào được chia sẻ với ứng dụng/người dùng khác
   - Xuất và tải xuống dữ liệu người dùng
   - Tùy chọn xóa tài khoản

4. **Cài đặt hóa đơn nâng cao**:
   - Quản lý nhiều hồ sơ công ty/cá nhân cho hóa đơn
   - Tải lên logo công ty cho hóa đơn
   - Mẫu hóa đơn tùy chỉnh

5. **Cài đặt giao diện người dùng**:
   - Tùy chọn chủ đề tối/sáng
   - Tùy chỉnh bố cục trang chủ
   - Ngôn ngữ hệ thống

6. **Cài đặt thông báo**:
   - Tùy chọn thông báo theo kênh (email, đẩy, SMS)
   - Tần suất thông báo
   - Đăng ký/hủy đăng ký thông báo cụ thể
