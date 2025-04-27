-- Migration: Thêm cột xác thực email vào bảng user
ALTER TABLE `user`
ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
ADD COLUMN `email_verify_token` VARCHAR(255) DEFAULT NULL AFTER `email_verified`;