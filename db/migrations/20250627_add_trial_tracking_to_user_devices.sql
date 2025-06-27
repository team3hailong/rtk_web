-- Add trial tracking columns to user_devices table
ALTER TABLE user_devices 
ADD COLUMN trial_used TINYINT(1) DEFAULT 0 COMMENT 'Whether this device has used the trial package',
ADD COLUMN trial_expire_date DATETIME DEFAULT NULL COMMENT 'Date when the trial restriction expires (3 months after usage)';

-- Update existing records to set default values
-- Assume existing devices haven't used trial yet
UPDATE user_devices SET trial_used = 0, trial_expire_date = NULL;
