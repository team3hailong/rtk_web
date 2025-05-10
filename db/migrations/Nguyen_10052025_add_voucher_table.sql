-- Migration: Add voucher table and add voucher_id foreign key to transaction_history
-- Date: May 10, 2025
-- Author: Nguyen

-- Step 1: Create the voucher table
CREATE TABLE `voucher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `voucher_type` enum('extend_duration', 'percentage_discount', 'fixed_discount') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'extend_duration: tăng tháng sử dụng, percentage_discount: giảm tiền theo phần trăm, fixed_discount: giảm tiền cố định',
  `discount_value` decimal(15,2) NOT NULL COMMENT 'số tháng tăng thêm hoặc % giảm giá hoặc số tiền giảm cố định',
  `max_discount` decimal(15,2) DEFAULT NULL COMMENT 'giới hạn số tiền giảm tối đa (chỉ áp dụng cho percentage_discount)',
  `min_order_value` decimal(15,2) DEFAULT NULL COMMENT 'giá trị đơn hàng tối thiểu để áp dụng voucher',
  `quantity` int DEFAULT NULL COMMENT 'số lượng voucher có thể sử dụng',
  `used_quantity` int NOT NULL DEFAULT '0' COMMENT 'số lượng voucher đã được sử dụng',
  `start_date` datetime NOT NULL COMMENT 'ngày bắt đầu hiệu lực',
  `end_date` datetime NOT NULL COMMENT 'ngày kết thúc hiệu lực',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'trạng thái kích hoạt',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Add voucher_id column to transaction_history table
ALTER TABLE `transaction_history` 
ADD COLUMN `voucher_id` int DEFAULT NULL AFTER `user_id`;

-- Step 3: Add foreign key constraint
ALTER TABLE `transaction_history`
ADD CONSTRAINT `fk_transaction_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 4: Create indexes for better performance
CREATE INDEX `idx_voucher_code` ON `voucher` (`code`);
CREATE INDEX `idx_voucher_dates` ON `voucher` (`start_date`, `end_date`, `is_active`);
CREATE INDEX `idx_transaction_voucher` ON `transaction_history` (`voucher_id`);