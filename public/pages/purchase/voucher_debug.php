<?php
/**
 * Voucher Debugging Tool
 * 
 * This utility helps debug voucher issues by checking database status and session data
 * Place this file in public/pages/purchase/ and access it directly to check voucher system health
 */

session_start();

// --- Require file config ---
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Voucher.php';

// Security check - restrict to admin or developer users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access denied. This tool is for administrators only.");
}

// Function to check if table exists
function tableExists($tableName) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to check session data
function checkSessionData() {
    $sessionData = [
        'has_user_id' => isset($_SESSION['user_id']),
        'has_pending_registration' => isset($_SESSION['pending_registration_id']),
        'has_pending_total_price' => isset($_SESSION['pending_total_price']),
        'has_order' => isset($_SESSION['order']),
        'has_renewal' => isset($_SESSION['renewal']),
        'is_renewal' => isset($_SESSION['is_renewal']) ? $_SESSION['is_renewal'] : false,
        'is_trial' => isset($_SESSION['pending_is_trial']) ? $_SESSION['pending_is_trial'] : false,
    ];
    
    return $sessionData;
}

// Check for voucher table
$voucherTableExists = tableExists('voucher');

// Get all vouchers if table exists
$vouchers = [];
if ($voucherTableExists) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT * FROM voucher");
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vouchers = ["Error fetching vouchers: " . $e->getMessage()];
    }
}

// Get session info
$sessionInfo = checkSessionData();

// Handle voucher code check if form submitted
$voucherResult = null;
if (isset($_POST['check_voucher']) && !empty($_POST['voucher_code'])) {
    $voucherCode = trim($_POST['voucher_code']);
    try {
        $db = new Database();
        $voucherService = new Voucher($db);
        $voucher = $voucherService->getVoucherByCode($voucherCode);
        
        if ($voucher) {
            // Check if it's active
            $now = new DateTime();
            $startDate = new DateTime($voucher['start_date']);
            $endDate = new DateTime($voucher['end_date']);
            
            $isActive = $voucher['is_active'] && $now >= $startDate && $now <= $endDate;
            $isUsedUp = $voucher['quantity'] !== null && $voucher['used_quantity'] >= $voucher['quantity'];
            
            $voucher['is_currently_active'] = $isActive;
            $voucher['is_used_up'] = $isUsedUp;
            $voucher['active_status'] = $isActive ? 'Active' : 'Not active';
            $voucher['usage_status'] = $isUsedUp ? 'Used up' : 'Available';
            
            $voucherResult = $voucher;
        } else {
            $voucherResult = ['not_found' => true, 'message' => 'Voucher not found'];
        }
    } catch (Exception $e) {
        $voucherResult = ['error' => true, 'message' => $e->getMessage()];
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .status-box { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .good { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeeba; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .check-form { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        input, button { padding: 8px 12px; margin: 5px; }
        button { cursor: pointer; background-color: #007bff; color: white; border: none; border-radius: 3px; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h1>Voucher System Debug Tool</h1>
    
    <div class="status-box <?php echo $voucherTableExists ? 'good' : 'error'; ?>">
        <h2>Database Check</h2>
        <p>Voucher Table: <?php echo $voucherTableExists ? '✅ Exists' : '❌ Missing'; ?></p>
    </div>
    
    <div class="status-box <?php echo (isset($_SESSION['order']) || isset($_SESSION['renewal'])) ? 'good' : 'warning'; ?>">
        <h2>Session Data</h2>
        <ul>
            <?php foreach ($sessionInfo as $key => $value): ?>
                <li><?php echo $key; ?>: <?php echo is_bool($value) ? ($value ? 'true' : 'false') : ($value ? '✅' : '❌'); ?></li>
            <?php endforeach; ?>
        </ul>
        
        <?php if (isset($_SESSION['order'])): ?>
            <h3>Order Session Data</h3>
            <pre><?php print_r($_SESSION['order']); ?></pre>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['renewal'])): ?>
            <h3>Renewal Session Data</h3>
            <pre><?php print_r($_SESSION['renewal']); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="check-form">
        <h2>Check Voucher Code</h2>
        <form method="post">
            <input type="text" name="voucher_code" placeholder="Enter voucher code" required>
            <button type="submit" name="check_voucher">Check Voucher</button>
        </form>
        
        <?php if ($voucherResult): ?>
            <h3>Voucher Check Results</h3>
            <?php if (isset($voucherResult['not_found'])): ?>
                <div class="status-box warning">
                    <p>Voucher not found in database</p>
                </div>
            <?php elseif (isset($voucherResult['error'])): ?>
                <div class="status-box error">
                    <p>Error: <?php echo $voucherResult['message']; ?></p>
                </div>
            <?php else: ?>
                <div class="status-box good">
                    <h3>Voucher Found</h3>
                    <p>Code: <strong><?php echo htmlspecialchars($voucherResult['code']); ?></strong></p>
                    <p>Type: <strong><?php echo htmlspecialchars($voucherResult['voucher_type']); ?></strong></p>
                    <p>Discount value: <strong><?php echo htmlspecialchars($voucherResult['discount_value']); ?></strong></p>
                    <p>Status: <strong><?php echo htmlspecialchars($voucherResult['active_status']); ?></strong></p>
                    <p>Usage: <strong><?php echo htmlspecialchars($voucherResult['usage_status']); ?></strong> 
                       (<?php echo htmlspecialchars($voucherResult['used_quantity']); ?>/<?php echo $voucherResult['quantity'] ?? 'unlimited'; ?>)</p>
                    <p>Valid from: <strong><?php echo htmlspecialchars($voucherResult['start_date']); ?></strong> 
                       to <strong><?php echo htmlspecialchars($voucherResult['end_date']); ?></strong></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($voucherTableExists && !empty($vouchers)): ?>
    <div>
        <h2>All Vouchers</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Active</th>
                    <th>Usage</th>
                    <th>Valid From</th>
                    <th>Valid To</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v['id']); ?></td>
                    <td><?php echo htmlspecialchars($v['code']); ?></td>
                    <td><?php echo htmlspecialchars($v['voucher_type']); ?></td>
                    <td><?php echo htmlspecialchars($v['discount_value']); ?></td>
                    <td><?php echo $v['is_active'] ? '✅' : '❌'; ?></td>
                    <td><?php echo htmlspecialchars($v['used_quantity']); ?>/<?php echo $v['quantity'] ?? '∞'; ?></td>
                    <td><?php echo htmlspecialchars($v['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($v['end_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</body>
</html>
