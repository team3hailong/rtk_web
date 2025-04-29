<?php
// Chuyển hướng người dùng đến cầu nối trung gian để xử lý xác thực email
// Lấy token từ URL và chuyển tiếp đến action_handler
$token = $_GET['token'] ?? '';

if (empty($token)) {
    // Nếu không có token, hiển thị thông báo lỗi
    $message = 'Token xác thực không hợp lệ.';
    $status = 'error';
} else {
    // Chuyển hướng đến file trung gian với token
    header("Location: /public/handlers/action_handler.php?module=auth&action=verify-email&token=" . urlencode($token));
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực Email</title>
    <link rel="stylesheet" href="/public/assets/css/base.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f7f6;
        }
        .verification-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .success {
            color: #2e7d32;
        }
        .error {
            color: #c62828;
        }
        .button {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #388e3c;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2 class="<?php echo $status ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </h2>
        <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <a href="/public/pages/auth/login.php" class="button">Đăng nhập</a>
    </div>
</body>
</html>