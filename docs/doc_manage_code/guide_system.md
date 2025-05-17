# Hệ thống Hướng dẫn - Tài liệu dành cho nhà phát triển

Tài liệu này cung cấp thông tin kỹ thuật chi tiết về hệ thống hướng dẫn (guide) của RTK Web, bao gồm cấu trúc, chức năng và các chi tiết triển khai.

## Mục lục

1. [Tổng quan](#tổng-quan)
2. [Cấu trúc cơ sở dữ liệu](#cấu-trúc-cơ-sở-dữ-liệu)
3. [Tổ chức mã nguồn](#tổ-chức-mã-nguồn)
4. [Luồng xử lý](#luồng-xử-lý)
5. [Xử lý lỗi](#xử-lý-lỗi)
6. [Phát triển trong tương lai](#phát-triển-trong-tương-lai)

## Tổng quan

Hệ thống hướng dẫn được thiết kế để cung cấp cho người dùng quyền truy cập vào tài liệu, hướng dẫn và các bài viết trợ giúp. Hệ thống bao gồm hai trang chính:

1. **Trang danh sách hướng dẫn (`guide.php`)**: Hiển thị danh sách các bài viết hướng dẫn có thể tìm kiếm và lọc
2. **Trang chi tiết hướng dẫn (`guide_detail.php`)**: Hiển thị nội dung đầy đủ của một bài viết hướng dẫn được chọn

Các tính năng chính bao gồm:
- Danh sách bài viết với hình thu nhỏ và tóm tắt
- Lọc theo chủ đề
- Tìm kiếm theo từ khóa
- Xem bài viết đầy đủ

## Cấu trúc cơ sở dữ liệu

Hệ thống hướng dẫn sử dụng bảng `guide` trong cơ sở dữ liệu. Mặc dù file migration chính thức không được tìm thấy trong mã nguồn, cấu trúc bảng có thể được suy ra từ mã:

```sql
CREATE TABLE `guide` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guide_slug` (`slug`),
  KEY `idx_guide_topic` (`topic`),
  KEY `idx_guide_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Các trường quan trọng:
- **id**: Định danh duy nhất cho bài viết hướng dẫn
- **title**: Tiêu đề bài viết
- **slug**: Phiên bản URL thân thiện của tiêu đề cho SEO và URL sạch
- **content**: Nội dung HTML đầy đủ của bài viết hướng dẫn
- **topic**: Danh mục hoặc chủ đề để lọc
- **thumbnail**: Đường dẫn đến hình thu nhỏ của bài viết
- **status**: Trạng thái xuất bản (nháp, đã xuất bản, đã lưu trữ)
- **created_at**: Thời điểm tạo
- **published_at**: Thời điểm xuất bản
- **updated_at**: Thời điểm cập nhật cuối cùng

## Tổ chức mã nguồn

Hệ thống hướng dẫn chủ yếu bao gồm hai file PHP trong thư mục `public/pages/support/`:

### 1. Danh sách Hướng dẫn (`guide.php`)
- **Chức năng chính**: Liệt kê tất cả hướng dẫn đã được xuất bản với khả năng lọc và tìm kiếm
- **Các phần mã chính**:
  - Truy vấn cơ sở dữ liệu với bộ lọc cho từ khóa và chủ đề
  - Danh sách bài viết với hình thu nhỏ và xem trước nội dung
  - Triển khai biểu mẫu tìm kiếm

### 2. Chi tiết Hướng dẫn (`guide_detail.php`)
- **Chức năng chính**: Hiển thị nội dung đầy đủ của một hướng dẫn được chọn
- **Các phần mã chính**:
  - Lấy bài viết theo slug
  - Hiển thị nội dung đầy đủ với định dạng HTML
  - Điều hướng quay lại danh sách hướng dẫn

### File CSS:
- `public/assets/css/pages/support/guide.css`: Kiểu dáng cho trang danh sách hướng dẫn
- `public/assets/css/pages/support/guide_detail.css`: Kiểu dáng cho trang chi tiết hướng dẫn

## Luồng xử lý

### Luồng xử lý Danh sách Hướng dẫn
1. Người dùng điều hướng đến `/public/pages/support/guide.php`
2. Hệ thống lấy tất cả các bài viết hướng dẫn đã xuất bản từ cơ sở dữ liệu
3. Nếu có bộ lọc được áp dụng (tìm kiếm chủ đề hoặc từ khóa):
   - Hệ thống sửa đổi truy vấn để bao gồm điều kiện lọc
   - Kết quả được lọc dựa trên chủ đề và/hoặc từ khóa
4. Danh sách các bài viết đã lọc được hiển thị với hình thu nhỏ và đoạn trích
5. Người dùng có thể nhấp vào một bài viết để xem chi tiết

### Luồng xử lý Chi tiết Hướng dẫn
1. Người dùng nhấp vào một bài viết hướng dẫn hoặc điều hướng trực tiếp đến `/public/pages/support/guide_detail.php?slug=article-slug`
2. Hệ thống lấy bài viết cụ thể theo slug từ cơ sở dữ liệu
3. Nếu bài viết được tìm thấy và đã xuất bản:
   - Nội dung đầy đủ của bài viết được hiển thị với định dạng HTML
   - Metadata (chủ đề, ngày) được hiển thị
4. Nếu bài viết không được tìm thấy hoặc chưa xuất bản:
   - Thông báo lỗi được hiển thị
5. Người dùng có thể điều hướng trở lại danh sách hướng dẫn

## Xử lý lỗi

Hệ thống hướng dẫn xử lý các điều kiện lỗi sau:

1. **Không tìm thấy bài viết**:
   - Trong `guide_detail.php`, khi không tìm thấy bài viết với slug đã chỉ định
   - Hiển thị thông báo: "Không tìm thấy bài viết hoặc bài viết đã bị ẩn"
   - Cung cấp liên kết quay lại danh sách hướng dẫn

2. **Không có kết quả tìm kiếm**:
   - Trong `guide.php`, khi không có bài viết nào phù hợp với tiêu chí tìm kiếm
   - Hiển thị thông báo: "Không có bài viết nào phù hợp"

3. **Làm sạch dữ liệu**:
   - Tham số đầu vào (slug, keyword, topic) được cắt bỏ khoảng trắng và làm sạch
   - Đầu ra được escape đúng cách bằng `htmlspecialchars()` để ngăn chặn tấn công XSS

## Phát triển trong tương lai

Các cải tiến tiềm năng cho hệ thống hướng dẫn:

1. **Quản lý nội dung**:
   - Tạo giao diện quản trị để quản lý các bài viết hướng dẫn
   - Thêm trình soạn thảo WYSIWYG để tạo nội dung
   - Triển khai lịch sử phiên bản cho các bài viết

2. **Nâng cao trải nghiệm người dùng**:
   - Thêm tính năng bài viết liên quan
   - Triển khai hệ thống đánh giá/phản hồi bài viết
   - Thêm chức năng in/xuất PDF

3. **Tổ chức nội dung**:
   - Triển khai danh mục/danh mục con lồng nhau
   - Thêm hệ thống gắn thẻ để khám phá nội dung tốt hơn
   - Tạo mục lục cho các bài viết dài

4. **Hỗ trợ đa phương tiện**:
   - Hỗ trợ nhúng video nâng cao
   - Chức năng thư viện hình ảnh
   - Hướng dẫn tương tác với hướng dẫn từng bước

5. **Tương tác người dùng**:
   - Thêm hệ thống bình luận để người dùng đặt câu hỏi
   - Triển khai cơ chế phản hồi "Điều này có hữu ích không?"
   - Theo dõi các bài viết được xem nhiều nhất/phổ biến nhất
