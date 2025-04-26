<?php
$token = $_GET['token'] ?? '';
if (!$token) die("Token không hợp lệ.");
?>

<form action="../../../../private/action/auth/process_reset_password.php" method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <label>Mật khẩu mới:</label>
    <input type="password" name="new_password" required>
    <label>Xác nhận mật khẩu:</label>
    <input type="password" name="confirm_password" required>
    <button type="submit">Đặt lại mật khẩu</button>
</form>
