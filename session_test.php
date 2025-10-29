<?php
/**
 * Session Testing Script
 * Script để test các chức năng session
 */

require_once __DIR__ . '/private/config/config.php';
require_once PROJECT_ROOT_PATH . '/private/utils/session_middleware.php';

// Khởi tạo session
init_session();

echo "<h1>Session Testing Dashboard</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    button { padding: 10px 20px; margin: 5px; cursor: pointer; }
</style>";

echo "<div class='test-section'>";
echo "<h2>Session Status</h2>";

// Kiểm tra trạng thái session
if (isset($_SESSION['user_id'])) {
    echo "<div class='status success'>✅ Đã đăng nhập - User ID: " . $_SESSION['user_id'] . "</div>";
    echo "<div class='status info'>Username: " . ($_SESSION['username'] ?? 'N/A') . "</div>";

    if (isset($_SESSION['last_activity'])) {
        $last_activity = date('Y-m-d H:i:s', $_SESSION['last_activity']);
        $inactive_time = time() - $_SESSION['last_activity'];
        $inactive_hours = round($inactive_time / 3600, 1);

        echo "<div class='status info'>⏰ Lần hoạt động cuối: $last_activity</div>";
        echo "<div class='status info'>⏳ Thời gian không hoạt động: $inactive_hours giờ</div>";
    } else {
        echo "<div class='status warning'>⚠️ Chưa có thông tin hoạt động</div>";
    }
} else {
    echo "<div class='status warning'>❌ Chưa đăng nhập</div>";
}
echo "</div>";

// Thông tin cấu hình
echo "<div class='test-section'>";
echo "<h2>Session Configuration</h2>";
echo "<div class='status info'>Session Lifetime: " . (SESSION_LIFETIME / 86400) . " ngày</div>";
echo "<div class='status info'>Inactive Timeout: " . (SESSION_INACTIVE_TIMEOUT / 86400) . " ngày</div>";
echo "<div class='status info'>Remember Me Duration: " . (REMEMBER_ME_DURATION / 86400) . " ngày</div>";
echo "</div>";

// Test các chức năng
echo "<div class='test-section'>";
echo "<h2>Test Functions</h2>";

// Test refresh session
echo "<button onclick='refreshSession()'>🔄 Refresh Session</button>";

// Test session ping
echo "<button onclick='testSessionPing()'>📡 Test Session Ping</button>";

// Test logout
echo "<button onclick='logout()' style='background: #dc3545; color: white;'>🚪 Logout</button>";

echo "<div id='test-result' style='margin-top: 10px;'></div>";
echo "</div>";

// JavaScript cho testing
echo "<script>
function refreshSession() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=refresh_session'
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('test-result').innerHTML =
            '<div class=\"status success\">✅ Session refreshed successfully</div>';
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        document.getElementById('test-result').innerHTML =
            '<div class=\"status warning\">❌ Refresh failed: ' + error + '</div>';
    });
}

function testSessionPing() {
    fetch('/public/handlers/session_ping.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'refresh_session' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('test-result').innerHTML =
                '<div class=\"status success\">✅ Session ping successful: ' + data.message + '</div>';
        } else {
            document.getElementById('test-result').innerHTML =
                '<div class=\"status warning\">❌ Session ping failed: ' + data.message + '</div>';
        }
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        document.getElementById('test-result').innerHTML =
            '<div class=\"status warning\">❌ Ping error: ' + error + '</div>';
    });
}

function logout() {
    if (confirm('Bạn có chắc muốn đăng xuất?')) {
        window.location.href = '/public/handlers/action_handler.php?module=auth&action=process_logout';
    }
}

// Auto refresh để test session tracker
setInterval(() => {
    console.log('Session auto-refresh triggered');
}, 900000); // 15 phút
</script>";

// Xử lý POST request để test refresh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refresh_session') {
    if (isset($_SESSION['user_id'])) {
        refresh_session();
        echo "<div class='status success'>Session refreshed via POST</div>";
    } else {
        echo "<div class='status warning'>Cannot refresh: not logged in</div>";
    }
    exit;
}
?>