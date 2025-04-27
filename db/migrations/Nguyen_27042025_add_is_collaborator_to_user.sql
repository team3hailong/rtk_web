-- Migration: Thêm cột is_collaborator vào bảng user
ALTER TABLE `user`
ADD COLUMN `is_collaborator` TINYINT(1) NOT NULL DEFAULT 0 AFTER `phone`;
