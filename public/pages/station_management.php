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

// Password protection - simple authentication
$station_password = 'taikhoandodac'; // Password để truy cập trang này

// Check if password is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['station_password'])) {
    if ($_POST['station_password'] === $station_password) {
        $_SESSION['station_access'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = 'Mật khẩu không đúng!';
    }
}

// Check if user has access
$has_access = isset($_SESSION['station_access']) && $_SESSION['station_access'] === true;

$page_title = 'Quản Lý Trạm NTRIP';

// Get database connection
$db = new Database();
$pdo = $db->getConnection();
$current_user_id = $_SESSION['user_id'];

// Get all stations
$stations = Map::getAllStations($pdo);

// Get auto schedule settings for all stations
$scheduleQuery = "SELECT station_id, auto_start, auto_stop, enabled FROM station_auto_schedule WHERE enabled = 1";
$scheduleStmt = $pdo->prepare($scheduleQuery);
$scheduleStmt->execute();
$scheduleSettings = [];
while ($row = $scheduleStmt->fetch(PDO::FETCH_ASSOC)) {
    $scheduleSettings[$row['station_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/main.css">
    <style>
        .station-management {
            padding: 20px;
            width: 100%;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-section h1 {
            margin: 0;
            color: #333;
        }
        
        .auto-schedule-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .schedule-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .schedule-item .status {
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .schedule-item .status.active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .controls-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-start {
            background-color: #28a745;
            color: white;
        }
        
        .btn-start:hover:not(:disabled) {
            background-color: #218838;
        }
        
        .btn-stop {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-stop:hover:not(:disabled) {
            background-color: #c82333;
        }
        
        .btn-select-all {
            background-color: #007bff;
            color: white;
        }
        
        .btn-select-all:hover {
            background-color: #0056b3;
        }
        
        .station-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .station-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .station-table thead {
            background-color: #f8f9fa;
        }
        
        .station-table th,
        .station-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .station-table th {
            font-weight: 600;
            color: #495057;
        }
        
        .station-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .station-table .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .station-table .actions-cell {
            width: 200px;
        }
        
        .auto-schedule-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #28a745;
        }
        
        .auto-schedule-checkbox:hover {
            transform: scale(1.1);
        }
        
        .actions-cell {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .station-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-running {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-stopped {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-unknown {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-spinner {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
            text-align: center;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        
        .toast.success {
            background-color: #28a745;
        }
        
        .toast.error {
            background-color: #dc3545;
        }
        
        .toast.info {
            background-color: #17a2b8;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <?php if (!$has_access): ?>
        <!-- Password Form -->
        <div style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f5f5f5;">
            <div style="background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 400px; width: 100%;">
                <h2 style="text-align: center; margin-bottom: 20px; color: #333;">🔒 Truy cập Quản lý Trạm</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">Vui lòng nhập mật khẩu để tiếp tục</p>
                
                <?php if (isset($error_message)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Mật khẩu:</label>
                        <input type="password" name="station_password" required 
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;"
                               placeholder="Nhập mật khẩu"
                               autofocus>
                    </div>
                    <button type="submit" 
                            style="width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 500; cursor: pointer; transition: background 0.3s;">
                        Truy cập
                    </button>
                </form>
                
                <p style="text-align: center; margin-top: 20px; color: #999; font-size: 13px;">
                    Mật khẩu sẽ được lưu trong session của bạn
                </p>
            </div>
        </div>
    <?php else: ?>
    <?php include $project_root_path . '/private/includes/header.php'; ?>
    
    <div class="dashboard-wrapper">
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>
    <main class="content-wrapper">
    
    <div class="station-management">
        <div class="header-section">
            <h1><?php echo $page_title; ?></h1>
            <div class="auto-schedule-info">
                <div class="schedule-item">
                    <span>🌅 Tự động Start:</span>
                    <span class="status active">6:00 AM</span>
                </div>
                <div class="schedule-item">
                    <span>🌙 Tự động Stop:</span>
                    <span class="status active">8:00 PM</span>
                </div>
            </div>
        </div>
        
        <div class="controls-section">
            <button class="btn btn-select-all" onclick="toggleSelectAll()">
                <span id="selectAllText">Chọn Tất Cả</span>
            </button>
            <button class="btn btn-start" onclick="batchStart()" id="batchStartBtn">
                ▶️ Start Hàng Loạt
            </button>
            <button class="btn btn-stop" onclick="batchStop()" id="batchStopBtn">
                ⏹️ Stop Hàng Loạt
            </button>
            <span id="selectedCount" style="margin-left: auto; color: #666;">
                Đã chọn: <strong>0</strong> trạm
            </span>
        </div>
        
        <div class="station-table-container">
            <table class="station-table">
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                        </th>
                        <th>ID</th>
                        <th>Tên Trạm</th>
                        <th>Mountpoint</th>
                        <th>Tỉnh/Thành</th>
                        <th>Trạng Thái</th>
                        <th style="text-align: center;">Auto Start</th>
                        <th style="text-align: center;">Auto Stop</th>
                        <th class="actions-cell">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stations)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: #999;">
                                Không có trạm nào
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stations as $station): 
                            $stationId = $station['id'];
                            $hasSchedule = isset($scheduleSettings[$stationId]);
                            $autoStart = $hasSchedule ? $scheduleSettings[$stationId]['auto_start'] : 0;
                            $autoStop = $hasSchedule ? $scheduleSettings[$stationId]['auto_stop'] : 0;
                        ?>
                            <tr data-station-id="<?php echo $stationId; ?>">
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="station-checkbox" 
                                           value="<?php echo $station['id']; ?>" 
                                           onchange="updateSelectedCount()">
                                </td>
                                <td><?php echo htmlspecialchars($station['id']); ?></td>
                                <td><?php echo htmlspecialchars($station['station_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($station['mountpoint'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($station['province'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $status = $station['status'] ?? -1;
                                    if ($status == 1) {
                                        echo '<span class="station-status status-running">✅ Đang chạy</span>';
                                    } elseif ($status == 0) {
                                        echo '<span class="station-status status-stopped">⏹️ Đã dừng</span>';
                                    } else {
                                        echo '<span class="station-status status-unknown">❓ Không rõ</span>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           class="auto-schedule-checkbox" 
                                           data-station-id="<?php echo $stationId; ?>"
                                           data-type="start"
                                           <?php echo $autoStart ? 'checked' : ''; ?>
                                           onchange="toggleAutoSchedule(<?php echo $stationId; ?>, 'start', this.checked)"
                                           title="Tự động start lúc 6:00 AM">
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           class="auto-schedule-checkbox" 
                                           data-station-id="<?php echo $stationId; ?>"
                                           data-type="stop"
                                           <?php echo $autoStop ? 'checked' : ''; ?>
                                           onchange="toggleAutoSchedule(<?php echo $stationId; ?>, 'stop', this.checked)"
                                           title="Tự động stop lúc 8:00 PM">
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn btn-start btn-sm" 
                                                onclick="startStation(<?php echo $station['id']; ?>)">
                                            ▶️ Start
                                        </button>
                                        <button class="btn btn-stop btn-sm" 
                                                onclick="stopStation(<?php echo $station['id']; ?>)">
                                            ⏹️ Stop
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div> <!-- End station-management -->
    
    </main> <!-- End content-wrapper -->
    </div> <!-- End dashboard-wrapper -->
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div>Đang xử lý...</div>
        </div>
    </div>
    
    <?php 
    if (isset($db)) $db->close();
    include $project_root_path . '/private/includes/footer.php'; 
    ?>
    
    <script>
        window.baseUrl = "<?php echo $base_url; ?>";
        window.basePath = "<?php echo $base_path; ?>";
        
        // Update selected count
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.station-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').innerHTML = `Đã chọn: <strong>${count}</strong> trạm`;
            
            // Update batch buttons state
            const batchStartBtn = document.getElementById('batchStartBtn');
            const batchStopBtn = document.getElementById('batchStopBtn');
            batchStartBtn.disabled = count === 0;
            batchStopBtn.disabled = count === 0;
        }
        
        // Toggle select all
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.station-checkbox');
            const allChecked = selectAllCheckbox.checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = allChecked;
            });
            
            updateSelectedCount();
            
            // Update button text
            document.getElementById('selectAllText').textContent = 
                allChecked ? 'Bỏ Chọn Tất Cả' : 'Chọn Tất Cả';
        }
        
        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Show/hide loading overlay
        function setLoading(isLoading) {
            const overlay = document.getElementById('loadingOverlay');
            if (isLoading) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }
        
        // Get selected station IDs
        function getSelectedStationIds() {
            const checkboxes = document.querySelectorAll('.station-checkbox:checked');
            return Array.from(checkboxes).map(cb => parseInt(cb.value));
        }
        
        // Start single station
        async function startStation(stationId) {
            if (!confirm('Bạn có chắc muốn start trạm này?')) {
                return;
            }
            
            setLoading(true);
            try {
                const response = await fetch(window.baseUrl + window.basePath + '/handlers/station_control.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'start',
                        station_ids: [stationId]
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Start trạm thành công!', 'success');
                    // Update status display
                    updateStationStatus(stationId, 1);
                } else {
                    showToast(result.message || 'Có lỗi xảy ra!', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Lỗi kết nối đến server!', 'error');
            } finally {
                setLoading(false);
            }
        }
        
        // Stop single station
        async function stopStation(stationId) {
            if (!confirm('Bạn có chắc muốn stop trạm này?')) {
                return;
            }
            
            setLoading(true);
            try {
                const response = await fetch(window.baseUrl + window.basePath + '/handlers/station_control.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'stop',
                        station_ids: [stationId]
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Stop trạm thành công!', 'success');
                    // Update status display
                    updateStationStatus(stationId, 0);
                } else {
                    showToast(result.message || 'Có lỗi xảy ra!', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Lỗi kết nối đến server!', 'error');
            } finally {
                setLoading(false);
            }
        }
        
        // Batch start stations
        async function batchStart() {
            const stationIds = getSelectedStationIds();
            
            if (stationIds.length === 0) {
                showToast('Vui lòng chọn ít nhất một trạm!', 'info');
                return;
            }
            
            if (!confirm(`Bạn có chắc muốn start ${stationIds.length} trạm đã chọn?`)) {
                return;
            }
            
            setLoading(true);
            try {
                const response = await fetch(window.baseUrl + window.basePath + '/handlers/station_control.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'batch_start',
                        station_ids: stationIds
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(`Start thành công ${stationIds.length} trạm!`, 'success');
                    // Update status for all stations
                    stationIds.forEach(id => updateStationStatus(id, 1));
                    // Clear selection
                    document.querySelectorAll('.station-checkbox:checked').forEach(cb => cb.checked = false);
                    document.getElementById('selectAllCheckbox').checked = false;
                    updateSelectedCount();
                } else {
                    showToast(result.message || 'Có lỗi xảy ra!', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Lỗi kết nối đến server!', 'error');
            } finally {
                setLoading(false);
            }
        }
        
        // Batch stop stations
        async function batchStop() {
            const stationIds = getSelectedStationIds();
            
            if (stationIds.length === 0) {
                showToast('Vui lòng chọn ít nhất một trạm!', 'info');
                return;
            }
            
            if (!confirm(`Bạn có chắc muốn stop ${stationIds.length} trạm đã chọn?`)) {
                return;
            }
            
            setLoading(true);
            try {
                const response = await fetch(window.baseUrl + window.basePath + '/handlers/station_control.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'batch_stop',
                        station_ids: stationIds
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(`Stop thành công ${stationIds.length} trạm!`, 'success');
                    // Update status for all stations
                    stationIds.forEach(id => updateStationStatus(id, 0));
                    // Clear selection
                    document.querySelectorAll('.station-checkbox:checked').forEach(cb => cb.checked = false);
                    document.getElementById('selectAllCheckbox').checked = false;
                    updateSelectedCount();
                } else {
                    showToast(result.message || 'Có lỗi xảy ra!', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Lỗi kết nối đến server!', 'error');
            } finally {
                setLoading(false);
            }
        }
        
        // Update station status display
        function updateStationStatus(stationId, status) {
            const row = document.querySelector(`tr[data-station-id="${stationId}"]`);
            if (!row) return;
            
            const statusCell = row.querySelector('.station-status');
            if (!statusCell) return;
            
            // Update status display
            statusCell.className = 'station-status';
            if (status === 1) {
                statusCell.className += ' status-running';
                statusCell.innerHTML = '✅ Đang chạy';
            } else if (status === 0) {
                statusCell.className += ' status-stopped';
                statusCell.innerHTML = '⏹️ Đã dừng';
            } else {
                statusCell.className += ' status-unknown';
                statusCell.innerHTML = '❓ Không rõ';
            }
        }
        
        // Toggle auto schedule for a station
        async function toggleAutoSchedule(stationId, type, enabled) {
            try {
                const response = await fetch(window.baseUrl + window.basePath + '/handlers/station_schedule_config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'toggle',
                        station_id: stationId,
                        type: type,
                        enabled: enabled
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const action = enabled ? 'Bật' : 'Tắt';
                    const schedule = type === 'start' ? 'auto start' : 'auto stop';
                    showToast(`${action} ${schedule} cho trạm #${stationId}`, 'success');
                } else {
                    showToast(result.message || 'Có lỗi xảy ra!', 'error');
                    // Revert checkbox
                    event.target.checked = !enabled;
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Lỗi kết nối đến server!', 'error');
                // Revert checkbox
                event.target.checked = !enabled;
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
        });
    </script>
    <?php endif; ?>
</body>
</html>
