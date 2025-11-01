-- Kiểm tra trạng thái hiện tại của voucher
SELECT id, code, discount_value, discount_type, auto_approve, need_upload_proof, is_active
FROM voucher
WHERE is_active = 1
ORDER BY created_at DESC
LIMIT 10;

-- Cập nhật voucher để test (thay 'YOUR_VOUCHER_CODE' bằng mã voucher thực tế)
-- Ví dụ: Nếu voucher có discount 100% và muốn auto-approve
UPDATE voucher 
SET need_upload_proof = 0, 
    auto_approve = 1
WHERE code = 'YOUR_VOUCHER_CODE' 
  AND is_active = 1;

-- Kiểm tra lại sau khi cập nhật
SELECT id, code, discount_value, discount_type, auto_approve, need_upload_proof, is_active
FROM voucher
WHERE code = 'YOUR_VOUCHER_CODE';
