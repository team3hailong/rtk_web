<?php
require_once __DIR__ . '/rinex_task_api.php';

// Xử lý request
$result = null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 20;

// Lấy danh sách tasks
$result = getStorageTasks($page, $size);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Tasks CGBAS</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .controls-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 10px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .stats {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stats-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-label {
            color: #666;
            font-size: 14px;
        }
        
        .stats-value {
            color: #333;
            font-size: 18px;
            font-weight: bold;
        }
        
        .tasks-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9ff;
        }
        
        td {
            padding: 12px 10px;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background: #4CAF50;
            color: white;
        }
        
        .status-inactive {
            background: #f44336;
            color: white;
        }
        
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 20px;
            border-radius: 5px;
            color: #c62828;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }
        
        .pagination {
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 16px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.2s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .json-viewer {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .json-viewer h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .json-viewer pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .toggle-json {
            margin-top: 20px;
            text-align: center;
        }
        
        .toggle-json button {
            background: #f0f0f0;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .toggle-json button:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Danh Sách Tasks CGBAS RTK</h1>
        
        <!-- Controls -->
        <div class="controls">
            <form method="GET" action="">
                <div class="controls-row">
                    <div class="form-group">
                        <label>Trang:</label>
                        <input type="number" name="page" value="<?php echo $page; ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label>Số lượng/trang:</label>
                        <input type="number" name="size" value="<?php echo $size; ?>" min="1" max="100">
                    </div>
                    <button type="submit" class="btn">🔍 Tải lại</button>
                    <button type="button" class="btn" onclick="window.location.href='demo_create_task.php'">➕ Tạo Task Mới</button>
                </div>
            </form>
        </div>
        
        <?php if ($result && $result['success']): ?>
            <!-- Stats -->
            <div class="stats">
                <div class="stats-item">
                    <span class="stats-label">Tổng số tasks:</span>
                    <span class="stats-value"><?php echo $result['total'] ?? 0; ?></span>
                </div>
                <div class="stats-item">
                    <span class="stats-label">Trang hiện tại:</span>
                    <span class="stats-value"><?php echo $result['page'] ?? $page; ?></span>
                </div>
                <div class="stats-item">
                    <span class="stats-label">Số lượng/trang:</span>
                    <span class="stats-value"><?php echo $result['size'] ?? $size; ?></span>
                </div>
                <div class="stats-item">
                    <span class="stats-label">Số bản ghi:</span>
                    <span class="stats-value"><?php echo count($result['records'] ?? []); ?></span>
                </div>
            </div>
            
            <!-- Tasks Table -->
            <div class="tasks-container">
                <?php if (!empty($result['records'])): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Code</th>
                                    <th>Tên Task</th>
                                    <th>Trạm</th>
                                    <th>Format</th>
                                    <th>Sample Rate</th>
                                    <th>Satellite</th>
                                    <th>Obs Type</th>
                                    <th>Compress</th>
                                    <th>Trạng thái</th>
                                    <th>Người tạo</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['records'] as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['id']); ?></td>
                                    <td><code><?php echo htmlspecialchars($task['code']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($task['taskName']); ?></strong></td>
                                    <td>
                                        <div class="tag-list">
                                            <?php foreach ($task['stationIds'] ?? [] as $stationId): ?>
                                                <span class="tag"><?php echo htmlspecialchars($stationId); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['fileFormat']); ?></td>
                                    <td><?php echo htmlspecialchars($task['sampleRate']); ?>s</td>
                                    <td>
                                        <div class="tag-list">
                                            <?php 
                                            $satNames = [1 => 'GPS', 2 => 'BDS', 3 => 'GLO', 4 => 'GAL', 5 => 'IRNSS', 6 => 'QZSS'];
                                            foreach ($task['satelliteSystem'] ?? [] as $satId): 
                                            ?>
                                                <span class="tag"><?php echo $satNames[$satId] ?? $satId; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tag-list">
                                            <?php foreach ($task['obsType'] ?? [] as $obsType): ?>
                                                <span class="tag">T<?php echo htmlspecialchars($obsType); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $compressModes = [0 => 'None', 1 => 'ZIP', 2 => 'GZIP'];
                                        echo $compressModes[$task['compressMode']] ?? $task['compressMode'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($task['status'] == 1): ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['createBy']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', $task['createTime'] / 1000); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php
                        $totalPages = ceil($result['total'] / $size);
                        $prevPage = max(1, $page - 1);
                        $nextPage = min($totalPages, $page + 1);
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="?page=1&size=<?php echo $size; ?>">« Đầu</a>
                            <a href="?page=<?php echo $prevPage; ?>&size=<?php echo $size; ?>">‹ Trước</a>
                        <?php else: ?>
                            <span class="disabled">« Đầu</span>
                            <span class="disabled">‹ Trước</span>
                        <?php endif; ?>
                        
                        <span class="current">Trang <?php echo $page; ?> / <?php echo max(1, $totalPages); ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $nextPage; ?>&size=<?php echo $size; ?>">Sau ›</a>
                            <a href="?page=<?php echo $totalPages; ?>&size=<?php echo $size; ?>">Cuối »</a>
                        <?php else: ?>
                            <span class="disabled">Sau ›</span>
                            <span class="disabled">Cuối »</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i>📭</i>
                        <h3>Không có task nào</h3>
                        <p>Chưa có task nào được tạo trong hệ thống.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ($result): ?>
            <!-- Error State -->
            <div class="error-box">
                <h3>❌ Lỗi khi lấy danh sách tasks</h3>
                <p><strong>Lỗi:</strong> <?php echo htmlspecialchars($result['error'] ?? 'Unknown error'); ?></p>
                <?php if (isset($result['http_code'])): ?>
                    <p><strong>HTTP Code:</strong> <?php echo $result['http_code']; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Toggle JSON Response -->
        <div class="toggle-json">
            <button onclick="toggleJson()">🔍 Hiện/Ẩn JSON Response</button>
        </div>
        
        <!-- JSON Response -->
        <div class="json-viewer" id="jsonViewer" style="display: none;">
            <h3>📄 Raw JSON Response</h3>
            <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
    
    <script>
        function toggleJson() {
            const viewer = document.getElementById('jsonViewer');
            viewer.style.display = viewer.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
