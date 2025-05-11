<?php
// filepath: c:\laragon\www\rtk_web\private\action\migration\check_and_update_commissions.php
/**
 * One-time script to check and update existing commissions to ensure they're properly reflected in the UI
 * 
 * This script:
 * 1. Checks if the database schema has been updated correctly
 * 2. Updates any existing completed transactions to have 'approved' commissions
 * 3. Logs all actions for debugging
 */

// Include necessary files
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';

try {
    // Set error reporting and display errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    echo "Connecting to database...\n";
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting commission correction script...\n\n";
    
    // 1. Make sure the database schema is updated
    echo "Checking database schema for referral_commission table...\n";
    
    $columnCheckSql = "SHOW COLUMNS FROM referral_commission WHERE Field = 'status'";
    $columnCheck = $conn->query($columnCheckSql)->fetch(PDO::FETCH_ASSOC);
    
    if ($columnCheck) {
        echo "Status column found. Current type: " . $columnCheck['Type'] . "\n";
        
        // Check if 'approved' is in the enum values
        if (strpos($columnCheck['Type'], "'approved'") === false) {
            echo "Status column doesn't include 'approved' status. Updating schema...\n";
            
            $alterSql = "ALTER TABLE `referral_commission` 
                         MODIFY COLUMN `status` ENUM('pending', 'approved', 'paid', 'cancelled') 
                         NOT NULL DEFAULT 'pending'";
            $conn->exec($alterSql);
            echo "Schema updated successfully.\n\n";
        } else {
            echo "Schema is already up-to-date.\n\n";
        }
    } else {
        echo "Error: Could not find status column in referral_commission table.\n";
        exit(1);
    }
      // 2. Check for completed transactions with confirmed payment but no commission records
    // Also check for transactions with commissions not marked as approved
    echo "Checking for transactions that should have approved commissions...\n";
    
    $conn->beginTransaction();
    
    $transactionsSql = "
        SELECT 
            th.id, th.user_id, th.amount,
            ru.referrer_id,
            rc.id as commission_id,
            rc.status as commission_status
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
            AND th.payment_confirmed = 1
            AND (rc.id IS NULL OR rc.status != 'approved')
    ";
      $transactions = $conn->query($transactionsSql)->fetchAll(PDO::FETCH_ASSOC);
    $count = count($transactions);
    
    echo "Found $count transactions needing commission updates.\n";
    
    if ($count > 0) {
        $newRecords = 0;
        $updatedRecords = 0;
        
        $insertSql = "
            INSERT INTO referral_commission 
            (referrer_id, referred_user_id, transaction_id, commission_amount, status) 
            VALUES (?, ?, ?, ?, 'approved')
        ";
        $insertStmt = $conn->prepare($insertSql);
        
        $updateSql = "
            UPDATE referral_commission
            SET status = 'approved'
            WHERE id = ?
        ";
        $updateStmt = $conn->prepare($updateSql);
        
        $commissionRate = 0.05; // 5%
        
        foreach ($transactions as $transaction) {
            // Check if this is an update or a new record
            if (!empty($transaction['commission_id'])) {
                // Update existing record
                $updateStmt->execute([$transaction['commission_id']]);
                echo "Updated commission ID: {$transaction['commission_id']} to approved for transaction ID: {$transaction['id']}\n";
                $updatedRecords++;
            } else {
                // Create new record
                $commissionAmount = $transaction['amount'] * $commissionRate;
                $insertStmt->execute([
                    $transaction['referrer_id'], 
                    $transaction['user_id'], 
                    $transaction['id'], 
                    $commissionAmount
                ]);
                
                echo "Added commission of {$commissionAmount} VND for transaction ID: {$transaction['id']}\n";
                $newRecords++;
            }
        }
        
        echo "\nUpdate completed. Added $newRecords new commission records and updated $updatedRecords existing records.\n";
          $conn->commit();
    } else {
        $conn->commit();
        echo "No updates needed.\n";
    }
    
    echo "\nScript executed successfully!\n";
    
} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
