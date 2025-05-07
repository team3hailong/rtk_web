-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 07, 2025 at 03:17 PM
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
-- Database: `new2`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_groups`
--

CREATE TABLE `account_groups` (
  `registration_id` int NOT NULL,
  `survey_account_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_groups`
--

INSERT INTO `account_groups` (`registration_id`, `survey_account_id`) VALUES
(147, 'RTK_112_1746552592'),
(147, 'RTK_61_1746444219'),
(147, 'RTK_64_1746457824'),
(147, 'RTK_68_1746510776'),
(147, 'RTK_69_1746511192'),
(147, 'RTK_70_1746511206'),
(147, 'RTK_78_1746549444'),
(147, 'RTK_93_1746551383');

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
(55, 91, 'login', 'user', '91', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:49:27'),
(56, 91, 'purchase', 'registration', '46', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:49:42'),
(57, 91, 'trial_activation', 'registration', '46', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:49:43'),
(58, 91, 'purchase', 'registration', '47', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:49:58'),
(59, 91, 'purchase', 'registration', '48', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:50:28'),
(60, 91, 'purchase', 'registration', '49', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:51:20'),
(61, 91, 'purchase', 'registration', '50', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:51:47'),
(62, 91, 'purchase', 'registration', '51', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:52:06'),
(63, 91, 'purchase', 'registration', '52', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:53:27'),
(64, 91, 'trial_activation', 'registration', '52', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:53:28'),
(65, 91, 'purchase', 'registration', '53', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:57:37'),
(66, 91, 'purchase', 'registration', '54', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:58:41'),
(67, 91, 'trial_activation', 'registration', '54', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 17:58:42'),
(68, 91, 'purchase', 'registration', '55', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:03:46'),
(69, 91, 'trial_activation', 'registration', '55', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:03:47'),
(70, 91, 'purchase', 'registration', '56', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:03:56'),
(71, 91, 'purchase', 'registration', '57', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:10:28'),
(72, 91, 'purchase', 'registration', '58', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:15:28'),
(73, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:18:15'),
(74, 92, 'purchase', 'registration', '59', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:18:30'),
(75, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:21:39'),
(76, 92, 'purchase', 'registration', '60', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:23:31'),
(77, 92, 'purchase', 'registration', '61', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:23:38'),
(78, 92, 'trial_activation', 'registration', '61', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:23:39'),
(79, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 18:30:01'),
(80, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 21:32:38'),
(81, 92, 'purchase', 'registration', '62', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 21:38:36'),
(82, 92, 'purchase', 'registration', '63', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 22:07:56'),
(83, 92, 'purchase', 'registration', '64', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 22:10:23'),
(84, 92, 'trial_activation', 'registration', '64', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-05 22:10:24'),
(85, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:18:29'),
(86, 92, 'renewal_request', 'registration', '65', NULL, '{\"package_id\":1,\"accounts\":[\"RTK_64_1746457824\"],\"start_time\":\"2025-05-06 12:48:42\",\"end_time\":\"2025-06-05 12:48:42\",\"total_price\":100000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:48:42'),
(87, 92, 'renewal_request', 'registration', '66', NULL, '{\"package_id\":4,\"accounts\":[\"RTK_64_1746457824\"],\"start_time\":\"2025-05-06 12:48:58\",\"end_time\":\"2026-05-06 12:48:58\",\"total_price\":900000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:48:58'),
(88, 92, 'renewal_request', 'registration', '67', NULL, '{\"package_id\":2,\"accounts\":[\"RTK_64_1746457824\"],\"start_time\":\"2025-05-06 12:49:08\",\"end_time\":\"2025-08-04 12:49:08\",\"total_price\":270000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:49:08'),
(89, 92, 'purchase', 'registration', '68', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:52:55'),
(90, 92, 'trial_activation', 'registration', '68', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:52:56'),
(91, 92, 'purchase', 'registration', '69', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:59:51'),
(92, 92, 'trial_activation', 'registration', '69', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 12:59:52'),
(93, 92, 'purchase', 'registration', '70', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 13:00:05'),
(94, 92, 'trial_activation', 'registration', '70', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 13:00:06'),
(95, 92, 'renewal_request', 'registration', '71', NULL, '{\"package_id\":2,\"accounts\":[\"RTK_69_1746511192\",\"RTK_68_1746510776\"],\"start_time\":\"2025-05-06 13:00:26\",\"end_time\":\"2025-08-04 13:00:26\",\"total_price\":270000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 13:00:26'),
(96, 92, 'purchase', 'registration', '72', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 13:04:38'),
(97, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 21:31:08'),
(98, 92, 'purchase', 'registration', '73', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 22:18:48'),
(99, 92, 'purchase', 'registration', '74', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 23:36:08'),
(100, 92, 'purchase', 'registration', '75', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 23:36:18'),
(101, 92, 'purchase', 'registration', '77', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 23:36:47'),
(102, 92, 'purchase', 'registration', '78', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 23:37:23'),
(103, 92, 'trial_activation', 'registration', '78', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 23:37:24'),
(104, 92, 'purchase', 'registration', '86', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-06 23:57:03'),
(105, 92, 'purchase', 'registration', '93', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:09:42'),
(106, 92, 'trial_activation', 'registration', '93', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:09:43'),
(107, 92, 'purchase', 'registration', '96', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:13:19'),
(108, 92, 'purchase', 'registration', '98', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:13:49'),
(109, 92, 'purchase', 'registration', '102', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:20:17'),
(110, 92, 'renewal_request', 'registration', '106', NULL, '{\"registration_ids\":[\"106\"],\"selected_accounts\":[\"RTK_93_1746551383\"],\"total_price\":270000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:22:19'),
(111, 92, 'renewal_completed', 'registration', '106', NULL, '{\"accounts\":[\"RTK_93_1746551383\"],\"new_end_time\":\"2025-08-14 00:09:42\"}', '::1', NULL, '2025-05-07 00:22:25'),
(112, 92, 'renewal_request', 'registration', '109', NULL, '{\"registration_ids\":[\"107\",\"108\",\"109\"],\"selected_accounts\":[\"RTK_70_1746511206\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":810000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:22:56'),
(113, 92, 'purchase', 'registration', '110', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:28:33'),
(114, 92, 'purchase', 'registration', '111', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:28:56'),
(115, 92, 'purchase', 'registration', '112', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:29:51'),
(116, 92, 'trial_activation', 'registration', '112', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:29:52'),
(117, 92, 'renewal_request', 'registration', '116', NULL, '{\"registration_ids\":[\"113\",\"114\",\"115\",\"116\"],\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_70_1746511206\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":1140000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 00:30:11'),
(118, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:44:27'),
(119, 92, 'renewal_request', 'registration', '118', NULL, '{\"registration_ids\":[\"117\",\"118\"],\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_93_1746551383\"],\"total_price\":540000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:44:37'),
(120, 92, 'renewal_request', 'registration', '125', NULL, '{\"registration_ids\":[\"119\",\"120\",\"121\",\"122\",\"123\",\"124\",\"125\"],\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_64_1746457824\",\"RTK_68_1746510776\",\"RTK_69_1746511192\",\"RTK_70_1746511206\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":5100000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:45:31'),
(121, 92, 'renewal_request', 'registration', '126', NULL, '{\"registration_ids\":[\"126\"],\"selected_accounts\":[\"RTK_112_1746552592\"],\"total_price\":100000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:47:49'),
(122, 92, 'renewal_request', 'registration', '129', NULL, '{\"registration_ids\":[\"127\",\"128\",\"129\"],\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":1270000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:48:27'),
(123, 92, 'purchase', 'registration', '130', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:55:23'),
(124, 92, 'purchase', 'registration', '131', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:55:38'),
(125, 92, 'purchase', 'registration', '132', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:56:10'),
(126, 92, 'renewal_request', 'registration', '133', NULL, '{\"registration_ids\":[\"133\"],\"selected_accounts\":[\"RTK_112_1746552592\"],\"total_price\":270000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 06:56:26'),
(127, 92, 'renewal_request', 'registration', '134', NULL, '{\"accounts\":{\"RTK_112_1746552592\":{\"account_id\":\"RTK_112_1746552592\",\"username\":\"TRIAL_YBI007\",\"num_account\":1,\"package_name\":\"G\\u00f3i 1 Th\\u00e1ng\",\"province\":\"Y\\u00ean B\\u00e1i\",\"start_time\":\"2025-05-14 00:29:51\",\"end_time\":\"2025-06-14 00:29:51\",\"location_id\":63}},\"package\":{\"id\":1,\"name\":\"G\\u00f3i 1 Th\\u00e1ng\",\"price\":\"100000.00\",\"duration_text\":\"\\/ 1 th\\u00e1ng\"},\"total_accounts\":1,\"total_price\":100000,\"timestamp\":1746576310}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 07:05:10'),
(128, 92, 'purchase', 'registration', '135', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 07:09:02'),
(129, 92, 'renewal_request', 'registration', '136', NULL, '{\"accounts\":{\"RTK_112_1746552592\":{\"account_id\":\"RTK_112_1746552592\",\"username\":\"TRIAL_YBI007\",\"num_account\":1,\"package_name\":\"G\\u00f3i 1 N\\u0103m\",\"province\":\"Y\\u00ean B\\u00e1i\",\"start_time\":\"2025-05-14 00:29:51\",\"end_time\":\"2026-05-14 00:29:51\",\"location_id\":63},\"RTK_78_1746549444\":{\"account_id\":\"RTK_78_1746549444\",\"username\":\"TRIAL_YBI005\",\"num_account\":1,\"package_name\":\"G\\u00f3i 1 N\\u0103m\",\"province\":\"Y\\u00ean B\\u00e1i\",\"start_time\":\"2025-05-13 23:37:23\",\"end_time\":\"2026-05-13 23:37:23\",\"location_id\":63},\"RTK_93_1746551383\":{\"account_id\":\"RTK_93_1746551383\",\"username\":\"TRIAL_YBI006\",\"num_account\":1,\"package_name\":\"G\\u00f3i 1 N\\u0103m\",\"province\":\"Y\\u00ean B\\u00e1i\",\"start_time\":\"2025-08-14 00:09:42\",\"end_time\":\"2026-08-14 00:09:42\",\"location_id\":63}},\"package\":{\"id\":4,\"name\":\"G\\u00f3i 1 N\\u0103m\",\"price\":\"900000.00\",\"duration_text\":\"\\/ 1 n\\u0103m\"},\"total_accounts\":3,\"total_price\":2700000,\"timestamp\":1746576552}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 07:09:12'),
(130, 92, 'renewal_request', 'registration', '137', NULL, '{\"accounts\":{\"RTK_112_1746552592\":{\"account_id\":\"RTK_112_1746552592\",\"username\":\"TRIAL_YBI007\",\"num_account\":1,\"package_name\":\"G\\u00f3i 3 Th\\u00e1ng\",\"province\":\"Y\\u00ean B\\u00e1i\",\"start_time\":\"2025-05-14 00:29:51\",\"end_time\":\"2025-08-14 00:29:51\",\"location_id\":63}},\"package\":{\"id\":2,\"name\":\"G\\u00f3i 3 Th\\u00e1ng\",\"price\":\"270000.00\",\"duration_text\":\"\\/ 3 th\\u00e1ng\"},\"total_accounts\":1,\"total_price\":270000,\"timestamp\":1746576910}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 07:15:10'),
(131, 92, 'renewal_request', 'registration', '138', NULL, '{\"registration_id\":\"138\",\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":300000,\"package\":\"G\\u00f3i 1 Th\\u00e1ng\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 07:25:29'),
(132, 92, 'renewal_request', 'registration', '139', NULL, '{\"registration_id\":\"139\",\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_64_1746457824\",\"RTK_68_1746510776\",\"RTK_69_1746511192\",\"RTK_70_1746511206\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":700000,\"package\":\"G\\u00f3i 1 Th\\u00e1ng\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 07:26:49'),
(133, 92, 'login', 'user', '92', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 21:55:33'),
(134, 92, 'renewal_request', 'registration', '140', NULL, '{\"registration_id\":\"140\",\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_93_1746551383\"],\"total_price\":540000,\"package\":\"G\\u00f3i 3 Th\\u00e1ng\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 21:55:52'),
(135, 92, 'renewal_request', 'registration', '141', NULL, '{\"registration_id\":\"141\",\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":1500000,\"package\":\"G\\u00f3i 6 Th\\u00e1ng\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 21:57:40'),
(136, 92, 'purchase', 'registration', '142', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 21:59:27'),
(137, 92, 'purchase', 'registration', '143', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 22:01:05'),
(138, 92, 'purchase', 'registration', '144', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 22:01:23'),
(139, 92, 'purchase', 'registration', '145', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 22:10:34'),
(140, 92, 'renewal_request', 'registration', '146', NULL, '{\"registration_id\":\"146\",\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_61_1746444219\",\"RTK_70_1746511206\",\"RTK_93_1746551383\"],\"total_price\":1080000,\"package\":\"G\\u00f3i 3 Th\\u00e1ng\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 22:11:05'),
(141, 92, 'renewal_request', 'registration', '147', NULL, '{\"registration_id\":\"147\",\"selected_accounts\":[\"RTK_112_1746552592\",\"RTK_61_1746444219\",\"RTK_64_1746457824\",\"RTK_68_1746510776\",\"RTK_69_1746511192\",\"RTK_70_1746511206\",\"RTK_78_1746549444\",\"RTK_93_1746551383\"],\"total_price\":4000000,\"package\":\"G\\u00f3i 6 Th\\u00e1ng\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36', '2025-05-07 22:13:30');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','customercare') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `admin_username`, `admin_password`, `role`, `created_at`, `updated_at`) VALUES
(2, 'Hello', 'ad', '$2y$10$pkuCs/ggxoVEA/gxeGwPeOAAsxtNTIUSceJoUtJ84twv0UQpYBDqW', 'admin', '2025-04-27 14:31:55', '2025-05-03 22:43:23');

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
-- Table structure for table `guide`
--

CREATE TABLE `guide` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_id` int NOT NULL,
  `topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `id` int NOT NULL,
  `transaction_history_id` int NOT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invoice_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejected_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`id`, `transaction_history_id`, `status`, `invoice_file`, `rejected_reason`, `created_at`) VALUES
(4, 60, 'pending', NULL, NULL, '2025-05-05 18:23:47'),
(5, 59, 'pending', NULL, NULL, '2025-05-05 18:32:12'),
(6, 86, 'pending', NULL, NULL, '2025-05-07 00:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `id` int NOT NULL,
  `province` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
(55, 91, 7, 63, NULL, 1, '2025-05-05 18:03:46', '2025-05-12 18:03:46', 0.00, 0, 0.00, 0.00, 'active', '2025-05-05 18:03:46', '2025-05-05 18:03:47', NULL, NULL),
(56, 91, 7, 63, NULL, 1, '2025-05-05 18:03:56', '2025-05-12 18:03:56', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-05 18:03:56', '2025-05-05 18:03:56', NULL, NULL),
(57, 91, 1, 63, NULL, 5, '2025-05-05 18:10:28', '2025-06-05 18:10:28', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-05 18:10:28', '2025-05-05 18:10:28', NULL, NULL),
(58, 91, 1, 63, NULL, 5, '2025-05-05 18:15:28', '2025-06-05 18:15:28', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-05 18:15:28', '2025-05-05 18:15:28', NULL, NULL),
(59, 92, 1, 63, NULL, 7, '2025-05-05 18:18:30', '2025-06-05 18:18:30', 100000.00, 0, 0.00, 700000.00, 'pending', '2025-05-05 18:18:30', '2025-05-05 18:18:30', NULL, NULL),
(60, 92, 7, 63, NULL, 1, '2025-05-05 18:23:31', '2025-05-12 18:23:31', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-05 18:23:31', '2025-05-05 18:23:31', NULL, NULL),
(61, 92, 7, 54, NULL, 1, '2025-05-05 18:23:38', '2025-05-12 18:23:38', 0.00, 0, 0.00, 0.00, 'active', '2025-05-05 18:23:38', '2025-05-05 18:23:39', NULL, NULL),
(62, 92, 1, 63, NULL, 5, '2025-05-05 21:38:36', '2025-06-05 21:38:36', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-05 21:38:36', '2025-05-05 22:06:23', NULL, 'test'),
(63, 92, 7, 63, NULL, 1, '2025-05-05 22:07:56', '2025-05-12 22:07:56', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-05 22:07:56', '2025-05-05 22:07:56', NULL, NULL),
(64, 92, 7, 63, NULL, 1, '2025-05-05 22:10:23', '2025-05-12 22:10:23', 0.00, 0, 0.00, 0.00, 'active', '2025-05-05 22:10:23', '2025-05-05 22:10:24', NULL, NULL),
(65, 92, 1, 63, NULL, 1, '2025-05-06 12:48:42', '2025-06-05 12:48:42', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-06 12:48:42', NULL, NULL, NULL),
(66, 92, 4, 63, NULL, 1, '2025-05-06 12:48:58', '2026-05-06 12:48:58', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-06 12:48:58', NULL, NULL, NULL),
(67, 92, 2, 63, NULL, 1, '2025-05-06 12:49:08', '2025-08-04 12:49:08', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-06 12:49:08', NULL, NULL, NULL),
(68, 92, 7, 63, NULL, 1, '2025-05-06 12:52:55', '2025-05-13 12:52:55', 0.00, 0, 0.00, 0.00, 'active', '2025-05-06 12:52:55', '2025-05-06 12:52:56', NULL, NULL),
(69, 92, 7, 63, NULL, 1, '2025-05-06 12:59:51', '2025-05-13 12:59:51', 0.00, 0, 0.00, 0.00, 'active', '2025-05-06 12:59:51', '2025-05-06 12:59:52', NULL, NULL),
(70, 92, 7, 54, NULL, 1, '2025-05-06 13:00:05', '2025-05-13 13:00:05', 0.00, 0, 0.00, 0.00, 'active', '2025-05-06 13:00:05', '2025-05-06 13:00:06', NULL, NULL),
(71, 92, 2, 63, NULL, 2, '2025-05-06 13:00:26', '2025-08-04 13:00:26', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-06 13:00:26', NULL, NULL, NULL),
(72, 92, 1, 63, NULL, 5, '2025-05-06 13:04:38', '2025-06-06 13:04:38', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-06 13:04:38', '2025-05-06 13:04:38', NULL, NULL),
(73, 92, 1, 63, NULL, 5, '2025-05-06 22:18:48', '2025-06-06 22:18:48', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-06 22:18:48', '2025-05-06 22:18:48', NULL, NULL),
(74, 92, 7, 63, NULL, 1, '2025-05-06 23:36:08', '2025-05-13 23:36:08', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-06 23:36:08', '2025-05-06 23:36:08', NULL, NULL),
(75, 92, 1, 63, NULL, 5, '2025-05-06 23:36:18', '2025-06-06 23:36:18', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-06 23:36:18', '2025-05-06 23:36:18', NULL, NULL),
(77, 92, 7, 63, NULL, 1, '2025-05-06 23:36:47', '2025-05-13 23:36:47', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-06 23:36:47', '2025-05-06 23:36:47', NULL, NULL),
(78, 92, 7, 63, NULL, 1, '2025-05-06 23:37:23', '2025-05-13 23:37:23', 0.00, 0, 0.00, 0.00, 'active', '2025-05-06 23:37:23', '2025-05-06 23:37:24', NULL, NULL),
(79, 92, 3, 63, NULL, 1, '2025-05-13 23:37:23', '2025-11-13 23:37:23', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-06 23:52:18', '2025-05-06 23:52:18', NULL, NULL),
(80, 92, 1, 63, NULL, 1, '2025-05-13 12:52:55', '2025-06-13 12:52:55', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-06 23:54:18', '2025-05-06 23:54:18', NULL, NULL),
(81, 92, 2, 63, NULL, 1, '2025-05-13 12:59:51', '2025-08-13 12:59:51', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-06 23:54:18', '2025-05-06 23:54:18', NULL, NULL),
(82, 92, 3, 54, NULL, 1, '2025-05-13 13:00:05', '2025-11-13 13:00:05', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-06 23:54:18', '2025-05-06 23:54:18', NULL, NULL),
(83, 92, 4, 63, NULL, 1, '2025-05-13 23:37:23', '2026-05-13 23:37:23', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-06 23:54:18', '2025-05-06 23:54:18', NULL, NULL),
(84, 92, 1, 54, NULL, 1, '2025-05-13 13:00:05', '2025-06-13 13:00:05', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-06 23:55:55', '2025-05-06 23:55:55', NULL, NULL),
(85, 92, 1, 63, NULL, 1, '2025-05-13 23:37:23', '2025-06-13 23:37:23', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-06 23:55:55', '2025-05-06 23:55:55', NULL, NULL),
(86, 92, 1, 63, NULL, 5, '2025-05-06 23:57:03', '2025-06-06 23:57:03', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-06 23:57:03', '2025-05-06 23:57:03', NULL, NULL),
(87, 92, 1, 54, NULL, 1, '2025-05-13 13:00:05', '2025-06-13 13:00:05', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-06 23:57:21', '2025-05-06 23:57:21', NULL, NULL),
(88, 92, 1, 63, NULL, 1, '2025-05-13 23:37:23', '2025-06-13 23:37:23', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-06 23:57:21', '2025-05-06 23:57:21', NULL, NULL),
(90, 92, 2, 63, NULL, 1, '2025-05-13 23:37:23', '2025-08-13 23:37:23', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:09:00', '2025-05-07 00:09:00', NULL, NULL),
(91, 92, 3, 54, NULL, 1, '2025-05-13 13:00:05', '2025-11-13 13:00:05', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 00:09:09', '2025-05-07 00:09:09', NULL, NULL),
(92, 92, 2, 63, NULL, 1, '2025-05-13 23:37:23', '2025-08-13 23:37:23', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:09:09', '2025-05-07 00:09:09', NULL, NULL),
(93, 92, 7, 63, NULL, 1, '2025-05-07 00:09:42', '2025-05-14 00:09:42', 0.00, 0, 0.00, 0.00, 'active', '2025-05-07 00:09:42', '2025-05-07 00:09:43', NULL, NULL),
(94, 92, 2, 63, NULL, 1, '2025-05-13 23:37:23', '2025-08-13 23:37:23', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:09:56', '2025-05-07 00:09:56', NULL, NULL),
(95, 92, 2, 63, NULL, 1, '2025-05-14 00:09:42', '2025-08-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:09:56', '2025-05-07 00:09:56', NULL, NULL),
(96, 92, 1, 63, NULL, 5, '2025-05-07 00:13:19', '2025-06-07 00:13:19', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 00:13:19', '2025-05-07 00:13:19', NULL, NULL),
(98, 92, 1, 63, NULL, 2, '2025-05-07 00:13:49', '2025-06-07 00:13:49', 100000.00, 0, 0.00, 200000.00, 'pending', '2025-05-07 00:13:49', '2025-05-07 00:13:49', NULL, NULL),
(99, 92, 2, 54, NULL, 1, '2025-05-13 13:00:05', '2025-08-13 13:00:05', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:14:05', '2025-05-07 00:14:05', NULL, NULL),
(100, 92, 3, 63, NULL, 1, '2025-05-13 23:37:23', '2025-11-13 23:37:23', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 00:14:05', '2025-05-07 00:14:05', NULL, NULL),
(101, 92, 2, 63, NULL, 1, '2025-05-14 00:09:42', '2025-08-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:14:05', '2025-05-07 00:14:05', NULL, NULL),
(102, 92, 1, 63, NULL, 3, '2025-05-07 00:20:17', '2025-06-07 00:20:17', 100000.00, 0, 0.00, 300000.00, 'pending', '2025-05-07 00:20:17', '2025-05-07 00:20:17', NULL, NULL),
(103, 92, 2, 54, NULL, 1, '2025-05-13 13:00:05', '2025-08-13 13:00:05', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:20:37', '2025-05-07 00:20:37', NULL, NULL),
(104, 92, 3, 63, NULL, 1, '2025-05-13 23:37:23', '2025-11-13 23:37:23', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 00:20:37', '2025-05-07 00:20:37', NULL, NULL),
(105, 92, 2, 63, NULL, 1, '2025-05-14 00:09:42', '2025-08-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:20:37', '2025-05-07 00:20:37', NULL, NULL),
(106, 92, 2, 63, NULL, 1, '2025-05-14 00:09:42', '2025-08-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:22:19', '2025-05-07 00:22:19', NULL, NULL),
(107, 92, 2, 54, NULL, 1, '2025-05-13 13:00:05', '2025-08-13 13:00:05', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:22:56', '2025-05-07 00:22:56', NULL, NULL),
(108, 92, 2, 63, NULL, 1, '2025-05-13 23:37:23', '2025-08-13 23:37:23', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:22:56', '2025-05-07 00:22:56', NULL, NULL),
(109, 92, 2, 63, NULL, 1, '2025-08-14 00:09:42', '2025-11-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:22:56', '2025-05-07 00:22:56', NULL, NULL),
(110, 92, 7, 63, NULL, 1, '2025-05-07 00:28:33', '2025-05-14 00:28:33', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-07 00:28:33', '2025-05-07 00:28:33', NULL, NULL),
(111, 92, 7, 62, NULL, 1, '2025-05-07 00:28:56', '2025-05-14 00:28:56', 0.00, 0, 0.00, 0.00, 'pending', '2025-05-07 00:28:56', '2025-05-07 00:28:56', NULL, NULL),
(112, 92, 7, 63, NULL, 1, '2025-05-07 00:29:51', '2025-05-14 00:29:51', 0.00, 0, 0.00, 0.00, 'active', '2025-05-07 00:29:51', '2025-05-07 00:29:52', NULL, NULL),
(113, 92, 1, 63, NULL, 1, '2025-05-14 00:29:51', '2025-06-14 00:29:51', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-07 00:30:11', '2025-05-07 00:30:11', NULL, NULL),
(114, 92, 2, 54, NULL, 1, '2025-05-13 13:00:05', '2025-08-13 13:00:05', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:30:11', '2025-05-07 00:30:11', NULL, NULL),
(115, 92, 3, 63, NULL, 1, '2025-05-13 23:37:23', '2025-11-13 23:37:23', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 00:30:11', '2025-05-07 00:30:11', NULL, NULL),
(116, 92, 2, 63, NULL, 1, '2025-08-14 00:09:42', '2025-11-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 00:30:11', '2025-05-07 00:30:11', NULL, NULL),
(117, 92, 2, 63, NULL, 1, '2025-05-14 00:29:51', '2025-08-14 00:29:51', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 06:44:37', '2025-05-07 06:44:37', NULL, NULL),
(118, 92, 2, 63, NULL, 1, '2025-08-14 00:09:42', '2025-11-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 06:44:37', '2025-05-07 06:44:37', NULL, NULL),
(119, 92, 1, 63, NULL, 1, '2025-05-14 00:29:51', '2025-06-14 00:29:51', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(120, 92, 4, 63, NULL, 1, '2025-05-07 06:45:31', '2026-05-07 06:45:31', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(121, 92, 4, 63, NULL, 1, '2025-05-13 12:52:55', '2026-05-13 12:52:55', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(122, 92, 4, 63, NULL, 1, '2025-05-13 12:59:51', '2026-05-13 12:59:51', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(123, 92, 4, 54, NULL, 1, '2025-05-13 13:00:05', '2026-05-13 13:00:05', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(124, 92, 3, 63, NULL, 1, '2025-05-13 23:37:23', '2025-11-13 23:37:23', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(125, 92, 4, 63, NULL, 1, '2025-08-14 00:09:42', '2026-08-14 00:09:42', 900000.00, 0, 0.00, 900000.00, 'pending', '2025-05-07 06:45:31', '2025-05-07 06:45:31', NULL, NULL),
(126, 92, 1, 63, NULL, 1, '2025-05-14 00:29:51', '2025-06-14 00:29:51', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-07 06:47:49', '2025-05-07 06:47:49', NULL, NULL),
(127, 92, 3, 63, NULL, 1, '2025-05-14 00:29:51', '2025-11-14 00:29:51', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 06:48:27', '2025-05-07 06:48:27', NULL, NULL),
(128, 92, 3, 63, NULL, 1, '2025-05-13 23:37:23', '2025-11-13 23:37:23', 500000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 06:48:27', '2025-05-07 06:48:27', NULL, NULL),
(129, 92, 2, 63, NULL, 1, '2025-08-14 00:09:42', '2025-11-14 00:09:42', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 06:48:27', '2025-05-07 06:48:27', NULL, NULL),
(130, 92, 1, 63, NULL, 5, '2025-05-07 06:55:23', '2025-06-07 06:55:23', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 06:55:23', '2025-05-07 06:55:23', NULL, NULL),
(131, 92, 1, 63, NULL, 5, '2025-05-07 06:55:38', '2025-06-07 06:55:38', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 06:55:38', '2025-05-07 06:55:38', NULL, NULL),
(132, 92, 1, 21, NULL, 6, '2025-05-07 06:56:10', '2025-06-07 06:56:10', 100000.00, 0, 0.00, 600000.00, 'pending', '2025-05-07 06:56:10', '2025-05-07 06:56:10', NULL, NULL),
(133, 92, 2, 63, NULL, 1, '2025-05-14 00:29:51', '2025-08-14 00:29:51', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 06:56:26', '2025-05-07 06:56:26', NULL, NULL),
(134, 92, 1, 63, NULL, 1, '2025-05-07 07:05:10', '2025-06-14 00:29:51', 100000.00, 0, 0.00, 100000.00, 'pending', '2025-05-07 07:05:10', '2025-05-07 07:05:10', NULL, NULL),
(135, 92, 1, 63, NULL, 5, '2025-05-07 07:09:02', '2025-06-07 07:09:02', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 07:09:02', '2025-05-07 07:09:02', NULL, NULL),
(136, 92, 4, 63, NULL, 3, '2025-05-07 07:09:12', '2026-05-14 00:29:51', 2700000.00, 0, 0.00, 2700000.00, 'pending', '2025-05-07 07:09:12', '2025-05-07 07:09:12', NULL, NULL),
(137, 92, 2, 63, NULL, 1, '2025-05-07 07:15:10', '2025-08-14 00:29:51', 270000.00, 0, 0.00, 270000.00, 'pending', '2025-05-07 07:15:10', '2025-05-07 07:15:10', NULL, NULL),
(138, 92, 1, 63, NULL, 3, '2025-05-14 00:29:51', '2025-06-14 00:29:51', 100000.00, 0, 0.00, 300000.00, 'pending', '2025-05-07 07:25:29', '2025-05-07 07:25:29', NULL, NULL),
(139, 92, 1, 63, NULL, 7, '2025-05-14 00:29:51', '2025-06-14 00:29:51', 100000.00, 0, 0.00, 700000.00, 'pending', '2025-05-07 07:26:49', '2025-05-07 07:26:49', NULL, NULL),
(140, 92, 2, 63, NULL, 2, '2025-05-14 00:29:51', '2025-08-14 00:29:51', 270000.00, 0, 0.00, 540000.00, 'pending', '2025-05-07 21:55:52', '2025-05-07 21:55:52', NULL, NULL),
(141, 92, 3, 63, NULL, 3, '2025-05-14 00:29:51', '2025-11-14 00:29:51', 500000.00, 0, 0.00, 1500000.00, 'pending', '2025-05-07 21:57:40', '2025-05-07 21:57:40', NULL, NULL),
(142, 92, 1, 62, NULL, 5, '2025-05-07 21:59:27', '2025-06-07 21:59:27', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 21:59:27', '2025-05-07 21:59:27', NULL, NULL),
(143, 92, 1, 63, NULL, 7, '2025-05-07 22:01:05', '2025-06-07 22:01:05', 100000.00, 0, 0.00, 700000.00, 'pending', '2025-05-07 22:01:05', '2025-05-07 22:01:05', NULL, NULL),
(144, 92, 2, 63, NULL, 99, '2025-05-07 22:01:23', '2025-08-07 22:01:23', 270000.00, 0, 0.00, 26730000.00, 'pending', '2025-05-07 22:01:23', '2025-05-07 22:01:23', NULL, NULL),
(145, 92, 1, 63, NULL, 5, '2025-05-07 22:10:34', '2025-06-07 22:10:34', 100000.00, 0, 0.00, 500000.00, 'pending', '2025-05-07 22:10:34', '2025-05-07 22:10:34', NULL, NULL),
(146, 92, 2, 63, NULL, 4, '2025-05-14 00:29:51', '2025-08-14 00:29:51', 270000.00, 0, 0.00, 1080000.00, 'pending', '2025-05-07 22:11:05', '2025-05-07 22:11:05', NULL, NULL),
(147, 92, 3, 63, NULL, 8, '2025-05-14 00:29:51', '2025-11-14 00:29:51', 500000.00, 0, 0.00, 4000000.00, 'pending', '2025-05-07 22:13:30', '2025-05-07 22:13:30', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('admin','customercare') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permission`, `allowed`) VALUES
('admin', 'account_management', 1),
('admin', 'admin_user_create', 1),
('admin', 'dashboard', 1),
('admin', 'guide_management', 1),
('admin', 'invoice_management', 1),
('admin', 'invoice_review', 1),
('admin', 'permission_edit', 1),
('admin', 'permission_management', 1),
('admin', 'referral_management', 1),
('admin', 'reports', 1),
('admin', 'revenue_management', 1),
('admin', 'settings', 1),
('admin', 'user_create', 1),
('admin', 'user_management', 1),
('customercare', 'dashboard', 1),
('customercare', 'invoice_management', 1),
('customercare', 'user_management', 1);

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
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
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

