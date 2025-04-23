-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 23, 2025 at 03:22 PM
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
-- Database: `surveying_account`
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
(1, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-15 01:06:22'),
(2, 1, 'logout', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-15 01:07:49'),
(3, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-15 01:07:57'),
(4, 1, 'logout', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-15 01:08:07'),
(5, 1, 'login', 'user', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-15 01:08:22'),
(6, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-15 18:50:20'),
(7, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 10:38:13'),
(8, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 11:11:24'),
(9, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 11:13:06'),
(10, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 11:30:46'),
(11, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 12:01:15'),
(12, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 13:19:15'),
(13, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 13:24:16'),
(14, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 13:26:01'),
(15, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 13:26:47'),
(16, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 13:34:42'),
(17, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 13:38:44'),
(18, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 15:13:28'),
(19, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 15:18:54'),
(20, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 15:28:39'),
(21, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-19 16:51:30'),
(22, 7, 'login', 'user', '7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-20 08:37:16'),
(23, 7, 'login', 'user', '7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-20 08:42:35'),
(24, 7, 'login', 'user', '7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-20 14:40:17'),
(25, 7, 'login', 'user', '7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-20 14:41:19'),
(26, 7, 'login', 'user', '7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-20 20:09:57'),
(27, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:13:56'),
(28, 6, 'login', 'user', '6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:14:26'),
(29, 8, 'login', 'user', '8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:28:32'),
(30, 8, 'login', 'user', '8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:36:46'),
(31, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:36:56'),
(32, 9, 'login', 'user', '9', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:40:38'),
(33, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 21:56:47'),
(34, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 23:17:23'),
(35, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 23:27:46'),
(36, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-20 23:39:09'),
(37, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 07:11:35'),
(38, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 07:14:12'),
(39, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 08:07:17'),
(40, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 09:26:13'),
(41, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 13:06:25'),
(42, 10, 'login', 'user', '10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 13:58:10'),
(43, 11, 'login', 'user', '11', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 14:03:58'),
(44, 12, 'login', 'user', '12', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 15:02:43'),
(45, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 15:42:48'),
(46, 13, 'login', 'user', '13', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 15:43:44'),
(47, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 16:05:38'),
(48, 13, 'login', 'user', '13', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 16:07:23'),
(49, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 16:27:30'),
(50, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 16:27:35'),
(51, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 21:49:39'),
(52, 14, 'login', 'user', '14', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 21:55:19'),
(53, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 22:10:59'),
(54, 14, 'login', 'user', '14', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 22:14:42'),
(55, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 22:21:01'),
(56, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-21 22:47:45'),
(57, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 14:57:32'),
(58, 3, 'login', 'user', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:06:45'),
(59, 8, 'login', 'user', '8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:16:00'),
(60, 14, 'login', 'user', '14', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:16:11'),
(61, 15, 'login', 'user', '15', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:39:35'),
(62, 16, 'login', 'user', '16', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:47:46'),
(63, 17, 'login', 'user', '17', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:51:43'),
(64, 18, 'login', 'user', '18', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:52:46'),
(65, 19, 'login', 'user', '19', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 16:53:49'),
(66, 20, 'login', 'user', '20', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 17:26:51'),
(67, 21, 'login', 'user', '21', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-22 17:41:53'),
(68, 8, 'login', 'user', '8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-23 21:34:31'),
(69, 22, 'login', 'user', '22', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-04-23 22:08:00');

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

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `admin_username`, `admin_password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin', '$2y$10$/N.5Ou22.iGuITyFvHQqau25CibNBSyNpPFmHiShCOFp42UwZosai', 'superadmin', '2025-04-14 16:52:22', '2025-04-21 23:34:15'),
(3, 'Default Admin', 'ad', '$2y$10$dksylZvMqKQ7yeHhTAhkZee7Jn1uZnDultuP1XTHagh25XlbUfQPq', 'superadmin', '2025-04-20 15:53:51', NULL);

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
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`id`, `province`, `created_at`) VALUES
(1, 'An Giang', '2025-04-19 12:15:05'),
(2, 'Bà Rịa - Vũng Tàu', '2025-04-19 12:15:05'),
(3, 'Bắc Giang', '2025-04-19 12:15:05'),
(4, 'Bắc Kạn', '2025-04-19 12:15:05'),
(5, 'Bạc Liêu', '2025-04-19 12:15:05'),
(6, 'Bắc Ninh', '2025-04-19 12:15:05'),
(7, 'Bến Tre', '2025-04-19 12:15:05'),
(8, 'Bình Định', '2025-04-19 12:15:05'),
(9, 'Bình Dương', '2025-04-19 12:15:05'),
(10, 'Bình Phước', '2025-04-19 12:15:05'),
(11, 'Bình Thuận', '2025-04-19 12:15:05'),
(12, 'Cà Mau', '2025-04-19 12:15:05'),
(13, 'Cần Thơ', '2025-04-19 12:15:05'),
(14, 'Cao Bằng', '2025-04-19 12:15:05'),
(15, 'Đà Nẵng', '2025-04-19 12:15:05'),
(16, 'Đắk Lắk', '2025-04-19 12:15:05'),
(17, 'Đắk Nông', '2025-04-19 12:15:05'),
(18, 'Điện Biên', '2025-04-19 12:15:05'),
(19, 'Đồng Nai', '2025-04-19 12:15:05'),
(20, 'Đồng Tháp', '2025-04-19 12:15:05'),
(21, 'Gia Lai', '2025-04-19 12:15:05'),
(22, 'Hà Giang', '2025-04-19 12:15:05'),
(23, 'Hà Nam', '2025-04-19 12:15:05'),
(24, 'Hà Nội', '2025-04-19 12:15:05'),
(25, 'Hà Tĩnh', '2025-04-19 12:15:05'),
(26, 'Hải Dương', '2025-04-19 12:15:05'),
(27, 'Hải Phòng', '2025-04-19 12:15:05'),
(28, 'Hậu Giang', '2025-04-19 12:15:05'),
(29, 'Hòa Bình', '2025-04-19 12:15:05'),
(30, 'Hưng Yên', '2025-04-19 12:15:05'),
(31, 'Khánh Hòa', '2025-04-19 12:15:05'),
(32, 'Kiên Giang', '2025-04-19 12:15:05'),
(33, 'Kon Tum', '2025-04-19 12:15:05'),
(34, 'Lai Châu', '2025-04-19 12:15:05'),
(35, 'Lâm Đồng', '2025-04-19 12:15:05'),
(36, 'Lạng Sơn', '2025-04-19 12:15:05'),
(37, 'Lào Cai', '2025-04-19 12:15:05'),
(38, 'Long An', '2025-04-19 12:15:05'),
(39, 'Nam Định', '2025-04-19 12:15:05'),
(40, 'Nghệ An', '2025-04-19 12:15:05'),
(41, 'Ninh Bình', '2025-04-19 12:15:05'),
(42, 'Ninh Thuận', '2025-04-19 12:15:05'),
(43, 'Phú Thọ', '2025-04-19 12:15:05'),
(44, 'Phú Yên', '2025-04-19 12:15:05'),
(45, 'Quảng Bình', '2025-04-19 12:15:05'),
(46, 'Quảng Nam', '2025-04-19 12:15:05'),
(47, 'Quảng Ngãi', '2025-04-19 12:15:05'),
(48, 'Quảng Ninh', '2025-04-19 12:15:05'),
(49, 'Quảng Trị', '2025-04-19 12:15:05'),
(50, 'Sóc Trăng', '2025-04-19 12:15:05'),
(51, 'Sơn La', '2025-04-19 12:15:05'),
(52, 'Tây Ninh', '2025-04-19 12:15:05'),
(53, 'Thái Bình', '2025-04-19 12:15:05'),
(54, 'Thái Nguyên', '2025-04-19 12:15:05'),
(55, 'Thanh Hóa', '2025-04-19 12:15:05'),
(56, 'Thừa Thiên Huế', '2025-04-19 12:15:05'),
(57, 'Tiền Giang', '2025-04-19 12:15:05'),
(58, 'TP Hồ Chí Minh', '2025-04-19 12:15:05'),
(59, 'Trà Vinh', '2025-04-19 12:15:05'),
(60, 'Tuyên Quang', '2025-04-19 12:15:05'),
(61, 'Vĩnh Long', '2025-04-19 12:15:05'),
(62, 'Vĩnh Phúc', '2025-04-19 12:15:05'),
(63, 'Yên Bái', '2025-04-19 12:15:05');

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
('1', 24, '203.171.25.138', 1509, 'HaNoi'),
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
('64', 63, '203.171.25.138', 1509, 'YBI_XuanAI'),
('65', 51, '203.171.25.138', 1509, 'SLA_MocChau'),
('66', 27, '203.171.25.138', 1509, 'VpHaiPhong'),
('67', 27, '203.171.25.138', 1509, 'HPG_LeChan'),
('68', 16, '203.171.25.138', 1509, 'P5_HaNoi');

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

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id`, `registration_id`, `payment_image`, `export_invoice`, `invoice_info`, `confirmed`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(1, 19, 'reg_19_1745056959.png', 0, NULL, 0, NULL, '2025-04-19 17:02:39', '2025-04-19 17:02:39'),
(2, 20, 'reg_20_1745057072.png', 0, NULL, 0, NULL, '2025-04-19 17:04:32', '2025-04-19 17:04:32'),
(3, 25, 'reg_25_1745077732.png', 0, NULL, 0, NULL, '2025-04-20 08:43:14', '2025-04-20 20:46:24'),
(4, 76, 'reg_76_1745167458.png', 0, NULL, 0, NULL, '2025-04-20 23:44:18', '2025-04-20 23:44:18'),
(5, 79, 'reg_79_1745215617.png', 0, NULL, 1, '2025-04-21 23:53:00', '2025-04-21 13:06:57', '2025-04-21 23:53:00'),
(6, 80, 'reg_80_1745218094.png', 0, NULL, 1, '2025-04-21 23:52:28', '2025-04-21 13:48:14', '2025-04-21 23:52:28'),
(7, 85, 'reg_85_1745226786.png', 0, NULL, 0, NULL, '2025-04-21 16:13:06', '2025-04-21 16:13:06'),
(8, 86, 'reg_86_1745227495.png', 0, NULL, 0, NULL, '2025-04-21 16:24:55', '2025-04-21 16:24:55'),
(9, 88, 'reg_88_1745248249.jpg', 0, NULL, 0, NULL, '2025-04-21 22:10:49', '2025-04-21 23:58:02'),
(10, 89, 'reg_89_1745308721.png', 0, NULL, 0, NULL, '2025-04-22 14:58:41', '2025-04-22 14:58:41'),
(11, 97, 'reg_97_1745318626.png', 0, NULL, 0, NULL, '2025-04-22 17:43:46', '2025-04-22 17:43:46'),
(12, 98, 'reg_98_1745318742.png', 0, NULL, 0, NULL, '2025-04-22 17:45:42', '2025-04-22 17:45:42'),
(13, 99, 'reg_99_1745419451.png', 0, NULL, 0, NULL, '2025-04-23 21:44:11', '2025-04-23 21:44:11');

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
(19, 6, 1, 19, NULL, 1, '2025-04-19 10:02:13', '2025-05-19 10:02:13', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-19 17:02:13', NULL, NULL, NULL),
(20, 6, 1, 17, NULL, 1, '2025-04-19 10:04:25', '2025-05-19 10:04:25', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-19 17:04:25', NULL, NULL, NULL),
(22, 6, 1, 14, NULL, 1, '2025-04-19 11:41:36', '2025-05-19 11:41:36', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-19 18:41:36', NULL, NULL, NULL),
(23, 6, 1, 14, NULL, 1, '2025-04-19 11:42:48', '2025-05-19 11:42:48', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-19 18:42:48', NULL, NULL, NULL),
(24, 6, 1, 17, NULL, 1, '2025-04-19 11:49:43', '2025-05-19 11:49:43', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-19 18:49:43', NULL, NULL, NULL),
(25, 7, 1, 6, NULL, 1, '2025-04-20 01:43:00', '2025-05-20 01:43:00', 100000.00, 0, 0.00, 100000.00, 'rejected', '2025-04-20 08:43:00', '2025-04-20 20:46:29', NULL, 'Chê'),
(26, 7, 2, 10, NULL, 1, '2025-04-20 08:00:28', '2025-07-20 08:00:28', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-04-20 15:00:28', '2025-04-20 20:53:51', NULL, NULL),
(30, 6, 1, 20, NULL, 1, '2025-04-20 14:14:03', '2025-05-20 14:14:03', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-20 21:14:03', '2025-04-20 21:14:03', NULL, NULL),
(31, 6, 1, 14, NULL, 1, '2025-04-20 14:14:31', '2025-05-20 14:14:31', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-20 21:14:31', '2025-04-20 21:14:31', NULL, NULL),
(32, 8, 1, 17, NULL, 1, '2025-04-20 14:33:16', '2025-05-20 14:33:16', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-20 21:33:16', '2025-04-20 21:33:16', NULL, NULL),
(33, 3, 7, 15, NULL, 1, '2025-04-20 14:38:22', '2025-04-27 14:38:22', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:38:22', '2025-04-20 21:38:22', NULL, NULL),
(34, 9, 7, 20, NULL, 1, '2025-04-20 14:41:20', '2025-04-27 14:41:20', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:41:20', '2025-04-20 21:41:20', NULL, NULL),
(35, 9, 7, 20, NULL, 1, '2025-04-20 14:42:34', '2025-04-27 14:42:34', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:42:34', '2025-04-20 21:42:34', NULL, NULL),
(36, 9, 7, 18, NULL, 1, '2025-04-20 14:43:38', '2025-04-27 14:43:38', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:43:38', '2025-04-20 21:43:38', NULL, NULL),
(37, 9, 7, 18, NULL, 12, '2025-04-20 14:43:53', '2025-04-27 14:43:53', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:43:53', '2025-04-20 21:43:53', NULL, NULL),
(38, 9, 7, 18, NULL, 12, '2025-04-20 14:43:58', '2025-04-27 14:43:58', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:43:58', '2025-04-20 21:43:58', NULL, NULL),
(39, 9, 7, 19, NULL, 1, '2025-04-20 14:47:06', '2025-04-27 14:47:06', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:47:06', '2025-04-20 21:47:06', NULL, NULL),
(40, 9, 7, 15, NULL, 1, '2025-04-20 14:47:49', '2025-04-27 14:47:49', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 21:47:49', '2025-04-20 21:47:49', NULL, NULL),
(41, 3, 7, 19, NULL, 1, '2025-04-20 15:00:38', '2025-04-27 15:00:38', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:00:38', '2025-04-20 22:00:38', NULL, NULL),
(42, 3, 7, 14, NULL, 1, '2025-04-20 15:12:28', '2025-04-27 15:12:28', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:12:28', '2025-04-20 22:12:28', NULL, NULL),
(43, 3, 7, 15, NULL, 1, '2025-04-20 15:12:50', '2025-04-27 15:12:50', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:12:50', '2025-04-20 22:12:50', NULL, NULL),
(44, 3, 7, 15, NULL, 1, '2025-04-20 15:15:54', '2025-04-27 15:15:54', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:15:54', '2025-04-20 22:15:54', NULL, NULL),
(45, 3, 7, 15, NULL, 1, '2025-04-20 15:16:11', '2025-04-27 15:16:11', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:16:11', '2025-04-20 22:16:11', NULL, NULL),
(46, 3, 7, 15, NULL, 1, '2025-04-20 15:16:57', '2025-04-27 15:16:57', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:16:57', '2025-04-20 22:16:57', NULL, NULL),
(47, 3, 7, 16, NULL, 1, '2025-04-20 15:18:12', '2025-04-19 23:58:12', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:18:12', '2025-04-20 23:47:14', NULL, NULL),
(48, 3, 7, 15, NULL, 1, '2025-04-20 15:19:14', '2025-04-27 15:19:14', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:19:14', '2025-04-20 22:19:14', NULL, NULL),
(49, 3, 7, 16, NULL, 1, '2025-04-20 15:20:57', '2025-04-27 15:20:57', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:20:57', '2025-04-20 22:20:57', NULL, NULL),
(50, 3, 7, 16, NULL, 1, '2025-04-20 15:22:08', '2025-04-27 15:22:08', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:22:08', '2025-04-20 22:22:08', NULL, NULL),
(51, 3, 7, 14, NULL, 1, '2025-04-20 15:22:16', '2025-04-27 15:22:16', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:22:16', '2025-04-20 22:22:16', NULL, NULL),
(52, 3, 1, 20, NULL, 2, '2025-04-20 15:22:39', '2025-05-20 15:22:39', 100000.00, 0, 0.00, 200000.00, 'pending', '2025-04-20 22:22:39', '2025-04-20 22:22:39', NULL, NULL),
(53, 3, 1, 20, NULL, 2, '2025-04-20 15:22:45', '2025-05-20 15:22:45', 100000.00, 0, 0.00, 200000.00, 'pending', '2025-04-20 22:22:45', '2025-04-20 22:22:45', NULL, NULL),
(54, 3, 7, 15, NULL, 1, '2025-04-20 15:23:17', '2025-04-27 15:23:17', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:23:17', '2025-04-20 22:23:17', NULL, NULL),
(55, 3, 7, 15, NULL, 1, '2025-04-20 15:25:08', '2025-04-27 15:25:08', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:25:08', '2025-04-20 22:25:08', NULL, NULL),
(56, 3, 7, 11, NULL, 1, '2025-04-20 15:25:55', '2025-04-27 15:25:55', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:25:55', '2025-04-20 22:25:55', NULL, NULL),
(57, 3, 7, 11, NULL, 1, '2025-04-20 15:26:54', '2025-04-27 15:26:54', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:26:54', '2025-04-20 22:26:54', NULL, NULL),
(58, 3, 1, 17, NULL, 5, '2025-04-20 15:27:08', '2025-05-20 15:27:08', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-04-20 22:27:08', '2025-04-20 22:27:08', NULL, NULL),
(59, 3, 7, 15, NULL, 1, '2025-04-20 15:27:25', '2025-04-27 15:27:25', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:27:25', '2025-04-20 22:27:25', NULL, NULL),
(60, 3, 1, 22, NULL, 1000, '2025-04-20 15:33:41', '2025-05-20 15:33:41', 100000.00, 0, 0.00, 100000000.00, 'pending', '2025-04-20 22:33:41', '2025-04-20 22:33:41', NULL, NULL),
(61, 3, 7, 17, NULL, 1, '2025-04-20 15:33:48', '2025-04-27 15:33:48', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:33:48', '2025-04-20 22:33:48', NULL, NULL),
(62, 3, 7, 14, NULL, 1, '2025-04-20 15:39:52', '2025-04-27 15:39:52', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:39:52', '2025-04-20 22:39:52', NULL, NULL),
(63, 3, 7, 8, NULL, 1, '2025-04-20 15:41:24', '2025-04-27 15:41:24', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:41:24', '2025-04-20 22:41:24', NULL, NULL),
(64, 3, 7, 16, NULL, 1, '2025-04-20 15:42:25', '2025-04-27 15:42:25', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 22:42:25', '2025-04-20 22:42:25', NULL, NULL),
(65, 3, 7, 15, NULL, 1, '2025-04-20 16:01:11', '2025-04-27 16:01:11', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:01:11', '2025-04-20 23:01:11', NULL, NULL),
(66, 3, 7, 14, NULL, 1, '2025-04-20 16:06:04', '2025-04-27 16:06:04', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:06:04', '2025-04-20 23:06:04', NULL, NULL),
(67, 3, 7, 17, NULL, 1, '2025-04-20 16:06:23', '2025-04-27 16:06:23', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:06:23', '2025-04-20 23:06:23', NULL, NULL),
(68, 3, 7, 10, NULL, 1, '2025-04-20 16:17:28', '2025-04-27 16:17:28', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:17:28', '2025-04-20 23:17:28', NULL, NULL),
(69, 3, 7, 14, NULL, 1, '2025-04-20 16:25:34', '2025-04-27 16:25:34', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:25:34', '2025-04-20 23:25:34', NULL, NULL),
(70, 3, 7, 15, NULL, 1, '2025-04-20 16:27:52', '2025-04-27 16:27:52', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:27:52', '2025-04-20 23:27:52', NULL, NULL),
(71, 3, 7, 14, NULL, 1, '2025-04-20 16:29:36', '2025-04-27 16:29:36', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-20 23:29:36', '2025-04-20 23:29:36', NULL, NULL),
(72, 3, 7, 11, NULL, 1, '2025-04-20 16:31:07', '2025-04-27 16:31:07', 0.00, 0, 0.00, 0.00, 'active', '2025-04-20 23:31:07', '2025-04-20 23:31:08', NULL, NULL),
(73, 3, 7, 14, NULL, 1, '2025-04-20 16:33:11', '2025-04-27 16:33:11', 0.00, 0, 0.00, 0.00, 'active', '2025-04-20 23:33:11', '2025-04-20 23:33:12', NULL, NULL),
(74, 3, 7, 5, NULL, 1, '2025-04-20 16:33:36', '2025-04-27 16:33:36', 0.00, 0, 0.00, 0.00, 'active', '2025-04-20 23:33:36', '2025-04-20 23:33:37', NULL, NULL),
(75, 3, 7, 59, NULL, 1, '2025-04-20 16:39:17', '2025-04-20 16:39:17', 0.00, 0, 0.00, 0.00, 'active', '2025-04-20 23:39:18', '2025-04-20 23:48:14', NULL, NULL),
(76, 3, 2, 13, NULL, 5, '2025-04-20 16:44:10', '2025-07-20 16:44:10', 270000.00, 0, 0.00, 1350000.00, 'pending', '2025-04-20 23:44:10', '2025-04-20 23:44:10', NULL, NULL),
(77, 3, 1, 8, NULL, 1, '2025-04-21 00:15:09', '2025-05-21 00:15:09', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-21 07:15:09', '2025-04-21 07:15:09', NULL, NULL),
(78, 3, 1, 15, NULL, 2, '2025-04-21 02:55:42', '2025-05-21 02:55:42', 100000.00, 0, 0.00, 200000.00, 'pending', '2025-04-21 09:55:42', '2025-04-21 09:55:42', NULL, NULL),
(79, 3, 4, 16, NULL, 2, '2025-04-21 06:06:45', '2026-04-21 06:06:45', 900000.00, 0, 0.00, 1800000.00, 'active', '2025-04-21 13:06:45', '2025-04-21 23:53:00', NULL, NULL),
(80, 3, 1, 18, NULL, 1, '2025-04-21 06:47:40', '2025-05-21 06:47:40', 100000.00, 0, 0.00, 100000.00, 'active', '2025-04-21 13:47:40', '2025-04-21 23:52:28', NULL, NULL),
(81, 10, 7, 15, NULL, 1, '2025-04-21 06:59:34', '2025-04-28 06:59:34', 0.00, 0, 0.00, 0.00, 'active', '2025-04-21 13:59:34', '2025-04-21 13:59:35', NULL, NULL),
(82, 11, 7, 16, NULL, 1, '2025-04-21 07:04:06', '2025-04-28 07:04:06', 0.00, 0, 0.00, 0.00, 'active', '2025-04-21 14:04:06', '2025-04-21 14:04:07', NULL, NULL),
(83, 12, 7, 63, NULL, 1, '2025-04-21 08:02:56', '2025-04-28 08:02:56', 0.00, 0, 0.00, 0.00, 'active', '2025-04-21 15:02:56', '2025-04-21 15:02:59', NULL, NULL),
(84, 13, 7, 63, NULL, 1, '2025-04-21 08:43:56', '2025-04-28 08:43:56', 0.00, 0, 0.00, 0.00, 'active', '2025-04-21 15:43:56', '2025-04-21 15:43:57', NULL, NULL),
(85, 13, 1, 17, NULL, 1, '2025-04-21 09:13:00', '2025-05-21 09:13:00', 100000.00, 0, 0.00, 100000.00, 'active', '2025-04-21 16:13:00', '2025-04-21 16:14:34', NULL, NULL),
(86, 13, 1, 63, NULL, 1, '2025-04-21 09:24:49', '2025-05-21 09:24:49', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-04-21 16:24:49', '2025-04-21 16:24:49', NULL, NULL),
(87, 14, 7, 63, NULL, 1, '2025-04-21 14:57:44', '2025-04-23 14:57:44', 0.00, 0, 0.00, 0.00, 'active', '2025-04-21 21:57:44', '2025-04-22 16:37:51', NULL, NULL),
(88, 14, 1, 17, NULL, 2, '2025-04-21 15:10:43', '2025-05-21 15:10:43', 100000.00, 0, 0.00, 200000.00, 'pending', '2025-04-21 22:10:43', '2025-04-21 23:58:02', NULL, NULL),
(89, 3, 1, 63, NULL, 2, '2025-04-22 07:58:31', '2025-05-22 07:58:31', 100000.00, 0, 0.00, 200000.00, 'pending', '2025-04-22 14:58:31', '2025-04-22 14:58:31', NULL, NULL),
(90, 15, 7, 63, NULL, 1, '2025-04-22 09:39:44', '2025-04-29 09:39:44', 0.00, 0, 0.00, 0.00, 'active', '2025-04-22 16:39:44', '2025-04-22 16:39:46', NULL, NULL),
(91, 16, 7, 63, NULL, 1, '2025-04-22 09:47:55', '2025-04-29 09:47:55', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-22 16:47:55', '2025-04-22 16:47:55', NULL, NULL),
(92, 17, 7, 15, NULL, 1, '2025-04-22 09:51:51', '2025-04-29 09:51:51', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-22 16:51:51', '2025-04-22 16:51:51', NULL, NULL),
(93, 18, 7, 63, NULL, 1, '2025-04-22 09:52:55', '2025-04-29 09:52:55', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-22 16:52:55', '2025-04-22 16:52:55', NULL, NULL),
(94, 19, 7, 63, NULL, 1, '2025-04-22 09:53:57', '2025-04-29 09:53:57', 0.00, 0, 0.00, 0.00, 'active', '2025-04-22 16:53:57', '2025-04-22 17:17:50', NULL, NULL),
(95, 20, 7, 63, NULL, 1, '2025-04-22 10:26:59', '2025-04-29 10:26:59', 0.00, 0, 0.00, 0.00, 'pending', '2025-04-22 17:26:59', '2025-04-22 17:26:59', NULL, NULL),
(96, 21, 7, 63, NULL, 1, '2025-04-22 10:42:01', '2025-04-29 10:42:01', 0.00, 0, 0.00, 0.00, 'active', '2025-04-22 17:42:01', '2025-04-22 17:42:02', NULL, NULL),
(97, 21, 1, 63, NULL, 1000, '2025-04-22 10:43:31', '2025-05-22 10:43:31', 100000.00, 0, 0.00, 100000000.00, 'pending', '2025-04-22 17:43:31', '2025-04-22 17:43:31', NULL, NULL),
(98, 21, 1, 18, NULL, 1000, '2025-04-22 10:45:01', '2025-05-22 10:45:01', 100000.00, 0, 0.00, 100000000.00, 'pending', '2025-04-22 17:45:01', '2025-04-22 17:45:01', NULL, NULL),
(99, 8, 1, 63, NULL, 5, '2025-04-23 14:44:04', '2025-05-23 14:44:04', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-04-23 21:44:04', '2025-04-23 21:44:04', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `station`
--

CREATE TABLE `station` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mountpoint_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `long` decimal(11,8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('RTK_87_1745247466', 87, 'new7gmailcom', 'ca20ff9c8fe0be25', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-21 21:57:46', NULL, NULL),
('RTK_90_1745314786', 90, 'Duy', '503e254f13abb14c', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-22 16:39:46', NULL, NULL),
('RTK_94_1745317070', 94, 'aaccccddccgmailcom', 'ba7060d3a5ed4cd5', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-22 17:17:50', NULL, NULL),
('RTK_96_1745318522', 96, 'new9999gmailcom', 'd3d57f4dceb22e03', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-04-22 17:42:02', NULL, NULL);

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
(1, 25, 7, 'purchase', 100000.00, 'failed', NULL, '2025-04-20 08:43:00', '2025-04-20 20:46:29'),
(2, 26, 7, 'purchase', 270000.00, 'pending', NULL, '2025-04-20 15:00:28', '2025-04-20 20:53:51'),
(3, 30, 6, 'purchase', 100000.00, 'pending', NULL, '2025-04-20 21:14:03', '2025-04-20 21:14:03'),
(4, 31, 6, 'purchase', 100000.00, 'pending', NULL, '2025-04-20 21:14:31', '2025-04-20 21:14:31'),
(5, 32, 4, 'purchase', 100000.00, 'pending', NULL, '2025-04-20 21:33:16', '2025-04-20 21:36:33'),
(7, 34, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:41:20', '2025-04-20 21:41:20'),
(8, 35, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:42:34', '2025-04-20 21:42:34'),
(9, 36, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:43:38', '2025-04-20 21:43:38'),
(10, 37, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:43:53', '2025-04-20 21:43:53'),
(11, 38, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:43:58', '2025-04-20 21:43:58'),
(12, 39, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:47:06', '2025-04-20 21:47:06'),
(13, 40, 9, 'purchase', 0.00, 'pending', NULL, '2025-04-20 21:47:49', '2025-04-20 21:47:49'),
(14, 41, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:00:38', '2025-04-20 22:00:38'),
(15, 42, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:12:28', '2025-04-20 22:12:28'),
(16, 43, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:12:50', '2025-04-20 22:12:50'),
(17, 44, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:15:54', '2025-04-20 22:15:54'),
(18, 45, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:16:11', '2025-04-20 22:16:11'),
(19, 46, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:16:57', '2025-04-20 22:16:57'),
(20, 47, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:18:12', '2025-04-20 22:18:12'),
(21, 48, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:19:14', '2025-04-20 22:19:14'),
(22, 49, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:20:57', '2025-04-20 22:20:57'),
(23, 50, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:22:08', '2025-04-20 22:22:08'),
(24, 51, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:22:16', '2025-04-20 22:22:16'),
(25, 52, 3, 'purchase', 200000.00, 'pending', NULL, '2025-04-20 22:22:39', '2025-04-20 22:22:39'),
(26, 53, 3, 'purchase', 200000.00, 'failed', NULL, '2025-04-20 22:22:45', '2025-04-21 07:43:34'),
(27, 54, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:23:17', '2025-04-20 22:23:17'),
(28, 55, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:25:08', '2025-04-20 22:25:08'),
(29, 56, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:25:55', '2025-04-20 22:25:55'),
(30, 57, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:26:54', '2025-04-20 22:26:54'),
(31, 58, 3, 'purchase', 500000.00, 'pending', NULL, '2025-04-20 22:27:08', '2025-04-20 22:27:08'),
(32, 59, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:27:25', '2025-04-20 22:27:25'),
(33, 60, 3, 'purchase', 100000000.00, 'pending', NULL, '2025-04-20 22:33:41', '2025-04-20 22:33:41'),
(34, 61, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:33:48', '2025-04-20 22:33:48'),
(35, 62, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:39:52', '2025-04-20 22:39:52'),
(36, 63, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:41:24', '2025-04-20 22:41:24'),
(37, 64, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 22:42:25', '2025-04-20 22:42:25'),
(38, 65, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:01:11', '2025-04-20 23:01:11'),
(39, 66, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:06:04', '2025-04-20 23:06:04'),
(40, 67, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:06:23', '2025-04-20 23:06:23'),
(41, 68, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:17:28', '2025-04-20 23:17:28'),
(42, 69, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:25:34', '2025-04-20 23:25:34'),
(43, 70, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:27:52', '2025-04-20 23:27:52'),
(44, 71, 3, 'purchase', 0.00, 'pending', NULL, '2025-04-20 23:29:36', '2025-04-20 23:29:36'),
(45, 72, 3, 'purchase', 0.00, 'completed', NULL, '2025-04-20 23:31:07', '2025-04-20 23:31:08'),
(46, 73, 3, 'purchase', 0.00, 'completed', NULL, '2025-04-20 23:33:11', '2025-04-20 23:33:12'),
(47, 74, 3, 'purchase', 0.00, 'completed', NULL, '2025-04-20 23:33:36', '2025-04-20 23:33:37'),
(48, 75, 3, 'purchase', 0.00, 'completed', NULL, '2025-04-20 23:39:18', '2025-04-20 23:39:19'),
(49, 76, 3, 'purchase', 1350000.00, 'pending', NULL, '2025-04-20 23:44:10', '2025-04-20 23:44:18'),
(50, 77, 3, 'purchase', 100000.00, 'pending', NULL, '2025-04-21 07:15:09', '2025-04-21 07:15:09'),
(51, 78, 3, 'purchase', 200000.00, 'pending', NULL, '2025-04-21 09:55:42', '2025-04-21 09:55:42'),
(52, 79, 3, 'purchase', 1800000.00, 'completed', NULL, '2025-04-21 13:06:45', '2025-04-21 23:53:00'),
(53, 80, 3, 'purchase', 100000.00, 'completed', NULL, '2025-04-21 13:47:40', '2025-04-21 23:52:28'),
(54, 81, 10, 'purchase', 0.00, 'completed', NULL, '2025-04-21 13:59:34', '2025-04-21 13:59:35'),
(55, 82, 11, 'purchase', 0.00, 'completed', NULL, '2025-04-21 14:04:06', '2025-04-21 14:04:07'),
(56, 83, 12, 'purchase', 0.00, 'completed', NULL, '2025-04-21 15:02:56', '2025-04-21 15:02:59'),
(57, 84, 13, 'purchase', 0.00, 'completed', NULL, '2025-04-21 15:43:56', '2025-04-21 15:43:57'),
(58, 85, 13, 'purchase', 100000.00, 'completed', NULL, '2025-04-21 16:13:00', '2025-04-21 16:15:08'),
(59, 86, 13, 'purchase', 100000.00, 'pending', NULL, '2025-04-21 16:24:49', '2025-04-21 16:24:55'),
(60, 87, 14, 'purchase', 0.00, 'completed', NULL, '2025-04-21 21:57:44', '2025-04-21 21:57:46'),
(61, 88, 14, 'purchase', 200000.00, 'pending', NULL, '2025-04-21 22:10:43', '2025-04-21 23:58:02'),
(62, 89, 3, 'purchase', 200000.00, 'pending', NULL, '2025-04-22 14:58:31', '2025-04-22 14:58:41'),
(63, 90, 15, 'purchase', 0.00, 'completed', NULL, '2025-04-22 16:39:44', '2025-04-22 16:39:46'),
(64, 91, 16, 'purchase', 0.00, 'pending', NULL, '2025-04-22 16:47:55', '2025-04-22 16:47:55'),
(65, 92, 17, 'purchase', 0.00, 'pending', NULL, '2025-04-22 16:51:51', '2025-04-22 16:51:51'),
(66, 93, 18, 'purchase', 0.00, 'pending', NULL, '2025-04-22 16:52:55', '2025-04-22 16:52:55'),
(67, 94, 19, 'purchase', 0.00, 'completed', NULL, '2025-04-22 16:53:57', '2025-04-22 17:17:50'),
(68, 95, 20, 'purchase', 0.00, 'pending', NULL, '2025-04-22 17:26:59', '2025-04-22 17:26:59'),
(69, 96, 21, 'purchase', 0.00, 'completed', NULL, '2025-04-22 17:42:01', '2025-04-22 17:42:02'),
(70, 97, 21, 'purchase', 100000000.00, 'pending', NULL, '2025-04-22 17:43:31', '2025-04-22 17:43:46'),
(71, 98, 21, 'purchase', 100000000.00, 'pending', NULL, '2025-04-22 17:45:01', '2025-04-22 17:45:42'),
(72, 99, 8, 'purchase', 500000.00, 'pending', NULL, '2025-04-23 21:44:04', '2025-04-23 21:44:11');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_company` tinyint(1) DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_registered` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `phone`, `is_company`, `company_name`, `tax_code`, `tax_registered`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Nguyen33443355', 'aa@gmail.com', '$2y$10$K7GvgUFN2wMfhXLIjR93FuzdYkvRBlHoVJHbgE2d0fA/wgffC7Bxa', '0999999999', 0, NULL, NULL, NULL, '2025-04-15 01:06:13', NULL, NULL),
(2, 'Đỗ Văn Nguyên', 'a55@gmail.com', '$2y$10$RijEyfrLRiE5iOF2787D/uV6JI.Q3dlVheEezN6yJACdu1nRUNImy', '0981190567', 0, '', '', NULL, '2025-04-15 18:21:08', NULL, NULL),
(3, 'Đỗ Văn Nguyên', 'na@gmail.com', '$2y$10$e/PAuV7aqrU0.1TF5Kk/2e3bFJlQ7.wVghx6k/ATcLUkjSBGLOada', '0981190561', 0, NULL, NULL, NULL, '2025-04-15 18:46:31', NULL, NULL),
(4, 'nguyen', 'an@gmail.com', '12345678', '0987654564', NULL, NULL, NULL, NULL, '2025-04-19 00:12:06', NULL, NULL),
(5, 'na@gmail.com', 'ab@gmail.com', '$2y$10$7njlF59/cT0N/F.brYvKKuJBUjnQKc6SQW0UG9SJPolXbld1RbrVa', '0981590564', 0, NULL, NULL, NULL, '2025-04-19 10:37:49', NULL, NULL),
(6, 'test2', 'az@gmail.com', '$2y$10$qaTgYQukWdh9jKnvIrk1keCjsnORwA2S8O/9lXOQjDwaZasM76xga', '0999999443', 0, NULL, NULL, NULL, '2025-04-19 11:12:23', NULL, NULL),
(7, 'B23DCCN511', 'acook6962@gmail.com', '$2y$10$47lF54B/ogJtcP6Fvkhkvub.PmmfQyP3kDfPSX9Yb31osZzlQL4Je', '0900000004', 1, 'as', 'as', NULL, '2025-04-20 08:37:11', '2025-04-20 14:57:57', NULL),
(8, 'new', 'new1@gmail.com', '$2y$10$lh8CnmfO4P8cwi8R54SAW.teyeBkNNf6vJKuUuJFpeYenqP1Yxfdq', '0981190564', 0, NULL, NULL, NULL, '2025-04-20 21:28:28', NULL, NULL),
(9, 'na@gmail.com', 'ak@gmail.com', '$2y$10$zhI8dcEGInHuawag2TvH5.H/xpwADKhJIHIVYTU/.Z24Dw8D0.53.', '0999919191', 0, NULL, NULL, NULL, '2025-04-20 21:40:35', NULL, NULL),
(10, 'na@gmail.com', 'kkkk@gmail.com', '$2y$10$OHy8qtfDebJC0BZpbrWnTuUr2bThheeWMe9tuBKCc.FPRNPhpqKE6', '0999999555', 0, NULL, NULL, NULL, '2025-04-21 13:57:59', NULL, NULL),
(11, 'new2@gmail.com', 'new2@gmail.com', '$2y$10$SFsoupw4s.WqQEztl5KTYudyJbe7Crik7ERebWZJJmJeYQivf3qNq', '0999992999', 0, NULL, NULL, NULL, '2025-04-21 14:03:48', NULL, NULL),
(12, 'new5@gmail.com', 'new5@gmail.com', '$2y$10$5xIBdajry3d42L.PVdxY5.TfQHlzQG1ge..RCqRGAr2jtR3eghhFe', '0984490564', 0, NULL, NULL, NULL, '2025-04-21 15:02:34', NULL, NULL),
(13, 'Zia', 'z@gmail.com', '$2y$10$z0G39SH0Y5ka3guDEIl3tuGkxfll/AlgbT5O0BzvaM7RlxZNmZTTi', '0956565656', 0, NULL, NULL, NULL, '2025-04-21 15:43:35', NULL, NULL),
(14, 'new7@gmail.com', 'new7@gmail.com', '$2y$10$/N.5Ou22.iGuITyFvHQqau25CibNBSyNpPFmHiShCOFp42UwZosai', '0581190564', 0, NULL, NULL, NULL, '2025-04-21 21:55:12', NULL, NULL),
(15, 'Duy', 'duy@gmail.com', '$2y$10$gIybm9.0Mk5R2YVOm1XSZOIQ3481J1utDn8605ph.SBx7aIHFyCWO', '0981140564', 0, NULL, NULL, NULL, '2025-04-22 16:39:31', NULL, NULL),
(16, 'c@gmail.com', 'c@gmail.com', '$2y$10$K9lOlhN.b7chhPEJjzcPyef1qF3D/ORHfBOPLvG0vWVG6FHSNLn8W', '0981190544', 0, NULL, NULL, NULL, '2025-04-22 16:47:42', NULL, NULL),
(17, 'aaccc@gmail.com', 'aaccc@gmail.com', '$2y$10$bhi/.fVnaMDlbZUh75dYNuOnvsXjVJSz66YBzXv0BnhBx5sVFufDW', '0981190533', 0, NULL, NULL, NULL, '2025-04-22 16:51:39', NULL, NULL),
(18, 'aacccccc@gmail.com', 'aacccccc@gmail.com', '$2y$10$eUpQDzSv3JnLeO4CxSCkneIUdBUCWjCTOafuCtFuDVLkT3RBsVm0O', '0481190564', 0, NULL, NULL, NULL, '2025-04-22 16:52:43', NULL, NULL),
(19, 'aaccccddcc@gmail.com', 'aaccccddcc@gmail.com', '$2y$10$6CUWKv7v7ftcDxjl6RLaieVsxLHXFvqbrdezLj4GkgysQ5A1rFc.q', '0981130564', 0, NULL, NULL, NULL, '2025-04-22 16:53:45', NULL, NULL),
(20, 'aarrr@gmail.com', 'aarrr@gmail.com', '$2y$10$suy6OSO7P1vr4Xt9FzwDk.xhTLv6Qw93dBGeRBLRGG4OXh1Zc/oWy', '0981190223', 0, NULL, NULL, NULL, '2025-04-22 17:26:47', NULL, NULL),
(21, 'new9999@gmail.com', 'new9999@gmail.com', '$2y$10$ted9QO/hTLpuZSS2ph.QA.QcAFv2L74t3HvJKpwg3OMYMsfbpxUQq', '09833340564', 0, NULL, NULL, NULL, '2025-04-22 17:41:49', NULL, NULL),
(22, 'new001@gmail.com', 'new001@gmail.com', '$2y$10$dElgustXy2qvJUjINmoFkeS8dgxTXT19tmLeYwDNe74Pt8tS0rHt.', '0981190444', 0, NULL, NULL, NULL, '2025-04-23 22:07:55', NULL, NULL);

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
(1, 1, 1, 0, 'light', '2025-04-15 01:06:13', NULL),
(2, 5, 1, 0, 'light', '2025-04-19 10:37:49', NULL),
(3, 6, 1, 0, 'light', '2025-04-19 11:12:23', NULL),
(4, 7, 1, 0, 'light', '2025-04-20 08:37:11', NULL),
(5, 8, 1, 0, 'light', '2025-04-20 21:28:28', NULL),
(6, 9, 1, 0, 'light', '2025-04-20 21:40:35', NULL),
(7, 10, 1, 0, 'light', '2025-04-21 13:57:59', NULL),
(8, 11, 1, 0, 'light', '2025-04-21 14:03:48', NULL),
(9, 12, 1, 0, 'light', '2025-04-21 15:02:34', NULL),
(10, 13, 1, 0, 'light', '2025-04-21 15:43:35', NULL),
(11, 14, 1, 0, 'light', '2025-04-21 21:55:12', NULL),
(12, 15, 1, 0, 'light', '2025-04-22 16:39:31', NULL),
(13, 16, 1, 0, 'light', '2025-04-22 16:47:42', NULL),
(14, 17, 1, 0, 'light', '2025-04-22 16:51:39', NULL),
(15, 18, 1, 0, 'light', '2025-04-22 16:52:43', NULL),
(16, 19, 1, 0, 'light', '2025-04-22 16:53:45', NULL),
(17, 20, 1, 0, 'light', '2025-04-22 17:26:47', NULL),
(18, 21, 1, 0, 'light', '2025-04-22 17:41:49', NULL),
(19, 22, 1, 0, 'light', '2025-04-23 22:07:55', NULL);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `transaction_history`
--
ALTER TABLE `transaction_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
