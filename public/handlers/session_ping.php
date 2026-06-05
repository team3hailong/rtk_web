<?php
/**
 * Session Ping Handler
 * Xử lý yêu cầu làm mới session từ client
 */

// Thêm các file cần thiết
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/utils/session_middleware.php';

// Đảm bảo session đã được khởi tạo
init_session();

// Set CORS headers để cho phép request từ frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Xử lý preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Chỉ chấp nhận yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
            } else {
                // Người dùng chưa đăng nhập
                header('HTTP/1.1 401 Unauthorized');
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'User not logged in']);
                exit;
            }
        } else {
            // Action không hợp lệ
            header('HTTP/1.1 400 Bad Request');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
        }
    } catch (Exception $e) {
        // Xử lý lỗi
        error_log("Session ping error: " . $e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
        exit;
    }
} else {
    // Phương thức không được hỗ trợ
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
