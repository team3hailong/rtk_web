<?php
/**
 * Session Middleware - Kiểm tra và quản lý tính hợp lệ của session
 * 
 * File này cung cấp các hàm để kiểm tra thời gian hoạt động của session
 * và tự động đăng xuất nếu session hết hạn.
 */

/**
 * Khởi tạo session với thời gian hết hạn được cấu hình
 * Gọi hàm này thay thế cho session_start() ở các trang
 */
function init_session() {
    // Cấu hình thời gian session (2 giờ = 7200 giây)
    ini_set('session.gc_maxlifetime', 7200);
    session_set_cookie_params(7200);
    
    // Khởi tạo session nếu chưa được khởi tạo
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Kiểm tra và xác thực session
    verify_session();
}

/**
 * Kiểm tra tính hợp lệ của session và tự động đăng xuất nếu hết hạn
 */
function verify_session() {
    // Bỏ qua kiểm tra nếu không có session user_id (chưa đăng nhập)
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    // Lần đầu tiên truy cập sau khi đăng nhập, thiết lập thời gian hoạt động
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }
    
    // Thời gian không hoạt động tối đa (30 phút = 1800 giây)
    $inactive_timeout = 1800; 
    
    // Tính thời gian không hoạt động
    $inactive_time = time() - $_SESSION['last_activity'];
    
    // Nếu thời gian không hoạt động vượt quá giới hạn, đăng xuất
    if ($inactive_time > $inactive_timeout) {
        // Lưu lại URL hiện tại để chuyển hướng sau khi đăng nhập
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $_SESSION['redirect_after_login'] = $current_url;
          // Lưu thông báo đăng xuất tự động
        $_SESSION['login_message'] = "Phiên làm việc của bạn đã hết hạn. Vui lòng đăng nhập lại.";
        
        // Tạo đường dẫn đến handler đăng xuất thay vì trang logout trung gian
        $logout_url = "/public/handlers/action_handler.php?module=auth&action=process_logout";
        
        // Chuyển hướng đến trang đăng xuất
        header("Location: " . $logout_url);
        exit();
    }
    
    // Cập nhật thời gian hoạt động mới nhất
    $_SESSION['last_activity'] = time();
}

/**
 * Làm mới session và cập nhật thời gian hoạt động
 * Gọi hàm này khi người dùng thực hiện các hành động quan trọng
 */
function refresh_session() {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}
