-- Fix for stored procedure update_user_rankings to avoid transaction issues in triggers

-- Drop existing procedure
DROP PROCEDURE IF EXISTS update_user_rankings;

-- Recreate without transactions
DELIMITER //
CREATE PROCEDURE update_user_rankings()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE user_id_var INT;
    DECLARE ref_count INT;
    DECLARE month_commission DECIMAL(10,2);
    DECLARE total_commission DECIMAL(10,2);
    
    -- Get all users and their commission data
    DECLARE user_cursor CURSOR FOR
        SELECT 
            u.id, 
            COUNT(DISTINCT ru.referred_user_id) AS referral_count,
            COALESCE(SUM(CASE 
                WHEN rc.created_at >= DATE_FORMAT(CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-01') 
                AND rc.status IN ('approved', 'paid') 
                THEN rc.commission_amount ELSE 0 END), 0) AS monthly_commission,
            COALESCE(SUM(CASE 
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
    
    -- Removed transaction handling to avoid errors in triggers
    
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
    
    -- Removed COMMIT statement to avoid errors in triggers
END //
DELIMITER ;
