<?php
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

// Kiểm tra đăng nhập
init_session();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized access');
}

// Lấy đường dẫn ảnh từ tham số
$image_url = $_GET['image'] ?? '';

// Validate URL
if (empty($image_url)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Image URL is required');
}

// Đảm bảo URL là từ domain của chúng ta
$base_url = BASE_URL;
if (strpos($image_url, $base_url) !== 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid image URL');
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem minh chứng</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f5f5f5;
            min-height: 100vh;
        }
        .image-container {
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 100%;
            text-align: center;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .close-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #4a5568;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .close-btn:hover {
            background: #2d3748;
        }
    </style>
</head>
<body>
    <div class="image-container">
        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Minh chứng thanh toán">
    </div>
    <button class="close-btn" onclick="window.close()">Đóng</button>
</body>
</html>
