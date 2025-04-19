<?php
session_start();

// Đường dẫn gốc thực tế trên server cho includes (An toàn hơn)
$project_root_path = dirname(dirname(__DIR__)); // Lùi lại hai cấp để đến gốc dự án (surveying_account)

// --- Bao gồm Header ---
// Sử dụng đường dẫn tuyệt đối dựa trên project_root_path
include $project_root_path . '/private/includes/header.php';
?>

<!-- Include Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

<!-- Nhúng CSS cho trang Map Display -->
<style>
    /* --- CSS Cụ thể cho Map Container --- */
    #map {
        height: 70vh; /* Chiều cao map */
        width: 100%;  /* Chiếm toàn bộ chiều rộng của content-wrapper */
        border-radius: var(--rounded-md); /* Bo góc nhẹ */
        border: 1px solid var(--gray-200); /* Viền nhẹ */
        background-color: var(--gray-100); /* Màu nền chờ load */
    }
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php
    // Bao gồm Sidebar - Sử dụng đường dẫn tuyệt đối
    include $project_root_path . '/private/includes/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <!-- Tiêu đề trang -->
        <h2 class="text-2xl font-semibold mb-6">Bản đồ trạm base</h2>

        <!-- Phần tử div để hiển thị bản đồ Leaflet -->
        <div id="map">
             <p style="text-align: center; padding-top: 50px; color: var(--gray-500);">
                Đang tải bản đồ...
            </p>
        </div>
    </main>
</div>

<!-- Include Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialCenter = [21.028511, 105.804817];
        const initialZoom = 13;
        const mapElement = document.getElementById('map');

        if (!mapElement) {
            console.error("Map container element with ID 'map' not found.");
            return;
        }

        try {
            const map = L.map('map').setView(initialCenter, initialZoom);
            // Use OpenStreetMap tile layer
            const tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

            L.tileLayer(tileUrl, {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const marker = L.marker(initialCenter).addTo(map);
            marker.bindPopup("<b>Hà Nội</b><br>Thủ đô Việt Nam.").openPopup();

        } catch (error) {
             console.error("Error initializing Leaflet Map:", error);
             if (mapElement) {
                 mapElement.innerHTML = '<p style="color: red; text-align: center;">Lỗi khi khởi tạo bản đồ. Vui lòng kiểm tra console.</p>';
             }
        }
    });
</script>

<?php
// --- Bao gồm Footer ---
// Sử dụng đường dẫn tuyệt đối
include $project_root_path . '/private/includes/footer.php';
?>