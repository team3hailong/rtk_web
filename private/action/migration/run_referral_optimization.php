<?php
/**
 * Run migration to optimize referral commission system
 * Apply the SQL migration file to:
 * 1. Add updated_at column if needed
 * 2. Update status ENUM values
 * 3. Add index for better performance
 * 4. Create a trigger for automatic commission approval
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
    
    echo "Starting migration to optimize referral commission system...\n";
    
    // Get the migration SQL
    $sqlFile = dirname(dirname(dirname(__DIR__))) . '/db/migrations/Nguyen_11052025_optimize_referral_commission_updates.sql';
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception("Could not read migration file: $sqlFile");
    }
    
    // Split SQL statements by delimiter
    $statements = explode(';', $sql);
    $statements = array_filter($statements, function($stmt) {
        return trim($stmt) !== '';
    });
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (stripos($statement, 'DELIMITER') === false) {
            $stmt = $pdo->prepare($statement);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Error executing SQL: " . print_r($stmt->errorInfo(), true));
            }
        }
    }
    
    // Handle the trigger separately (because of DELIMITER issues)
    echo "Creating trigger for automatic commission approval...\n";
    $triggerSql = "
        DROP TRIGGER IF EXISTS trg_auto_approve_commission;
        
        CREATE TRIGGER trg_auto_approve_commission
        AFTER UPDATE ON transaction_history
        FOR EACH ROW
        BEGIN
            -- If transaction is completed and payment is confirmed 
            IF NEW.status = 'completed' AND NEW.payment_confirmed = 1 THEN
                -- Check if a commission record already exists
                IF NOT EXISTS (SELECT 1 FROM referral_commission WHERE transaction_id = NEW.id) THEN
                    -- Check if the user was referred
                    IF EXISTS (SELECT 1 FROM referred_user WHERE referred_user_id = NEW.user_id) THEN
                        -- Get referrer information
                        SET @referrer_id = (SELECT referrer_id FROM referred_user WHERE referred_user_id = NEW.user_id);
                        -- Insert new commission record
                        INSERT INTO referral_commission 
                            (referrer_id, referred_user_id, transaction_id, commission_amount, status, created_at)
                        VALUES 
                            (@referrer_id, NEW.user_id, NEW.id, NEW.amount * 0.05, 'approved', NOW());
                    END IF;
                ELSE
                    -- Ensure existing commission is approved
                    UPDATE referral_commission 
                    SET status = 'approved', updated_at = NOW()
                    WHERE transaction_id = NEW.id AND status != 'approved';
                END IF;
            END IF;
        END
    ";
    
    $stmt = $pdo->prepare($triggerSql);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Error creating trigger: " . print_r($stmt->errorInfo(), true));
    }
    
    // Commit transaction
    $pdo->commit();
    echo "Migration completed successfully!\n";
    
    // Run the referral system optimizer to fix any existing data issues
    echo "\nRunning referral system optimizer to fix existing data...\n";
    include dirname(dirname(__DIR__)) . '/private/action/referral/optimize_referral_system.php';
    
    echo "\nOptimization complete!\n";
    echo "You can now disable the cron job for private/cron/update_commissions.php as it is no longer needed.\n";
    echo "All commissions will be automatically calculated and approved when transactions are updated.\n";
    
} catch (Exception $e) {
    // Rollback transaction if an error occurred
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed with error: " . $e->getMessage() . "\n";
}
?>
