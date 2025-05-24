-- ==========================================
-- 10. Purchase Type and VAT on Registration (24-05-2025)
-- ==========================================

ALTER TABLE `registration`
  ADD `purchase_type` ENUM('company','individual') NOT NULL DEFAULT 'individual' COMMENT 'Loại mua: company - cty, individual - cá nhân',
  ADD `invoice_allowed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cho phép xuất hóa đơn';

-- Trigger to set VAT percent, amount, invoice_allowed and total_price
DROP TRIGGER IF EXISTS `trg_set_registration_vat`;
DELIMITER //
CREATE TRIGGER `trg_set_registration_vat`
  BEFORE INSERT ON `registration`
  FOR EACH ROW
BEGIN
  IF NEW.`purchase_type` = 'company' THEN
    SET NEW.`vat_percent` = 10;
    SET NEW.`vat_amount` = ROUND((NEW.`base_price` * COALESCE(NEW.`num_account`,1)) * 0.1, 2);
    SET NEW.`invoice_allowed` = 1;
  ELSE
    SET NEW.`vat_percent` = 0;
    SET NEW.`vat_amount` = 0;
    SET NEW.`invoice_allowed` = 0;
  END IF;
  SET NEW.`total_price` = ROUND((NEW.`base_price` * COALESCE(NEW.`num_account`,1)) + NEW.`vat_amount`, 2);
END;//
DELIMITER ;

-- Trigger to set VAT percent, amount, invoice_allowed and total_price on updates
DROP TRIGGER IF EXISTS `trg_update_registration_vat_update`;
DELIMITER //
CREATE TRIGGER `trg_update_registration_vat_update`
  BEFORE UPDATE ON `registration`
  FOR EACH ROW
BEGIN
  IF NEW.`purchase_type` = 'company' THEN
    SET NEW.`vat_percent` = 10;
    SET NEW.`vat_amount` = ROUND((NEW.`base_price` * COALESCE(NEW.`num_account`,1)) * 0.1, 2);
    SET NEW.`invoice_allowed` = 1;
  ELSE
    SET NEW.`vat_percent` = 0;
    SET NEW.`vat_amount` = 0;
    SET NEW.`invoice_allowed` = 0;
  END IF;
  SET NEW.`total_price` = ROUND((NEW.`base_price` * COALESCE(NEW.`num_account`,1)) + NEW.`vat_amount`, 2);
END;//
DELIMITER ;

