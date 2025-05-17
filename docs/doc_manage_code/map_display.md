# Tài liệu Hệ thống Hiển thị Bản đồ (Map Display)

## 1. Chi tiết chức năng và trang

### Tổng quan
Trang Map Display là một tính năng quan trọng cho phép người dùng xem vị trí các trạm base RTK trên bản đồ. Hệ thống hiển thị thông tin về trạng thái hoạt động của các trạm và các trạm mà người dùng có quyền truy cập.

### Tệp tin chính
- **Tệp tin UI**: `public/pages/map_display.php`
- **Lớp xử lý dữ liệu**: `private/classes/Map.php`
- **JavaScript**: `public/assets/js/pages/map.js`
- **CSS**: `public/assets/css/pages/map.css`

### Chức năng chính
1. **Hiển thị bản đồ**: Sử dụng thư viện Leaflet để hiển thị bản đồ tương tác
2. **Hiển thị trạm base**: Hiển thị vị trí và phạm vi hoạt động của các trạm base trên bản đồ
3. **Phân biệt trạng thái trạm**: Các trạm được hiển thị với màu sắc khác nhau tùy theo trạng thái
4. **Định vị người dùng**: Cho phép định vị vị trí hiện tại của người dùng
5. **Chuyển đổi kiểu bản đồ**: Cho phép chuyển đổi giữa bản đồ thường và bản đồ vệ tinh

## 2. Điểm quan trọng hình thành chức năng