INSERT INTO `survey_account` (`id`, `registration_id`, `start_time`, `end_time`, `username_acc`, `password_acc`, `concurrent_user`, `enabled`, `caster`, `user_type`, `regionIds`, `customerBizType`, `area`, `created_at`, `updated_at`, `deleted_at`) VALUES
('RTK_112_1746552592', 112, '2025-05-07 00:29:51', '2025-05-14 00:29:51', 'TRIAL_YBI007', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-07 00:29:52', NULL, NULL),
('RTK_55_1746443027', 55, '2025-05-05 18:03:46', '2025-05-12 18:03:46', 'TRIAL_YBI001', '0981190564', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-05 18:03:47', NULL, NULL),
('RTK_61_1746444219', 61, '2025-05-05 18:23:38', '2025-05-12 18:23:38', 'TRIAL_TNN001', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-05 18:23:39', NULL, NULL),
('RTK_64_1746457824', 64, '2025-05-05 22:10:23', '2025-05-05 22:10:23', 'TRIAL_YBI002', '0981190562', 1, 0, NULL, NULL, NULL, 1, NULL, '2025-05-05 22:10:24', '2025-05-05 22:12:19', NULL),
('RTK_68_1746510776', 68, '2025-05-06 12:52:55', '2025-05-13 12:52:55', 'TRIAL_YBI003', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-06 12:52:56', NULL, NULL),
('RTK_69_1746511192', 69, '2025-05-06 12:59:51', '2025-05-13 12:59:51', 'TRIAL_YBI004', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-06 12:59:52', NULL, NULL),
('RTK_70_1746511206', 70, '2025-05-06 13:00:05', '2025-05-13 13:00:05', 'TRIAL_TNN002', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-06 13:00:06', NULL, NULL),
('RTK_78_1746549444', 78, '2025-05-06 23:37:23', '2025-05-13 23:37:23', 'TRIAL_YBI005', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-06 23:37:24', NULL, NULL),
('RTK_93_1746551383', 93, '2025-05-07 00:09:42', '2025-08-14 00:09:42', 'TRIAL_YBI006', '0981190562', 1, 1, NULL, NULL, NULL, 1, NULL, '2025-05-07 00:09:43', '2025-05-07 00:22:25', NULL);

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
  `payment_method` enum('Chuyển khoản ngân hàng') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `export_invoice` tinyint(1) DEFAULT '0',
  `invoice_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payment_confirmed` tinyint(1) DEFAULT '0',
  `payment_confirmed_at` datetime DEFAULT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_history`
--

INSERT INTO `transaction_history` (`id`, `registration_id`, `user_id`, `transaction_type`, `amount`, `status`, `payment_method`, `payment_image`, `export_invoice`, `invoice_info`, `payment_confirmed`, `payment_confirmed_at`, `payment_reference`, `created_at`, `updated_at`) VALUES
(59, 60, 92, 'purchase', 0.00, 'completed', NULL, 'reg_60_1746444618.png', 0, NULL, 0, NULL, NULL, '2025-05-05 18:23:31', '2025-05-05 18:31:01'),
(60, 61, 92, 'purchase', 0.00, 'completed', NULL, 'reg_61_1746444766.jpg', 1, NULL, 0, NULL, NULL, '2025-05-05 18:23:38', '2025-05-05 18:33:50'),
(61, 62, 92, 'purchase', 500000.00, 'failed', NULL, 'reg_62_1746455922.png', 0, NULL, 0, NULL, NULL, '2025-05-05 21:38:36', '2025-05-05 22:05:03'),
(62, 63, 92, 'purchase', 0.00, 'pending', NULL, 'reg_63_1746458014.png', 0, NULL, 0, NULL, NULL, '2025-05-05 22:07:56', '2025-05-05 22:13:34'),
(63, 64, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-05 22:10:24', NULL, '2025-05-05 22:10:23', '2025-05-05 22:10:24'),
(64, 65, 92, 'renewal', 100000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 12:48:42', NULL),
(65, 66, 92, 'renewal', 900000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 12:48:58', NULL),
(66, 67, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 12:49:08', NULL),
(67, 68, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-06 12:52:56', NULL, '2025-05-06 12:52:55', '2025-05-06 12:52:56'),
(68, 69, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-06 12:59:52', NULL, '2025-05-06 12:59:51', '2025-05-06 12:59:52'),
(69, 70, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-06 13:00:06', NULL, '2025-05-06 13:00:05', '2025-05-06 13:00:06'),
(70, 71, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 13:00:26', NULL),
(71, 72, 92, 'purchase', 500000.00, 'pending', NULL, 'reg_72_1746511483.png', 0, NULL, 0, NULL, NULL, '2025-05-06 13:04:38', '2025-05-06 13:04:43'),
(72, 73, 92, 'purchase', 500000.00, 'pending', NULL, 'reg_73_1746544734.png', 0, NULL, 0, NULL, NULL, '2025-05-06 22:18:48', '2025-05-06 22:18:54'),
(73, 74, 92, 'purchase', 0.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:36:08', '2025-05-06 23:36:08'),
(74, 75, 92, 'purchase', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:36:18', '2025-05-06 23:36:18'),
(75, 77, 92, 'purchase', 0.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:36:47', '2025-05-06 23:36:47'),
(76, 78, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-06 23:37:24', NULL, '2025-05-06 23:37:23', '2025-05-06 23:37:24'),
(77, 79, 92, 'renewal', 500000.00, 'pending', NULL, 'reg_79_1746550344.png', 0, NULL, 0, NULL, NULL, '2025-05-06 23:52:18', '2025-05-06 23:52:24'),
(78, NULL, 92, 'renewal', 100000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:54:18', '2025-05-07 00:02:37'),
(79, 81, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:54:18', '2025-05-06 23:54:18'),
(80, 82, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:54:18', '2025-05-06 23:54:18'),
(81, 83, 92, 'renewal', 900000.00, 'pending', NULL, 'reg_83_1746550472.png', 0, NULL, 0, NULL, NULL, '2025-05-06 23:54:18', '2025-05-06 23:54:32'),
(82, 84, 92, 'renewal', 100000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:55:55', '2025-05-06 23:55:55'),
(83, 85, 92, 'renewal', 100000.00, 'pending', NULL, 'reg_85_1746550562.png', 0, NULL, 0, NULL, NULL, '2025-05-06 23:55:55', '2025-05-06 23:56:02'),
(84, 86, 92, 'purchase', 500000.00, 'pending', NULL, 'reg_86_1746550629.png', 0, NULL, 0, NULL, NULL, '2025-05-06 23:57:03', '2025-05-06 23:57:09'),
(85, 87, 92, 'renewal', 100000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-06 23:57:21', '2025-05-06 23:57:21'),
(86, NULL, 92, 'renewal', 100000.00, 'completed', NULL, 'reg_88_1746550648.png', 0, NULL, 0, NULL, NULL, '2025-05-06 23:57:21', '2025-05-07 00:04:15'),
(87, 90, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:09:00', '2025-05-07 00:09:00'),
(88, 91, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:09:09', '2025-05-07 00:09:09'),
(89, 92, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:09:09', '2025-05-07 00:09:09'),
(90, 93, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-07 00:09:43', NULL, '2025-05-07 00:09:42', '2025-05-07 00:09:43'),
(91, 94, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:09:56', '2025-05-07 00:09:56'),
(92, 95, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:09:56', '2025-05-07 00:09:56'),
(93, 96, 92, 'purchase', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:13:19', '2025-05-07 00:13:19'),
(94, 98, 92, 'purchase', 200000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:13:49', '2025-05-07 00:13:49'),
(95, 99, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:14:05', '2025-05-07 00:14:05'),
(96, 100, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:14:05', '2025-05-07 00:14:05'),
(97, 101, 92, 'renewal', 270000.00, 'pending', NULL, 'reg_101_1746551652.png', 0, NULL, 0, NULL, NULL, '2025-05-07 00:14:05', '2025-05-07 00:14:12'),
(98, 102, 92, 'purchase', 300000.00, 'pending', NULL, 'reg_102_1746552025.png', 0, NULL, 0, NULL, NULL, '2025-05-07 00:20:17', '2025-05-07 00:20:25'),
(99, 103, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:20:37', '2025-05-07 00:20:37'),
(100, 104, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:20:37', '2025-05-07 00:20:37'),
(101, 105, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:20:37', '2025-05-07 00:20:37'),
(102, 106, 92, 'renewal', 270000.00, 'pending', NULL, 'reg_106_1746552145.png', 0, NULL, 0, NULL, NULL, '2025-05-07 00:22:19', '2025-05-07 00:22:25'),
(103, 107, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:22:56', '2025-05-07 00:22:56'),
(104, 108, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:22:56', '2025-05-07 00:22:56'),
(105, 109, 92, 'renewal', 270000.00, 'pending', NULL, 'reg_109_1746552332.png', 0, NULL, 0, NULL, NULL, '2025-05-07 00:22:56', '2025-05-07 00:25:32'),
(106, 110, 92, 'purchase', 0.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:28:33', '2025-05-07 00:28:33'),
(107, 111, 92, 'purchase', 0.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:28:56', '2025-05-07 00:28:56'),
(108, 112, 92, 'purchase', 0.00, 'completed', NULL, NULL, 0, NULL, 1, '2025-05-07 00:29:52', NULL, '2025-05-07 00:29:51', '2025-05-07 00:29:52'),
(109, 113, 92, 'renewal', 100000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:30:11', '2025-05-07 00:30:11'),
(110, 114, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:30:11', '2025-05-07 00:30:11'),
(111, 115, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:30:11', '2025-05-07 00:30:11'),
(112, 116, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 00:30:11', '2025-05-07 00:30:11'),
(113, 117, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:44:37', '2025-05-07 06:44:37'),
(114, 118, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:44:37', '2025-05-07 06:44:37'),
(115, 119, 92, 'renewal', 100000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:45:31'),
(116, 120, 92, 'renewal', 900000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:45:31'),
(117, 121, 92, 'renewal', 900000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:45:31'),
(118, 122, 92, 'renewal', 900000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:45:31'),
(119, 123, 92, 'renewal', 900000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:45:31'),
(120, 124, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:45:31'),
(121, 125, 92, 'renewal', 900000.00, 'pending', NULL, 'reg_125_1746575220.png', 0, NULL, 0, NULL, NULL, '2025-05-07 06:45:31', '2025-05-07 06:47:00'),
(122, 126, 92, 'renewal', 100000.00, 'pending', NULL, 'reg_126_1746575274.png', 0, NULL, 0, NULL, NULL, '2025-05-07 06:47:49', '2025-05-07 06:47:54'),
(123, 127, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:48:27', '2025-05-07 06:48:27'),
(124, 128, 92, 'renewal', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:48:27', '2025-05-07 06:48:27'),
(125, 129, 92, 'renewal', 270000.00, 'pending', NULL, 'reg_129_1746575312.png', 0, NULL, 0, NULL, NULL, '2025-05-07 06:48:27', '2025-05-07 06:48:32'),
(126, 130, 92, 'purchase', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:55:23', '2025-05-07 06:55:23'),
(127, 131, 92, 'purchase', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:55:38', '2025-05-07 06:55:38'),
(128, 132, 92, 'purchase', 600000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:56:10', '2025-05-07 06:56:10'),
(129, 133, 92, 'renewal', 270000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 06:56:26', '2025-05-07 06:56:26'),
(130, 134, 92, 'renewal', 100000.00, 'pending', NULL, 'reg_134_1746576324.png', 0, NULL, 0, NULL, NULL, '2025-05-07 07:05:10', '2025-05-07 07:05:24'),
(131, 135, 92, 'purchase', 500000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 07:09:02', '2025-05-07 07:09:02'),
(132, 136, 92, 'renewal', 2700000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 07:09:12', '2025-05-07 07:09:12'),
(133, 137, 92, 'renewal', 270000.00, 'pending', NULL, 'reg_137_1746576931.png', 0, NULL, 0, NULL, NULL, '2025-05-07 07:15:10', '2025-05-07 07:15:31'),
(134, 138, 92, 'renewal', 300000.00, 'pending', NULL, 'reg_138_1746577538.png', 0, NULL, 0, NULL, NULL, '2025-05-07 07:25:29', '2025-05-07 07:25:38'),
(135, 139, 92, 'renewal', 700000.00, 'pending', NULL, 'reg_139_1746577617.png', 0, NULL, 0, NULL, NULL, '2025-05-07 07:26:49', '2025-05-07 07:26:57'),
(136, 140, 92, 'renewal', 540000.00, 'pending', NULL, 'reg_140_1746629763.png', 0, NULL, 0, NULL, NULL, '2025-05-07 21:55:52', '2025-05-07 21:56:03'),
(137, 141, 92, 'renewal', 1500000.00, 'pending', NULL, 'reg_141_1746629866.png', 0, NULL, 0, NULL, NULL, '2025-05-07 21:57:40', '2025-05-07 21:57:46'),
(138, 142, 92, 'purchase', 500000.00, 'pending', NULL, 'reg_142_1746629981.png', 0, NULL, 0, NULL, NULL, '2025-05-07 21:59:27', '2025-05-07 21:59:41'),
(139, 143, 92, 'purchase', 700000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 22:01:05', '2025-05-07 22:01:05'),
(140, 144, 92, 'purchase', 26730000.00, 'pending', NULL, NULL, 0, NULL, 0, NULL, NULL, '2025-05-07 22:01:23', '2025-05-07 22:01:23'),
(141, 145, 92, 'purchase', 500000.00, 'pending', NULL, 'reg_145_1746630640.png', 0, NULL, 0, NULL, NULL, '2025-05-07 22:10:34', '2025-05-07 22:10:40'),
(142, 146, 92, 'renewal', 1080000.00, 'pending', NULL, 'reg_146_1746630676.png', 0, NULL, 0, NULL, NULL, '2025-05-07 22:11:05', '2025-05-07 22:11:16'),
(143, 147, 92, 'renewal', 4000000.00, 'pending', NULL, 'reg_147_1746630834.png', 0, NULL, 0, NULL, NULL, '2025-05-07 22:13:30', '2025-05-07 22:13:54');

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
  `is_collaborator` tinyint(1) NOT NULL DEFAULT '0',
  `is_company` tinyint(1) DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_registered` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái người dùng (1: active, 0: inactive)',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `email_verify_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `phone`, `is_collaborator`, `is_company`, `company_name`, `tax_code`, `tax_registered`, `created_at`, `updated_at`, `deleted_at`, `status`, `email_verified`, `email_verify_token`) VALUES
(88, 'Long2004', 'tranhailong2408@gmail.com', '$2y$10$Ykj5ewmTa9z.EWdiVDPN6OKJ0lCXZU7L7ndZ7DDNd0LCXrHVraTOq', '0999999445', 0, 1, 'as', '123', NULL, '2025-04-27 14:55:01', '2025-05-04 09:08:21', NULL, 1, 1, NULL),
(89, 'Long2005', 'tranhailong2410@gmail.com', '$2y$10$4/NI4svx6977OTC0q13r5eRE5Uovn7nmR47Z.j3V9yy19n9XacQBa', '0900000005', 0, 1, 'ad', '123', NULL, '2025-04-29 15:00:08', '2025-05-04 09:08:23', NULL, 1, 1, NULL),
(90, 'Long2002', 'tranhailong2407@gmail.com', '$2y$10$Qt93QW.LtJzj/Vze0box4OmShRAfRxKCJdUtpYIJ5Ccu6H3nSy9Qe', '0999999443', 0, 0, NULL, NULL, NULL, '2025-05-04 09:08:01', NULL, NULL, 1, 0, '88a57ead39eec40e1867d27c87466a29904d3b6a280db60521cac1b77001c9a0'),
(91, 'nguyendozxc15@gmail.com', 'nguyendozxc15@gmail.com', '$2y$10$y8rSLvI2J48XZjTCb9IIgOmEf5Tz42r0OVLOrrlovJHt8JjVrNRvq', '0981190564', 0, 0, NULL, NULL, NULL, '2025-05-05 11:09:06', '2025-05-05 11:09:26', NULL, 1, 1, NULL),
(92, 'dovannguyen2005bv@gmail.com', 'dovannguyen2005bv@gmail.com', '$2y$10$BUQc5aTNhk0h1mBfQhrlG.1kkVfb8t.9Hj6lnHZYr43CEQUqBlLpS', '0981190562', 0, 0, 'Công ty cổ phần công nghệ Otek', '2222333332', NULL, '2025-05-05 14:33:57', '2025-05-05 17:10:23', NULL, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `account_groups`
--
ALTER TABLE `account_groups`
  ADD PRIMARY KEY (`registration_id`,`survey_account_id`),
  ADD KEY `idx_account_groups_survey_account_id` (`survey_account_id`);

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
  ADD KEY `idx_collaborator_user_id` (`user_id`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_error_type` (`error_type`),
  ADD KEY `idx_error_logs_user_id` (`user_id`);

--
-- Indexes for table `guide`
--
ALTER TABLE `guide`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slug` (`slug`),
  ADD KEY `idx_guide_author_id` (`author_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_transaction_history_id` (`transaction_history_id`);

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
  ADD KEY `idx_mount_point_location_id` (`location_id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_package_id` (`package_id`),
  ADD KEY `idx_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_methods_user_id` (`user_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_registration_user_id` (`user_id`),
  ADD KEY `idx_registration_package_id` (`package_id`),
  ADD KEY `idx_registration_location_id` (`location_id`),
  ADD KEY `idx_registration_collaborator_id` (`collaborator_id`),
  ADD KEY `idx_status_date` (`created_at`),
  ADD KEY `idx_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`permission`);

--
-- Indexes for table `station`
--
ALTER TABLE `station`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_station_mountpoint_id` (`mountpoint_id`);

--
-- Indexes for table `survey_account`
--
ALTER TABLE `survey_account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_acc` (`username_acc`),
  ADD KEY `idx_survey_account_registration_id` (`registration_id`);

--
-- Indexes for table `transaction_history`
--
ALTER TABLE `transaction_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_history_registration_id` (`registration_id`),
  ADD KEY `idx_transaction_history_user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_email` (`email`),
  ADD UNIQUE KEY `uq_user_username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_sessions_user_id` (`user_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_settings_user_id` (`user_id`);

--
-- Indexes for table `withdrawal`
--
ALTER TABLE `withdrawal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_withdrawal_collaborator_id` (`collaborator_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `guide`
--
ALTER TABLE `guide`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `transaction_history`
--
ALTER TABLE `transaction_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `withdrawal`
--
ALTER TABLE `withdrawal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_groups`
--
ALTER TABLE `account_groups`
  ADD CONSTRAINT `fk_account_groups_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_account_groups_survey_account` FOREIGN KEY (`survey_account_id`) REFERENCES `survey_account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `collaborator`
--
ALTER TABLE `collaborator`
  ADD CONSTRAINT `fk_collaborator_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `fk_error_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `guide`
--
ALTER TABLE `guide`
  ADD CONSTRAINT `fk_guide_admin` FOREIGN KEY (`author_id`) REFERENCES `admin` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `fk_invoice_transaction_history` FOREIGN KEY (`transaction_history_id`) REFERENCES `transaction_history` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `mount_point`
--
ALTER TABLE `mount_point`
  ADD CONSTRAINT `fk_mount_point_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `fk_payment_methods_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `fk_registration_collaborator` FOREIGN KEY (`collaborator_id`) REFERENCES `collaborator` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration_package` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `station`
--
ALTER TABLE `station`
  ADD CONSTRAINT `fk_station_mount_point` FOREIGN KEY (`mountpoint_id`) REFERENCES `mount_point` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `survey_account`
--
ALTER TABLE `survey_account`
  ADD CONSTRAINT `fk_survey_account_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction_history`
--
ALTER TABLE `transaction_history`
  ADD CONSTRAINT `fk_transaction_history_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_history_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `withdrawal`
--
ALTER TABLE `withdrawal`
  ADD CONSTRAINT `fk_withdrawal_collaborator` FOREIGN KEY (`collaborator_id`) REFERENCES `collaborator` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
