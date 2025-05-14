-- Combined Migration File
-- Tổng hợp tất cả các thay đổi từ các file migration riêng lẻ
-- Generated on: May 11, 2025

-- ==========================================
-- 1. Email Verification (27-04-2025)
-- ==========================================
ALTER TABLE `user`
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Trạng thái xác thực email',
ADD COLUMN IF NOT EXISTS `email_verify_token` VARCHAR(255) DEFAULT NULL COMMENT 'Token xác thực email';

-- ==========================================
-- 2. Merge Payment into Transaction (04-05-2025)
-- ==========================================
-- Step 1: Add new columns to transaction_history
ALTER TABLE `transaction_history` 
ADD COLUMN IF NOT EXISTS `payment_image` varchar(255) DEFAULT NULL COMMENT 'Ảnh minh chứng thanh toán',
ADD COLUMN IF NOT EXISTS `export_invoice` tinyint(1) DEFAULT 0 COMMENT 'Yêu cầu xuất hóa đơn',
ADD COLUMN IF NOT EXISTS `invoice_info` text DEFAULT NULL COMMENT 'Thông tin xuất hóa đơn',
ADD COLUMN IF NOT EXISTS `payment_confirmed` tinyint(1) DEFAULT 0 COMMENT 'Xác nhận đã thanh toán',
ADD COLUMN IF NOT EXISTS `payment_confirmed_at` datetime DEFAULT NULL COMMENT 'Thời gian xác nhận thanh toán',
ADD COLUMN IF NOT EXISTS `voucher_id` int DEFAULT NULL COMMENT 'ID voucher được áp dụng';

-- Step 2: Create foreign key for voucher_id
ALTER TABLE `transaction_history`
ADD CONSTRAINT IF NOT EXISTS `fk_transaction_voucher` 
FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 3: Remove payment table (after migration)
-- First remove foreign key constraints
ALTER TABLE `payment` DROP FOREIGN KEY IF EXISTS `fk_payment_registration`;

-- Then drop the table
DROP TABLE IF EXISTS `payment`;

-- ==========================================
-- 3. Company Address (10-05-2025)
-- ==========================================
ALTER TABLE `user` 
ADD COLUMN IF NOT EXISTS `company_address` VARCHAR(255) DEFAULT NULL COMMENT 'Địa chỉ công ty' AFTER `tax_code`;

