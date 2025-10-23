document.addEventListener('DOMContentLoaded', function () {
    // --- KHỞI TẠO MAP VÀ CÁC THÀNH PHẦN CƠ BẢN ---
    const map = L.map('map').setView([16.0, 106.0], 6);
    const normalLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '© OpenStreetMap'});
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {attribution: '© Esri'});
    normalLayer.addTo(map);

    let circles = [];
    let userLocationMarker = null, userLocationCircle = null;
    let enrichedStations = [];

    // --- BIẾN CHO RULER VÀ CƠ CHẾ "HÍT" ---
    const SNAP_DISTANCE_PIXELS = 20;
    let isRulerModeActive = false;
    let rulerStartPoint = null;
    let rulerLine = null, rulerStartMarker = null, rulerEndMarker = null, rulerTooltip = null;
    let measurementMarkers = []; // Lưu tất cả markers và lines khi đo nhiều trạm
    
    // Lấy các thành phần DOM của popup
    const rulerPopup = document.getElementById('ruler-choice-popup');
    const btnRulerFromCurrent = document.getElementById('ruler-from-current');
    const btnRulerFromInput = document.getElementById('ruler-from-input');
    const btnRulerCancel = document.getElementById('ruler-cancel');
    
    // Popup nhập tọa độ/địa chỉ
    const coordinatePopup = document.getElementById('coordinate-input-popup');
    const addressInput = document.getElementById('address-input');
    const latInput = document.getElementById('lat-input');
    const lngInput = document.getElementById('lng-input');
    const btnSearchAddress = document.getElementById('search-address-btn');
    const btnUseCoordinates = document.getElementById('use-coordinates-btn');
    const btnCoordinateCancel = document.getElementById('coordinate-cancel');

    // --- CÁC HÀM TIỆN ÍCH CHO RULER ---
    function clearRulerVisuals() {
        if (rulerStartMarker) map.removeLayer(rulerStartMarker);
        if (rulerEndMarker) map.removeLayer(rulerEndMarker);
        if (rulerLine) map.removeLayer(rulerLine);
        if (rulerTooltip) map.removeLayer(rulerTooltip);
        rulerStartMarker = rulerEndMarker = rulerLine = rulerTooltip = rulerStartPoint = null;
        
        // Xóa tất cả measurement markers
        measurementMarkers.forEach(item => {
            if (item.marker) map.removeLayer(item.marker);
            if (item.line) map.removeLayer(item.line);
            if (item.tooltip) map.removeLayer(item.tooltip);
        });
        measurementMarkers = [];
    }

    function activateRulerMode(startLatLng = null) {
        rulerPopup.classList.add('hidden');
        clearRulerVisuals();
        isRulerModeActive = true;
        map.getContainer().style.cursor = 'crosshair';
        map.getContainer().classList.add('ruler-mode-active');

        if (startLatLng) {
            startRuler(startLatLng);
        } else {
            map.once('click', e => {
                if (isRulerModeActive) startRuler(e.latlng);
            });
        }
    }

    function deactivateRulerMode() {
        isRulerModeActive = false;
        map.getContainer().style.cursor = '';
        map.getContainer().classList.remove('ruler-mode-active');
        map.off('mousemove', handleRulerMove);
        clearRulerVisuals();
    }
    
    function startRuler(latlng) {
        rulerStartPoint = latlng;
        rulerStartMarker = L.marker(latlng, { icon: L.divIcon({ className: 'ruler-marker', html: '📍' }) }).addTo(map);
        rulerLine = L.polyline([latlng], { color: '#f06292', weight: 3, dashArray: '5, 10' }).addTo(map);
        rulerTooltip = L.tooltip({ permanent: true, className: 'ruler-tooltip' }).setLatLng(latlng).setContent('Bắt đầu đo').addTo(map);
        map.on('mousemove', handleRulerMove);
        map.once('click', handleRulerEnd);
    }
    
    function findClosestStationToSnap(mouseLatLng) {
        let closestStation = null;
        let minDistance = Infinity;
        const mousePoint = map.latLngToContainerPoint(mouseLatLng);
        enrichedStations.forEach(station => {
            const stationPoint = map.latLngToContainerPoint(station._latlng);
            const distance = mousePoint.distanceTo(stationPoint);
            if (distance < SNAP_DISTANCE_PIXELS && distance < minDistance) {
                minDistance = distance;
                closestStation = station;
            }
        });
        return closestStation;
    }

    function handleRulerMove(e) {
        if (!rulerStartPoint) return;
        let currentLatLng = e.latlng; let isSnapped = false;
        const snappedStation = findClosestStationToSnap(e.latlng);
        if (snappedStation) { currentLatLng = snappedStation._latlng; isSnapped = true; }
        const dist = rulerStartPoint.distanceTo(currentLatLng);
        rulerLine.setLatLngs([rulerStartPoint, currentLatLng]);
        rulerTooltip.setLatLng(currentLatLng).setContent(dist < 1000 ? `${dist.toFixed(0)} m` : `${(dist / 1000).toFixed(2)} km`);
        const tooltipElement = rulerTooltip.getElement(); const lineElement = rulerLine.getElement();
        if (isSnapped) { tooltipElement.classList.add('snapped'); lineElement.classList.add('snapped');
        } else { tooltipElement.classList.remove('snapped'); lineElement.classList.remove('snapped'); }
    }

    function handleRulerEnd(e) {
        const snappedStation = findClosestStationToSnap(e.latlng);
        const endLatLng = snappedStation ? snappedStation._latlng : e.latlng;
        rulerEndMarker = L.marker(endLatLng, { icon: L.divIcon({ className: 'ruler-marker', html: '🏁' }) }).addTo(map);
        const dist = rulerStartPoint.distanceTo(endLatLng);
        rulerLine.setLatLngs([rulerStartPoint, endLatLng]);
        rulerTooltip.setLatLng(endLatLng).setContent(dist < 1000 ? `${dist.toFixed(0)} m` : `${(dist / 1000).toFixed(2)} km`);
        deactivateRulerMode();
    }
    
    // Hàm tìm N trạm gần nhất
    function findNearestStations(fromLatLng, count = 3) {
        const stationsWithDistance = enrichedStations.map(station => {
            const distance = fromLatLng.distanceTo(station._latlng);
            return { ...station, distance };
        });
        
        // Sắp xếp theo khoảng cách và lấy N trạm đầu tiên
        stationsWithDistance.sort((a, b) => a.distance - b.distance);
        return stationsWithDistance.slice(0, count);
    }
    
    // Hàm đo khoảng cách đến 3 trạm gần nhất
    function measureToNearestStations(startLatLng) {
        // Xóa các marker cũ
        clearRulerVisuals();
        
        // Tạo marker điểm bắt đầu (vị trí hiện tại)
        rulerStartPoint = startLatLng;
        rulerStartMarker = L.marker(startLatLng, { 
            icon: L.divIcon({ 
                className: 'ruler-marker', 
                html: '📍',
                iconSize: [30, 30]
            }) 
        }).addTo(map).bindPopup('<b>Vị trí của bạn</b>');
        
        // Tìm 3 trạm gần nhất
        const nearestStations = findNearestStations(startLatLng, 3);
        
        if (nearestStations.length === 0) {
            alert('Không tìm thấy trạm nào!');
            return;
        }
        
        // Màu sắc cho 3 trạm
        const colors = ['#e74c3c', '#3498db', '#2ecc71']; // Đỏ, Xanh dương, Xanh lá
        const labels = ['🥇', '🥈', '🥉']; // Huy chương vàng, bạc, đồng
        
        let allPoints = [startLatLng];
        
        nearestStations.forEach((station, index) => {
            const endLatLng = station._latlng;
            allPoints.push(endLatLng);
            
            // Tạo marker cho trạm
            const marker = L.marker(endLatLng, { 
                icon: L.divIcon({ 
                    className: 'ruler-marker station-marker', 
                    html: labels[index],
                    iconSize: [30, 30]
                }) 
            }).addTo(map);
            
            const stationName = station.mountpoint || station.station_name || 'Trạm';
            const distanceText = station.distance < 1000 
                ? `${station.distance.toFixed(0)} m` 
                : `${(station.distance / 1000).toFixed(2)} km`;
            
            marker.bindPopup(`
                <div style="min-width: 200px;">
                    <b>${labels[index]} Trạm #${index + 1}</b><br>
                    <b>${stationName}</b><br>
                    Khoảng cách: <b>${distanceText}</b><br>
                    Trạng thái: ${station.status == 1 ? '✅ Hoạt động' : '❌ Không hoạt động'}
                </div>
            `);
            
            // Vẽ đường thẳng
            const line = L.polyline([startLatLng, endLatLng], { 
                color: colors[index], 
                weight: 3,
                opacity: 0.7,
                dashArray: index === 0 ? '' : '10, 5' // Trạm đầu tiên là nét liền
            }).addTo(map);
            
            // Hiển thị tooltip ở điểm giữa
            const midPoint = L.latLng(
                (startLatLng.lat + endLatLng.lat) / 2,
                (startLatLng.lng + endLatLng.lng) / 2
            );
            
            const tooltip = L.tooltip({ 
                permanent: true, 
                className: 'ruler-tooltip',
                direction: 'center'
            }).setLatLng(midPoint).setContent(
                `${labels[index]} ${distanceText}`
            ).addTo(map);
            
            // Lưu vào mảng để có thể xóa sau
            measurementMarkers.push({ marker, line, tooltip });
        });
        
        // Fit map để hiển thị tất cả các điểm
        map.fitBounds(allPoints, { padding: [50, 50] });
        
        // Hiển thị popup cho trạm gần nhất
        if (measurementMarkers[0] && measurementMarkers[0].marker) {
            setTimeout(() => {
                measurementMarkers[0].marker.openPopup();
            }, 500);
        }
    }
    
    // Hàm đo khoảng cách từ điểm đã chọn đến 3 trạm gần nhất
    function measureFromPointToNearestStations(pointLatLng, pointLabel = 'Điểm đã chọn') {
        // Xóa các marker cũ
        clearRulerVisuals();
        
        // Tạo marker cho điểm đã chọn
        rulerStartPoint = pointLatLng;
        rulerStartMarker = L.marker(pointLatLng, { 
            icon: L.divIcon({ 
                className: 'ruler-marker', 
                html: '📍',
                iconSize: [30, 30]
            }) 
        }).addTo(map).bindPopup(`<b>${pointLabel}</b>`);
        
        // Tìm 3 trạm gần nhất
        const nearestStations = findNearestStations(pointLatLng, 3);
        
        if (nearestStations.length === 0) {
            alert('Không tìm thấy trạm nào!');
            return;
        }
        
        // Màu sắc cho 3 trạm
        const colors = ['#e74c3c', '#3498db', '#2ecc71']; // Đỏ, Xanh dương, Xanh lá
        const labels = ['🥇', '🥈', '🥉']; // Huy chương vàng, bạc, đồng
        
        let allPoints = [pointLatLng];
        
        nearestStations.forEach((station, index) => {
            const endLatLng = station._latlng;
            allPoints.push(endLatLng);
            
            // Tạo marker cho trạm
            const marker = L.marker(endLatLng, { 
                icon: L.divIcon({ 
                    className: 'ruler-marker station-marker', 
                    html: labels[index],
                    iconSize: [30, 30]
                }) 
            }).addTo(map);
            
            const stationName = station.mountpoint || station.station_name || 'Trạm';
            const distanceText = station.distance < 1000 
                ? `${station.distance.toFixed(0)} m` 
                : `${(station.distance / 1000).toFixed(2)} km`;
            
            marker.bindPopup(`
                <div style="min-width: 200px;">
                    <b>${labels[index]} Trạm #${index + 1}</b><br>
                    <b>${stationName}</b><br>
                    Khoảng cách: <b>${distanceText}</b><br>
                    Trạng thái: ${station.status == 1 ? '✅ Hoạt động' : '❌ Không hoạt động'}
                </div>
            `);
            
            // Vẽ đường thẳng
            const line = L.polyline([pointLatLng, endLatLng], { 
                color: colors[index], 
                weight: 3,
                opacity: 0.7,
                dashArray: index === 0 ? '' : '10, 5' // Trạm đầu tiên là nét liền
            }).addTo(map);
            
            // Hiển thị tooltip ở điểm giữa
            const midPoint = L.latLng(
                (pointLatLng.lat + endLatLng.lat) / 2,
                (pointLatLng.lng + endLatLng.lng) / 2
            );
            
            const tooltip = L.tooltip({ 
                permanent: true, 
                className: 'ruler-tooltip',
                direction: 'center'
            }).setLatLng(midPoint).setContent(
                `${labels[index]} ${distanceText}`
            ).addTo(map);
            
            // Lưu vào mảng để có thể xóa sau
            measurementMarkers.push({ marker, line, tooltip });
        });
        
        // Fit map để hiển thị tất cả các điểm
        map.fitBounds(allPoints, { padding: [50, 50] });
        
        // Hiển thị popup cho trạm gần nhất
        if (measurementMarkers[0] && measurementMarkers[0].marker) {
            setTimeout(() => {
                measurementMarkers[0].marker.openPopup();
            }, 500);
        }
    }
    
    // --- HÀM XỬ LÝ GEOCODING (CHUYỂN ĐỊA CHỈ THÀNH TỌA ĐỘ) ---
    async function geocodeAddress(address) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
            const data = await response.json();
            if (data && data.length > 0) {
                return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon), display_name: data[0].display_name };
            }
            return null;
        } catch (error) {
            console.error('Geocoding error:', error);
            return null;
        }
    }

    // --- GẮN SỰ KIỆN CHO TẤT CẢ CÁC NÚT ---
    document.getElementById('calculateDistance').addEventListener('click', () => { 
        isRulerModeActive ? deactivateRulerMode() : rulerPopup.classList.remove('hidden'); 
    });
    
    btnRulerFromCurrent.addEventListener('click', () => { 
        rulerPopup.classList.add('hidden');
        navigator.geolocation.getCurrentPosition(pos => {
            const currentLatLng = L.latLng(pos.coords.latitude, pos.coords.longitude);
            // Đo luôn đến 3 trạm gần nhất
            measureToNearestStations(currentLatLng);
        }, () => alert('Không thể lấy vị trí. Vui lòng cấp quyền.')); 
    });
    
    btnRulerFromInput.addEventListener('click', () => {
        rulerPopup.classList.add('hidden');
        coordinatePopup.classList.remove('hidden');
    });
    
    btnRulerCancel.addEventListener('click', () => { 
        rulerPopup.classList.add('hidden'); 
    });
    
    // Xử lý tìm kiếm địa chỉ
    btnSearchAddress.addEventListener('click', async () => {
        const address = addressInput.value.trim();
        if (!address) {
            alert('Vui lòng nhập địa chỉ!');
            return;
        }
        
        btnSearchAddress.textContent = 'Đang tìm...';
        btnSearchAddress.disabled = true;
        
        const result = await geocodeAddress(address);
        
        btnSearchAddress.textContent = 'Tìm kiếm';
        btnSearchAddress.disabled = false;
        
        if (result) {
            coordinatePopup.classList.add('hidden');
            addressInput.value = ''; // Reset input
            const latlng = L.latLng(result.lat, result.lng);
            // Đo đến 3 trạm gần nhất từ địa chỉ tìm được
            measureFromPointToNearestStations(latlng, result.display_name || 'Địa chỉ đã chọn');
        } else {
            alert('Không tìm thấy địa chỉ. Vui lòng thử lại với địa chỉ khác.');
        }
    });
    
    // Xử lý nhập tọa độ trực tiếp
    btnUseCoordinates.addEventListener('click', () => {
        const lat = parseFloat(latInput.value.trim());
        const lng = parseFloat(lngInput.value.trim());
        
        if (isNaN(lat) || isNaN(lng)) {
            alert('Vui lòng nhập tọa độ hợp lệ!\nVí dụ: Latitude: 21.0285, Longitude: 105.8542');
            return;
        }
        
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            alert('Tọa độ không hợp lệ!\nLatitude phải từ -90 đến 90\nLongitude phải từ -180 đến 180');
            return;
        }
        
        coordinatePopup.classList.add('hidden');
        latInput.value = ''; // Reset input
        lngInput.value = ''; // Reset input
        const latlng = L.latLng(lat, lng);
        // Đo đến 3 trạm gần nhất từ tọa độ đã nhập
        measureFromPointToNearestStations(latlng, `Tọa độ: ${lat.toFixed(4)}, ${lng.toFixed(4)}`);
    });
    
    btnCoordinateCancel.addEventListener('click', () => {
        coordinatePopup.classList.add('hidden');
        addressInput.value = '';
        latInput.value = '';
        lngInput.value = '';
    });
    
    // Hỗ trợ nhấn Enter để tìm kiếm
    addressInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') btnSearchAddress.click();
    });
    
    latInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') lngInput.focus();
    });
    
    lngInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') btnUseCoordinates.click();
    });

    document.getElementById('getCurrentLocation').addEventListener('click', function() {
        if (isRulerModeActive) deactivateRulerMode();
        this.textContent = "Đang tìm..."; this.disabled = true;
        navigator.geolocation.getCurrentPosition(pos => {
            const userLatLng = [pos.coords.latitude, pos.coords.longitude];
            if (userLocationMarker) map.removeLayer(userLocationMarker);
            if (userLocationCircle) map.removeLayer(userLocationCircle);
            const userIcon = L.divIcon({ className: 'user-location-marker', html: `<div style="background-color: #4285F4; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>`, iconSize: [24, 24], iconAnchor: [12, 12] });
            userLocationMarker = L.marker(userLatLng, {icon: userIcon}).addTo(map).bindPopup('<b>Vị trí của bạn</b>');
            userLocationCircle = L.circle(userLatLng, { radius: pos.coords.accuracy, weight: 1 }).addTo(map);
            map.setView(userLatLng, 15);
            this.textContent = "Vị trí của tôi"; this.disabled = false;
        }, () => {
            alert('Không thể lấy vị trí của bạn.');
            this.textContent = "Vị trí của tôi"; this.disabled = false;
        });
    });

    document.getElementById('toggleMapType').addEventListener('click', function() {
        if (isRulerModeActive) deactivateRulerMode();
        if (map.hasLayer(normalLayer)) {
            map.removeLayer(normalLayer); map.addLayer(satelliteLayer);
            this.textContent = "Bản đồ thường";
        } else {
            map.removeLayer(satelliteLayer); map.addLayer(normalLayer);
            this.textContent = "Bản đồ vệ tinh";
        }
    });
    
    // --- HIỂN THỊ CÁC TRẠM VÀ CÁC THÀNH PHẦN TĨNH ---
    const MIN_ZOOM_LABELS = 8, MAX_ZOOM_LABELS = 15;
    function updateLabelsZoomVisibility() { map.getContainer().classList.toggle('show-station-labels', map.getZoom() >= MIN_ZOOM_LABELS && map.getZoom() <= MAX_ZOOM_LABELS); }
    map.on('zoomend', updateLabelsZoomVisibility);
    
    enrichedStations = window.stationsData
        .filter(station => station.lat && station.long && station.status != 0 && station.status != -1)
        .map(station => {
            station._latlng = L.latLng(parseFloat(station.lat), parseFloat(station.long));
            return station;
        });

    enrichedStations.forEach(station => {
        let circleColor = '#3cb043';
        if (station.status == 3) circleColor = '#e74c3c';
        else if (window.userAccessibleStationsData.includes(station.id)) circleColor = '#3498db';
        const circle = L.circle(station._latlng, { radius: 20 * 1000, color: circleColor, fillColor: circleColor, fillOpacity: 0.3, weight: 1 }).addTo(map);
        circles.push(circle);
        circle.bindPopup(`<div><b>${station.mountpoint || station.station_name}</b><br>Trạng thái: ${station.status == 1 ? 'Hoạt động' : 'Không hoạt động'}</div>`);
        circle.bindTooltip(`<div>${station.mountpoint || station.station_name}</div>`, { permanent: true, direction: 'center', className: 'station-label-tooltip' });
    });

    // *** ĐOẠN CODE BỊ THIẾU ĐÃ ĐƯỢC THÊM LẠI Ở ĐÂY ***
    const islandLabel = (text) => L.divIcon({ className: 'island-label', html: `<div>${text}</div>`, iconSize: [150, 20], iconAnchor: [75, 10] });
    L.marker([16.5, 112.0], {icon: islandLabel('Quần đảo Hoàng Sa'), interactive: false}).addTo(map);
    L.marker([10.0, 114.0], {icon: islandLabel('Quần đảo Trường Sa'), interactive: false}).addTo(map);

    updateLabelsZoomVisibility();
});