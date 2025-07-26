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
    // Chỉ đặt cấu hình session khi chưa được khởi tạo
    if (session_status() === PHP_SESSION_NONE) {
        // Cấu hình thời gian session (2 giờ = 7200 giây)
        ini_set('session.gc_maxlifetime', 7200);
        session_set_cookie_params(7200);
        
        // Khởi tạo session
        session_start();
    }
    
    // Kiểm tra "Remember Me" cookie nếu người dùng chưa đăng nhập
    if (!isset($_SESSION['user_id'])) {
        check_remember_me();
    }
    
    // Kiểm tra và xác thực session
    verify_session();
}

/**
 * Kiểm tra cookie "Remember Me" và tự động đăng nhập nếu hợp lệ
 */
function check_remember_me() {
    // Kiểm tra xem có cookie remember_token không
    if (isset($_COOKIE['remember_token'])) {
        // Parse cookie để lấy user_id và token
        list($user_id, $token) = explode(':', $_COOKIE['remember_token'], 2);
        
        // Nếu có user_id và token hợp lệ
        if ($user_id && $token) {
            require_once __DIR__ . '/../config/database.php';
            
            // Chuẩn bị câu lệnh để lấy token từ database
            $stmt = $conn->prepare("
                SELECT u.id, u.username, u.email, rt.token 
                FROM remember_tokens rt
                JOIN user u ON rt.user_id = u.id
                WHERE rt.user_id = ? AND rt.expiry > NOW()
            ");
            
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    
                    // Xác thực token
                    if (password_verify($token, $row['token'])) {
                        // Đăng nhập tự động
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['last_activity'] = time();
                        
                        // Lưu log hoạt động
                        try {
                            // Lưu log hoạt động đăng nhập tự động
                            require_once __DIR__ . '/../utils/error_handler.php';
                            $notify_content = 'Người dùng ' . $row['username'] . ' đã đăng nhập tự động qua "Ghi nhớ đăng nhập"';
                            log_activity($conn, $row['id'], 'auto_login', 'user', $row['id'], null, [
                                'login_time' => date('Y-m-d H:i:s'),
                                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                            ], $notify_content);
                        } catch (Exception $e) {
                            error_log("Error logging auto-login: " . $e->getMessage());
                        }
                        
                        // Tạo token mới và cập nhật cookie (để tăng bảo mật)
                        $new_token = bin2hex(random_bytes(32));
                        $new_hash = password_hash($new_token, PASSWORD_DEFAULT);
                        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        $update_stmt = $conn->prepare("UPDATE remember_tokens SET token = ?, expiry = ? WHERE user_id = ?");
                        $update_stmt->bind_param("ssi", $new_hash, $expiry, $user_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // Cập nhật cookie với token mới
                        setcookie('remember_token', $user_id . ':' . $new_token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
                    }
                }
                
                $stmt->close();
            }
            
            $conn->close();
        }
    }
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
