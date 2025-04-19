-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: surveying_account
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `activity_logs_chk_1` CHECK (json_valid(`old_values`)),
  CONSTRAINT `activity_logs_chk_2` CHECK (json_valid(`new_values`))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'login','user','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-15 01:06:22'),(2,1,'logout','user','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-15 01:07:49'),(3,1,'login','user','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-15 01:07:57'),(4,1,'logout','user','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-15 01:08:07'),(5,1,'login','user','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-15 01:08:22'),(6,3,'login','user','3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-15 18:50:20'),(7,3,'login','user','3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 10:38:13'),(8,3,'login','user','3',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 11:11:24'),(9,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 11:13:06'),(10,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 11:30:46'),(11,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 12:01:15'),(12,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 13:19:15'),(13,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 13:24:16'),(14,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 13:26:01'),(15,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 13:26:47'),(16,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 13:34:42'),(17,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 13:38:44'),(18,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 15:13:28'),(19,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 15:18:54'),(20,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 15:28:39'),(21,6,'login','user','6',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36','2025-04-19 16:51:30');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('superadmin','admin','operator') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_username` (`admin_username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'System Admin','admin','$2y$10$7a9Qf314eaCMkCJIlLO12OVuVs0KjUq4/Y43A6H9zg/Jf7v/TRHFu','superadmin','2025-04-14 16:52:22',NULL);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaborator`
--

DROP TABLE IF EXISTS `collaborator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collaborator` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `referral_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `collaborator_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborator`
--

