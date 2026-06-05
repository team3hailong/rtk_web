# Class Mocqg - Xử lý mốc quốc gia

## Mô tả
Class `Mocqg` chứa các phương thức để xử lý thông tin mốc quốc gia, bao gồm tìm kiếm mốc theo vị trí, tính khoảng cách, và quản lý thông tin mốc.

## Vị trí
`private/classes/Mocqg.php`

## Các phương thức

### 1. findNearbyMocqg()
Tìm các mốc quốc gia trong bán kính từ một điểm.

**Cú pháp:**
```php
Mocqg::findNearbyMocqg(PDO $pdo, float $lat, float $lng, float $radius = 50)
```

**Parameters:**
- `$pdo` (PDO): Database connection
- `$lat` (float): Vĩ độ điểm tìm kiếm
- `$lng` (float): Kinh độ điểm tìm kiếm
- `$radius` (float): Bán kính tìm kiếm (km), mặc định 50

**Return:**
- `array`: Danh sách mốc tìm được, được sắp xếp theo khoảng cách

**Ví dụ:**
```php
$db = new Database();
$pdo = $db->getConnection();

$mocqg_list = Mocqg::findNearbyMocqg($pdo, 21.028511, 105.804817, 50);

foreach ($mocqg_list as $mocqg) {
    echo "{$mocqg['ten_moc']} - {$mocqg['distance_km']} km\n";
}
```

**Kết quả:**
```php
[
    [
        'id' => 1,
        'ten_moc' => 'Mốc 001',
        'lat' => 21.028511,
        'lng' => 105.804817,
        'status' => 1,
        'created_at' => '2025-10-23 17:23:42',
        'distance_km' => 0.00
    ],
    // ...
]
```

### 2. getAllActiveMocqg()
Lấy tất cả mốc quốc gia đang hoạt động.

**Cú pháp:**
```php
Mocqg::getAllActiveMocqg(PDO $pdo)
```

**Parameters:**
- `$pdo` (PDO): Database connection

**Return:**
- `array`: Danh sách tất cả mốc có status = 1

**Ví dụ:**
```php
$mocqg_list = Mocqg::getAllActiveMocqg($pdo);
echo "Tổng số mốc: " . count($mocqg_list);
```

### 3. getMocqgById()
Lấy thông tin một mốc theo ID.

**Cú pháp:**
```php
Mocqg::getMocqgById(PDO $pdo, int $id)
```

**Parameters:**
- `$pdo` (PDO): Database connection
- `$id` (int): ID của mốc

**Return:**
- `array|null`: Thông tin mốc hoặc null nếu không tìm thấy

**Ví dụ:**
```php
$mocqg = Mocqg::getMocqgById($pdo, 1);
if ($mocqg) {
    echo "Tên mốc: {$mocqg['ten_moc']}\n";
    echo "Tọa độ: {$mocqg['lat']}, {$mocqg['lng']}\n";
}
```

### 4. calculateDistance()
Tính khoảng cách giữa 2 điểm sử dụng công thức Haversine.

**Cú pháp:**
```php
Mocqg::calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2)
```

**Parameters:**
- `$lat1` (float): Vĩ độ điểm 1
- `$lng1` (float): Kinh độ điểm 1
- `$lat2` (float): Vĩ độ điểm 2
- `$lng2` (float): Kinh độ điểm 2

**Return:**
- `float`: Khoảng cách (km)

**Ví dụ:**
```php
// Tính khoảng cách từ Hà Nội đến Đà Nẵng
$distance = Mocqg::calculateDistance(21.028511, 105.804817, 16.047079, 108.206230);
echo "Khoảng cách: $distance km";
// Output: Khoảng cách: 608.87 km
```

## Công thức Haversine

Class sử dụng công thức Haversine để tính khoảng cách chính xác trên bề mặt cầu của Trái Đất:

```
d = 6371 * acos(
    cos(radians(lat1)) * cos(radians(lat2)) * 
    cos(radians(lng2) - radians(lng1)) + 
    sin(radians(lat1)) * sin(radians(lat2))
)
```

Trong đó:
- `d` = khoảng cách (km)
- `6371` = bán kính trung bình của Trái Đất (km)
- `lat1, lng1` = tọa độ điểm 1
- `lat2, lng2` = tọa độ điểm 2

## Validation

Class tự động validate input:
- Latitude: -90 đến 90
- Longitude: -180 đến 180
- Radius: 1 đến 1000 km

## Exception Handling

Class throw `InvalidArgumentException` khi:
- Tọa độ không hợp lệ
- Bán kính không hợp lệ
- Input không phải là số

## Sử dụng trong API

Xem file: `public/api/map/get_nearby_mocqg.php`

```php
require_once PROJECT_ROOT_PATH . '/private/classes/Mocqg.php';

$lat = $_GET['lat'];
$lng = $_GET['lng'];
$radius = $_GET['radius'] ?? 50;

$mocqg_list = Mocqg::findNearbyMocqg($pdo, $lat, $lng, $radius);

echo json_encode([
    'success' => true,
    'data' => $mocqg_list
]);
```

## Tối ưu hóa

- Sử dụng index trên cột `lat` và `long` để tăng tốc độ tìm kiếm
- Chỉ query mốc có `status = 1` (đang hoạt động)
- Sắp xếp kết quả theo khoảng cách tăng dần
- Format số thập phân để tránh floating point issues

## Testing

File test: `db/test_api_final.php`

```bash
php db/test_api_final.php
```
