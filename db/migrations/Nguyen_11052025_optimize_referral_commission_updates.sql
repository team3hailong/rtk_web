-- Migration: Optimize referral commission update system (11-05-2025)
-- This migration adds an updated_at column to referral_commission table if it doesn't exist

-- Check if updated_at column exists, if not add it
SET @dbname = DATABASE();
SET @tablename = 'referral_commission';
SET @columnname = 'updated_at';
SET @preparedStatement = (
    SELECT IF(
        (
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname
        ) > 0,
        'SELECT 1', -- Column already exists, do nothing
        'ALTER TABLE referral_commission ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    )
);

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Make sure the status field allows the 'approved' value
ALTER TABLE referral_commission MODIFY COLUMN 
status ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending';

-- Add index to improve query performance
SET @checkIndexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = 'idx_transaction_id'
);

SET @createIndexStatement = (
    SELECT IF(
        @checkIndexExists > 0,
        'SELECT 1', -- Index already exists
        'ALTER TABLE referral_commission ADD INDEX idx_transaction_id (transaction_id)'
    )
);

PREPARE createIndexIfNotExists FROM @createIndexStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Create trigger for automatic commission creation
-- First drop if exists to avoid errors
DROP TRIGGER IF EXISTS trg_auto_approve_commission;

-- Create trigger
DELIMITER //
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
                -- Insert new commission record (calculation will be handled by the application)
                -- This is just a backup mechanism, the main process should happen in application code
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
END //
DELIMITER ;
