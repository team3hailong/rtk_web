# Hệ thống Hỗ trợ - Tài liệu dành cho nhà phát triển

Tài liệu này cung cấp thông tin kỹ thuật chi tiết về hệ thống hỗ trợ của RTK Web, bao gồm cấu trúc, chức năng và chi tiết triển khai.

## Mục lục

1. [Tổng quan](#tổng-quan)
2. [Cấu trúc cơ sở dữ liệu](#cấu-trúc-cơ-sở-dữ-liệu)
3. [Tổ chức mã nguồn](#tổ-chức-mã-nguồn)
4. [Luồng xử lý](#luồng-xử-lý)
5. [Xử lý lỗi](#xử-lý-lỗi)
6. [Phát triển trong tương lai](#phát-triển-trong-tương-lai)

## Tổng quan

Hệ thống hỗ trợ cho phép người dùng tạo và theo dõi các yêu cầu hỗ trợ. Hệ thống bao gồm:

1. **Trang liên hệ hỗ trợ (`contact.php`)**: Cho phép người dùng gửi yêu cầu hỗ trợ và xem các yêu cầu trước đó
2. **Xử lý phía máy chủ (`process_support_request.php`)**: Xử lý việc tạo và lưu trữ các yêu cầu hỗ trợ
3. **Lớp hỗ trợ (`SupportRequest.php`)**: Đóng gói logic nghiệp vụ cho các hoạt động hỗ trợ

Tính năng chính bao gồm:
- Gửi biểu mẫu yêu cầu hỗ trợ
- Theo dõi lịch sử yêu cầu
- Cập nhật trạng thái
- Hiển thị thông tin liên hệ công ty
- Xem chi tiết yêu cầu qua modal

## Cấu trúc cơ sở dữ liệu

Hệ thống hỗ trợ dựa vào hai bảng chính:

### 1. Bảng `support_requests`

```sql
CREATE TABLE `support_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('technical','billing','account','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `status` enum('pending','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_support_requests_user_id` (`user_id`),
  KEY `idx_support_requests_status` (`status`),
  CONSTRAINT `fk_support_requests_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Bảng `company_info`

```sql
CREATE TABLE `company_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci,
  `tax_code` varchar(50) COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `working_hours` varchar(255) COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Bảng liên quan bổ sung

```sql
-- Nhật ký hoạt động cho các hành động hỗ trợ
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `notify_content` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  KEY `idx_activity_logs_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Tổ chức mã nguồn

Hệ thống hỗ trợ được tổ chức thành các thành phần sau:

### 1. Lớp SupportRequest (`private/classes/SupportRequest.php`)

Lớp này đóng gói logic nghiệp vụ cho hệ thống hỗ trợ:

- **Các phương thức chính**:
  - `createRequest()`: Tạo một yêu cầu hỗ trợ mới
  - `getRequestsByUser()`: Lấy các yêu cầu hỗ trợ cho một người dùng cụ thể
  - `getCompanyInfo()`: Lấy thông tin liên hệ của công ty
  - `logSupportActivity()`: Ghi lại các hoạt động liên quan đến hỗ trợ

### 2. Trang liên hệ (`public/pages/support/contact.php`)

Giao diện người dùng cho hệ thống hỗ trợ:

- **Các phần chính**:
  - Biểu mẫu yêu cầu hỗ trợ
  - Hiển thị thông tin công ty
  - Bảng các yêu cầu hỗ trợ trước đó
  - Modal chi tiết yêu cầu

### 3. Xử lý yêu cầu (`private/action/support/process_support_request.php`)

Xử lý gửi biểu mẫu và xử lý yêu cầu:

- **Chức năng chính**:
  - Xác thực dữ liệu biểu mẫu
  - Tạo yêu cầu hỗ trợ thông qua lớp SupportRequest
  - Xử lý lỗi và thông báo phiên
  - Chuyển hướng trở lại trang liên hệ

### 4. CSS và JavaScript

- `public/assets/css/pages/support/contact.css`: Định kiểu cho trang liên hệ hỗ trợ
- JavaScript nội tuyến trong `contact.php` cho chức năng modal và cập nhật giao diện người dùng động

## Luồng xử lý

### Luồng gửi yêu cầu hỗ trợ

1. Người dùng truy cập trang liên hệ hỗ trợ (`contact.php`)
2. Người dùng điền vào biểu mẫu yêu cầu hỗ trợ với:
   - Tiêu đề
   - Loại (kỹ thuật, thanh toán, tài khoản, khác)
   - Nội dung
3. Người dùng gửi biểu mẫu
4. Gửi biểu mẫu được xử lý bởi `process_support_request.php`:
   - Xác thực đầu vào được thực hiện
   - Yêu cầu hỗ trợ được tạo bằng `SupportRequest::createRequest()`
   - Hoạt động được ghi lại với `logSupportActivity()`
   - Thông báo thành công/lỗi được lưu trong phiên
5. Người dùng được chuyển hướng trở lại trang liên hệ
6. Thông báo thành công/lỗi được hiển thị cho người dùng
7. Yêu cầu mới xuất hiện trong lịch sử yêu cầu của người dùng

### Luồng xem yêu cầu hỗ trợ

1. Người dùng truy cập trang liên hệ hỗ trợ (`contact.php`)
2. Các yêu cầu hỗ trợ trước đó được tải qua `SupportRequest::getRequestsByUser()`
3. Các yêu cầu được hiển thị trong bảng với:
   - Tiêu đề
   - Loại
   - Ngày tạo
   - Trạng thái
   - Nút xem chi tiết
4. Người dùng nhấp vào nút "Xem" cho một yêu cầu cụ thể
5. Cửa sổ modal mở ra hiển thị:
   - Chi tiết yêu cầu (tiêu đề, loại, ngày, trạng thái)
   - Nội dung của người dùng
   - Phản hồi của quản trị viên (nếu có)
6. Người dùng có thể đóng modal và xem các yêu cầu khác

## Xử lý lỗi

Hệ thống hỗ trợ xử lý các điều kiện lỗi sau:

1. **Lỗi xác thực biểu mẫu**:
   - Tiêu đề hoặc nội dung trống
   - Loại không hợp lệ
   - Giải pháp: Hiển thị thông báo lỗi cụ thể và giữ lại dữ liệu biểu mẫu

2. **Lỗi xác thực**:
   - Người dùng chưa đăng nhập
   - Giải pháp: Chuyển hướng đến trang đăng nhập với thông báo thích hợp

3. **Lỗi cơ sở dữ liệu**:
   - Vấn đề kết nối
   - Lỗi truy vấn
   - Giải pháp: Ghi lại lỗi với `error_log()` và hiển thị thông báo thân thiện với người dùng

4. **Bảo vệ CSRF**:
   - Token CSRF không hợp lệ hoặc bị thiếu
   - Giải pháp: Được xử lý bởi `csrf_helper.php` và trình xử lý hành động

5. **Hiển thị thông báo lỗi**:
   - Thông báo được lưu trong biến phiên
   - Tự động ẩn sau 5 giây
   - Phân biệt rõ ràng về kiểu dáng giữa lỗi và thông báo thành công

## Phát triển trong tương lai

Các cải tiến tiềm năng cho hệ thống hỗ trợ:

1. **Tăng cường giao tiếp**:
   - Tích hợp hỗ trợ trò chuyện thời gian thực
   - Thông báo email về cập nhật trạng thái
   - Hỗ trợ đính kèm tệp cho các yêu cầu

2. **Cải thiện trải nghiệm người dùng**:
   - Ưu tiên yêu cầu hỗ trợ
   - Chỉ báo thời gian phản hồi dự kiến
   - Hệ thống đánh giá sự hài lòng cho các yêu cầu đã đóng
   - Mẫu yêu cầu hỗ trợ cho các vấn đề phổ biến

3. **Cải tiến giao diện quản trị**:
   - Hệ thống phản hồi quản trị tích hợp
   - Ghi chú nội bộ cho nhân viên hỗ trợ
   - Gán yêu cầu cho nhân viên cụ thể
   - Số liệu hiệu suất và báo cáo

4. **Tích hợp cơ sở kiến thức**:
   - Tự động đề xuất bài viết hướng dẫn dựa trên nội dung yêu cầu
   - Chuyển đổi các yêu cầu hỗ trợ phổ biến thành bài viết hướng dẫn
   - Liên kết các bài viết hướng dẫn liên quan với yêu cầu hỗ trợ

5. **Tối ưu hóa cho thiết bị di động**:
   - Cải thiện thiết kế đáp ứng
   - Tích hợp ứng dụng di động gốc
   - Thông báo đẩy cho cập nhật trạng thái

This document provides technical details about the RTK Web's support system, including its structure, functionality, and implementation details.

## Table of Contents

1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [Code Organization](#code-organization)
4. [Process Flows](#process-flows)
5. [Error Handling](#error-handling)
6. [Future Development](#future-development)

## Overview

The support system enables users to create and track support requests. The system consists of:

1. **Support Contact Page (`contact.php`)**: Allows users to submit support requests and view past requests
2. **Backend Processing (`process_support_request.php`)**: Handles the creation and storage of support requests
3. **Support Class (`SupportRequest.php`)**: Encapsulates the business logic for support operations

Key features include:
- Support request form submission
- Request history tracking
- Status updates
- Company contact information display
- Modal view for request details

## Database Structure

The support system relies on two primary tables:

### 1. `support_requests` Table

```sql
CREATE TABLE `support_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('technical','billing','account','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `status` enum('pending','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_support_requests_user_id` (`user_id`),
  KEY `idx_support_requests_status` (`status`),
  CONSTRAINT `fk_support_requests_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. `company_info` Table

```sql
CREATE TABLE `company_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci,
  `tax_code` varchar(50) COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `working_hours` varchar(255) COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Additional Related Table

```sql
-- Activity logs for support actions
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `notify_content` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  KEY `idx_activity_logs_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Code Organization

The support system is organized into the following components:

### 1. SupportRequest Class (`private/classes/SupportRequest.php`)

This class encapsulates the business logic for the support system:

- **Key Methods**:
  - `createRequest()`: Creates a new support request
  - `getRequestsByUser()`: Retrieves support requests for a specific user
  - `getCompanyInfo()`: Retrieves company contact information
  - `logSupportActivity()`: Logs support-related activities

### 2. Contact Page (`public/pages/support/contact.php`)

Frontend interface for the support system:

- **Main Sections**:
  - Support request form
  - Company information display
  - Previous support requests table
  - Request details modal

### 3. Request Processing (`private/action/support/process_support_request.php`)

Handles form submission and request processing:

- **Key Functions**:
  - Form data validation
  - Support request creation through the SupportRequest class
  - Error handling and session messaging
  - Redirection back to the contact page

### 4. CSS and JavaScript

- `public/assets/css/pages/support/contact.css`: Styling for the support contact page
- Inline JavaScript in `contact.php` for modal functionality and dynamic UI updates

## Process Flows

### Support Request Submission Flow

1. User navigates to the support contact page (`contact.php`)
2. User fills out the support request form with:
   - Subject
   - Category (technical, billing, account, other)
   - Message
3. User submits the form
4. Form submission is processed by `process_support_request.php`:
   - Input validation is performed
   - Support request is created using `SupportRequest::createRequest()`
   - Activity is logged with `logSupportActivity()`
   - Success/error message is stored in session
5. User is redirected back to contact page
6. Success/error message is displayed to the user
7. New request appears in the user's request history

### Support Request Viewing Flow

1. User navigates to the support contact page (`contact.php`)
2. Previous support requests are loaded via `SupportRequest::getRequestsByUser()`
3. Requests are displayed in a table with:
   - Subject
   - Category
   - Creation date
   - Status
   - View details button
4. User clicks "View" button for a specific request
5. Modal window opens showing:
   - Request details (subject, category, date, status)
   - User's message
   - Admin response (if any)
6. User can close the modal and view other requests

## Error Handling

The support system handles the following error conditions:

1. **Form Validation Errors**:
   - Empty subject or message
   - Invalid category
   - Solution: Display specific error messages and retain form data

2. **Authentication Errors**:
   - User not logged in
   - Solution: Redirect to login page with appropriate message

3. **Database Errors**:
   - Connection issues
   - Query failures
   - Solution: Log errors with `error_log()` and display user-friendly messages

4. **CSRF Protection**:
   - Invalid or missing CSRF token
   - Solution: Handled by `csrf_helper.php` and action handler

5. **Error Message Display**:
   - Messages stored in session variables
   - Auto-dismissing after 5 seconds
   - Clear styling distinction between errors and success messages

## Future Development

Potential improvements for the support system:

1. **Enhanced Communication**:
   - Real-time chat support integration
   - Email notifications for status updates
   - File attachment support for requests

2. **User Experience Improvements**:
   - Support request prioritization
   - Expected response time indicators
   - Satisfaction rating system for closed requests
   - Support request templates for common issues

3. **Admin Interface Enhancements**:
   - Integrated admin response system
   - Internal notes for support agents
   - Request assignment to specific staff members
   - Performance metrics and reporting

4. **Knowledge Base Integration**:
   - Auto-suggest guide articles based on request content
   - Convert common support requests into guide articles
   - Link related guide articles to support requests

5. **Mobile Optimization**:
   - Responsive design improvements
   - Native mobile app integration
   - Push notifications for status updates
