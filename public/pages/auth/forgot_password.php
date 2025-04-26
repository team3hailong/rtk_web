<!-- forgot_password.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
</head>
<body>
    <h2>Quên mật khẩu</h2>
    <form action="../../../../private/action/auth/process_forgot_password.php" method="POST">
        <label for="email">Nhập email của bạn:</label>
        <input type="email" name="email" required>
        <button type="submit">Gửi liên kết khôi phục</button>
    </form>
</body>
</html>
