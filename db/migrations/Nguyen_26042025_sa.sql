-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 26, 2025 at 01:59 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sa_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-24 17:23:51'),
(2, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-24 17:28:19'),
(3, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-24 21:49:53'),
(4, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-25 17:55:59'),
(5, 2, 'login', 'user', '2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-25 17:56:36'),
(6, 87, 'login', 'user', '87', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-25 18:08:21'),
(7, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-25 22:09:55'),
(8, 2, 'login', 'user', '2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-26 08:42:35'),
(9, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-26 08:42:59');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('superadmin','admin','operator') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collaborator`
--

CREATE TABLE `collaborator` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `referral_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--

CREATE TABLE `error_logs` (
  `id` int NOT NULL,
  `error_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stack_trace` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `id` int NOT NULL,
  `province` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`id`, `province`, `province_code`, `status`, `created_at`) VALUES
(1, 'An Giang', 'AGG', 1, '2025-04-19 12:15:05'),
(2, 'Bà Rịa - Vũng Tàu', 'BVT', 1, '2025-04-19 12:15:05'),
(3, 'Bắc Giang', 'BGG', 1, '2025-04-19 12:15:05'),
(4, 'Bắc Kạn', 'BKN', 1, '2025-04-19 12:15:05'),
(5, 'Bạc Liêu', 'BLU', 1, '2025-04-19 12:15:05'),
(6, 'Bắc Ninh', 'BNH', 1, '2025-04-19 12:15:05'),
(7, 'Bến Tre', 'BTE', 1, '2025-04-19 12:15:05'),
(8, 'Bình Định', 'BDH', 1, '2025-04-19 12:15:05'),
(9, 'Bình Dương', 'BDG', 1, '2025-04-19 12:15:05'),
(10, 'Bình Phước', 'BPC', 1, '2025-04-19 12:15:05'),
(11, 'Bình Thuận', 'BTN', 1, '2025-04-19 12:15:05'),
(12, 'Cà Mau', 'CMA', 1, '2025-04-19 12:15:05'),
(13, 'Cần Thơ', 'CTO', 1, '2025-04-19 12:15:05'),
(14, 'Cao Bằng', 'CBG', 1, '2025-04-19 12:15:05'),
(15, 'Đà Nẵng', 'DNG', 1, '2025-04-19 12:15:05'),
(16, 'Đắk Lắk', 'DLK', 1, '2025-04-19 12:15:05'),
(17, 'Đắk Nông', 'DNG', 1, '2025-04-19 12:15:05'),
(18, 'Điện Biên', 'DBN', 1, '2025-04-19 12:15:05'),
(19, 'Đồng Nai', 'DNI', 1, '2025-04-19 12:15:05'),
(20, 'Đồng Tháp', 'DTP', 1, '2025-04-19 12:15:05'),
(21, 'Gia Lai', 'GLI', 1, '2025-04-19 12:15:05'),
(22, 'Hà Giang', 'HGG', 1, '2025-04-19 12:15:05'),
(23, 'Hà Nam', 'HNM', 1, '2025-04-19 12:15:05'),
(24, 'Hà Nội', 'HNI', 1, '2025-04-19 12:15:05'),
(25, 'Hà Tĩnh', 'HTH', 1, '2025-04-19 12:15:05'),
(26, 'Hải Dương', 'HDG', 1, '2025-04-19 12:15:05'),
(27, 'Hải Phòng', 'HPG', 1, '2025-04-19 12:15:05'),
(28, 'Hậu Giang', 'HGG', 1, '2025-04-19 12:15:05'),
(29, 'Hòa Bình', 'HBI', 1, '2025-04-19 12:15:05'),
(30, 'Hưng Yên', 'HYE', 1, '2025-04-19 12:15:05'),
(31, 'Khánh Hòa', 'KHA', 1, '2025-04-19 12:15:05'),
(32, 'Kiên Giang', 'KGG', 1, '2025-04-19 12:15:05'),
(33, 'Kon Tum', 'KTM', 1, '2025-04-19 12:15:05'),
(34, 'Lai Châu', 'LCH', 1, '2025-04-19 12:15:05'),
(35, 'Lâm Đồng', 'LDM', 1, '2025-04-19 12:15:05'),
(36, 'Lạng Sơn', 'LSN', 1, '2025-04-19 12:15:05'),
(37, 'Lào Cai', 'LCI', 1, '2025-04-19 12:15:05'),
(38, 'Long An', 'LAN', 1, '2025-04-19 12:15:05'),
(39, 'Nam Định', 'NDI', 1, '2025-04-19 12:15:05'),
(40, 'Nghệ An', 'NAN', 1, '2025-04-19 12:15:05'),
(41, 'Ninh Bình', 'NBI', 1, '2025-04-19 12:15:05'),
(42, 'Ninh Thuận', 'NNT', 1, '2025-04-19 12:15:05'),
(43, 'Phú Thọ', 'PTO', 1, '2025-04-19 12:15:05'),
(44, 'Phú Yên', 'PYE', 1, '2025-04-19 12:15:05'),
(45, 'Quảng Bình', 'QBN', 1, '2025-04-19 12:15:05'),
(46, 'Quảng Nam', 'QNM', 1, '2025-04-19 12:15:05'),
(47, 'Quảng Ngãi', 'QNG', 1, '2025-04-19 12:15:05'),
(48, 'Quảng Ninh', 'QNI', 1, '2025-04-19 12:15:05'),
(49, 'Quảng Trị', 'QTR', 1, '2025-04-19 12:15:05'),
(50, 'Sóc Trăng', 'STR', 1, '2025-04-19 12:15:05'),
(51, 'Sơn La', 'SLA', 1, '2025-04-19 12:15:05'),
(52, 'Tây Ninh', 'TNN', 1, '2025-04-19 12:15:05'),
(53, 'Thái Bình', 'TBI', 1, '2025-04-19 12:15:05'),
(54, 'Thái Nguyên', 'TNN', 1, '2025-04-19 12:15:05'),
(55, 'Thanh Hóa', 'THA', 1, '2025-04-19 12:15:05'),
(56, 'Thừa Thiên Huế', 'TTH', 1, '2025-04-19 12:15:05'),
(57, 'Tiền Giang', 'TYG', 1, '2025-04-19 12:15:05'),
(58, 'TP Hồ Chí Minh', 'HCM', 1, '2025-04-19 12:15:05'),
(59, 'Trà Vinh', 'TVI', 1, '2025-04-19 12:15:05'),
(60, 'Tuyên Quang', 'TQU', 1, '2025-04-19 12:15:05'),
(61, 'Vĩnh Long', 'VLO', 1, '2025-04-19 12:15:05'),
(62, 'Vĩnh Phúc', 'VPH', 1, '2025-04-19 12:15:05'),
(63, 'Yên Bái', 'YBI', 1, '2025-04-19 12:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `mount_point`
--

CREATE TABLE `mount_point` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int NOT NULL,
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int NOT NULL,
  `mountpoint` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mount_point`
--

INSERT INTO `mount_point` (`id`, `location_id`, `ip`, `port`, `mountpoint`) VALUES
('18', 27, '203.171.25.138', 1509, 'HaiPhong'),
('19', 30, '203.171.25.138', 1509, 'HungYen'),
('41', 51, '203.171.25.138', 1509, 'SonLa'),
('44', 63, '203.171.25.138', 1509, 'YBI_TPYenBai'),
('45', 63, '203.171.25.138', 1509, 'YBI_NghiaLo'),
('46', 63, '203.171.25.138', 1509, 'YBI_LucYen'),
('47', 63, '203.171.25.138', 1509, 'YBI_TramTau'),
('48', 63, '203.171.25.138', 1509, 'YBI_VanYen'),
('49', 63, '203.171.25.138', 1509, 'YBI_MuCangChai'),
('50', 51, '203.171.25.138', 1509, 'SLA_TPSonLa'),
('51', 48, '203.171.25.138', 1509, 'QuangNinh'),
('54', 54, '203.171.25.138', 1509, 'TNN_TPThaiNguyen'),
('55', 54, '203.171.25.138', 1509, 'TNN_VoNhai'),
('56', 54, '203.171.25.138', 1509, 'TNN_DaiTu'),
('57', 54, '203.171.25.138', 1509, 'TNN_PhoYen'),
('58', 54, '203.171.25.138', 1509, 'TNN_DinhHoa'),
('59', 54, '203.171.25.138', 1509, 'ThaiNguyen'),
('60', 63, '203.171.25.138', 1509, 'YenBai'),
('61', 27, '203.171.25.138', 1509, 'HPG_VinhBao'),
('63', 24, '203.171.25.138', 1509, 'HaNoi'),
('64', 63, '203.171.25.138', 1509, 'YBI_XuanAI'),
('65', 51, '203.171.25.138', 1509, 'SLA_MocChau'),
('66', 27, '203.171.25.138', 1509, 'VpHaiPhong'),
('67', 27, '203.171.25.138', 1509, 'HPG_LeChan'),
('68', 16, '203.171.25.138', 1509, 'P5_HaNoi'),
('69', 24, '203.171.25.138', 1509, 'HNI_CauGiay'),
('70', 48, '203.171.25.138', 1509, 'QNH_HaLong'),
('71', 30, '203.171.25.138', 1509, 'HYN_AnThi');

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `id` int NOT NULL,
  `package_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `duration_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `features_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0',
  `button_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chọn Gói',
  `savings_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package`
--

INSERT INTO `package` (`id`, `package_id`, `name`, `price`, `duration_text`, `features_json`, `is_recommended`, `button_text`, `savings_text`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'monthly', 'Gói 1 Tháng', 100000.00, '/ 1 tháng', '[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ cơ bản\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"10 lượt đo đạc / ngày\",\"available\":true}]', 0, 'Chọn Gói', NULL, 1, 10, '2025-04-19 12:14:35', '2025-04-19 15:39:26'),
(2, 'quarterly', 'Gói 3 Tháng', 270000.00, '/ 3 tháng', '[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ cơ bản\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"15 lượt đo đạc / ngày\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ thấp\",\"available\":true}]', 0, 'Chọn Gói', NULL, 1, 20, '2025-04-19 12:14:35', NULL),
(3, 'biannual', 'Gói 6 Tháng', 500000.00, '/ 6 tháng', '[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ tiêu chuẩn\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"25 lượt đo đạc / ngày\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ trung bình\",\"available\":true}]', 0, 'Chọn Gói', NULL, 1, 30, '2025-04-19 12:14:35', NULL),
(4, 'annual', 'Gói 1 Năm', 900000.00, '/ 1 năm', '[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ ưu tiên\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"50 lượt đo đạc / ngày\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ cao\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Truy cập sớm tính năng mới\",\"available\":true}]', 1, 'Chọn Gói', NULL, 1, 40, '2025-04-19 12:14:35', '2025-04-19 15:56:38'),
(5, 'lifetime', 'Gói Vĩnh Viễn', 5000000.00, '/ trọn đời', '[{\"icon\":\"fa-check\",\"text\":\"Truy cập đầy đủ tính năng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ VIP trọn đời\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Không giới hạn lượt đo đạc\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Ưu tiên hỗ trợ cao nhất\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Mọi cập nhật trong tương lai\",\"available\":true}]', 0, 'Liên hệ mua', NULL, 1, 50, '2025-04-19 12:14:35', NULL),
(7, 'trial_7d', 'Gói Dùng Thử 7 Ngày', 0.00, '/ 7 ngày', '[{\"icon\":\"fa-check\",\"text\":\"Truy cập tính năng cơ bản\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"Hỗ trợ cộng đồng\",\"available\":true},{\"icon\":\"fa-check\",\"text\":\"5 lượt đo đạc / ngày\",\"available\":true}]', 0, 'Dùng Thử Miễn Phí', NULL, 1, 5, '2025-04-20 21:25:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int NOT NULL,
  `registration_id` int NOT NULL,
  `payment_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `export_invoice` tinyint(1) DEFAULT '0',
  `invoice_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `confirmed` tinyint(1) DEFAULT '0',
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_holder` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `package_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `collaborator_id` int DEFAULT NULL,
  `num_account` int DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `base_price` decimal(15,2) DEFAULT NULL,
  `vat_percent` float NOT NULL DEFAULT '0',
  `vat_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL,
  `status` enum('pending','active','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `user_id`, `package_id`, `location_id`, `collaborator_id`, `num_account`, `start_time`, `end_time`, `base_price`, `vat_percent`, `vat_amount`, `total_price`, `status`, `created_at`, `updated_at`, `deleted_at`, `rejection_reason`) VALUES
(5, 1, 7, 63, NULL, 1, '2025-04-25 15:10:06', '2025-05-02 15:10:06', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-25 22:10:06', '2025-04-25 22:10:06', NULL, NULL),
(6, 1, 7, 63, NULL, 1, '2025-04-25 15:11:26', '2025-05-02 15:11:26', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:11:26', '2025-04-25 22:11:27', NULL, NULL),
(7, 1, 7, 63, NULL, 1, '2025-04-25 15:22:07', '2025-05-02 15:22:07', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:22:07', '2025-04-25 22:22:08', NULL, NULL),
(8, 1, 7, 54, NULL, 1, '2025-04-25 15:23:20', '2025-05-02 15:23:20', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:23:20', '2025-04-25 22:23:21', NULL, NULL),
(9, 1, 7, 63, NULL, 1, '2025-04-25 15:32:38', '2025-05-02 15:32:38', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:32:38', '2025-04-25 22:32:39', NULL, NULL),
(10, 1, 7, 63, NULL, 1, '2025-04-25 15:36:09', '2025-05-02 15:36:09', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:36:09', '2025-04-25 22:36:10', NULL, NULL),
(11, 1, 7, 63, NULL, 1, '2025-04-25 15:40:36', '2025-05-02 15:40:36', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:40:36', '2025-04-25 22:40:37', NULL, NULL),
(12, 1, 7, 63, NULL, 1, '2025-04-25 15:41:48', '2025-05-02 15:41:48', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:41:48', '2025-04-25 22:41:50', NULL, NULL),
(13, 1, 7, 63, NULL, 1, '2025-04-25 15:44:37', '2025-05-02 15:44:37', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:44:37', '2025-04-25 22:44:38', NULL, NULL),
(14, 1, 7, 63, NULL, 1, '2025-04-25 15:45:18', '2025-05-02 15:45:18', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:45:18', '2025-04-25 22:45:19', NULL, NULL),
(15, 1, 7, 63, NULL, 1, '2025-04-25 15:45:54', '2025-05-02 15:45:54', 0.00, 0, 0.00, 0.00, 'active', '2025-04-25 22:45:54', '2025-04-25 22:45:55', NULL, NULL),
(16, 1, 7, 63, NULL, 1, '2025-04-26 01:56:09', '2025-05-03 01:56:09', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-26 08:56:09', '2025-04-26 08:56:09', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `station`
--

CREATE TABLE `station` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `identificationName` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mountpoint_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `long` decimal(11,8) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái hoạt động của trạm (1: active, 0: inactive)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `station`
--

INSERT INTO `station` (`id`, `station_name`, `identificationName`, `mountpoint_id`, `lat`, `long`, `status`) VALUES
('25', 'HPG1', 'VP Hải Phòng - 136 Dương Đình Nghệ', '67', 20.83045360, 106.67064669, 1),
('26', 'HPG2', 'Nhà a Cương - Vĩnh Bảo|Hưng - 0379527233', '61', 20.71929979, 106.46123062, 1),
('28', 'QNH5', 'Nhà cậu a Dũng - Hạ Long|Toản - 0934496886', '70', 20.94945693, 107.10729783, 1),
('30', 'HYN1', 'Nhà a Hữu - bạn a Dũng - Ân Thi|A Hữu - 0979514287', '71', 20.82821906, 106.08995397, 1),
('34', 'HNI0', 'VP Hà Nội - 216 Trung Kính', '69', 21.02222257, 105.79144295, 1),
('37', 'SLA2', 'Nhà a Trung - Mộc Châu - Sơn La|A Trung - 0976698698', '65', 20.84684019, 104.63939672, 1),
('38', 'YBI4', 'Vp ban quản lý Nghĩa Lộ|Anh Hiếu - 0865251185', '45', 21.59752950, 104.51231468, 1),
('41', 'YBI6', 'Nhà con gái a Tuấn - Trạm Tấu|A Tuấn - 0859596050', '47', 21.46597839, 104.38110734, 1),
('42', 'YBI5', 'VP ban quan lý dự án Lục Yên|Hải - 0378639689', '46', 22.11158723, 104.76653605, 1),
('43', 'YBI8', 'Nhà anh Tuân - Mù Cang Chải|A Tuân - 0987019029', '49', 21.84980281, 104.08574760, 1),
('44', 'YBI1', 'Nhà a Việt Béo - TP Yên bái|A Việt - 0947366066', '44', 21.71137621, 104.90685342, 1),
('45', 'YBI7', 'Nhà anh Chiến - Văn Yên|A Chiến - 0977886300', '48', 21.96978018, 104.56571434, 1),
('49', 'TNN1', 'Nhà A Hưng râu - TP Thái Nguyên|A Hưng - 0982892196', '54', 21.57363716, 105.84031625, 1),
('50', 'SL12', 'Nhà anh Tân - Tp Son La| Anh Tân - 0335027798', '50', 21.32205139, 103.91523536, 1),
('53', 'TNN6', 'Nhà anh Phú - Định hoá| a Phú - 0867666929', '58', 21.91116659, 105.64929213, 1),
('54', 'TNN4', 'vpdkdd Đại Từ|Đỗ Đình Long - 0355055740', '56', 21.63442798, 105.63582762, 1),
('55', 'TNN3', 'Nhà anh Thoi - Võ Nhai|Anh Việt - 0353177492', '55', 21.75411886, 106.07746349, 1),
('56', 'TNN5', 'Nhà anh Long - Phổ Yên|Anh Long - 0986650808', '57', 21.41624565, 105.86203136, 1),
('57', 'YBI2', 'Nhà anh Tuấn - Văn Yên|Anh Tuấn - 0963844634', '64', 21.85003200, 104.70568793, 1),
('58', 'G216', 'Trạm a Hưng gửi', '67', 20.96750510, 106.71247261, 1),
('59', 'P501', 'tram Ha Noi', '69', 21.02222257, 105.79144295, 1);

-- --------------------------------------------------------

--
-- Table structure for table `survey_account`
--

CREATE TABLE `survey_account` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_id` int NOT NULL,
  `username_acc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_acc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `concurrent_user` int DEFAULT '1',
  `enabled` tinyint(1) DEFAULT '1',
  `caster` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_type` int DEFAULT NULL,
  `regionIds` int DEFAULT NULL,
  `customerBizType` int DEFAULT '1',
  `area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `survey_account`
--

INSERT INTO `survey_account` (`id`, `registration_id`, `username_acc`, `password_acc`, `concurrent_user`, `enabled`, `caster`, `user_type`, `regionIds`, `customerBizType`, `area`, `created_at`, `updated_at`, `deleted_at`) VALUES
('RTK_10_1745595370', 10, 'TRIAL_YBI004', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:36:10', NULL, NULL),
('RTK_11_1745595637', 11, 'TRIAL_YBI005', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:40:37', NULL, NULL),
('RTK_12_1745595710', 12, 'TRIAL_YBI006', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:41:50', NULL, NULL),
('RTK_13_1745595878', 13, 'TRIAL_YBI007', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:44:38', NULL, NULL),
('RTK_14_1745595919', 14, 'TRIAL_YBI008', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:45:19', NULL, NULL),
('RTK_15_1745595955', 15, 'TRIAL_YBI009', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:45:55', NULL, NULL),
('RTK_6_1745593887', 6, 'TRIAL_YBI001', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:11:27', NULL, NULL),
('RTK_7_1745594528', 7, 'TRIAL_YBI002', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:22:08', NULL, NULL),
('RTK_8_1745594601', 8, 'TRIAL_TNN001', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:23:21', NULL, NULL),
('RTK_9_1745595159', 9, 'TRIAL_YBI003', '0868458693', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-25 22:32:39', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_history`
--

CREATE TABLE `transaction_history` (
  `id` int NOT NULL,
  `registration_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `transaction_type` enum('purchase','renewal','refund') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_history`
--

INSERT INTO `transaction_history` (`id`, `registration_id`, `user_id`, `transaction_type`, `amount`, `status`, `payment_method`, `created_at`, `updated_at`) VALUES
(5, 5, 1, 'purchase', 0.00, 'pending', NULL, '2025-04-25 22:10:06', '2025-04-25 22:10:06'),
(6, 6, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:11:26', '2025-04-25 22:11:27'),
(7, 7, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:22:07', '2025-04-25 22:22:08'),
(8, 8, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:23:20', '2025-04-25 22:23:21'),
(9, 9, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:32:38', '2025-04-25 22:32:39'),
(10, 10, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:36:09', '2025-04-25 22:36:10'),
(11, 11, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:40:36', '2025-04-25 22:40:37'),
(12, 12, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:41:48', '2025-04-25 22:41:50'),
(13, 13, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:44:37', '2025-04-25 22:44:38'),
(14, 14, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:45:18', '2025-04-25 22:45:19'),
(15, 15, 1, 'purchase', 0.00, 'completed', NULL, '2025-04-25 22:45:54', '2025-04-25 22:45:55'),
(16, 16, 1, 'purchase', 0.00, 'pending', NULL, '2025-04-26 08:56:09', '2025-04-26 08:56:09');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_company` tinyint(1) DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_registered` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái người dùng (1: active, 0: inactive)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `phone`, `is_company`, `company_name`, `tax_code`, `tax_registered`, `created_at`, `updated_at`, `deleted_at`, `status`) VALUES
(1, 'Công ty Sao đỏ', 'test@gmail.com', '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0868458693', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', '2025-04-24 17:23:39', NULL, 1),
(2, 'Diệm - Mộc Châu', 'test2@gmail.com', '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0384862037', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', '2025-04-25 17:56:30', NULL, 1),
(3, 'Luyện', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0973620683', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(4, 'A Hưng Râu', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0982892196', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(5, 'A Phú', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0867666929', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(6, 'Anh Thoi', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0949784474', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(7, 'Anh Long', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0355055740', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(8, 'Anh Phú', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0978549838', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(9, 'A Long - Phổ Yên', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0986650808', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(10, 'Nguyễn Văn Dần', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0972611283', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(11, 'Trần Hồng Quân', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0973563973', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(12, 'Anh Hải - Người sp trạm YBI005, Lục Yên', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0378639689', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(13, 'Ngô công Đoàn', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0868433888', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(14, 'A Đảm', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0838216612', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(15, 'A Ngô Như Hoàn - Văn Yên, YB, 2 sđt, 0382664629', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0382664629', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(16, 'A Đỗ Xuân Đức - Đồng Tâm,Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0859139931', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(17, 'A PHẠM VĂN VĨNH - Đồng Tâm,Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0968582808', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(18, 'Lê Quang Vinh - tp Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0932222183', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(19, 'A Hiếu - Nghĩa Lộ', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0865251185', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(20, 'nguyễn quang huy', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0971338390', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(21, 'Bùi Khắc Chung', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0976974149', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(22, 'Chú Việt Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0983094693', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(23, 'Trần Ngô Doãn - Văn Yên, tỉnh Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0383426694', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(24, 'Đặng Văn Huy - thành phố yên bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0822814999', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(25, 'a Sao - yên bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0868192431', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(26, 'Nguyễn Duy Tiến - Nghĩa Lộ, Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0915923333', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(27, 'HOÀNG  ĐỨC KHANG - Yên Ninh, Yên bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0979289056', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(28, 'Nguyễn minh Ngọc - yenbai', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0834759333', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(29, 'Phạm Trung Thông - tp Yên bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0332161883', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(30, 'Phùng Thái Hoàng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0944921285', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(31, 'A Thiện - ng của a Đảm YBI', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0919314836', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(32, 'ĐÀO MẠNH HÙNG', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0977795228', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(33, 'NGUYỄN VĂN TIẾN - Yenbai', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0972886393', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(34, 'Đặng Anh Tú', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0985870561', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(35, 'Trần Bắc Hải - hồng ha, thành phố yên bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0978319555', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(36, 'A Trương thế tùng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0944882080', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(37, 'Nguyễn Xuân Lập', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0984630874', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(38, 'Vũ Đức Dương - Văn Yên', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0343636556', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(39, 'Anh Minh - Nghĩa Lộ, Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0986445666', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(40, 'Cường - tp Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0975642061', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(41, 'Nguyễn Quang Triểu', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0986394000', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(42, 'Nguyễn Đức Cựu', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0349813227', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(43, 'Nguyễn công luận', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0913251121', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(44, 'A Nguyễn Đình Thấu - Yên Bình, tỉnh Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0974473838', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(45, 'Mạnh Hà - tp Yên Bái', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0813549838', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(46, 'Bùi quốc hưng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0395299064', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(47, 'Lê Mạnh Hùng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0394986390', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(48, 'A Nguyễn Trọng Nguyên', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0328663655', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(49, 'Lê Văn Hùng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0372493391', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(50, 'Hoàng Anh Lập', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0818115795', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(51, 'Sơn Ninh Quý', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0986333843', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(52, 'Bùi Văn Giáp', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0984004902', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(53, 'Đoàn Quang Hiệp', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0868638688', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(54, 'Đỗ Anh Dũng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0357503704', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(55, 'Trần Thế Anh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0988853917', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(56, 'Doàn Xuân Thiêm', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0982331325', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(57, 'Ban Quản Lý Dự Án Đầu Tư Xây Dựng Huyện Lục Yên', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0977356458', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(58, 'Nguyễn Quyết Thắng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0975953853', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(59, 'Nguyễn Đức Linh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0975025185', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(60, 'Bùi Văn Tân', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0335027798', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(61, 'Nguyễn Văn Thanh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0961868046', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(62, 'Lò Văn Cường', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0976972668', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(63, 'Lò Văn Thảo', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0355179351', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(64, 'Triệu Văn Khanh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0836014333', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(65, 'Tòng Văn Thiệp', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0374905929', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(66, 'Cầm Văn Mạnh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0386021020', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(67, 'Đào Thanh Tuấn', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0915591555', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(68, 'Vũ Đức Thanh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0338295913', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(69, 'Lê đình Hùng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0962941622', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(70, 'Ngô Đức Thanh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0986234273', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(71, 'Đỗ tuấn tùng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0984519659', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(72, 'Lương Công Hoả', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0962009680', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(73, 'Mạc Văn Tuyến', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0985792082', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(74, 'Ngô Xuân Ánh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0365288792', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(75, 'lê trung hiếu', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0327437329', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(76, 'Nguyễn Văn Khôi', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0972247587', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(77, 'TRIỆU HỒNG LÂM', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0374686852', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(78, 'Anh Chiến - sp trạm YBI7, Văn Yên', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0977886300', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(79, 'Chu Đình Thư', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0913413586', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(80, 'Lê duy Linh', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0981171289', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(81, 'Anh A', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0992838273', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(82, 'Nguyễn Văn Duyệt', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0836017666', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(83, 'Công ty TNHH Sông Hồng', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0858683931', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(84, 'Đỗ Xuân Phúc', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0972285435', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(85, 'Cường PostCoast', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0949902720', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(86, 'Trần Văn Đạt', NULL, '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0982679832', 0, NULL, NULL, NULL, '2025-04-24 16:19:04', NULL, NULL, 1),
(87, 'test3', 'test3@gmail.com', '$2y$10$ubMxDfBjEesNhyTp7EGqD.H71E/I.dqYzOHIrfDcp/CdRgtRiDeju', '0985190564', 0, NULL, NULL, NULL, '2025-04-25 18:08:15', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `notification_email` tinyint(1) DEFAULT '1',
  `notification_sms` tinyint(1) DEFAULT '0',
  `theme_preference` enum('light','dark') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `notification_email`, `notification_sms`, `theme_preference`, `created_at`, `updated_at`) VALUES
(1, 87, 1, 0, 'light', '2025-04-25 18:08:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal`
--

CREATE TABLE `withdrawal` (
  `id` int NOT NULL,
  `collaborator_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `bank_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_username` (`admin_username`);

--
-- Indexes for table `collaborator`
--
ALTER TABLE `collaborator`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_error_type` (`error_type`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mount_point`
--
ALTER TABLE `mount_point`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_package_id` (`package_id`),
  ADD KEY `idx_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `collaborator_id` (`collaborator_id`),
  ADD KEY `idx_status_date` (`created_at`),
  ADD KEY `idx_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `station`
--
ALTER TABLE `station`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mountpoint_id` (`mountpoint_id`);

--
-- Indexes for table `survey_account`
--
ALTER TABLE `survey_account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_acc` (`username_acc`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `transaction_history`
--
ALTER TABLE `transaction_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `withdrawal`
--
ALTER TABLE `withdrawal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collaborator_id` (`collaborator_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collaborator`
--
ALTER TABLE `collaborator`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transaction_history`
--
ALTER TABLE `transaction_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `withdrawal`
--
ALTER TABLE `withdrawal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `collaborator`
--
ALTER TABLE `collaborator`
  ADD CONSTRAINT `collaborator_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `mount_point`
--
ALTER TABLE `mount_point`
  ADD CONSTRAINT `mount_point_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`);

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `registration_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`),
  ADD CONSTRAINT `registration_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`),
  ADD CONSTRAINT `registration_ibfk_4` FOREIGN KEY (`collaborator_id`) REFERENCES `collaborator` (`id`);

--
-- Constraints for table `station`
--
ALTER TABLE `station`
  ADD CONSTRAINT `station_ibfk_1` FOREIGN KEY (`mountpoint_id`) REFERENCES `mount_point` (`id`);

--
-- Constraints for table `survey_account`
--
ALTER TABLE `survey_account`
  ADD CONSTRAINT `survey_account_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`);

--
-- Constraints for table `transaction_history`
--
ALTER TABLE `transaction_history`
  ADD CONSTRAINT `transaction_history_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`),
  ADD CONSTRAINT `transaction_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `withdrawal`
--
ALTER TABLE `withdrawal`
  ADD CONSTRAINT `withdrawal_ibfk_1` FOREIGN KEY (`collaborator_id`) REFERENCES `collaborator` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
