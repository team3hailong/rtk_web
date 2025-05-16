-- Thêm trường notify_content vào bảng activity_logs
ALTER TABLE activity_logs ADD COLUMN notify_content TEXT AFTER new_values;
