-- Migration: Add simplified ranking table
-- Date: 2025-05-20

-- Table to store user ranking data without explicit rank columns
CREATE TABLE IF NOT EXISTS `user_ranking` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `referral_count` INT NOT NULL DEFAULT 0,
  `monthly_commission` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_commission` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_ranking` (`user_id`),
  INDEX `idx_total_commission` (`total_commission`),
  INDEX `idx_monthly_commission` (`monthly_commission`),
  CONSTRAINT `fk_ranking_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Procedure to update user rankings
DELIMITER //
CREATE PROCEDURE update_user_rankings()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE user_id_var INT;
    DECLARE ref_count INT;
    DECLARE month_commission DECIMAL(15,2);
    DECLARE total_commission DECIMAL(15,2);
    
    -- Cursor for selecting users with their totals
    DECLARE user_cursor CURSOR FOR
        SELECT 
            u.id AS user_id,
            COUNT(DISTINCT ru.referred_user_id) AS referral_count,
            IFNULL(SUM(CASE 
                WHEN rc.status IN ('approved', 'paid') 
                AND MONTH(rc.created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(rc.created_at) = YEAR(CURRENT_DATE()) 
                THEN rc.commission_amount ELSE 0 END), 0) AS monthly_commission,
            IFNULL(SUM(CASE 
                WHEN rc.status IN ('approved', 'paid') 
                THEN rc.commission_amount ELSE 0 END), 0) AS total_commission
        FROM 
            user u
            LEFT JOIN referral r ON u.id = r.user_id
            LEFT JOIN referred_user ru ON r.user_id = ru.referrer_id
            LEFT JOIN referral_commission rc ON r.user_id = rc.referrer_id
        GROUP BY 
            u.id;

    -- Handler for NOT FOUND condition
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Start transaction
    START TRANSACTION;
    
    OPEN user_cursor;
    
    -- Loop through each user and update their commission data
    read_loop: LOOP
        FETCH user_cursor INTO user_id_var, ref_count, month_commission, total_commission;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Insert or update user ranking
        INSERT INTO user_ranking (
            user_id, 
            referral_count, 
            monthly_commission, 
            total_commission,
            created_at,
            updated_at
        ) VALUES (
            user_id_var, 
            ref_count, 
            month_commission, 
            total_commission, 
            NOW(),
            NOW()
        ) ON DUPLICATE KEY UPDATE
            referral_count = ref_count,
            monthly_commission = month_commission,
            total_commission = total_commission,
            updated_at = NOW();
    END LOOP;
    
    CLOSE user_cursor;
    
    COMMIT;
END //
DELIMITER ;

-- Event to reset monthly commission at the beginning of each month
DELIMITER //
CREATE EVENT IF NOT EXISTS reset_monthly_commission
ON SCHEDULE EVERY 1 MONTH
STARTS TIMESTAMP(CONCAT(DATE_FORMAT(LAST_DAY(NOW() + INTERVAL 1 MONTH), '%Y-%m-01'), ' 00:00:01'))
DO
BEGIN
    UPDATE user_ranking SET monthly_commission = 0.00, updated_at = NOW();
    CALL update_user_rankings();
END //
DELIMITER ;

-- Event to update user rankings every hour
DELIMITER //
CREATE EVENT IF NOT EXISTS hourly_ranking_update
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL update_user_rankings();
END //
DELIMITER ;

-- Trigger to update user rankings when a commission is added or updated
DELIMITER //
CREATE TRIGGER trg_update_ranking_after_commission_change
AFTER INSERT ON referral_commission
FOR EACH ROW
BEGIN
    -- Call procedure to update all rankings
    CALL update_user_rankings();
END //
DELIMITER ;

-- Trigger to update user rankings when a commission status changes
DELIMITER //
CREATE TRIGGER trg_update_ranking_after_commission_update
AFTER UPDATE ON referral_commission
FOR EACH ROW
BEGIN
    -- Only update ranking if status changed to a status that affects the calculations
    IF OLD.status != NEW.status OR OLD.commission_amount != NEW.commission_amount THEN
        CALL update_user_rankings();
    END IF;
END //
DELIMITER ;

-- Trigger to update user rankings when a new referral is added
DELIMITER //
CREATE TRIGGER trg_update_ranking_after_referral_add
AFTER INSERT ON referred_user
FOR EACH ROW
BEGIN
    CALL update_user_rankings();
END //
DELIMITER ;

-- Initial population of the ranking table
CALL update_user_rankings();
