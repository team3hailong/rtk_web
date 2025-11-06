-- Migration: Add station auto schedule configuration table
-- Created: 2025-11-06
-- Purpose: Store which stations should be automatically started/stopped

CREATE TABLE IF NOT EXISTS `station_auto_schedule` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `station_id` INT(11) NOT NULL,
  `auto_start` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = auto start at 6:00 AM, 0 = manual only',
  `auto_stop` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = auto stop at 8:00 PM, 0 = manual only',
  `start_time` TIME DEFAULT '06:00:00' COMMENT 'Time to auto start',
  `stop_time` TIME DEFAULT '20:00:00' COMMENT 'Time to auto stop',
  `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = schedule active, 0 = disabled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_station` (`station_id`),
  KEY `idx_auto_start` (`auto_start`),
  KEY `idx_auto_stop` (`auto_stop`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment
ALTER TABLE `station_auto_schedule` COMMENT = 'Configuration for automatic station start/stop scheduling';
