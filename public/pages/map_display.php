<?php
// --- Require file cấu hình - đã bao gồm các tiện ích đường dẫn ---
require_once dirname(dirname(__DIR__)) . '/private/config/config.php';

init_session();
// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

// --- Original requires ---
require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Map.php';

$db = new Database();
$pdo = $db->getConnection();
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$stations = Map::getAllStations($pdo);
$user_accessible_stations = Map::getUserAccessibleStations($pdo, $current_user_id);

include $project_root_path . '/private/includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/leaflet-fix.css">
<link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/pages/map.css" />

<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Bản đồ trạm đo đạc</h2>
        <p class="text-gray-600 mb-4">Trạng thái trạm sẽ được cập nhật liên tục mỗi 5 phút</p>
        <div id="map-container">
            <div id="map"><p>Đang tải bản đồ...</p></div>

            <!-- *** POPUP MỚI BẰNG HTML/CSS *** -->
            <div id="ruler-choice-popup" class="ruler-choice-popup hidden">
                <div class="popup-content">
                    <h4>Bắt đầu đo khoảng cách</h4>
                    <button id="ruler-from-current" class="ruler-btn">Dùng vị trí hiện tại</button>
                    <button id="ruler-from-input" class="ruler-btn">Nhập địa chỉ/tọa độ</button>
                    <button id="ruler-cancel" class="ruler-btn cancel-btn">Hủy</button>
                </div>
            </div>
            
            <!-- *** POPUP NHẬP TỌA ĐỘ/ĐỊA CHỈ *** -->
            <div id="coordinate-input-popup" class="ruler-choice-popup hidden">
                <div class="popup-content">
                    <h4>Nhập địa chỉ hoặc tọa độ</h4>
                    <div class="input-group">
                        <label>Địa chỉ:</label>
                        <input type="text" id="address-input" placeholder="VD: Hà Nội, Việt Nam" class="coordinate-input">
                        <button id="search-address-btn" class="ruler-btn">Tìm kiếm</button>
                    </div>
                    <div class="input-separator">HOẶC</div>
                    <div class="input-group">
                        <label>Tọa độ (Latitude, Longitude):</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="lat-input" placeholder="VD: 21.0285" class="coordinate-input" style="flex: 1;">
                            <input type="text" id="lng-input" placeholder="VD: 105.8542" class="coordinate-input" style="flex: 1;">
                        </div>
                        <button id="use-coordinates-btn" class="ruler-btn">Sử dụng tọa độ</button>
                    </div>
                    <button id="coordinate-cancel" class="ruler-btn cancel-btn">Hủy</button>
                </div>
            </div>
            
            <div class="map-controls">
                <button id="toggleMapType" class="map-type-btn active">Bản đồ vệ tinh</button>
                <button id="getCurrentLocation" class="map-type-btn">Vị trí của tôi</button>
                <button id="calculateDistance" class="map-type-btn">Tính khoảng cách</button>
            </div> 
            <div class="map-legend">
                <div class="legend-title">Chú thích:</div>
                <div class="legend-item"><div class="legend-color green-station"></div><div>Trạm đang hoạt động</div></div>
                <div class="legend-item"><div class="legend-color blue-station"></div><div>Trạm bạn có quyền truy cập</div></div>
                <div class="legend-item"><div class="legend-color red-station"></div><div>Trạm không hoạt động</div></div>
            </div>
        </div>
    </main>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
window.stationsData = <?php echo json_encode($stations); ?>;
window.userAccessibleStationsData = <?php echo json_encode($user_accessible_stations); ?>;
</script>
<script src="<?php echo $base_url . $base_path; ?>/assets/js/pages/map.js"></script>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';