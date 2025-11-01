<?php
// Load cấu hình session
require_once dirname(__DIR__) . '/config/session_config.php';

/**
 * Session Middleware - Kiểm tra và quản lý tính hợp lệ của session
 * 
 * File này cung cấp các hàm để kiểm tra thời gian hoạt động của session
 * và tự động đăng xuất nếu session hết hạn.
 * 
 * CẤU HÌNH THỜI GIAN SESSION:
 * - Session lifetime: 30 ngày (2,592,000 giây)
 * - Inactive timeout: 7 ngày (604,800 giây)
 * - Remember Me: 30 ngày
 */

/**
 * Khởi tạo session với thời gian hết hạn được cấu hình
 * Gọi hàm này thay thế cho session_start() ở các trang
 * 
 * Session sẽ tồn tại 30 ngày và chỉ logout nếu không hoạt động trong 7 ngày
 */
function init_session() {
    // Chỉ đặt cấu hình session khi chưa được khởi tạo
    if (session_status() === PHP_SESSION_NONE) {
        // Cấu hình thời gian session từ file session_config.php
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Đặt true nếu dùng HTTPS
            'httponly' => true, // Bảo mật: không cho JavaScript truy cập cookie
            'samesite' => 'Lax' // Bảo vệ CSRF
        ]);
        
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
        // 1. Kiểm tra định dạng cookie trước khi xử lý
        $cookie_parts = explode(':', $_COOKIE['remember_token'], 2); // Thêm limit = 2
        if (count($cookie_parts) === 2) {
            $user_id = $cookie_parts[0];
            $token_from_cookie = $cookie_parts[1];

            require_once __DIR__ . '/../config/database.php';

            // 2. Truy vấn DB một cách an toàn
            $sql = "SELECT token FROM remember_tokens WHERE user_id = ? AND expiry > NOW()";
            $stmt = $conn->prepare($sql);
            
            // 3. Thêm kiểm tra prepare statement
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                // 4. Lặp qua các token (một user có thể có nhiều token) và kiểm tra
                while ($row = $result->fetch_assoc()) {
                    $hashed_token_from_db = $row['token'];

                    if (password_verify($token_from_cookie, $hashed_token_from_db)) {
                        // Token hợp lệ! Đăng nhập cho người dùng
                        // Lấy thông tin user một cách an toàn
                        $user_stmt = $conn->prepare("SELECT id, username FROM user WHERE id = ? AND deleted_at IS NULL");
                        if ($user_stmt) {
                            $user_stmt->bind_param("i", $user_id);
                            $user_stmt->execute();
                            $user_result = $user_stmt->get_result();

                            if ($user = $user_result->fetch_assoc()) {
                                 session_regenerate_id(true);
                                 $_SESSION['user_id'] = $user['id'];
                                 $_SESSION['username'] = $user['username'];
                                 $_SESSION['last_activity'] = time();
                                 
                                 // Lưu log hoạt động
                                 try {
                                     require_once __DIR__ . '/../utils/error_handler.php';
                                     $notify_content = 'Người dùng ' . $user['username'] . ' đã đăng nhập tự động qua "Ghi nhớ đăng nhập"';
                                     log_activity($conn, $user['id'], 'auto_login', 'user', $user['id'], null, [
                                         'login_time' => date('Y-m-d H:i:s'),
                                         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                                     ], $notify_content);
                                 } catch (Exception $e) {
                                     error_log("Error logging auto-login: " . $e->getMessage());
                                 }
                                 
                                 // Tạo token mới và cập nhật cookie (để tăng bảo mật)
                                 $new_token = bin2hex(random_bytes(32));
                                 $new_hash = password_hash($new_token, PASSWORD_DEFAULT);
                                 $expiry = date('Y-m-d H:i:s', time() + REMEMBER_ME_DURATION);
                                 
                                 $update_stmt = $conn->prepare("UPDATE remember_tokens SET token = ?, expiry = ? WHERE user_id = ?");
                                 if ($update_stmt) {
                                     $update_stmt->bind_param("ssi", $new_hash, $expiry, $user_id);
                                     $update_stmt->execute();
                                     $update_stmt->close();
                                 }
                                 
                                 // Cập nhật cookie với token mới
                                 setcookie('remember_token', $user_id . ':' . $new_token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
                            }
                            $user_stmt->close();
                        }
                        
                        // Thoát khỏi vòng lặp sau khi tìm thấy token hợp lệ
                        break;
                    }
                }
                $stmt->close();
            }
            
            $conn->close();
        }
        
        // 5. Nếu sau tất cả các bước trên mà vẫn chưa đăng nhập được
        // (do cookie sai, token hết hạn/không khớp), hãy xóa cookie hỏng đi.
        // Điều này cực kỳ quan trọng để ngăn lỗi lặp lại.
        if (!isset($_SESSION['user_id'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true); // Xóa cookie
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
    
    // Thời gian không hoạt động tối đa từ file session_config.php
    $inactive_timeout = SESSION_INACTIVE_TIMEOUT; 
    
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
