
<?php
require_once __DIR__ . '/../../config/database.php'; 

$token = $_POST['token'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($new_password) || $new_password !== $confirm_password) {
    die('Dữ liệu không hợp lệ.');
}

$stmt = $conn->prepare("SELECT id FROM user WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die('Token không hợp lệ hoặc đã hết hạn.');
}

$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

// Cập nhật mật khẩu
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE user SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $user_id);
$stmt->execute();
$stmt->close();

echo "Mật khẩu đã được đặt lại thành công.";
