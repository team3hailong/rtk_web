-- Migration: Update referral_commission status field
-- Date: 2025-05-11

-- Modify status enum field to include 'approved'
ALTER TABLE `referral_commission` 
MODIFY COLUMN `status` ENUM('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending';
