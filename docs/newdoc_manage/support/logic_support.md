# Logic xử lý hệ thống hướng dẫn và liên hệ

## 1. Logic hệ thống hướng dẫn (Guide System)

### Quy trình hiển thị danh sách hướng dẫn

1. **Khởi tạo trang danh sách**
   - Khi người dùng truy cập `public/pages/support/guide.php`
   - Hệ thống kiểm tra phiên đăng nhập của người dùng
   - Tạo kết nối database thông qua class `Database`
   - Gọi các hàm helper từ `guide_helper.php`

2. **Xử lý filter và tìm kiếm**
   - Hệ thống lấy các tham số tìm kiếm từ URL (`$_GET['keyword']` và `$_GET['topic']`)
   - Gọi hàm `get_guide_topics()` để lấy danh sách tất cả các chủ đề có trong database
   - Gọi hàm `get_filtered_guide_articles()` để lấy danh sách bài viết phù hợp với filter

3. **Hiển thị danh sách bài viết**
   - Render danh sách sử dụng HTML và CSS từ `guide.css`
   - Hiển thị thông tin tóm tắt của mỗi bài viết: tiêu đề, thumbnail, tóm tắt nội dung
   - Tạo link tới trang chi tiết sử dụng URL parameter `?slug={article_slug}`
   - Hiển thị thông báo khi không có bài viết nào phù hợp với filter

### Quy trình hiển thị chi tiết hướng dẫn

1. **Lấy thông tin bài viết**
   - Khi người dùng truy cập `public/pages/support/guide_detail.php?slug=XXX`
   - Hệ thống lấy parameter `slug` từ URL
   - Thực hiện truy vấn vào database để lấy chi tiết bài viết theo slug

2. **Render nội dung bài viết**
   - Nếu không tìm thấy bài viết, hiển thị thông báo lỗi
   - Nếu tìm thấy, render các thông tin: tiêu đề, chủ đề, ngày đăng, thumbnail
   - Hiển thị nội dung HTML đầy đủ của bài viết
   - Cung cấp link để quay lại danh sách hướng dẫn

3. **Xử lý trường hợp đặc biệt**
   - Kiểm tra trạng thái của bài viết, chỉ hiển thị bài viết có trạng thái "published"
   - Định dạng ngày tháng hiển thị theo định dạng Việt Nam (dd/mm/yyyy)
   - Đảm bảo hiển thị hình ảnh thumbnails với đường dẫn đầy đủ

## 2. Logic hệ thống liên hệ hỗ trợ (Contact System)

### Quy trình gửi yêu cầu hỗ trợ

1. **Khởi tạo trang liên hệ**
   - Khi người dùng truy cập `public/pages/support/contact.php`
   - Hệ thống kiểm tra phiên đăng nhập của người dùng
   - Khởi tạo class `SupportRequest` với kết nối database
   - Lấy danh sách các yêu cầu hỗ trợ trước đó của người dùng qua `getRequestsByUser()`
   - Lấy thông tin công ty từ database qua `getCompanyInfo()`

2. **Xử lý form submission**
   - Người dùng điền thông tin vào form (tiêu đề, loại yêu cầu, nội dung)
   - Form được gửi đến `action_handler.php?module=support&action=process_support_request`
   - Handler xác thực và sanitize dữ liệu đầu vào
   - Kiểm tra CSRF token để bảo mật
   - Gọi method `createRequest()` của class `SupportRequest`
   - Ghi log hoạt động thông qua `logSupportActivity()`

3. **Phản hồi và điều hướng**
   - Nếu tạo yêu cầu thành công, lưu thông báo thành công vào session
   - Nếu có lỗi, lưu thông báo lỗi vào session
   - Chuyển hướng người dùng về trang `contact.php`
   - Hiển thị thông báo flash message dựa trên session

### Quy trình xem lịch sử yêu cầu hỗ trợ

1. **Hiển thị danh sách yêu cầu**
   - Hệ thống lấy danh sách yêu cầu hỗ trợ của user từ database
   - Hiển thị dạng bảng với các cột: Tiêu đề, Loại yêu cầu, Ngày gửi, Trạng thái, Chi tiết
   - Biểu thị trạng thái bằng các class CSS và màu sắc khác nhau
   - Cung cấp nút "Xem" để mở modal chi tiết cho mỗi yêu cầu

