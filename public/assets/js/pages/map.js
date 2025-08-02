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
    
    // Lấy các thành phần DOM của popup
    const rulerPopup = document.getElementById('ruler-choice-popup');
    const btnRulerFromCurrent = document.getElementById('ruler-from-current');
    const btnRulerFromMap = document.getElementById('ruler-from-map');
    const btnRulerCancel = document.getElementById('ruler-cancel');

    // --- CÁC HÀM TIỆN ÍCH CHO RULER ---
    function clearRulerVisuals() {
        if (rulerStartMarker) map.removeLayer(rulerStartMarker);
        if (rulerEndMarker) map.removeLayer(rulerEndMarker);
        if (rulerLine) map.removeLayer(rulerLine);
        if (rulerTooltip) map.removeLayer(rulerTooltip);
        rulerStartMarker = rulerEndMarker = rulerLine = rulerTooltip = rulerStartPoint = null;
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
    
    // --- GẮN SỰ KIỆN CHO TẤT CẢ CÁC NÚT ---
    document.getElementById('calculateDistance').addEventListener('click', () => { isRulerModeActive ? deactivateRulerMode() : rulerPopup.classList.remove('hidden'); });
    btnRulerFromCurrent.addEventListener('click', () => { navigator.geolocation.getCurrentPosition(pos => activateRulerMode(L.latLng(pos.coords.latitude, pos.coords.longitude)), () => alert('Không thể lấy vị trí. Vui lòng cấp quyền.')); });
    btnRulerFromMap.addEventListener('click', () => { activateRulerMode(); });
    btnRulerCancel.addEventListener('click', () => { rulerPopup.classList.add('hidden'); });

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