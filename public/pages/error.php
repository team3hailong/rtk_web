<?php
session_start(); // Bắt đầu session nếu chưa có

// Lấy thông báo lỗi từ session hoặc tham số GET (ưu tiên session)
$error_message = $_SESSION['error_message'] ?? $_GET['message'] ?? 'Đã xảy ra lỗi không xác định.';
$error_details = $_SESSION['error_details'] ?? $_GET['details'] ?? null;

// Xóa thông báo lỗi khỏi session để không hiển thị lại sau khi tải lại trang
unset($_SESSION['error_message']);
unset($_SESSION['error_details']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .error-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545; /* Màu đỏ cho biểu tượng lỗi */
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .error-details {
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            text-align: left;
            font-family: monospace; /* Font chữ phù hợp cho code/debug */
            white-space: pre-wrap; /* Giữ nguyên định dạng xuống dòng và khoảng trắng */
            word-wrap: break-word; /* Tự động ngắt dòng nếu quá dài */
            margin-top: 20px;
            max-height: 200px; /* Giới hạn chiều cao và thêm thanh cuộn nếu cần */
            overflow-y: auto;
        }
        a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">&#9888;</div> <!-- Biểu tượng cảnh báo -->
        <h1>Đã xảy ra lỗi</h1>
        <p><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>

        <?php if (isset($error_details)): ?>
            <h2>Chi tiết lỗi (dành cho debug):</h2>
            <pre class="error-details"><?php echo htmlspecialchars(print_r($error_details, true), ENT_QUOTES, 'UTF-8'); ?></pre>
        <?php endif; ?>

        <p><a href="/">Quay lại trang chủ</a></p>
    </div>
</body>
</html>
