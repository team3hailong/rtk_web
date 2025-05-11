<?php
/**
 * Run migration to update referral_commission table
 * Add 'approved' to status ENUM values
 */

// Include necessary files
require_once dirname(dirname(dirname(__DIR__))) . '/private/config/config.php';
require_once dirname(dirname(dirname(__DIR__))) . '/private/classes/Database.php';

// Connect to database
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "Starting migration to update referral_commission table...\n";
    
    // Run the ALTER TABLE query to modify the status column
    $sql = "ALTER TABLE `referral_commission` 
            MODIFY COLUMN `status` ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        // Commit transaction
        $pdo->commit();
        echo "Migration completed successfully!\n";
        echo "The referral_commission table has been updated to include 'approved' status.\n";
    } else {
        // Rollback transaction
        $pdo->rollBack();
        echo "Migration failed: Unable to update the table.\n";
    }
    
} catch (PDOException $e) {
    // Rollback transaction if an error occurred
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "Migration failed with error: " . $e->getMessage() . "\n";
}
?>
