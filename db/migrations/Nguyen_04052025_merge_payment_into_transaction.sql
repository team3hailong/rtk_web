-- Migration: Merge payment table into transaction_history
-- Date: May 4, 2025
-- Author: Nguyen

-- Step 1: Add new columns to transaction_history
ALTER TABLE `transaction_history` 
ADD COLUMN `payment_image` varchar(255) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `export_invoice` tinyint(1) DEFAULT 0 AFTER `payment_image`,
ADD COLUMN `invoice_info` text DEFAULT NULL AFTER `export_invoice`,
ADD COLUMN `payment_confirmed` tinyint(1) DEFAULT 0 AFTER `invoice_info`,
ADD COLUMN `payment_confirmed_at` datetime DEFAULT NULL AFTER `payment_confirmed`;

-- Step 2: Migrate existing payment data to transaction_history
UPDATE transaction_history th
JOIN payment p ON th.registration_id = p.registration_id
SET 
    th.payment_image = p.payment_image,
    th.export_invoice = p.export_invoice,
    th.invoice_info = p.invoice_info,
    th.payment_confirmed = p.confirmed,
    th.payment_confirmed_at = p.confirmed_at,
    -- Update status to completed if the payment was confirmed
    th.status = CASE WHEN p.confirmed = 1 THEN 'completed' ELSE th.status END;

-- Step 3: Create a backup of the payment table before we stop using it
CREATE TABLE `payment_backup_20250504` LIKE `payment`;
INSERT INTO `payment_backup_20250504` SELECT * FROM `payment`;

-- Note: We're not dropping the payment table immediately to ensure a safe migration
-- After testing the new functionality, you can run:
-- DROP TABLE `payment`;