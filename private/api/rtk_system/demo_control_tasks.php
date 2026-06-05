<?php
require_once __DIR__ . '/rinex_task_api.php';

// Check if task IDs are provided via GET or POST
$ids = [];
if (isset($_REQUEST['ids'])) {
    if (is_array($_REQUEST['ids'])) {
        $ids = $_REQUEST['ids'];
    } else {
        $ids = explode(',', $_REQUEST['ids']);
    }
}

// Check action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$result = null;

if (!empty($ids) && !empty($action)) {
    if ($action === 'stop') {
        $result = stopStorageTasks($ids);
    } elseif ($action === 'start') {
        $result = startStorageTasks($ids);
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Control Tasks (Start/Stop)</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 8px; }
        button { padding: 10px 15px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px; }
        button.stop { background: #dc3545; }
        button:hover { opacity: 0.9; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .result { margin-top: 20px; border: 1px solid #ccc; padding: 15px; border-radius: 5px; }
        .success { border-color: green; background: #e8f5e9; }
        .error { border-color: red; background: #ffebee; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Control Storage Tasks</h1>
        
        <form method="POST">
            <div class="form-group">
                <label for="ids">Task IDs (comma separated, e.g., 8,9):</label>
                <input type="text" id="ids" name="ids" value="<?php echo isset($_REQUEST['ids']) ? (is_array($_REQUEST['ids']) ? implode(',', $_REQUEST['ids']) : htmlspecialchars($_REQUEST['ids'])) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <button type="submit" name="action" value="start">Start Tasks</button>
                <button type="submit" name="action" value="stop" class="stop">Stop Tasks</button>
            </div>
        </form>
        
        <?php if ($result): ?>
        <div class="result <?php echo ($result['success'] ?? false) ? 'success' : 'error'; ?>">
            <h3>Result:</h3>
            <pre><?php echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <h3>Quick Links:</h3>
            <p><a href="demo_list_tasks.php">View Task List</a></p>
        </div>
    </div>
</body>
</html>
