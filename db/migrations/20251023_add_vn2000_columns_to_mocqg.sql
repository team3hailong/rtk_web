-- Migration: Add vn2000 columns to mocqg table
-- Date: 2025-10-23
-- Description: Thêm 3 cột x, y, z (tọa độ VN-2000) vào bảng mocqg

ALTER TABLE `mocqg`
ADD COLUMN `vn2000_x` DECIMAL(12,3) DEFAULT NULL COMMENT 'VN2000 X',
ADD COLUMN `vn2000_y` DECIMAL(12,3) DEFAULT NULL COMMENT 'VN2000 Y',
ADD COLUMN `vn2000_z` DECIMAL(12,3) DEFAULT NULL COMMENT 'VN2000 Z';
