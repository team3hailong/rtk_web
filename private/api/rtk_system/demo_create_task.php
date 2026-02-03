<?php
require_once __DIR__ . '/rinex_task_api.php';

// Xử lý request
$result = null;
$requestType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quick_create'])) {
        // KIỂU 1: Tạo nhanh
        $requestType = 'quick';
        $taskName = $_POST['quick_task_name'] ?? 'quick_task_' . time();
        $stationIds = isset($_POST['quick_station_ids']) ? 
            array_map('trim', explode(',', $_POST['quick_station_ids'])) : 
            ['56', '58'];
        
        $result = createStorageTaskQuick($taskName, $stationIds);
        
    } elseif (isset($_POST['custom_create'])) {
        // KIỂU 2: Tạo theo thông tin người dùng nhập
        $requestType = 'custom';
        
        // Parse station IDs
        $stationIds = isset($_POST['stationIds']) ? 
            array_map('trim', explode(',', $_POST['stationIds'])) : 
            [];
        
        // Parse satellite systems
        $satelliteSystem = isset($_POST['satelliteSystem']) ? 
            array_map('intval', $_POST['satelliteSystem']) : 
            [1, 2, 3, 4, 6];
        
        // Parse obs types
        $obsType = isset($_POST['obsType']) ? 
            array_map('intval', $_POST['obsType']) : 
            [1, 2, 3, 4];
        
        // Parse upload addresses
        $uploadAddresses = isset($_POST['uploadAddresses']) ? 
            array_map('trim', explode(',', $_POST['uploadAddresses'])) : 
            ['2'];
        
        // Build threshold values
        $thresholdValue = [];
        $satellites = ['GPS', 'BDS', 'GLO', 'GAL', 'QZSS'];
        foreach ($satellites as $sat) {
            $thresholdValue[] = [
                'satellite' => $sat,
                'rateUse' => intval($_POST['rateUse'] ?? 95),
                'rateCs' => intval($_POST['rateCs'] ?? 400)
            ];
        }
        
        $taskData = [
            'taskName' => $_POST['taskName'] ?? 'custom_task',
            'stationIds' => $stationIds,
            'fileFormat' => $_POST['fileFormat'] ?? 'Rinex v304',
            'dailyQuantity' => intval($_POST['dailyQuantity'] ?? 1),
            'satelliteSystem' => $satelliteSystem,
            'obsType' => $obsType,
            'sampleRate' => intval($_POST['sampleRate'] ?? 30),
            'compressMode' => intval($_POST['compressMode'] ?? 2),
            'fileDeletePeriod' => $_POST['fileDeletePeriod'] ?? '3',
            'highAngle' => intval($_POST['highAngle'] ?? 15),
            'rateEpoch' => intval($_POST['rateEpoch'] ?? 85),
            'mp1' => floatval($_POST['mp1'] ?? 0.5),
            'snr1' => intval($_POST['snr1'] ?? 36),
            'mp2' => floatval($_POST['mp2'] ?? 0.5),
            'snr2' => intval($_POST['snr2'] ?? 36),
            'mp5' => floatval($_POST['mp5'] ?? 0.5),
            'snr5' => intval($_POST['snr5'] ?? 36),
            'mp6' => floatval($_POST['mp6'] ?? 0.5),
            'snr6' => intval($_POST['snr6'] ?? 36),
            'mp7' => floatval($_POST['mp7'] ?? 0.5),
            'snr7' => intval($_POST['snr7'] ?? 36),
            'mp8' => floatval($_POST['mp8'] ?? 0.5),
            'snr8' => intval($_POST['snr8'] ?? 36),
            'uploadStrategy' => intval($_POST['uploadStrategy'] ?? 0),
            'uploadAddresses' => $uploadAddresses,
            'uploadEnabled' => intval($_POST['uploadEnabled'] ?? 1),
            'qcEnabled' => intval($_POST['qcEnabled'] ?? 1),
            'storeEph' => intval($_POST['storeEph'] ?? 1),
            'isDefaultLocalDir' => intval($_POST['isDefaultLocalDir'] ?? 0),
            'localDir' => $_POST['localDir'] ?? '/YYYY/MM/DD,/STATION',
            'isDefaultUploadDir' => intval($_POST['isDefaultUploadDir'] ?? 0),
            'uploadDir' => $_POST['uploadDir'] ?? '/STATION,/YYYY/MM/DD',
            'thresholdValue' => $thresholdValue
        ];
        
        $result = createStorageTask($taskData);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Tạo Task CGBAS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }
        
        .tab-button:hover {
            background: #f0f0f0;
        }
        
        .tab-button.active {
            background: #4CAF50;
            color: white;
        }
        
        .tab-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 0 10px 10px 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            cursor: pointer;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
        }
        
        .result-box {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        
        .result-box h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .result-box pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .success {
            border-left-color: #4CAF50;
        }
        
        .error {
            border-left-color: #f44336;
        }
        
        .section-title {
            background: #f0f0f0;
            padding: 10px 15px;
            margin: 20px -30px;
            font-weight: bold;
            color: #555;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Demo Tạo Task CGBAS RTK</h1>
        
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab(0)">⚡ Tạo Nhanh</button>
            <button class="tab-button" onclick="switchTab(1)">⚙️ Tạo Tùy Chỉnh</button>
        </div>
        
        <!-- TAB 1: TẠO NHANH -->
        <div class="tab-content active">
            <h2>⚡ Tạo Task Nhanh</h2>
            <p style="color: #666; margin-bottom: 20px;">Sử dụng các giá trị mặc định, chỉ cần nhập tên task và ID trạm.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label>Tên Task:</label>
                    <input type="text" name="quick_task_name" placeholder="Ví dụ: sc2" required>
                    <div class="help-text">Tên định danh cho task</div>
                </div>
                
                <div class="form-group">
                    <label>Station IDs (cách nhau bởi dấu phẩy):</label>
                    <input type="text" name="quick_station_ids" placeholder="Ví dụ: 56, 58" value="56, 58" required>
                    <div class="help-text">Danh sách ID các trạm, phân cách bởi dấu phẩy</div>
                </div>
                
                <button type="submit" name="quick_create">🚀 Tạo Task Nhanh</button>
            </form>
        </div>
        
        <!-- TAB 2: TẠO TÙY CHỈNH -->
        <div class="tab-content">
            <h2>⚙️ Tạo Task Tùy Chỉnh</h2>
            <p style="color: #666; margin-bottom: 20px;">Cấu hình chi tiết tất cả các tham số của task.</p>
            
            <form method="POST">
                <div class="section-title">📋 Thông Tin Cơ Bản</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tên Task:</label>
                        <input type="text" name="taskName" value="sc2" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Station IDs (phân cách bởi dấu phẩy):</label>
                        <input type="text" name="stationIds" value="56, 58" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>File Format:</label>
                        <select name="fileFormat">
                            <option value="Rinex v304" selected>Rinex v304</option>
                            <option value="Rinex v303">Rinex v303</option>
                            <option value="Rinex v302">Rinex v302</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Daily Quantity:</label>
                        <input type="number" name="dailyQuantity" value="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Sample Rate (giây):</label>
                        <input type="number" name="sampleRate" value="30">
                    </div>
                    
                    <div class="form-group">
                        <label>Compress Mode:</label>
                        <select name="compressMode">
                            <option value="0">No Compress</option>
                            <option value="1">ZIP</option>
                            <option value="2" selected>GZIP</option>
                        </select>
                    </div>
                </div>
                
                <div class="section-title">🛰️ Satellite System</div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="satelliteSystem[]" value="1" checked> GPS (1)</label>
                        <label><input type="checkbox" name="satelliteSystem[]" value="2" checked> BDS (2)</label>
                        <label><input type="checkbox" name="satelliteSystem[]" value="3" checked> GLO (3)</label>
                        <label><input type="checkbox" name="satelliteSystem[]" value="4" checked> GAL (4)</label>
                        <label><input type="checkbox" name="satelliteSystem[]" value="5"> IRNSS (5)</label>
                        <label><input type="checkbox" name="satelliteSystem[]" value="6" checked> QZSS (6)</label>
                    </div>
                </div>
                
                <div class="section-title">📊 Observation Type</div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="obsType[]" value="1" checked> Type 1</label>
                        <label><input type="checkbox" name="obsType[]" value="2" checked> Type 2</label>
                        <label><input type="checkbox" name="obsType[]" value="3" checked> Type 3</label>
                        <label><input type="checkbox" name="obsType[]" value="4" checked> Type 4</label>
                    </div>
                </div>
                
                <div class="section-title">🔧 Quality Control Parameters</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>High Angle:</label>
                        <input type="number" name="highAngle" value="15">
                    </div>
                    
                    <div class="form-group">
                        <label>Rate Epoch:</label>
                        <input type="number" name="rateEpoch" value="85">
                    </div>
                    
                    <div class="form-group">
                        <label>File Delete Period (ngày):</label>
                        <input type="text" name="fileDeletePeriod" value="3">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>MP1 / SNR1:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" step="0.1" name="mp1" value="0.5" placeholder="MP1">
                            <input type="number" name="snr1" value="36" placeholder="SNR1">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>MP2 / SNR2:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" step="0.1" name="mp2" value="0.5" placeholder="MP2">
                            <input type="number" name="snr2" value="36" placeholder="SNR2">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>MP5 / SNR5:</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" step="0.1" name="mp5" value="0.5" placeholder="MP5">
                            <input type="number" name="snr5" value="36" placeholder="SNR5">
                        </div>
                    </div>
                </div>
                
                <div class="section-title">📤 Upload Settings</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Upload Strategy:</label>
                        <select name="uploadStrategy">
                            <option value="0" selected>Strategy 0</option>
                            <option value="1">Strategy 1</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Addresses (phân cách bởi dấu phẩy):</label>
                        <input type="text" name="uploadAddresses" value="2">
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Enabled:</label>
                        <select name="uploadEnabled">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>QC Enabled:</label>
                        <select name="qcEnabled">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                
                <div class="section-title">📁 Directory Settings</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Local Directory:</label>
                        <input type="text" name="localDir" value="/YYYY/MM/DD,/STATION">
                        <div class="help-text">Sử dụng placeholders: /YYYY, /MM, /DD, /STATION</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Directory:</label>
                        <input type="text" name="uploadDir" value="/STATION,/YYYY/MM/DD">
                        <div class="help-text">Sử dụng placeholders: /YYYY, /MM, /DD, /STATION</div>
                    </div>
                </div>
                
                <div class="section-title">📈 Threshold Values</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rate Use (%):</label>
                        <input type="number" name="rateUse" value="95">
                    </div>
                    
                    <div class="form-group">
                        <label>Rate CS:</label>
                        <input type="number" name="rateCs" value="400">
                    </div>
                </div>
                <div class="help-text">Áp dụng cho tất cả satellite systems: GPS, BDS, GLO, GAL, QZSS</div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" name="custom_create">⚙️ Tạo Task Tùy Chỉnh</button>
                </div>
            </form>
        </div>
        
        <?php if ($result): ?>
        <div class="result-box <?php echo $result['success'] ? 'success' : 'error'; ?>">
            <h3>
                <?php if ($result['success']): ?>
                    ✅ Kết Quả (<?php echo $requestType === 'quick' ? 'Tạo Nhanh' : 'Tạo Tùy Chỉnh'; ?>)
                <?php else: ?>
                    ❌ Lỗi (<?php echo $requestType === 'quick' ? 'Tạo Nhanh' : 'Tạo Tùy Chỉnh'; ?>)
                <?php endif; ?>
            </h3>
            <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function switchTab(index) {
            const buttons = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');
            
            buttons.forEach((btn, i) => {
                btn.classList.toggle('active', i === index);
            });
            
            contents.forEach((content, i) => {
                content.classList.toggle('active', i === index);
            });
        }
    </script>
</body>
</html>
