-- Migration: Add need_upload_proof column to voucher table
-- Date: 2025-06-02
-- Description: Thêm cột need_upload_proof để xác định voucher có cần upload ảnh minh chứng hay không

-- Add need_upload_proof column (default = 1: cần upload)
ALTER TABLE `voucher` 
ADD COLUMN `need_upload_proof` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT '1 = Cần upload minh chứng, 0 = Không cần upload' 
AFTER `auto_approve`;


-- Commit
COMMIT;
