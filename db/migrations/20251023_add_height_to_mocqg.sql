-- Migration: Add height column to mocqg table
-- Date: 2025-10-23
-- Description: Thêm cột height (độ cao) vào bảng mocqg

ALTER TABLE `mocqg`
ADD COLUMN `height` DECIMAL(8,2) DEFAULT NULL COMMENT 'Độ cao (m)' AFTER `long`;