LOCK TABLES `collaborator` WRITE;
/*!40000 ALTER TABLE `collaborator` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaborator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `error_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stack_trace` text COLLATE utf8mb4_unicode_ci,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_error_type` (`error_type`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_logs`
--

LOCK TABLES `error_logs` WRITE;
/*!40000 ALTER TABLE `error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location` (
  `id` int NOT NULL AUTO_INCREMENT,
  `province` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location`
--

LOCK TABLES `location` WRITE;
/*!40000 ALTER TABLE `location` DISABLE KEYS */;
INSERT INTO `location` VALUES (1,'An Giang','2025-04-19 12:15:05'),(2,'Bà Rịa - Vũng Tàu','2025-04-19 12:15:05'),(3,'Bắc Giang','2025-04-19 12:15:05'),(4,'Bắc Kạn','2025-04-19 12:15:05'),(5,'Bạc Liêu','2025-04-19 12:15:05'),(6,'Bắc Ninh','2025-04-19 12:15:05'),(7,'Bến Tre','2025-04-19 12:15:05'),(8,'Bình Định','2025-04-19 12:15:05'),(9,'Bình Dương','2025-04-19 12:15:05'),(10,'Bình Phước','2025-04-19 12:15:05'),(11,'Bình Thuận','2025-04-19 12:15:05'),(12,'Cà Mau','2025-04-19 12:15:05'),(13,'Cần Thơ','2025-04-19 12:15:05'),(14,'Cao Bằng','2025-04-19 12:15:05'),(15,'Đà Nẵng','2025-04-19 12:15:05'),(16,'Đắk Lắk','2025-04-19 12:15:05'),(17,'Đắk Nông','2025-04-19 12:15:05'),(18,'Điện Biên','2025-04-19 12:15:05'),(19,'Đồng Nai','2025-04-19 12:15:05'),(20,'Đồng Tháp','2025-04-19 12:15:05'),(21,'Gia Lai','2025-04-19 12:15:05'),(22,'Hà Giang','2025-04-19 12:15:05'),(23,'Hà Nam','2025-04-19 12:15:05'),(24,'Hà Nội','2025-04-19 12:15:05'),(25,'Hà Tĩnh','2025-04-19 12:15:05'),(26,'Hải Dương','2025-04-19 12:15:05'),(27,'Hải Phòng','2025-04-19 12:15:05'),(28,'Hậu Giang','2025-04-19 12:15:05'),(29,'Hòa Bình','2025-04-19 12:15:05'),(30,'Hưng Yên','2025-04-19 12:15:05'),(31,'Khánh Hòa','2025-04-19 12:15:05'),(32,'Kiên Giang','2025-04-19 12:15:05'),(33,'Kon Tum','2025-04-19 12:15:05'),(34,'Lai Châu','2025-04-19 12:15:05'),(35,'Lâm Đồng','2025-04-19 12:15:05'),(36,'Lạng Sơn','2025-04-19 12:15:05'),(37,'Lào Cai','2025-04-19 12:15:05'),(38,'Long An','2025-04-19 12:15:05'),(39,'Nam Định','2025-04-19 12:15:05'),(40,'Nghệ An','2025-04-19 12:15:05'),(41,'Ninh Bình','2025-04-19 12:15:05'),(42,'Ninh Thuận','2025-04-19 12:15:05'),(43,'Phú Thọ','2025-04-19 12:15:05'),(44,'Phú Yên','2025-04-19 12:15:05'),(45,'Quảng Bình','2025-04-19 12:15:05'),(46,'Quảng Nam','2025-04-19 12:15:05'),(47,'Quảng Ngãi','2025-04-19 12:15:05'),(48,'Quảng Ninh','2025-04-19 12:15:05'),(49,'Quảng Trị','2025-04-19 12:15:05'),(50,'Sóc Trăng','2025-04-19 12:15:05'),(51,'Sơn La','2025-04-19 12:15:05'),(52,'Tây Ninh','2025-04-19 12:15:05'),(53,'Thái Bình','2025-04-19 12:15:05'),(54,'Thái Nguyên','2025-04-19 12:15:05'),(55,'Thanh Hóa','2025-04-19 12:15:05'),(56,'Thừa Thiên Huế','2025-04-19 12:15:05'),(57,'Tiền Giang','2025-04-19 12:15:05'),(58,'TP Hồ Chí Minh','2025-04-19 12:15:05'),(59,'Trà Vinh','2025-04-19 12:15:05'),(60,'Tuyên Quang','2025-04-19 12:15:05'),(61,'Vĩnh Long','2025-04-19 12:15:05'),(62,'Vĩnh Phúc','2025-04-19 12:15:05'),(63,'Yên Bái','2025-04-19 12:15:05');
/*!40000 ALTER TABLE `location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mount_point`
--

DROP TABLE IF EXISTS `mount_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mount_point` (
  `id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int NOT NULL,
  `ip` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int NOT NULL,
  `mountpoint` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `mount_point_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mount_point`
--

LOCK TABLES `mount_point` WRITE;
/*!40000 ALTER TABLE `mount_point` DISABLE KEYS */;
INSERT INTO `mount_point` VALUES ('1',1,'192.168.1.100',2101,'HANOI01'),('MP_HN_01',57,'192.168.1.100',2101,'HANOI05');
/*!40000 ALTER TABLE `mount_point` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package`
--

DROP TABLE IF EXISTS `package`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `package` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `duration_text` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `features_json` text COLLATE utf8mb4_unicode_ci,
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0',
  `button_text` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chọn Gói',
  `savings_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_package_id` (`package_id`),
  KEY `idx_active_order` (`is_active`,`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package`
--

LOCK TABLES `package` WRITE;
/*!40000 ALTER TABLE `package` DISABLE KEYS */;
INSERT INTO `package` VALUES (1,'monthly','Gói 1 Tháng',100000.00,'/ 1 tháng','[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ cơ bản\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"10 lượt đo đạc / ngày\",\"available\":true}]',0,'Chọn Gói',NULL,1,10,'2025-04-19 12:14:35','2025-04-19 15:39:26'),(2,'quarterly','Gói 3 Tháng',270000.00,'/ 3 tháng','[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ cơ bản\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"15 lượt đo đạc / ngày\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ thấp\",\"available\":true}]',0,'Chọn Gói',NULL,1,20,'2025-04-19 12:14:35',NULL),(3,'biannual','Gói 6 Tháng',500000.00,'/ 6 tháng','[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ tiêu chuẩn\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"25 lượt đo đạc / ngày\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ trung bình\",\"available\":true}]',0,'Chọn Gói',NULL,1,30,'2025-04-19 12:14:35',NULL),(4,'annual','Gói 1 Năm',900000.00,'/ 1 năm','[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ ưu tiên\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"50 lượt đo đạc / ngày\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ cao\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Truy cập sớm tính năng mới\",\"available\":true}]',1,'Chọn Gói',NULL,1,40,'2025-04-19 12:14:35','2025-04-19 15:56:38'),(5,'lifetime','Gói Vĩnh Viễn',5000000.00,'/ trọn đời','[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ VIP trọn đời\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Không giới hạn lượt đo đạc\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ cao nhất\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Mọi cập nhật trong tương lai\",\"available\":true}]',0,'Liên hệ mua',NULL,1,50,'2025-04-19 12:14:35',NULL);
/*!40000 ALTER TABLE `package` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `payment_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `export_invoice` tinyint(1) DEFAULT '0',
  `invoice_info` text COLLATE utf8mb4_unicode_ci,
  `confirmed` tinyint(1) DEFAULT '0',
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment`
--

LOCK TABLES `payment` WRITE;
/*!40000 ALTER TABLE `payment` DISABLE KEYS */;
INSERT INTO `payment` VALUES (1,19,'reg_19_1745056959.png',0,NULL,0,NULL,'2025-04-19 17:02:39','2025-04-19 17:02:39'),(2,20,'reg_20_1745057072.png',0,NULL,0,NULL,'2025-04-19 17:04:32','2025-04-19 17:04:32');
/*!40000 ALTER TABLE `payment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_holder` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration`
--

DROP TABLE IF EXISTS `registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `package_id` int NOT NULL,
  `location_id` int NOT NULL,
  `collaborator_id` int DEFAULT NULL,
  `num_account` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `base_price` decimal(15,2) NOT NULL,
  `vat_percent` float NOT NULL DEFAULT '0',
  `vat_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL,
  `status` enum('pending','active','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  KEY `location_id` (`location_id`),
  KEY `collaborator_id` (`collaborator_id`),
  KEY `idx_status_date` (`status`,`created_at`),
  KEY `idx_user_date` (`user_id`,`created_at`),
  CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `registration_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`),
  CONSTRAINT `registration_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`),
  CONSTRAINT `registration_ibfk_4` FOREIGN KEY (`collaborator_id`) REFERENCES `collaborator` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration`
--

LOCK TABLES `registration` WRITE;
/*!40000 ALTER TABLE `registration` DISABLE KEYS */;
INSERT INTO `registration` VALUES (19,6,1,19,NULL,1,'2025-04-19 10:02:13','2025-05-19 10:02:13',100000.00,0,0.00,100000.00,'pending','2025-04-19 17:02:13',NULL,NULL),(20,6,1,17,NULL,1,'2025-04-19 10:04:25','2025-05-19 10:04:25',100000.00,0,0.00,100000.00,'pending','2025-04-19 17:04:25',NULL,NULL),(22,6,1,14,NULL,1,'2025-04-19 11:41:36','2025-05-19 11:41:36',100000.00,0,0.00,100000.00,'pending','2025-04-19 18:41:36',NULL,NULL),(23,6,1,14,NULL,1,'2025-04-19 11:42:48','2025-05-19 11:42:48',100000.00,0,0.00,100000.00,'pending','2025-04-19 18:42:48',NULL,NULL),(24,6,1,17,NULL,1,'2025-04-19 11:49:43','2025-05-19 11:49:43',100000.00,0,0.00,100000.00,'pending','2025-04-19 18:49:43',NULL,NULL);
/*!40000 ALTER TABLE `registration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `station`
--

DROP TABLE IF EXISTS `station`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `station` (
  `id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mountpoint_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `long` decimal(11,8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mountpoint_id` (`mountpoint_id`),
  CONSTRAINT `station_ibfk_1` FOREIGN KEY (`mountpoint_id`) REFERENCES `mount_point` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `station`
--

LOCK TABLES `station` WRITE;
/*!40000 ALTER TABLE `station` DISABLE KEYS */;
INSERT INTO `station` VALUES ('STN_HN_A','Trạm Hà Nội A','1',21.02780000,105.83420000),('STN_HN_B','Trạm Hà Nội B','1',21.02880000,105.83520000),('STN_HN_C','Trạm Hà Nội C','1',21.02980000,105.83620000);
/*!40000 ALTER TABLE `station` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_account`
--

DROP TABLE IF EXISTS `survey_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_account` (
  `id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_id` int NOT NULL,
  `username_acc` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_acc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `concurrent_user` int DEFAULT '1',
  `enabled` tinyint(1) DEFAULT '1',
  `caster` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_type` int DEFAULT NULL,
  `regionIds` int DEFAULT NULL,
  `customerBizType` int DEFAULT '1',
  `area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_acc` (`username_acc`),
  KEY `registration_id` (`registration_id`),
  CONSTRAINT `survey_account_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_account`
--

LOCK TABLES `survey_account` WRITE;
/*!40000 ALTER TABLE `survey_account` DISABLE KEYS */;
INSERT INTO `survey_account` VALUES ('ACC_REG19_USER6_01',19,'user6_dongnai_01','hashed_password_19',1,0,NULL,NULL,NULL,1,NULL,'2025-04-19 18:30:02',NULL,NULL),('ACC_REG20_USER6_01',20,'user6_daknong_01','hashed_password_20',1,0,NULL,NULL,NULL,1,NULL,'2025-04-19 18:30:02',NULL,NULL);
/*!40000 ALTER TABLE `survey_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_history`
--

DROP TABLE IF EXISTS `transaction_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `registration_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `transaction_type` enum('purchase','renewal','refund') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transaction_history_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`),
  CONSTRAINT `transaction_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_history`
--

LOCK TABLES `transaction_history` WRITE;
/*!40000 ALTER TABLE `transaction_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaction_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_company` tinyint(1) DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_registered` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'Nguyen33443355','aa@gmail.com','$2y$10$K7GvgUFN2wMfhXLIjR93FuzdYkvRBlHoVJHbgE2d0fA/wgffC7Bxa','0999999999',0,NULL,NULL,NULL,'2025-04-15 01:06:13',NULL,NULL),(2,'Đỗ Văn Nguyên','a55@gmail.com','$2y$10$RijEyfrLRiE5iOF2787D/uV6JI.Q3dlVheEezN6yJACdu1nRUNImy','0981190567',0,'','',NULL,'2025-04-15 18:21:08',NULL,NULL),(3,'Đỗ Văn Nguyên','na@gmail.com','$2y$10$e/PAuV7aqrU0.1TF5Kk/2e3bFJlQ7.wVghx6k/ATcLUkjSBGLOada','0981190561',0,NULL,NULL,NULL,'2025-04-15 18:46:31',NULL,NULL),(4,'nguyen','an@gmail.com','12345678','0987654564',NULL,NULL,NULL,NULL,'2025-04-19 00:12:06',NULL,NULL),(5,'na@gmail.com','ab@gmail.com','$2y$10$7njlF59/cT0N/F.brYvKKuJBUjnQKc6SQW0UG9SJPolXbld1RbrVa','0981590564',0,NULL,NULL,NULL,'2025-04-19 10:37:49',NULL,NULL),(6,'test2','az@gmail.com','$2y$10$qaTgYQukWdh9jKnvIrk1keCjsnORwA2S8O/9lXOQjDwaZasM76xga','0999999443',0,NULL,NULL,NULL,'2025-04-19 11:12:23',NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `notification_email` tinyint(1) DEFAULT '1',
  `notification_sms` tinyint(1) DEFAULT '0',
  `theme_preference` enum('light','dark') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,1,1,0,'light','2025-04-15 01:06:13',NULL),(2,5,1,0,'light','2025-04-19 10:37:49',NULL),(3,6,1,0,'light','2025-04-19 11:12:23',NULL);
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawal`
--

DROP TABLE IF EXISTS `withdrawal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `collaborator_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `bank_info` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collaborator_id` (`collaborator_id`),
  CONSTRAINT `withdrawal_ibfk_1` FOREIGN KEY (`collaborator_id`) REFERENCES `collaborator` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawal`
--

LOCK TABLES `withdrawal` WRITE;
/*!40000 ALTER TABLE `withdrawal` DISABLE KEYS */;
/*!40000 ALTER TABLE `withdrawal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'surveying_account'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-19 22:19:42
