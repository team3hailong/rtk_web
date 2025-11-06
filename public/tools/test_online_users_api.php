<?php

/**
 * File test cho Online Users API
 * Truy cập file này qua trình duyệt hoặc chạy từ command line
 */

require_once __DIR__ . '/../../private/api/rtk_system/online_users_api.php';

// Set header để hiển thị JSON đẹp hơn
header('Content-Type: application/json; charset=utf-8');

// Bắt đầu output buffering
ob_start();

echo "=== TEST RTK ONLINE USERS API ===\n\n";

// Test 1: Lấy danh sách người dùng online
echo "Test 1: Lấy danh sách người dùng online (page=1, size=100)\n";
echo str_repeat("-", 50) . "\n";

$result = getRtkOnlineUsers(1, 100, '');

if ($result['success']) {
    echo "✓ Kết nối API thành công!\n\n";
    
    // Parse data
    $parsedData = parseOnlineUsersData($result);
    
    if ($parsedData['success']) {
        echo "Thông tin tổng quan:\n";
        echo "- Code: " . $parsedData['code'] . "\n";
        echo "- Total: " . $parsedData['total'] . "\n";
        echo "- Page: " . $parsedData['page'] . "\n";
        echo "- Size: " . $parsedData['size'] . "\n";
        echo "- Số records nhận được: " . count($parsedData['records']) . "\n\n";
        
        // Hiển thị 3 record đầu tiên
        echo "3 record đầu tiên:\n";
        $firstThree = array_slice($parsedData['records'], 0, 3);
        foreach ($firstThree as $index => $record) {
            echo "\nRecord #" . ($index + 1) . ":\n";
            echo "  - ID: " . ($record['id'] ?? 'N/A') . "\n";
            echo "  - Username: " . ($record['userName'] ?? 'N/A') . "\n";
            echo "  - Mount: " . ($record['mountName'] ?? 'N/A') . "\n";
            echo "  - User Agent: " . ($record['userAgent'] ?? 'N/A') . "\n";
            echo "  - Status: " . ($record['status'] ?? 'N/A') . "\n";
            echo "  - Sat Count: " . ($record['satCount'] ?? 'N/A') . "\n";
            echo "  - Send Bytes: " . ($record['sendBytes'] ?? 'N/A') . "\n";
            echo "  - User IP: " . ($record['userIp'] ?? 'N/A') . "\n";
        }
        
        // Test 2: Lấy thống kê
        echo "\n\n" . str_repeat("=", 50) . "\n";
        echo "Test 2: Thống kê người dùng online\n";
        echo str_repeat("-", 50) . "\n";
        
        $stats = getOnlineUsersStatistics($parsedData['records']);
        echo "Tổng số người dùng: " . $stats['total_users'] . "\n\n";
        
        echo "Thống kê theo trạng thái:\n";
        foreach ($stats['by_status'] as $status => $count) {
            echo "  - Status $status: $count người dùng\n";
        }
        
        echo "\nThống kê theo Mount (Top 10):\n";
        arsort($stats['by_mount']);
        $topMounts = array_slice($stats['by_mount'], 0, 10, true);
        foreach ($topMounts as $mount => $count) {
            echo "  - $mount: $count người dùng\n";
        }
        
        echo "\nThống kê bytes:\n";
        echo "  - Tổng: " . number_format($stats['total_send_bytes'], 2) . " bytes\n";
        echo "  - Trung bình: " . number_format($stats['avg_send_bytes'], 2) . " bytes/user\n";
        
        // Test 3: Tìm kiếm user cụ thể
        echo "\n\n" . str_repeat("=", 50) . "\n";
        echo "Test 3: Tìm kiếm user cụ thể\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!empty($parsedData['records'])) {
            $firstUser = $parsedData['records'][0];
            $userName = $firstUser['userName'] ?? null;
            
            if ($userName) {
                echo "Tìm kiếm user: $userName\n";
                $foundUser = findUserByUsername($parsedData['records'], $userName);
                
                if ($foundUser) {
                    echo "✓ Tìm thấy user!\n";
                    echo json_encode($foundUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "✗ Không tìm thấy user\n";
                }
            }
        }
        
        // Test 4: Lọc theo status
        echo "\n\n" . str_repeat("=", 50) . "\n";
        echo "Test 4: Lọc người dùng theo status\n";
        echo str_repeat("-", 50) . "\n";
        
        $statusesToTest = [-1, 4, 5];
        foreach ($statusesToTest as $statusTest) {
            $filtered = filterUsersByStatus($parsedData['records'], $statusTest);
            echo "Status $statusTest: " . count($filtered) . " người dùng\n";
        }
        
    } else {
        echo "✗ Lỗi parse data: " . $parsedData['error'] . "\n";
    }
    
} else {
    echo "✗ Lỗi kết nối API:\n";
    echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    if (isset($result['http_code'])) {
        echo "HTTP Code: " . $result['http_code'] . "\n";
    }
    if (isset($result['response'])) {
        echo "Response: " . $result['response'] . "\n";
    }
}

// Test 5: Lấy tất cả dữ liệu (100000 records)
echo "\n\n" . str_repeat("=", 50) . "\n";
echo "Test 5: Lấy toàn bộ dữ liệu (size=100000)\n";
echo str_repeat("-", 50) . "\n";

$resultAll = getRtkOnlineUsers(1, 100000, '');

if ($resultAll['success']) {
    $parsedAll = parseOnlineUsersData($resultAll);
    if ($parsedAll['success']) {
        echo "✓ Lấy toàn bộ dữ liệu thành công!\n";
        echo "- Tổng số records trong DB: " . $parsedAll['total'] . "\n";
        echo "- Số records nhận được: " . count($parsedAll['records']) . "\n";
    } else {
        echo "✗ Lỗi parse data\n";
    }
} else {
    echo "✗ Lỗi lấy dữ liệu\n";
}

echo "\n\n=== KẾT THÚC TEST ===\n";

// Lấy output và hiển thị
$output = ob_get_clean();

// Nếu chạy từ command line
if (php_sapi_name() === 'cli') {
    echo $output;
} else {
    // Nếu chạy từ browser, hiển thị dạng HTML với pre tag
    echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test RTK Online Users API</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        pre {
            background: #252526;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            line-height: 1.5;
        }
        h1 {
            color: #4ec9b0;
        }
    </style>
</head>
<body>
    <h1>🧪 Test RTK Online Users API</h1>
    <pre>" . htmlspecialchars($output) . "</pre>
</body>
</html>";
}
