-- Add company_address column to user table
ALTER TABLE `user` ADD COLUMN `company_address` VARCHAR(255) DEFAULT NULL COMMENT 'Địa chỉ công ty' AFTER `tax_code`;

-- Update existing rows with NULL values
UPDATE `user` SET `company_address` = NULL WHERE `company_address` IS NULL;
