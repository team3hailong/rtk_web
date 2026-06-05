<?php

/**
 * Test Final - Sử dụng RTK_API_URL từ config
 */

require_once __DIR__ . '/../../private/api/rtk_system/online_users_api.php';

echo str_repeat("=", 80) . "\n";
echo "TEST RTK ONLINE USERS API - FINAL VERSION\n";
echo str_repeat("=", 80) . "\n\n";

echo "Configuration:\n";
echo "  RTK_API_URL: " . RTK_API_URL . "\n";
echo "  Base URL sẽ được trích xuất tự động\n\n";

echo str_repeat("-", 80) . "\n\n";

// Test với authentication (mặc định)
echo "TEST 1: Gọi API với Authentication (useAuth=true)\n";
echo str_repeat("-", 80) . "\n";

$result1 = getRtkOnlineUsers(1, 10, '', true);

if ($result1['success']) {
    echo "[✓] Thành công với Authentication!\n\n";
    $parsed1 = parseOnlineUsersData($result1);
    
    if ($parsed1['success']) {
        echo sprintf("  Total: %s users\n", $parsed1['total']);
        echo sprintf("  Records: %d\n", count($parsed1['records']));
        
        if (!empty($parsed1['records'])) {
            echo "\nRecord đầu tiên:\n";
            $first = $parsed1['records'][0];
            echo sprintf("  - Username: %s\n", $first['userName'] ?? 'N/A');
            echo sprintf("  - Mount: %s\n", $first['mountName'] ?? 'N/A');
            echo sprintf("  - Status: %s\n", $first['status'] ?? 'N/A');
        }
    }
} else {
    echo "[✗] Lỗi với Authentication:\n";
    echo "  " . ($result1['error'] ?? 'Unknown error') . "\n";
    if (isset($result1['content_type'])) {
        echo "  Content-Type: " . $result1['content_type'] . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Test không authentication
echo "TEST 2: Gọi API không Authentication (useAuth=false)\n";
echo str_repeat("-", 80) . "\n";

$result2 = getRtkOnlineUsers(1, 10, '', false);

if ($result2['success']) {
    echo "[✓] Thành công không Authentication!\n\n";
    $parsed2 = parseOnlineUsersData($result2);
    
    if ($parsed2['success']) {
        echo sprintf("  Total: %s users\n", $parsed2['total']);
        echo sprintf("  Records: %d\n", count($parsed2['records']));
        
        if (!empty($parsed2['records'])) {
            echo "\nRecord đầu tiên:\n";
            $first = $parsed2['records'][0];
            echo sprintf("  - Username: %s\n", $first['userName'] ?? 'N/A');
            echo sprintf("  - Mount: %s\n", $first['mountName'] ?? 'N/A');
            echo sprintf("  - Status: %s\n", $first['status'] ?? 'N/A');
        }
    }
} else {
    echo "[✗] Lỗi không Authentication:\n";
    echo "  " . ($result2['error'] ?? 'Unknown error') . "\n";
    if (isset($result2['content_type'])) {
        echo "  Content-Type: " . $result2['content_type'] . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Kết luận
echo "KẾT LUẬN:\n";
echo str_repeat("-", 80) . "\n";

if ($result1['success']) {
    echo "[✓] API hoạt động với Authentication\n";
    echo "    → Sử dụng: getRtkOnlineUsers(\$page, \$size, \$status, true)\n";
} elseif ($result2['success']) {
    echo "[✓] API hoạt động không cần Authentication\n";
    echo "    → Sử dụng: getRtkOnlineUsers(\$page, \$size, \$status, false)\n";
} else {
    echo "[✗] API không hoạt động với cả hai cách\n";
    echo "\nCó thể:\n";
    echo "  1. API endpoint này chỉ accessible từ internal network\n";
    echo "  2. Cần authentication method khác (cookie/session)\n";
    echo "  3. URL hoặc port không đúng\n";
    echo "\nHướng dẫn:\n";
    echo "  - Kiểm tra URL trong config: " . RTK_API_URL . "\n";
    echo "  - Xác nhận endpoint online-users có tồn tại không\n";
    echo "  - Thử truy cập từ browser để xem response\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "TEST HOÀN TẤT\n";
echo str_repeat("=", 80) . "\n";
