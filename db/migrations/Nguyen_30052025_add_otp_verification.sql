-- Add OTP verification fields to user table
ALTER TABLE user 
ADD COLUMN email_verify_otp VARCHAR(6) DEFAULT NULL COMMENT 'OTP code for email verification',
ADD COLUMN email_verify_otp_expires_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Expiration time for email verification OTP',
ADD COLUMN password_reset_otp VARCHAR(6) DEFAULT NULL COMMENT 'OTP code for password reset',
ADD COLUMN password_reset_otp_expires_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Expiration time for password reset OTP';

-- Index for performance
CREATE INDEX idx_email_verify_otp ON user(email_verify_otp);
CREATE INDEX idx_password_reset_otp ON user(password_reset_otp);
