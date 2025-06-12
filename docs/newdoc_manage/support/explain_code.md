# Giải thích cấu trúc code hệ thống hướng dẫn và liên hệ hỗ trợ

## Cấu trúc tổng quan

Hệ thống hướng dẫn và liên hệ hỗ trợ được thiết kế theo mô hình tách biệt UI và xử lý logic:

1. **UI (User Interface)**: 
   - `public/pages/support/guide.php`: Hiển thị danh sách bài viết hướng dẫn
   - `public/pages/support/guide_detail.php`: Hiển thị chi tiết một bài viết hướng dẫn
   - `public/pages/support/contact.php`: Giao diện gửi yêu cầu hỗ trợ và xem lịch sử

2. **Logic xử lý (Backend)**:
   - `private/utils/guide_helper.php`: Các hàm xử lý dữ liệu cho hệ thống hướng dẫn
   - `private/classes/SupportRequest.php`: Class xử lý yêu cầu hỗ trợ
   - `private/action/support/process_support_request.php`: Xử lý form gửi yêu cầu hỗ trợ

3. **CSS và JavaScript**:
   - `public/assets/css/pages/support/guide.css`: CSS cho trang danh sách hướng dẫn
   - `public/assets/css/pages/support/guide_detail.css`: CSS cho trang chi tiết hướng dẫn
   - `public/assets/css/pages/support/contact.css`: CSS cho trang liên hệ hỗ trợ
   - `public/assets/js/pages/support/contact.js`: JavaScript cho trang liên hệ hỗ trợ

## Files quan trọng và chức năng

### 1. Hệ thống Hướng dẫn

#### 1.1. Giao diện người dùng

**public/pages/support/guide.php**
- Hiển thị danh sách bài viết hướng dẫn
- Cho phép lọc theo chủ đề và tìm kiếm theo từ khóa
- Hiển thị tóm tắt và hình ảnh thumbnails của từng bài viết

**public/pages/support/guide_detail.php**
- Hiển thị nội dung đầy đủ của một bài viết hướng dẫn
- Hiển thị metadata (tiêu đề, chủ đề, ngày đăng...)
- Chuyển đổi nội dung HTML thành định dạng hiển thị phù hợp

#### 1.2. Helper Functions

**private/utils/guide_helper.php**
- Cung cấp các hàm xử lý dữ liệu cho hệ thống hướng dẫn:
  - `get_guide_topics()`: Lấy danh sách các chủ đề hướng dẫn 
  - `get_filtered_guide_articles()`: Lấy bài viết hướng dẫn theo bộ lọc
  - `get_guide_article_by_slug()`: Lấy chi tiết bài viết theo slug

#### 1.3. CSS và Styling

**public/assets/css/pages/support/guide.css**
- Định dạng cho trang danh sách hướng dẫn
- Thiết kế responsive cho các kích thước màn hình
- Định dạng các thành phần UI (cards, buttons, forms...)

**public/assets/css/pages/support/guide_detail.css**
- Định dạng cho trang chi tiết hướng dẫn
- Hiển thị nội dung HTML với định dạng phù hợp
- Responsive design cho các kích thước màn hình

### 2. Hệ thống Liên hệ Hỗ trợ

#### 2.1. Giao diện người dùng

**public/pages/support/contact.php**
- Form gửi yêu cầu hỗ trợ (tiêu đề, loại yêu cầu, nội dung)
- Hiển thị thông tin công ty (đã bị comment out trong code)
- Bảng danh sách các yêu cầu hỗ trợ trước đó
- Modal hiển thị chi tiết yêu cầu hỗ trợ

#### 2.2. Class Xử lý

**private/classes/SupportRequest.php**
- Quản lý các hoạt động liên quan đến yêu cầu hỗ trợ:
  - `createRequest()`: Tạo yêu cầu hỗ trợ mới
  - `getRequestsByUser()`: Lấy danh sách yêu cầu hỗ trợ theo user ID
  - `getCompanyInfo()`: Lấy thông tin công ty để hiển thị
  - `logSupportActivity()`: Ghi log hoạt động