-- ==========================================
-- 4. Voucher System (10-05-2025)
-- ==========================================
-- Step 1: Create the voucher table if not exists
CREATE TABLE IF NOT EXISTS `voucher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `voucher_type` enum('extend_duration', 'percentage_discount', 'fixed_discount') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'extend_duration: tăng tháng sử dụng, percentage_discount: giảm tiền theo phần trăm, fixed_discount: giảm tiền cố định',
  `discount_value` decimal(15,2) NOT NULL COMMENT 'số tháng tăng thêm hoặc % giảm giá hoặc số tiền giảm cố định',
  `max_discount` decimal(15,2) DEFAULT NULL COMMENT 'giới hạn số tiền giảm tối đa (chỉ áp dụng cho percentage_discount)',
  `min_order_value` decimal(15,2) DEFAULT NULL COMMENT 'giá trị đơn hàng tối thiểu để áp dụng voucher',
  `quantity` int DEFAULT NULL COMMENT 'số lượng voucher có thể sử dụng',
  `limit_usage` int DEFAULT NULL COMMENT 'số lần tối đa một người dùng có thể sử dụng voucher này (NULL = không giới hạn)',
  `used_quantity` int NOT NULL DEFAULT '0' COMMENT 'số lượng voucher đã được sử dụng',
  `start_date` datetime NOT NULL COMMENT 'ngày bắt đầu hiệu lực',
  `end_date` datetime NOT NULL COMMENT 'ngày kết thúc hiệu lực',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'trạng thái kích hoạt',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code` (`code`),
  KEY `idx_voucher_code` (`code`),
  KEY `idx_voucher_dates` (`start_date`, `end_date`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Create user voucher usage tracking table
CREATE TABLE IF NOT EXISTS `user_voucher_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `transaction_id` int DEFAULT NULL,
  `used_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_voucher_usage` (`user_id`, `voucher_id`),
  KEY `fk_uvu_voucher_id` (`voucher_id`),
  KEY `fk_uvu_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_uvu_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_uvu_voucher_id` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_uvu_transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_history` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 5. Support Request System (10-05-2025)
-- ==========================================
-- Create support_requests table
CREATE TABLE IF NOT EXISTS `support_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('technical','billing','account','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `status` enum('pending','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_support_requests_user_id` (`user_id`),
  KEY `idx_support_requests_status` (`status`),
  CONSTRAINT `fk_support_requests_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create company_info table
CREATE TABLE IF NOT EXISTS `company_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci,
  `tax_code` varchar(50) COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `working_hours` varchar(255) COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 6. Fix Bank Info Primary Key (10-05-2025)
-- ==========================================
ALTER TABLE `bank_info`
  ADD PRIMARY KEY IF NOT EXISTS (`id`),
  ADD KEY IF NOT EXISTS `user_id` (`user_id`);

ALTER TABLE `bank_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- ==========================================
-- 7. Referral System (11-05-2025)
-- ==========================================
-- Create referral table
CREATE TABLE IF NOT EXISTS `referral` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `referral_code` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_id` (`user_id`),
  UNIQUE KEY `unique_referral_code` (`referral_code`),
  CONSTRAINT `fk_referral_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create referred_user table
CREATE TABLE IF NOT EXISTS `referred_user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_referred_user` (`referred_user_id`),
  CONSTRAINT `fk_referred_user_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_referred_user_referred` FOREIGN KEY (`referred_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create referral_commission table
CREATE TABLE IF NOT EXISTS `referral_commission` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `transaction_id` INT NOT NULL,
  `commission_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_commission_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commission_referred_user` FOREIGN KEY (`referred_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commission_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create withdrawal_request table
CREATE TABLE IF NOT EXISTS `withdrawal_request` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_holder` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_withdrawal_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remove is_collaborator column in favor of new referral system
ALTER TABLE `user` DROP COLUMN IF EXISTS `is_collaborator`;

-- ==========================================
-- 8. Auto Commission Trigger (11-05-2025)
-- ==========================================
-- Create trigger for automatic commission creation
DROP TRIGGER IF EXISTS trg_auto_approve_commission;

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
END //
DELIMITER ;

-- ==========================================
-- 9. Insert Sample Data (Optional)
-- ==========================================
-- Insert default company info if not exists
INSERT INTO `company_info` (`name`, `address`, `phone`, `email`, `website`, `tax_code`, `description`, `working_hours`) 
SELECT 'Công ty Cổ phần Công nghệ RTK',
       'Tòa nhà Otek, 17 Duy Tân, Cầu Giấy, Hà Nội',
       '0981190564',
       'support@rtktech.vn',
       'https://rtktech.vn',
       '0109281282',
       'Công ty chuyên cung cấp giải pháp đo đạc với công nghệ RTK hiện đại, chất lượng cao, đáng tin cậy.',
       'Thứ 2 - Thứ 6: 8:00 - 17:30, Thứ 7: 8:00 - 12:00'
WHERE NOT EXISTS (SELECT 1 FROM company_info LIMIT 1);

-- Insert sample vouchers
INSERT INTO `voucher` (`code`, `description`, `voucher_type`, `discount_value`, `max_discount`, `min_order_value`, `quantity`, `limit_usage`, `used_quantity`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) 
SELECT 'WELCOME50', 'Giảm 50.000đ cho đơn hàng từ 200.000đ, mỗi tài khoản chỉ dùng 1 lần', 'fixed_discount', 50000, NULL, 200000, 100, 1, 0, '2025-05-01 00:00:00', '2025-12-31 23:59:59', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM voucher WHERE code = 'WELCOME50');

INSERT INTO `voucher` (`code`, `description`, `voucher_type`, `discount_value`, `max_discount`, `min_order_value`, `quantity`, `limit_usage`, `used_quantity`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) 
SELECT 'VIP20', 'Giảm 20% tối đa 500.000đ, mỗi tài khoản dùng tối đa 3 lần', 'percentage_discount', 20, 500000, NULL, 200, 3, 0, '2025-05-01 00:00:00', '2025-12-31 23:59:59', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM voucher WHERE code = 'VIP20');
