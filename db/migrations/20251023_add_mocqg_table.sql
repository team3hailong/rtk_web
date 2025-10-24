-- Migration: Add mocqg table
-- Date: 2025-10-23
-- Description: Tạo bảng mocqg để lưu thông tin các mốc quốc gia

CREATE TABLE IF NOT EXISTS `mocqg` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ten_moc` VARCHAR(255) NOT NULL COMMENT 'Tên mốc quốc gia',
    `lat` DECIMAL(10, 8) NOT NULL COMMENT 'Vĩ độ',
    `long` DECIMAL(11, 8) NOT NULL COMMENT 'Kinh độ',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái: 0=Inactive, 1=Active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_location` (`lat`, `long`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu thông tin các mốc quốc gia';

-- Thêm một số dữ liệu mẫu (có thể xóa nếu không cần)
INSERT INTO `mocqg` (`ten_moc`, `lat`, `long`, `status`) VALUES
('Mốc 001', 21.028511, 105.804817, 1),
('Mốc 002', 21.033333, 105.850000, 1),
('Mốc 003', 16.047079, 108.206230, 1);