### 2.1. Thư viện Leaflet
Trang map_display sử dụng thư viện Leaflet (https://leafletjs.com/), một thư viện JavaScript mã nguồn mở cho bản đồ tương tác.

```javascript
// Khởi tạo bản đồ
const map = L.map('map').setView(initialCenter, initialZoom);
```

### 2.2. Lớp dữ liệu Map.php
Class Map bao gồm các phương thức quan trọng để lấy dữ liệu trạm base từ cơ sở dữ liệu.

```php
class Map {
    // Lấy tất cả các trạm từ cơ sở dữ liệu
    public static function getAllStations($pdo) {
        $query = "SELECT s.*, m.mountpoint, l.province 
                  FROM station s 
                  LEFT JOIN mount_point m ON s.mountpoint_id = m.id 
                  LEFT JOIN location l ON m.location_id = l.id
                  ORDER BY s.station_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lấy các trạm mà người dùng có quyền truy cập
    public static function getUserAccessibleStations($pdo, $user_id) {
        $user_accessible_stations = [];
        if (!$user_id) return $user_accessible_stations;
        $accessible_query = "SELECT DISTINCT s.id as station_id
                            FROM registration r
                            JOIN location l ON r.location_id = l.id
                            JOIN mount_point m ON m.location_id = l.id
                            JOIN station s ON s.mountpoint_id = m.id
                            WHERE r.user_id = :user_id
                            AND r.status = 'active'
                            AND r.end_time >= NOW()";
        $accessible_stmt = $pdo->prepare($accessible_query);
        $accessible_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $accessible_stmt->execute();
        while ($row = $accessible_stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_accessible_stations[] = $row['station_id'];
        }
        return $user_accessible_stations;
    }
}
```

### 2.3. Cấu trúc bảng liên quan trong cơ sở dữ liệu
- **station**: Lưu thông tin trạm base (id, station_name, lat, long, status,...)
- **mount_point**: Liên kết giữa trạm base và vị trí
- **location**: Thông tin vị trí (tỉnh, thành phố)
- **registration**: Đăng ký sử dụng dịch vụ của người dùng, liên kết với location

```sql
CREATE TABLE `station` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_name` varchar(100) NOT NULL,
  `mountpoint_id` int(11) DEFAULT NULL,
  `lat` decimal(10,6) DEFAULT NULL,
  `long` decimal(10,6) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '0:Inactive, 1:Active, 3:Error',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `mountpoint_id` (`mountpoint_id`),
  CONSTRAINT `station_ibfk_1` FOREIGN KEY (`mountpoint_id`) REFERENCES `mount_point` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2.4. Phân loại trạng thái trạm
Hệ thống phân loại trạm theo các trạng thái sau:
- **Trạng thái 1 (Xanh lá)**: Trạm đang hoạt động bình thường
- **Trạng thái 3 (Đỏ)**: Trạm không hoạt động hoặc có lỗi
- **Trạng thái 0**: Trạm bị tắt (không hiển thị trên bản đồ)
- **Trạm có quyền truy cập (Xanh dương)**: Các trạm mà người dùng có quyền truy cập

```javascript
// JavaScript hiển thị trạm với màu sắc phù hợp
let circleColor = '#3cb043'; // Default: Green for Status 1 (Hoạt động)
let isUserAccessible = userAccessibleStations.includes(station.id);
if (station.status == 3) {
    circleColor = '#e74c3c'; // Red for Status 3 (Không hoạt động)
} else if (isUserAccessible) {
    circleColor = '#3498db'; // Blue for stations user has access to
}
```

### 2.5. Hiển thị thông tin trạm
Mỗi trạm được hiển thị dưới dạng hình tròn với bán kính 15 km và có các thông tin như:
- Tên trạm/mountpoint
- Trạng thái hoạt động
- Thông tin người dùng có quyền truy cập hay không

```javascript
// Hiển thị popup với thông tin trạm
let popupContent = `<div><b>${station.mountpoint || station.station_name}</b><br>`;
popupContent += `Trạng thái: ${station.status == 1 ? 'Đang hoạt động' : (station.status == 3 ? 'Không hoạt động' : 'Không xác định')}<br>`;
popupContent += `${isUserAccessible ? '<span style=\"color:#3498db\">Bạn có quyền truy cập</span>' : ''}</div>`;
circle.bindPopup(popupContent);
```

## 3. Các luồng xử lý của chức năng

### 3.1. Luồng hiển thị bản đồ và trạm base
1. Trang `map_display.php` tải và khởi tạo các thành phần cần thiết
2. Lấy danh sách tất cả các trạm từ cơ sở dữ liệu qua phương thức `Map::getAllStations()`
3. Lấy danh sách các trạm mà người dùng có quyền truy cập qua `Map::getUserAccessibleStations()`
4. Truyền dữ liệu JSON về các trạm sang JavaScript
5. File JavaScript `map.js` khởi tạo bản đồ Leaflet và hiển thị các trạm với thông tin tương ứng
6. Đánh dấu các trạm bằng các vòng tròn với màu sắc khác nhau dựa trên trạng thái và quyền truy cập

```php
// PHP: Lấy dữ liệu trạm và truyền sang JavaScript
$stations = Map::getAllStations($pdo);
$user_accessible_stations = Map::getUserAccessibleStations($pdo, $current_user_id);
```

```javascript
// JavaScript: Xử lý và hiển thị trạm trên bản đồ
const stations = window.stationsData;
const userAccessibleStations = window.userAccessibleStationsData;
(stations.length ? stations : [{lat: initialCenter[0], long: initialCenter[1], mountpoint: 'HN', status: 1}]).forEach((station, index) => {
    // Skip stations with status 0 (Bị tắt)
    if (station.lat && station.long && station.status != 0 && station.status != -1) {
        const pos = [parseFloat(station.lat), parseFloat(station.long)];
        // Xác định màu sắc dựa trên trạng thái và quyền truy cập
        // Thêm vòng tròn và nhãn cho trạm
    }
});
```

### 3.2. Luồng chuyển đổi kiểu bản đồ
1. Người dùng nhấp vào nút "Bản đồ vệ tinh" hoặc "Bản đồ thường"
2. Sự kiện click được kích hoạt trên nút `toggleMapType`
3. JavaScript kiểm tra kiểu bản đồ hiện tại và chuyển đổi sang kiểu bản đồ khác
4. Cập nhật lại màu sắc và độ trong suốt của các vòng tròn để phù hợp với kiểu bản đồ mới

```javascript
// JavaScript: Chuyển đổi kiểu bản đồ
const toggleMapTypeBtn = document.getElementById('toggleMapType');
toggleMapTypeBtn.addEventListener('click', function() {
    if (isNormalMap) {
        // Chuyển từ bản đồ thường sang vệ tinh
        map.removeLayer(currentLayer);
        map.addLayer(satelliteLayer);
        currentLayer = satelliteLayer;
        this.textContent = "Bản đồ thường";
        updateCirclesColor(true); // Cập nhật màu cho bản đồ vệ tinh
        isNormalMap = false;
    } else {
        // Chuyển từ bản đồ vệ tinh sang bản đồ thường
        map.removeLayer(currentLayer);
        map.addLayer(normalLayer);
        currentLayer = normalLayer;
        this.textContent = "Bản đồ vệ tinh";
        updateCirclesColor(false); // Cập nhật màu cho bản đồ thường
        isNormalMap = true;
    }
});
```

### 3.3. Luồng định vị người dùng
1. Người dùng nhấp vào nút "Vị trí của tôi"
2. JavaScript kích hoạt API Geolocation để lấy vị trí hiện tại của người dùng
3. Khi nhận được vị trí thành công, hiển thị marker và vòng tròn độ chính xác trên bản đồ
4. Di chuyển bản đồ đến vị trí người dùng
5. Nếu có lỗi, hiển thị thông báo lỗi phù hợp

```javascript
// JavaScript: Định vị người dùng
document.getElementById('getCurrentLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
        this.textContent = "Đang tìm vị trí...";
        this.disabled = true;
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Xử lý khi nhận được vị trí thành công
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                const userPosition = [userLat, userLng];
                // Hiển thị marker và vòng tròn độ chính xác
                // Di chuyển bản đồ đến vị trí người dùng
            },
            function(error) {
                // Xử lý lỗi
                let errorMessage = '';
                switch(error.code) {
                    // Xác định loại lỗi và hiển thị thông báo
                }
                alert("Lỗi: " + errorMessage);
            },
            {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
        );
    } else {
        alert("Trình duyệt của bạn không hỗ trợ định vị vị trí.");
    }
});
```

## 4. Các lỗi có thể phát sinh và cách sửa

### 4.1. Lỗi hiển thị trạm
- **Triệu chứng**: Không hiển thị các trạm trên bản đồ
- **Nguyên nhân**: 
  - Dữ liệu trạm không được trả về từ cơ sở dữ liệu
  - Toạ độ lat/long của trạm không hợp lệ
  - Tất cả các trạm đều có trạng thái 0 (bị tắt)
- **Giải pháp**:
  - Kiểm tra truy vấn SQL trong `Map::getAllStations()`
  - Kiểm tra dữ liệu trong bảng `station`, đảm bảo các trường lat/long có giá trị hợp lệ
  - Kiểm tra trạng thái các trạm trong cơ sở dữ liệu

### 4.2. Lỗi không hiển thị trạm được truy cập
- **Triệu chứng**: Không hiển thị màu xanh dương cho các trạm mà người dùng có quyền truy cập
- **Nguyên nhân**: 
  - Phương thức `Map::getUserAccessibleStations()` không trả về dữ liệu chính xác
  - Thiếu dữ liệu đăng ký (`registration`) hoặc dữ liệu đã hết hạn
- **Giải pháp**:
  - Kiểm tra truy vấn SQL trong `Map::getUserAccessibleStations()`
  - Xác nhận rằng người dùng đã đăng nhập (`$current_user_id` không null)
  - Kiểm tra dữ liệu trong bảng `registration`, xác nhận rằng `status='active'` và `end_time >= NOW()`

### 4.3. Lỗi định vị người dùng
- **Triệu chứng**: Không thể xác định vị trí người dùng khi nhấn nút "Vị trí của tôi"
- **Nguyên nhân**: 
  - Người dùng từ chối quyền truy cập vị trí
  - Trình duyệt không hỗ trợ Geolocation API
  - Không thể xác định vị trí (ví dụ: trong nhà hoặc khu vực GPS yếu)
- **Giải pháp**:
  - Hiển thị hướng dẫn cho người dùng cách cấp quyền cho trình duyệt
  - Kiểm tra xem trình duyệt có hỗ trợ Geolocation API không (`if (navigator.geolocation)`)
  - Xử lý các trường hợp lỗi trong callback geolocation

### 4.4. Lỗi hiệu suất với nhiều trạm
- **Triệu chứng**: Hiệu suất bản đồ kém khi có nhiều trạm, tải trang chậm
- **Nguyên nhân**: 
  - Quá nhiều marker và hình tròn được tạo trên bản đồ
  - Cập nhật DOM quá thường xuyên
- **Giải pháp**:
  - Sử dụng các kỹ thuật như clustering để nhóm các trạm gần nhau
  - Chỉ hiển thị nhãn ở mức zoom nhất định (đã được triển khai)
  - Chỉ cập nhật UI khi cần thiết

```javascript
// Kỹ thuật hiển thị nhãn chỉ ở mức zoom nhất định
function updateLabelsVisibility() {
    const z = map.getZoom();
    labels.forEach(l => { l.getElement().style.display = (z >= MIN_ZOOM_LABELS && z <= MAX_ZOOM_LABELS) ? 'block' : 'none'; });
}
map.on('zoomend', updateLabelsVisibility);
```

## 5. Các dự kiến phát triển trong tương lai

### 5.1. Bộ lọc trạm
- Thêm chức năng lọc trạm theo tỉnh thành, trạng thái, quyền truy cập
- Cần bổ sung UI phần bộ lọc
- Cập nhật JavaScript để xử lý lọc và hiển thị trạm theo điều kiện

### 5.2. Cập nhật trạng thái trạm thời gian thực
- Sử dụng WebSocket hoặc Short Polling để cập nhật trạng thái trạm mà không cần tải lại trang
- Cần bổ sung API endpoint để lấy trạng thái trạm mới nhất
- Cập nhật JavaScript để cập nhật giao diện khi có thay đổi

```javascript
// Ví dụ cho cập nhật trạng thái trạm thời gian thực
function updateStationStatus() {
    fetch('/api/stations/status')
        .then(response => response.json())
        .then(data => {
            // Cập nhật màu sắc và thông tin cho các trạm
            data.forEach(station => {
                // Tìm trạm trong danh sách hiện tại và cập nhật
            });
        });
    
    // Gọi lại hàm sau 5 phút
    setTimeout(updateStationStatus, 5 * 60 * 1000);
}
```

### 5.3. Chức năng tìm đường đến trạm
- Tích hợp với dịch vụ định tuyến (như Google Directions API, MapBox)
- Cho phép người dùng nhận chỉ đường từ vị trí hiện tại đến trạm được chọn
- Hiển thị quãng đường và thời gian dự kiến

### 5.4. Xem thông tin chi tiết trạm
- Bổ sung trang thông tin chi tiết cho mỗi trạm khi nhấp vào
- Hiển thị lịch sử hoạt động, thống kê sử dụng
- Thông tin kỹ thuật về trạm

### 5.5. Thống kê hình ảnh
- Hiển thị bản đồ nhiệt (heat map) về mật độ trạm hoặc mức độ sử dụng
- Thêm biểu đồ thống kê về phạm vi phủ sóng
- Sử dụng các plugin của Leaflet như Leaflet Heat, Leaflet.markercluster

### 5.6. Giao diện người dùng cải tiến
- Thêm chế độ xem 3D cho bản đồ
- Hỗ trợ giao diện thiết bị di động tốt hơn
- Thêm tuỳ chọn tùy chỉnh (customize) cho người dùng (màu sắc, bán kính hiển thị, mức zoom mặc định)
