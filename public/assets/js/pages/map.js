document.addEventListener('DOMContentLoaded', function () {
    const initialCenter = [16.0, 106.0];
    const initialZoom = 6;
    const radiusKm = 20;
    const MIN_ZOOM_LABELS = 8, MAX_ZOOM_LABELS = 15;
    
    // Mảng 'labels' không còn cần thiết nữa.
    let circles = [];
    const stationInfo = new Map();
    let stationsData = [];
    
    const map = L.map('map').setView(initialCenter, initialZoom);
    const normalLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'});
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {attribution: 'Tiles © Esri', maxZoom: 19});
    normalLayer.addTo(map);

    let currentLayer = normalLayer;
    let isNormalMap = true;
    let userLocationMarker = null;
    let userLocationCircle = null;
    
    // Cập nhật chức năng nút định vị
    document.getElementById('getCurrentLocation').addEventListener('click', function() {
        if (navigator.geolocation) {
            this.textContent = "Đang tìm vị trí...";
            this.disabled = true;
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const userPosition = [userLat, userLng];
                    const accuracy = position.coords.accuracy;
                    if (userLocationMarker) map.removeLayer(userLocationMarker);
                    if (userLocationCircle) map.removeLayer(userLocationCircle);
                    const userIcon = L.divIcon({
                        className: 'user-location-marker',
                        html: `<div style="background-color: #4285F4; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });
                    userLocationMarker = L.marker(userPosition, {icon: userIcon}).addTo(map);
                    userLocationMarker.bindPopup("<b>Vị trí của bạn</b><br>Độ chính xác: " + Math.round(accuracy) + " mét");
                    userLocationCircle = L.circle(userPosition, {
                        radius: accuracy,
                        color: '#4285F4',
                        fillColor: '#4285F4',
                        fillOpacity: 0.15,
                        weight: 1
                    }).addTo(map);
                    map.setView(userPosition, 15);
                    document.getElementById('getCurrentLocation').textContent = "Vị trí của tôi";
                    document.getElementById('getCurrentLocation').disabled = false;
                },
                function(error) {
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED: errorMessage = "Bạn đã từ chối quyền truy cập vị trí."; break;
                        case error.POSITION_UNAVAILABLE: errorMessage = "Không thể xác định vị trí của bạn."; break;
                        case error.TIMEOUT: errorMessage = "Yêu cầu vị trí đã hết thời gian."; break;
                        default: errorMessage = "Đã xảy ra lỗi không xác định.";
                    }
                    alert("Lỗi: " + errorMessage);
                    document.getElementById('getCurrentLocation').textContent = "Vị trí của tôi";
                    document.getElementById('getCurrentLocation').disabled = false;
                },
                {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
            );
        } else {
            alert("Trình duyệt của bạn không hỗ trợ định vị vị trí.");
        }
    });
    
    // Cập nhật chức năng nút chuyển đổi loại bản đồ
    const toggleMapTypeBtn = document.getElementById('toggleMapType');
    toggleMapTypeBtn.addEventListener('click', function() {
        if (isNormalMap) {
            map.removeLayer(currentLayer);
            map.addLayer(satelliteLayer);
            currentLayer = satelliteLayer;
            this.textContent = "Bản đồ thường";
            updateCirclesColor(true);
            isNormalMap = false;
        } else {
            map.removeLayer(currentLayer);
            map.addLayer(normalLayer);
            currentLayer = normalLayer;
            this.textContent = "Bản đồ vệ tinh";
            updateCirclesColor(false);
            isNormalMap = true;
        }
    });
    
    if (isNormalMap) {
        toggleMapTypeBtn.textContent = "Bản đồ vệ tinh";
    } else {
        toggleMapTypeBtn.textContent = "Bản đồ thường";
    }

    function updateCirclesColor(isSatellite) {
        const opacity = isSatellite ? 0.5 : 0.3;
        circles.forEach((circle, index) => {
            if (stationInfo.has(index)) {
                const info = stationInfo.get(index);
                circle.setStyle({color: info.color, fillColor: info.color, fillOpacity: opacity});
            }
        });
    }

    // --- SỬA ĐỔI QUAN TRỌNG ---
    // Hàm mới, hiệu quả hơn để quản lý việc hiển thị nhãn theo mức zoom
    function updateLabelsZoomVisibility() {
        const z = map.getZoom();
        const mapContainer = map.getContainer();
        // Dùng classList.toggle để thêm/xóa class một cách hiệu quả
        // CSS sẽ dựa vào class này để ẩn/hiện tất cả các nhãn cùng lúc
        mapContainer.classList.toggle('show-station-labels', z >= MIN_ZOOM_LABELS && z <= MAX_ZOOM_LABELS);
    }
    // Gắn sự kiện zoomend vào hàm mới
    map.on('zoomend', updateLabelsZoomVisibility);
    
    const stations = window.stationsData;
    stationsData = stations; // Lưu lại để dùng cho chức năng tính khoảng cách
    const userAccessibleStations = window.userAccessibleStationsData;

    (stations.length ? stations : [{lat: initialCenter[0], long: initialCenter[1], mountpoint: 'HN', status: 1}]).forEach((station, index) => {
        if (station.lat && station.long && station.status != 0 && station.status != -1) {
            const pos = [parseFloat(station.lat), parseFloat(station.long)];
            let circleColor = '#3cb043'; // Default: Green
            let isUserAccessible = userAccessibleStations.includes(station.id);
            if (station.status == 3) {
                circleColor = '#e74c3c'; // Red
            } else if (isUserAccessible) {
                circleColor = '#3498db'; // Blue
            }
            
            stationInfo.set(circles.length, {color: circleColor, isUserAccessible, status: station.status});
            
            const circle = L.circle(pos, {
                radius: radiusKm * 1000, 
                color: circleColor, 
                fillColor: circleColor, 
                fillOpacity: 0.3, 
                weight: 1
            }).addTo(map);
            circles.push(circle);

            let popupContent = `<div><b>${station.mountpoint || station.station_name}</b><br>`;
            popupContent += `Trạng thái: ${station.status == 1 ? 'Đang hoạt động' : (station.status == 3 ? 'Không hoạt động' : 'Không xác định')}<br>`;
            popupContent += `${isUserAccessible ? '<span style=\"color:#3498db\">Bạn có quyền truy cập</span>' : ''}</div>`;
            circle.bindPopup(popupContent);

            // --- THAY THẾ L.divIcon BẰNG L.tooltip ---
            // Gắn tooltip trực tiếp vào vòng tròn. Nó sẽ hoạt động như một nhãn.
            circle.bindTooltip(`<div>${station.mountpoint || station.station_name}</div>`, {
                permanent: true, // Luôn hiển thị (giống như nhãn)
                direction: 'center', // Hiển thị ở giữa
                className: 'station-label-tooltip', // Class CSS riêng để tùy chỉnh
                offset: [0, 0] // Không cần lệch đi so với tâm
            });
        }
    });

    // Gọi hàm một lần lúc khởi tạo để thiết lập trạng thái ban đầu
    updateLabelsZoomVisibility();
    
    // Nhãn quần đảo (giữ nguyên)
    const hoangSaCoords = [16.5, 112.0];
    const truongSaCoords = [10.0, 114.0];
    const hoangSaLabel = L.divIcon({ className: 'island-label', html: '<div>Quần đảo Hoàng Sa</div>', iconSize: [150, 20], iconAnchor: [75, 10] });
    L.marker(hoangSaCoords, {icon: hoangSaLabel, interactive: false}).addTo(map);
    const truongSaLabel = L.divIcon({ className: 'island-label', html: '<div>Quần đảo Trường Sa</div>', iconSize: [150, 20], iconAnchor: [75, 10] });
    L.marker(truongSaCoords, {icon: truongSaLabel, interactive: false}).addTo(map);
    
    // Hàm xác định vị trí tối ưu cho popup dựa trên kích thước màn hình
    function getOptimalPopupPosition(map) {
        // Kiểm tra nếu đang ở thiết bị di động (dựa trên chiều rộng màn hình)
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Trên thiết bị di động, hiển thị popup ở giữa và phía trên của bản đồ
            // để tránh bị che bởi map-controls ở dưới cùng
            const center = map.getCenter();
            const bounds = map.getBounds();
            const south = bounds.getSouth();
            const north = bounds.getNorth();
            
            // Đặt popup ở 1/3 phía trên bản đồ
            const lat = north - (north - south) * 0.35;
            return [lat, center.lng];
        } else {
            // Trên desktop, hiển thị ở giữa bản đồ
            return map.getCenter();
        }
    }
    
    // Chức năng tính khoảng cách đến trạm đã chọn
    const calculateDistanceBtn = document.getElementById('calculateDistance');
    if (calculateDistanceBtn) {
        calculateDistanceBtn.addEventListener('click', function() {
            // Tạo danh sách các trạm cho dropdown
            let stationOptions = '';
            stations.forEach((station, index) => {
                if (station.lat && station.long && station.status != 0 && station.status != -1) {
                    stationOptions += `<option value="${index}">${station.mountpoint || station.station_name}</option>`;
                }
            });
            
            // Tạo popup HTML
            const popupContent = `
                <div class="distance-calculator">
                    <h3>Tính khoảng cáchTính khoảng cách                     ${stationOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="custom-lat">Vĩ độ (Latitude):</label>
                        <input type="number" id="custom-lat" class="form-control" step="0.000001" placeholder="Ví dụ: 21.028511">
                    </div>
                    
                    <div class="form-group">
                        <label for="custom-lng">Kinh độ (Longitude):</label>
                        <input type="number" id="custom-lng" class="form-control" step="0.000001" placeholder="Ví dụ: 105.804817">
                    </div>
                    
                    <div class="form-group">
                        <button id="get-current-position" class="btn btn-secondary">Dùng vị trí hiện tại</button>
                    </div>
                    
                    <div class="form-group">
                        <button id="calculate-btn" class="btn btn-primary">Tính khoảng cách</button>
                    </div>
                    
                    <div id="result-container" style="display:none;">
                        <h4>Kết quả</h4>
                        <div id="distance-result"></div>
                    </div>
                </div>
            `;
            
            // Tạo popup và hiển thị
            const popup = L.popup({
                className: 'distance-calculator-popup',
                autoPanPadding: [10, 80], // Thêm padding khi tự động di chuyển bản đồ, đảm bảo không bị che bởi controls
                maxWidth: 350,
                closeOnClick: false // Giữ popup mở khi click vào map
            })
                .setLatLng(getOptimalPopupPosition(map))
                .setContent(popupContent);
                
            // Trước khi mở popup, gỡ bỏ sự kiện popupopen cũ nếu có
            map.off('popupopen');
            
            // Thêm sự kiện mới cho popup này
            popup.once('add', function() {
                setTimeout(function() {
                    // Nút lấy vị trí hiện tại
                    const getCurrentPositionBtn = document.getElementById('get-current-position');
                    if (getCurrentPositionBtn) {
                        // Xóa các event listener cũ nếu có
                        const newGetCurrentPositionBtn = getCurrentPositionBtn.cloneNode(true);
                        getCurrentPositionBtn.parentNode.replaceChild(newGetCurrentPositionBtn, getCurrentPositionBtn);
                        
                        newGetCurrentPositionBtn.addEventListener('click', function() {
                            if (navigator.geolocation) {
                                this.textContent = "Đang lấy vị trí...";
                                this.disabled = true;
                                
                                navigator.geolocation.getCurrentPosition(
                                    function(position) {
                                        document.getElementById('custom-lat').value = position.coords.latitude;
                                        document.getElementById('custom-lng').value = position.coords.longitude;
                                        newGetCurrentPositionBtn.textContent = "Dùng vị trí hiện tại";
                                        newGetCurrentPositionBtn.disabled = false;
                                    },
                                    function(error) {
                                        alert("Không thể lấy vị trí hiện tại. Lỗi: " + error.message);
                                        newGetCurrentPositionBtn.textContent = "Dùng vị trí hiện tại";
                                        newGetCurrentPositionBtn.disabled = false;
                                    },
                                    {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
                                );
                            } else {
                                alert("Trình duyệt của bạn không hỗ trợ định vị vị trí.");
                            }
                        });
                    }
                    
                    // Nút tính khoảng cách
                    const calculateBtn = document.getElementById('calculate-btn');
                    if (calculateBtn) {
                        // Xóa các event listener cũ nếu có
                        const newCalculateBtn = calculateBtn.cloneNode(true);
                        calculateBtn.parentNode.replaceChild(newCalculateBtn, calculateBtn);
                        
                        newCalculateBtn.addEventListener('click', function() {
                            // Hiển thị thông báo "đang tính"
                            const resultContainer = document.getElementById('result-container');
                            const distanceResult = document.getElementById('distance-result');
                            resultContainer.style.display = 'block';
                            distanceResult.innerHTML = `<p>Đang tính khoảng cách...</p>`;
                            
                            // Sử dụng setTimeout để tránh block UI
                            setTimeout(function() {
                                try {
                                    const stationSelect = document.getElementById('station-select');
                                    const customLat = parseFloat(document.getElementById('custom-lat').value);
                                    const customLng = parseFloat(document.getElementById('custom-lng').value);
                                    
                                    if (!stationSelect || !stationSelect.value || isNaN(customLat) || isNaN(customLng)) {
                                        alert("Vui lòng chọn trạm và nhập tọa độ hợp lệ.");
                                        resultContainer.style.display = 'none';
                                        return;
                                    }
                                    
                                    const selectedStation = stations[stationSelect.value];
                                    if (!selectedStation || !selectedStation.lat || !selectedStation.long) {
                                        alert("Không thể tìm thấy thông tin trạm đã chọn.");
                                        resultContainer.style.display = 'none';
                                        return;
                                    }
                                    
                                    const stationLat = parseFloat(selectedStation.lat);
                                    const stationLng = parseFloat(selectedStation.long);
                                    
                                    if (isNaN(stationLat) || isNaN(stationLng)) {
                                        alert("Tọa độ trạm không hợp lệ.");
                                        resultContainer.style.display = 'none';
                                        return;
                                    }
                                    
                                    // Tính khoảng cách đơn giản và nhanh chóng
                                    const distance = calculateSimpleDistance(customLat, customLng, stationLat, stationLng);
                                    
                                    // Thêm sai số ngẫu nhiên từ 1 đến 1.5km
                                    const randomError = (Math.random() * 0.5 + 1);
                                    const finalDistance = distance + randomError;
                                    
                                    // Hiển thị kết quả
                                    distanceResult.innerHTML = `
                                        <p>Khoảng cách từ vị trí<br>
                                        <strong>(${customLat.toFixed(5)}, ${customLng.toFixed(5)})</strong><br>
                                        đến trạm <strong>${selectedStation.mountpoint || selectedStation.station_name}</strong> là:</p>
                                        <h3>${finalDistance.toFixed(2)} km</h3>
                                    `;
                                } catch (error) {
                                    console.error("Lỗi tính khoảng cách:", error);
                                    distanceResult.innerHTML = `<p>Đã xảy ra lỗi khi tính khoảng cách: ${error.message}</p>`;
                                }
                            }, 50);
                        });
                    }
                }, 100); // Đợi một chút để DOM được cập nhật đầy đủ
            });
            
            // Mở popup
            popup.openOn(map);
        });
    }
    
    // Chức năng tìm trạm gần nhất
    const calculateNearestBtn = document.getElementById('calculateNearestDistance');
    if (calculateNearestBtn) {
        calculateNearestBtn.addEventListener('click', function() {
            // Tạo popup HTML
            const popupContent = `
                <div class="distance-calculator">
                    <h3>Tìm trạm gần nhất</h3>
                    <p><small>Hệ thống sẽ tự động tìm trạm gần nhất với vị trí của bạn</small></p>
                    
                    <div class="form-group">
                        <label for="nearest-custom-lat">Vĩ độ (Latitude):</label>
                        <input type="number" id="nearest-custom-lat" class="form-control" step="0.000001" placeholder="Ví dụ: 21.028511">
                    </div>
                    
                    <div class="form-group">
                        <label for="nearest-custom-lng">Kinh độ (Longitude):</label>
                        <input type="number" id="nearest-custom-lng" class="form-control" step="0.000001" placeholder="Ví dụ: 105.804817">
                    </div>
                    
                    <div class="form-group">
                        <button id="nearest-get-current-position" class="btn btn-secondary">Dùng vị trí hiện tại</button>
                    </div>
                    
                    <div class="form-group">
                        <button id="find-nearest-btn" class="btn btn-primary">Tìm trạm gần nhất</button>
                    </div>
                    
                    <div id="nearest-result-container" style="display:none;">
                        <h4>Kết quả</h4>
                        <div id="nearest-distance-result"></div>
                    </div>
                </div>
            `;
            
            // Tạo popup và hiển thị
            const popup = L.popup({
                className: 'distance-calculator-popup',
                autoPanPadding: [10, 80],
                maxWidth: 350,
                closeOnClick: false
            })
                .setLatLng(getOptimalPopupPosition(map))
                .setContent(popupContent);
                
            map.off('popupopen');
            
            popup.once('add', function() {
                setTimeout(function() {
                    // Nút lấy vị trí hiện tại
                    const getCurrentPositionBtn = document.getElementById('nearest-get-current-position');
                    if (getCurrentPositionBtn) {
                        const newGetCurrentPositionBtn = getCurrentPositionBtn.cloneNode(true);
                        getCurrentPositionBtn.parentNode.replaceChild(newGetCurrentPositionBtn, getCurrentPositionBtn);
                        
                        newGetCurrentPositionBtn.addEventListener('click', function() {
                            if (navigator.geolocation) {
                                this.textContent = "Đang lấy vị trí...";
                                this.disabled = true;
                                
                                navigator.geolocation.getCurrentPosition(
                                    function(position) {
                                        document.getElementById('nearest-custom-lat').value = position.coords.latitude;
                                        document.getElementById('nearest-custom-lng').value = position.coords.longitude;
                                        newGetCurrentPositionBtn.textContent = "Dùng vị trí hiện tại";
                                        newGetCurrentPositionBtn.disabled = false;
                                    },
                                    function(error) {
                                        alert("Không thể lấy vị trí hiện tại. Lỗi: " + error.message);
                                        newGetCurrentPositionBtn.textContent = "Dùng vị trí hiện tại";
                                        newGetCurrentPositionBtn.disabled = false;
                                    },
                                    {enableHighAccuracy: true, timeout: 10000, maximumAge: 0}
                                );
                            } else {
                                alert("Trình duyệt của bạn không hỗ trợ định vị vị trí.");
                            }
                        });
                    }
                    
                    // Nút tìm trạm gần nhất
                    const findNearestBtn = document.getElementById('find-nearest-btn');
                    if (findNearestBtn) {
                        const newFindNearestBtn = findNearestBtn.cloneNode(true);
                        findNearestBtn.parentNode.replaceChild(newFindNearestBtn, findNearestBtn);
                        
                        newFindNearestBtn.addEventListener('click', function() {
                            // Hiển thị thông báo "đang tìm"
                            const resultContainer = document.getElementById('nearest-result-container');
                            const distanceResult = document.getElementById('nearest-distance-result');
                            resultContainer.style.display = 'block';
                            distanceResult.innerHTML = `<p>Đang tìm trạm gần nhất...</p>`;
                            
                            // Sử dụng setTimeout để tránh block UI
                            setTimeout(function() {
                                try {
                                    const customLat = parseFloat(document.getElementById('nearest-custom-lat').value);
                                    const customLng = parseFloat(document.getElementById('nearest-custom-lng').value);
                                    
                                    if (isNaN(customLat) || isNaN(customLng)) {
                                        alert("Vui lòng nhập tọa độ hợp lệ.");
                                        resultContainer.style.display = 'none';
                                        return;
                                    }
                                    
                                    // Tìm trạm gần nhất
                                    let nearestStation = null;
                                    let shortestDistance = Infinity;
                                    
                                    stations.forEach((station, index) => {
                                        if (station.lat && station.long && station.status != 0 && station.status != -1) {
                                            const stationLat = parseFloat(station.lat);
                                            const stationLng = parseFloat(station.long);
                                            
                                            if (!isNaN(stationLat) && !isNaN(stationLng)) {
                                                const distance = calculateSimpleDistance(customLat, customLng, stationLat, stationLng);
                                                
                                                if (distance < shortestDistance) {
                                                    shortestDistance = distance;
                                                    nearestStation = station;
                                                }
                                            }
                                        }
                                    });
                                    
                                    if (!nearestStation) {
                                        distanceResult.innerHTML = `<p>Không tìm thấy trạm nào hoạt động gần vị trí của bạn.</p>`;
                                        return;
                                    }
                                    
                                    // Thêm sai số ngẫu nhiên từ 1 đến 1.5km
                                    const randomError = (Math.random() * 0.5 + 1);
                                    const finalDistance = shortestDistance + randomError;
                                    
                                    // Hiển thị kết quả
                                    distanceResult.innerHTML = `
                                        <p>Trạm gần nhất với vị trí<br>
                                        <strong>(${customLat.toFixed(5)}, ${customLng.toFixed(5)})</strong><br>
                                        là trạm <strong>${nearestStation.mountpoint || nearestStation.station_name}</strong>:</p>
                                        <h3>${finalDistance.toFixed(2)} km</h3>
                                    `;
                                    
                                } catch (error) {
                                    console.error("Lỗi tìm trạm gần nhất:", error);
                                    distanceResult.innerHTML = `<p>Đã xảy ra lỗi khi tìm trạm gần nhất: ${error.message}</p>`;
                                }
                            }, 50);
                        });
                    }
                }, 100);
            });
            
            // Mở popup
            popup.openOn(map);
        });
    }
    
    // Hàm tính khoảng cách nhanh và đơn giản hơn
    function calculateSimpleDistance(lat1, lon1, lat2, lon2) {
        // Hàm tính khoảng cách đơn giản, nhanh hơn công thức Haversine
        // Đủ chính xác cho các khoảng cách ngắn (dưới 1000km)
        const R = 6371; // Bán kính trái đất theo km
        const dLat = (lat2 - lat1) * Math.PI / 180;  
        const dLon = (lon2 - lon1) * Math.PI / 180;
        
        // Công thức đơn giản hóa từ Haversine
        const x = dLon * Math.cos((lat1 + lat2) * Math.PI / 360);
        const y = dLat;
        const d = Math.sqrt(x*x + y*y) * R;
        
        return d;
    }
    
    // Giữ lại hàm Haversine nếu cần dùng cho tính toán chính xác hơn
    function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Bán kính trái đất theo km
        const dLat = toRadians(lat2 - lat1);
        const dLon = toRadians(lon2 - lon1);
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * 
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Khoảng cách theo km
    }
    
    function toRadians(degrees) {
        return degrees * (Math.PI / 180);
    }
});