2. **Xử lý chi tiết yêu cầu qua Modal**
   - Dữ liệu chi tiết của yêu cầu được encode vào data attribute của nút "Xem"
   - JavaScript trong `contact.js` bắt sự kiện click và mở modal
   - Modal hiển thị đầy đủ thông tin: tiêu đề, loại, ngày gửi, nội dung, trạng thái
   - Nếu có phản hồi từ admin, hiển thị phần phản hồi
   - Cung cấp nút đóng modal

3. **Xử lý trạng thái yêu cầu**
   - Mỗi trạng thái có styling riêng biệt: 
     - Pending: màu vàng
     - In Progress: màu xanh dương
     - Resolved: màu xanh lá
     - Closed: màu xám
   - Hiển thị text tiếng Việt tương ứng với từng trạng thái
   - Hiển thị text tiếng Việt cho các category

## 3. Logic giao diện người dùng (UI/UX)

### Xử lý responsive design

1. **Responsive cho trang Guide**
   - Sử dụng media queries trong CSS để điều chỉnh layout cho các kích thước màn hình
   - Trên màn hình nhỏ, chuyển đổi form tìm kiếm từ ngang sang dọc
   - Điều chỉnh kích thước thumbnail và layout thẻ bài viết

2. **Responsive cho trang Contact**
   - Sử dụng CSS Grid và Flexbox để tạo layout linh hoạt
   - Điều chỉnh cấu trúc form và bảng trên màn hình nhỏ
   - Modal được thiết kế để hiển thị tốt trên cả desktop và mobile

### Xử lý JavaScript tương tác

1. **Character Counter**
   - Theo dõi số ký tự đã nhập trong các input fields
   - Hiển thị số ký tự hiện tại / tối đa
   - Cảnh báo trực quan khi người dùng nhập gần đến giới hạn ký tự

2. **Modal Interaction**
   - Mở modal khi người dùng nhấp vào nút "Xem"
   - Đưa dữ liệu chi tiết yêu cầu vào các trường tương ứng trong modal
   - Đóng modal khi người dùng nhấp vào nút đóng hoặc bên ngoài modal
   - Hiển thị/ẩn phần phản hồi tùy theo dữ liệu có sẵn

3. **Flash Messages**
   - Hiển thị thông báo thành công/lỗi khi người dùng thực hiện hành động
   - Tự động ẩn thông báo sau 5 giây
   - Sử dụng hiệu ứng fade để tăng trải nghiệm người dùng

## 4. Logic bảo mật và xử lý lỗi

### Bảo mật

1. **CSRF Protection**
   - Sử dụng CSRF token trong form để ngăn chặn tấn công CSRF
   - Kiểm tra token trước khi xử lý các request POST
   - Tạo token mới cho mỗi phiên làm việc

2. **Input Validation**
   - Sanitize tất cả đầu vào người dùng trước khi lưu vào database
   - Sử dụng prepared statements để ngăn chặn SQL Injection
   - Giới hạn độ dài các trường input để tránh lạm dụng

3. **Authentication Check**
   - Kiểm tra session người dùng trước khi cho phép truy cập các trang
   - Chuyển hướng về trang đăng nhập nếu chưa xác thực
   - Giới hạn người dùng chỉ có thể xem yêu cầu hỗ trợ của chính họ

### Xử lý lỗi

1. **Database Errors**
   - Bắt các lỗi PDO Exception và ghi log
   - Trả về thông báo lỗi thân thiện với người dùng
   - Duy trì tính nhất quán dữ liệu khi xảy ra lỗi

2. **Empty States**
   - Hiển thị thông báo khi không có bài viết hướng dẫn nào phù hợp
   - Hiển thị thông báo khi người dùng chưa có yêu cầu hỗ trợ nào
   - Xử lý trường hợp bài viết không tồn tại hoặc đã bị ẩn

3. **Client-Side Validation**
   - Sử dụng HTML5 validation attributes (required, maxlength)
   - JavaScript kiểm tra độ dài và định dạng đầu vào
   - Hiển thị thông báo lỗi ngay lập tức cho người dùng
