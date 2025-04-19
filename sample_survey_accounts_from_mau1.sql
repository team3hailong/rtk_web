-- --- Sample Survey Accounts (Linked to Registrations 19 and 20 from mau1.sql) ---
-- **IMPORTANT**: Passwords here are examples and NOT secure. Use hashed passwords in production.
-- Ensure these IDs are unique or delete existing before running
DELETE FROM `survey_account` WHERE `id` IN ('ACC_REG19_USER6_01', 'ACC_REG20_USER6_01');

-- Account 1 - Linked to Registration 19 (User 6, Location 19 - Đồng Nai, Package 1)
-- Assuming this registration is 'pending', set enabled=0 initially
INSERT INTO `survey_account` (`id`, `registration_id`, `username_acc`, `password_acc`, `concurrent_user`, `enabled`, `created_at`) VALUES
('ACC_REG19_USER6_01', 19, 'user6_dongnai_01', 'hashed_password_19', 1, 0, NOW()); -- Use password_hash() in PHP

-- Account 2 - Linked to Registration 20 (User 6, Location 17 - Đắk Nông, Package 1)
-- Assuming this registration is 'pending', set enabled=0 initially
INSERT INTO `survey_account` (`id`, `registration_id`, `username_acc`, `password_acc`, `concurrent_user`, `enabled`, `created_at`) VALUES
('ACC_REG20_USER6_01', 20, 'user6_daknong_01', 'hashed_password_20', 1, 0, NOW()); -- Use password_hash() in PHP

COMMIT;
