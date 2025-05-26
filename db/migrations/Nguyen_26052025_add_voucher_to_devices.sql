-- Add voucher column to user_devices table
ALTER TABLE `user_devices` 
ADD COLUMN `voucher_code` VARCHAR(50) NULL AFTER `login_count`,
ADD COLUMN `voucher_used` TINYINT(1) DEFAULT 0 AFTER `voucher_code`;
