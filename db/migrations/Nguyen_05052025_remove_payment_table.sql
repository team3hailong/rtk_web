-- Migration: Xóa bảng payment sau khi đã hoàn tất quá trình di chuyển dữ liệu
-- Date: May 5, 2025
-- Author: Nguyen

-- Xóa các ràng buộc khóa ngoại trước khi xóa bảng
ALTER TABLE `payment` DROP FOREIGN KEY `fk_payment_registration`;

-- Xóa bảng payment
DROP TABLE IF EXISTS `payment`;

-- Xóa các tham chiếu đến bảng payment trong code sẽ được xử lý trong mã nguồn PHP