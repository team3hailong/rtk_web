<?php

/**
 * File test cho Online Users API với authentication
 */

require_once __DIR__ . '/../../private/api/rtk_system/online_users_api.php';

echo str_repeat("=", 80) . "\n";
echo "TEST RTK ONLINE USERS API (With Authentication)\n";
echo str_repeat("=", 80) . "\n\n";

// Test 1: Lấy danh sách người dùng online (page 1, 100 records)
echo "Test 1: Lấy danh sách người dùng online (page=1, size=100)\n";
echo str_repeat("-", 80) . "\n";

$result = getRtkOnlineUsers(1, 100, '');

if ($result['success']) {
    echo "[✓] Kết nối API thành công!\n\n";
    
    $parsedData = parseOnlineUsersData($result);
    
    if ($parsedData['success']) {
        echo "Thông tin tổng quan:\n";
        echo sprintf("  %-25s: %s\n", "Response Code", $parsedData['code']);
        echo sprintf("  %-25s: %s\n", "Total trong DB", $parsedData['total']);
        echo sprintf("  %-25s: %s\n", "Page", $parsedData['page']);
        echo sprintf("  %-25s: %s\n", "Size", $parsedData['size']);
        echo sprintf("  %-25s: %d\n", "Records nhận được", count($parsedData['records']));
        
        // Hiển thị 5 record đầu tiên
        if (!empty($parsedData['records'])) {
            echo "\n5 record đầu tiên:\n";
            echo str_repeat("-", 80) . "\n";
            
            $firstFive = array_slice($parsedData['records'], 0, 5);
            foreach ($firstFive as $index => $record) {
                echo "\n[Record #" . ($index + 1) . "]\n";
                echo sprintf("  %-25s: %s\n", "ID", $record['id'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Username", $record['userName'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Caster Name", $record['casterName'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Mount Name", $record['mountName'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "User Agent", $record['userAgent'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Status", $record['status'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Sat Count", $record['satCount'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Send Bytes", $record['sendBytes'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "User IP", $record['userIp'] ?? 'N/A');
                echo sprintf("  %-25s: %s\n", "Master Station", $record['masterStationName'] ?? 'N/A');
                echo sprintf("  %-25s: %s km\n", "Distance", $record['masterStationDistance'] ?? 'N/A');
            }
        }
        
        // Test 2: Thống kê
        echo "\n\n" . str_repeat("=", 80) . "\n";
        echo "Test 2: Thống kê người dùng online\n";
        echo str_repeat("-", 80) . "\n\n";
        
        $stats = getOnlineUsersStatistics($parsedData['records']);
        
        echo "[Thống kê chung]\n";
        echo sprintf("  %-30s: %d users\n", "Tổng số người dùng", $stats['total_users']);
        echo sprintf("  %-30s: %s bytes\n", "Tổng bytes", number_format($stats['total_send_bytes'], 2));
        echo sprintf("  %-30s: %s bytes/user\n", "Trung bình bytes", number_format($stats['avg_send_bytes'], 2));
        
        echo "\n[Phân bố theo Status]\n";
        ksort($stats['by_status']);
        foreach ($stats['by_status'] as $status => $count) {
            $percentage = ($count / $stats['total_users']) * 100;
            echo sprintf("  Status %-15s: %5d users (%6.2f%%)\n", $status, $count, $percentage);
        }
        
        echo "\n[Top 15 Mount Points]\n";
        arsort($stats['by_mount']);
        $topMounts = array_slice($stats['by_mount'], 0, 15, true);
        foreach ($topMounts as $mount => $count) {
            $percentage = ($count / $stats['total_users']) * 100;
            echo sprintf("  %-35s: %5d users (%6.2f%%)\n", $mount, $count, $percentage);
        }
        
        // Test 3: Tìm kiếm user
        echo "\n\n" . str_repeat("=", 80) . "\n";
        echo "Test 3: Tìm kiếm user cụ thể\n";
        echo str_repeat("-", 80) . "\n\n";
        
        if (!empty($parsedData['records'])) {
            $testUsers = array_slice($parsedData['records'], 0, 3);
            foreach ($testUsers as $i => $testUser) {
                $userName = $testUser['userName'] ?? null;
                if ($userName) {
                    echo sprintf("Tìm user '%s' ... ", $userName);
                    $foundUser = findUserByUsername($parsedData['records'], $userName);
                    if ($foundUser) {
                        echo "[✓] Tìm thấy!\n";
                        echo sprintf("  → Mount: %s, Status: %s, IP: %s\n", 
                            $foundUser['mountName'] ?? 'N/A',
                            $foundUser['status'] ?? 'N/A',
                            $foundUser['userIp'] ?? 'N/A'
                        );
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
        
        $allStatuses = array_keys($stats['by_status']);
        foreach ($allStatuses as $statusTest) {
            $filtered = filterUsersByStatus($parsedData['records'], $statusTest);
            echo sprintf("Status %-15s: %d users\n", $statusTest, count($filtered));
            
            if (count($filtered) > 0 && count($filtered) <= 3) {
                foreach ($filtered as $user) {
                    echo sprintf("  → %s (%s)\n", 
                        $user['userName'] ?? 'N/A',
                        $user['mountName'] ?? 'N/A'
                    );
                }
            } elseif (count($filtered) > 3) {
                $sample = array_slice($filtered, 0, 2);
                foreach ($sample as $user) {
                    echo sprintf("  → %s (%s)\n", 
                        $user['userName'] ?? 'N/A',
                        $user['mountName'] ?? 'N/A'
                    );
                }
                echo "  → ... và " . (count($filtered) - 2) . " user khác\n";
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
}

// Test 5: Lấy tất cả dữ liệu
echo "\n\n" . str_repeat("=", 80) . "\n";
echo "Test 5: Lấy toàn bộ dữ liệu (size=100000)\n";
echo str_repeat("-", 80) . "\n\n";

$resultAll = getRtkOnlineUsers(1, 100000, '');

if ($resultAll['success']) {
    $parsedAll = parseOnlineUsersData($resultAll);
    if ($parsedAll['success']) {
        echo "[✓] Lấy toàn bộ dữ liệu thành công!\n\n";
        echo sprintf("  %-30s: %s\n", "Tổng records trong DB", $parsedAll['total']);
        echo sprintf("  %-30s: %d\n", "Records nhận được", count($parsedAll['records']));
        
        $statsAll = getOnlineUsersStatistics($parsedAll['records']);
        echo sprintf("  %-30s: %s bytes\n", "Tổng bytes", number_format($statsAll['total_send_bytes'], 2));
        echo sprintf("  %-30s: %d\n", "Số mount points khác nhau", count($statsAll['by_mount']));
        echo sprintf("  %-30s: %d\n", "Số status khác nhau", count($statsAll['by_status']));
        
        echo "\n[Danh sách tất cả Mount Points (" . count($statsAll['by_mount']) . " mount)]\n";
        arsort($statsAll['by_mount']);
        foreach ($statsAll['by_mount'] as $mount => $count) {
            $percentage = ($count / $statsAll['total_users']) * 100;
            echo sprintf("  %-40s: %5d users (%6.2f%%)\n", $mount, $count, $percentage);
        }
        
    } else {
        echo "[✗] Lỗi parse data\n";
    }
} else {
    echo "[✗] Lỗi lấy dữ liệu: " . ($resultAll['error'] ?? 'Unknown') . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "TEST HOÀN TẤT!\n";
echo str_repeat("=", 80) . "\n";
