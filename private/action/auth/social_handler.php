<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/error_handler.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Hybridauth\Hybridauth;

// 1. Cấu hình nhanh
$callback = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/public/handlers/auth_callback.php";
$config = [
    'callback' => $callback,
    'providers' => [
        'Google'   => ['enabled' => true, 'keys' => ['id' => env('GOOGLE_CLIENT_ID'), 'secret' => env('GOOGLE_CLIENT_SECRET')]],
        'Facebook' => ['enabled' => true, 'keys' => ['id' => env('FACEBOOK_APP_ID'), 'secret' => env('FACEBOOK_APP_SECRET')], 'scope' => 'email public_profile']
    ]
];

try {
    // 2. Xác thực và lấy Profile
    if (isset($_GET['provider'])) $_SESSION['social_provider'] = ucfirst(strtolower($_GET['provider']));
    $provider = $_SESSION['social_provider'] ?? 'Google';
    
    $hybridauth = new Hybridauth($config);
    $adapter = $hybridauth->authenticate($provider);
    $profile = $adapter->getUserProfile();
    $adapter->disconnect();

    if (!$profile->email) throw new Exception("Không lấy được Email từ $provider");

    // 3. Kết nối DB (PDO)
    $pdo = new PDO("mysql:host=".DB_SERVER.";dbname=".DB_NAME.";charset=utf8mb4", DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $idField = ($provider === 'Google') ? 'google_id' : 'facebook_id';
    $email = $profile->email;
    $socialId = $profile->identifier;

    // 4. Tìm user theo Social ID hoặc Email
    $stmt = $pdo->prepare("SELECT id, username FROM user WHERE ($idField = ? OR email = ?) AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$socialId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Cập nhật Social ID nếu trước đó chưa có (trường hợp tìm thấy qua email)
        $pdo->prepare("UPDATE user SET $idField = ?, email_verified = 1 WHERE id = ?")->execute([$socialId, $user['id']]);
    } else {
        // Tạo user mới
        $username = $profile->displayName ?: ($profile->firstName . ' ' . $profile->lastName);
        $pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $pdo->prepare("INSERT INTO user (username, email, password, $idField, email_verified, status, created_at) VALUES (?, ?, ?, ?, 1, 1, NOW())")
            ->execute([$username, $email, $pass, $socialId]);
            
        $user = ['id' => $pdo->lastInsertId(), 'username' => $username];
    }

    // 5. Thiết lập Session và Redirect
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['last_activity'] = time();

    log_activity($pdo, $user['id'], 'social_login', 'user', $user['id'], null, null, "Đăng nhập bằng $provider thành công");

    header("Location: /public/pages/dashboard.php");
    exit;

} catch (Exception $e) {
    error_log("Social Login Error: " . $e->getMessage());
    $_SESSION['login_error'] = "Lỗi: " . $e->getMessage();
    header("Location: /public/pages/auth/login.php");
    exit;
}
