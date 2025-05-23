<?php
/**
 * Session Ping Handler
 * Xử lý yêu cầu làm mới session từ client
 */

// Thêm các file cần thiết
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// Đảm bảo session đã được khởi tạo
init_session();

// Chỉ chấp nhận yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy nội dung yêu cầu
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    
    if (isset($data['action']) && $data['action'] === 'refresh_session') {
        // Chỉ làm mới session nếu người dùng đã đăng nhập
        if (isset($_SESSION['user_id'])) {
            // Cập nhật thời gian hoạt động cuối cùng
            refresh_session();
            
            // Trả về phản hồi thành công
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Session refreshed']);
            exit;
        }
    }
}

// Trả về lỗi nếu yêu cầu không hợp lệ
header('HTTP/1.1 400 Bad Request');
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
