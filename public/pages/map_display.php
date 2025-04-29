<?php
session_start();
// --- Base URL và Path (chuẩn như rtk_accountmanagement) ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['PHP_SELF']); // /pages
$base_project_dir = dirname($script_dir); // lùi 1 cấp
$base_url = rtrim($protocol . $domain . ($base_project_dir === '/' || $base_project_dir === '\\' ? '' : $base_project_dir), '/');
$project_root_path = dirname(dirname(__DIR__)); // lùi 2 cấp từ /pages -> project root
require_once $project_root_path . '/private/config/database.php';
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
<link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/pages/map.css" />
<div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Bản đồ trạm base</h2>
        <p class="text-gray-600 mb-4">Trạng thái trạm sẽ được cập nhật liên tục mỗi 5 phút</p>
        <div id="map-container">
            <div id="map"><p>Đang tải bản đồ...</p></div>
            <div class="map-controls">
                <button id="mapNormal" class="map-type-btn active">Bản đồ thường</button>
                <button id="mapSatellite" class="map-type-btn">Bản đồ vệ tinh</button>
                <button id="getCurrentLocation" class="map-type-btn">Vị trí của tôi</button>
            </div>
            <div class="map-legend">
                <div class="legend-title">Chú thích:</div>
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
window.stationsData = <?php echo json_encode($stations); ?>;
window.userAccessibleStationsData = <?php echo json_encode($user_accessible_stations); ?>;
const baseUrl = '<?php echo $base_url; ?>';
</script>
<script src="<?php echo $base_url; ?>/assets/js/pages/map.js"></script>
<?php
if (isset($db)) $db->close();
include $project_root_path . '/private/includes/footer.php';
?>