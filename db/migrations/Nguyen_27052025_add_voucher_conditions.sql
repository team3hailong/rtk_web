-- Thêm các trường điều kiện mới vào bảng voucher
-- Các trường này sẽ giới hạn việc áp dụng voucher dựa trên các điều kiện cụ thể
ALTER TABLE voucher 
ADD COLUMN max_sa INT NULL DEFAULT NULL COMMENT 'Số lượng tài khoản survey tối đa được phép áp dụng mã voucher. NULL = không giới hạn',
ADD COLUMN location_id INT NULL DEFAULT NULL COMMENT 'Tỉnh được áp dụng mã voucher. NULL = áp dụng cho tất cả các tỉnh',
ADD COLUMN package_id INT NULL DEFAULT NULL COMMENT 'Gói được áp dụng mã voucher. NULL = áp dụng cho tất cả các gói';

-- Thêm foreign key cho các trường mới
ALTER TABLE voucher
ADD CONSTRAINT fk_voucher_location FOREIGN KEY (location_id) REFERENCES location(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT fk_voucher_package FOREIGN KEY (package_id) REFERENCES package(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Thêm index để tối ưu hiệu suất
ALTER TABLE voucher
ADD INDEX idx_voucher_max_sa (max_sa),
ADD INDEX idx_voucher_location (location_id),
ADD INDEX idx_voucher_package (package_id);
