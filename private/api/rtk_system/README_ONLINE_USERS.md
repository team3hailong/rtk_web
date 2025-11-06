# RTK Online Users API

API để lấy danh sách người dùng đang online trong hệ thống RTK.

## Files

- `online_users_api.php` - File API chính
- `test_final_online_users.php` - File test API
- `test_final_result.txt` - Kết quả test

## Cấu hình

API sử dụng các constants từ `config.php`:
- `RTK_API_URL` - Base URL của RTK API
- `RTK_API_ACCESS_KEY` - Access key để xác thực
- `RTK_API_SECRET_KEY` - Secret key để tạo signature
- `RTK_API_SIGN_METHOD` - Phương thức mã hóa (HmacSHA256)

## Sử dụng

### 1. Lấy danh sách online users

```php
require_once __DIR__ . '/path/to/online_users_api.php';

// Lấy 100 users đầu tiên
$result = getRtkOnlineUsers(1, 100);

if ($result['success']) {
    $data = $result['data'];
    // Xử lý data...
} else {
    echo "Error: " . $result['error'];
}
```

### 2. Parse dữ liệu

```php
$parsedData = parseOnlineUsersData($result);

if ($parsedData['success']) {
    echo "Total users: " . $parsedData['total'];
    echo "Records: " . count($parsedData['records']);
    
    foreach ($parsedData['records'] as $record) {
        echo $record['userName'];
        echo $record['mountName'];
        // ...
    }
}
```

### 3. Tìm kiếm user theo username

```php
$records = $parsedData['records'];
$user = findUserByUsername($records, 'HNI015');

if ($user) {
    echo "Found: " . $user['userName'];
}
```

### 4. Lọc theo status

```php
$activeUsers = filterUsersByStatus($records, 4);
echo "Active users: " . count($activeUsers);
```

### 5. Lấy thống kê

```php
$stats = getOnlineUsersStatistics($records);

echo "Total users: " . $stats['total_users'];
echo "Total bytes: " . $stats['total_send_bytes'];
echo "Average bytes: " . $stats['avg_send_bytes'];

// Thống kê theo status
foreach ($stats['by_status'] as $status => $count) {
    echo "Status $status: $count users";
}

// Thống kê theo mount
foreach ($stats['by_mount'] as $mount => $count) {
    echo "Mount $mount: $count users";
}
```

## Các hàm có sẵn

### `getRtkOnlineUsers($page, $size, $status, $useAuth)`

Lấy danh sách người dùng online từ API.

**Parameters:**
- `$page` (int) - Số trang (mặc định: 1)
- `$size` (int) - Số records mỗi trang (mặc định: 100000)
- `$status` (string) - Lọc theo status (mặc định: '')
- `$useAuth` (bool) - Sử dụng authentication (mặc định: true)

**Returns:**
```php
[
    'success' => true/false,
    'data' => [...], // Nếu success
    'error' => '...' // Nếu có lỗi
]
```

### `parseOnlineUsersData($apiResult)`

Parse dữ liệu từ kết quả API.

**Returns:**
```php
[
    'success' => true/false,
    'code' => 'SUCCESS',
    'message' => null,
    'total' => '37',
    'page' => '1',
    'size' => '100',
    'records' => [...]
]
```

### `findUserByUsername($records, $userName)`

Tìm user theo username.

**Returns:** Array của user hoặc null

### `filterUsersByStatus($records, $status)`

Lọc users theo status.

**Returns:** Array các users

### `getOnlineUsersStatistics($records)`

Tính toán thống kê.

**Returns:**
```php
[
    'total_users' => 37,
    'by_status' => [...],
    'by_mount' => [...],
    'total_send_bytes' => 123.45,
    'avg_send_bytes' => 3.34
]
```

## Cấu trúc dữ liệu Record

```php
[
    'id' => '155783',
    'channelId' => '67a83a87-1f9b-0001a985-af74ea6e',
    'userName' => 'HNI015',
    'casterName' => 'Geo_System',
    'mountName' => 'MienBac',
    'userAgent' => 'CGI-230/1.1.1',
    'connectTime' => 1762397150000,
    'status' => -1,
    'ggaAge' => null,
    'satCount' => 0,
    'sendBytes' => 0.014,
    'masterStationName' => null,
    'masterStationDistance' => null,
    'userIp' => '27.70.186.238:17814',
    'hdop' => null,
    'lat' => null,
    'lon' => null,
    'nvrsEngineId' => null
]
```

## Status codes

- `-1` - Unknown/disconnected
- `4` - Active
- `5` - Active with good signal
- (Các status khác có thể có trong hệ thống)

## Test

Chạy test:

```bash
php public/tools/test_final_online_users.php
```

Kết quả test mẫu:
- ✓ API hoạt động với Authentication
- Total: 37 users
- Records nhận được: 10 (với size=10)

## Lưu ý

1. **Authentication required**: API này yêu cầu authentication với HMAC signature
2. **Base URL**: Được lấy từ `RTK_API_URL` trong config và tự động trích xuất
3. **Port**: API online-users sử dụng cùng base URL với API users (port 8090)
4. **Rate limiting**: Cân nhắc implement cache hoặc rate limiting khi sử dụng production
5. **Error handling**: Luôn kiểm tra `$result['success']` trước khi xử lý data

## Ví dụ sử dụng thực tế

```php
// Lấy tất cả users online
$result = getRtkOnlineUsers(1, 100000);

if ($result['success']) {
    $parsed = parseOnlineUsersData($result);
    $stats = getOnlineUsersStatistics($parsed['records']);
    
    echo "Có {$stats['total_users']} người dùng online\n";
    
    // Hiển thị top 10 mount points
    arsort($stats['by_mount']);
    $top10 = array_slice($stats['by_mount'], 0, 10, true);
    
    foreach ($top10 as $mount => $count) {
        $pct = ($count / $stats['total_users']) * 100;
        echo sprintf("%s: %d users (%.2f%%)\n", $mount, $count, $pct);
    }
}
```