#### 2.3. Action Handler

**private/action/support/process_support_request.php**
- Xử lý form submit từ trang contact.php
- Kiểm tra tính hợp lệ của dữ liệu đầu vào
- Tạo yêu cầu hỗ trợ mới thông qua SupportRequest class
- Chuyển hướng người dùng và hiển thị thông báo

#### 2.4. CSS và JavaScript

**public/assets/css/pages/support/contact.css**
- Định dạng form liên hệ và bảng yêu cầu hỗ trợ
- Định dạng modal chi tiết yêu cầu
- Responsive design cho các kích thước màn hình

**public/assets/js/pages/support/contact.js**
- Xử lý form validation và char counter
- Xử lý sự kiện hiển thị modal chi tiết
- Auto-hide flash messages
- Cập nhật UI động khi tương tác

## Cơ sở dữ liệu

### 1. Bảng "guide"
- **id**: ID duy nhất của bài viết
- **title**: Tiêu đề bài viết
- **slug**: Slug URL-friendly của bài viết
- **content**: Nội dung HTML của bài viết
- **topic**: Chủ đề của bài viết 
- **thumbnail**: Đường dẫn đến hình thumbnail
- **status**: Trạng thái ('draft', 'published', 'hidden')
- **created_at**: Thời gian tạo bài viết
- **updated_at**: Thời gian cập nhật bài viết
- **published_at**: Thời gian xuất bản bài viết

### 2. Bảng "support_requests"
- **id**: ID duy nhất của yêu cầu hỗ trợ
- **user_id**: ID người dùng gửi yêu cầu
- **subject**: Tiêu đề yêu cầu
- **message**: Nội dung yêu cầu
- **category**: Loại yêu cầu ('technical', 'billing', 'account', 'other')
- **status**: Trạng thái ('pending', 'in_progress', 'resolved', 'closed')
- **admin_response**: Phản hồi từ admin
- **created_at**: Thời gian tạo yêu cầu
- **updated_at**: Thời gian cập nhật yêu cầu

### 3. Bảng "company_info"
- **id**: ID duy nhất
- **name**: Tên công ty
- **description**: Mô tả về công ty
- **address**: Địa chỉ công ty (có thể là JSON hoặc plain text)
- **phone**: Số điện thoại
- **email**: Địa chỉ email
- **website**: Website công ty
- **tax_code**: Mã số thuế
- **working_hours**: Giờ làm việc
- **created_at**: Thời gian tạo thông tin
- **updated_at**: Thời gian cập nhật thông tin

## Xử lý Data Flow

### 1. Luồng xử lý Hướng dẫn
1. Người dùng truy cập `guide.php`
2. System load danh sách các topic từ `get_guide_topics()`
3. System load danh sách bài viết từ `get_filtered_guide_articles()`
4. Người dùng có thể lọc theo chủ đề hoặc tìm kiếm
5. Khi người dùng click vào bài viết, chuyển đến `guide_detail.php?slug=XXX`
6. `guide_detail.php` load chi tiết bài viết từ database theo slug

### 2. Luồng xử lý Liên hệ Hỗ trợ
1. Người dùng truy cập `contact.php`
2. System load danh sách yêu cầu hỗ trợ từ `getRequestsByUser()`
3. System load thông tin công ty từ `getCompanyInfo()`
4. Người dùng điền form yêu cầu hỗ trợ và submit
5. `process_support_request.php` xử lý, gọi `createRequest()`
6. Yêu cầu được lưu vào database và hiển thị thông báo thành công
7. Khi người dùng click "Xem" ở một yêu cầu, modal hiển thị chi tiết

## Tương tác với các hệ thống khác

1. **Tương tác với hệ thống Authentication**: Kiểm tra đăng nhập trước khi cho phép xem hướng dẫn và gửi yêu cầu
2. **Tương tác với hệ thống Logging**: Ghi log các hoạt động liên quan đến yêu cầu hỗ trợ
3. **Tương tác với hệ thống Admin**: Admin phản hồi và cập nhật trạng thái yêu cầu thông qua hệ thống admin
