<?php
/**
 * Cấu hình Session - Quản lý thời gian phiên đăng nhập
 * 
 * File này chứa các hằng số cấu hình cho session lifetime và timeout
 * Thay đổi các giá trị này để điều chỉnh thời gian đăng nhập
 */

// Thời gian tồn tại của session (30 ngày)
// Session cookie sẽ tồn tại trong thời gian này
define('SESSION_LIFETIME', 30 * 24 * 60 * 60); // 2,592,000 giây = 30 ngày

// Thời gian không hoạt động tối đa trước khi tự động đăng xuất (30 ngày)
// Người dùng sẽ bị đăng xuất nếu không có hoạt động nào trong thời gian này
define('SESSION_INACTIVE_TIMEOUT', 30 * 24 * 60 * 60); // 2,592,000 giây = 30 ngày

// Thời gian tồn tại của "Remember Me" token (90 ngày)
define('REMEMBER_ME_DURATION', 90 * 24 * 60 * 60); // 7,776,000 giây = 90 ngày

/**
 * GHI CHÚ:
 * 
 * 1. SESSION_LIFETIME: Xác định thời gian tối đa mà session có thể tồn tại
 *    - Sau thời gian này, session sẽ bị xóa bởi garbage collector của PHP
 *    - Nên đặt giá trị này lớn hơn SESSION_INACTIVE_TIMEOUT
 * 
 * 2. SESSION_INACTIVE_TIMEOUT: Xác định thời gian không hoạt động trước khi logout
 *    - Nếu người dùng không có hoạt động nào trong thời gian này, họ sẽ bị đăng xuất
 *    - Thời gian được tính từ lần hoạt động cuối cùng
 * 
 * 3. REMEMBER_ME_DURATION: Thời gian tồn tại của cookie "Remember Me"
 *    - Cho phép người dùng tự động đăng nhập lại khi quay lại trang web
 *    - Token được lưu trong database và cookie
 * 
 * CÁC GIÁ TRỊ ĐỀ XUẤT:
 * - Development: SESSION_LIFETIME = 7 days, INACTIVE_TIMEOUT = 2 days
 * - Production: SESSION_LIFETIME = 30 days, INACTIVE_TIMEOUT = 7 days
 * - High Security: SESSION_LIFETIME = 1 day, INACTIVE_TIMEOUT = 2 hours
 */
