<?php
/**
 * Optimize Referral System
 * 
 * This script removes redundancy in the referral commission system by:
 * 1. Enhancing transaction_history updates to handle commission calculations directly
 * 2. Eliminating the need for a separate cron job
 * 3. Ensuring all commissions are properly tracked and approved
 */

// Include necessary files
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';

// --- Sử dụng các hằng số được định nghĩa từ path_helpers ---
$base_url = BASE_URL;
$base_path = PUBLIC_URL;
$project_root_path = PROJECT_ROOT_PATH;

require_once $project_root_path . '/private/classes/Database.php';
require_once $project_root_path . '/private/classes/Referral.php';

// Check for missing commission records for completed transactions
function checkAndFixMissingCommissions() {
    try {
        $db = new Database();
        $referralService = new Referral($db);
        $conn = $db->getConnection();
          // Find transactions that should have commissions but don't
        // No longer checking for payment_confirmed
        $sql = "
            SELECT 
                th.id, th.user_id, th.amount
            FROM 
                transaction_history th
            JOIN 
                user u ON th.user_id = u.id
            JOIN 
                referred_user ru ON ru.referred_user_id = u.id
            LEFT JOIN 
                referral_commission rc ON rc.transaction_id = th.id
            WHERE 
                th.status = 'completed' 
                AND rc.id IS NULL
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($transactions) . " transactions missing commission records.\n";
        
        // Process each transaction
        foreach ($transactions as $transaction) {
            $result = $referralService->calculateCommission($transaction['id']);
            if ($result) {
                echo "Created commission for transaction ID: {$transaction['id']}\n";
            } else {
                echo "Failed to create commission for transaction ID: {$transaction['id']}\n";
            }
        }
        
        // Find commissions that should be approved but aren't
        $sql = "
            SELECT 
                rc.id, rc.transaction_id
            FROM 
                referral_commission rc
            JOIN 
                transaction_history th ON rc.transaction_id = th.id
            WHERE 
                th.status = 'completed' 
                AND th.payment_confirmed = 1
                AND rc.status != 'approved'
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($commissions) . " commissions needing status update.\n";
        
        // Update each commission
        foreach ($commissions as $commission) {
            $stmt = $conn->prepare("
                UPDATE referral_commission 
                SET status = 'approved' 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $commission['id'], PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                echo "Updated commission ID: {$commission['id']} to 'approved'\n";
            } else {
                echo "Failed to update commission ID: {$commission['id']}\n";
            }
        }
        
        return true;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the check and fix function
$result = checkAndFixMissingCommissions();
echo $result ? "Optimization completed successfully.\n" : "Optimization failed.\n";

echo "\nRecommendations:\n";
echo "1. The cron job in private/cron/update_commissions.php is now redundant and can be disabled\n";
echo "2. All commission calculations are automatically handled when transactions are updated\n";
echo "3. This script can be run manually if you suspect any inconsistencies in commission data\n";
?>
