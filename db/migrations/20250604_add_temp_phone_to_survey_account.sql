-- Add temp_phone column to survey_account table
ALTER TABLE `survey_account` ADD COLUMN `temp_phone` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `deleted_at`;

-- Create trigger to link survey_account with user accounts when phone numbers match
DELIMITER //

CREATE TRIGGER `link_survey_account_to_user_after_update`
AFTER UPDATE ON `survey_account`
FOR EACH ROW
BEGIN
    DECLARE user_id_found INT;
    
    -- Only proceed if temp_phone is not NULL and has been updated
    IF NEW.temp_phone IS NOT NULL AND (OLD.temp_phone IS NULL OR OLD.temp_phone != NEW.temp_phone) THEN
        -- Find the user ID with matching phone number
        SELECT id INTO user_id_found 
        FROM `user`
        WHERE `phone` = NEW.temp_phone
        LIMIT 1;
        
        -- If matching user found
        IF user_id_found IS NOT NULL THEN
            -- Update the registration record
            UPDATE `registration`
            SET `user_id` = user_id_found
            WHERE `id` = NEW.registration_id;
        END IF;
    END IF;
END//

-- Also create an insert trigger to handle the case when temp_phone is set during record creation
CREATE TRIGGER `link_survey_account_to_user_after_insert`
AFTER INSERT ON `survey_account`
FOR EACH ROW
BEGIN
    DECLARE user_id_found INT;
    
    -- Only proceed if temp_phone is not NULL
    IF NEW.temp_phone IS NOT NULL THEN
        -- Find the user ID with matching phone number
        SELECT id INTO user_id_found 
        FROM `user`
        WHERE `phone` = NEW.temp_phone
        LIMIT 1;
        
        -- If matching user found
        IF user_id_found IS NOT NULL THEN
            -- Update the registration record
            UPDATE `registration`
            SET `user_id` = user_id_found
            WHERE `id` = NEW.registration_id;
        END IF;
    END IF;
END//

DELIMITER ;