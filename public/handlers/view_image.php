<?php
// Filepath: public/view_image.php

// Thêm file cấu hình để sử dụng các hàm session
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// Khởi tạo session
init_session();

// Đường dẫn thư mục chứa ảnh minh chứng
$uploadDir = __DIR__ . '/../uploads/payment_proofs/';

// Lấy tên file từ query string
$filename = $_GET['file'] ?? '';

// Kiểm tra nếu tên file hợp lệ
if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif)$/i', $filename)) {
    http_response_code(400);
    echo 'Invalid file name.';
    exit;
}

// Đường dẫn đầy đủ đến file
$filePath = $uploadDir . $filename;

// Kiểm tra nếu file tồn tại
if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

// Gửi header để hiển thị ảnh
$mimeType = mime_content_type($filePath);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));

// Đọc và xuất nội dung file
readfile($filePath);
exit;
