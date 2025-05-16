-- Add has_read column to activity_logs table
ALTER TABLE activity_logs ADD COLUMN has_read TINYINT DEFAULT 0 AFTER notify_content;
