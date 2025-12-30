-- Migration: Add social login fields to user table
-- Date: 2025-12-30

ALTER TABLE `user` 
ADD COLUMN `google_id` VARCHAR(255) NULL AFTER `email`,
ADD COLUMN `facebook_id` VARCHAR(255) NULL AFTER `google_id`;

-- Indexing for faster lookup during login
CREATE INDEX idx_user_google_id ON `user`(`google_id`);
CREATE INDEX idx_user_facebook_id ON `user`(`facebook_id`);
