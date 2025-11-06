<?php

/**
 * File test đơn giản cho Online Users API - CLI version
 */

// Chỉ require file API, không load config khác
require_once __DIR__ . '/../../private/api/rtk_system/online_users_api.php';

echo "=== TEST RTK ONLINE USERS API ===\n\n";

// Test 1: Lấy danh sách người dùng online
echo "Test 1: Lấy danh sách người dùng online (page=1, size=100)\n";
echo str_repeat("-", 80) . "\n";

$result = getRtkOnlineUsers(1, 100, '');

if ($result['success']) {
    echo "[✓] Kết nối API thành công!\n\n";
    
    // Parse data
    $parsedData = parseOnlineUsersData($result);
    
    if ($parsedData['success']) {
        echo "Thông tin tổng quan:\n";
        echo "  - Code: " . $parsedData['code'] . "\n";
        echo "  - Total trong DB: " . $parsedData['total'] . " users\n";
        echo "  - Page: " . $parsedData['page'] . "\n";
        echo "  - Size: " . $parsedData['size'] . "\n";
        echo "  - Số records nhận được: " . count($parsedData['records']) . "\n\n";
        
        // Hiển thị 5 record đầu tiên
        echo "5 record đầu tiên:\n";
        echo str_repeat("-", 80) . "\n";
        $firstFive = array_slice($parsedData['records'], 0, 5);
        foreach ($firstFive as $index => $record) {
            echo "\n[Record #" . ($index + 1) . "]\n";
            echo sprintf("  %-20s: %s\n", "ID", $record['id'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Username", $record['userName'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Caster Name", $record['casterName'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Mount Name", $record['mountName'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "User Agent", $record['userAgent'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Status", $record['status'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Sat Count", $record['satCount'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Send Bytes", $record['sendBytes'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "User IP", $record['userIp'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Master Station", $record['masterStationName'] ?? 'N/A');
            echo sprintf("  %-20s: %s\n", "Distance", $record['masterStationDistance'] ?? 'N/A');
        }
        
        // Test 2: Lấy thống kê
        echo "\n\n" . str_repeat("=", 80) . "\n";
        echo "Test 2: Thống kê người dùng online\n";
        echo str_repeat("-", 80) . "\n";
        
        $stats = getOnlineUsersStatistics($parsedData['records']);
        echo "\n[Thống kê chung]\n";
        echo sprintf("  Tổng số người dùng: %d\n", $stats['total_users']);
        echo sprintf("  Tổng bytes: %s\n", number_format($stats['total_send_bytes'], 2));
        echo sprintf("  Trung bình bytes/user: %s\n", number_format($stats['avg_send_bytes'], 2));
        
        echo "\n[Phân bố theo Status]\n";
        ksort($stats['by_status']);
        foreach ($stats['by_status'] as $status => $count) {
            $percentage = ($count / $stats['total_users']) * 100;
            echo sprintf("  Status %-10s: %5d users (%5.2f%%)\n", $status, $count, $percentage);
        }
        
        echo "\n[Top 15 Mount Points]\n";
        arsort($stats['by_mount']);
        $topMounts = array_slice($stats['by_mount'], 0, 15, true);
        foreach ($topMounts as $mount => $count) {
            $percentage = ($count / $stats['total_users']) * 100;
            echo sprintf("  %-30s: %5d users (%5.2f%%)\n", $mount, $count, $percentage);
        }
        
        // Test 3: Tìm kiếm user cụ thể
        echo "\n\n" . str_repeat("=", 80) . "\n";
        echo "Test 3: Tìm kiếm user cụ thể\n";
        echo str_repeat("-", 80) . "\n";
        
        if (!empty($parsedData['records'])) {
            $testUsers = array_slice($parsedData['records'], 0, 3);
            foreach ($testUsers as $testUser) {
                $userName = $testUser['userName'] ?? null;
                if ($userName) {
                    echo "\nTìm kiếm user: $userName ... ";
                    $foundUser = findUserByUsername($parsedData['records'], $userName);
                    if ($foundUser) {
                        echo "[✓] Tìm thấy!\n";
                        echo "  - Mount: " . ($foundUser['mountName'] ?? 'N/A') . "\n";
                        echo "  - Status: " . ($foundUser['status'] ?? 'N/A') . "\n";
                        echo "  - IP: " . ($foundUser['userIp'] ?? 'N/A') . "\n";
                    } else {
                        echo "[✗] Không tìm thấy\n";
                    }
                }
            }
        }
        
        // Test 4: Lọc theo status
        echo "\n\n" . str_repeat("=", 80) . "\n";
        echo "Test 4: Lọc người dùng theo status\n";
        echo str_repeat("-", 80) . "\n\n";
        
        // Lấy tất cả các status có trong dữ liệu
        $allStatuses = array_keys($stats['by_status']);
        foreach ($allStatuses as $statusTest) {
            $filtered = filterUsersByStatus($parsedData['records'], $statusTest);
            echo sprintf("  Status %-10s: %d người dùng\n", $statusTest, count($filtered));
            
            // Hiển thị 2 user đầu tiên của status này
            if (count($filtered) > 0) {
                $sample = array_slice($filtered, 0, 2);
                foreach ($sample as $user) {
                    echo sprintf("    → %s (%s)\n", 
                        $user['userName'] ?? 'N/A', 
                        $user['mountName'] ?? 'N/A'
                    );
                }
            }
        }
        
    } else {
        echo "[✗] Lỗi parse data: " . $parsedData['error'] . "\n";
    }
    
} else {
    echo "[✗] Lỗi kết nối API:\n";
    echo "  Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    if (isset($result['http_code'])) {
        echo "  HTTP Code: " . $result['http_code'] . "\n";
    }
    if (isset($result['response'])) {
        echo "  Response: " . substr($result['response'], 0, 500) . "...\n";
    }
}

// Test 5: Lấy tất cả dữ liệu
echo "\n\n" . str_repeat("=", 80) . "\n";
echo "Test 5: Lấy toàn bộ dữ liệu (size=100000)\n";
echo str_repeat("-", 80) . "\n\n";

$resultAll = getRtkOnlineUsers(1, 100000, '');

if ($resultAll['success']) {
    $parsedAll = parseOnlineUsersData($resultAll);
    if ($parsedAll['success']) {
        echo "[✓] Lấy toàn bộ dữ liệu thành công!\n";
        echo sprintf("  - Tổng số records trong DB: %s\n", $parsedAll['total']);
        echo sprintf("  - Số records nhận được: %d\n", count($parsedAll['records']));
        
        // Thống kê toàn bộ
        $statsAll = getOnlineUsersStatistics($parsedAll['records']);
        echo sprintf("  - Tổng bytes: %s\n", number_format($statsAll['total_send_bytes'], 2));
        echo sprintf("  - Số mount points: %d\n", count($statsAll['by_mount']));
        echo sprintf("  - Số status khác nhau: %d\n", count($statsAll['by_status']));
    } else {
        echo "[✗] Lỗi parse data: " . $parsedAll['error'] . "\n";
    }
} else {
    echo "[✗] Lỗi lấy dữ liệu: " . ($resultAll['error'] ?? 'Unknown error') . "\n";
}

echo "\n\n" . str_repeat("=", 80) . "\n";
echo "=== KẾT THÚC TEST ===\n";
echo str_repeat("=", 80) . "\n";
