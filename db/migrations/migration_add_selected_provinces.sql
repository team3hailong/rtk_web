-- Migration: Thêm cột selected_provinces vào bảng registration
-- Date: 2025-10-06
-- Description: Cho phép lưu danh sách nhiều tỉnh cho một đăng ký tài khoản RTK

-- Thêm cột selected_provinces để lưu danh sách location_id dạng JSON
ALTER TABLE `registration`
ADD COLUMN `selected_provinces` JSON DEFAULT NULL COMMENT 'Danh sách location_id các tỉnh được chọn, VD: [21, 30, 40, 42]'
AFTER `location_id`;

-- Cập nhật dữ liệu cũ: Chuyển location_id hiện tại thành mảng JSON trong selected_provinces
UPDATE `registration`
SET `selected_provinces` = JSON_ARRAY(`location_id`)
WHERE `location_id` IS NOT NULL AND `selected_provinces` IS NULL;

-- Lưu ý:
-- - location_id sẽ giữ nguyên để backward compatibility (tỉnh đầu tiên/chính)
-- - selected_provinces lưu danh sách đầy đủ các tỉnh dạng JSON array
-- - Khi tạo tài khoản:
--   + Username lấy province_code từ location_id (tỉnh đầu tiên)
--   + Mountpoint lấy tất cả từ selected_provinces
