document.addEventListener('DOMContentLoaded', function () {
    const initialCenter = [16.0, 106.0];
    const initialZoom = 6;
    const radiusKm = 15;
    const MIN_ZOOM_LABELS = 7, MAX_ZOOM_LABELS = 12;
    let labels = [], circles = [];
    const map = L.map('map').setView(initialCenter, initialZoom);
    const normalLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'});
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {attribution: 'Tiles &copy; Esri', maxZoom: 19});
    normalLayer.addTo(map);
    let currentLayer = normalLayer;
    let userLocationMarker = null;
    let userLocationCircle = null;
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
                        case error.PERMISSION_DENIED:
                            errorMessage = "Bạn đã từ chối quyền truy cập vị trí."; break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Không thể xác định vị trí của bạn."; break;
                        case error.TIMEOUT:
                            errorMessage = "Yêu cầu vị trí đã hết thời gian."; break;
                        default:
                            errorMessage = "Đã xảy ra lỗi không xác định.";
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
    const stationInfo = new Map();
    function updateCirclesColor(isSatellite) {
        const opacity = isSatellite ? 0.5 : 0.3;
        circles.forEach((circle, index) => {
            if (stationInfo.has(index)) {
                const info = stationInfo.get(index);
                circle.setStyle({color: info.color, fillColor: info.color, fillOpacity: opacity});
            }
        });
    }
    function updateLabelsVisibility() {
        const z = map.getZoom();
        labels.forEach(l => { l.getElement().style.display = (z >= MIN_ZOOM_LABELS && z <= MAX_ZOOM_LABELS) ? 'block' : 'none'; });
    }
    map.on('zoomend', updateLabelsVisibility);
    const stations = window.stationsData;
    const userAccessibleStations = window.userAccessibleStationsData;
    (stations.length ? stations : [{lat: initialCenter[0], long: initialCenter[1], mountpoint: 'HN', status: 1}]).forEach((station, index) => {
        if (station.lat && station.long && station.status != -1) {
            const pos = [parseFloat(station.lat), parseFloat(station.long)];
            let circleColor = '#3cb043';
            let isUserAccessible = userAccessibleStations.includes(station.id);
            if (station.status == 0) {
                circleColor = '#e74c3c';
            } else if (isUserAccessible) {
                circleColor = '#3498db';
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
            popupContent += `Trạng thái: ${station.status == 1 ? 'Đang hoạt động' : (station.status == 0 ? 'Không hoạt động' : 'Không xác định')}<br>`;
            popupContent += `${isUserAccessible ? '<span style=\"color:#3498db\">Bạn có quyền truy cập</span>' : ''}</div>`;
            circle.bindPopup(popupContent);
            const label = L.divIcon({
                className: 'station-label', 
                html: `<div>${station.mountpoint || station.station_name}</div>`, 
                iconSize: [100, 20], 
                iconAnchor: [100, 10]
            });
            const labelMarker = L.marker(pos, {icon: label, interactive: false}).addTo(map);
            labels.push(labelMarker);
        }
    });
    updateLabelsVisibility();
    
    // Nhãn quần đảo
    const hoangSaCoords = [16.5, 112.0];
    const truongSaCoords = [10.0, 114.0];
    const hoangSaLabel = L.divIcon({
        className: 'island-label',
        html: '<div>Quần đảo Hoàng Sa</div>',
        iconSize: [150, 20],
        iconAnchor: [75, 10]
    });
    L.marker(hoangSaCoords, {icon: hoangSaLabel, interactive: false}).addTo(map);
    const truongSaLabel = L.divIcon({
        className: 'island-label',
        html: '<div>Quần đảo Trường Sa</div>',
        iconSize: [150, 20],
        iconAnchor: [75, 10]
    });
    L.marker(truongSaCoords, {icon: truongSaLabel, interactive: false}).addTo(map);
});
