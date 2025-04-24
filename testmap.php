<?php
session_start();
$project_root_path = dirname(dirname(__DIR__));
require_once $project_root_path . '/private/config/database.php';
require_once $project_root_path . '/private/classes/Database.php';
$db = new Database();
$pdo = $db->getConnection();
$stations = [];
$user_accessible_stations = [];

// Nếu người dùng đã đăng nhập
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    $query = "SELECT s.*, m.mountpoint, l.province 
              FROM station s 
              LEFT JOIN mount_point m ON s.mountpoint_id = m.id 
              LEFT JOIN location l ON m.location_id = l.id
              ORDER BY s.station_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh sách các trạm mà người dùng hiện tại có quyền truy cập
    if ($current_user_id) {
        $accessible_query = "SELECT DISTINCT s.id as station_id
                            FROM registration r
                            JOIN location l ON r.location_id = l.id
                            JOIN mount_point m ON m.location_id = l.id
                            JOIN station s ON s.mountpoint_id = m.id
                            WHERE r.user_id = :user_id
                            AND r.status = 'active'";
        $accessible_stmt = $pdo->prepare($accessible_query);
        $accessible_stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $accessible_stmt->execute();
        
        while ($row = $accessible_stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_accessible_stations[] = $row['station_id'];
        }
    }
} catch (PDOException $e) {
    error_log("Lỗi truy vấn dữ liệu trạm: " . $e->getMessage());
}
include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
<style>
#map {height: calc(100vh - 120px); width: 100%; border-radius: var(--rounded-md); border: 1px solid var(--gray-200); background-color: var(--gray-100);}
.map-controls {position: absolute; top: 10px; right: 10px; z-index: 1000; background: #fff; padding: 10px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
.map-type-btn {margin: 0 5px; padding: 6px 12px; border: 1px solid #ccc; background: #f5f5f5; cursor: pointer; border-radius: 3px;}
.map-type-btn.active {background: var(--primary-color); color: #fff; border-color: var(--primary-color);}
.station-label div {padding: 3px 5px; border-radius: 3px; font-size: 12px; white-space: nowrap; color: black; text-shadow: 1px 1px 2px white; background: none;}
/* CSS cho chú thích màu sắc */
.map-legend {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: white;
    padding: 10px;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    font-size: 12px;
}
.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}
.legend-color {
    width: 15px;
    height: 15px;
    border-radius: 50%;
    margin-right: 8px;
}
.green-station {
    background-color: #3cb043;
}
.blue-station {
    background-color: #3498db;
}
.red-station {
    background-color: #e74c3c;
}
/* CSS cho nhãn quần đảo */
.island-label div {
    padding: 3px 5px;
    border-radius: 3px;
    font-size: 13px; /* Kích thước lớn hơn một chút */
    font-weight: bold; /* In đậm */
    white-space: nowrap;
    color: #003366; /* Màu xanh đậm */
    text-shadow: 1px 1px 2px white, -1px -1px 2px white, 1px -1px 2px white, -1px 1px 2px white; /* Viền trắng */
    background: none;
}
</style>
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Bản đồ trạm base</h2>
        <p class="text-gray-600 mb-4">Trạng thái trạm sẽ được cập nhật liên tục mỗi 5 phút</p>
        <div id="map-container" style="position: relative;">
            <div id="map"><p style="text-align: center; padding-top: 50px; color: var(--gray-500);">Đang tải bản đồ...</p></div>
            <div class="map-controls">
                <button id="mapNormal" class="map-type-btn active">Bản đồ thường</button>
                <button id="mapSatellite" class="map-type-btn">Bản đồ vệ tinh</button>
                <button id="getCurrentLocation" class="map-type-btn" style="margin-top: 10px; background-color: #3498db; color: white;">Vị trí của tôi</button>
            </div>
            <div class="map-legend">
                <div class="legend-title" style="font-weight: bold; margin-bottom: 5px;">Chú thích:</div>
                <div class="legend-item">
                    <div class="legend-color green-station"></div>
                    <div>Trạm đang hoạt động</div>
                </div>
                <div class="legend-item">
                    <div class="legend-color blue-station"></div>
                    <div>Trạm bạn có quyền truy cập</div>
                </div>
                <div class="legend-item">
                    <div class="legend-color red-station"></div>
                    <div>Trạm không hoạt động</div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js" charset="utf-8"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tọa độ trung tâm Việt Nam
    const initialCenter = [16.0, 106.0]; 
    const initialZoom = 6; // Mức zoom phù hợp để thấy toàn bộ Việt Nam
    const radiusKm = 15;
    const MIN_ZOOM_LABELS = 7, MAX_ZOOM_LABELS = 12;
    let labels = [], circles = [];
    const map = L.map('map').setView(initialCenter, initialZoom);
    const normalLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'});
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {attribution: 'Tiles &copy; Esri', maxZoom: 19});
    normalLayer.addTo(map);
    let currentLayer = normalLayer;
    
    // Biến để lưu marker vị trí hiện tại của người dùng
    let userLocationMarker = null;
    let userLocationCircle = null;
    
    // Xử lý sự kiện click nút "Vị trí của tôi"
    document.getElementById('getCurrentLocation').addEventListener('click', function() {
        if (navigator.geolocation) {
            // Hiển thị thông báo đang tìm vị trí
            this.textContent = "Đang tìm vị trí...";
            this.disabled = true;
            
            // Xin quyền truy cập vị trí người dùng
            navigator.geolocation.getCurrentPosition(
                // Thành công
                function(position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const userPosition = [userLat, userLng];
                    const accuracy = position.coords.accuracy;
                    
                    // Xóa marker cũ nếu có
                    if (userLocationMarker) {
                        map.removeLayer(userLocationMarker);
                    }
                    if (userLocationCircle) {
                        map.removeLayer(userLocationCircle);
                    }
                    
                    // Tạo icon đặc biệt cho marker vị trí người dùng
                    const userIcon = L.divIcon({
                        className: 'user-location-marker',
                        html: `<div style="background-color: #4285F4; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });
                    
                    // Thêm marker vị trí người dùng
                    userLocationMarker = L.marker(userPosition, {icon: userIcon}).addTo(map);
                    userLocationMarker.bindPopup("<b>Vị trí của bạn</b><br>Độ chính xác: " + Math.round(accuracy) + " mét");
                    
                    // Thêm vòng tròn thể hiện độ chính xác
                    userLocationCircle = L.circle(userPosition, {
                        radius: accuracy,
                        color: '#4285F4',
                        fillColor: '#4285F4',
                        fillOpacity: 0.15,
                        weight: 1
                    }).addTo(map);
                    
                    // Di chuyển bản đồ đến vị trí người dùng
                    map.setView(userPosition, 15);
                    
                    // Cập nhật nút
                    document.getElementById('getCurrentLocation').textContent = "Vị trí của tôi";
                    document.getElementById('getCurrentLocation').disabled = false;
                },
                // Thất bại
                function(error) {
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "Bạn đã từ chối quyền truy cập vị trí.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Không thể xác định vị trí của bạn.";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "Yêu cầu vị trí đã hết thời gian.";
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMessage = "Đã xảy ra lỗi không xác định.";
                            break;
                    }
                    alert("Lỗi: " + errorMessage);
                    
                    // Cập nhật nút
                    document.getElementById('getCurrentLocation').textContent = "Vị trí của tôi";
                    document.getElementById('getCurrentLocation').disabled = false;
                },
                // Tùy chọn
                {
                    enableHighAccuracy: true, // Độ chính xác cao
                    timeout: 10000, // 10 giây
                    maximumAge: 0 // Không sử dụng cache
                }
            );
        } else {
            alert("Trình duyệt của bạn không hỗ trợ định vị vị trí.");
        }
    });
    
    document.getElementById('mapNormal').onclick = function() {
        if (currentLayer !== normalLayer) {
            map.removeLayer(currentLayer); 
            map.addLayer(normalLayer); 
            currentLayer = normalLayer;
            this.classList.add('active');
            document.getElementById('mapSatellite').classList.remove('active');
            updateCirclesColor(false);
        }
    };
    
    document.getElementById('mapSatellite').onclick = function() {
        if (currentLayer !== satelliteLayer) {
            map.removeLayer(currentLayer); 
            map.addLayer(satelliteLayer); 
            currentLayer = satelliteLayer;
            this.classList.add('active');
            document.getElementById('mapNormal').classList.remove('active');
            updateCirclesColor(true);
        }
    };
    // Lưu trữ thông tin trạm để phục vụ cho việc cập nhật màu sắc khi chuyển đổi bản đồ
    const stationInfo = new Map();
    
    function updateCirclesColor(isSatellite) {
        const opacity = isSatellite ? 0.5 : 0.3;
        circles.forEach((circle, index) => {
            if (stationInfo.has(index)) {
                const info = stationInfo.get(index);
                circle.setStyle({
                    color: info.color,
                    fillColor: info.color,
                    fillOpacity: opacity
                });
            }
        });
    }
    
    function updateLabelsVisibility() {
        const z = map.getZoom();
        labels.forEach(l => { l.getElement().style.display = (z >= MIN_ZOOM_LABELS && z <= MAX_ZOOM_LABELS) ? 'block' : 'none'; });
    }
    map.on('zoomend', updateLabelsVisibility);
    const stations = <?php echo json_encode($stations); ?>;
    const userAccessibleStations = <?php echo json_encode($user_accessible_stations); ?>;
    // Log danh sách trạm và trạng thái ra console để kiểm tra
    console.log('Danh sách trạm và trạng thái:', stations.map(s => ({name: s.station_name, status: s.status})));
    console.log('Trạm người dùng có quyền truy cập:', userAccessibleStations);
    (stations.length ? stations : [{lat: initialCenter[0], long: initialCenter[1], station_name: 'Hà Nội', status: 1}]).forEach((station, index) => {
        if (station.lat && station.long && station.status != -1 && station.status !== -1 && station.status !== '-1') {
            const pos = [parseFloat(station.lat), parseFloat(station.long)];
            
            // Kiểm tra quyền truy cập của người dùng và trạng thái trạm
            let circleColor = '#3cb043'; // Màu xanh lá (mặc định cho status = 1)
            let isUserAccessible = userAccessibleStations.includes(station.id);
            
            if (station.status == 0 || station.status === 0 || station.status === '0') {
                circleColor = '#e74c3c'; // Màu đỏ cho status = 0
            } 
            // Nếu người dùng có quyền truy cập vào trạm này, hiển thị màu xanh lam
            else if (isUserAccessible) {
                circleColor = '#3498db'; // Màu xanh lam cho trạm người dùng có quyền truy cập
            }
            
            // Lưu thông tin màu sắc của trạm vào stationInfo để sử dụng khi chuyển đổi loại bản đồ
            stationInfo.set(circles.length, {
                color: circleColor,
                isUserAccessible: isUserAccessible,
                status: station.status
            });
            
            const circle = L.circle(pos, {
                radius: radiusKm * 1000, 
                color: circleColor, 
                fillColor: circleColor, 
                fillOpacity: 0.3, 
                weight: 1
            }).addTo(map);
            
            circles.push(circle);
            
            // Thêm thông tin trạm vào pop-up khi click vào vòng tròn
            let popupContent = `<div><b>${station.station_name}</b><br>`;
            popupContent += `Trạng thái: ${station.status == 1 ? 'Đang hoạt động' : (station.status == 0 ? 'Không hoạt động' : 'Không xác định')}<br>`;
            popupContent += `${isUserAccessible ? '<span style="color:#3498db">Bạn có quyền truy cập</span>' : ''}</div>`;
            
            circle.bindPopup(popupContent);
            
            const label = L.divIcon({
                className: 'station-label', 
                html: `<div>${station.station_name}</div>`, 
                iconSize: [100, 20], 
                iconAnchor: [100, 10]
            });
            const labelMarker = L.marker(pos, {icon: label, interactive: false}).addTo(map);
            labels.push(labelMarker);
        }
    });
    updateLabelsVisibility();
    
    // Thêm nhãn cho quần đảo Hoàng Sa và Trường Sa
    const hoangSaCoords = [16.5, 112.0];
    const truongSaCoords = [10.0, 114.0]; // Tọa độ trung tâm gần đúng
    
    const hoangSaLabel = L.divIcon({
        className: 'island-label',
        html: '<div>Quần đảo Hoàng Sa</div>',
        iconSize: [150, 20],
        iconAnchor: [75, 10] // Căn giữa
    });
    L.marker(hoangSaCoords, {icon: hoangSaLabel, interactive: false}).addTo(map);
    
    const truongSaLabel = L.divIcon({
        className: 'island-label',
        html: '<div>Quần đảo Trường Sa</div>',
        iconSize: [150, 20],
        iconAnchor: [75, 10] // Căn giữa
    });
    L.marker(truongSaCoords, {icon: truongSaLabel, interactive: false}).addTo(map);
});
</script>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';
?>