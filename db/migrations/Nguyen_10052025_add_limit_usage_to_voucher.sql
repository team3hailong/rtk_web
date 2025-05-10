-- Migration: Add limit_usage column to voucher table
-- Date: May 10, 2025
-- Author: Nguyen

-- Step 1: Add limit_usage column to voucher table
ALTER TABLE `voucher` 
ADD COLUMN `limit_usage` int DEFAULT NULL COMMENT 'số lần tối đa một người dùng có thể sử dụng voucher này (NULL = không giới hạn)' AFTER `quantity`;

-- Step 2: Create a new table to track user voucher usage
CREATE TABLE `user_voucher_usage` (
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

-- Step 3: Add sample data for vouchers with limit usage
INSERT INTO `voucher` (`code`, `description`, `voucher_type`, `discount_value`, `max_discount`, `min_order_value`, `quantity`, `limit_usage`, `used_quantity`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) 
VALUES 
('WELCOME50', 'Giảm 50.000đ cho đơn hàng từ 200.000đ, mỗi tài khoản chỉ dùng 1 lần', 'fixed_discount', 50000, NULL, 200000, 100, 1, 0, '2025-05-01 00:00:00', '2025-12-31 23:59:59', 1, NOW(), NOW()),
('VIP20', 'Giảm 20% tối đa 500.000đ, mỗi tài khoản dùng tối đa 3 lần', 'percentage_discount', 20, 500000, NULL, 200, 3, 0, '2025-05-01 00:00:00', '2025-12-31 23:59:59', 1, NOW(), NOW());
