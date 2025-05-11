-- Migration: Add referral system tables
-- Date: 2025-05-11

-- Table to store user referral information
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

-- Table to store referred users
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

-- Table to store commission/reward transactions
CREATE TABLE IF NOT EXISTS `referral_commission` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `referrer_id` INT NOT NULL,
  `referred_user_id` INT NOT NULL,
  `transaction_id` INT NOT NULL,
  `commission_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_commission_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commission_referred_user` FOREIGN KEY (`referred_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commission_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store withdrawal requests
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

-- Remove the is_collaborator column from user table since everyone can now refer others
ALTER TABLE `user` DROP COLUMN IF EXISTS `is_collaborator`;
