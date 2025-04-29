<?php
/**
 * CSRF Helper - Bảo vệ chống lại các cuộc tấn công CSRF
 * 
 * File này cung cấp các hàm để tạo, xác thực và quản lý CSRF token
 * dùng để bảo vệ các form POST trên trang web
 */

/**
 * Tạo một CSRF token mới, lưu vào session và trả về token đó
 * 
 * @return string CSRF token mới được tạo
 */
function generate_csrf_token() {
    // Nếu chưa có mảng CSRF tokens trong session thì khởi tạo
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Tạo token ngẫu nhiên và thêm vào session
    $token = bin2hex(random_bytes(32));
    
    // Lưu token trong session với thời gian hết hạn (1 giờ)
    $_SESSION['csrf_tokens'][$token] = time() + 3600; // 3600 giây = 1 giờ
    
    // Dọn dẹp các token cũ để tránh session quá lớn
    clean_old_csrf_tokens();
    
    return $token;
}

/**
 * Tạo một input hidden với CSRF token để thêm vào form
 * 
 * @return string HTML input chứa CSRF token
 */
function generate_csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Kiểm tra xem CSRF token từ request có hợp lệ hay không
 * 
 * @param string $token Token từ request cần kiểm tra
 * @return bool True nếu token hợp lệ, False nếu không
 */
function validate_csrf_token($token) {
    // Nếu không có token hoặc không có mảng token trong session
    if (empty($token) || !isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        return false;
    }
    
    // Kiểm tra token có tồn tại trong session không
    if (!isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }
    
    // Kiểm tra token có còn hiệu lực không
    $expiry = $_SESSION['csrf_tokens'][$token];
    if (time() > $expiry) {
        // Xóa token nếu đã hết hạn
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }
    
    // Token hợp lệ, xóa khỏi session để đảm bảo chỉ sử dụng một lần
    // (one-time token)
    unset($_SESSION['csrf_tokens'][$token]);
    return true;
}

/**
 * Dọn dẹp các CSRF token đã hết hạn trong session
 */
function clean_old_csrf_tokens() {
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        return;
    }
    
    $current_time = time();
    foreach ($_SESSION['csrf_tokens'] as $token => $expiry) {
        if ($current_time > $expiry) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
    }
}