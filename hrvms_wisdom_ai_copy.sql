-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 09, 2026 at 03:19 AM
-- Server version: 9.1.0
-- PHP Version: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hrvms_wisdom_ai_copy`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_types`
--

DROP TABLE IF EXISTS `accommodation_types`;
CREATE TABLE IF NOT EXISTS `accommodation_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `AccommodationName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accommodation_types_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accommodation_types`
--

INSERT INTO `accommodation_types` (`id`, `resort_id`, `AccommodationName`, `created_by`, `modified_by`, `created_at`, `updated_at`, `Color`) VALUES
(11, 26, 'Single Share', 240, 240, '2025-11-13 10:21:00', '2025-11-13 10:21:00', '#371a94'),
(12, 26, 'Double Share', 240, 240, '2025-11-13 10:21:10', '2025-11-13 10:21:10', '#d95000'),
(13, 26, 'Four Share', 240, 240, '2025-11-13 11:03:56', '2025-11-13 11:04:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `action_stores`
--

DROP TABLE IF EXISTS `action_stores`;
CREATE TABLE IF NOT EXISTS `action_stores` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `ActionName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_stores_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cell_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `sms` tinyint(1) NOT NULL DEFAULT '0',
  `allow_login` tinyint(1) NOT NULL DEFAULT '0',
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('super','sub') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `added_by` bigint DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `first_name`, `last_name`, `middle_name`, `email`, `password`, `role_id`, `profile_picture`, `home_phone`, `cell_phone`, `start_date`, `address`, `sms`, `allow_login`, `notes`, `status`, `type`, `added_by`, `created_by`, `modified_by`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(11, 'Super', 'Admin', NULL, 'superadmin@wisdom.ai', '$2y$10$bfJl1n5ZAlxETlefFeAk8ey0e09LjTxDNPz.aNHCNm74Bz9qcdhLy', 1, NULL, NULL, NULL, '2025-10-28 13:17:26', 'Head Office', 1, 1, 'Default Super Admin account', 'active', 'super', NULL, 1, 1, NULL, '2025-10-28 13:17:26', '2025-11-11 18:23:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admins_password_resets`
--

DROP TABLE IF EXISTS `admins_password_resets`;
CREATE TABLE IF NOT EXISTS `admins_password_resets` (
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `admins_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins_password_resets`
--

INSERT INTO `admins_password_resets` (`email`, `token`, `created_at`) VALUES
('web.dev@thewisdom.ai', '$2y$10$G2i87GeVRM8JQagAY3SK0O4ZizPCcq0EkwHI571xRYLv2eAjq3yoS', '2025-11-11 18:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `admin_modules`
--

DROP TABLE IF EXISTS `admin_modules`;
CREATE TABLE IF NOT EXISTS `admin_modules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_modules`
--

INSERT INTO `admin_modules` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Admin Users', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(2, 'Settings', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(3, 'Roles Permissions', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(4, 'Email Templates', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(5, 'Resorts', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(6, 'Divisions', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(7, 'Departments', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(8, 'Sections', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(9, 'Positions', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(10, 'Notifications', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(11, 'Resort Modules', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(12, 'Resort Pages Module', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(13, 'Public Holidays', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(14, 'EWT Brackets', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(15, 'Support Categories', '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(16, 'Support', '2025-12-23 14:08:26', '2025-12-23 14:08:26');

-- --------------------------------------------------------

--
-- Table structure for table `admin_module_permissions`
--

DROP TABLE IF EXISTS `admin_module_permissions`;
CREATE TABLE IF NOT EXISTS `admin_module_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_module_permissions`
--

INSERT INTO `admin_module_permissions` (`id`, `module_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(2, 1, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(3, 1, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(4, 1, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(5, 2, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(6, 3, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(7, 3, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(8, 3, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(9, 3, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(10, 4, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(11, 4, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(12, 4, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(13, 4, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(14, 5, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(15, 5, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(16, 5, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(17, 5, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(18, 6, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(19, 6, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(20, 6, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(21, 6, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(22, 7, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(23, 7, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(24, 7, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(25, 7, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(26, 8, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(27, 8, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(28, 8, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(29, 8, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(30, 9, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(31, 9, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(32, 9, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(33, 9, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(34, 10, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(35, 10, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(36, 10, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(37, 10, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(38, 11, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(39, 11, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(40, 11, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(41, 11, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(42, 12, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(43, 12, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(44, 12, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(45, 12, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(46, 13, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(47, 13, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(48, 13, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(49, 13, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(50, 14, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(51, 14, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(52, 14, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(53, 14, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(54, 15, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(55, 15, 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(56, 15, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(57, 15, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(58, 16, 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(59, 16, 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(60, 16, 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26');

-- --------------------------------------------------------

--
-- Table structure for table `admin_role_module_permissions`
--

DROP TABLE IF EXISTS `admin_role_module_permissions`;
CREATE TABLE IF NOT EXISTS `admin_role_module_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint UNSIGNED NOT NULL,
  `module_permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_role_module_permissions`
--

INSERT INTO `admin_role_module_permissions` (`id`, `role_id`, `module_permission_id`, `created_at`, `updated_at`) VALUES
(1, 10, 1, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(2, 10, 2, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(3, 10, 3, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(4, 10, 4, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(5, 10, 22, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(6, 10, 23, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(7, 10, 24, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(8, 10, 25, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(9, 10, 18, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(10, 10, 19, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(11, 10, 20, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(12, 10, 21, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(13, 10, 10, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(14, 10, 11, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(15, 10, 12, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(16, 10, 13, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(17, 10, 50, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(18, 10, 51, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(19, 10, 52, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(20, 10, 53, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(21, 10, 34, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(22, 10, 35, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(23, 10, 36, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(24, 10, 37, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(25, 10, 30, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(26, 10, 31, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(27, 10, 32, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(28, 10, 33, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(29, 10, 46, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(30, 10, 47, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(31, 10, 48, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(32, 10, 49, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(33, 10, 38, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(34, 10, 39, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(35, 10, 40, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(36, 10, 41, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(37, 10, 42, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(38, 10, 43, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(39, 10, 44, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(40, 10, 45, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(41, 10, 14, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(42, 10, 15, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(43, 10, 16, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(44, 10, 17, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(45, 10, 6, '2025-12-23 14:08:18', '2025-12-23 14:08:18'),
(46, 10, 7, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(47, 10, 8, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(48, 10, 9, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(49, 10, 26, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(50, 10, 27, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(51, 10, 28, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(52, 10, 29, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(53, 10, 5, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(54, 10, 58, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(55, 10, 59, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(56, 10, 60, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(57, 10, 54, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(58, 10, 55, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(59, 10, 56, '2025-12-23 14:08:19', '2025-12-23 14:08:19'),
(60, 10, 57, '2025-12-23 14:08:19', '2025-12-23 14:08:19');

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

DROP TABLE IF EXISTS `announcement`;
CREATE TABLE IF NOT EXISTS `announcement` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `title` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_date` datetime DEFAULT NULL,
  `status` enum('Draft','Published','Scheduled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `announcement_resort_id_foreign` (`resort_id`),
  KEY `announcement_title_foreign` (`title`),
  KEY `announcement_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`id`, `resort_id`, `title`, `employee_id`, `message`, `published_date`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`, `archived`) VALUES
(61, 26, 31, 189, 'Congrats Rani for becoming Employee of the Month. We wish you continued the success', '2025-11-27 21:49:33', 'Published', 259, 259, '2025-11-27 21:49:33', '2025-11-27 21:49:33', 0),
(62, 26, 31, 189, 'Congrats Rani for becoming Employee of the Month. We wish you continued the success', '2025-11-27 21:49:36', 'Published', 259, 259, '2025-11-27 21:49:36', '2025-11-27 21:49:36', 0),
(63, 26, 31, 189, 'Congrats Rani for becoming Employee of the Month. We wish you continued the success', NULL, 'Draft', 259, 259, '2025-11-27 21:49:39', '2025-11-27 21:49:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_category`
--

DROP TABLE IF EXISTS `announcement_category`;
CREATE TABLE IF NOT EXISTS `announcement_category` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcement_category_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement_category`
--

INSERT INTO `announcement_category` (`id`, `resort_id`, `name`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(31, 26, 'Employee of the Month', 259, 259, '2025-11-27 21:45:00', '2025-11-27 21:45:00'),
(32, 26, 'Supervisor of the Month', 259, 259, '2025-11-27 21:45:44', '2025-11-27 21:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_notification`
--

DROP TABLE IF EXISTS `announcement_notification`;
CREATE TABLE IF NOT EXISTS `announcement_notification` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `announcement_id` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `status` enum('unread','read','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcement_notification_resort_id_foreign` (`resort_id`),
  KEY `announcement_notification_announcement_id_foreign` (`announcement_id`),
  KEY `announcement_notification_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_form_data`
--

DROP TABLE IF EXISTS `applicant_form_data`;
CREATE TABLE IF NOT EXISTS `applicant_form_data` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int NOT NULL,
  `Parent_v_id` int NOT NULL,
  `Application_date` date DEFAULT NULL,
  `passport_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_img` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_expiry_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curriculum_vitae` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_length_photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'male',
  `dob` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` int DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('married','unmarried') COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_children` int DEFAULT NULL,
  `address_line_one` text COLLATE utf8mb4_unicode_ci,
  `address_line_two` text COLLATE utf8mb4_unicode_ci,
  `country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pin_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Joining_availability` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `select_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terms_conditions` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_retention_month` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_retention_year` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `TimeZone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NotiesPeriod` int NOT NULL,
  `employment_status` enum('Available','Working') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SalaryExpectation` double(8,2) DEFAULT NULL,
  `Total_Experiance` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `AIRanking` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Scoring` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Applicant_Source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_form_job_assessment`
--

DROP TABLE IF EXISTS `applicant_form_job_assessment`;
CREATE TABLE IF NOT EXISTS `applicant_form_job_assessment` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_form_id` bigint UNSIGNED NOT NULL,
  `question_id` bigint UNSIGNED DEFAULT NULL,
  `question_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `multiple_responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `video_language_test` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_path` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicant_form_job_assessment_applicant_form_id_foreign` (`applicant_form_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_inter_view_details`
--

DROP TABLE IF EXISTS `applicant_inter_view_details`;
CREATE TABLE IF NOT EXISTS `applicant_inter_view_details` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Applicant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `ApplicantStatus_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `InterViewDate` date NOT NULL,
  `ApplicantInterviewtime` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `ResortInterviewtime` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `Approved_By` int DEFAULT NULL,
  `Status` enum('Active','Slot Booked','Slot Not Booked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Slot Not Booked',
  `MeetingLink` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `EmailTemplateId` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicant_inter_view_details_applicant_id_foreign` (`Applicant_id`),
  KEY `applicant_inter_view_details_resort_id_foreign` (`resort_id`),
  KEY `applicant_inter_view_details_applicantstatus_id_foreign` (`ApplicantStatus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_languages`
--

DROP TABLE IF EXISTS `applicant_languages`;
CREATE TABLE IF NOT EXISTS `applicant_languages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_form_id` bigint UNSIGNED NOT NULL,
  `language` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicant_languages_applicant_form_id_foreign` (`applicant_form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_wise_statuses`
--

DROP TABLE IF EXISTS `applicant_wise_statuses`;
CREATE TABLE IF NOT EXISTS `applicant_wise_statuses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Applicant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `As_ApprovedBy` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Sortlisted By Wisdom AI','Rejected By Wisdom AI','Sortlisted','Round','Rejected','Selected','Complete','Pending') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Comments` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicant_wise_statuses_applicant_id_foreign` (`Applicant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_links`
--

DROP TABLE IF EXISTS `application_links`;
CREATE TABLE IF NOT EXISTS `application_links` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `ta_child_id` bigint UNSIGNED DEFAULT NULL,
  `link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_Expiry_date` date DEFAULT NULL,
  `Old_ExpiryDate` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_links_resort_id_foreign` (`Resort_id`),
  KEY `application_links_ta_child_id_foreign` (`ta_child_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assing_accommodations`
--

DROP TABLE IF EXISTS `assing_accommodations`;
CREATE TABLE IF NOT EXISTS `assing_accommodations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `available_a_id` bigint UNSIGNED NOT NULL,
  `emp_id` int NOT NULL,
  `effected_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `BedNo` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `assing_accommodations_resort_id_foreign` (`resort_id`),
  KEY `assing_accommodations_available_a_id_foreign` (`available_a_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_parameters`
--

DROP TABLE IF EXISTS `attendance_parameters`;
CREATE TABLE IF NOT EXISTS `attendance_parameters` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `threshold_percentage` int DEFAULT NULL,
  `auto_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `evaluation_reminder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_parameters_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_parameters`
--

INSERT INTO `attendance_parameters` (`id`, `resort_id`, `threshold_percentage`, `auto_notifications`, `evaluation_reminder`, `created_at`, `updated_at`) VALUES
(2, 25, 35, 1, 'after_3_days', '2025-11-13 17:28:15', '2025-11-13 17:28:15'),
(3, 26, 35, 1, 'after_3_days', '2025-11-13 17:28:52', '2025-12-13 17:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `file_id` bigint UNSIGNED NOT NULL,
  `TypeofAction` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_resort_id_foreign` (`resort_id`),
  KEY `audit_logs_file_id_foreign` (`file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `available_accommodation_inv_items`
--

DROP TABLE IF EXISTS `available_accommodation_inv_items`;
CREATE TABLE IF NOT EXISTS `available_accommodation_inv_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Available_Acc_id` bigint UNSIGNED NOT NULL,
  `Item_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `available_accommodation_inv_items_item_id_foreign` (`Item_id`),
  KEY `available_accommodation_inv_items_available_acc_id_foreign` (`Available_Acc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `available_accommodation_models`
--

DROP TABLE IF EXISTS `available_accommodation_models`;
CREATE TABLE IF NOT EXISTS `available_accommodation_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `BuildingName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Floor` int DEFAULT NULL,
  `RoomNo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Accommodation_type_id` bigint UNSIGNED NOT NULL,
  `RoomType` int DEFAULT NULL,
  `BedNo` int DEFAULT NULL,
  `blockFor` enum('Male','Female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Capacity` int DEFAULT NULL,
  `CleaningSchedule` enum('Daily','Weekly','By Weekly','Monthly') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `RoomStatus` enum('Available','Occupied','Under Maintenance','Maintenance Required','Under Maintenance','Not in Operation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Available',
  `Occupancytheresold` int DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `modified_by` bigint UNSIGNED DEFAULT NULL,
  `Colour` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `available_accommodation_models_resort_id_foreign` (`resort_id`),
  KEY `available_accommodation_models_accommodation_type_id_foreign` (`Accommodation_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `break_attendaces`
--

DROP TABLE IF EXISTS `break_attendaces`;
CREATE TABLE IF NOT EXISTS `break_attendaces` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_attd_id` bigint UNSIGNED NOT NULL,
  `Break_InTime` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Break_OutTime` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Total_Break_Time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `InTime_Location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `OutTime_Location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `break_attendaces_parent_attd_id_foreign` (`Parent_attd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_statuses`
--

DROP TABLE IF EXISTS `budget_statuses`;
CREATE TABLE IF NOT EXISTS `budget_statuses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Department_id` int NOT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Budget_id` bigint UNSIGNED NOT NULL,
  `status` enum('Genrated','Approved','Rejected','Pending','Completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Genrated',
  `comments` text COLLATE utf8mb4_unicode_ci,
  `OtherComments` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budget_statuses_resort_id_foreign` (`resort_id`),
  KEY `budget_statuses_message_id_foreign` (`message_id`),
  KEY `budget_statuses_budget_id_foreign` (`Budget_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_statuses`
--

INSERT INTO `budget_statuses` (`id`, `resort_id`, `Department_id`, `message_id`, `Budget_id`, `status`, `comments`, `OtherComments`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(59, 25, 75, 'SR413172861', 54, 'Genrated', 'Respond to HR', '', 238, 238, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(60, 25, 77, 'SR413172861', 55, 'Genrated', 'Respond to HR', '', 239, 239, '2025-11-09 06:12:26', '2025-11-09 06:12:26'),
(61, 26, 79, 'DR620587438', 56, 'Genrated', 'Respond to HR', '', 253, 253, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(62, 26, 80, 'DR620587438', 57, 'Genrated', 'Respond to HR', '', 248, 248, '2025-11-15 12:45:44', '2025-11-15 12:45:44'),
(63, 26, 78, 'DR620587438', 58, 'Genrated', 'Respond to HR', '', 250, 250, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(64, 26, 80, '5', 57, 'Rejected', 'Reviewed by HR and Sent to Finance', 'Due to low budget, we will need to remove the Commis position', 259, 259, '2025-12-05 13:19:41', '2025-12-05 13:19:41'),
(65, 26, 80, 'DR620587438', 57, 'Genrated', 'Respond to HR', '', 248, 248, '2025-12-05 13:28:15', '2025-12-05 13:28:15');

-- --------------------------------------------------------

--
-- Table structure for table `building_models`
--

DROP TABLE IF EXISTS `building_models`;
CREATE TABLE IF NOT EXISTS `building_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `BuildingName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `building_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `building_models`
--

INSERT INTO `building_models` (`id`, `resort_id`, `BuildingName`, `created_at`, `updated_at`) VALUES
(18, 26, 'Building A', '2025-11-13 10:16:02', '2025-11-13 10:16:02'),
(19, 26, 'Building B', '2025-11-13 10:16:08', '2025-11-13 10:16:08');

-- --------------------------------------------------------

--
-- Table structure for table `bulidng_and_floor_and_rooms`
--

DROP TABLE IF EXISTS `bulidng_and_floor_and_rooms`;
CREATE TABLE IF NOT EXISTS `bulidng_and_floor_and_rooms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `building_id` bigint UNSIGNED NOT NULL,
  `Floor` int NOT NULL,
  `Room` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bulidng_and_floor_and_rooms_resort_id_foreign` (`resort_id`),
  KEY `bulidng_and_floor_and_rooms_building_id_foreign` (`building_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bulidng_and_floor_and_rooms`
--

INSERT INTO `bulidng_and_floor_and_rooms` (`id`, `resort_id`, `building_id`, `Floor`, `Room`, `created_at`, `updated_at`) VALUES
(22, 26, 18, 1, 101, '2025-11-13 10:17:27', '2025-11-13 10:17:27'),
(23, 26, 18, 1, 102, '2025-11-13 10:17:43', '2025-11-13 10:17:43'),
(24, 26, 19, 2, 201, '2025-11-13 10:19:32', '2025-11-13 10:19:32'),
(25, 26, 19, 2, 202, '2025-11-13 10:20:28', '2025-11-13 10:20:28');

-- --------------------------------------------------------

--
-- Table structure for table `bulidng_and_foolr_and_rooms`
--

DROP TABLE IF EXISTS `bulidng_and_foolr_and_rooms`;
CREATE TABLE IF NOT EXISTS `bulidng_and_foolr_and_rooms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_hours`
--

DROP TABLE IF EXISTS `business_hours`;
CREATE TABLE IF NOT EXISTS `business_hours` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `day_of_week` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_hours_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_hours`
--

INSERT INTO `business_hours` (`id`, `resort_id`, `day_of_week`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(36, 26, 'Monday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20'),
(37, 26, 'Tuesday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20'),
(38, 26, 'Wednesday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20'),
(39, 26, 'Thursday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20'),
(40, 26, 'Friday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20'),
(41, 26, 'Saturday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20'),
(42, 26, 'Sunday', '09:00:00', '18:00:00', '2025-11-11 18:41:20', '2025-11-11 18:41:20');

-- --------------------------------------------------------

--
-- Table structure for table `chat_group`
--

DROP TABLE IF EXISTS `chat_group`;
CREATE TABLE IF NOT EXISTS `chat_group` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_group_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_group_member`
--

DROP TABLE IF EXISTS `chat_group_member`;
CREATE TABLE IF NOT EXISTS `chat_group_member` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `chat_group_id` bigint UNSIGNED DEFAULT NULL COMMENT 'chat_group_id',
  `user_id` int UNSIGNED DEFAULT NULL COMMENT 'resort_admin_id',
  `role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_group_member_chat_group_id_foreign` (`chat_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_message_read`
--

DROP TABLE IF EXISTS `chat_message_read`;
CREATE TABLE IF NOT EXISTS `chat_message_read` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `read_at` datetime NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_approved_maintanace_requests`
--

DROP TABLE IF EXISTS `child_approved_maintanace_requests`;
CREATE TABLE IF NOT EXISTS `child_approved_maintanace_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `child_maintanance_request_id` bigint UNSIGNED NOT NULL,
  `maintanance_request_id` bigint UNSIGNED NOT NULL,
  `ApprovedBy` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rank` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `Status` enum('pending','On-Hold','Open','Assinged','In-Progress','Resolvedawaiting','Closed','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Resolvedawaiting',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_resort_id` (`resort_id`),
  KEY `fk_child_request_id` (`child_maintanance_request_id`),
  KEY `fk_main_request_id` (`maintanance_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_attendaces`
--

DROP TABLE IF EXISTS `child_attendaces`;
CREATE TABLE IF NOT EXISTS `child_attendaces` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_attd_id` bigint UNSIGNED NOT NULL,
  `InTime_out` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `OutTime_out` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `InTime_Location` text COLLATE utf8mb4_unicode_ci,
  `OutTime_Location` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `child_attendaces_parent_attd_id_foreign` (`Parent_attd_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `child_attendaces`
--

INSERT INTO `child_attendaces` (`id`, `Parent_attd_id`, `InTime_out`, `OutTime_out`, `created_at`, `updated_at`, `InTime_Location`, `OutTime_Location`) VALUES
(17, 2149, '4:00', '12:00', '2026-01-01 08:29:01', '2026-01-01 08:29:58', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(18, 2150, '13:00', '15:00', '2026-01-01 08:30:15', '2026-01-01 08:30:40', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(19, 2151, '01:00', '03:00', '2026-01-01 08:44:38', '2026-01-01 08:44:59', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(20, 2152, '04:00', '12:00', '2026-01-01 08:45:25', '2026-01-01 08:45:33', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(21, 2153, '03:00', '13:00', '2026-01-01 08:46:50', '2026-01-01 08:47:14', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(22, 2154, '02:00', '12:00', '2026-01-01 08:48:16', '2026-01-01 08:48:29', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(23, 2155, '02:00', '10:00', '2026-01-01 08:49:36', '2026-01-01 08:49:56', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(24, 2156, '03:00', '00:00', '2026-01-01 09:40:29', '2026-01-01 09:40:29', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', NULL),
(25, 2157, '03:00', '12:00', '2026-01-01 09:56:08', '2026-01-01 10:02:15', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(26, 2158, '03:00', '12:00', '2026-01-01 13:31:58', '2026-01-01 13:32:28', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(27, 2159, '13:00', '14:00', '2026-01-01 13:33:27', '2026-01-01 13:33:40', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(33, 2165, '04:00', '12:00', '2026-01-05 12:35:00', '2026-01-05 12:35:19', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(34, 2166, '14:00', '16:00', '2026-01-05 12:35:48', '2026-01-05 12:35:55', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(35, 2167, '04:00', '12:00', '2026-01-05 12:39:57', '2026-01-05 12:40:12', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12'),
(36, 2168, '14:00', '16:00', '2026-01-05 12:40:26', '2026-01-05 12:40:39', '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12');

-- --------------------------------------------------------

--
-- Table structure for table `child_events`
--

DROP TABLE IF EXISTS `child_events`;
CREATE TABLE IF NOT EXISTS `child_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `child_events_resort_id_foreign` (`resort_id`),
  KEY `child_events_event_id_foreign` (`event_id`),
  KEY `child_events_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_file_management`
--

DROP TABLE IF EXISTS `child_file_management`;
CREATE TABLE IF NOT EXISTS `child_file_management` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Parent_File_ID` bigint UNSIGNED NOT NULL,
  `File_Name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NewFileName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_secure` tinyint(1) NOT NULL DEFAULT '0',
  `File_Type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Size` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Extension` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Upload_By` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Upload_Date` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Upload_Time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `File_Upload_IP` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `child_file_management_resort_id_foreign` (`resort_id`),
  KEY `child_file_management_parent_file_id_foreign` (`Parent_File_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_housekeeping_schedules`
--

DROP TABLE IF EXISTS `child_housekeeping_schedules`;
CREATE TABLE IF NOT EXISTS `child_housekeeping_schedules` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `housekeeping_id` bigint UNSIGNED NOT NULL,
  `ApprovedBy` int DEFAULT NULL,
  `rank` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('Pending','Open','On-Hold','Assigned','In-Progress','Complete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `child_housekeeping_schedules_resort_id_foreign` (`resort_id`),
  KEY `child_housekeeping_schedules_housekeeping_id_foreign` (`housekeeping_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_maintanance_requests`
--

DROP TABLE IF EXISTS `child_maintanance_requests`;
CREATE TABLE IF NOT EXISTS `child_maintanance_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `maintanance_request_id` bigint UNSIGNED NOT NULL,
  `ApprovedBy` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rank` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `Status` enum('pending','On-Hold','Open','Assinged','In-Progress','Resolvedawaiting','Closed','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `child_maintanance_requests_resort_id_foreign` (`resort_id`),
  KEY `child_maintanance_requests_maintanance_request_id_foreign` (`maintanance_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=369 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_sos_history`
--

DROP TABLE IF EXISTS `child_sos_history`;
CREATE TABLE IF NOT EXISTS `child_sos_history` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sos_history_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `child_sos_history_sos_history_id_foreign` (`sos_history_id`),
  KEY `child_sos_history_team_id_foreign` (`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=300 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `child_sos_history_status`
--

DROP TABLE IF EXISTS `child_sos_history_status`;
CREATE TABLE IF NOT EXISTS `child_sos_history_status` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sos_history_id` bigint UNSIGNED NOT NULL,
  `sos_status` enum('sos_activation','manager_acknowledgement','team_notifications_sent','acknowledgements_received_from_team_members','chat_updates','situation_was_marked_as_under_control','sos_completed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `child_sos_history_status_sos_history_id_foreign` (`sos_history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
CREATE TABLE IF NOT EXISTS `cities` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_foreign` (`state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_appointment`
--

DROP TABLE IF EXISTS `clinic_appointment`;
CREATE TABLE IF NOT EXISTS `clinic_appointment` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `doctor_id` int UNSIGNED NOT NULL,
  `appointment_category_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Reschedule','Cancel','Treatment','Medical Certificate') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_by` bigint UNSIGNED NOT NULL,
  `modified_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_appointment_resort_id_foreign` (`resort_id`),
  KEY `clinic_appointment_employee_id_foreign` (`employee_id`),
  KEY `clinic_appointment_doctor_id_foreign` (`doctor_id`),
  KEY `clinic_appointment_appointment_category_id_foreign` (`appointment_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_appointment_attechements`
--

DROP TABLE IF EXISTS `clinic_appointment_attechements`;
CREATE TABLE IF NOT EXISTS `clinic_appointment_attechements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `appointment_id` bigint UNSIGNED NOT NULL,
  `attachment` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_appointment_attechements_resort_id_foreign` (`resort_id`),
  KEY `clinic_appointment_attechements_appointment_id_foreign` (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_appointment_categories`
--

DROP TABLE IF EXISTS `clinic_appointment_categories`;
CREATE TABLE IF NOT EXISTS `clinic_appointment_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `appointment_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `modified_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_appointment_categories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_medical_certificate`
--

DROP TABLE IF EXISTS `clinic_medical_certificate`;
CREATE TABLE IF NOT EXISTS `clinic_medical_certificate` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `appointment_id` bigint UNSIGNED DEFAULT NULL,
  `clinic_treatment_id` bigint UNSIGNED DEFAULT NULL,
  `leave_request_id` int UNSIGNED DEFAULT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `appointment_category_id` bigint UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `modified_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_medical_certificate_resort_id_foreign` (`resort_id`),
  KEY `clinic_medical_certificate_appointment_id_foreign` (`appointment_id`),
  KEY `clinic_medical_certificate_clinic_treatment_id_foreign` (`clinic_treatment_id`),
  KEY `clinic_medical_certificate_leave_request_id_foreign` (`leave_request_id`),
  KEY `clinic_medical_certificate_employee_id_foreign` (`employee_id`),
  KEY `clinic_medical_certificate_appointment_category_id_foreign` (`appointment_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_treatment`
--

DROP TABLE IF EXISTS `clinic_treatment`;
CREATE TABLE IF NOT EXISTS `clinic_treatment` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `appointment_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `appointment_category_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `treatment_provided` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_notes` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_consultation` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('High','Medium','Low') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `created_by` bigint UNSIGNED NOT NULL,
  `modified_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_treatment_resort_id_foreign` (`resort_id`),
  KEY `clinic_treatment_appointment_id_foreign` (`appointment_id`),
  KEY `clinic_treatment_employee_id_foreign` (`employee_id`),
  KEY `clinic_treatment_appointment_category_id_foreign` (`appointment_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_treatment_attachments`
--

DROP TABLE IF EXISTS `clinic_treatment_attachments`;
CREATE TABLE IF NOT EXISTS `clinic_treatment_attachments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `clinic_treatment_id` bigint UNSIGNED NOT NULL,
  `attachment` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `code_of_counducts`
--

DROP TABLE IF EXISTS `code_of_counducts`;
CREATE TABLE IF NOT EXISTS `code_of_counducts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Deciplinery_cat_id` int DEFAULT NULL,
  `Offenses_id` int DEFAULT NULL,
  `Action_id` int DEFAULT NULL,
  `Severity_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `code_of_counducts_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `color_themes`
--

DROP TABLE IF EXISTS `color_themes`;
CREATE TABLE IF NOT EXISTS `color_themes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `color_themes_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `color_themes`
--

INSERT INTO `color_themes` (`id`, `resort_id`, `name`, `color`, `created_at`, `updated_at`) VALUES
(14, 26, 'A', '#ff2600', '2025-11-17 13:40:36', '2025-11-17 13:40:36'),
(15, 26, 'P', '#00f900', '2025-11-17 13:40:36', '2025-11-17 13:40:36'),
(16, 26, 'Day Off', '#000000', '2025-11-17 13:40:36', '2025-11-17 13:40:36');

-- --------------------------------------------------------

--
-- Table structure for table `compliances`
--

DROP TABLE IF EXISTS `compliances`;
CREATE TABLE IF NOT EXISTS `compliances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED DEFAULT NULL,
  `employee_id` int UNSIGNED DEFAULT NULL,
  `module_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compliance_breached_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reported_on` datetime DEFAULT NULL,
  `status` enum('Breached','Resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Breached',
  `Dismissal_status` enum('Pending','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `assigned_to` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliances_resort_id_foreign` (`resort_id`),
  KEY `compliances_employee_id_foreign` (`employee_id`),
  KEY `compliances_assigned_to_foreign` (`assigned_to`)
) ENGINE=InnoDB AUTO_INCREMENT=12996 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `compliances`
--

INSERT INTO `compliances` (`id`, `resort_id`, `employee_id`, `module_name`, `compliance_breached_name`, `description`, `reported_on`, `status`, `Dismissal_status`, `assigned_to`, `created_at`, `updated_at`, `deleted_at`) VALUES
(12804, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-13 18:13:57', 'Breached', 'Pending', NULL, '2025-11-13 18:13:57', '2025-11-13 18:13:57', NULL),
(12805, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-13 18:14:20', 'Breached', 'Pending', NULL, '2025-11-13 18:14:20', '2025-11-13 18:14:20', NULL),
(12806, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-11-17 15:44:42', 'Breached', 'Pending', NULL, '2025-11-17 15:44:42', '2025-11-17 15:44:42', NULL),
(12807, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-11-17 16:01:37', 'Breached', 'Pending', NULL, '2025-11-17 16:01:37', '2025-11-17 16:01:37', NULL),
(12808, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-11-17 16:03:04', 'Breached', 'Pending', NULL, '2025-11-17 16:03:04', '2025-11-17 16:03:04', NULL),
(12809, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-11-17 16:03:13', 'Breached', 'Pending', NULL, '2025-11-17 16:03:13', '2025-11-17 16:03:13', NULL),
(12810, 26, NULL, 'Time and Attendance', 'Over Time Not Eligibile', ' (DR-20 - ) is not eligible for overtime.', '2025-11-17 17:44:56', '', 'Pending', NULL, '2025-11-17 17:44:56', '2025-11-17 17:44:56', NULL),
(12811, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-17 18:32:20', 'Breached', 'Pending', NULL, '2025-11-17 18:32:20', '2025-11-17 18:32:20', NULL),
(12812, 26, NULL, 'People Management', 'Minimum Wage', 'Employee James Wilson has a basic salary below the minimum wage.', '2025-11-18 15:38:27', 'Breached', 'Pending', NULL, '2025-11-18 15:38:27', '2025-11-18 15:38:27', NULL),
(12813, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Anastasia Volkova has a basic salary below the minimum wage.', '2025-11-18 22:33:55', 'Breached', 'Pending', NULL, '2025-11-18 22:33:55', '2025-11-18 22:33:55', NULL),
(12814, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-11-18 22:34:09', 'Breached', 'Pending', NULL, '2025-11-18 22:34:09', '2025-11-18 22:34:09', NULL),
(12815, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-11-18 22:34:36', 'Breached', 'Pending', NULL, '2025-11-18 22:34:36', '2025-11-18 22:34:36', NULL),
(12816, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-11-19 10:27:15', 'Breached', 'Pending', NULL, '2025-11-19 10:27:15', '2025-11-19 10:27:15', NULL),
(12817, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:21:21', 'Breached', 'Pending', NULL, '2025-11-19 11:21:21', '2025-11-19 11:21:21', NULL),
(12818, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:22:52', 'Breached', 'Pending', NULL, '2025-11-19 11:22:52', '2025-11-19 11:22:52', NULL),
(12819, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:24:35', 'Breached', 'Pending', NULL, '2025-11-19 11:24:35', '2025-11-19 11:24:35', NULL),
(12820, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:26:44', 'Breached', 'Pending', NULL, '2025-11-19 11:26:44', '2025-11-19 11:26:44', NULL),
(12821, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:35:38', 'Breached', 'Pending', NULL, '2025-11-19 11:35:38', '2025-11-19 11:35:38', NULL),
(12822, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:36:21', 'Breached', 'Pending', NULL, '2025-11-19 11:36:21', '2025-11-19 11:36:21', NULL),
(12823, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:41:29', 'Breached', 'Pending', NULL, '2025-11-19 11:41:29', '2025-11-19 11:41:29', NULL),
(12824, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:50:13', 'Breached', 'Pending', NULL, '2025-11-19 11:50:13', '2025-11-19 11:50:13', NULL),
(12825, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:51:04', 'Breached', 'Pending', NULL, '2025-11-19 11:51:04', '2025-11-19 11:51:04', NULL),
(12826, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 11:52:37', 'Breached', 'Pending', NULL, '2025-11-19 11:52:37', '2025-11-19 11:52:37', NULL),
(12827, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 12:34:05', 'Breached', 'Pending', NULL, '2025-11-19 12:34:05', '2025-11-19 12:34:05', NULL),
(12828, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 12:34:53', 'Breached', 'Pending', NULL, '2025-11-19 12:34:53', '2025-11-19 12:34:53', NULL),
(12829, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 13:20:05', 'Breached', 'Pending', NULL, '2025-11-19 13:20:05', '2025-11-19 13:20:05', NULL),
(12830, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 13:20:22', 'Breached', 'Pending', NULL, '2025-11-19 13:20:22', '2025-11-19 13:20:22', NULL),
(12831, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 13:20:40', 'Breached', 'Pending', NULL, '2025-11-19 13:20:40', '2025-11-19 13:20:40', NULL),
(12832, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 13:21:36', 'Breached', 'Pending', NULL, '2025-11-19 13:21:36', '2025-11-19 13:21:36', NULL),
(12833, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 14:37:14', 'Breached', 'Pending', NULL, '2025-11-19 14:37:14', '2025-11-19 14:37:14', NULL),
(12834, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 14:37:34', 'Breached', 'Pending', NULL, '2025-11-19 14:37:34', '2025-11-19 14:37:34', NULL),
(12835, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Aminath Abdul has a basic salary below the minimum wage.', '2025-11-19 14:37:38', 'Breached', 'Pending', NULL, '2025-11-19 14:37:38', '2025-11-19 14:37:38', NULL),
(12836, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 14:51:01', 'Breached', 'Pending', NULL, '2025-11-21 14:51:01', '2025-11-21 14:51:01', NULL),
(12837, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 14:56:16', 'Breached', 'Pending', NULL, '2025-11-21 14:56:16', '2025-11-21 14:56:16', NULL),
(12838, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 14:57:24', 'Breached', 'Pending', NULL, '2025-11-21 14:57:24', '2025-11-21 14:57:24', NULL),
(12839, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 14:58:13', 'Breached', 'Pending', NULL, '2025-11-21 14:58:13', '2025-11-21 14:58:13', NULL),
(12840, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:03:23', 'Breached', 'Pending', NULL, '2025-11-21 15:03:23', '2025-11-21 15:03:23', NULL),
(12841, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:04:09', 'Breached', 'Pending', NULL, '2025-11-21 15:04:09', '2025-11-21 15:04:09', NULL),
(12842, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:04:54', 'Breached', 'Pending', NULL, '2025-11-21 15:04:54', '2025-11-21 15:04:54', NULL),
(12843, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:07:17', 'Breached', 'Pending', NULL, '2025-11-21 15:07:17', '2025-11-21 15:07:17', NULL),
(12844, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:09:29', 'Breached', 'Pending', NULL, '2025-11-21 15:09:29', '2025-11-21 15:09:29', NULL),
(12845, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:15:21', 'Breached', 'Pending', NULL, '2025-11-21 15:15:21', '2025-11-21 15:15:21', NULL),
(12846, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:19:42', 'Breached', 'Pending', NULL, '2025-11-21 15:19:42', '2025-11-21 15:19:42', NULL),
(12847, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 15:26:45', 'Breached', 'Pending', NULL, '2025-11-21 15:26:45', '2025-11-21 15:26:45', NULL),
(12848, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:28:14', 'Breached', 'Pending', NULL, '2025-11-21 15:28:14', '2025-11-21 15:28:14', NULL),
(12849, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:31:10', 'Breached', 'Pending', NULL, '2025-11-21 15:31:10', '2025-11-21 15:31:10', NULL),
(12850, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:40:22', 'Breached', 'Pending', NULL, '2025-11-21 15:40:22', '2025-11-21 15:40:22', NULL),
(12851, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:41:50', 'Breached', 'Pending', NULL, '2025-11-21 15:41:50', '2025-11-21 15:41:50', NULL),
(12852, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:41:53', 'Breached', 'Pending', NULL, '2025-11-21 15:41:53', '2025-11-21 15:41:53', NULL),
(12853, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:41:57', 'Breached', 'Pending', NULL, '2025-11-21 15:41:57', '2025-11-21 15:41:57', NULL),
(12854, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:41:58', 'Breached', 'Pending', NULL, '2025-11-21 15:41:58', '2025-11-21 15:41:58', NULL),
(12855, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:42:34', 'Breached', 'Pending', NULL, '2025-11-21 15:42:34', '2025-11-21 15:42:34', NULL),
(12856, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:45:52', 'Breached', 'Pending', NULL, '2025-11-21 15:45:52', '2025-11-21 15:45:52', NULL),
(12857, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:46:59', 'Breached', 'Pending', NULL, '2025-11-21 15:46:59', '2025-11-21 15:46:59', NULL),
(12858, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:47:04', 'Breached', 'Pending', NULL, '2025-11-21 15:47:04', '2025-11-21 15:47:04', NULL),
(12859, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:47:25', 'Breached', 'Pending', NULL, '2025-11-21 15:47:25', '2025-11-21 15:47:25', NULL),
(12860, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Priya Sharma has a basic salary below the minimum wage.', '2025-11-21 15:47:36', 'Breached', 'Pending', NULL, '2025-11-21 15:47:36', '2025-11-21 15:47:36', NULL),
(12861, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 16:31:50', 'Breached', 'Pending', NULL, '2025-11-21 16:31:50', '2025-11-21 16:31:50', NULL),
(12862, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 17:05:51', 'Breached', 'Pending', NULL, '2025-11-21 17:05:51', '2025-11-21 17:05:51', NULL),
(12863, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 17:07:34', 'Breached', 'Pending', NULL, '2025-11-21 17:07:34', '2025-11-21 17:07:34', NULL),
(12864, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 17:15:39', 'Breached', 'Pending', NULL, '2025-11-21 17:15:39', '2025-11-21 17:15:39', NULL),
(12865, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 17:45:30', 'Breached', 'Pending', NULL, '2025-11-21 17:45:30', '2025-11-21 17:45:30', NULL),
(12866, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 20:25:45', 'Breached', 'Pending', NULL, '2025-11-21 20:25:45', '2025-11-21 20:25:45', NULL),
(12867, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-21 20:25:53', 'Breached', 'Pending', NULL, '2025-11-21 20:25:53', '2025-11-21 20:25:53', NULL),
(12868, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 20:59:52', 'Breached', 'Pending', NULL, '2025-11-21 20:59:52', '2025-11-21 20:59:52', NULL),
(12869, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:01:28', 'Breached', 'Pending', NULL, '2025-11-21 21:01:28', '2025-11-21 21:01:28', NULL),
(12870, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:07:27', 'Breached', 'Pending', NULL, '2025-11-21 21:07:27', '2025-11-21 21:07:27', NULL),
(12871, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:09:07', 'Breached', 'Pending', NULL, '2025-11-21 21:09:07', '2025-11-21 21:09:07', NULL),
(12872, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:11:11', 'Breached', 'Pending', NULL, '2025-11-21 21:11:11', '2025-11-21 21:11:11', NULL),
(12873, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:12:11', 'Breached', 'Pending', NULL, '2025-11-21 21:12:11', '2025-11-21 21:12:11', NULL),
(12874, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:13:52', 'Breached', 'Pending', NULL, '2025-11-21 21:13:52', '2025-11-21 21:13:52', NULL),
(12875, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Olivia Davis has a basic salary below the minimum wage.', '2025-11-21 21:14:39', 'Breached', 'Pending', NULL, '2025-11-21 21:14:39', '2025-11-21 21:14:39', NULL),
(12876, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-23 10:40:45', 'Breached', 'Pending', NULL, '2025-11-23 10:40:45', '2025-11-23 10:40:45', NULL),
(12877, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-23 19:39:08', 'Breached', 'Pending', NULL, '2025-11-23 19:39:08', '2025-11-23 19:39:08', NULL),
(12878, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-23 19:39:29', 'Breached', 'Pending', NULL, '2025-11-23 19:39:29', '2025-11-23 19:39:29', NULL),
(12879, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-23 19:43:20', 'Breached', 'Pending', NULL, '2025-11-23 19:43:20', '2025-11-23 19:43:20', NULL),
(12880, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-27 16:50:27', 'Breached', 'Pending', NULL, '2025-11-27 16:50:27', '2025-11-27 16:50:27', NULL),
(12881, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-30 23:54:10', 'Breached', 'Pending', NULL, '2025-11-30 23:54:10', '2025-11-30 23:54:10', NULL),
(12882, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-30 23:54:16', 'Breached', 'Pending', NULL, '2025-11-30 23:54:16', '2025-11-30 23:54:16', NULL),
(12883, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-30 23:55:28', 'Breached', 'Pending', NULL, '2025-11-30 23:55:28', '2025-11-30 23:55:28', NULL),
(12884, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-30 23:55:53', 'Breached', 'Pending', NULL, '2025-11-30 23:55:53', '2025-11-30 23:55:53', NULL),
(12885, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-30 23:57:57', 'Breached', 'Pending', NULL, '2025-11-30 23:57:57', '2025-11-30 23:57:57', NULL),
(12886, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-11-30 23:58:06', 'Breached', 'Pending', NULL, '2025-11-30 23:58:06', '2025-11-30 23:58:06', NULL),
(12887, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-03 19:58:29', 'Breached', 'Pending', NULL, '2025-12-03 19:58:29', '2025-12-03 19:58:29', NULL),
(12888, 26, NULL, 'People Management', 'Minimum Wage', 'Employee James Wilson has a basic salary below the minimum wage.', '2025-12-03 19:59:09', 'Breached', 'Pending', NULL, '2025-12-03 19:59:09', '2025-12-03 19:59:09', NULL),
(12889, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:00:02', 'Breached', 'Pending', NULL, '2025-12-03 20:00:02', '2025-12-03 20:00:02', NULL),
(12890, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:02:05', 'Breached', 'Pending', NULL, '2025-12-03 20:02:05', '2025-12-03 20:02:05', NULL),
(12891, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:02:24', 'Breached', 'Pending', NULL, '2025-12-03 20:02:24', '2025-12-03 20:02:24', NULL),
(12892, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:04:59', 'Breached', 'Pending', NULL, '2025-12-03 20:04:59', '2025-12-03 20:04:59', NULL),
(12893, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:11:15', 'Breached', 'Pending', NULL, '2025-12-03 20:11:15', '2025-12-03 20:11:15', NULL),
(12894, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:20:02', 'Breached', 'Pending', NULL, '2025-12-03 20:20:02', '2025-12-03 20:20:02', NULL),
(12895, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-03 20:31:10', 'Breached', 'Pending', NULL, '2025-12-03 20:31:10', '2025-12-03 20:31:10', NULL),
(12896, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-04 14:42:11', 'Breached', 'Pending', NULL, '2025-12-04 14:42:11', '2025-12-04 14:42:11', NULL),
(12897, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-04 15:17:06', 'Breached', 'Pending', NULL, '2025-12-04 15:17:06', '2025-12-04 15:17:06', NULL),
(12898, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:17:56', 'Breached', 'Pending', NULL, '2025-12-04 15:17:56', '2025-12-04 15:17:56', NULL),
(12899, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:18:45', 'Breached', 'Pending', NULL, '2025-12-04 15:18:45', '2025-12-04 15:18:45', NULL),
(12900, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:19:27', 'Breached', 'Pending', NULL, '2025-12-04 15:19:27', '2025-12-04 15:19:27', NULL),
(12901, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:19:36', 'Breached', 'Pending', NULL, '2025-12-04 15:19:36', '2025-12-04 15:19:36', NULL),
(12902, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:19:47', 'Breached', 'Pending', NULL, '2025-12-04 15:19:47', '2025-12-04 15:19:47', NULL),
(12903, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:25:18', 'Breached', 'Pending', NULL, '2025-12-04 15:25:18', '2025-12-04 15:25:18', NULL),
(12904, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:26:35', 'Breached', 'Pending', NULL, '2025-12-04 15:26:35', '2025-12-04 15:26:35', NULL),
(12905, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:28:46', 'Breached', 'Pending', NULL, '2025-12-04 15:28:46', '2025-12-04 15:28:46', NULL),
(12906, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Laila Hassan has a basic salary below the minimum wage.', '2025-12-04 15:30:15', 'Breached', 'Pending', NULL, '2025-12-04 15:30:15', '2025-12-04 15:30:15', NULL),
(12907, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Dmitri Petrov has a basic salary below the minimum wage.', '2025-12-04 16:14:22', 'Breached', 'Pending', NULL, '2025-12-04 16:14:22', '2025-12-04 16:14:22', NULL),
(12908, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Dmitri Petrov has a basic salary below the minimum wage.', '2025-12-04 16:15:01', 'Breached', 'Pending', NULL, '2025-12-04 16:15:01', '2025-12-04 16:15:01', NULL),
(12909, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Dmitri Petrov has a basic salary below the minimum wage.', '2025-12-04 16:20:14', 'Breached', 'Pending', NULL, '2025-12-04 16:20:14', '2025-12-04 16:20:14', NULL),
(12910, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Dmitri Petrov has a basic salary below the minimum wage.', '2025-12-04 16:22:06', 'Breached', 'Pending', NULL, '2025-12-04 16:22:06', '2025-12-04 16:22:06', NULL),
(12911, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rajesh Patel has a basic salary below the minimum wage.', '2025-12-04 16:43:01', 'Breached', 'Pending', NULL, '2025-12-04 16:43:01', '2025-12-04 16:43:01', NULL),
(12912, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rajesh Patel has a basic salary below the minimum wage.', '2025-12-04 16:43:35', 'Breached', 'Pending', NULL, '2025-12-04 16:43:35', '2025-12-04 16:43:35', NULL),
(12913, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rajesh Patel has a basic salary below the minimum wage.', '2025-12-04 16:48:14', 'Breached', 'Pending', NULL, '2025-12-04 16:48:14', '2025-12-04 16:48:14', NULL),
(12914, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rajesh Patel has a basic salary below the minimum wage.', '2025-12-04 16:51:42', 'Breached', 'Pending', NULL, '2025-12-04 16:51:42', '2025-12-04 16:51:42', NULL),
(12915, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rajesh Patel has a basic salary below the minimum wage.', '2025-12-04 17:10:46', 'Breached', 'Pending', NULL, '2025-12-04 17:10:46', '2025-12-04 17:10:46', NULL),
(12916, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rajesh Patel has a basic salary below the minimum wage.', '2025-12-04 17:11:36', 'Breached', 'Pending', NULL, '2025-12-04 17:11:36', '2025-12-04 17:11:36', NULL),
(12917, 26, NULL, 'People Management', 'TIN Requirement', 'Rajesh Patel (DR-7 - Executive Chef) (RSWT: MVR 131070/month) not registered. Submit MIRA 118 form.', '2025-12-04 17:12:14', 'Breached', 'Pending', NULL, '2025-12-04 17:12:14', '2025-12-04 17:12:14', NULL),
(12918, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 17:16:32', 'Breached', 'Pending', NULL, '2025-12-04 17:16:32', '2025-12-04 17:16:32', NULL),
(12919, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 17:17:11', 'Breached', 'Pending', NULL, '2025-12-04 17:17:11', '2025-12-04 17:17:11', NULL),
(12920, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 17:20:35', 'Breached', 'Pending', NULL, '2025-12-04 17:20:35', '2025-12-04 17:20:35', NULL),
(12921, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 17:22:52', 'Breached', 'Pending', NULL, '2025-12-04 17:22:52', '2025-12-04 17:22:52', NULL),
(12922, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 19:29:08', 'Breached', 'Pending', NULL, '2025-12-04 19:29:08', '2025-12-04 19:29:08', NULL),
(12923, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 19:31:18', 'Breached', 'Pending', NULL, '2025-12-04 19:31:18', '2025-12-04 19:31:18', NULL),
(12924, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Fatima Naseer has a basic salary below the minimum wage.', '2025-12-04 19:31:45', 'Breached', 'Pending', NULL, '2025-12-04 19:31:45', '2025-12-04 19:31:45', NULL),
(12925, 26, NULL, 'People Management', 'TIN Requirement', 'Fatima Naseer (DR-10 - Human Resources Manager) (RSWT: MVR 34695/month) not registered. Submit MIRA 118 form.', '2025-12-04 19:32:51', 'Breached', 'Pending', NULL, '2025-12-04 19:32:51', '2025-12-04 19:32:51', NULL),
(12926, 26, NULL, 'People Management', 'TIN Requirement', 'Fatima Naseer (DR-10 - Human Resources Manager) (RSWT: MVR 34695/month) not registered. Submit MIRA 118 form.', '2025-12-04 19:33:08', 'Breached', 'Pending', NULL, '2025-12-04 19:33:08', '2025-12-04 19:33:08', NULL),
(12927, 26, NULL, 'People Management', 'TIN Requirement', 'Fatima Naseer (DR-10 - Human Resources Manager) (RSWT: MVR 34695/month) not registered. Submit MIRA 118 form.', '2025-12-04 19:33:18', 'Breached', 'Pending', NULL, '2025-12-04 19:33:18', '2025-12-04 19:33:18', NULL),
(12928, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 19:35:07', 'Breached', 'Pending', NULL, '2025-12-04 19:35:07', '2025-12-04 19:35:07', NULL),
(12929, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 19:44:10', 'Breached', 'Pending', NULL, '2025-12-04 19:44:10', '2025-12-04 19:44:10', NULL),
(12930, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 19:46:26', 'Breached', 'Pending', NULL, '2025-12-04 19:46:26', '2025-12-04 19:46:26', NULL),
(12931, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 19:48:35', 'Breached', 'Pending', NULL, '2025-12-04 19:48:35', '2025-12-04 19:48:35', NULL),
(12932, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 19:59:54', 'Breached', 'Pending', NULL, '2025-12-04 19:59:54', '2025-12-04 19:59:54', NULL),
(12933, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 20:00:22', 'Breached', 'Pending', NULL, '2025-12-04 20:00:22', '2025-12-04 20:00:22', NULL),
(12934, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Mohamed Shareef has a basic salary below the minimum wage.', '2025-12-04 20:00:28', 'Breached', 'Pending', NULL, '2025-12-04 20:00:28', '2025-12-04 20:00:28', NULL),
(12935, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Elena Morozova has a basic salary below the minimum wage.', '2025-12-04 20:51:45', 'Breached', 'Pending', NULL, '2025-12-04 20:51:45', '2025-12-04 20:51:45', NULL),
(12936, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Elena Morozova has a basic salary below the minimum wage.', '2025-12-04 20:52:17', 'Breached', 'Pending', NULL, '2025-12-04 20:52:17', '2025-12-04 20:52:17', NULL),
(12937, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Elena Morozova has a basic salary below the minimum wage.', '2025-12-04 20:58:19', 'Breached', 'Pending', NULL, '2025-12-04 20:58:19', '2025-12-04 20:58:19', NULL),
(12938, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Elena Morozova has a basic salary below the minimum wage.', '2025-12-04 21:02:21', 'Breached', 'Pending', NULL, '2025-12-04 21:02:21', '2025-12-04 21:02:21', NULL),
(12939, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Elena Morozova has a basic salary below the minimum wage.', '2025-12-04 21:03:14', 'Breached', 'Pending', NULL, '2025-12-04 21:03:14', '2025-12-04 21:03:14', NULL),
(12940, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Elena Morozova has a basic salary below the minimum wage.', '2025-12-04 21:03:50', 'Breached', 'Pending', NULL, '2025-12-04 21:03:50', '2025-12-04 21:03:50', NULL),
(12941, 26, NULL, 'People Management', 'TIN Requirement', 'Elena Morozova (DR-13 - Finance Manager) (RSWT: MVR 40092/month) not registered. Submit MIRA 118 form.', '2025-12-04 21:05:06', 'Breached', 'Pending', NULL, '2025-12-04 21:05:06', '2025-12-04 21:05:06', NULL),
(12942, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 21:52:16', 'Breached', 'Pending', NULL, '2025-12-04 21:52:16', '2025-12-04 21:52:16', NULL),
(12943, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 21:53:18', 'Breached', 'Pending', NULL, '2025-12-04 21:53:18', '2025-12-04 21:53:18', NULL),
(12944, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 21:53:28', 'Breached', 'Pending', NULL, '2025-12-04 21:53:28', '2025-12-04 21:53:28', NULL),
(12945, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 21:56:57', 'Breached', 'Pending', NULL, '2025-12-04 21:56:57', '2025-12-04 21:56:57', NULL),
(12946, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 22:01:52', 'Breached', 'Pending', NULL, '2025-12-04 22:01:52', '2025-12-04 22:01:52', NULL),
(12947, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 22:09:55', 'Breached', 'Pending', NULL, '2025-12-04 22:09:55', '2025-12-04 22:09:55', NULL),
(12948, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Deepika Iyer has a basic salary below the minimum wage.', '2025-12-04 22:10:31', 'Breached', 'Pending', NULL, '2025-12-04 22:10:31', '2025-12-04 22:10:31', NULL),
(12949, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-12-04 22:32:43', 'Breached', 'Pending', NULL, '2025-12-04 22:32:43', '2025-12-04 22:32:43', NULL),
(12950, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-12-04 22:33:15', 'Breached', 'Pending', NULL, '2025-12-04 22:33:15', '2025-12-04 22:33:15', NULL),
(12951, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-12-04 22:35:18', 'Breached', 'Pending', NULL, '2025-12-04 22:35:18', '2025-12-04 22:35:18', NULL),
(12952, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-12-04 22:37:53', 'Breached', 'Pending', NULL, '2025-12-04 22:37:53', '2025-12-04 22:37:53', NULL),
(12953, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-12-04 22:38:58', 'Breached', 'Pending', NULL, '2025-12-04 22:38:58', '2025-12-04 22:38:58', NULL),
(12954, 26, NULL, 'People Management', 'Minimum Wage', 'Employee John Carter has a basic salary below the minimum wage.', '2025-12-04 22:39:48', 'Breached', 'Pending', NULL, '2025-12-04 22:39:48', '2025-12-04 22:39:48', NULL),
(12955, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 22:57:16', 'Breached', 'Pending', NULL, '2025-12-04 22:57:16', '2025-12-04 22:57:16', NULL),
(12956, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 22:57:49', 'Breached', 'Pending', NULL, '2025-12-04 22:57:49', '2025-12-04 22:57:49', NULL),
(12957, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 23:03:48', 'Breached', 'Pending', NULL, '2025-12-04 23:03:48', '2025-12-04 23:03:48', NULL),
(12958, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 23:09:51', 'Breached', 'Pending', NULL, '2025-12-04 23:09:51', '2025-12-04 23:09:51', NULL),
(12959, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 23:10:54', 'Breached', 'Pending', NULL, '2025-12-04 23:10:54', '2025-12-04 23:10:54', NULL),
(12960, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 23:27:54', 'Breached', 'Pending', NULL, '2025-12-04 23:27:54', '2025-12-04 23:27:54', NULL),
(12961, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 23:28:29', 'Breached', 'Pending', NULL, '2025-12-04 23:28:29', '2025-12-04 23:28:29', NULL),
(12962, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Ibrahim Manik has a basic salary below the minimum wage.', '2025-12-04 23:28:41', 'Breached', 'Pending', NULL, '2025-12-04 23:28:41', '2025-12-04 23:28:41', NULL),
(12963, 26, NULL, 'People Management', 'TIN Requirement', 'Ibrahim Manik (DR-2 - Director Of Finance) (RSWT: MVR 124902/month) not registered. Submit MIRA 118 form.', '2025-12-04 23:29:26', 'Breached', 'Pending', NULL, '2025-12-04 23:29:26', '2025-12-04 23:29:26', NULL),
(12964, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Anastasia Volkova has a basic salary below the minimum wage.', '2025-12-04 23:30:33', 'Breached', 'Pending', NULL, '2025-12-04 23:30:33', '2025-12-04 23:30:33', NULL),
(12965, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Anastasia Volkova has a basic salary below the minimum wage.', '2025-12-04 23:30:59', 'Breached', 'Pending', NULL, '2025-12-04 23:30:59', '2025-12-04 23:30:59', NULL),
(12966, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Anastasia Volkova has a basic salary below the minimum wage.', '2025-12-04 23:42:27', 'Breached', 'Pending', NULL, '2025-12-04 23:42:27', '2025-12-04 23:42:27', NULL),
(12967, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Anastasia Volkova has a basic salary below the minimum wage.', '2025-12-04 23:46:47', 'Breached', 'Pending', NULL, '2025-12-04 23:46:47', '2025-12-04 23:46:47', NULL),
(12968, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Anastasia Volkova has a basic salary below the minimum wage.', '2025-12-04 23:56:35', 'Breached', 'Pending', NULL, '2025-12-04 23:56:35', '2025-12-04 23:56:35', NULL),
(12969, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-05 11:45:30', 'Breached', 'Pending', NULL, '2025-12-05 11:45:30', '2025-12-05 11:45:30', NULL),
(12970, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-05 11:45:50', 'Breached', 'Pending', NULL, '2025-12-05 11:45:50', '2025-12-05 11:45:50', NULL),
(12971, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-05 11:46:36', 'Breached', 'Pending', NULL, '2025-12-05 11:46:36', '2025-12-05 11:46:36', NULL),
(12972, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-05 11:46:52', 'Breached', 'Pending', NULL, '2025-12-05 11:46:52', '2025-12-05 11:46:52', NULL),
(12973, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-05 11:46:59', 'Breached', 'Pending', NULL, '2025-12-05 11:46:59', '2025-12-05 11:46:59', NULL),
(12974, 26, NULL, 'People Management', 'TIN Requirement', 'Olivia Davis (DR-19 - Director Of Human Resources) (RSWT: MVR 95604/month) not registered. Submit MIRA 118 form.', '2025-12-05 11:48:48', 'Breached', 'Pending', NULL, '2025-12-05 11:48:48', '2025-12-05 11:48:48', NULL),
(12975, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-05 11:50:13', 'Breached', 'Pending', NULL, '2025-12-05 11:50:13', '2025-12-05 11:50:13', NULL),
(12976, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-05 11:54:39', 'Breached', 'Pending', NULL, '2025-12-05 11:54:39', '2025-12-05 11:54:39', NULL),
(12977, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-05 11:55:15', 'Breached', 'Pending', NULL, '2025-12-05 11:55:15', '2025-12-05 11:55:15', NULL),
(12978, 26, NULL, 'People Management', 'TIN Requirement', 'Rajesh Patel (DR-7 - Executive Chef) (RSWT: MVR 131070/month) not registered. Submit MIRA 118 form.', '2025-12-05 11:59:47', 'Breached', 'Pending', NULL, '2025-12-05 11:59:47', '2025-12-05 11:59:47', NULL),
(12979, 26, NULL, 'People Management', 'TIN Requirement', 'Ibrahim Manik (DR-2 - Director Of Finance) (RSWT: MVR 124902/month) not registered. Submit MIRA 118 form.', '2025-12-05 13:02:28', 'Breached', 'Pending', NULL, '2025-12-05 13:02:28', '2025-12-05 13:02:28', NULL),
(12980, 26, NULL, 'People Management', 'TIN Requirement', 'Ibrahim Manik (DR-2 - Director Of Finance) (RSWT: MVR 124902/month) not registered. Submit MIRA 118 form.', '2025-12-05 13:15:40', 'Breached', 'Pending', NULL, '2025-12-05 13:15:40', '2025-12-05 13:15:40', NULL),
(12981, 26, NULL, 'Time and Attendance', 'Over Time Not Eligibile', ' (DR-19 - ) is not eligible for overtime.', '2025-12-16 20:35:33', '', 'Pending', NULL, '2025-12-16 20:35:33', '2025-12-16 20:35:33', NULL),
(12982, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-17 14:22:54', 'Breached', 'Pending', NULL, '2025-12-17 14:22:54', '2025-12-17 14:22:54', NULL),
(12983, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-17 14:23:09', 'Breached', 'Pending', NULL, '2025-12-17 14:23:09', '2025-12-17 14:23:09', NULL),
(12984, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-17 14:23:12', 'Breached', 'Pending', NULL, '2025-12-17 14:23:12', '2025-12-17 14:23:12', NULL),
(12985, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-17 14:32:10', 'Breached', 'Pending', NULL, '2025-12-17 14:32:10', '2025-12-17 14:32:10', NULL),
(12986, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-18 15:10:35', 'Breached', 'Pending', NULL, '2025-12-18 15:10:35', '2025-12-18 15:10:35', NULL),
(12987, 26, NULL, 'People Management', 'Minimum Wage', 'Employee Rani Khan has a basic salary below the minimum wage.', '2025-12-18 19:13:22', 'Breached', 'Pending', NULL, '2025-12-18 19:13:22', '2025-12-18 19:13:22', NULL),
(12988, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:14:47', 'Breached', 'Pending', NULL, '2025-12-18 19:14:47', '2025-12-18 19:14:47', NULL),
(12989, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:15:12', 'Breached', 'Pending', NULL, '2025-12-18 19:15:12', '2025-12-18 19:15:12', NULL),
(12990, 26, NULL, 'People Management', 'TIN Requirement', 'Rajesh Patel (DR-7 - Executive Chef) (RSWT: MVR 131070/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:16:50', 'Breached', 'Pending', NULL, '2025-12-18 19:16:50', '2025-12-18 19:16:50', NULL),
(12991, 26, NULL, 'People Management', 'TIN Requirement', 'Rajesh Patel (DR-7 - Executive Chef) (RSWT: MVR 131070/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:17:18', 'Breached', 'Pending', NULL, '2025-12-18 19:17:18', '2025-12-18 19:17:18', NULL),
(12992, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:17:31', 'Breached', 'Pending', NULL, '2025-12-18 19:17:31', '2025-12-18 19:17:31', NULL),
(12993, 26, NULL, 'People Management', 'TIN Requirement', 'James Wilson (DR-18 - General Manager) (RSWT: MVR 127986/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:17:39', 'Breached', 'Pending', NULL, '2025-12-18 19:17:39', '2025-12-18 19:17:39', NULL),
(12994, 26, NULL, 'People Management', 'TIN Requirement', 'Ibrahim Manik (DR-2 - Director Of Finance) (RSWT: MVR 124902/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:30:37', 'Breached', 'Pending', NULL, '2025-12-18 19:30:37', '2025-12-18 19:30:37', NULL),
(12995, 26, NULL, 'People Management', 'TIN Requirement', 'Ibrahim Manik (DR-2 - Director Of Finance) (RSWT: MVR 124902/month) not registered. Submit MIRA 118 form.', '2025-12-18 19:30:46', 'Breached', 'Pending', NULL, '2025-12-18 19:30:46', '2025-12-18 19:30:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `conversation`
--

DROP TABLE IF EXISTS `conversation`;
CREATE TABLE IF NOT EXISTS `conversation` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED DEFAULT NULL COMMENT 'ID of the resort',
  `type` enum('group','individual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual' COMMENT 'Type of conversation, group or individual',
  `type_id` int NOT NULL COMMENT 'ID of the group or individual',
  `sender_id` int NOT NULL COMMENT 'ID of the sender',
  `message` text COLLATE utf8mb4_unicode_ci,
  `attachment` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Attachment',
  `created_by` int UNSIGNED DEFAULT NULL COMMENT 'ID of the user who created the conversation',
  `modified_by` int UNSIGNED DEFAULT NULL COMMENT 'ID of the user who modified the conversation',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
CREATE TABLE IF NOT EXISTS `countries` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `shortname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phonecode` int NOT NULL,
  `flag_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=286 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cultural_insights`
--

DROP TABLE IF EXISTS `cultural_insights`;
CREATE TABLE IF NOT EXISTS `cultural_insights` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `cultural_insights` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cultural_insights_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cultural_insights`
--

INSERT INTO `cultural_insights` (`id`, `resort_id`, `cultural_insights`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(2, 26, '<p><strong>Key Cultural Information:</strong></p>\r\n\r\n<ol>\r\n	<li>\r\n	<p><strong>Religion</strong>&nbsp;- Islam as the official religion, prayer times, Friday as holy day</p>\r\n	</li>\r\n	<li>\r\n	<p><strong>Language</strong>&nbsp;- Dhivehi as national language, English widely spoken</p>\r\n	</li>\r\n	<li>\r\n	<p><strong>Maldivian Values</strong>&nbsp;- Family, community, respect, hospitality</p>\r\n	</li>\r\n	<li>\r\n	<p><strong>Ramadan &amp; Festivals</strong>&nbsp;- Sacred month, fasting, Eid celebrations</p>\r\n	</li>\r\n</ol>\r\n\r\n<p><strong>DOS Section (10 key items):</strong></p>\r\n\r\n<ul>\r\n	<li>\r\n	<p>Show respect to customs and traditions</p>\r\n	</li>\r\n	<li>\r\n	<p>Dress modestly in public areas</p>\r\n	</li>\r\n	<li>\r\n	<p>Use right hand for greeting, eating, and giving items</p>\r\n	</li>\r\n	<li>\r\n	<p>Remove shoes when entering homes/prayer areas</p>\r\n	</li>\r\n	<li>\r\n	<p>Learn basic Dhivehi greetings (Assalamu Alaikum)</p>\r\n	</li>\r\n	<li>\r\n	<p>Respect prayer times</p>\r\n	</li>\r\n	<li>\r\n	<p>Accept tea when offered (sign of hospitality)</p>\r\n	</li>\r\n	<li>\r\n	<p>Acknowledge Ramadan practices</p>\r\n	</li>\r\n	<li>\r\n	<p>Maintain professionalism and courtesy</p>\r\n	</li>\r\n	<li>\r\n	<p>Arrive on time for work</p>\r\n	</li>\r\n</ul>\r\n\r\n<p><strong>DON&#39;Ts Section (11 key items):</strong></p>\r\n\r\n<ul>\r\n	<li>\r\n	<p>DON&#39;T consume or bring alcohol (strictly prohibited by law)</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T disrespect Islam or Islamic beliefs</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T display physical affection publicly</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T point fingers at people</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T show the soles of your feet</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T pass items with left hand</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T eat, drink, or smoke during Ramadan fasting hours</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T wear revealing clothing</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T take photographs without permission</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T be loud or disruptive</p>\r\n	</li>\r\n	<li>\r\n	<p>DON&#39;T criticize Islam or Maldivian culture</p>\r\n	</li>\r\n</ul>\r\n\r\n<p><strong>Workplace Culture Guidelines:</strong></p>\r\n\r\n<ul>\r\n	<li>\r\n	<p>Respect and hierarchy</p>\r\n	</li>\r\n	<li>\r\n	<p>Appropriate titles for seniors</p>\r\n	</li>\r\n	<li>\r\n	<p>Punctuality valued</p>\r\n	</li>\r\n	<li>\r\n	<p>Teamwork essential</p>\r\n	</li>\r\n	<li>\r\n	<p>Professional dress code</p>\r\n	</li>\r\n	<li>\r\n	<p>Warm greetings</p>\r\n	</li>\r\n</ul>', 259, 259, '2025-12-13 16:17:46', '2025-12-13 16:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `custom_benfits`
--

DROP TABLE IF EXISTS `custom_benfits`;
CREATE TABLE IF NOT EXISTS `custom_benfits` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `benefit_grid_id` int UNSIGNED NOT NULL,
  `benefit_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `benefit_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_benfits_benefit_grid_id_foreign` (`benefit_grid_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_discounts`
--

DROP TABLE IF EXISTS `custom_discounts`;
CREATE TABLE IF NOT EXISTS `custom_discounts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `benefit_grid_id` int UNSIGNED NOT NULL,
  `discount_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_rate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_discounts_benefit_grid_id_foreign` (`benefit_grid_id`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_leaves`
--

DROP TABLE IF EXISTS `custom_leaves`;
CREATE TABLE IF NOT EXISTS `custom_leaves` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `benefit_grid_id` int UNSIGNED NOT NULL,
  `leave_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `leave_days` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_leaves_benefit_grid_id_foreign` (`benefit_grid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
CREATE TABLE IF NOT EXISTS `department` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `division_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_division_id_foreign` (`division_id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`id`, `division_id`, `name`, `created_at`, `updated_at`, `code`, `short_name`, `status`, `created_by`, `modified_by`) VALUES
(46, 22, 'Management', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MANAGEMENT', 'Management', 'active', 1, 1),
(47, 22, 'Front Office', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FRONT_OFFICE', 'Front Offi', 'active', 1, 1),
(48, 22, 'Guest Services', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GUEST_SERVICES', 'Guest Serv', 'active', 1, 1),
(49, 22, 'Housekeeping', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOUSEKEEPING', 'Housekeepi', 'active', 1, 1),
(50, 22, 'Laundry', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'LAUNDRY', 'Laundry', 'active', 1, 1),
(51, 22, 'Reservations', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RESERVATIONS', 'Reservatio', 'active', 1, 1),
(52, 22, 'Transportation', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'TRANSPORTATION', 'Transporta', 'active', 1, 1),
(53, 22, 'Complimentary Food and Beverage Club', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'COMPLIMENTARY_FOOD_AND_BEVERAGE_CLUB', 'Compliment', 'active', 1, 1),
(54, 23, 'Management—Service', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MANAGEMENT_SERVICE', 'Management', 'active', 1, 1),
(55, 23, 'Management—Kitchen', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MANAGEMENT_KITCHEN', 'Management', 'active', 1, 1),
(56, 23, 'Banquet/Conference/Catering', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BANQUET_CONFERENCE_CATERING', 'Banquet/Co', 'active', 1, 1),
(57, 23, 'Kitchen', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'KITCHEN', 'Kitchen', 'active', 1, 1),
(58, 23, 'Venues', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'VENUES', 'Venues', 'active', 1, 1),
(59, 24, 'Management', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MANAGEMENT', 'Management', 'active', 1, 1),
(60, 24, 'Golf Pros/Operations', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_PROS_OPERATIONS', 'Golf Pros/', 'active', 1, 1),
(61, 24, 'Greens/Maintenance', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GREENS_MAINTENANCE', 'Greens/Mai', 'active', 1, 1),
(62, 24, 'Pro Shop', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PRO_SHOP', 'Pro Shop', 'active', 1, 1),
(63, 25, 'Management', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MANAGEMENT', 'Management', 'active', 1, 1),
(64, 25, 'Accounting', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTING', 'Accounting', 'active', 1, 1),
(65, 25, 'Human Resources', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HUMAN_RESOURCES', 'Human Reso', 'active', 1, 1),
(66, 25, 'Purchasing and Receiving', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PURCHASING_AND_RECEIVING', 'Purchasing', 'active', 1, 1),
(67, 25, 'Security', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SECURITY', 'Security', 'active', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `deposit_refounds`
--

DROP TABLE IF EXISTS `deposit_refounds`;
CREATE TABLE IF NOT EXISTS `deposit_refounds` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `initial_reminder` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `followup_reminder` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deposit_refounds_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_appeal_models`
--

DROP TABLE IF EXISTS `disciplinary_appeal_models`;
CREATE TABLE IF NOT EXISTS `disciplinary_appeal_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `AppealDeadLine` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Appeal_Type` enum('Committee','Individual') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `MemberId_or_CommitteeId` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_appeal_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_approval_roles`
--

DROP TABLE IF EXISTS `disciplinary_approval_roles`;
CREATE TABLE IF NOT EXISTS `disciplinary_approval_roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Approval_role_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_approval_roles_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_categories_models`
--

DROP TABLE IF EXISTS `disciplinary_categories_models`;
CREATE TABLE IF NOT EXISTS `disciplinary_categories_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `DisciplinaryCategoryName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_categories_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_delegation_rules`
--

DROP TABLE IF EXISTS `disciplinary_delegation_rules`;
CREATE TABLE IF NOT EXISTS `disciplinary_delegation_rules` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Del_cat_id` bigint UNSIGNED NOT NULL,
  `Del_Rule` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_delegation_rules_del_cat_id_foreign` (`Del_cat_id`),
  KEY `disciplinary_delegation_rules_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_emailmodels`
--

DROP TABLE IF EXISTS `disciplinary_emailmodels`;
CREATE TABLE IF NOT EXISTS `disciplinary_emailmodels` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Action_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Placeholders` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_emailmodels_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_investigation_children`
--

DROP TABLE IF EXISTS `disciplinary_investigation_children`;
CREATE TABLE IF NOT EXISTS `disciplinary_investigation_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Disciplinary_P_id` bigint UNSIGNED NOT NULL,
  `inves_find_recommendations` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `follow_up_action` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `follow_up_description` text COLLATE utf8mb4_unicode_ci,
  `investigation_stage` text COLLATE utf8mb4_unicode_ci,
  `resolution_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_investigation_children_disciplinary_p_id_foreign` (`Disciplinary_P_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_investigation_parents`
--

DROP TABLE IF EXISTS `disciplinary_investigation_parents`;
CREATE TABLE IF NOT EXISTS `disciplinary_investigation_parents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Disciplinary_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Committee_member_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invesigation_date` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolution_date` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `investigation_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outcome_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_investigation_parents_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_submits`
--

DROP TABLE IF EXISTS `disciplinary_submits`;
CREATE TABLE IF NOT EXISTS `disciplinary_submits` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Disciplinary_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Employee_id` int NOT NULL,
  `Committee_id` int NOT NULL,
  `Category_id` int NOT NULL,
  `SubCategory_id` int NOT NULL,
  `Offence_id` int NOT NULL,
  `Action_id` int NOT NULL,
  `Severity_id` int NOT NULL,
  `Expiry_date` date DEFAULT NULL,
  `Incident_description` longtext COLLATE utf8mb4_unicode_ci,
  `Acknowledgment_description` longtext COLLATE utf8mb4_unicode_ci,
  `Attachements` text COLLATE utf8mb4_unicode_ci,
  `upload_signed_document` text COLLATE utf8mb4_unicode_ci,
  `Request_For_Statement` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `Assigned` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `select_witness` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `SendtoHr` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `status` enum('pending','In_Review','resolved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `Priority` enum('High','Medium','Low') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disciplinary_submits_disciplinary_id_unique` (`Disciplinary_id`),
  KEY `disciplinary_submits_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_witnesses`
--

DROP TABLE IF EXISTS `disciplinary_witnesses`;
CREATE TABLE IF NOT EXISTS `disciplinary_witnesses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Disciplinary_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Employee_id` int NOT NULL,
  `Statement` text COLLATE utf8mb4_unicode_ci,
  `Attachement` text COLLATE utf8mb4_unicode_ci,
  `Request_For_Statement` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `Wintness_Status` enum('Requested','Approved','NoAction') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Requested',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_witnesses_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinery_assign_committees`
--

DROP TABLE IF EXISTS `disciplinery_assign_committees`;
CREATE TABLE IF NOT EXISTS `disciplinery_assign_committees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `CommitteeName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinery_assign_committees_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinery_committee_members`
--

DROP TABLE IF EXISTS `disciplinery_committee_members`;
CREATE TABLE IF NOT EXISTS `disciplinery_committee_members` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_committee_id` bigint UNSIGNED NOT NULL,
  `MemberId` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinery_committee_members_parent_committee_id_foreign` (`Parent_committee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinery_latter_templetes`
--

DROP TABLE IF EXISTS `disciplinery_latter_templetes`;
CREATE TABLE IF NOT EXISTS `disciplinery_latter_templetes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Latter_Temp_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Latter_Structure` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinery_latter_templetes_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

DROP TABLE IF EXISTS `divisions`;
CREATE TABLE IF NOT EXISTS `divisions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`id`, `name`, `created_at`, `updated_at`, `code`, `short_name`, `status`, `created_by`, `modified_by`) VALUES
(22, 'Rooms', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ROOMS', 'Rooms', 'active', 1, 1),
(23, 'Food and Beverage', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FOOD_AND_BEVERAGE', 'Food and B', 'active', 1, 1),
(24, 'Golf Course/Pro Shop', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_COURSE_PRO_SHOP', 'Golf Cours', 'active', 1, 1),
(25, 'Administrative and General', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ADMINISTRATIVE_AND_GENERAL', 'Administra', 'active', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `duty_rosters`
--

DROP TABLE IF EXISTS `duty_rosters`;
CREATE TABLE IF NOT EXISTS `duty_rosters` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Shift_id` bigint UNSIGNED NOT NULL,
  `Emp_id` int UNSIGNED NOT NULL,
  `ShiftDate` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Year` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DayOfDate` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `duty_rosters_emp_id_foreign` (`Emp_id`),
  KEY `duty_rosters_resort_id_foreign` (`resort_id`),
  KEY `duty_rosters_shift_id_foreign` (`Shift_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1952 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `duty_rosters`
--

INSERT INTO `duty_rosters` (`id`, `resort_id`, `Shift_id`, `Emp_id`, `ShiftDate`, `Year`, `DayOfDate`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1931, 26, 20, 189, '11/17/2025 - 11/23/2025', '2025', 'Sat', 248, 248, '2025-11-17 17:44:56', '2025-11-17 17:44:56'),
(1932, 26, 21, 189, '11/26/2025 - 12/03/2025', '2025', 'Sat', 248, 248, '2025-11-26 21:25:19', '2025-11-26 21:25:19'),
(1933, 26, 22, 189, '12/01/2025 - 12/07/2025', '2025', 'Fri', 248, 248, '2025-12-07 14:44:42', '2025-12-07 14:44:42'),
(1934, 26, 21, 189, '12/14/2025 - 12/21/2025', '2025', 'Fri', 248, 248, '2025-12-07 15:07:52', '2025-12-07 15:07:52'),
(1935, 26, 22, 189, '12/15/2025 - 12/21/2025', '2025', 'Fri', 248, 248, '2025-12-15 17:35:17', '2025-12-15 17:35:17'),
(1936, 26, 20, 188, '12/15/2025 - 12/23/2025', '2025', 'Wed', 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(1937, 26, 20, 189, '12/15/2025 - 12/23/2025', '2025', 'Wed', 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(1938, 26, 20, 183, '12/01/2025 - 12/31/2025', '2025', 'Sat', 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(1939, 26, 20, 183, '12/01/2025 - 12/31/2025', '2025', 'Sat', 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(1940, 26, 21, 183, '12/01/2025 - 12/31/2025', '2025', NULL, 248, 248, '2025-12-18 00:03:34', '2025-12-18 00:03:34'),
(1941, 26, 21, 183, '12/17/2025 - 12/31/2025', '2025', NULL, 248, 248, '2025-12-18 00:06:11', '2025-12-18 00:06:11'),
(1942, 26, 20, 188, '12/23/2025 - 12/31/2025', '2025', NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(1943, 26, 20, 189, '12/23/2025 - 12/31/2025', '2025', NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(1944, 26, 20, 189, '12/23/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:07:50', '2025-12-23 10:07:50'),
(1945, 26, 20, 189, '12/23/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:16:01', '2025-12-23 10:16:01'),
(1946, 26, 20, 189, '12/23/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:30:10', '2025-12-23 10:30:10'),
(1947, 26, 20, 189, '12/23/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:32:15', '2025-12-23 10:32:15'),
(1948, 26, 20, 188, '12/23/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(1949, 26, 20, 189, '12/19/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:50:52', '2025-12-23 10:50:52'),
(1950, 26, 20, 187, '12/23/2025 - 12/30/2025', '2025', NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(1951, 26, 20, 183, '01/01/2026 - 01/31/2026', '2026', NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `duty_roster_entries`
--

DROP TABLE IF EXISTS `duty_roster_entries`;
CREATE TABLE IF NOT EXISTS `duty_roster_entries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `roster_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `Shift_id` bigint UNSIGNED NOT NULL,
  `Emp_id` int UNSIGNED NOT NULL,
  `OverTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CheckingTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DayWiseTotalHours` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CheckingOutTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `Status` enum('On-Time','Late','Absent','Present','DayOff','ShortLeave','HalfDayLeave','FullDayLeave') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `CheckInCheckOut_Type` enum('Manual','Geofencing','Biometric') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `OTStatus` enum('Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `OTApproved_By` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `duty_roster_entries_emp_id_foreign` (`Emp_id`),
  KEY `duty_roster_entries_resort_id_foreign` (`resort_id`),
  KEY `duty_roster_entries_shift_id_foreign` (`Shift_id`),
  KEY `duty_roster_entries_roster_id_foreign` (`roster_id`)
) ENGINE=MyISAM AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `duty_roster_entries`
--

INSERT INTO `duty_roster_entries` (`id`, `roster_id`, `resort_id`, `Shift_id`, `Emp_id`, `OverTime`, `CheckingTime`, `DayWiseTotalHours`, `CheckingOutTime`, `date`, `Status`, `note`, `CheckInCheckOut_Type`, `OTStatus`, `OTApproved_By`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-15', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(2, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-16', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(3, 1936, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-17', 'DayOff', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(4, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-18', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(5, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-19', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(6, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-20', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(7, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-21', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(8, 1936, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-22', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(9, 1936, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(10, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-15', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(11, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-16', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(12, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-17', 'DayOff', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(13, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-18', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(14, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-19', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(15, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-20', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(16, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-21', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(17, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-22', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(18, 1937, 26, 20, 189, '00:00', '04:00', '128:00', '128:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-16 20:35:33', '2025-12-16 20:35:33'),
(19, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-01', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(20, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-02', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(21, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-03', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(22, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-04', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(23, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-05', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(24, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-06', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(25, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-07', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(26, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-08', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(27, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-09', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(28, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-10', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(29, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-11', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(30, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-12', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(31, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-13', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(32, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-14', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(33, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-15', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(34, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-16', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(35, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-17', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(36, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-18', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(37, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-19', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(38, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-20', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(39, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-21', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(40, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-22', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(41, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(42, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-24', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(43, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-25', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(44, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-26', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(45, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-27', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(46, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-28', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(47, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-29', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(48, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-30', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(49, 1938, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-31', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:36:54', '2025-12-17 13:36:54'),
(50, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-01', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(51, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-02', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(52, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-03', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(53, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-04', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(54, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-05', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(55, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-06', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(56, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-07', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(57, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-08', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(58, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-09', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(59, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-10', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(60, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-11', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(61, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-12', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(62, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-13', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(63, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-14', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(64, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-15', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(65, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-16', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(66, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-17', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(67, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-18', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(68, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-19', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(69, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-20', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(70, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-21', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(71, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-22', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(72, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(73, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-24', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(74, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-25', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(75, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-26', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(76, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-27', 'DayOff', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(77, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-28', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(78, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-29', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(79, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-30', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(80, 1939, 26, 20, 183, '00:00', '04:00', '216:00', '216:00', '2025-12-31', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-17 13:38:12', '2025-12-17 13:38:12'),
(81, 1940, 26, 21, 183, '00:00', '12:00', '216:00', '216:00', '1970-01-01', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-18 00:03:34', '2025-12-18 00:03:34'),
(82, 1941, 26, 21, 183, '00:00', '12:00', '104:00', '104:00', '1970-01-01', 'Present', NULL, NULL, NULL, NULL, 248, 248, '2025-12-18 00:06:11', '2025-12-18 00:06:11'),
(83, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(84, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-24', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(85, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-25', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(86, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-26', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(87, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-27', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(88, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-28', 'DayOff', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(89, 1942, 26, 20, 188, '00:00', '04:00', '128:00', '128:00', '2025-12-29', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(90, 1942, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-30', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(91, 1942, 26, 20, 188, '02:00', '04:00', '128:00', '128:00', '2025-12-31', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 09:49:42', '2025-12-23 09:49:42'),
(92, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(93, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-24', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(94, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-25', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(95, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-26', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(96, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-27', 'DayOff', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(97, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-28', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(98, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-29', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(99, 1948, 26, 20, 188, '00:00', '04:00', '112:00', '112:00', '2025-12-30', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:46:04', '2025-12-23 10:46:04'),
(100, 1949, 26, 20, 189, '01:00', '04:00', '88:00', '88:00', '2025-12-19', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:50:52', '2025-12-23 10:50:52'),
(101, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-23', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(102, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-24', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(103, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-25', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(104, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-26', 'DayOff', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(105, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-27', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(106, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-28', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(107, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-29', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(108, 1950, 26, 20, 187, '00:00', '04:00', '112:00', '112:00', '2025-12-30', 'Present', NULL, NULL, NULL, NULL, 259, 259, '2025-12-23 10:54:57', '2025-12-23 10:54:57'),
(109, 1951, 26, 20, 183, '02:00', '03:00', '208:00', '208:00', '2026-01-01', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(110, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-02', 'DayOff', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(111, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-03', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(112, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-04', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(113, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-05', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(114, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-06', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(115, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-07', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(116, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-08', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(117, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-09', 'DayOff', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(118, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-10', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(119, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-11', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(120, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-12', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(121, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-13', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(122, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-14', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(123, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-15', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(124, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-16', 'DayOff', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(125, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-17', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(126, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-18', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(127, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-19', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(128, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-20', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(129, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-21', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(130, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-22', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(131, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-23', 'DayOff', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(132, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-24', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(133, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-25', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(134, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-26', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(135, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-27', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(136, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-28', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(137, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-29', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(138, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-30', 'DayOff', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29'),
(139, 1951, 26, 20, 183, '00:00', '03:00', '208:00', '208:00', '2026-01-31', 'Present', NULL, NULL, NULL, NULL, 250, 250, '2026-01-01 13:17:29', '2026-01-01 13:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `education_applicant_form`
--

DROP TABLE IF EXISTS `education_applicant_form`;
CREATE TABLE IF NOT EXISTS `education_applicant_form` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_form_id` bigint UNSIGNED NOT NULL,
  `institute_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `educational_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_educational` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_educational` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `education_applicant_form_applicant_form_id_foreign` (`applicant_form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` enum('Mr','Miss','Mrs') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mr',
  `Admin_Parent_id` int UNSIGNED DEFAULT NULL,
  `resort_id` int NOT NULL,
  `Emp_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `division_id` int NOT NULL,
  `Dept_id` int UNSIGNED NOT NULL,
  `Section_id` int UNSIGNED DEFAULT NULL,
  `Position_id` int UNSIGNED NOT NULL,
  `reporting_to` int UNSIGNED NOT NULL DEFAULT '0',
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_employee` tinyint NOT NULL DEFAULT '1',
  `rank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `main_rank` int DEFAULT NULL,
  `nationality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Single',
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `employment_type` enum('Full-Time','Part-Time','Contract','Casual','Probationary','Internship','Temporary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Full-Time',
  `passport_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nid` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `present_address` text COLLATE utf8mb4_unicode_ci,
  `biometric_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contract_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `payment_mode` enum('Cash','Bank') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cash',
  `entitled_service_charge` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `entitled_overtime` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `entitled_public_holiday` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `ewt_status` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `pension` decimal(8,2) NOT NULL DEFAULT '0.00',
  `ewt` decimal(8,2) NOT NULL DEFAULT '0.00',
  `probation_end_date` date DEFAULT NULL,
  `probation_status` enum('Active','Extended','Confirmed','Failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `probation_review_date` date DEFAULT NULL,
  `probation_confirmed_by` int UNSIGNED DEFAULT NULL,
  `probation_remarks` text COLLATE utf8mb4_unicode_ci,
  `confirmation_date` date DEFAULT NULL,
  `probation_letter_path` text COLLATE utf8mb4_unicode_ci,
  `contract_end_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `basic_salary_currency` enum('USD','MVR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `proposed_salary` decimal(10,2) DEFAULT NULL,
  `proposed_salary_unit` enum('MVR','USD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `incremented_date` date DEFAULT NULL,
  `last_increment_salary_amount` decimal(5,2) DEFAULT NULL,
  `last_salary_increment_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Increment Type is like Annual etc.',
  `notes` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'remark if any',
  `emg_cont_first_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_last_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_alt_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_relationship` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_nationality` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_dob` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_age` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_education` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_passport_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_passport_expiry_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emg_cont_current_address` text COLLATE utf8mb4_unicode_ci,
  `emg_cont_permanent_address` text COLLATE utf8mb4_unicode_ci,
  `work_location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0 -> non-muslim, 1 -> muslim',
  `resign_effective_date` date DEFAULT NULL,
  `last_working_day` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Terminated','Resigned','On Leave','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `leave_destination` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selfie_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefit_grid_level` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_dept_id_foreign` (`Dept_id`),
  KEY `employees_position_id_foreign` (`Position_id`),
  KEY `employees_section_id_foreign` (`Section_id`),
  KEY `employees_probation_confirmed_by_foreign` (`probation_confirmed_by`),
  KEY `employees_admin_parent_id_foreign` (`Admin_Parent_id`),
  KEY `employees_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `title`, `Admin_Parent_id`, `resort_id`, `Emp_id`, `device_token`, `division_id`, `Dept_id`, `Section_id`, `Position_id`, `reporting_to`, `remember_token`, `is_employee`, `rank`, `main_rank`, `nationality`, `dob`, `marital_status`, `blood_group`, `joining_date`, `employment_type`, `passport_number`, `nid`, `present_address`, `biometric_file`, `tin`, `contract_type`, `termination_date`, `payment_mode`, `entitled_service_charge`, `entitled_overtime`, `entitled_public_holiday`, `ewt_status`, `pension`, `ewt`, `probation_end_date`, `probation_status`, `probation_review_date`, `probation_confirmed_by`, `probation_remarks`, `confirmation_date`, `probation_letter_path`, `contract_end_date`, `basic_salary`, `basic_salary_currency`, `proposed_salary`, `proposed_salary_unit`, `incremented_date`, `last_increment_salary_amount`, `last_salary_increment_type`, `notes`, `emg_cont_first_name`, `emg_cont_last_name`, `emg_cont_no`, `emg_cont_alt_no`, `emg_cont_relationship`, `emg_cont_email`, `emg_cont_nationality`, `emg_cont_dob`, `emg_cont_age`, `emg_cont_education`, `emg_cont_passport_no`, `emg_cont_passport_expiry_date`, `emg_cont_current_address`, `emg_cont_permanent_address`, `work_location`, `religion`, `resign_effective_date`, `last_working_day`, `created_by`, `modified_by`, `deleted_at`, `created_at`, `updated_at`, `status`, `leave_destination`, `selfie_image`, `latitude`, `longitude`, `benefit_grid_level`) VALUES
(168, 'Mr', 238, 25, 'SR-1', NULL, 72, 75, NULL, 139, 0, NULL, 1, '2', 3, 'Maldivian', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 233, 233, NULL, '2025-11-01 06:29:45', '2025-11-01 06:29:45', 'Active', NULL, NULL, NULL, NULL, NULL),
(169, 'Mr', 239, 25, 'SR-2', NULL, 75, 77, NULL, 141, 0, NULL, 1, '2', 3, 'Maldivian', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 233, 238, NULL, '2025-11-06 15:38:22', '2025-11-06 15:55:45', 'Active', NULL, NULL, NULL, NULL, NULL),
(170, 'Miss', 241, 26, 'DR-1', NULL, 76, 79, NULL, 145, 182, NULL, 1, '6', 3, 'Maldivian', '1992-01-01', 'Single', 'A+', '2025-11-17', 'Full-Time', 'A123456789', 'A01234567', NULL, NULL, NULL, 'Permanent', NULL, 'Bank', 'no', 'yes', 'no', 'no', 647.64, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 600.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Fahud', 'Ahmed', '+960912345', NULL, 'Brother', 'fahud@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'Lot 1123, Magu, Maldives', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:40', '2025-12-18 19:30:28', 'Active', 'Kempegowda International Airport Bengaluru', NULL, NULL, NULL, 6),
(171, 'Mr', 242, 26, 'DR-2', NULL, 76, 79, NULL, 143, 188, NULL, 1, '1', 7, 'Maldivian', '1970-07-07', 'Single', 'AB+', '2010-03-11', 'Full-Time', NULL, 'A0945384', NULL, NULL, NULL, 'Single', NULL, 'Bank', 'no', '', 'no', 'no', 8095.50, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 7500.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Aminath Ibrahim', NULL, '9876543', NULL, 'Sister', 'aminath.ibrahim@email.com', NULL, NULL, NULL, NULL, NULL, NULL, 'Malé, Maldives', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:40', '2025-12-18 19:30:46', 'Active', 'Velana International Airport', NULL, NULL, NULL, 1),
(172, 'Mr', 243, 26, 'DR-3', NULL, 76, 78, 40, 151, 0, NULL, 1, '2', 3, 'American', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 240, 240, '2025-11-14 15:04:10', '2025-11-13 16:48:40', '2025-11-13 17:05:12', 'Inactive', NULL, NULL, NULL, NULL, NULL),
(173, 'Miss', 244, 26, 'DR-4', NULL, 77, 80, NULL, 152, 177, NULL, 1, '6', 6, 'Russian', '1992-05-16', 'Single', 'AB+', '2025-12-01', 'Full-Time', 'R46192000', NULL, NULL, NULL, NULL, 'Single', NULL, 'Bank', 'no', 'yes', 'no', 'no', 647.64, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 600.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Irina Volkova', NULL, '+7 495 123-45-67', NULL, 'Mother', 'irina.volkova@mail.ru', NULL, NULL, NULL, NULL, NULL, NULL, 'Ulitsa Tverskaya, 10 Kv. 45 Moscow Moscow Russia - 125009', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:40', '2025-12-18 19:31:06', 'Active', 'Vnukovo (VKO)', NULL, NULL, NULL, 6),
(174, 'Mr', 245, 26, 'DR-5', NULL, 77, 80, NULL, 150, 177, NULL, 1, '6', 6, 'Russian', '1990-06-25', 'Single', 'O+', '2025-12-03', 'Full-Time', 'R8967389', NULL, NULL, NULL, NULL, 'Single', NULL, 'Bank', 'no', 'yes', 'no', 'no', 809.55, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 750.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Mikhail Petrov', NULL, '+7 812 987-65-56', NULL, 'Father', 'mikhail.petrov1962@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'Nevsky Prospekt, 25 Kv. 12, Saint Petersburg, Russia - 191023', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:16:39', 'Active', 'Domodedovo (DME)', NULL, NULL, NULL, 6),
(175, 'Mr', 246, 26, 'DR-6', NULL, 76, 78, 40, 146, 0, NULL, 1, '6', 3, 'Russian', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 240, 240, '2025-11-14 15:04:16', '2025-11-13 16:48:41', '2025-11-13 17:03:40', 'Inactive', NULL, NULL, NULL, NULL, NULL),
(176, 'Mr', 247, 26, 'DR-7', NULL, 77, 80, NULL, 148, 188, NULL, 1, '1', 3, 'Indian', '1982-09-30', 'Single', 'A+', '2020-06-24', 'Full-Time', 'T78302847', NULL, NULL, NULL, NULL, 'Permanent', NULL, 'Bank', 'no', '', 'no', 'no', 7016.10, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 6500.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, 'Priya Patel', '+91 98765 43210', NULL, 'Sister', 'priya.patel@email.com', NULL, NULL, NULL, NULL, NULL, NULL, '45 Bandra Lane, Mumbai, Maharashtra 400050, India', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:17:18', 'Active', 'Indira Gandhi International Airport, Mumbai', NULL, NULL, NULL, 1),
(177, 'Miss', 248, 26, 'DR-8', NULL, 77, 80, NULL, 149, 176, NULL, 1, '2', 3, 'Indian', '1980-11-18', 'Married', 'AB-', '2024-11-01', 'Full-Time', 'L1364850', NULL, NULL, NULL, NULL, 'Single', NULL, 'Cash', 'no', '', 'no', 'yes', 1295.28, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 1200.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Rajesh', 'Sharma', '+91 98765 43210', NULL, 'Husband', 'rajesh.sharma@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '27 Connaught Place Floor 3 New Delhi, Delhi India - 110001', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:18:05', 'Active', 'Indira Gandhi International Airport, New Delhi', NULL, NULL, NULL, 2),
(178, 'Mr', 249, 26, 'DR-9', NULL, 76, 79, NULL, 145, 0, NULL, 1, '6', 3, 'Indian', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 240, 240, '2025-11-14 15:04:21', '2025-11-13 16:48:41', '2025-11-13 16:57:55', 'Inactive', NULL, NULL, NULL, NULL, NULL),
(179, 'Miss', 250, 26, 'DR-10', NULL, 76, 78, NULL, 151, 188, NULL, 1, '2', 3, 'Maldivian', '1992-09-16', 'Single', 'AB-', '2023-05-19', 'Full-Time', NULL, 'A0963428', NULL, NULL, 'TIN85705u4', 'Single', NULL, 'Bank', 'no', '', 'no', 'yes', 2374.68, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 2200.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Hassan Naseer', '+960 7654321', '+960 7654321', NULL, 'Brother', 'hassan.naseer@email.com', NULL, NULL, NULL, NULL, NULL, NULL, 'Male City, Malé, Maldives', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:18:58', 'Active', 'Velana International Airport', NULL, NULL, NULL, 2),
(180, 'Mr', 251, 26, 'DR-11', NULL, 77, 80, NULL, 152, 0, NULL, 1, '6', 6, 'Maldivian', '1998-03-15', 'Single', 'B+', '2022-01-12', 'Full-Time', NULL, 'A123456789', NULL, NULL, NULL, 'Single', NULL, 'Cash', 'yes', 'yes', 'yes', 'no', 593.67, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 550.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Ahmed', 'Shareef', '+960 7765432', NULL, 'Brother', 'ahmed.shareef@email.com', NULL, NULL, NULL, NULL, NULL, NULL, 'Malé City, Maldives', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 20:19:49', 'Active', 'Velana International Airport', NULL, NULL, NULL, 6),
(181, 'Mr', 252, 26, 'DR-12', NULL, 77, 80, 39, 150, 0, NULL, 1, '6', 6, 'Russian', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 240, 240, '2025-11-14 15:04:25', '2025-11-13 16:48:41', '2025-11-13 17:04:49', 'Inactive', NULL, NULL, NULL, NULL, NULL),
(182, 'Miss', 253, 26, 'DR-13', NULL, 76, 79, NULL, 144, 171, NULL, 1, '2', 2, 'Russian', '1984-03-21', 'Divorced', 'AB+', '2018-11-05', 'Full-Time', 'R67395740', NULL, NULL, NULL, 'Tin765f767', 'Single', NULL, 'Bank', 'no', '', 'no', 'yes', 2698.50, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 2500.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Viktor Morozov', NULL, '+7 383 555-1234', NULL, 'Brother', 'viktor.morozov@email.com', NULL, NULL, NULL, NULL, NULL, NULL, 'Krasny Prospekt, 30 Kv. 50\nNovosibirsk, Russia 630090', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:28:25', 'Active', 'Moscow Sheremetyevo Alexander S. Pushkin International Airport', NULL, NULL, NULL, 2),
(183, 'Miss', 254, 26, 'DR-14', NULL, 77, 80, NULL, 152, 177, NULL, 1, '6', 6, 'Indian', '1983-05-16', 'Single', 'A-', '2025-04-21', 'Full-Time', 'Y764246', NULL, NULL, NULL, NULL, 'Single', NULL, 'Cash', 'no', 'yes', 'no', 'no', 701.61, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 650.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Lakshmi Iyer', '+91 98765 43210', 'Mother', NULL, 'lakshmi.iyer@email.com', '42, Residency Road, Chennai, Tamil Nadu 600026, India', NULL, NULL, NULL, NULL, NULL, NULL, '42, Residency Road, Chennai, Tamil Nadu 600026, India', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:29:03', 'Active', 'Chennai International Airport', NULL, NULL, NULL, 6),
(184, 'Mr', 255, 26, 'DR-15', NULL, 76, 78, NULL, 146, 179, NULL, 1, '6', 3, 'American', '1993-06-24', 'Single', 'AB-', '2021-10-01', 'Full-Time', 'A4893047', NULL, NULL, NULL, NULL, 'Single', NULL, 'Bank', 'no', 'yes', 'no', 'no', 593.67, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 550.00, 'USD', 600.00, 'USD', NULL, NULL, NULL, NULL, 'Sarah Carter', NULL, '+1 702-555-0189', NULL, 'Sister', 'sarah.carter@email.com', NULL, NULL, NULL, NULL, NULL, NULL, '350 Las Vegas Blvd, Las Vegas, Nevada USA 89109', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:30:02', 'Active', 'Hartsfield-Jackson Atlanta (ATL)', NULL, NULL, NULL, 6),
(185, 'Mr', 256, 26, 'DR-16', NULL, 77, 80, 39, 149, 0, NULL, 1, '2', 3, 'Russian', NULL, 'Single', NULL, NULL, 'Full-Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Cash', 'no', 'no', 'no', 'no', 0.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, 240, 240, '2025-11-14 15:04:30', '2025-11-13 16:48:41', '2025-11-13 17:04:34', 'Inactive', NULL, NULL, NULL, NULL, NULL),
(186, 'Miss', 257, 26, 'DR-17', NULL, 77, 80, NULL, 152, 177, NULL, 1, '6', 6, 'Maldivian', '2004-02-17', 'Single', 'B-', '2025-01-01', 'Full-Time', NULL, 'A0987644', NULL, NULL, NULL, 'Single', NULL, 'Cash', 'no', 'yes', 'no', 'no', 647.64, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 600.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Aminath Hassan', NULL, '+960 9876543', NULL, 'Sister', 'aminath.hassan@maldivesmail.mv', NULL, NULL, NULL, NULL, NULL, NULL, 'Feydhoo, Laamu Atoll, Feydhoo, Maldives 20601', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 16:48:42', '2025-12-18 19:15:31', 'Active', 'Velana International Airport', NULL, NULL, NULL, 6),
(187, 'Mr', 258, 26, 'DR-18', NULL, 76, 81, NULL, 142, 188, NULL, 1, '8', 3, 'American', '1969-12-03', 'Married', 'B+', '2023-02-15', 'Full-Time', 'C12345678', NULL, NULL, NULL, NULL, 'Permanent', NULL, 'Bank', 'no', '', 'no', 'no', 8635.20, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 8000.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Sarah Wilson', NULL, '+1 212-555-0205', NULL, 'Spouse', 'sarah.wilson@email.com', NULL, NULL, NULL, NULL, NULL, NULL, '456 Madison Avenue Apt 12A New York City New York USA - 10022', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 17:37:28', '2025-12-18 19:17:38', 'Active', 'John F. Kennedy International Airport (JFK)', NULL, NULL, NULL, 1),
(188, 'Mrs', 259, 26, 'DR-19', NULL, 76, 78, NULL, 147, 187, NULL, 1, '1', 3, 'American', '1980-12-31', 'Married', 'AB+', '2018-01-06', 'Full-Time', 'AC123456', NULL, 'Hulhumale, Male, Maldives', NULL, 'TIN84305u4', 'Single', NULL, 'Bank', 'no', '', 'no', 'yes', 5397.00, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 5000.00, 'USD', 5500.00, 'USD', NULL, NULL, NULL, NULL, 'Michael', 'Davis', '+1 415-555-0187', NULL, 'Spouse', 'michael.davis@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '789 Golden Gate Ave, Unit 10, San Francisco, California, USA 94102', NULL, NULL, '0', NULL, NULL, 240, 259, NULL, '2025-11-13 17:49:29', '2025-12-18 19:14:17', 'Active', 'San Francisco International Airport (SFO)', NULL, NULL, NULL, 1),
(189, 'Miss', 260, 26, 'DR-20', NULL, 77, 80, NULL, 152, 177, NULL, 1, '6', 6, 'Sri Lankan', '1992-12-17', 'Single', 'O+', '2025-11-01', 'Full-Time', 'N4567823', NULL, NULL, NULL, NULL, 'Permanent', NULL, 'Bank', 'no', 'yes', 'no', 'no', 377.79, 0.00, NULL, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, 350.00, 'USD', NULL, 'USD', NULL, NULL, NULL, NULL, 'Fatima Khan', NULL, '0112345678', NULL, 'Sister', 'fatima.khan@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '123 Galle Road, Colombo 03, Sri Lanka', NULL, NULL, '1', NULL, NULL, 240, 259, NULL, '2025-11-13 22:07:35', '2025-12-17 14:23:09', 'Active', 'Colombo Bandaranaike International', NULL, NULL, NULL, 6);

-- --------------------------------------------------------

--
-- Table structure for table `employees_allowance`
--

DROP TABLE IF EXISTS `employees_allowance`;
CREATE TABLE IF NOT EXISTS `employees_allowance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `allowance_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_unit` enum('USD','MVR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_allowance_employee_id_foreign` (`employee_id`),
  KEY `employees_allowance_allowance_id_foreign` (`allowance_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_allowance`
--

INSERT INTO `employees_allowance` (`id`, `employee_id`, `allowance_id`, `amount`, `amount_unit`, `created_at`, `updated_at`) VALUES
(8, 170, 164, 300.00, 'USD', '2025-11-19 15:37:02', '2025-11-19 15:37:02'),
(9, 170, 166, 50.00, 'USD', '2025-11-19 15:37:02', '2025-11-19 15:37:02'),
(10, 189, 163, 50.00, 'USD', '2025-11-21 15:09:27', '2025-11-21 15:09:27'),
(11, 177, 171, 300.00, 'USD', '2025-11-21 15:48:36', '2025-11-21 15:48:36'),
(12, 188, 172, 1200.00, 'USD', '2025-11-21 21:18:12', '2025-11-21 21:18:12'),
(13, 187, 172, 300.00, 'USD', '2025-12-03 20:00:00', '2025-12-03 20:00:00'),
(14, 186, 163, 50.00, 'USD', '2025-12-04 15:30:52', '2025-12-04 15:30:52'),
(15, 174, 166, 50.00, 'USD', '2025-12-04 16:31:03', '2025-12-04 16:31:03'),
(16, 176, 172, 2000.00, 'USD', '2025-12-04 17:12:13', '2025-12-04 17:12:13'),
(17, 179, 172, 50.00, 'USD', '2025-12-04 19:32:49', '2025-12-04 19:32:49'),
(18, 180, 163, 50.00, 'USD', '2025-12-04 20:19:49', '2025-12-04 20:19:49'),
(19, 180, 166, 50.00, 'USD', '2025-12-04 20:19:49', '2025-12-04 20:19:49'),
(20, 182, 163, 50.00, 'USD', '2025-12-04 21:05:04', '2025-12-04 21:05:04'),
(21, 182, 172, 50.00, 'USD', '2025-12-04 21:05:04', '2025-12-04 21:05:04'),
(22, 183, 164, 300.00, 'USD', '2025-12-04 22:12:25', '2025-12-04 22:12:25'),
(23, 184, 164, 300.00, 'USD', '2025-12-04 22:40:54', '2025-12-04 22:40:54'),
(24, 171, 172, 300.00, 'USD', '2025-12-04 23:29:24', '2025-12-04 23:29:24'),
(25, 171, 164, 300.00, 'USD', '2025-12-04 23:29:24', '2025-12-04 23:29:24'),
(26, 173, 163, 50.00, 'USD', '2025-12-04 23:57:09', '2025-12-04 23:57:09');

-- --------------------------------------------------------

--
-- Table structure for table `employees_documents`
--

DROP TABLE IF EXISTS `employees_documents`;
CREATE TABLE IF NOT EXISTS `employees_documents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `document_title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_file_size` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_documents`
--

INSERT INTO `employees_documents` (`id`, `employee_id`, `resort_id`, `document_title`, `document_path`, `document_category`, `document_file_size`, `expiry_date`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(65, 189, 26, 'test', '', 'Personal', '', NULL, NULL, NULL, '2025-12-12 01:00:03', '2025-12-12 01:00:03');

-- --------------------------------------------------------

--
-- Table structure for table `employees_education`
--

DROP TABLE IF EXISTS `employees_education`;
CREATE TABLE IF NOT EXISTS `employees_education` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `education_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institution_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_of_study` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `degree` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attendance_period` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certification` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_education_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_education`
--

INSERT INTO `employees_education` (`id`, `employee_id`, `education_level`, `institution_name`, `field_of_study`, `degree`, `attendance_period`, `certification`, `location`, `created_at`, `updated_at`) VALUES
(10, 170, 'Bachelor\'s Degree', 'University of Finance and Accounting', 'Finance', 'Bachelor of Commerce (B.Com) in Finance', '2015-2019', NULL, 'Mumbai, India', '2025-11-19 15:50:02', '2025-11-19 15:50:02'),
(11, 189, 'Secondary School Certificate', 'Colombo Secondary School', 'General Education', 'Secondary School Certificate', '2015-2019', NULL, 'Colombo, Sri Lanka', '2025-11-21 15:18:39', '2025-11-21 15:20:55'),
(12, 189, 'Certificate Course', 'Hospitality Training Institute Sri Lanka', 'Food & Beverage Service', 'Food Service Certificate', '2020-2021', NULL, 'Colombo, Sri Lanka', '2025-11-21 15:19:17', '2025-11-21 15:21:38'),
(13, 177, 'Bachelor\'s Degree', 'University of Delhi', 'Hotel Management & Catering', 'Bachelor of Hotel Management (BHM)', '1998-2002', NULL, 'New Delhi, India', '2025-11-21 15:55:35', '2025-11-21 15:55:35'),
(14, 177, 'Master\'s Degree', 'Institute of Hotel Management, Mumbai', 'Business Administration - Hospitality Management', 'MBA in Hospitality Management', '2007-2009', NULL, 'Mumbai, India', '2025-11-21 15:56:18', '2025-11-21 15:56:18'),
(15, 188, 'Bachelor\'s Degree', 'University of California, Berkeley', 'Human Resources Management', 'Bachelor of Science in Human Resources Management', '1998-2002', NULL, 'Berkeley, California, USA', '2025-11-21 21:39:33', '2025-11-21 21:39:33'),
(16, 188, 'Master\'s Degree', 'Cornell University', 'Industrial and Labor Relations', 'Master of Science in Industrial and Labor Relations', '2002-2004', NULL, 'Ithaca, New York, USA', '2025-11-21 21:40:27', '2025-11-21 21:40:27'),
(17, 188, 'Professional Certification', 'Society for Human Resource Management (SHRM)', 'Human Resource Management', 'SHRM-SCP (Senior Certified Professional)', '2008', NULL, 'Alexandria, Virginia, USA', '2025-11-21 21:41:28', '2025-11-21 21:41:28'),
(18, 187, 'Master\'s Degree', 'Cornell University', 'Business Administration', 'MBA', '1990-1992', NULL, 'Ithaca, New York', '2025-12-03 20:35:44', '2025-12-03 20:35:44'),
(19, 187, 'Bachelor\'s Degree', 'New York University', 'Finance', 'B.S. in Finance', '1986-1990', NULL, 'New York, New York', '2025-12-03 20:39:36', '2025-12-03 20:39:36'),
(20, 187, 'Professional Certification', 'American Hotel & Lodging Association', 'Hotel Management', 'Certified Hotel Administrator (CHA)', '2003-2005', NULL, 'Washington, D.C.', '2025-12-03 20:42:56', '2025-12-03 20:42:56'),
(21, 186, 'O Level', 'Maldives National University Secondary School', 'General Education', 'O Level Certificate', '2019-2021', NULL, 'Maldives', '2025-12-04 15:36:22', '2025-12-04 15:36:22'),
(22, 186, 'A Level', 'Maldives National University College', 'General Education', 'A Level Certificate', '2021-2023', NULL, 'Maldives', '2025-12-04 15:39:25', '2025-12-04 15:51:16'),
(23, 186, 'Diploma', 'Maldives National University', 'Hotel Management', 'Diploma in Hotel Management', '2023-2024', NULL, 'Maldives', '2025-12-04 15:42:45', '2025-12-04 15:42:45'),
(24, 174, 'Vocational Certification', 'Saint Petersburg Culinary Institute', 'Culinary Arts', 'Professional Cook Certificate', '2006-2008', NULL, 'Saint Petersburg, Russia', '2025-12-04 16:24:32', '2025-12-04 16:24:32'),
(25, 174, 'Professional Certification', 'International Food Safety Institute', 'Food Safety', 'Food Safety Certificate', '2008-2009', NULL, 'Moscow, Russia', '2025-12-04 16:25:45', '2025-12-04 16:25:45'),
(26, 174, 'Diploma', 'Institute of Culinary Excellence', 'Advanced Culinary', 'Diploma in Advanced Culinary', '2009-2011', NULL, 'Saint Petersburg, Russia', '2025-12-04 16:27:10', '2025-12-04 16:27:10'),
(27, 176, 'Diploma', 'Institute of Hotel Management', 'Culinary Arts', 'Professional Diploma in Culinary Arts', '1999-2001', NULL, 'Mumbai, India', '2025-12-04 16:53:57', '2025-12-04 16:53:57'),
(28, 176, 'Professional Certificate', 'National Restaurant Association', 'Advanced Culinary Techniques', 'Advanced Culinary Certificate', '2003-2004', NULL, 'Delhi, India', '2025-12-04 16:55:38', '2025-12-04 16:55:38'),
(29, 176, 'Diploma', 'Hotel Management Institute', 'Executive Culinary Management', 'Executive Chef Diploma', '2006-2008', NULL, 'Bangalore, India', '2025-12-04 16:56:23', '2025-12-04 16:56:23'),
(30, 176, 'Professional Certificate', 'International Culinary Institute', 'Culinary Leadership', 'Professional Chef Certification', '2010-2011', NULL, 'Goa, India', '2025-12-04 16:57:19', '2025-12-04 16:57:19'),
(31, 179, 'Bachelor\'s Degree', 'University of Bucharest', 'Business Administration', 'Bachelor of Science in Business Administration', '2008-2012', NULL, 'Bucharest, Romania', '2025-12-04 17:23:48', '2025-12-04 17:23:48'),
(32, 179, 'Professional Certificate', 'Chartered Institute of Personnel and Development', 'Human Resources Management', 'CIPD Level 3 Certificate in Human Resource Management', '2013-2014', NULL, 'London, United Kingdom', '2025-12-04 17:24:19', '2025-12-04 17:24:19'),
(33, 179, 'Master\'s Degree', 'Corvinus University of Budapest', 'Human Resource Development', 'Master of Science in Human Resource Management', '2015-2017', NULL, 'Budapest, Hungary', '2025-12-04 17:24:55', '2025-12-04 17:24:55'),
(34, 180, 'Secondary Education', 'Aminiya School', 'General Studies', 'Secondary School Certificate', '2010-2016', NULL, 'Malé, Maldives', '2025-12-04 20:32:53', '2025-12-04 20:32:53'),
(35, 180, 'Hospitality Diploma', 'Maldives National University', 'Hospitality Management', 'Diploma in Hospitality Management', '2018-2020', NULL, 'Malé, Maldives', '2025-12-04 20:34:42', '2025-12-04 20:34:42'),
(36, 182, 'Bachelor\'s Degree', 'University of Economics and Finance', 'Finance and Accounting', 'B.Sc. in Finance', '2010-2014', NULL, 'Moscow, Russia', '2025-12-04 21:28:39', '2025-12-04 21:28:39'),
(37, 182, 'Master\'s Degree', 'London Business School', 'Business Administration', 'M.B.A. in Finance', '2015-2017', NULL, 'London, United Kingdom', '2025-12-04 21:32:58', '2025-12-04 21:32:58'),
(38, 182, 'Professional Certification', 'AICPA (American Institute)', 'Accounting', 'CPA (Certified Public Accountant)', '2018-2019', NULL, 'USA', '2025-12-04 21:36:46', '2025-12-04 21:36:46'),
(39, 183, 'Certificate', 'Institute of Hospitality Services', 'Food & Beverage Service', 'Professional Waitress Certification', '2005-2006', NULL, 'India', '2025-12-04 22:18:08', '2025-12-04 22:18:08'),
(40, 184, 'Bachelor\'s Degree', 'State University of Maldives', 'Human Resources Management', 'B.A. in Human Resources', '2018-2021', NULL, 'Maldives', '2025-12-04 22:49:37', '2025-12-04 22:49:37'),
(41, 171, 'Master of Business Administration (MBA)', 'Maldives National University', 'Finance', 'MBA', '2010-2012', NULL, 'Malé, Maldives', '2025-12-04 23:14:00', '2025-12-04 23:14:00'),
(42, 173, 'Hospitality Service Certificate', 'Moscow Hospitality Training Institute', 'Food Service and Hospitality', 'Professional Hospitality Service Certificate', '2011-2013', NULL, 'Moscow, Russia', '2025-12-04 23:48:54', '2025-12-04 23:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `employees_experiance`
--

DROP TABLE IF EXISTS `employees_experiance`;
CREATE TABLE IF NOT EXISTS `employees_experiance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `company_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_for_leaving` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_contact` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_experiance_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_experiance`
--

INSERT INTO `employees_experiance` (`id`, `employee_id`, `company_name`, `job_title`, `employment_type`, `duration`, `location`, `reason_for_leaving`, `reference_name`, `reference_contact`, `created_at`, `updated_at`) VALUES
(11, 170, 'Global Finance Solutions Pvt Ltd', 'Accounts Assistant', 'Full-Time', '08/2021-11/2024', 'Mumbai, India', 'Career Growth', 'Rajesh Kumar', '919876543210', '2025-11-19 15:53:59', '2025-11-19 15:56:16'),
(12, 170, 'Mumbai Retail & Finance Co.', 'Finance Intern', 'Contract', '06/2019-07/2021', 'Mumbai, India', 'Pursuing Full-time Position', 'Priya Sharma', '919876512345', '2025-11-19 15:54:54', '2025-11-19 15:56:15'),
(13, 189, 'Green Garden Restaurant', 'Waitress', 'Internship', '01/2023-01/2024', 'Colombo, Sri Lanka', 'Career Growth', 'Sunil Fernando', '94771234567', '2025-11-21 15:26:06', '2025-11-21 15:27:18'),
(14, 177, 'ITC Grand Chola, Chennai', 'Assistant Food & Beverage Manager', 'Full-Time', '11/2009-08/2013', 'Chennai, India', 'Promoted to Higher Role', 'Mr. Ravi Kumar', '9876543221', '2025-11-21 16:12:36', '2025-11-21 16:16:07'),
(15, 177, 'Radisson Blu, Bangalore', 'Food & Beverage Manager', 'Full-Time', '09/2013-05/2018', 'Bangalore, India', 'International Opportunity', 'Ms. Deepa Reddy', '9876543232', '2025-11-21 16:12:37', '2025-11-21 16:16:12'),
(16, 177, 'Paradise Resorts, Maldives', 'Food & Beverage Manager', 'Full-Time', '06/2018-Present', 'Malé, Maldives', 'Currently Employed', 'Mr. Ahmed Hassan', '9607123456', '2025-11-21 16:12:38', '2025-11-21 16:16:18'),
(17, 177, 'The Taj Palace Hotel, New Delhi', 'Restaurant Supervisor', 'Full-Time', '07/2002-05/2005', 'New Delhi, India', 'Career Advancement', 'Mr. Arun Mehta', '9876543210', '2025-11-21 16:12:40', '2025-11-21 16:16:23'),
(18, 177, 'The Oberoi, Mumbai', 'Restaurant Manager', 'Full-Time', '06/2005-10/2009', 'Mumbai, India', 'Better Opportunity', 'Ms. Kavita Singh', '9123456789', '2025-11-21 16:12:41', '2025-11-21 16:16:27'),
(19, 188, 'Marriott International', 'HR Coordinator', 'Part-Time', '06/2002-08/2005', 'San Francisco, California, USA', 'Career Growth', 'Robert Johnson', NULL, '2025-11-21 21:46:34', '2025-11-21 21:58:59'),
(20, 188, 'Hilton Hotels Corporation', 'HR Generalist', 'Full-Time', '09/2005-06/2009', 'Chicago, Illinois, USA', 'Career Advancement', 'Sarah Martinez', NULL, '2025-11-21 21:47:31', '2025-11-21 21:59:13'),
(21, 188, 'Four Seasons Hotels and Resorts', 'Senior HR Manager', 'Full-Time', '01/2014-09/2017', 'Miami, Florida, USA', 'Career Progression', 'David Thompson', NULL, '2025-11-21 21:49:34', '2025-11-21 21:59:32'),
(22, 188, 'Hyatt Hotels Corporation', 'HR Manager', 'Full-Time', '07/2009-12/2013', 'New York, New York, USA', 'Better Opportunity', NULL, NULL, '2025-11-21 21:58:19', '2025-11-21 21:59:39'),
(23, 188, 'Current Employer (Maldives Resort)', 'Director of Human Resources', 'Full-Time', '10/2017-Present', 'Maldives', 'Currently Employed', NULL, NULL, '2025-11-21 21:58:31', '2025-11-21 21:59:43'),
(24, 187, 'New York Plaza Hotel', 'Front Desk Agent', 'Full-Time', '01/1987-06/1993', 'New York, USA', 'Career advancement opportunity', 'Michael Johnson', '2125551234', '2025-12-03 20:44:42', '2025-12-04 14:45:53'),
(25, 187, 'Manhattan Grand Hotel', 'Front Office Manager', 'Full-Time', '07/1993-12/2002', 'Manhattan, New York, USA', 'Better career prospects and salary', 'Sarah Williams', '2125559876', '2025-12-03 20:45:47', '2025-12-04 14:47:52'),
(26, 187, 'Elite Hospitality Group', 'Regional Operations Manager', 'Contract', '01/2003-06/2015', 'Dallas, Texas, USA', 'Project completion and seeking new opportunities', 'David Martinez', '4145551111', '2025-12-03 20:46:49', '2025-12-04 14:50:33'),
(27, 187, 'Plaza Resort & Spa', 'General Manager', 'Full-Time', '07/2015-present', 'Miami, Florida, USA', 'Still employed - current position', 'Robert Anderson', '7865554444', '2025-12-03 20:47:20', '2025-12-04 14:53:23'),
(28, 186, 'Palm Beach Resort & Restaurant', 'Waitress / Server', 'Full-Time', '06/2021-12/2023', 'Male, Maldives', 'Career Advancement', 'Mr. Ahmed Ali', '9607654321', '2025-12-04 15:59:18', '2025-12-04 16:09:15'),
(29, 186, 'Tropical Island Café & Restaurant', 'Cashier / Receptionist', 'Part-Time', '01/2020-05/2021', 'Addu City, Maldives', 'Better Opportunity', 'Ms. Fatima Hassan', '9603456789', '2025-12-04 16:02:23', '2025-12-04 16:11:41'),
(30, 174, 'Moscow Culinary Academy Restaurant', 'Junior Chef', 'Full-Time', '01/2011-01/2015', 'Moscow, Russia', 'Career Advancement', 'Ruso', NULL, '2025-12-04 16:29:08', '2025-12-04 16:42:45'),
(31, 176, 'Mumbai Metropolitan Hotel', 'Junior Chef', 'Full-Time', '01/2001-07/2005', 'Mumbai, India', 'Career Growth', 'Vikram Singh', '912212345678', '2025-12-04 16:59:57', '2025-12-04 17:13:54'),
(32, 176, 'Delhi Metropolitan Hotel', 'Senior Chef', 'Full-Time', '08/2005-06/2009', 'Delhi, India', 'Better Opportunity', 'Rajesh Kumar', '911156789012', '2025-12-04 17:00:58', '2025-12-04 17:13:57'),
(33, 176, 'Bangalore Grand Hotel', 'Head Chef', 'Full-Time', '09/2009-04/2013', 'Bangalore, India', 'Career Advancement', 'Arjun Patel', NULL, '2025-12-04 17:01:55', '2025-12-04 17:14:02'),
(34, 176, 'Goa Luxury Resort', 'Sous Chef', 'Full-Time', '05/2013-12/2016', 'Goa, India', 'Career advancement opportunity', 'Sunny', '9188746294', '2025-12-04 17:02:39', '2025-12-04 17:14:33'),
(35, 179, 'Bucharest Luxury Resort', 'Junior HR Officer', 'Full-Time', 'Full-time', '01/2018-12/2019', 'Bucharest, Romania', 'Career Development', '77849576', '2025-12-04 17:26:07', '2025-12-04 19:34:27'),
(36, 179, 'Dubai Palm Resort', 'Senior HR Manager', 'Contract', 'Full-time', '01/2020-present', 'Dubai, UAE', 'Ongoing', '984639', '2025-12-04 17:26:44', '2025-12-04 19:34:36'),
(37, 180, 'Island Paradise Resort', 'Waitress', 'Full-Time', '06/2020-11/2022', 'Malé, Maldives', 'Career Growth', 'Ali Hassan', '9607776543', '2025-12-04 20:42:32', '2025-12-04 20:51:15'),
(38, 180, 'Ocean View Hotel', 'Restaurant Server', 'Full-Time', '02/2018-05/2020', 'Malé, Maldives', 'Better Opportunity', 'Fatima Ahmed', '9607784321', '2025-12-04 20:48:45', '2025-12-04 20:51:18'),
(39, 182, 'Global Finance Solutions Ltd', 'Senior Accountant', 'Full-Time', '02/2015-05/2019', 'Mumbai, India', 'Pursued higher studies in finance', 'Rajesh Kumar', '919876543210', '2025-12-04 21:39:31', '2025-12-04 21:50:11'),
(40, 182, 'Sterling Finance Pvt Ltd', 'Accounting Manager', 'Full-Time', '05/2019-08/2022', 'Bangalore, India', 'Career advancement opportunity', 'Priya Sharma', '919123456789', '2025-12-04 21:41:42', '2025-12-04 21:50:15'),
(41, 182, 'Capital Markets Advisors Inc', 'Senior Finance Manager', NULL, '08/2022-02/2024', 'Dubai, UAE', 'Career advancement opportunity', 'Michael Chen', '971501234567', '2025-12-04 22:06:18', '2025-12-04 22:06:18'),
(42, 183, 'The Taj Hotels Resorts', 'Waitress', 'Full-Time', '06/2018-12/2021', 'Delhi, India', 'Career advancement', 'Mr. Rajesh Verma', '919876543210', '2025-12-04 22:19:38', '2025-12-04 22:32:22'),
(43, 183, 'Oberoi Hotels & Resorts', 'Senior Waitress', 'Full-Time', '03/2014-05/2018', 'Mumbai, India', 'Personal reasons', 'Ms. Priya Singh', '919123456789', '2025-12-04 22:20:36', '2025-12-04 22:32:27'),
(44, 183, 'Le Meridien Hotels', 'Waitress', NULL, '09/2009-02/2014', 'Bangalore, India', 'Better opportunity', 'Mr. Amit Kumar', '918765432101', '2025-12-04 22:22:09', '2025-12-04 22:22:09'),
(45, 183, 'ITC Hotels', 'Waitress', NULL, '01/2006-08/2009', 'Pune, India', 'Relocation', 'Mr. Vikram Patel', '917654321098', '2025-12-04 22:23:23', '2025-12-04 22:23:23'),
(46, 184, 'Paradise Resort & Spa', 'HR Assistant', 'Full-Time', '06/2021-10/2024', 'Maldives', 'Career Advancement', 'John Doe', '98356934', '2025-12-04 22:53:31', '2025-12-04 22:55:03'),
(47, 171, 'Paradise Finance Group', 'Senior Finance Manager', 'Full-Time', '01/2012-12/2019', 'Malé, Maldives', 'Career advancement', 'Adhil Khan', '0096093658', '2025-12-04 23:17:30', '2025-12-04 23:30:09'),
(48, 173, 'Pushkin Restaurant Moscow', 'Waitress', 'Full-Time', '01/2015-12/2023', 'Moscow, Russia', 'Career advancement opportunity', 'Petro', '009473462', '2025-12-04 23:51:14', '2025-12-04 23:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `employees_language`
--

DROP TABLE IF EXISTS `employees_language`;
CREATE TABLE IF NOT EXISTS `employees_language` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `language` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proficiency_level` enum('Beginner','Intermediate','Advanced','Fluent','Native') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Beginner',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_language_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_language`
--

INSERT INTO `employees_language` (`id`, `employee_id`, `language`, `proficiency_level`, `created_at`, `updated_at`) VALUES
(94, 170, 'Dhivehi', 'Native', '2025-11-19 11:52:37', '2025-11-19 11:52:37'),
(95, 170, 'English', 'Fluent', '2025-11-19 11:52:37', '2025-11-19 11:52:37'),
(96, 170, 'Arabic', 'Intermediate', '2025-11-19 11:52:37', '2025-11-19 11:52:37'),
(97, 189, 'Sinhala', 'Native', '2025-11-21 15:04:09', '2025-11-21 15:04:09'),
(98, 189, 'English', 'Fluent', '2025-11-21 15:04:09', '2025-11-21 15:04:09'),
(99, 189, 'Tamil', 'Intermediate', '2025-11-21 15:04:09', '2025-11-21 15:04:09'),
(100, 177, 'English', 'Fluent', '2025-11-21 15:40:22', '2025-11-21 15:40:22'),
(101, 177, 'Hindi', 'Native', '2025-11-21 15:40:22', '2025-11-21 15:40:22'),
(102, 177, 'Tamil', 'Intermediate', '2025-11-21 15:40:22', '2025-11-21 15:40:22'),
(103, 177, 'Punjabi', 'Beginner', '2025-11-21 15:40:22', '2025-11-21 15:40:22'),
(104, 188, 'English', 'Native', '2025-11-21 21:11:11', '2025-11-21 21:11:11'),
(105, 188, 'Spanish', 'Intermediate', '2025-11-21 21:11:11', '2025-11-21 21:11:11'),
(111, 187, 'English', 'Fluent', '2025-12-03 20:20:02', '2025-12-03 20:20:02'),
(112, 187, 'Spanish', 'Advanced', '2025-12-03 20:20:02', '2025-12-03 20:20:02'),
(113, 187, 'French', 'Intermediate', '2025-12-03 20:20:02', '2025-12-03 20:20:02'),
(114, 187, 'German', 'Beginner', '2025-12-03 20:20:02', '2025-12-03 20:20:02'),
(115, 187, 'Italian', 'Intermediate', '2025-12-03 20:20:02', '2025-12-03 20:20:02'),
(116, 186, 'Dhivehi', 'Native', '2025-12-04 15:28:46', '2025-12-04 15:28:46'),
(117, 186, 'English', 'Intermediate', '2025-12-04 15:28:46', '2025-12-04 15:28:46'),
(120, 174, 'Russian', 'Native', '2025-12-04 16:33:44', '2025-12-04 16:33:44'),
(121, 174, 'English', 'Intermediate', '2025-12-04 16:33:44', '2025-12-04 16:33:44'),
(122, 176, 'Hindi', 'Native', '2025-12-04 16:51:42', '2025-12-04 16:51:42'),
(123, 176, 'English', 'Intermediate', '2025-12-04 16:51:42', '2025-12-04 16:51:42'),
(127, 179, 'English', 'Fluent', '2025-12-04 19:31:18', '2025-12-04 19:31:18'),
(128, 179, 'French', 'Intermediate', '2025-12-04 19:31:18', '2025-12-04 19:31:18'),
(129, 179, 'Romanian', 'Native', '2025-12-04 19:31:18', '2025-12-04 19:31:18'),
(132, 180, 'Dhivehi', 'Native', '2025-12-04 20:00:28', '2025-12-04 20:00:28'),
(133, 180, 'English', 'Fluent', '2025-12-04 20:00:28', '2025-12-04 20:00:28'),
(137, 182, 'English', 'Fluent', '2025-12-04 21:03:14', '2025-12-04 21:03:14'),
(138, 182, 'French', 'Intermediate', '2025-12-04 21:03:14', '2025-12-04 21:03:14'),
(139, 182, 'Spanish', 'Advanced', '2025-12-04 21:03:14', '2025-12-04 21:03:14'),
(140, 182, 'Slavic', 'Native', '2025-12-04 21:03:14', '2025-12-04 21:03:14'),
(144, 183, 'English', 'Fluent', '2025-12-04 22:31:10', '2025-12-04 22:31:10'),
(145, 183, 'Tamil', 'Native', '2025-12-04 22:31:10', '2025-12-04 22:31:10'),
(146, 183, 'Hindi', 'Intermediate', '2025-12-04 22:31:10', '2025-12-04 22:31:10'),
(149, 184, 'English', 'Native', '2025-12-04 22:38:57', '2025-12-04 22:38:57'),
(150, 184, 'Spanish', 'Intermediate', '2025-12-04 22:38:57', '2025-12-04 22:38:57'),
(154, 171, 'Dhivehi', 'Native', '2025-12-04 23:28:41', '2025-12-04 23:28:41'),
(155, 171, 'English', 'Fluent', '2025-12-04 23:28:41', '2025-12-04 23:28:41'),
(158, 173, 'Russian', 'Native', '2025-12-04 23:58:40', '2025-12-04 23:58:40'),
(159, 173, 'English', 'Intermediate', '2025-12-04 23:58:40', '2025-12-04 23:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `employees_leaves`
--

DROP TABLE IF EXISTS `employees_leaves`;
CREATE TABLE IF NOT EXISTS `employees_leaves` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `emp_id` int UNSIGNED NOT NULL,
  `leave_category_id` int UNSIGNED NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `total_days` int NOT NULL,
  `duration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag` int UNSIGNED DEFAULT NULL,
  `task_delegation` int UNSIGNED DEFAULT NULL,
  `destination` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transportation` bigint UNSIGNED DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_leaves_resort_id_foreign` (`resort_id`),
  KEY `employees_leaves_emp_id_foreign` (`emp_id`),
  KEY `employees_leaves_leave_category_id_foreign` (`leave_category_id`),
  KEY `employees_leaves_task_delegation_foreign` (`task_delegation`),
  KEY `employees_leaves_transportation_foreign` (`transportation`)
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_leaves`
--

INSERT INTO `employees_leaves` (`id`, `resort_id`, `emp_id`, `leave_category_id`, `from_date`, `to_date`, `total_days`, `duration`, `from_time`, `to_time`, `attachments`, `reason`, `flag`, `task_delegation`, `destination`, `transportation`, `departure_date`, `arrival_date`, `status`, `created_at`, `updated_at`) VALUES
(239, 26, 177, 58, '2025-12-17', '2025-12-18', 2, '', NULL, NULL, NULL, 'My son is sick', NULL, 185, 'Male', NULL, NULL, NULL, 'Pending', '2025-12-13 18:28:05', '2025-12-13 18:28:05'),
(240, 26, 189, 58, '2025-12-01', '2025-12-02', 2, '', NULL, NULL, NULL, 'Father is sick', NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2025-12-17 14:02:50', '2025-12-17 14:02:50'),
(241, 26, 189, 59, '2025-12-20', '2025-12-31', 12, '', NULL, NULL, NULL, 'my son is born so I need a leave', NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2025-12-17 14:10:15', '2025-12-17 14:10:15'),
(242, 26, 177, 58, '2025-12-20', '2025-12-21', 2, '', NULL, NULL, 'uploads/leave_attachments/177/attachment_6942745aa8f790.64551287.pdf', 'Father is sick', NULL, 185, NULL, NULL, NULL, NULL, 'Pending', '2025-12-17 14:44:02', '2025-12-17 14:44:02'),
(245, 26, 177, 63, '2025-12-28', '2025-12-28', 1, '', NULL, NULL, NULL, 'relax', NULL, 177, 'male', NULL, NULL, NULL, 'Pending', '2025-12-17 19:37:17', '2025-12-17 19:37:17'),
(248, 26, 177, 57, '2025-12-25', '2025-12-25', 1, '', NULL, NULL, NULL, 'feeling not weel i want sick leave', NULL, 177, 'test', NULL, NULL, NULL, 'Pending', '2025-12-18 14:33:09', '2025-12-18 14:33:09');

-- --------------------------------------------------------

--
-- Table structure for table `employees_leaves_status`
--

DROP TABLE IF EXISTS `employees_leaves_status`;
CREATE TABLE IF NOT EXISTS `employees_leaves_status` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `leave_request_id` int UNSIGNED NOT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `approver_rank` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approver_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_leaves_status_leave_request_id_foreign` (`leave_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=532 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees_leave_transportation`
--

DROP TABLE IF EXISTS `employees_leave_transportation`;
CREATE TABLE IF NOT EXISTS `employees_leave_transportation` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `leave_request_id` int UNSIGNED NOT NULL,
  `transportation` bigint UNSIGNED NOT NULL,
  `trans_arrival_date` date NOT NULL,
  `trans_departure_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_leave_transportation_leave_request_id_foreign` (`leave_request_id`),
  KEY `employees_leave_transportation_transportation_foreign` (`transportation`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees_leave_transportation`
--

INSERT INTO `employees_leave_transportation` (`id`, `leave_request_id`, `transportation`, `trans_arrival_date`, `trans_departure_date`, `created_at`, `updated_at`) VALUES
(23, 242, 5, '2025-12-21', '2025-12-20', '2025-12-17 14:44:02', '2025-12-17 14:44:02'),
(26, 245, 6, '0000-00-00', '2025-12-17', '2025-12-17 19:37:17', '2025-12-17 19:37:17'),
(29, 248, 5, '0000-00-00', '2025-12-25', '2025-12-18 14:33:09', '2025-12-18 14:33:09');

-- --------------------------------------------------------

--
-- Table structure for table `employee_bank_details`
--

DROP TABLE IF EXISTS `employee_bank_details`;
CREATE TABLE IF NOT EXISTS `employee_bank_details` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `IFSC_BIC` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_holder_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` enum('USD','MVR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `IBAN` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_bank_details_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_bank_details`
--

INSERT INTO `employee_bank_details` (`id`, `employee_id`, `bank_name`, `bank_branch`, `account_type`, `IFSC_BIC`, `account_holder_name`, `account_no`, `currency`, `IBAN`, `created_at`, `updated_at`) VALUES
(7, 170, 'Bank of Maldives', 'Malé Main Branch', 'Saving', 'MALBMVMV', 'Aminath Abdul', '772000000012', 'USD', NULL, '2025-11-19 12:34:44', '2025-11-19 12:34:44'),
(8, 189, 'Islamic Bank of Maldives', 'Male Branch', 'Savings Account', 'IBMLMVMV', 'Rani Khan', '7701234567', 'USD', 'MV12IBML7701234567890123', '2025-11-21 15:14:05', '2025-11-21 15:16:04'),
(9, 189, 'Bank of Ceylon Sri Lanka', 'Colombo Branch', 'Current Account', 'BCEYLKLX', 'Rani khan', '1234567890', 'USD', 'LK34BCEY1234567890123456', '2025-11-21 15:14:46', '2025-11-21 15:16:14'),
(10, 177, 'Bank of Maldives', 'Male Branch', 'Savings Account', 'BOMVMVMV', 'Priya Sharma', '7712345678', 'USD', 'MV98BOMV7712345678901234', '2025-11-21 15:53:00', '2025-11-21 15:53:00'),
(11, 188, 'Bank of Maldives', 'Male Branch', 'Savings', 'MALBMVMVXXX', 'Olivia Davis', '7701234567890', 'USD', NULL, '2025-11-21 21:30:33', '2025-11-21 21:30:33'),
(12, 187, 'Bank of Maldives', 'Main Branch', 'Savings', 'BMLUSUSNYX', 'James Wilson', '987654321098', 'USD', NULL, '2025-12-03 20:23:54', '2025-12-03 20:31:26'),
(13, 174, 'Bank of Maldives', 'Male, Maldives', 'Savings', 'BOMAMV2X', 'Dmitri Petrov', '1234567890', 'USD', 'MV94BOMAMV2X1234567890', '2025-12-04 16:40:27', '2025-12-04 16:40:50'),
(14, 176, 'Bank of Maldives', 'Male, Maldives', 'Savings', 'BOMAMV2X', 'Rajesh Patel', '1234567890', 'USD', 'MV94BOMAMV2X1234567890', '2025-12-04 17:05:07', '2025-12-04 17:05:07'),
(15, 179, 'Islamic Bank of Maldives', 'Male, Maldives', 'Savings', 'IBOMMV2X', 'Fatima Nasir', '1234567890', 'USD', 'MV94IBOMMV2X1234567890', '2025-12-04 17:28:28', '2025-12-04 17:28:28'),
(16, 180, 'MIB', 'Male, Maldives', 'Savings', 'MIBMMVMX', 'Mohamed Shareef', '1234567890', 'USD', NULL, '2025-12-04 20:27:30', '2025-12-04 20:27:30'),
(17, 182, 'Bank of Maldives', 'Male, Maldives', 'Savings', 'BOMMMV23', 'Elena Morozova', '123456789012', 'USD', 'MV15BOMM123456789012', '2025-12-04 21:09:56', '2025-12-04 21:09:56'),
(18, 183, 'Bank of Maldives', 'Male, Maldives', 'Savings', 'BOMAMV2X', 'Deepika Iyer', '1234567890', 'USD', 'MV9400123456789012345', '2025-12-04 22:16:08', '2025-12-04 22:16:08'),
(19, 184, 'MIB', 'Male City Branch', 'Savings', 'MIBMMV01', 'John Carter', '123456789012', 'USD', 'MV94MIBM0123456789012345', '2025-12-04 22:45:14', '2025-12-04 22:45:14'),
(20, 171, 'Bank of Maldives', 'Malé Main Branch', 'Savings Account', 'BOMAMVMV', 'Ibrahim Manik', '123456789012', 'USD', NULL, '2025-12-04 23:25:35', '2025-12-04 23:25:35'),
(21, 173, 'MIB Bank', 'Moscow Main Branch', 'Savings', 'MIBBRUMMX', 'Anastasia Volkova', '123456789012', 'USD', NULL, '2025-12-04 23:54:35', '2025-12-04 23:54:35');

-- --------------------------------------------------------

--
-- Table structure for table `employee_chat_messages`
--

DROP TABLE IF EXISTS `employee_chat_messages`;
CREATE TABLE IF NOT EXISTS `employee_chat_messages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `sender_id` bigint UNSIGNED NOT NULL,
  `receiver_id` bigint UNSIGNED NOT NULL,
  `conversation_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_chat_messages_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_info_update_request`
--

DROP TABLE IF EXISTS `employee_info_update_request`;
CREATE TABLE IF NOT EXISTS `employee_info_update_request` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `info_payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Approved','Rejected','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `reject_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_info_update_request_resort_id_foreign` (`resort_id`),
  KEY `employee_info_update_request_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_info_update_request`
--

INSERT INTO `employee_info_update_request` (`id`, `resort_id`, `employee_id`, `title`, `info_payload`, `status`, `reject_reason`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(60, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"+91 98765 43210\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_nationality\":null,\"emg_cont_dob\":\"1994-11-21\",\"emg_cont_age\":\"31\",\"emg_cont_education\":\"fgdfd\",\"emg_cont_passport_no\":\"no pass\",\"emg_cont_passport_expiry_date\":\"no expiry\",\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"no perm\"}', 'Pending', NULL, 248, 248, '2025-11-21 22:36:24', '2025-11-21 22:36:24'),
(61, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"+91 98765 43210\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_nationality\":null,\"emg_cont_dob\":\"1999-11-21\",\"emg_cont_age\":\"26\",\"emg_cont_education\":\"high\",\"emg_cont_passport_no\":\"no pass\",\"emg_cont_passport_expiry_date\":\"no expiry\",\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"no perm\"}', 'Pending', NULL, 248, 248, '2025-11-21 22:40:33', '2025-11-21 22:40:33'),
(62, 26, 189, 'Personal Information', '{\"first_name\":\"Shaurya\",\"middle_name\":\"m\",\"last_name\":\"Pawarrr\",\"personal_phone\":\"1234567897\"}', 'Pending', NULL, 260, 260, '2025-11-21 23:34:26', '2025-11-21 23:34:26'),
(63, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"+91 98765 43210\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_nationality\":null,\"emg_cont_dob\":\"1990-11-21\",\"emg_cont_age\":\"35\",\"emg_cont_education\":\"dad\",\"emg_cont_passport_no\":\"no pass\",\"emg_cont_passport_expiry_date\":\"no expiry\",\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"no perm\"}', 'Pending', NULL, 248, 248, '2025-11-21 23:35:54', '2025-11-21 23:35:54'),
(64, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"dob\":\"2015-11-21\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"+91 98765 43210\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_nationality\":null,\"emg_cont_dob\":null,\"emg_cont_age\":\"10\",\"emg_cont_education\":\"education\",\"emg_cont_passport_no\":\"no pass\",\"emg_cont_passport_expiry_date\":\"no expiry\",\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"no perm\"}', 'Pending', NULL, 248, 248, '2025-11-21 23:59:57', '2025-11-21 23:59:57'),
(65, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"dob\":\"1980-11-18\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"+91 98765 43210\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_nationality\":null,\"emg_cont_dob\":null,\"emg_cont_age\":\"45\",\"emg_cont_education\":\"John\",\"emg_cont_passport_no\":\"no pass\",\"emg_cont_passport_expiry_date\":\"no expiry\",\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"no perm\"}', 'Pending', NULL, 248, 248, '2025-11-22 00:27:47', '2025-11-22 00:27:47'),
(66, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"4242452424\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_dob\":null,\"emg_cont_age\":\"0\",\"emg_cont_education\":\"ffs\",\"emg_cont_passport_no\":\"4242\",\"emg_cont_passport_expiry_date\":null,\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"fsfsfsf\",\"dob\":\"2025-11-05\",\"nationality\":\"Indian\"}', 'Pending', NULL, 248, 248, '2025-11-22 00:51:06', '2025-11-22 00:51:06'),
(67, 26, 177, 'Personal Information', '{\"first_name\":\"Priya\",\"middle_name\":\"Devi\",\"last_name\":\"Sharma\",\"personal_phone\":\"+91 11 4567 8901\",\"emg_cont_first_name\":\"Rajesh\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"+91 98765 43210\",\"emg_cont_alt_no\":\"4242424244\",\"emg_cont_relationship\":\"Husband\",\"emg_cont_email\":\"caliph-nursing-3q@icloud.com\",\"emg_cont_dob\":null,\"emg_cont_age\":\"0\",\"emg_cont_education\":\"dad\",\"emg_cont_passport_no\":\"3232\",\"emg_cont_passport_expiry_date\":null,\"emg_cont_current_address\":\"27 Connaught Place Floor 3 New Delhi, Delhi India - 110001\",\"emg_cont_permanent_address\":\"dads\",\"dob\":\"2025-11-11\",\"nationality\":\"Indian\",\"emg_cont_nationality\":null}', 'Pending', NULL, 248, 248, '2025-11-22 00:54:02', '2025-11-22 00:54:02'),
(68, 26, 189, 'Personal Information', '{\"first_name\":\"Rani\",\"middle_name\":null,\"last_name\":\"Khan\",\"personal_phone\":\"+91949870987\",\"emg_cont_first_name\":\"Fatima Khan\",\"emg_cont_last_name\":null,\"emg_cont_no\":\"0112345678\",\"emg_cont_alt_no\":\"4324242422\",\"emg_cont_relationship\":\"Sister\",\"emg_cont_email\":\"09-wearers-primary@icloud.com\",\"emg_cont_dob\":null,\"emg_cont_age\":\"33\",\"emg_cont_education\":\"seeds\",\"emg_cont_passport_no\":\"32423\",\"emg_cont_passport_expiry_date\":null,\"emg_cont_current_address\":\"123 Galle Road, Colombo 03, Sri Lanka\",\"emg_cont_permanent_address\":\"dads\",\"dob\":\"1992-03-22\",\"nationality\":\"Sri Lankan\",\"emg_cont_nationality\":null}', 'Pending', NULL, 260, 260, '2025-11-22 01:07:29', '2025-11-22 01:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `employee_insurances`
--

DROP TABLE IF EXISTS `employee_insurances`;
CREATE TABLE IF NOT EXISTS `employee_insurances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `insurance_company` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_policy_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_coverage` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_start_date` date NOT NULL,
  `insurance_end_date` date NOT NULL,
  `Currency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Premium` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_insurances_resort_id_foreign` (`resort_id`),
  KEY `employee_insurances_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_insurance_children`
--

DROP TABLE IF EXISTS `employee_insurance_children`;
CREATE TABLE IF NOT EXISTS `employee_insurance_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_insurances_id` bigint UNSIGNED NOT NULL,
  `insurance_company` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_policy_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_coverage` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_start_date` date NOT NULL,
  `insurance_end_date` date NOT NULL,
  `insurance_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Premium` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_insurance_children_employee_insurances_id_foreign` (`employee_insurances_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_itineraries`
--

DROP TABLE IF EXISTS `employee_itineraries`;
CREATE TABLE IF NOT EXISTS `employee_itineraries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `template_id` bigint UNSIGNED NOT NULL,
  `greeting_message` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arrival_date` date NOT NULL,
  `arrival_time` time NOT NULL,
  `entry_pass_file` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flight_ticket_file` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_employee_id` int UNSIGNED DEFAULT NULL,
  `accompany_medical_employee_id` int UNSIGNED DEFAULT NULL,
  `resort_transportation_id` bigint UNSIGNED DEFAULT NULL,
  `domestic_flight_date` date DEFAULT NULL,
  `domestic_departure_time` time DEFAULT NULL,
  `domestic_arrival_time` time DEFAULT NULL,
  `domestic_flight_ticket` text COLLATE utf8mb4_unicode_ci,
  `speedboat_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `speedboat_date` date DEFAULT NULL,
  `speedboat_departure_time` time DEFAULT NULL,
  `speedboat_arrival_time` time DEFAULT NULL,
  `captain_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seaplane_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seaplane_date` date DEFAULT NULL,
  `seaplane_departure_time` time DEFAULT NULL,
  `seaplane_arrival_time` time DEFAULT NULL,
  `hotel_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hotel_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hotel_contact_no` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_reference` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hotel_address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medical_center_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medical_center_contact_no` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medical_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `medical_date` date DEFAULT NULL,
  `medical_time` time DEFAULT NULL,
  `approx_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_itineraries_resort_id_foreign` (`resort_id`),
  KEY `employee_itineraries_employee_id_foreign` (`employee_id`),
  KEY `employee_itineraries_template_id_foreign` (`template_id`),
  KEY `employee_itineraries_pickup_employee_id_foreign` (`pickup_employee_id`),
  KEY `employee_itineraries_accompany_medical_employee_id_foreign` (`accompany_medical_employee_id`),
  KEY `employee_itineraries_resort_transportation_id_foreign` (`resort_transportation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_itineraries_meeting`
--

DROP TABLE IF EXISTS `employee_itineraries_meeting`;
CREATE TABLE IF NOT EXISTS `employee_itineraries_meeting` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_itinerary_id` bigint UNSIGNED NOT NULL,
  `meeting_title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time NOT NULL,
  `meeting_link` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_participant_ids` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_itineraries_meeting_employee_itinerary_id_foreign` (`employee_itinerary_id`),
  KEY `employee_itineraries_meeting_meeting_participant_id_foreign` (`meeting_participant_ids`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_notice_period`
--

DROP TABLE IF EXISTS `employee_notice_period`;
CREATE TABLE IF NOT EXISTS `employee_notice_period` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Days Ex-30',
  `immediate_release` int NOT NULL DEFAULT '0',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_notice_period_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_notice_period`
--

INSERT INTO `employee_notice_period` (`id`, `resort_id`, `title`, `period`, `immediate_release`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(4, 26, 'LINE WORKERS', NULL, 1, 259, 259, '2025-12-13 15:21:46', '2025-12-13 15:22:00'),
(5, 26, 'MGR', '30', 0, 259, 259, '2025-12-13 15:21:46', '2025-12-13 15:21:46'),
(6, 26, 'HOD', '45', 0, 259, 259, '2025-12-13 15:21:46', '2025-12-13 15:21:46'),
(7, 26, 'EXCOM', '45', 0, 259, 259, '2025-12-13 15:21:46', '2025-12-13 15:21:46'),
(8, 26, 'GM', '60', 0, 259, 259, '2025-12-13 15:21:46', '2025-12-13 15:21:46');

-- --------------------------------------------------------

--
-- Table structure for table `employee_onboarding_acknowledgements`
--

DROP TABLE IF EXISTS `employee_onboarding_acknowledgements`;
CREATE TABLE IF NOT EXISTS `employee_onboarding_acknowledgements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `acknowledgement_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `acknowledged_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_onboarding_acknowledgements_employee_id_foreign` (`employee_id`),
  KEY `employee_onboarding_acknowledgements_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_overtimes`
--

DROP TABLE IF EXISTS `employee_overtimes`;
CREATE TABLE IF NOT EXISTS `employee_overtimes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Emp_id` int UNSIGNED NOT NULL,
  `Shift_id` bigint UNSIGNED NOT NULL,
  `roster_id` int UNSIGNED DEFAULT NULL,
  `parent_attendance_id` int UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `start_location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overtime_type` enum('before_shift','after_shift','split') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_overtimes_shift_id_foreign` (`Shift_id`),
  KEY `employee_overtimes_roster_id_foreign` (`roster_id`),
  KEY `employee_overtimes_parent_attendance_id_foreign` (`parent_attendance_id`),
  KEY `employee_overtimes_approved_by_foreign` (`approved_by`),
  KEY `employee_overtimes_emp_id_date_index` (`Emp_id`,`date`),
  KEY `employee_overtimes_resort_id_date_index` (`resort_id`,`date`),
  KEY `employee_overtimes_status_index` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_overtimes`
--

INSERT INTO `employee_overtimes` (`id`, `resort_id`, `Emp_id`, `Shift_id`, `roster_id`, `parent_attendance_id`, `date`, `start_time`, `end_time`, `total_time`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `notes`, `start_location`, `end_location`, `overtime_type`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(6, 26, 183, 20, 1938, 2153, '2025-12-03', '12:00', '13:00', '01:00', 'pending', NULL, NULL, NULL, NULL, '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12', 'split', 254, 254, '2026-01-01 08:47:15', '2026-01-01 08:47:15'),
(5, 26, 183, 20, 1938, 2153, '2025-12-03', '03:00', '04:00', '01:00', 'pending', NULL, NULL, NULL, NULL, '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12', 'split', 254, 254, '2026-01-01 08:47:15', '2026-01-01 08:47:15'),
(4, 26, 183, 20, 1938, 2151, '2025-12-02', '01:00', '03:00', '02:00', 'pending', NULL, NULL, NULL, NULL, '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', NULL, 'before_shift', 254, 254, '2026-01-01 08:44:59', '2026-01-01 08:44:59'),
(7, 26, 188, 20, 1938, 2154, '2026-01-01', '02:00', '04:00', '02:00', 'pending', NULL, NULL, NULL, NULL, '[\"https:\\/\\/www.google.com\\/maps\\/embed\\/v1\\/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=37.785834,-122.406417&zoom=12\"]', NULL, 'before_shift', 254, 254, '2026-01-01 08:48:29', '2026-01-01 08:48:29'),
(8, 26, 189, 20, 1938, 2157, '2026-01-01', '11:00', '12:00', '01:00', 'pending', NULL, NULL, NULL, NULL, NULL, 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12', 'after_shift', 254, 254, '2026-01-01 10:02:15', '2026-01-01 10:02:15'),
(9, 26, 183, 20, 1951, 2158, '2026-01-01', '11:00', '12:00', '01:00', 'pending', NULL, NULL, NULL, NULL, NULL, 'https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center=23.229891,77.437286&zoom=12', 'after_shift', 254, 254, '2026-01-01 13:32:28', '2026-01-02 13:13:37');

-- --------------------------------------------------------

--
-- Table structure for table `employee_promotions`
--

DROP TABLE IF EXISTS `employee_promotions`;
CREATE TABLE IF NOT EXISTS `employee_promotions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Jd_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `current_position_id` int UNSIGNED NOT NULL,
  `new_position_id` int UNSIGNED DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `new_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_salary` decimal(10,2) DEFAULT NULL,
  `salary_increment_percent` decimal(10,2) DEFAULT NULL,
  `salary_increment_amount` decimal(10,2) DEFAULT NULL,
  `new_salary` decimal(10,2) DEFAULT NULL,
  `updated_benefit_grid` int DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Approved','Rejected','Pending','On Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `follow_up_date` date DEFAULT NULL,
  `letter_dispatched` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_promotions_resort_id_foreign` (`resort_id`),
  KEY `employee_promotions_employee_id_foreign` (`employee_id`),
  KEY `employee_promotions_current_position_id_foreign` (`current_position_id`),
  KEY `employee_promotions_new_position_id_foreign` (`new_position_id`),
  KEY `employee_promotions_jd_id_foreign` (`Jd_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_promotions_approval`
--

DROP TABLE IF EXISTS `employee_promotions_approval`;
CREATE TABLE IF NOT EXISTS `employee_promotions_approval` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint UNSIGNED NOT NULL,
  `status` enum('Approved','Rejected','Pending','On Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `approval_rank` enum('Finance','GM') COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved_by` int UNSIGNED NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_promotions_approval_promotion_id_foreign` (`promotion_id`),
  KEY `employee_promotions_approval_approved_by_foreign` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_reminder`
--

DROP TABLE IF EXISTS `employee_reminder`;
CREATE TABLE IF NOT EXISTS `employee_reminder` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `task` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `days` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex-5',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_reminder_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_reminder`
--

INSERT INTO `employee_reminder` (`id`, `resort_id`, `task`, `days`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(3, 26, 'Exit Interview', '5', 259, 259, '2025-12-13 15:22:45', '2025-12-13 15:22:45'),
(4, 26, 'Handover Form', '5', 259, 259, '2025-12-13 15:22:45', '2025-12-13 15:22:45');

-- --------------------------------------------------------

--
-- Table structure for table `employee_resignation`
--

DROP TABLE IF EXISTS `employee_resignation`;
CREATE TABLE IF NOT EXISTS `employee_resignation` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `reason` bigint UNSIGNED NOT NULL,
  `resignation_date` date NOT NULL,
  `certificate_issue` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no' COMMENT 'resignation certificate has issued',
  `full_and_final_settlement` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no' COMMENT 'full and final settlement has been done',
  `departure_arrangements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Details of departure arrangements made by the employee',
  `last_working_day` date DEFAULT NULL,
  `immediate_release` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `resignation_letter` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Approved','Rejected','On Hold','Withdraw') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `hod_status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `hod_meeting_status` enum('Not Scheduled','Scheduled','Completed','Employee Schedule Confirm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not Scheduled',
  `hod_comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hod_id` int UNSIGNED DEFAULT NULL,
  `hr_status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `hr_meeting_status` enum('Not Scheduled','Scheduled','Completed','Employee Schedule Confirm') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not Scheduled',
  `hr_comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hr_id` bigint UNSIGNED DEFAULT NULL,
  `rejected_reason` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `withdraw_reason` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Deposit_withdraw` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Deposit_Amt` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_resignation_resort_id_foreign` (`resort_id`),
  KEY `employee_resignation_employee_id_foreign` (`employee_id`),
  KEY `employee_resignation_reason_foreign` (`reason`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_resignation`
--

INSERT INTO `employee_resignation` (`id`, `resort_id`, `employee_id`, `reason`, `resignation_date`, `certificate_issue`, `full_and_final_settlement`, `departure_arrangements`, `last_working_day`, `immediate_release`, `comments`, `resignation_letter`, `status`, `hod_status`, `hod_meeting_status`, `hod_comments`, `hod_id`, `hr_status`, `hr_meeting_status`, `hr_comments`, `hr_id`, `rejected_reason`, `withdraw_reason`, `Deposit_withdraw`, `Deposit_Amt`, `created_at`, `updated_at`) VALUES
(1, 26, 189, 1, '2025-12-02', 'no', 'no', '{\"documentVerifed\":\"0\",\"passport_validity\":\"0\",\"international_flight\":\"0\",\"accommodation_arranged\":\"0\",\"transportation_arranged\":\"0\"}', '2025-06-10', 'No', 'tes test', NULL, 'Pending', 'Pending', 'Not Scheduled', NULL, 177, 'Pending', 'Not Scheduled', NULL, 179, NULL, NULL, NULL, NULL, '2025-12-02 00:30:59', '2025-12-02 00:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `employee_resignation_reasons`
--

DROP TABLE IF EXISTS `employee_resignation_reasons`;
CREATE TABLE IF NOT EXISTS `employee_resignation_reasons` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Inactive','Active') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_resignation_reasons_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_resignation_reasons`
--

INSERT INTO `employee_resignation_reasons` (`id`, `resort_id`, `reason`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(20, 26, 'Better Opportunity', 'Active', 259, 259, '2025-11-27 17:31:44', '2025-11-27 17:31:44'),
(21, 26, 'Career growth opportunity', 'Active', 259, 259, '2025-11-27 17:31:44', '2025-11-27 17:31:44'),
(22, 26, 'Relocation', 'Active', 259, 259, '2025-11-27 17:31:44', '2025-11-27 17:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `employee_resignation_withdrawal_configuration`
--

DROP TABLE IF EXISTS `employee_resignation_withdrawal_configuration`;
CREATE TABLE IF NOT EXISTS `employee_resignation_withdrawal_configuration` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `enable_resignation_withdrawal` int NOT NULL DEFAULT '0' COMMENT '0=Disable,1=Enable',
  `required_resignation_withdrawal_reason` int NOT NULL DEFAULT '0' COMMENT '0=Disable,1=Enable',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_resignation_withdrawal_configuration_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_resignation_withdrawal_configuration`
--

INSERT INTO `employee_resignation_withdrawal_configuration` (`id`, `resort_id`, `enable_resignation_withdrawal`, `required_resignation_withdrawal_reason`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(3, 26, 1, 1, 259, 259, '2025-12-13 15:36:44', '2025-12-13 15:36:44');

-- --------------------------------------------------------

--
-- Table structure for table `employee_transfers`
--

DROP TABLE IF EXISTS `employee_transfers`;
CREATE TABLE IF NOT EXISTS `employee_transfers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `current_department_id` int UNSIGNED NOT NULL,
  `target_department_id` int UNSIGNED NOT NULL,
  `current_position_id` int UNSIGNED NOT NULL,
  `target_position_id` int UNSIGNED NOT NULL,
  `reason_for_transfer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date NOT NULL,
  `transfer_status` enum('Permanent','Temporary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Permanent',
  `additional_notes` text COLLATE utf8mb4_unicode_ci,
  `reporting_manager` int UNSIGNED DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','On Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `letter_dispatched` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_transfers_resort_id_foreign` (`resort_id`),
  KEY `employee_transfers_employee_id_foreign` (`employee_id`),
  KEY `employee_transfers_current_department_id_foreign` (`current_department_id`),
  KEY `employee_transfers_target_department_id_foreign` (`target_department_id`),
  KEY `employee_transfers_current_position_id_foreign` (`current_position_id`),
  KEY `employee_transfers_target_position_id_foreign` (`target_position_id`),
  KEY `employee_transfers_reporting_manager_foreign` (`reporting_manager`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_transfers_approval`
--

DROP TABLE IF EXISTS `employee_transfers_approval`;
CREATE TABLE IF NOT EXISTS `employee_transfers_approval` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint UNSIGNED NOT NULL,
  `status` enum('Pending','Approved','Rejected','On Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `approval_rank` enum('Finance','GM') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Approval rank for the transfer request',
  `approved_by` int UNSIGNED NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_transfers_approval_transfer_id_foreign` (`transfer_id`),
  KEY `employee_transfers_approval_approved_by_foreign` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_travel_passes`
--

DROP TABLE IF EXISTS `employee_travel_passes`;
CREATE TABLE IF NOT EXISTS `employee_travel_passes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `leave_request_id` int UNSIGNED DEFAULT NULL,
  `transportation` bigint UNSIGNED DEFAULT NULL,
  `arrival_date` date DEFAULT NULL,
  `arrival_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_mode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `departure_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departure_mode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departure_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `employee_departure_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_arrival_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_travel_passes_resort_id_foreign` (`resort_id`),
  KEY `employee_travel_passes_employee_id_foreign` (`employee_id`),
  KEY `employee_travel_passes_leave_request_id_foreign` (`leave_request_id`),
  KEY `employee_travel_passes_transportation_foreign` (`transportation`)
) ENGINE=InnoDB AUTO_INCREMENT=450 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_travel_passes`
--

INSERT INTO `employee_travel_passes` (`id`, `resort_id`, `employee_id`, `leave_request_id`, `transportation`, `arrival_date`, `arrival_time`, `arrival_mode`, `arrival_reason`, `departure_date`, `departure_time`, `departure_mode`, `departure_reason`, `status`, `employee_departure_status`, `employee_arrival_status`, `created_at`, `updated_at`) VALUES
(438, 26, 189, NULL, NULL, '2025-12-11', NULL, '1', NULL, '2025-12-10', NULL, '1', NULL, 'Pending', NULL, NULL, '2025-12-10 23:56:27', '2025-12-10 23:56:27'),
(442, 26, 177, 242, 5, '2025-12-21', NULL, NULL, NULL, '2025-12-20', '13:00', NULL, NULL, 'Pending', NULL, NULL, '2025-12-17 14:44:02', '2025-12-17 14:44:02'),
(445, 26, 177, 245, NULL, '2025-12-28', '19:36', '6', 'relax', '2025-12-28', '19:34', '6', 'trsth', 'Pending', NULL, NULL, '2025-12-17 19:37:17', '2025-12-17 19:37:17'),
(448, 26, 177, 248, NULL, '2025-12-25', '22:16', '5', 'going to mahabaleshwar', '2025-12-25', '22:16', '5', 'going to mahabaleshwar', 'Pending', NULL, NULL, '2025-12-18 14:33:09', '2025-12-18 14:33:09'),
(449, 26, 189, NULL, NULL, '2025-12-19', NULL, '1', NULL, '2025-12-18', NULL, '1', NULL, 'Approved', NULL, NULL, '2025-12-18 15:29:35', '2025-12-18 16:35:00');

-- --------------------------------------------------------

--
-- Table structure for table `employee_travel_pass_assign`
--

DROP TABLE IF EXISTS `employee_travel_pass_assign`;
CREATE TABLE IF NOT EXISTS `employee_travel_pass_assign` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `travel_pass_id` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_travel_pass_assign_resort_id_foreign` (`resort_id`),
  KEY `employee_travel_pass_assign_travel_pass_id_foreign` (`travel_pass_id`),
  KEY `employee_travel_pass_assign_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_travel_pass_status`
--

DROP TABLE IF EXISTS `employee_travel_pass_status`;
CREATE TABLE IF NOT EXISTS `employee_travel_pass_status` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `travel_pass_id` bigint UNSIGNED NOT NULL,
  `approver_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approver_rank` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `emergency_cancel_status` enum('Cancel') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_travel_pass_status_travel_pass_id_foreign` (`travel_pass_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1226 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_travel_pass_status`
--

INSERT INTO `employee_travel_pass_status` (`id`, `travel_pass_id`, `approver_id`, `approver_rank`, `status`, `emergency_cancel_status`, `comments`, `approved_at`, `created_at`, `updated_at`) VALUES
(1215, 438, '177', '2', 'Rejected', NULL, 'there is a lot of work', '2025-12-18 15:03:02', '2025-12-10 23:56:27', '2025-12-18 15:03:02'),
(1221, 445, '177', '2', 'Pending', NULL, NULL, NULL, '2025-12-17 19:37:17', '2025-12-17 19:37:17'),
(1224, 448, '177', '2', 'Pending', NULL, NULL, NULL, '2025-12-18 14:33:09', '2025-12-18 14:33:09'),
(1225, 449, '177', '2', 'Approved', NULL, NULL, '2025-12-18 16:35:00', '2025-12-18 15:29:35', '2025-12-18 16:35:00');

-- --------------------------------------------------------

--
-- Table structure for table `escalation_days`
--

DROP TABLE IF EXISTS `escalation_days`;
CREATE TABLE IF NOT EXISTS `escalation_days` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `EscalationDay` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escalation_days_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_form`
--

DROP TABLE IF EXISTS `evaluation_form`;
CREATE TABLE IF NOT EXISTS `evaluation_form` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `form_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluation_form_resort_id_foreign` (`resort_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_form_responses`
--

DROP TABLE IF EXISTS `evaluation_form_responses`;
CREATE TABLE IF NOT EXISTS `evaluation_form_responses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` int UNSIGNED NOT NULL,
  `training_id` int UNSIGNED NOT NULL,
  `participant_id` int UNSIGNED NOT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluation_form_responses_form_id_foreign` (`form_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reminder_days` int NOT NULL DEFAULT '7',
  `events_for` enum('organization','department','employee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `employees` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','accept','decline') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ewt_tax_brackets`
--

DROP TABLE IF EXISTS `ewt_tax_brackets`;
CREATE TABLE IF NOT EXISTS `ewt_tax_brackets` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `min_salary` decimal(10,2) NOT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ewt_tax_brackets`
--

INSERT INTO `ewt_tax_brackets` (`id`, `min_salary`, `max_salary`, `tax_rate`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(7, 0.00, 60000.00, 0.00, 11, 11, '2025-12-06 13:21:23', '2025-12-06 13:21:23'),
(8, 60001.00, 100000.00, 5.50, 11, 11, '2025-12-06 13:21:44', '2025-12-06 13:21:44'),
(9, 100001.00, 150000.00, 8.00, 11, 11, '2025-12-06 13:22:01', '2025-12-06 13:22:01'),
(10, 150001.00, 200000.00, 12.00, 11, 11, '2025-12-06 13:22:14', '2025-12-06 13:22:14'),
(11, 200001.00, 2000000.00, 15.00, 11, 11, '2025-12-06 13:22:52', '2025-12-06 13:22:52');

-- --------------------------------------------------------

--
-- Table structure for table `exit_clearance_form`
--

DROP TABLE IF EXISTS `exit_clearance_form`;
CREATE TABLE IF NOT EXISTS `exit_clearance_form` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `form_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type of exit clearance form',
  `department_id` int UNSIGNED DEFAULT NULL,
  `form_type` enum('department','employee') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'department',
  `form_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exit_clearance_form_resort_id_foreign` (`resort_id`),
  KEY `exit_clearance_form_department_id_foreign` (`department_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exit_clearance_form`
--

INSERT INTO `exit_clearance_form` (`id`, `resort_id`, `form_name`, `type`, `department_id`, `form_type`, `form_structure`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 26, 'F&B Services Department Exit Clearance Checklist', NULL, 80, 'department', '[{\"type\":\"header\",\"subtype\":\"h1\",\"label\":\"F&amp;B SERVICES DEPARTMENT - EMPLOYEE EXIT CLEARANCE FORM\",\"access\":false},{\"type\":\"text\",\"required\":false,\"label\":\"Employee Name\",\"className\":\"form-control\",\"name\":\"text-1765620702355-0\",\"access\":false,\"subtype\":\"text\"},{\"type\":\"date\",\"required\":false,\"label\":\"Last Working Day\",\"className\":\"form-control\",\"name\":\"date-1765620739554-0\",\"access\":false,\"subtype\":\"date\"},{\"type\":\"checkbox-group\",\"required\":false,\"label\":\"F&amp;B Department Clearances Required\",\"toggle\":false,\"inline\":false,\"name\":\"checkbox-group-1765620778303-0\",\"access\":false,\"other\":false,\"values\":[{\"label\":\"Kitchen Equipment & Utensils Returned\",\"value\":\"option-1\",\"selected\":true},{\"label\":\"Food Safety Certificate Cleared\",\"value\":\"\",\"selected\":false},{\"label\":\"Inventory Signed Off & Uniforms Returned\",\"value\":\"\",\"selected\":false}]},{\"type\":\"file\",\"required\":false,\"label\":\"Clearance Verification Document\",\"className\":\"form-control\",\"name\":\"file-1765620864087-0\",\"access\":false,\"multiple\":false}]', 259, 259, '2025-12-13 15:45:17', '2025-12-13 15:45:17'),
(2, 26, 'Accounting Department Exit Clearance Form', NULL, 79, 'department', '[{\"type\":\"header\",\"subtype\":\"h1\",\"label\":\"ACCOUNTING DEPARTMENT - EMPLOYEE EXIT CLEARANCE &amp; FINANCIAL SETTLEMENT FORM\",\"access\":false},{\"type\":\"text\",\"required\":false,\"label\":\"Employee Name\",\"className\":\"form-control\",\"name\":\"text-1765621161892-0\",\"access\":false,\"subtype\":\"text\"},{\"type\":\"number\",\"required\":false,\"label\":\"Advance Loan/Amount Outstanding (MVR)\",\"className\":\"form-control\",\"name\":\"number-1765621219218-0\",\"access\":false,\"subtype\":\"number\"},{\"type\":\"checkbox-group\",\"required\":false,\"label\":\"Accounting Department Financial Clearances\",\"toggle\":false,\"inline\":false,\"name\":\"checkbox-group-1765621276277-0\",\"access\":false,\"other\":false,\"values\":[{\"label\":\"All Outstanding Advance Loans/Dues Settled\",\"value\":\"option-1\",\"selected\":true},{\"label\":\"Financial Records & Documents Handed Over\",\"value\":\"\",\"selected\":false},{\"label\":\"Final Salary & Gratuity Settlement Confirmed\",\"value\":\"\",\"selected\":false}]}]', 259, 259, '2025-12-13 15:55:30', '2025-12-13 15:55:30'),
(3, 26, 'Employee Handover & Knowledge Transfer Form', 'handover', NULL, 'employee', '[{\"type\":\"header\",\"subtype\":\"h1\",\"label\":\"EMPLOYEE HANDOVER &amp; KNOWLEDGE TRANSFER CHECKLIST\",\"access\":false},{\"type\":\"text\",\"required\":false,\"label\":\"Employee Name\",\"className\":\"form-control\",\"name\":\"text-1765621767148-0\",\"access\":false,\"subtype\":\"text\"},{\"type\":\"checkbox-group\",\"required\":false,\"label\":\"Handover Items\",\"toggle\":false,\"inline\":false,\"name\":\"checkbox-group-1765621839191-0\",\"access\":false,\"other\":false,\"values\":[{\"label\":\"All Responsibilities Handed Over\",\"value\":\"option-1\",\"selected\":true},{\"label\":\"Documentation Provided\",\"value\":\"\",\"selected\":false},{\"label\":\"Knowledge Transfer Completed\",\"value\":\"\",\"selected\":false}]}]', 259, 259, '2025-12-13 16:03:22', '2025-12-13 16:03:22'),
(4, 26, 'Employee Exit Interview Form', 'exit_interview', NULL, 'employee', '[{\"type\":\"header\",\"subtype\":\"h1\",\"label\":\"EMPLOYEE EXIT INTERVIEW QUESTIONNAIRE\",\"access\":false},{\"type\":\"text\",\"required\":false,\"label\":\"Employee Name\",\"className\":\"form-control\",\"name\":\"text-1765622274004-0\",\"access\":false,\"subtype\":\"text\"},{\"type\":\"radio-group\",\"required\":false,\"label\":\"Primary Reason for Leaving\",\"inline\":false,\"name\":\"radio-group-1765622294804-0\",\"access\":false,\"other\":false,\"values\":[{\"label\":\"Better Opportunity or Career Growth\",\"value\":\"option-1\",\"selected\":false},{\"label\":\"Higher Salary / Compensation\",\"value\":\"option-2\",\"selected\":false},{\"label\":\"Relocation / Personal Reasons\",\"value\":\"option-3\",\"selected\":false}]},{\"type\":\"radio-group\",\"required\":false,\"label\":\"Overall Satisfaction with Organization\",\"inline\":false,\"name\":\"radio-group-1765622332246-0\",\"access\":false,\"other\":false,\"values\":[{\"label\":\"Very Satisfied\",\"value\":\"option-1\",\"selected\":false},{\"label\":\"Neutral / Neither Satisfied Nor Dissatisfied\",\"value\":\"option-2\",\"selected\":false},{\"label\":\"Very Dissatisfied\",\"value\":\"option-3\",\"selected\":false}]},{\"type\":\"textarea\",\"required\":false,\"label\":\"Suggestions for Company Improvement &amp; Additional Comments\",\"className\":\"form-control\",\"name\":\"textarea-1765622375254-0\",\"access\":false,\"subtype\":\"textarea\"}]', 259, 259, '2025-12-13 16:10:05', '2025-12-13 16:10:05'),
(5, 26, 'Employee Exit Clearance Checklist', 'exit_clearance', NULL, 'employee', '[{\"type\":\"header\",\"subtype\":\"h1\",\"label\":\"EMPLOYEE EXIT CLEARANCE FORM\",\"access\":false},{\"type\":\"text\",\"required\":false,\"label\":\"Employee Name\",\"className\":\"form-control\",\"name\":\"text-1765622517383-0\",\"access\":false,\"subtype\":\"text\"},{\"type\":\"date\",\"required\":false,\"label\":\"Date of Exit\",\"className\":\"form-control\",\"name\":\"date-1765622548650-0\",\"access\":false,\"subtype\":\"date\"},{\"type\":\"checkbox-group\",\"required\":false,\"label\":\"Exit Clearance Checklist\",\"toggle\":false,\"inline\":false,\"name\":\"checkbox-group-1765622580117-0\",\"access\":false,\"other\":false,\"values\":[{\"label\":\"All Company Property Returned\",\"value\":\"option-1\",\"selected\":true},{\"label\":\"No Outstanding Debts/Dues\",\"value\":\"\",\"selected\":false},{\"label\":\"All Final Payments Received\",\"value\":\"\",\"selected\":false}]},{\"type\":\"textarea\",\"required\":false,\"label\":\"Additional Notes/Comments\",\"className\":\"form-control\",\"name\":\"textarea-1765622647534-0\",\"access\":false,\"subtype\":\"textarea\"}]', 259, 259, '2025-12-13 16:14:46', '2025-12-13 16:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `exit_clearance_form_assignments`
--

DROP TABLE IF EXISTS `exit_clearance_form_assignments`;
CREATE TABLE IF NOT EXISTS `exit_clearance_form_assignments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED DEFAULT NULL,
  `department_id` int UNSIGNED DEFAULT NULL,
  `reminder_frequency` int DEFAULT NULL,
  `emp_resignation_id` bigint UNSIGNED NOT NULL,
  `form_id` bigint UNSIGNED NOT NULL,
  `assigned_to_type` enum('employee','department') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `assigned_to_id` int UNSIGNED DEFAULT NULL,
  `assigned_by` int UNSIGNED DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `deadline_date` date DEFAULT NULL,
  `status` enum('Pending','Completed','Overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exit_clearance_form_assignments_emp_resignation_id_foreign` (`emp_resignation_id`),
  KEY `exit_clearance_form_assignments_form_id_foreign` (`form_id`),
  KEY `exit_clearance_form_assignments_assigned_to_id_foreign` (`assigned_to_id`),
  KEY `exit_clearance_form_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `exit_clearance_form_assignments_resort_id_foreign` (`resort_id`),
  KEY `exit_clearance_form_assignments_department_id_foreign` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_clearance_form_responses`
--

DROP TABLE IF EXISTS `exit_clearance_form_responses`;
CREATE TABLE IF NOT EXISTS `exit_clearance_form_responses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint UNSIGNED NOT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `submitted_by` int UNSIGNED DEFAULT NULL,
  `submitted_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exit_clearance_form_responses_assignment_id_foreign` (`assignment_id`),
  KEY `exit_clearance_form_responses_submitted_by_foreign` (`submitted_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility_tour_categories`
--

DROP TABLE IF EXISTS `facility_tour_categories`;
CREATE TABLE IF NOT EXISTS `facility_tour_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `thumbnail_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `modified_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facility_tour_categories_slug_unique` (`slug`),
  KEY `facility_tour_categories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility_tour_images`
--

DROP TABLE IF EXISTS `facility_tour_images`;
CREATE TABLE IF NOT EXISTS `facility_tour_images` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `facility_tour_category_id` bigint UNSIGNED NOT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facility_tour_images_facility_tour_category_id_foreign` (`facility_tour_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `filemangement_systems`
--

DROP TABLE IF EXISTS `filemangement_systems`;
CREATE TABLE IF NOT EXISTS `filemangement_systems` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Folder_unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `UnderON` int NOT NULL DEFAULT '0',
  `Folder_Name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Folder_Type` enum('uncategorized','categorized') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uncategorized',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `filemangement_systems_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_permissions`
--

DROP TABLE IF EXISTS `file_permissions`;
CREATE TABLE IF NOT EXISTS `file_permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Department_id` int DEFAULT NULL,
  `Position_id` int DEFAULT NULL,
  `file_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_permissions_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_versions`
--

DROP TABLE IF EXISTS `file_versions`;
CREATE TABLE IF NOT EXISTS `file_versions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `file_id` bigint UNSIGNED NOT NULL,
  `version_number` int NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_versions_file_id_foreign` (`file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_settlements`
--

DROP TABLE IF EXISTS `final_settlements`;
CREATE TABLE IF NOT EXISTS `final_settlements` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `pension` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `leave_balance` decimal(10,2) DEFAULT NULL,
  `leave_encashment` decimal(10,2) DEFAULT NULL,
  `loan_payment` decimal(10,2) DEFAULT NULL,
  `basic_salary` decimal(10,2) DEFAULT NULL,
  `service_charge` decimal(10,2) DEFAULT NULL,
  `total_earnings` decimal(10,2) DEFAULT NULL,
  `total_deductions` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_working_date` date DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `reference_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','review','finalized') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `final_settlements_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_settlement_deductions`
--

DROP TABLE IF EXISTS `final_settlement_deductions`;
CREATE TABLE IF NOT EXISTS `final_settlement_deductions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `final_settlement_id` int UNSIGNED NOT NULL,
  `deduction_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_unit` enum('MVR','USD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MVR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `final_settlement_deductions_final_settlement_id_foreign` (`final_settlement_id`),
  KEY `final_settlement_deductions_deduction_id_foreign` (`deduction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_settlement_earnings`
--

DROP TABLE IF EXISTS `final_settlement_earnings`;
CREATE TABLE IF NOT EXISTS `final_settlement_earnings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `final_settlement_id` int UNSIGNED NOT NULL,
  `earning_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_unit` enum('MVR','USD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MVR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `final_settlement_earnings_final_settlement_id_foreign` (`final_settlement_id`),
  KEY `final_settlement_earnings_earning_id_foreign` (`earning_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_appeal_deadline_models`
--

DROP TABLE IF EXISTS `grievance_appeal_deadline_models`;
CREATE TABLE IF NOT EXISTS `grievance_appeal_deadline_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `AppealDeadLine` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `MemberId_or_CommitteeId` int DEFAULT NULL,
  `Appeal_Type` enum('Committee','Individual') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Proccess` enum('on','off') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_appeal_deadline_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_categories`
--

DROP TABLE IF EXISTS `grievance_categories`;
CREATE TABLE IF NOT EXISTS `grievance_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Category_Name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Category_Description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_categories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grievance_categories`
--

INSERT INTO `grievance_categories` (`id`, `resort_id`, `Category_Name`, `Category_Description`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(30, 26, 'Test', 'Test', 259, 259, '2025-12-11 18:46:01', '2025-12-11 18:46:01');

-- --------------------------------------------------------

--
-- Table structure for table `grievance_category_and_subcat_models`
--

DROP TABLE IF EXISTS `grievance_category_and_subcat_models`;
CREATE TABLE IF NOT EXISTS `grievance_category_and_subcat_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grievance_Cat_id` bigint UNSIGNED NOT NULL,
  `Gri_Sub_cat_id` bigint UNSIGNED NOT NULL,
  `Priority_Level` enum('High','Low','Medium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_category_and_subcat_models_grievance_cat_id_foreign` (`Grievance_Cat_id`),
  KEY `grievance_category_and_subcat_models_gri_sub_cat_id_foreign` (`Gri_Sub_cat_id`),
  KEY `grievance_category_and_subcat_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_committee_member_children`
--

DROP TABLE IF EXISTS `grievance_committee_member_children`;
CREATE TABLE IF NOT EXISTS `grievance_committee_member_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Parent_id` bigint UNSIGNED NOT NULL,
  `Committee_Member_Id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_committee_member_children_parent_id_foreign` (`Parent_id`),
  KEY `grievance_committee_member_children_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_committee_member_parents`
--

DROP TABLE IF EXISTS `grievance_committee_member_parents`;
CREATE TABLE IF NOT EXISTS `grievance_committee_member_parents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grivance_CommitteeName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_committee_member_parents_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_delegation_rule_models`
--

DROP TABLE IF EXISTS `grievance_delegation_rule_models`;
CREATE TABLE IF NOT EXISTS `grievance_delegation_rule_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grievance_Cat_id` bigint UNSIGNED NOT NULL,
  `delegation_rule` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_delegation_rule_models_grievance_cat_id_foreign` (`Grievance_Cat_id`),
  KEY `grievance_delegation_rule_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_non_retaliations`
--

DROP TABLE IF EXISTS `grievance_non_retaliations`;
CREATE TABLE IF NOT EXISTS `grievance_non_retaliations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `timeframe_submission` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reminder_frequency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reminder_default_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NonRetaliationFeedback` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `ReminderCompleteFeedback` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_non_retaliations_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_right_to_be_accompanieds`
--

DROP TABLE IF EXISTS `grievance_right_to_be_accompanieds`;
CREATE TABLE IF NOT EXISTS `grievance_right_to_be_accompanieds` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Right_to_be_accompanied` enum('yes','no') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_right_to_be_accompanieds_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grievance_subcategories`
--

DROP TABLE IF EXISTS `grievance_subcategories`;
CREATE TABLE IF NOT EXISTS `grievance_subcategories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grievance_Cat_id` bigint UNSIGNED NOT NULL,
  `Category_Name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Sub_Category_Name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Sub_Category_Descr` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_subcategories_grievance_cat_id_foreign` (`Grievance_Cat_id`),
  KEY `grievance_subcategories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grievance_subcategories`
--

INSERT INTO `grievance_subcategories` (`id`, `resort_id`, `Grievance_Cat_id`, `Category_Name`, `Sub_Category_Name`, `Sub_Category_Descr`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(37, 26, 30, NULL, 'Sub Test', NULL, 259, 259, '2025-12-11 18:46:50', '2025-12-11 18:46:50');

-- --------------------------------------------------------

--
-- Table structure for table `grievance_templete_models`
--

DROP TABLE IF EXISTS `grievance_templete_models`;
CREATE TABLE IF NOT EXISTS `grievance_templete_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grievance_Cat_id` int DEFAULT NULL,
  `Grievance_Temp_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Grievance_Temp_Structure` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_templete_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_escaltion_models`
--

DROP TABLE IF EXISTS `grivance_escaltion_models`;
CREATE TABLE IF NOT EXISTS `grivance_escaltion_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grievance_Cat_id` int DEFAULT NULL,
  `resolved_duration` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_escaltion_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_investigation_child_models`
--

DROP TABLE IF EXISTS `grivance_investigation_child_models`;
CREATE TABLE IF NOT EXISTS `grivance_investigation_child_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `investigation_p_id` bigint UNSIGNED NOT NULL,
  `follow_up_action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `follow_up_description` longtext COLLATE utf8mb4_unicode_ci,
  `investigation_stage` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Grivance_Eexplination_description` longtext COLLATE utf8mb4_unicode_ci,
  `resolution_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inves_find_recommendations` longtext COLLATE utf8mb4_unicode_ci,
  `resolution_note` longtext COLLATE utf8mb4_unicode_ci,
  `Committee_member_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_investigation_child_models_investigation_p_id_foreign` (`investigation_p_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_investigation_models`
--

DROP TABLE IF EXISTS `grivance_investigation_models`;
CREATE TABLE IF NOT EXISTS `grivance_investigation_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grievance_s_id` bigint UNSIGNED NOT NULL,
  `Committee_id` int NOT NULL,
  `inves_start_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolution_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `investigation_files` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_investigation_models_grievance_s_id_foreign` (`Grievance_s_id`),
  KEY `grivance_investigation_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_investigation_parent_models`
--

DROP TABLE IF EXISTS `grivance_investigation_parent_models`;
CREATE TABLE IF NOT EXISTS `grivance_investigation_parent_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Disciplinary_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Committee_member_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invesigation_date` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolution_date` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `investigation_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outcome_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_investigation_parent_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_key_people`
--

DROP TABLE IF EXISTS `grivance_key_people`;
CREATE TABLE IF NOT EXISTS `grivance_key_people` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `emp_ids` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_key_people_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_resoultion_time_line_models`
--

DROP TABLE IF EXISTS `grivance_resoultion_time_line_models`;
CREATE TABLE IF NOT EXISTS `grivance_resoultion_time_line_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `HighPriority` int DEFAULT NULL,
  `MediumPriority` int DEFAULT NULL,
  `LowPriority` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_resoultion_time_line_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_submission_models`
--

DROP TABLE IF EXISTS `grivance_submission_models`;
CREATE TABLE IF NOT EXISTS `grivance_submission_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Grivance_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Committee_id` int NOT NULL,
  `Grivance_Cat_id` int NOT NULL,
  `Grivance_Sub_cat` int NOT NULL,
  `Employee_id` int NOT NULL,
  `date` date DEFAULT NULL,
  `Grivance_description` longtext COLLATE utf8mb4_unicode_ci,
  `Grivance_date_time` datetime DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `witness_id` int DEFAULT NULL,
  `Grivance_Eexplination_description` longtext COLLATE utf8mb4_unicode_ci,
  `Attachements` text COLLATE utf8mb4_unicode_ci,
  `Grivance_Submission_Type` enum('Yes','No','NotApplicable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `grievance_informally` enum('Yes','No','NotApplicable') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_review','resolved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `Priority` enum('High','Medium','Low') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `Assigned` enum('Yes','No','DeliverToHr') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `SentToGM` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `outcome_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_taken` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Request_Identity_Disclosure` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Gm_Decision` enum('Approved','Rejacted') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `RequestforStatment` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `Gm_Resoan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grivance_submission_models_grivance_id_unique` (`Grivance_id`),
  KEY `grivance_submission_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grivance_submission_witnesses`
--

DROP TABLE IF EXISTS `grivance_submission_witnesses`;
CREATE TABLE IF NOT EXISTS `grivance_submission_witnesses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `G_S_Parent_id` bigint UNSIGNED NOT NULL,
  `Witness_id` int NOT NULL,
  `Wintness_Status` enum('Active','In-Active') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grivance_submission_witnesses_g_s_parent_id_foreign` (`G_S_Parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hiring_sources`
--

DROP TABLE IF EXISTS `hiring_sources`;
CREATE TABLE IF NOT EXISTS `hiring_sources` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `source_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `colour` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hiring_sources_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_schedules`
--

DROP TABLE IF EXISTS `housekeeping_schedules`;
CREATE TABLE IF NOT EXISTS `housekeeping_schedules` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `available_a_id` bigint UNSIGNED NOT NULL,
  `BuildingName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Floor` int DEFAULT NULL,
  `RoomNo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Assigned_To` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_instructions` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clean_type` enum('Deep Cleaning','Standard') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Open','On-Hold','Assigned','In-Progress','Complete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `modified_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `housekeeping_schedules_resort_id_foreign` (`resort_id`),
  KEY `housekeeping_schedules_available_a_id_foreign` (`available_a_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_schedules_img`
--

DROP TABLE IF EXISTS `housekeeping_schedules_img`;
CREATE TABLE IF NOT EXISTS `housekeeping_schedules_img` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `housekeeping_id` bigint UNSIGNED NOT NULL,
  `emp_id` int UNSIGNED NOT NULL,
  `document_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `housekeeping_schedules_img_resort_id_foreign` (`resort_id`),
  KEY `housekeeping_schedules_img_housekeeping_id_foreign` (`housekeeping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_reminder_request_mannings`
--

DROP TABLE IF EXISTS `hr_reminder_request_mannings`;
CREATE TABLE IF NOT EXISTS `hr_reminder_request_mannings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminder_message_subject` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

DROP TABLE IF EXISTS `incidents`;
CREATE TABLE IF NOT EXISTS `incidents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `incident_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reporter_id` int UNSIGNED DEFAULT NULL,
  `victims` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time NOT NULL,
  `location` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` bigint UNSIGNED NOT NULL,
  `subcategory` bigint UNSIGNED NOT NULL,
  `isWitness` enum('Yes','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `involved_employees` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachements` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('Low','Medium','High') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Low',
  `assigned_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `severity` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outcome_type` bigint UNSIGNED DEFAULT NULL,
  `preventive_measures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `action_taken` bigint UNSIGNED DEFAULT NULL,
  `approval` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `resolved_by` int DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_resort_id_foreign` (`resort_id`),
  KEY `incidents_reporter_id_foreign` (`reporter_id`),
  KEY `incidents_category_foreign` (`category`),
  KEY `incidents_subcategory_foreign` (`subcategory`),
  KEY `incidents_outcome_type_foreign` (`outcome_type`),
  KEY `incidents_action_taken_foreign` (`action_taken`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `resort_id`, `incident_id`, `incident_name`, `description`, `reporter_id`, `victims`, `incident_date`, `incident_time`, `location`, `category`, `subcategory`, `isWitness`, `involved_employees`, `attachements`, `priority`, `assigned_to`, `comments`, `severity`, `outcome_type`, `preventive_measures`, `action_taken`, `approval`, `approved_by`, `approved_at`, `approval_remarks`, `status`, `created_by`, `modified_by`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 26, 'YOPN7093', 'Test', 'test', 177, '189', '2025-12-11', '12:07:00', 'test', 29, 27, 'Yes', '189', '[]', 'Low', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Reported', 248, 248, NULL, NULL, '2025-12-11 00:08:14', '2025-12-11 00:08:14'),
(2, 26, 'VEF15000', 'app not working', 'the employee felt down from the stairs while he was working.', 189, '189', '2025-12-15', '07:18:00', 'back of the house', 29, 27, 'Yes', '174', '[]', 'Low', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Reported', 260, 260, NULL, NULL, '2025-12-15 18:55:59', '2025-12-15 18:55:59'),
(3, 26, 'BPSL0141', 'multi test', 'ere', 189, '179', '2025-12-16', '12:12:00', 'red', 30, 29, 'Yes', '176', '[]', 'Low', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Reported', 260, 260, NULL, NULL, '2025-12-16 00:13:13', '2025-12-16 00:13:13'),
(4, 26, '9QOX9630', 'multi test', 'ere', 189, '179', '2025-12-16', '12:12:00', 'red', 30, 29, 'Yes', '176', '[]', 'Low', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Reported', 260, 260, NULL, NULL, '2025-12-16 00:13:36', '2025-12-16 00:13:36');

-- --------------------------------------------------------

--
-- Table structure for table `incidents_investigation`
--

DROP TABLE IF EXISTS `incidents_investigation`;
CREATE TABLE IF NOT EXISTS `incidents_investigation` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` bigint UNSIGNED NOT NULL,
  `committee_id` bigint UNSIGNED DEFAULT NULL,
  `police_date` date NOT NULL,
  `police_time` time NOT NULL,
  `mdf_notified` enum('yes','no','not_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `mndf_date` date NOT NULL,
  `mndf_time` time NOT NULL,
  `fire_rescue_notified` enum('yes','no','not_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `fire_rescue_date` date NOT NULL,
  `fire_rescue_time` time NOT NULL,
  `Ministry_notified` enum('yes','no','not_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `Ministry_notified_date` date DEFAULT NULL,
  `Ministry_time` time DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expected_resolution_date` date DEFAULT NULL,
  `investigation_findings` text COLLATE utf8mb4_unicode_ci,
  `folloup_action` bigint UNSIGNED DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `police_notified` enum('yes','no','not_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `added_by_member_id` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_investigation_incident_id_foreign` (`incident_id`),
  KEY `incidents_investigation_committee_id_foreign` (`committee_id`),
  KEY `incidents_investigation_added_by_member_id_foreign` (`added_by_member_id`),
  KEY `incidents_investigation_folloup_action_foreign` (`folloup_action`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents_investigation_meetings`
--

DROP TABLE IF EXISTS `incidents_investigation_meetings`;
CREATE TABLE IF NOT EXISTS `incidents_investigation_meetings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` bigint UNSIGNED NOT NULL,
  `meeting_subject` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_agenda` text COLLATE utf8mb4_unicode_ci,
  `attachments` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_investigation_meetings_incident_id_foreign` (`incident_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents_investigation_meetings_participants`
--

DROP TABLE IF EXISTS `incidents_investigation_meetings_participants`;
CREATE TABLE IF NOT EXISTS `incidents_investigation_meetings_participants` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint UNSIGNED NOT NULL,
  `participant_id` int UNSIGNED NOT NULL,
  `participant_role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_investigation_meetings_participants_meeting_id_foreign` (`meeting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents_meetings_external_participants`
--

DROP TABLE IF EXISTS `incidents_meetings_external_participants`;
CREATE TABLE IF NOT EXISTS `incidents_meetings_external_participants` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint UNSIGNED NOT NULL,
  `participant_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_meetings_external_participants_meeting_id_foreign` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents_witness`
--

DROP TABLE IF EXISTS `incidents_witness`;
CREATE TABLE IF NOT EXISTS `incidents_witness` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` bigint UNSIGNED NOT NULL,
  `witness_id` int UNSIGNED NOT NULL,
  `witness_statements` text COLLATE utf8mb4_unicode_ci,
  `witness_statement_file` text COLLATE utf8mb4_unicode_ci,
  `witness_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incidents_witness_incident_id_foreign` (`incident_id`),
  KEY `incidents_witness_witness_id_foreign` (`witness_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incidents_witness`
--

INSERT INTO `incidents_witness` (`id`, `incident_id`, `witness_id`, `witness_statements`, `witness_statement_file`, `witness_status`, `created_at`, `updated_at`) VALUES
(37, 1, 189, NULL, NULL, NULL, '2025-12-11 00:08:14', '2025-12-11 00:08:14'),
(38, 2, 184, NULL, NULL, NULL, '2025-12-15 18:55:59', '2025-12-15 18:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `incident_actions_taken`
--

DROP TABLE IF EXISTS `incident_actions_taken`;
CREATE TABLE IF NOT EXISTS `incident_actions_taken` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `action_taken` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_actions_taken_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_actions_taken`
--

INSERT INTO `incident_actions_taken` (`id`, `resort_id`, `action_taken`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(13, 26, 'Terminate', 259, 259, '2025-12-15 19:16:37', '2025-12-15 19:16:37'),
(14, 26, 'Consultation', 259, 259, '2025-12-15 19:16:37', '2025-12-15 19:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `incident_categories`
--

DROP TABLE IF EXISTS `incident_categories`;
CREATE TABLE IF NOT EXISTS `incident_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `category_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_categories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_categories`
--

INSERT INTO `incident_categories` (`id`, `resort_id`, `category_name`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(29, 26, 'Workplace Injuries', 259, 259, '2025-11-27 17:04:04', '2025-11-27 17:04:04'),
(30, 26, 'Online / Digital Harassment', 259, 259, '2025-11-27 17:09:47', '2025-11-27 17:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `incident_committee`
--

DROP TABLE IF EXISTS `incident_committee`;
CREATE TABLE IF NOT EXISTS `incident_committee` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `commitee_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_committee_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_committee`
--

INSERT INTO `incident_committee` (`id`, `resort_id`, `commitee_name`, `date`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(11, 26, 'Safety Committee', '2025-12-15', 259, 259, '2025-12-15 19:15:07', '2025-12-15 19:15:07'),
(12, 26, 'Sexual Harassment Committee', '2025-12-15', 259, 259, '2025-12-15 19:15:07', '2025-12-15 19:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `incident_committee_members`
--

DROP TABLE IF EXISTS `incident_committee_members`;
CREATE TABLE IF NOT EXISTS `incident_committee_members` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `commitee_id` bigint UNSIGNED NOT NULL,
  `member_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_committee_members_commitee_id_foreign` (`commitee_id`),
  KEY `incident_committee_members_member_id_foreign` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_committee_members`
--

INSERT INTO `incident_committee_members` (`id`, `commitee_id`, `member_id`, `created_at`, `updated_at`) VALUES
(13, 11, 171, '2025-12-15 19:15:07', '2025-12-15 19:15:07'),
(14, 11, 187, '2025-12-15 19:15:07', '2025-12-15 19:15:07'),
(15, 11, 188, '2025-12-15 19:15:07', '2025-12-15 19:15:07'),
(16, 12, 171, '2025-12-15 19:15:07', '2025-12-15 19:15:07'),
(17, 12, 187, '2025-12-15 19:15:07', '2025-12-15 19:15:07'),
(18, 12, 188, '2025-12-15 19:15:07', '2025-12-15 19:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `incident_configuration`
--

DROP TABLE IF EXISTS `incident_configuration`;
CREATE TABLE IF NOT EXISTS `incident_configuration` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `setting_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_configuration_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_configuration`
--

INSERT INTO `incident_configuration` (`id`, `resort_id`, `setting_key`, `setting_value`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(4, 26, 'status', 'Reported,Assigned To,Acknowledged,Investigation In Progress,Under Review,Findings Submitted,Resolution Suggested,Approval Pending,Approved,Rejected,Resolved', 259, 259, '2025-12-15 19:15:21', '2025-12-15 19:15:21'),
(5, 26, 'meeting_reminder', '{\"reminder_days\":\"1 Business Days\"}', 259, 259, '2025-12-15 19:15:40', '2025-12-15 19:15:40'),
(6, 26, 'severity_levels', 'Minor,Moderate,Severe', 259, 259, '2025-12-15 19:15:46', '2025-12-15 19:15:46');

-- --------------------------------------------------------

--
-- Table structure for table `incident_employee_statements`
--

DROP TABLE IF EXISTS `incident_employee_statements`;
CREATE TABLE IF NOT EXISTS `incident_employee_statements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `statement` text COLLATE utf8mb4_unicode_ci,
  `document_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','submitted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_employee_statements_incident_id_foreign` (`incident_id`),
  KEY `incident_employee_statements_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_followup_actions`
--

DROP TABLE IF EXISTS `incident_followup_actions`;
CREATE TABLE IF NOT EXISTS `incident_followup_actions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `followup_action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `requires_employee_statement` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `incident_followup_actions_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_followup_actions`
--

INSERT INTO `incident_followup_actions` (`id`, `resort_id`, `followup_action`, `created_by`, `modified_by`, `created_at`, `updated_at`, `requires_employee_statement`) VALUES
(15, 26, 'Employee Statement', 259, 259, '2025-12-15 19:16:09', '2025-12-15 19:16:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `incident_outcome_types`
--

DROP TABLE IF EXISTS `incident_outcome_types`;
CREATE TABLE IF NOT EXISTS `incident_outcome_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `outcome_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_outcome_types_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_outcome_types`
--

INSERT INTO `incident_outcome_types` (`id`, `resort_id`, `outcome_type`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(7, 26, 'Resolved', 259, 259, '2025-12-15 19:17:06', '2025-12-15 19:17:06'),
(8, 26, 'Advised', 259, 259, '2025-12-15 19:17:06', '2025-12-15 19:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `incident_resolution_timeline`
--

DROP TABLE IF EXISTS `incident_resolution_timeline`;
CREATE TABLE IF NOT EXISTS `incident_resolution_timeline` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timeline` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_resolution_timeline_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_resolution_timeline`
--

INSERT INTO `incident_resolution_timeline` (`id`, `resort_id`, `priority`, `timeline`, `created_at`, `updated_at`) VALUES
(4, 26, 'High', '2 Business Days', '2025-12-15 19:15:35', '2025-12-15 19:15:35'),
(5, 26, 'Medium', '4 Business Days', '2025-12-15 19:15:35', '2025-12-15 19:15:35'),
(6, 26, 'Low', '6 Business Days', '2025-12-15 19:15:35', '2025-12-15 19:15:35');

-- --------------------------------------------------------

--
-- Table structure for table `incident_subcategories`
--

DROP TABLE IF EXISTS `incident_subcategories`;
CREATE TABLE IF NOT EXISTS `incident_subcategories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `subcategory_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('High','Medium','Low') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Low',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_subcategories_resort_id_foreign` (`resort_id`),
  KEY `incident_subcategories_category_id_foreign` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_subcategories`
--

INSERT INTO `incident_subcategories` (`id`, `resort_id`, `category_id`, `subcategory_name`, `priority`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(27, 26, 29, 'Slips, trips, and falls', 'Low', 259, 259, '2025-11-27 17:05:16', '2025-11-27 17:05:16'),
(28, 26, 29, 'Burns (kitchen, sun, or equipment-related) or Scalds', 'Medium', 259, 259, '2025-11-27 17:05:16', '2025-11-27 17:05:16'),
(29, 26, 30, 'Inappropriate messages', 'High', 259, 259, '2025-11-27 17:10:21', '2025-11-27 17:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `increment_types`
--

DROP TABLE IF EXISTS `increment_types`;
CREATE TABLE IF NOT EXISTS `increment_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_by` bigint UNSIGNED NOT NULL,
  `modified_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `increment_types_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `increment_types`
--

INSERT INTO `increment_types` (`id`, `resort_id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(2, 26, 'Annual Performance Increment', 'Active', 259, 259, '2025-12-13 15:38:52', '2025-12-13 15:38:52'),
(3, 26, 'Service Recognition Increment', 'Active', 259, 259, '2025-12-13 15:38:52', '2025-12-13 15:38:52'),
(4, 26, 'Skill Enhancement Increment', 'Active', 259, 259, '2025-12-13 15:38:52', '2025-12-13 15:38:52');

-- --------------------------------------------------------

--
-- Table structure for table `interview_assessment_forms`
--

DROP TABLE IF EXISTS `interview_assessment_forms`;
CREATE TABLE IF NOT EXISTS `interview_assessment_forms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `position` int UNSIGNED NOT NULL,
  `form_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interview_assessment_forms_resort_id_foreign` (`resort_id`),
  KEY `interview_assessment_forms_position_foreign` (`position`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `interview_assessment_forms`
--

INSERT INTO `interview_assessment_forms` (`id`, `resort_id`, `position`, `form_name`, `form_structure`, `created_at`, `updated_at`) VALUES
(1, 26, 146, 'Interview Assessment Form - HR Coordinator', '\"[{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"1. CANDIDATE INFORMATION &amp; INTERVIEW DETAILS\\\",\\\"access\\\":false},{\\\"type\\\":\\\"date\\\",\\\"required\\\":true,\\\"label\\\":\\\"Interview Date\\\",\\\"className\\\":\\\"form-control\\\",\\\"name\\\":\\\"date-1765171643643-0\\\",\\\"access\\\":false,\\\"subtype\\\":\\\"date\\\"},{\\\"type\\\":\\\"text\\\",\\\"required\\\":true,\\\"label\\\":\\\"Candidate Name\\\",\\\"className\\\":\\\"form-control\\\",\\\"name\\\":\\\"text-1765171669078-0\\\",\\\"access\\\":false,\\\"subtype\\\":\\\"text\\\"},{\\\"type\\\":\\\"text\\\",\\\"required\\\":false,\\\"label\\\":\\\"Position Applied For\\\",\\\"className\\\":\\\"form-control\\\",\\\"name\\\":\\\"text-1765171693388-0\\\",\\\"access\\\":false,\\\"subtype\\\":\\\"text\\\"},{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"2. CORE COMPETENCIES ASSESSMENT\\\",\\\"access\\\":false},{\\\"type\\\":\\\"radio-group\\\",\\\"required\\\":false,\\\"label\\\":\\\"HR Knowledge &amp; Expertise\\\",\\\"inline\\\":false,\\\"name\\\":\\\"radio-group-1765171736963-0\\\",\\\"access\\\":false,\\\"other\\\":false,\\\"values\\\":[{\\\"label\\\":\\\"Poor\\\",\\\"value\\\":\\\"1\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Fair\\\",\\\"value\\\":\\\"2\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Good\\\",\\\"value\\\":\\\"3\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Very Good\\\",\\\"value\\\":\\\"4\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Excellent\\\",\\\"value\\\":\\\"5\\\",\\\"selected\\\":false}]},{\\\"type\\\":\\\"radio-group\\\",\\\"required\\\":false,\\\"label\\\":\\\"Leadership &amp; Communication\\\",\\\"inline\\\":false,\\\"name\\\":\\\"radio-group-1765171788528-0\\\",\\\"access\\\":false,\\\"other\\\":false,\\\"values\\\":[{\\\"label\\\":\\\"Poor\\\",\\\"value\\\":\\\"1\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Fair\\\",\\\"value\\\":\\\"2\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Good\\\",\\\"value\\\":\\\"3\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Very Good\\\",\\\"value\\\":\\\"4\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Excellent\\\",\\\"value\\\":\\\"5\\\",\\\"selected\\\":false}]},{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"3. MALDIVES RESORT INDUSTRY KNOWLEDGE &amp; RESORT-SPECIFIC COMPETENCIES\\\",\\\"access\\\":false},{\\\"type\\\":\\\"textarea\\\",\\\"required\\\":false,\\\"label\\\":\\\"Interviewer Assessment Notes &amp; Comments\\\",\\\"className\\\":\\\"form-control\\\",\\\"name\\\":\\\"textarea-1765171903874-0\\\",\\\"access\\\":false,\\\"subtype\\\":\\\"textarea\\\"}]\"', '2025-12-08 11:02:28', '2025-12-08 11:02:28');

-- --------------------------------------------------------

--
-- Table structure for table `interview_assessment_responses`
--

DROP TABLE IF EXISTS `interview_assessment_responses`;
CREATE TABLE IF NOT EXISTS `interview_assessment_responses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` bigint UNSIGNED NOT NULL,
  `interviewer_id` int UNSIGNED NOT NULL,
  `interviewee_id` bigint UNSIGNED NOT NULL,
  `interviewer_signature` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interview_assessment_responses_form_id_foreign` (`form_id`),
  KEY `interview_assessment_responses_interviewee_id_foreign` (`interviewee_id`),
  KEY `interview_assessment_responses_interviewer_id_foreign` (`interviewer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_category_models`
--

DROP TABLE IF EXISTS `inventory_category_models`;
CREATE TABLE IF NOT EXISTS `inventory_category_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `CategoryName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_category_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_modules`
--

DROP TABLE IF EXISTS `inventory_modules`;
CREATE TABLE IF NOT EXISTS `inventory_modules` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Inv_Cat_id` bigint UNSIGNED NOT NULL,
  `ItemName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ItemCode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PurchageDate` date NOT NULL DEFAULT '2025-01-10',
  `Occupied` int DEFAULT NULL,
  `Quantity` int DEFAULT NULL,
  `MinMumStockQty` int DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `modified_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_modules_resort_id_foreign` (`resort_id`),
  KEY `inventory_modules_inv_cat_id_foreign` (`Inv_Cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investing_hearing_templete_models`
--

DROP TABLE IF EXISTS `investing_hearing_templete_models`;
CREATE TABLE IF NOT EXISTS `investing_hearing_templete_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Hearing_Temp_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Hearing_Temp_Structure` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investing_hearing_templete_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_templates`
--

DROP TABLE IF EXISTS `itinerary_templates`;
CREATE TABLE IF NOT EXISTS `itinerary_templates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_type` enum('supervisor_line','manager_above') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template scope',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `itinerary_templates_resort_id_foreign` (`resort_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `itinerary_templates`
--

INSERT INTO `itinerary_templates` (`id`, `resort_id`, `name`, `template_type`, `description`, `fields`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 26, 'Arrival Form', 'supervisor_line', 'This form is used for Below Supervisor Positions only', '\"[{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"<span style=\\\\\\\"border-width: 0px; border-style: solid; border-color: oklch(0.3039 0.04 213.68 \\/ 0.16); scrollbar-color: initial; scrollbar-width: initial; --tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(59 130 246 \\/ .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; color: oklch(0.3039 0.04 213.68); font-family: fkGroteskNeue, ui-sans-serif, system-ui, -apple-system, &quot;system-ui&quot;, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, &quot;Noto Sans&quot;, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;, &quot;Hiragino Sans&quot;, &quot;PingFang SC&quot;, &quot;Apple SD Gothic Neo&quot;, &quot;Yu Gothic&quot;, &quot;Microsoft YaHei&quot;, &quot;Microsoft JhengHei&quot;, Meiryo; font-size: 16px; letter-spacing: 0.08px; background-color: oklch(0.9902 0.004 106.47);\\\\\\\">DAY 1 - ARRIVAL DAY<\\/span><span style=\\\\\\\"color: oklch(0.3039 0.04 213.68); font-family: fkGroteskNeue, ui-sans-serif, system-ui, -apple-system, &quot;system-ui&quot;, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, &quot;Noto Sans&quot;, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;, &quot;Hiragino Sans&quot;, &quot;PingFang SC&quot;, &quot;Apple SD Gothic Neo&quot;, &quot;Yu Gothic&quot;, &quot;Microsoft YaHei&quot;, &quot;Microsoft JhengHei&quot;, Meiryo; font-size: 16px; letter-spacing: 0.08px; background-color: oklch(0.9902 0.004 106.47);\\\\\\\">&nbsp;<\\/span>\\\",\\\"access\\\":false},{\\\"type\\\":\\\"checkbox-group\\\",\\\"required\\\":false,\\\"label\\\":\\\"Checkbox Group\\\",\\\"toggle\\\":false,\\\"inline\\\":false,\\\"name\\\":\\\"checkbox-group-1765624221634-0\\\",\\\"access\\\":false,\\\"other\\\":false,\\\"values\\\":[{\\\"label\\\":\\\"Airport\\/Resort Greeting and City Orientation\\\",\\\"value\\\":\\\"option-1\\\",\\\"selected\\\":true},{\\\"label\\\":\\\"Medical Tests and Health Screening\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Work Permit Processing and Documentation\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"HR Paperwork and Formalities\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Meet and Greet with HODs and Team\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false}]},{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"<span style=\\\\\\\"border-width: 0px; border-style: solid; border-color: oklch(0.3039 0.04 213.68 \\/ 0.16); scrollbar-color: initial; scrollbar-width: initial; --tw-border-spacing-x: 0; --tw-border-spacing-y: 0; --tw-translate-x: 0; --tw-translate-y: 0; --tw-rotate: 0; --tw-skew-x: 0; --tw-skew-y: 0; --tw-scale-x: 1; --tw-scale-y: 1; --tw-pan-x: ; --tw-pan-y: ; --tw-pinch-zoom: ; --tw-scroll-snap-strictness: proximity; --tw-gradient-from-position: ; --tw-gradient-via-position: ; --tw-gradient-to-position: ; --tw-ordinal: ; --tw-slashed-zero: ; --tw-numeric-figure: ; --tw-numeric-spacing: ; --tw-numeric-fraction: ; --tw-ring-inset: ; --tw-ring-offset-width: 0px; --tw-ring-offset-color: #fff; --tw-ring-color: rgb(59 130 246 \\/ .5); --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; --tw-shadow-colored: 0 0 #0000; --tw-blur: ; --tw-brightness: ; --tw-contrast: ; --tw-grayscale: ; --tw-hue-rotate: ; --tw-invert: ; --tw-saturate: ; --tw-sepia: ; --tw-drop-shadow: ; --tw-backdrop-blur: ; --tw-backdrop-brightness: ; --tw-backdrop-contrast: ; --tw-backdrop-grayscale: ; --tw-backdrop-hue-rotate: ; --tw-backdrop-invert: ; --tw-backdrop-opacity: ; --tw-backdrop-saturate: ; --tw-backdrop-sepia: ; --tw-contain-size: ; --tw-contain-layout: ; --tw-contain-paint: ; --tw-contain-style: ; color: oklch(0.3039 0.04 213.68); font-family: fkGroteskNeue, ui-sans-serif, system-ui, -apple-system, &quot;system-ui&quot;, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, &quot;Noto Sans&quot;, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;, &quot;Hiragino Sans&quot;, &quot;PingFang SC&quot;, &quot;Apple SD Gothic Neo&quot;, &quot;Yu Gothic&quot;, &quot;Microsoft YaHei&quot;, &quot;Microsoft JhengHei&quot;, Meiryo; font-size: 16px; letter-spacing: 0.08px; background-color: oklch(0.9902 0.004 106.47);\\\\\\\">WEEK 1 - ORIENTATION AND TRAINING<\\/span><span style=\\\\\\\"color: oklch(0.3039 0.04 213.68); font-family: fkGroteskNeue, ui-sans-serif, system-ui, -apple-system, &quot;system-ui&quot;, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, &quot;Noto Sans&quot;, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;, &quot;Hiragino Sans&quot;, &quot;PingFang SC&quot;, &quot;Apple SD Gothic Neo&quot;, &quot;Yu Gothic&quot;, &quot;Microsoft YaHei&quot;, &quot;Microsoft JhengHei&quot;, Meiryo; font-size: 16px; letter-spacing: 0.08px; background-color: oklch(0.9902 0.004 106.47);\\\\\\\">&nbsp;Section<\\/span>\\\",\\\"access\\\":false},{\\\"type\\\":\\\"checkbox-group\\\",\\\"required\\\":false,\\\"label\\\":\\\"Checkbox Group\\\",\\\"toggle\\\":false,\\\"inline\\\":false,\\\"name\\\":\\\"checkbox-group-1765624311409-0\\\",\\\"access\\\":false,\\\"other\\\":false,\\\"values\\\":[{\\\"label\\\":\\\"Property Knowledge and Familiarization\\\",\\\"value\\\":\\\"option-1\\\",\\\"selected\\\":true},{\\\"label\\\":\\\"Job Role Training and Responsibilities\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Buddy Training and Mentoring\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Mandatory Trainings (Safety, Compliance, etc.)\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false}]}]\"', NULL, NULL, '2025-12-13 16:43:16', '2025-12-13 16:43:16'),
(2, 26, 'Arrival Form', 'manager_above', 'This form is used for Manager and above only', '\"[{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"First Week\\\",\\\"access\\\":false},{\\\"type\\\":\\\"checkbox-group\\\",\\\"required\\\":false,\\\"label\\\":\\\"Checkbox Group\\\",\\\"toggle\\\":false,\\\"inline\\\":false,\\\"name\\\":\\\"checkbox-group-1765624454212-0\\\",\\\"access\\\":false,\\\"other\\\":false,\\\"values\\\":[{\\\"label\\\":\\\"Greet and meet\\\",\\\"value\\\":\\\"option-1\\\",\\\"selected\\\":true},{\\\"label\\\":\\\"WP Medical\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"Travel to Resort\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false}]},{\\\"type\\\":\\\"header\\\",\\\"subtype\\\":\\\"h1\\\",\\\"label\\\":\\\"Second week\\\",\\\"access\\\":false},{\\\"type\\\":\\\"checkbox-group\\\",\\\"required\\\":false,\\\"label\\\":\\\"Checkbox Group\\\",\\\"toggle\\\":false,\\\"inline\\\":false,\\\"name\\\":\\\"checkbox-group-1765624527303-0\\\",\\\"access\\\":false,\\\"other\\\":false,\\\"values\\\":[{\\\"label\\\":\\\"Paper work with HR\\\",\\\"value\\\":\\\"option-1\\\",\\\"selected\\\":true},{\\\"label\\\":\\\"HODs meeting\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false},{\\\"label\\\":\\\"GM Meeting\\\",\\\"value\\\":\\\"\\\",\\\"selected\\\":false}]}]\"', NULL, NULL, '2025-12-13 16:45:51', '2025-12-13 16:45:51');

-- --------------------------------------------------------

--
-- Table structure for table `job_advertisements`
--

DROP TABLE IF EXISTS `job_advertisements`;
CREATE TABLE IF NOT EXISTS `job_advertisements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `Jobadvimg` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FinalApproval` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_advertisements_resort_id_foreign` (`Resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_advertisements`
--

INSERT INTO `job_advertisements` (`id`, `Resort_id`, `Jobadvimg`, `FinalApproval`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(4, 26, '', 8, 259, 259, '2025-12-08 10:29:03', '2025-12-08 10:29:03');

-- --------------------------------------------------------

--
-- Table structure for table `job_assessment_forms`
--

DROP TABLE IF EXISTS `job_assessment_forms`;
CREATE TABLE IF NOT EXISTS `job_assessment_forms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint UNSIGNED NOT NULL,
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_descriptions`
--

DROP TABLE IF EXISTS `job_descriptions`;
CREATE TABLE IF NOT EXISTS `job_descriptions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `Division_id` int UNSIGNED NOT NULL,
  `Department_id` int UNSIGNED NOT NULL,
  `Position_id` int UNSIGNED DEFAULT NULL,
  `Section_id` int UNSIGNED DEFAULT NULL,
  `jobdescription` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compliance` enum('Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Rejected',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_descriptions_resort_id_foreign` (`Resort_id`),
  KEY `job_descriptions_division_id_foreign` (`Division_id`),
  KEY `job_descriptions_department_id_foreign` (`Department_id`),
  KEY `job_descriptions_position_id_foreign` (`Position_id`),
  KEY `job_descriptions_section_id_foreign` (`Section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_descriptions`
--

INSERT INTO `job_descriptions` (`id`, `Resort_id`, `Division_id`, `Department_id`, `Position_id`, `Section_id`, `jobdescription`, `slug`, `compliance`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(17, 26, 76, 78, 146, NULL, '<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">JOB DESCRIPTION_HR Coordinator</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">JOB_METADATA</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Job_Title: HR Coordinator</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Department: Human Resources</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Location: Maldives (Resort)</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Employment_Type: Full-Time</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Reporting_To: HR Manager / Director of Human Resources</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Work_Environment: Resort Operations</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Level: Officer Level</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Contract_Type: Local or Expat</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">JOB_SUMMARY</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">Supports daily HR operations including recruitment coordination, documentation management, payroll assistance, and compliance monitoring.</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">JOB_OBJECTIVES</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Maintain employee records</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Support recruitment and onboarding</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Assist payroll processing</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Ensure compliance with Maldives Employment Act</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Support employee relations</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">ROLES_AND_RESPONSIBILITIES</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">CORE_HR_OPERATIONS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Maintain staff files</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Update HR systems</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Track attendance</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Support payroll</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">RECRUITMENT_AND_ONBOARDING</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Schedule interviews</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Prepare documents</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Issue contracts</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Conduct induction</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">EMPLOYEE_RELATIONS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Handle staff queries</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Maintain grievance records</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">COMPLIANCE_AND_DOCUMENT_CONTROL</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Ensure legal compliance</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Maintain disciplinary files</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Support audits</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">TRAINING_AND_DEVELOPMENT</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Arrange training</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Maintain records</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">ADMIN_SUPPORT</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Prepare HR reports</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Manage ID records</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Support HR projects</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">REQUIRED_QUALIFICATIONS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Degree or Diploma in HR</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- 2 years HR/administration experience</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Hospitality experience preferred</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Knowledge of Maldives Employment Act</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- MS Office proficiency</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">REQUIRED_SKILLS</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">TECHNICAL_SKILLS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- HR documentation</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Attendance systems</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Payroll support</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Reporting</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">SOFT_SKILLS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Confidentiality</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Communication</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Organization</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Attention to detail</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">COMPETENCIES</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Accountability</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Accuracy</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Ethics</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Service orientation</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">WORK_CONDITIONS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Resort working hours</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Weekend availability</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">PERFORMANCE_INDICATORS</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Record accuracy</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Payroll error rate</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Query resolution time</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Compliance audit success</span></span></p>\n\n<p>&nbsp;</p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">LEGAL_COMPLIANCE</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Maldives Employment Act</span></span></p>\n\n<p><span style=\"font-size:11pt\"><span style=\"font-family:Cambria,serif\">- Data protection</span></span></p>', 'jd-lyptvvxo', 'Rejected', 259, 259, '2025-12-08 10:29:34', '2025-12-08 10:29:34');

-- --------------------------------------------------------

--
-- Table structure for table `learning_calendar_sessions`
--

DROP TABLE IF EXISTS `learning_calendar_sessions`;
CREATE TABLE IF NOT EXISTS `learning_calendar_sessions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `learning_program_id` int UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `venue` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` enum('one-time','recurring','quarterly','annually') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one-time',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_calendar_sessions_resort_id_foreign` (`resort_id`),
  KEY `learning_calendar_sessions_learning_program_id_foreign` (`learning_program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_categories`
--

DROP TABLE IF EXISTS `learning_categories`;
CREATE TABLE IF NOT EXISTS `learning_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_categories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `learning_categories`
--

INSERT INTO `learning_categories` (`id`, `resort_id`, `category`, `color`, `created_at`, `updated_at`) VALUES
(32, 26, 'Guest Service Excellence', '#a264f7', '2025-12-13 16:53:08', '2025-12-13 16:53:08'),
(33, 26, 'Safety & Compliance', '#a264f7', '2025-12-13 16:54:37', '2025-12-13 16:54:37'),
(34, 26, 'Professional Development', '#a264f7', '2025-12-13 16:55:23', '2025-12-13 16:55:23');

-- --------------------------------------------------------

--
-- Table structure for table `learning_materials`
--

DROP TABLE IF EXISTS `learning_materials`;
CREATE TABLE IF NOT EXISTS `learning_materials` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `learning_program_id` int UNSIGNED NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_materials_learning_program_id_foreign` (`learning_program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_programs`
--

DROP TABLE IF EXISTS `learning_programs`;
CREATE TABLE IF NOT EXISTS `learning_programs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `objectives` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `learning_category_id` int UNSIGNED NOT NULL,
  `audience_type` enum('departments','grades','employees') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `hours` double(8,2) NOT NULL,
  `days` int NOT NULL,
  `frequency` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_mode` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trainer` int UNSIGNED NOT NULL,
  `prior_qualification` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_programs_resort_id_foreign` (`resort_id`),
  KEY `learning_programs_learning_category_id_foreign` (`learning_category_id`),
  KEY `learning_programs_trainer_foreign` (`trainer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_requests`
--

DROP TABLE IF EXISTS `learning_requests`;
CREATE TABLE IF NOT EXISTS `learning_requests` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `learning_id` int UNSIGNED NOT NULL,
  `reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `learning_manager_id` int UNSIGNED NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Pending','Approved','Denied','On Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `rejection_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_requests_resort_id_foreign` (`resort_id`),
  KEY `learning_requests_learning_id_foreign` (`learning_id`),
  KEY `learning_requests_learning_manager_id_foreign` (`learning_manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_requests_employees`
--

DROP TABLE IF EXISTS `learning_requests_employees`;
CREATE TABLE IF NOT EXISTS `learning_requests_employees` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `learning_request_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_requests_employees_learning_request_id_foreign` (`learning_request_id`),
  KEY `learning_requests_employees_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_categories`
--

DROP TABLE IF EXISTS `leave_categories`;
CREATE TABLE IF NOT EXISTS `leave_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `leave_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_days` int NOT NULL,
  `carry_forward` tinyint(1) NOT NULL DEFAULT '0',
  `carry_max` int DEFAULT NULL,
  `earned_leave` tinyint(1) NOT NULL DEFAULT '0',
  `earned_max` int DEFAULT NULL,
  `eligibility` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_times` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `leave_category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `combine_with_other` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_categories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_categories`
--

INSERT INTO `leave_categories` (`id`, `resort_id`, `leave_type`, `number_of_days`, `carry_forward`, `carry_max`, `earned_leave`, `earned_max`, `eligibility`, `frequency`, `number_of_times`, `color`, `leave_category`, `combine_with_other`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(57, 26, 'Annual Leave', 30, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '1', '#a264f7', '', 0, 240, 240, '2025-11-11 23:31:36', '2025-11-11 23:31:36'),
(58, 26, 'Emergency Leave', 10, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '1', '#ff2600', '', 0, 240, 240, '2025-11-11 23:34:24', '2025-11-11 23:34:38'),
(59, 26, 'Maternity Leave', 60, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '1', '#0056d6', '', 0, 240, 240, '2025-11-12 09:48:58', '2025-11-12 09:48:58'),
(60, 26, 'Paternity Leave', 3, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '1', '#00a3d7', '57', 1, 240, 240, '2025-11-12 09:49:53', '2025-11-12 09:49:53'),
(61, 26, 'Birthday Leave', 1, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '1', '#7a7a7a', '', 0, 240, 240, '2025-11-12 09:51:30', '2025-11-12 09:52:06'),
(62, 26, 'Sick Leave', 30, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '30', '#ffc4ab', '', 0, 240, 240, '2025-11-12 09:53:14', '2025-11-12 09:53:14'),
(63, 26, 'Rest Relaxation Leave', 12, 0, NULL, 0, NULL, '8,1,2', 'Yearly', '2', '#d29d00', '', 0, 240, 240, '2025-11-12 09:54:01', '2025-11-12 09:54:01'),
(64, 26, 'Circumcision Leave', 5, 0, NULL, 0, NULL, '8,1,2,4,5,6', 'Yearly', '1', '#d29d00', '', 0, 240, 240, '2025-11-12 09:55:06', '2025-11-12 09:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `leave_recommendations`
--

DROP TABLE IF EXISTS `leave_recommendations`;
CREATE TABLE IF NOT EXISTS `leave_recommendations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `leave_id` int UNSIGNED NOT NULL,
  `recommended_by` int UNSIGNED NOT NULL,
  `alt_start_date` date NOT NULL,
  `alt_end_date` date NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_recommendations_leave_id_foreign` (`leave_id`),
  KEY `leave_recommendations_recommended_by_foreign` (`recommended_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintanace_requests`
--

DROP TABLE IF EXISTS `maintanace_requests`;
CREATE TABLE IF NOT EXISTS `maintanace_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `building_id` bigint UNSIGNED NOT NULL,
  `item_id` bigint UNSIGNED NOT NULL,
  `FloorNo` int NOT NULL,
  `RoomNo` int NOT NULL,
  `Image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Completed_Image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Video` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Request_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ReasonOnHold` text COLLATE utf8mb4_unicode_ci,
  `Raised_By` int NOT NULL,
  `Assigned_To` int DEFAULT NULL,
  `descriptionIssues` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `priority` enum('High','Low','Medium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `Status` enum('Open','pending','On-Hold','In-Progress','Assigned','Closed','Approved','Rejected','ResolvedAwaiting') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `RejactionReason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintanace_requests_request_id_unique` (`Request_id`),
  KEY `maintanace_requests_resort_id_foreign` (`resort_id`),
  KEY `maintanace_requests_building_id_foreign` (`building_id`),
  KEY `maintanace_requests_item_id_foreign` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mandatory_learning_programs`
--

DROP TABLE IF EXISTS `mandatory_learning_programs`;
CREATE TABLE IF NOT EXISTS `mandatory_learning_programs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED NOT NULL,
  `department_id` int UNSIGNED DEFAULT NULL,
  `position_id` int UNSIGNED DEFAULT NULL,
  `notify_before_days` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mandatory_learning_programs_resort_id_foreign` (`resort_id`),
  KEY `mandatory_learning_programs_program_id_foreign` (`program_id`),
  KEY `mandatory_learning_programs_department_id_foreign` (`department_id`),
  KEY `mandatory_learning_programs_position_id_foreign` (`position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manifest`
--

DROP TABLE IF EXISTS `manifest`;
CREATE TABLE IF NOT EXISTS `manifest` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `manifest_type` enum('arrival','departure') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transportation_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transportation_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` enum('draft','confirmed','saved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'saved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manifest_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manifest_employees`
--

DROP TABLE IF EXISTS `manifest_employees`;
CREATE TABLE IF NOT EXISTS `manifest_employees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `manifest_id` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manifest_employees_manifest_id_foreign` (`manifest_id`),
  KEY `manifest_employees_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manifest_visitors`
--

DROP TABLE IF EXISTS `manifest_visitors`;
CREATE TABLE IF NOT EXISTS `manifest_visitors` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `manifest_id` bigint UNSIGNED NOT NULL,
  `visitor_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manifest_visitors_manifest_id_foreign` (`manifest_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manningandbudgeting_configfiles`
--

DROP TABLE IF EXISTS `manningandbudgeting_configfiles`;
CREATE TABLE IF NOT EXISTS `manningandbudgeting_configfiles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `consolidatdebudget` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benifitgrid` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xpat` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `local` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manningandbudgeting_configfiles`
--

INSERT INTO `manningandbudgeting_configfiles` (`id`, `resort_id`, `consolidatdebudget`, `benifitgrid`, `xpat`, `created_by`, `modified_by`, `created_at`, `updated_at`, `deleted_at`, `local`) VALUES
(8, 26, NULL, NULL, 55, 240, 240, '2025-11-11 21:29:27', '2025-11-11 21:29:27', NULL, 45);

-- --------------------------------------------------------

--
-- Table structure for table `manning_responses`
--

DROP TABLE IF EXISTS `manning_responses`;
CREATE TABLE IF NOT EXISTS `manning_responses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `dept_id` int UNSIGNED NOT NULL,
  `year` year NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_headcount` int UNSIGNED NOT NULL DEFAULT '0',
  `total_filled_positions` int UNSIGNED NOT NULL DEFAULT '0',
  `total_vacant_positions` int UNSIGNED NOT NULL DEFAULT '0',
  `budget_process_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manning_responses`
--

INSERT INTO `manning_responses` (`id`, `resort_id`, `dept_id`, `year`, `status`, `total_headcount`, `total_filled_positions`, `total_vacant_positions`, `budget_process_status`, `created_at`, `updated_at`) VALUES
(54, 25, 75, '2026', '', 1, 1, 0, NULL, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(55, 25, 77, '2026', '', 2, 1, 1, NULL, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(56, 26, 79, '2026', '', 4, 3, 1, NULL, '2025-11-15 12:37:10', '2025-11-15 12:37:10'),
(57, 26, 80, '2026', '', 10, 8, 2, NULL, '2025-11-15 12:45:44', '2025-11-15 12:45:44'),
(58, 26, 78, '2026', '', 4, 3, 1, NULL, '2025-11-15 12:49:43', '2025-11-15 12:49:43');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_12_31_164507_create_employee_overtimes_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
CREATE TABLE IF NOT EXISTS `modules` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `module_name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Workforce Planning', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(2, 'Payroll', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(3, 'Talent Acquisition', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(4, 'People', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(5, 'Time and Attendance', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(6, 'Leave', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(7, 'Performance', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(8, 'Learning', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(9, 'Accommodation', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(10, 'Incident', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(11, 'Survey', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(12, 'Reports', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(13, 'Support', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(14, 'Visa', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(15, 'Grievance and Disciplinary', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(16, 'File Management', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(17, 'SOS', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(18, 'Compliance', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL),
(19, 'Settings', 'Active', 1, 1, '2025-10-29 07:16:39', '2025-10-29 07:16:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `module_pages`
--

DROP TABLE IF EXISTS `module_pages`;
CREATE TABLE IF NOT EXISTS `module_pages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `Module_Id` bigint UNSIGNED NOT NULL,
  `internal_route` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TypeOfPage` enum('InsideOfPage','InsideOfMenu') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InsideOfPage',
  `type` enum('para','normal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `place_order` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_pages_module_id_foreign` (`Module_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `module_pages`
--

INSERT INTO `module_pages` (`id`, `page_name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`, `deleted_at`, `Module_Id`, `internal_route`, `TypeOfPage`, `type`, `place_order`) VALUES
(1, 'Support List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 13, 'support.index', 'InsideOfMenu', 'normal', 1),
(2, 'Manning', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.budget.manning', 'InsideOfMenu', 'normal', 2),
(3, 'Budget', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.budget.viewbudget', 'InsideOfMenu', 'normal', 3),
(4, 'Consolidate Budget', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.budget.consolidatebudget', 'InsideOfMenu', 'normal', 4),
(5, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.budget.config', 'InsideOfMenu', 'normal', 5),
(6, 'Cost Config Page', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.budget.index', 'InsideOfPage', 'normal', 0),
(7, 'Benefit Grid', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.benifitgrid.index', 'InsideOfPage', 'normal', 0),
(8, 'Setting', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:56', NULL, 14, 'resort.sitesettings', 'InsideOfPage', 'normal', 0),
(9, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.recruitement.hrdashboard', 'InsideOfMenu', 'normal', 1),
(10, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.configration', 'InsideOfMenu', 'normal', 7),
(11, 'Talent Pool', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.TalentPool', 'InsideOfMenu', 'normal', 2),
(12, 'Shortlisted Applicants', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.shortlistedapplicants', 'InsideOfMenu', 'normal', 3),
(13, 'Moving to talent pool', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.getTalentPoolApplicant', 'InsideOfPage', 'normal', 0),
(14, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.workforceplan.dashboard', 'InsideOfMenu', 'normal', 1),
(15, 'Compare Budget', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 1, 'resort.budget.comparebudget,{id]', 'InsideOfPage', 'normal', 0),
(16, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.hr.dashboard', 'InsideOfMenu', 'normal', 1),
(17, 'Create Duty Roster', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.CreateDutyRoster', 'InsideOfMenu', 'normal', 2),
(18, 'Questionnaire', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.Questionnaire', 'InsideOfPage', 'normal', 0),
(19, 'Job Description', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.jobdescription.index', 'InsideOfPage', 'normal', 0),
(20, 'Interview Assessment', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'interview-assessment.index', 'InsideOfMenu', 'normal', 6),
(21, 'Email Templates', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.ta.emailtemplates', 'InsideOfMenu', 'normal', 4),
(22, 'Vacancies', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 3, 'resort.vacancies.FreshApplicant', 'InsideOfMenu', 'normal', 5),
(23, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.dashboard', 'InsideOfMenu', 'normal', 1),
(24, 'Employee', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.employee', 'InsideOfMenu', 'normal', 5),
(25, 'Attandance Register', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.AttandanceRegister', 'InsideOfMenu', 'normal', 4),
(26, 'Location History', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.LocationHistory', 'InsideOfPage', 'normal', 0),
(27, 'Overtime', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.OverTime', 'InsideOfMenu', 'normal', 6),
(28, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.Configration', 'InsideOfMenu', 'normal', 7),
(29, 'Todo List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.todolist', 'InsideOfPage', 'normal', 0),
(30, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.dashboard', 'InsideOfMenu', 'normal', 0),
(31, 'Maintenance Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.MaintanaceRequestlist', 'InsideOfMenu', 'normal', 0),
(32, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.config.index', 'InsideOfMenu', 'normal', 0),
(33, 'Inventory', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.inventory', 'InsideOfMenu', 'normal', 0),
(34, 'Inventory Management', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.InventoryManagement', 'InsideOfMenu', 'normal', 0),
(35, 'Assign Accommodation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.AssignAccommation', 'InsideOfMenu', 'normal', 0),
(36, 'Accommodation Master', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.AccommodationMaster', 'InsideOfMenu', 'normal', 0),
(37, 'Available Accommodation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.AvailableAccommodation', 'InsideOfMenu', 'normal', 0),
(38, 'Employee Accommodation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.EmployeeAccommodation', 'InsideOfMenu', 'normal', 0),
(39, 'Hold Maintanace Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.HoldMaintanaceRequest', 'InsideOfPage', 'normal', 0),
(40, 'Event', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.event', 'InsideOfMenu', 'normal', 0),
(41, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 7, 'Performance.Hrdashboard', 'InsideOfMenu', 'normal', 0),
(42, 'Meeting', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 7, 'Performance.Meeting.index', 'InsideOfMenu', 'normal', 0),
(43, 'Cycle', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 7, 'Performance.cycle', 'InsideOfMenu', 'normal', 0),
(44, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 7, 'Performance.configuration', 'InsideOfMenu', 'normal', 0),
(45, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'leave.dashboard', 'InsideOfMenu', 'normal', 0),
(46, 'Leave Apply', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'leave.apply', 'InsideOfMenu', 'normal', 0),
(47, 'Leave Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'leave.request', 'InsideOfMenu', 'normal', 0),
(48, 'Calendar', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'leave.calendar', 'InsideOfMenu', 'normal', 0),
(49, 'Up Coming Holiday', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'resort.upcomingholiday.list', 'InsideOfMenu', 'normal', 0),
(50, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'leave.configration', 'InsideOfMenu', 'normal', 0),
(51, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'payroll.dashboard', 'InsideOfMenu', 'normal', 1),
(52, 'Shopkeepers', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'shopkeepers.create', 'InsideOfMenu', 'normal', 2),
(53, 'Run Pay Roll', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'payroll.run', 'InsideOfPage', 'normal', 9),
(54, 'Pension', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'payroll.pension.index', 'InsideOfMenu', 'normal', 3),
(55, 'EWT', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'payroll.ewt.index', 'InsideOfMenu', 'normal', 4),
(56, 'Final Settlement', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'payroll.final.settlement', 'InsideOfMenu', 'normal', 5),
(57, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'payroll.configration', 'InsideOfMenu', 'normal', 8),
(58, 'Programs', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.programs.index', 'InsideOfMenu', 'normal', 2),
(59, 'Add Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.request.add', 'InsideOfMenu', 'normal', 3),
(60, 'Calendar', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.calendar.index', 'InsideOfMenu', 'normal', 4),
(61, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.configration', 'InsideOfMenu', 'normal', 0),
(62, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'GrievanceAndDisciplinery.config.index', 'InsideOfMenu', 'normal', 0),
(63, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'GrievanceAndDisciplinery.Hrdashboard', 'InsideOfMenu', 'normal', 0),
(64, 'Grievance', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'GrievanceAndDisciplinery.grivance.GrivanceIndex', 'InsideOfMenu', 'normal', 0),
(65, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'Survey.hr.dashboard', 'InsideOfMenu', 'normal', 0),
(66, 'Survey', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'Survey.Surveylist', 'InsideOfMenu', 'normal', 0),
(67, 'Create Maintenance Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 10, 'resort.accommodation.CreateMaintenanceRequest', 'InsideOfMenu', 'normal', 0),
(68, 'Disciplinary List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex', 'InsideOfMenu', 'normal', 0),
(69, 'Schedule Learning', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.schedule', 'InsideOfMenu', 'normal', 5),
(70, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'incident.hr.dashboard', 'InsideOfMenu', 'normal', 1),
(71, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'incident.configration', 'InsideOfMenu', 'normal', 5),
(72, 'Incident List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'incident.index', 'InsideOfMenu', 'normal', 2),
(73, 'Incident Meeting', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'incident.meeting', 'InsideOfMenu', 'normal', 3),
(74, 'Calendar', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'incident.calendar', 'InsideOfMenu', 'normal', 4),
(75, 'Permission', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 16, 'FileManage.Permission', 'InsideOfMenu', 'normal', 3),
(76, 'Employees Documents', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 16, 'Employees.Documents', 'InsideOfMenu', 'normal', 1),
(77, 'Uncategorized Documents', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 16, 'Categories.Documents', 'InsideOfMenu', 'normal', 1),
(78, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 16, 'FileManagment.hr.dashboard', 'InsideOfMenu', 'normal', 0),
(79, 'Monlty Check In', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 7, 'Performance.MonltyCheckIn', 'InsideOfMenu', 'normal', 5),
(80, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 17, 'sos.config.index', 'InsideOfMenu', 'normal', 1),
(81, 'Boarding Pass', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 6, 'resort.boardingpass.list', 'InsideOfMenu', 'normal', 5),
(82, 'Info Update', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.info-update.index', 'InsideOfMenu', 'normal', 5),
(83, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 17, 'sos.dashboard.index', 'InsideOfMenu', 'normal', 0),
(84, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.hr.dashboard', 'InsideOfMenu', 'normal', 1),
(85, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.config', 'InsideOfMenu', 'normal', 6),
(86, 'Promotion Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.promotion.dashboard', 'InsideOfMenu', 'normal', 2),
(87, 'Probation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.probation', 'InsideOfMenu', 'normal', 3),
(88, 'Initiate Transfer', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.transfer.initiate', 'InsideOfMenu', 'normal', 4),
(89, 'Onboarding Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.onboarding.config', 'InsideOfMenu', 'normal', 8),
(90, 'Onboarding Creation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.onboarding.index', 'InsideOfMenu', 'normal', 7),
(91, 'Report List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 12, 'resort.report.index', 'InsideOfMenu', 'normal', 0),
(92, 'Salary Increment Managment', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.salary-increment.index', 'InsideOfMenu', 'normal', 9),
(93, 'Salary Increment Summary', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.salary-increment.summary-list', 'InsideOfMenu', 'normal', 10),
(94, 'Salary Advance', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.advance-salary.index', 'InsideOfMenu', 'normal', 11),
(95, 'Loan Salary Advance Repayment Tracker', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.advance-salary-repayment-tracker.index', 'InsideOfMenu', 'normal', 11),
(96, 'Exit Clearance', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.exit-clearance', 'InsideOfMenu', 'normal', 12),
(97, 'Itiernaries List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.onboarding.itinerary.list', 'InsideOfMenu', 'normal', 14),
(98, 'Employee Resignation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.employee-resignation.index', 'InsideOfMenu', 'normal', 12),
(99, 'Initial Liability Estimation', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.liability.index', 'InsideOfMenu', 'normal', 16),
(100, 'Deposit Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.DepositRequest', 'InsideOfMenu', 'normal', 1),
(101, 'Payment Request List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.PaymentRequestIndex', 'InsideOfMenu', 'normal', 3),
(102, 'Create Payment Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.PaymentRequest', 'InsideOfMenu', 'normal', 2),
(103, 'Verify Details', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.VerifyDetails', 'InsideOfMenu', 'normal', 4),
(104, 'Expiry', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.Expiry', 'InsideOfMenu', 'normal', 6),
(105, 'Xpact Sync', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.XpactSync', 'InsideOfMenu', 'normal', 1),
(106, 'Xpact Employee', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.xpactEmployee', 'InsideOfMenu', 'normal', 5),
(107, 'Renewal', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.RenewalView', 'InsideOfMenu', 'normal', 7),
(108, 'Dashboard', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'visa.hr.dashboard', 'InsideOfMenu', 'normal', 1),
(109, 'Configuration', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'visa.config', 'InsideOfMenu', 'normal', 9),
(110, 'Liabilities', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 14, 'resort.visa.Liabilities', 'InsideOfMenu', 'normal', 8),
(111, 'Compliances', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 13, 'people.compliance.index', 'InsideOfMenu', 'normal', 1),
(112, 'Learning List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.schedule.index', 'InsideOfMenu', 'normal', 6),
(113, 'Learning Request List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.request.index', 'InsideOfMenu', 'normal', 7),
(114, 'Request Detail', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 9, 'learning.request.details', 'InsideOfPage', 'para', 7),
(115, 'Final Settlement List', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 2, 'final.settlement.list', 'InsideOfMenu', 'normal', 6),
(116, 'Organization chart', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.org-chart', 'InsideOfMenu', 'normal', 12),
(117, 'Approval Request', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 4, 'people.approvel.index', 'InsideOfMenu', 'normal', 17),
(118, 'View Duty Roster', 'Active', 1, 1, '2025-12-23 13:56:55', '2025-12-23 13:56:55', NULL, 5, 'resort.timeandattendance.ViewDutyRoster', 'InsideOfMenu', 'normal', 3);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_checking_models`
--

DROP TABLE IF EXISTS `monthly_checking_models`;
CREATE TABLE IF NOT EXISTS `monthly_checking_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Checkin_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `tranining_id` int UNSIGNED DEFAULT NULL,
  `emp_id` int DEFAULT NULL,
  `date_discussion` date DEFAULT NULL,
  `start_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Meeting_Place` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Area_of_Discussion` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Area_of_Improvement` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Time_Line` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_comment` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Conducted','Confirm','Rescheduled') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_checking_models_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ninty_day_peformance_forms`
--

DROP TABLE IF EXISTS `ninty_day_peformance_forms`;
CREATE TABLE IF NOT EXISTS `ninty_day_peformance_forms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `FormName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_structure` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ninty_day_peformance_forms_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notice_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `font_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sticky` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_resort`
--

DROP TABLE IF EXISTS `notification_resort`;
CREATE TABLE IF NOT EXISTS `notification_resort` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

DROP TABLE IF EXISTS `oauth_access_tokens`;
CREATE TABLE IF NOT EXISTS `oauth_access_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `client_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('03eedf0a7f25695ac6b672948c85606aca53a09ba219129fef61516c7ff5fbb9f57f197589eed748', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-16 00:31:34', '2025-12-16 00:31:34', '2026-12-16 00:31:34'),
('03ef64ab28672a83f099a2e0bb121615faee9ac6e12dcfdfcb6c66e4bb39c59f1b85c37caeb5cf31', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:20:01', '2025-12-10 05:20:01', '2026-12-10 05:20:01'),
('05d4c267c16a38473f3dcefc62b18dbe36e5bd66cdc46b1bec1f5dc88aa94281366ef9a3290557b8', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-20 01:01:36', '2025-11-20 01:01:36', '2026-11-20 01:01:36'),
('0daa0e291a1481d2c6da3cb477931b566c6388f260012b2b4ff4ccfc8810d762661e3ecf62f6f7f1', 259, 5, 'ResortAdminToken', '[]', 1, '2025-12-15 18:31:42', '2025-12-15 18:31:42', '2026-12-15 18:31:42'),
('0f70579368f963192aa353046e0dcdf2e13edad9300e9e8173e3edb70b97efb52ff4840a0396672b', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-07 11:08:50', '2025-12-07 11:08:50', '2026-12-07 11:08:50'),
('116187bf7650f74d249f0faec75f1cbd40c77ebaf5efef4776249f0bc9cf357a4bf1bf31e72838ef', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-22 20:16:40', '2025-11-22 20:16:40', '2026-11-22 20:16:40'),
('117a8b3ef045ce8f45804ec2125678e63b2c8f23e3e833ef4130acacd56548144bf14887a9c3262f', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 22:42:43', '2025-12-05 22:42:43', '2026-12-05 22:42:43'),
('162a1c7a456c32c9c9b0e31b3b03246c1b0f0efca820eb5abf861de01e3c311ea445c682e4ea4c04', 244, 5, 'ResortAdminToken', '[]', 1, '2025-11-20 13:53:59', '2025-11-20 13:53:59', '2026-11-20 13:53:59'),
('184c6d857f43f441840f3c0f5d890acbbe5fc0d8b72fbf9bb3e9f5e9bfe0d53c670a7605319eb141', 239, 5, 'ResortAdminToken', '[]', 0, '2025-11-13 22:45:36', '2025-11-13 22:45:36', '2026-11-13 22:45:36'),
('1a90164ad6f53a8d7b64d9ba2c4f89182d5486bbf540fac69ed87d9938def713311bb1ec254555f1', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-22 00:59:27', '2025-11-22 00:59:27', '2026-11-22 00:59:27'),
('1ce6240ae467f6521d939d47cd16a0d24e49b227f27c0429e60914c825528842a74e14146275f5ed', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 14:35:47', '2025-12-10 14:35:47', '2026-12-10 14:35:47'),
('1eddd41c9d34484ec8a3b73dc8cb072a892ee6c71e653342fb10941661bc2d5843f7d9e3ab2a6cbc', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-15 21:42:23', '2025-11-15 21:42:23', '2026-11-15 21:42:23'),
('21d8a4adf970da9db6542143b7d581772773ab911e996e167c1d0f295161ac22ef3c9284b37a9fff', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 23:33:57', '2025-12-10 23:33:57', '2026-12-10 23:33:57'),
('224040f07ef917731e8088fa311a16c96e83eeb505634e7af31c7dc514607140e4d8cfaf84847f0c', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-12 02:18:18', '2025-12-12 02:18:18', '2026-12-12 02:18:18'),
('2d3f58c409a420694f87abfcfd7d7c1b75dcaa785817f2e0188ec397a9835f61cf603095986047b8', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-15 04:34:14', '2025-11-15 04:34:14', '2026-11-15 04:34:14'),
('2efbb06718613ca648b1bdfa5f4ca6d2c914457bf637fb874f0f618a9c7a5be75c26ce88f72e7a23', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-07 11:07:46', '2025-12-07 11:07:46', '2026-12-07 11:07:46'),
('35b1d9d74e21029324c95384e545f1517c8c0be04626fe7b93a42aaf621059fb94f8469df550ea45', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:19:28', '2025-12-10 05:19:28', '2026-12-10 05:19:28'),
('36df0f578077a95b5e1c0c5b4e48b95aa44383a53ce18154ecc4ffdcfceecb05fcd05b0dfb701c98', 254, 5, 'ResortAdminToken', '[]', 0, '2025-12-25 15:22:07', '2025-12-25 15:22:07', '2026-12-25 20:52:07'),
('39d75750b877e3a517df29b8634a6d090ed303128b8e622120c82d866ed5e24e262484a3bb816ec8', 250, 5, 'ResortAdminToken', '[]', 0, '2025-12-22 09:35:00', '2025-12-22 09:35:00', '2026-12-22 15:05:00'),
('3a7db5979132e2a3bddf993b37f74e34ac664d80b8dfc598aed6e2adb2c550ed66991a18dd740c7f', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-21 16:09:59', '2025-11-21 16:09:59', '2026-11-21 16:09:59'),
('40e83c049e3be7080cb89215cd5eefbce6918c28ff55e993e8833cd62185defcf21774612c9b2a1e', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-09 23:45:42', '2025-12-09 23:45:42', '2026-12-09 23:45:42'),
('4362bcb0eff90f327df4a762cc6d14fcd1c7dd93066c253f7a418ba53bccf92b96bcace962d48186', 250, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 07:20:46', '2025-12-10 07:20:46', '2026-12-10 07:20:46'),
('43e0adcf44ee07fd2458d3f556855bc553f27aee5beb42139c96605cf39a4905ae1025923e20f237', 248, 5, 'ResortAdminToken', '[]', 0, '2025-11-22 20:18:55', '2025-11-22 20:18:55', '2026-11-22 20:18:55'),
('448cbf062d9bd4354f9b94e3e3a072ab4d1c4ed52e75517ffee384a98d251a531bb5057f83971344', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 22:02:58', '2025-12-05 22:02:58', '2026-12-05 22:02:58'),
('4810bdf63242884e74ea5e5e3bd8f723100a1ac0963eb27cd6819fecc0d520b0dbeb8043786cfa41', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-12 01:08:06', '2025-12-12 01:08:06', '2026-12-12 01:08:06'),
('487b6ab0cc473153d641be462a0aaab5eb1206823be809748a28a9bc595d20d5b971c4a1f737e80f', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-11 00:33:25', '2025-12-11 00:33:25', '2026-12-11 00:33:25'),
('48859bac1f34dbb1180e591a8bd1c2e7085fe0ab63934104f22813b9f5eaef44163ef42b6398654b', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 23:55:39', '2025-12-10 23:55:39', '2026-12-10 23:55:39'),
('4a181074ba9fafefaf570a10f96932c3b02e6406679cdf4ec1708d9e2df991f8b54787109422f447', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-12 01:52:12', '2025-12-12 01:52:12', '2026-12-12 01:52:12'),
('4ba4e3bd92b4a188068592858eee9f36cb8b37c589a3b1dc70f4661aaa5976084b97f5341d7557b0', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:31:46', '2025-12-10 05:31:46', '2026-12-10 05:31:46'),
('4bfc2ca6569a13235974f64e94ba234eb4a915bca5de475c763f861bfaec25519ff92bbc0d8179ba', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-17 14:58:54', '2025-12-17 14:58:54', '2026-12-17 14:58:54'),
('4ea5d214c49c9e16204e084a0a789e9c755b271a0663a3c049ef7991c948b7745850ae3bbbf34081', 255, 5, 'ResortAdminToken', '[]', 1, '2025-11-16 01:29:14', '2025-11-16 01:29:14', '2026-11-16 01:29:14'),
('51d2b6f99653f4c40e491d89873c4d4715b46b49a0a38fff08c4a28bf42e62849ed7fc29a3c60b46', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-22 10:29:57', '2025-12-22 10:29:57', '2026-12-22 15:59:57'),
('57e87484581374ce8b4227c5117d8a47eb788ae043bcf82c715c7c45e2f67c336782f1fe524fb0a6', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-19 16:10:59', '2025-12-19 16:10:59', '2026-12-19 16:10:59'),
('59d4b1e84b12f64ce1fefe5ed2a56a280fc83bc67919b1eb0030c6c188b63357f3e518d223d2811f', 253, 5, 'ResortAdminToken', '[]', 1, '2025-12-18 23:45:07', '2025-12-18 23:45:07', '2026-12-18 23:45:07'),
('5beebd7257257a2df164732f93253bb90d7f31319a48d3553dd58f909325ec7da65020b796ce1ecf', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-18 13:04:57', '2025-12-18 13:04:57', '2026-12-18 13:04:57'),
('5fc38e12acefc517b44b1728843097212b54d3ccd7b288e5918f235e5b315be28487ad0f7fb615a7', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-08 23:45:22', '2025-12-08 23:45:22', '2026-12-08 23:45:22'),
('602065e833d18be868340076e60b353139687967c82821b62f04fad0eef2c0d3ee6aa2d9093b1cc5', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-18 13:04:39', '2025-12-18 13:04:39', '2026-12-18 13:04:39'),
('6339903910ca4f8f8e998478b930e4e05d3022fc09e60de4896763a79c5bbf47b796cbc2bd4d28c1', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-19 00:55:56', '2025-11-19 00:55:56', '2026-11-19 00:55:56'),
('63e6a565fc5adc089d639d5184bb9f1472da18a707b6565781c6adea1dfe95a03d107deea0d03299', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-24 13:36:43', '2025-11-24 13:36:43', '2026-11-24 13:36:43'),
('64698d4fd275d5c35ba981b219c70072b5932bb6234035703633868e60ba93d7542c500d4107456e', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-07 18:57:52', '2025-12-07 18:57:52', '2026-12-07 18:57:52'),
('64da0dfd8bcc81858aec28db91b414b1738cd99e03faf5dcf644f9fcf7af54b00bdbf9a3e84b1353', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-20 13:54:32', '2025-11-20 13:54:32', '2026-11-20 13:54:32'),
('6506db955cbf8d7622407396031b839eb003866e95582dc66e1786302d4ab194c047069971c3465e', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 22:22:49', '2025-12-05 22:22:49', '2026-12-05 22:22:49'),
('654aa51d482a464b84e3e1cdfee83eeba7756641ef39813143510e826c72d2fd23151470fc6063cf', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-12 01:04:30', '2025-12-12 01:04:30', '2026-12-12 01:04:30'),
('67288fa81f6ea6fc2f171666349cb4dc867ccd28b844f5bd9a4b6eead0053b1ad7167113dc4e4b8e', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-21 01:01:17', '2025-11-21 01:01:17', '2026-11-21 01:01:17'),
('67849649925d43754b1f33283600d11ce0219d4ad149c0419a8c19a64b6e6a340e5ed582fee38292', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-23 23:23:17', '2025-11-23 23:23:17', '2026-11-23 23:23:17'),
('68308d1d5443aeee8ba6c59ed438aa22426748967ab0b4d59197fae3db53f6d7bc59bd244e835ee8', 241, 5, 'ResortAdminToken', '[]', 0, '2025-11-19 00:00:21', '2025-11-19 00:00:21', '2026-11-19 00:00:21'),
('693bda4535f418a1751f78f267820a788ba0059b16cd5288a31b90dd966c30e04d2cd4087c79022d', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:33:05', '2025-12-10 05:33:05', '2026-12-10 05:33:05'),
('69712afcbbae8ec7cfb8c1ce3e80741aa3a810c015461f8be720c18320e0e744d3230f2451b6dedc', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-12 01:52:49', '2025-12-12 01:52:49', '2026-12-12 01:52:49'),
('6e9215d0f612b740bb4b75d22f05d51bab13d018f46a2ed65dcf24206992b277a0657cb192642bc1', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-07 11:07:21', '2025-12-07 11:07:21', '2026-12-07 11:07:21'),
('6f5ec43faf8ec51c071471be726402df1734e94d7b074ff720e8401621818a11d53c9e0a417acc5b', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-07 11:09:34', '2025-12-07 11:09:34', '2026-12-07 11:09:34'),
('723d8b455ba93b27f499246a6fb5ccd87bae9d41d53765c8e53c262e9bc963457adc75f910306585', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:21:41', '2025-12-10 05:21:41', '2026-12-10 05:21:41'),
('7649df8e211045817e178ee8dfa0bc9023e5f84cfa1aea53530d6e6f05df97ead90cbd1da0336d70', 251, 5, 'ResortAdminToken', '[]', 1, '2025-11-16 00:12:52', '2025-11-16 00:12:52', '2026-11-16 00:12:52'),
('76791e9643fb63d0028266838dc92877ee161ab71d579556cc1fb976be4e9e9cc86c50ec67c1a081', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-19 22:29:03', '2025-11-19 22:29:03', '2026-11-19 22:29:03'),
('7c1d0d0acb038682a49848e337dee33a38eef9c913053fa7edc9f92d6774656cd5828b1697d21a8d', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-18 23:46:09', '2025-12-18 23:46:09', '2026-12-18 23:46:09'),
('7d068e696482b4e4d2d138f72c105b18b9a0988ef8cf777e2acf458c934e337a02b0e601f032fcbb', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-15 11:21:18', '2025-12-15 11:21:18', '2026-12-15 11:21:18'),
('7d0b1365dc86722a18861cbf54522aa963095550137a8434af05b55330b3b95fefc89c6013f53a56', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-16 00:57:23', '2025-12-16 00:57:23', '2026-12-16 00:57:23'),
('8211d828a4fe82814e24c4ea132832a9906f87611e642a96d14798a0fa0d1098a1631b99f61ade84', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-17 13:25:10', '2025-11-17 13:25:10', '2026-11-17 13:25:10'),
('82ffc7a794c92c83a8a920a085870cd0088f2d12af874eb40435a73d6da9cf2e8ee4c229f22de9f7', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-19 16:08:15', '2025-12-19 16:08:15', '2026-12-19 16:08:15'),
('83161183dc9314d4a8dce6cd02b58a29a703f606c66120020a2dbc827660e10d0ed3fdd5068b660b', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 16:24:50', '2025-12-10 16:24:50', '2026-12-10 16:24:50'),
('831a53505b047df34d2f74f287b1423de54d4a0afe4d74e90a2dbf26ca612ff4feed670793f7c9d6', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 17:04:39', '2025-12-10 17:04:39', '2026-12-10 17:04:39'),
('85fdcb661b6722d52e265cdd1ac7811dd8806af87b14ed2f2e58e46fd38e3712c04cffa01d113a0d', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-15 04:37:35', '2025-11-15 04:37:35', '2026-11-15 04:37:35'),
('86b5b186555d8728e1fe379bf70cbb5430839b26e87b42cd15f41f70d67ceae0267baee26ed4dfae', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-08 23:25:21', '2025-12-08 23:25:21', '2026-12-08 23:25:21'),
('872340ba4a2d4cd1bd63bb6043c46e3ebc4e9c1fdcb9b54a64db661a0daad76b8996df5d7a442e6b', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-13 11:53:23', '2025-12-13 11:53:23', '2026-12-13 11:53:23'),
('87ad12c38b37b0c9cdb80b8f9b0b81a5113242326cdced4f1a1d231b4646d30902989da8d034c983', 241, 5, 'ResortAdminToken', '[]', 1, '2025-11-18 22:32:16', '2025-11-18 22:32:16', '2026-11-18 22:32:16'),
('88c73321eac7aed14844e1b050768780e2478f1a69923701f75c18ed62e21b4a2a2f84ea00e49691', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 22:01:11', '2025-12-05 22:01:11', '2026-12-05 22:01:11'),
('8a16fa9b2c8b239fd43169a485fc7c189fcab7dd55694695164c1a49cba46b8a8f449917a0a44dcc', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 22:30:21', '2025-12-05 22:30:21', '2026-12-05 22:30:21'),
('8f1426e2503916f8eb3b39ca047cb934d993fa6ddde0472bd1fdb003a7ad778c174a2bad642c0eff', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-28 01:03:10', '2025-11-28 01:03:10', '2026-11-28 01:03:10'),
('91f3b0d6d7d65a7c88aaa075d9c11a4eca9a5df3a4e6f79430b31059f014aee0d82f1ee1d1e142e9', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-01 00:40:00', '2025-12-01 00:40:00', '2026-12-01 00:40:00'),
('923a243b7ef7c79dcda8b8e888ffc08dd7ff8848cff6bfc5d596cb659d14521152610eb277e7cebe', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-22 09:35:32', '2025-12-22 09:35:32', '2026-12-22 15:05:32'),
('93142ef1b06fe465a10ada2c2712da7a2d21ad30e0babe9e995188c5e038dc6850f88c0ab2d4f391', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-19 22:44:39', '2025-11-19 22:44:39', '2026-11-19 22:44:39'),
('933139b888192de5ca5ddb7536ca17fc170d3023c86dc800e2b240960179a084f24f524277a7eb13', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 07:21:56', '2025-12-10 07:21:56', '2026-12-10 07:21:56'),
('9331aa53fccb816585c2e1999b915959eba201873152fab51bfb7f00e7171ab4b831a4e635b97660', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-07 11:10:41', '2025-12-07 11:10:41', '2026-12-07 11:10:41'),
('965d65819a3a45ae5259c1a440daadb0429b3e3e9653d46f53bc267bff02f4049d7892e140958493', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-27 22:03:48', '2025-11-27 22:03:48', '2026-11-27 22:03:48'),
('9bbecaa736bdc5d334b4f925c0fb7b1e5d9e6a5c04b852ad1c8938c6077b5f25617319507e1f33bf', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-18 20:17:43', '2025-11-18 20:17:43', '2026-11-18 20:17:43'),
('9d99183cda70cd54051392fb6ca06149f95a5fbbbd0db236c1eb0e541c0f3eae3fac10c487d26733', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-02 00:27:27', '2025-12-02 00:27:27', '2026-12-02 00:27:27'),
('9ef4efa0b1446396c8242c6b5591917a22056bdd4cab77415967073e6a1ed352c3f8d890e4b7b5af', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-15 18:32:23', '2025-12-15 18:32:23', '2026-12-15 18:32:23'),
('9f53cd74133dd9205d0d8cab34304d8068e72d186279d3dc80716af95aca3faf7226ebd72db9c405', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-13 23:17:45', '2025-11-13 23:17:45', '2026-11-13 23:17:45'),
('9fc397c87b3ea6da9531ba21db7b93c68756bbc5e7c07a48b75d485945076223e30e7c63997342c6', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-21 23:10:04', '2025-11-21 23:10:04', '2026-11-21 23:10:04'),
('a035b6b96f3a7c258fb24fff9861e3a70b98b2d3916dc87a2ccc93dcbd5bdb5fbf09d34897abf25c', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-15 04:32:41', '2025-11-15 04:32:41', '2026-11-15 04:32:41'),
('a22445eb41a92f0140803e4d0bc571d98f9bc91487287cdf5d26073c5a1b7ec6383eea03584bcbcf', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-19 16:11:49', '2025-12-19 16:11:49', '2026-12-19 16:11:49'),
('a416285546433e0cace572f0cc90fa19867e645f452edf6019bcc78618ff6d1ab6b868bf7e04d40b', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-17 13:48:56', '2025-11-17 13:48:56', '2026-11-17 13:48:56'),
('a6507fcf8bb76ae803e610ac66df1c0790c28edad7e876f22b7aa3b000500f5e527433d56af8a300', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-15 18:05:28', '2025-12-15 18:05:28', '2026-12-15 18:05:28'),
('a81bb03dd5534dd5c8d1def22dd1948a3d9e8427152b18a8a5dab303b0b45a206a71a513ff134404', 253, 5, 'ResortAdminToken', '[]', 1, '2025-11-16 00:54:41', '2025-11-16 00:54:41', '2026-11-16 00:54:41'),
('aa699860eb7bb9519135af7aed4c4f477a0156d46eb3b30f84ffc35c2c56d9a45d520142bcdd927f', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-18 13:21:30', '2025-11-18 13:21:30', '2026-11-18 13:21:30'),
('ac72c31b8849d99ea8342a63ca8e0a55349c0559e1056b201eaf407c3dc07d6c8bf0c8f93d2d9fd8', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-20 13:42:59', '2025-11-20 13:42:59', '2026-11-20 13:42:59'),
('b1f1806d686ba04de348477e7fadc869d8626acf8d8473f41d33d79099c7ad976fc1a1f389a56237', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-19 16:11:24', '2025-12-19 16:11:24', '2026-12-19 16:11:24'),
('b37ef4706fe051a6fe8330770731f0b863507fe6d450dc7861b8f2f1ab6d95f3d726c80fb534a795', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-20 13:46:57', '2025-11-20 13:46:57', '2026-11-20 13:46:57'),
('b3ea02af661f69246476fb3000a50d12676a9cab8c85d14c56981a52c005748631406918db3a43b5', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 23:05:13', '2025-12-05 23:05:13', '2026-12-05 23:05:13'),
('b68950bfd83c8a46273016f7f72a85f914be3e00413284ff934d068acc483bc3f792321290207a9e', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-01 00:42:12', '2025-12-01 00:42:12', '2026-12-01 00:42:12'),
('b93f155e40d54829feda67ccccb68f4332b821b3894e21d41db2ebda4d7579e25c5b7fbdb695ee74', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-11 00:40:42', '2025-12-11 00:40:42', '2026-12-11 00:40:42'),
('bd6058385cf1eec4974a9b98cf70cd14e0fcbb1bc3418538046558657b5395ab3ca248ca2fd4f734', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 14:34:47', '2025-12-10 14:34:47', '2026-12-10 14:34:47'),
('bdd480598f4bb9589473cc58ca58a19028cd83a98241ffb4c250b1fe5ad47e16e493f83ffdf8f8d2', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-18 02:06:42', '2025-11-18 02:06:42', '2026-11-18 02:06:42'),
('c0ef65742bbb7eb0f7b46ca70b3951de6ced833f5e7a83b86ae58f9d540a08c82ba1244678acc01d', 259, 5, 'ResortAdminToken', '[]', 1, '2025-11-22 20:17:40', '2025-11-22 20:17:40', '2026-11-22 20:17:40'),
('c40afc6b759d3926d95d18540c9632576bfc32b749d876159fcca071a918c5dc6f0d553aa836bad9', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-18 23:44:37', '2025-12-18 23:44:37', '2026-12-18 23:44:37'),
('c48a8c9c26e473a49e3bd0d52f91cadfe9b69835396d9386687e9951be1f0c09b503fe2a23307e27', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-01 00:13:40', '2025-12-01 00:13:40', '2026-12-01 00:13:40'),
('cc8451c9f22f36784a10d9a2790f606c8536c3e23cfd897b1d402cd5e923c8d269a664aab97b190f', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:21:12', '2025-12-10 05:21:12', '2026-12-10 05:21:12'),
('cda254769d576ae196984d3f438ff5ad2418c6238c59151b913ceb9af2f26a400604fd48d7f99d4d', 254, 5, 'ResortAdminToken', '[]', 0, '2025-12-31 12:03:43', '2025-12-31 12:03:43', '2026-12-31 17:33:43'),
('d04a0d36f9a458bb931de39aaa36bc4dd084e2c3809879cb2f0bfa765c836da364eada393ec5037e', 241, 5, 'ResortAdminToken', '[]', 1, '2025-11-17 13:29:29', '2025-11-17 13:29:29', '2026-11-17 13:29:29'),
('d18832f6c2ef232b893f6150883e7f8aa0742693dadc3a952d6d54cb4560832f127206cb54efe26c', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 05:07:19', '2025-12-10 05:07:19', '2026-12-10 05:07:19'),
('d1c2b16589b17c9771e1a81a0a214d118c0340a06d77255b08e04b2f10cc419569155ffa1dc91434', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-18 23:42:16', '2025-12-18 23:42:16', '2026-12-18 23:42:16'),
('d1e4a981174d735cf0143a60f90a5569f08635b8c2487db94703eedba37a781da31042e5cf674082', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-15 23:56:34', '2025-11-15 23:56:34', '2026-11-15 23:56:34'),
('d374d9e8d0b6fb3d8cdb14c3d0773eeb3b0fc0b5a2e25c4f6abddf05fb0479dd0d4b8d5bda8df694', 239, 5, 'ResortAdminToken', '[]', 0, '2025-12-11 00:52:02', '2025-12-11 00:52:02', '2026-12-11 00:52:02'),
('d82c30c10beffa72b07aecc84b7097f40cc0d4b28679b2d8292d03fb53c362e6a9680f1aca55575b', 250, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 05:18:14', '2025-12-10 05:18:14', '2026-12-10 05:18:14'),
('d8952fdee07df5b140b0c40b668a72b3d6c44bb8920f9f4cb13af4a9a926c21d450ae95f9b1f6213', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-23 19:38:23', '2025-11-23 19:38:23', '2026-11-23 19:38:23'),
('d9eaa7781e555c003c9356a6eb365550ad019d73f04bf97c5167f2752de6b34d43ac66ec92a2f40c', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-05 22:29:59', '2025-12-05 22:29:59', '2026-12-05 22:29:59'),
('db0d98b0d70b619f0af26be188bbc90d7e3dea549077c2e7a9ddca4e22f40127a1070ae59d2c560f', 248, 5, 'ResortAdminToken', '[]', 1, '2025-11-21 01:02:16', '2025-11-21 01:02:16', '2026-11-21 01:02:16'),
('dc4ea47ac4a78528cfad9282d01094e1f40feca1a44a1d97bcddd1da24310604f58240440c4c68d3', 254, 5, 'ResortAdminToken', '[]', 0, '2026-01-01 13:27:26', '2026-01-01 13:27:26', '2027-01-01 18:57:26'),
('de5be8a71840bd9586f809ff91231cb6c1a7cc331268ffc41174795959b464220ca8f349336688d9', 260, 5, 'ResortAdminToken', '[]', 1, '2025-11-23 19:40:12', '2025-11-23 19:40:12', '2026-11-23 19:40:12'),
('dfefc9173764b63398e053eff22ead2a7b5f1e98cadef55bf887a607e0e1b81eaa9952ed3722b0ce', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 06:09:04', '2025-12-10 06:09:04', '2026-12-10 06:09:04'),
('e2de99dd03b58703289bb12af76b1d31aebe935e8a11adcf7ae6f75dbfb1040a592538b299bacffa', 250, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 05:13:53', '2025-12-10 05:13:53', '2026-12-10 05:13:53'),
('e3cb519b926cb0f8a1ffa285b1d26476c8e253e2ffddbfd10f192324eb1f2ca8003687903531ee88', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-19 16:10:26', '2025-12-19 16:10:26', '2026-12-19 16:10:26'),
('e89365d6555f729ab38370f57dcf32b5157f6c3c33cf8f47d72eae44b08892bc27754d5b04f06e64', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-05 22:10:23', '2025-12-05 22:10:23', '2026-12-05 22:10:23'),
('eb662e6e0ad945decd167889d7edc8b14d6f6213fa95a591c2865656ae11d196c6ae371c4c32b058', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-12 01:53:48', '2025-12-12 01:53:48', '2026-12-12 01:53:48'),
('ebb65905d756a3d7ccc32e75155d21e656f55ddc243ba24cc33f826de3acc6ced8bad5f902e74ba5', 260, 5, 'ResortAdminToken', '[]', 0, '2025-12-11 00:09:21', '2025-12-11 00:09:21', '2026-12-11 00:09:21'),
('edbe94c202c1f2202fd1f82deebd279474096e20eea15258392c0188a6ac5d928f1f0fa5f221c5b8', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-06 22:12:02', '2025-12-06 22:12:02', '2026-12-06 22:12:02'),
('ede9659a82b162a6aa3872c359c221167ac91c4667802cac4b0524193812f4fdfb8f2b836ab40599', 248, 5, 'ResortAdminToken', '[]', 1, '2025-12-10 23:41:05', '2025-12-10 23:41:05', '2026-12-10 23:41:05'),
('edf533f93df957eeeeb892dd7cfc1fa0ae315b977877b51eaa50fc4722fa2979f93e4449008b8a74', 250, 5, 'ResortAdminToken', '[]', 0, '2025-12-10 06:32:56', '2025-12-10 06:32:56', '2026-12-10 06:32:56'),
('eeed20a973f1d01a5bc1c886494d7cd3166ef8c56efc5c56d3e58f14493fe5b973a1ce8375bf8f38', 254, 5, 'ResortAdminToken', '[]', 1, '2025-11-16 01:28:50', '2025-11-16 01:28:50', '2026-11-16 01:28:50'),
('f1f817f419964e32d1d22211eb37f8a25a44d2898b998062655343779f70bafa9ab8507a969d2596', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-28 01:01:51', '2025-11-28 01:01:51', '2026-11-28 01:01:51'),
('f84d474173c71099c20087480b90ae37446f621edfd58c14d51410c2c838f9a66481c3f5233980d4', 250, 5, 'ResortAdminToken', '[]', 1, '2025-11-23 19:40:45', '2025-11-23 19:40:45', '2026-11-23 19:40:45'),
('f8d378d92d3a45f83deca1e8b53b0df6f0efd36faff6d398c5701729e29b5a7ee78c37ced3ecfea5', 258, 5, 'ResortAdminToken', '[]', 1, '2025-11-16 01:29:44', '2025-11-16 01:29:44', '2026-11-16 01:29:44'),
('f94b4e65997892c750a5e26db05b34a40f11c21dd2fc00ca1cec933df718839165a51b4b104e8714', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-22 13:36:58', '2025-12-22 13:36:58', '2026-12-22 19:06:58'),
('f9d2c67d425b9e0110cda6e8ab21924a3a4231b7ed9b7eddc5c42062b1fc40320a68b34650cdb5da', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-13 22:53:20', '2025-11-13 22:53:20', '2026-11-13 22:53:20'),
('fc055367b67f050030792c83495abeec7c961dc8c238b1cdad7fd507e85795675c7bfa963681938e', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-23 21:56:51', '2025-11-23 21:56:51', '2026-11-23 21:56:51'),
('fc1816edf54a4f24db8df9dc33dc46903b9964216fdb772c1f3a6f68b8f8623d2849584f1ad650ac', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-02 18:51:00', '2025-12-02 18:51:00', '2026-12-02 18:51:00'),
('fcc9547dc2d2ffedfc7a6f25880de0f9d07a704917008a83d002bb4214404919b1159f16bf6476ae', 260, 5, 'ResortAdminToken', '[]', 0, '2025-11-13 22:54:26', '2025-11-13 22:54:26', '2026-11-13 22:54:26'),
('fe6362eb4ab2718c7232b1d4473dbdbe77947023d30eed18618560a6d04776762608bacedb132662', 248, 5, 'ResortAdminToken', '[]', 0, '2025-12-22 09:27:17', '2025-12-22 09:27:17', '2026-12-22 14:57:17'),
('fe6e093b1c5eb7e8816f9a7bf1bc8f056cb97ae420b2f60096ddff07e3e1d7ad515349a7002fcd95', 260, 5, 'ResortAdminToken', '[]', 1, '2025-12-19 16:09:41', '2025-12-19 16:09:41', '2026-12-19 16:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_auth_codes`
--

DROP TABLE IF EXISTS `oauth_auth_codes`;
CREATE TABLE IF NOT EXISTS `oauth_auth_codes` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `client_id` bigint UNSIGNED NOT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

DROP TABLE IF EXISTS `oauth_clients`;
CREATE TABLE IF NOT EXISTS `oauth_clients` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `user_id`, `name`, `secret`, `provider`, `redirect`, `personal_access_client`, `password_client`, `revoked`, `created_at`, `updated_at`) VALUES
(5, NULL, 'WisdomAi Personal Access Client', 'LFSkC5q1SzSThvq8H56vUSXL7Jh02YkYEfAC3voU', NULL, 'http://localhost', 1, 0, 0, '2025-11-13 22:45:28', '2025-11-13 22:45:28'),
(6, NULL, 'WisdomAi Password Grant Client', 'Dm4uCMnKoKl8ugmJaHCfpFn5WKNROheH9CT1Gwz9', 'users', 'http://localhost', 0, 1, 0, '2025-11-13 22:45:28', '2025-11-13 22:45:28');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_personal_access_clients`
--

DROP TABLE IF EXISTS `oauth_personal_access_clients`;
CREATE TABLE IF NOT EXISTS `oauth_personal_access_clients` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_personal_access_clients`
--

INSERT INTO `oauth_personal_access_clients` (`id`, `client_id`, `created_at`, `updated_at`) VALUES
(3, 5, '2025-11-13 22:45:28', '2025-11-13 22:45:28');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

DROP TABLE IF EXISTS `oauth_refresh_tokens`;
CREATE TABLE IF NOT EXISTS `oauth_refresh_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `occupancy_levels_hit_a_critical_thresholds`
--

DROP TABLE IF EXISTS `occupancy_levels_hit_a_critical_thresholds`;
CREATE TABLE IF NOT EXISTS `occupancy_levels_hit_a_critical_thresholds` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `building_id` bigint UNSIGNED NOT NULL,
  `Floor` int NOT NULL,
  `RoomNo` int NOT NULL,
  `ThresSoldLevel` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `occupancy_levels_hit_a_critical_thresholds_resort_id_foreign` (`resort_id`),
  KEY `occupancy_levels_hit_a_critical_thresholds_building_id_foreign` (`building_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `occupancy_thresholds`
--

DROP TABLE IF EXISTS `occupancy_thresholds`;
CREATE TABLE IF NOT EXISTS `occupancy_thresholds` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `occuplanies`
--

DROP TABLE IF EXISTS `occuplanies`;
CREATE TABLE IF NOT EXISTS `occuplanies` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `occupancydate` date NOT NULL,
  `occupancyinPer` double(8,2) NOT NULL,
  `occupancytotalRooms` int DEFAULT NULL,
  `occupancyOccupiedRooms` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `occuplanies_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `occuplanies`
--

INSERT INTO `occuplanies` (`id`, `resort_id`, `occupancydate`, `occupancyinPer`, `occupancytotalRooms`, `occupancyOccupiedRooms`, `created_by`, `modified_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(145, 25, '2025-10-31', 55.56, 900, 500, 233, 233, '2025-10-31 11:38:08', '2025-10-31 11:38:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `offenses_models`
--

DROP TABLE IF EXISTS `offenses_models`;
CREATE TABLE IF NOT EXISTS `offenses_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `disciplinary_cat_id` bigint UNSIGNED NOT NULL,
  `OffensesName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `offensesdescription` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offenses_models_resort_id_foreign` (`resort_id`),
  KEY `offenses_models_disciplinary_cat_id_foreign` (`disciplinary_cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offline_interviews`
--

DROP TABLE IF EXISTS `offline_interviews`;
CREATE TABLE IF NOT EXISTS `offline_interviews` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_events`
--

DROP TABLE IF EXISTS `onboarding_events`;
CREATE TABLE IF NOT EXISTS `onboarding_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `event_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `onboarding_events`
--

INSERT INTO `onboarding_events` (`id`, `resort_id`, `event_name`, `notification_time`, `created_at`, `updated_at`) VALUES
(15, 26, 'Employee Arrivaal', '2 Days Before', '2025-12-13 16:21:08', '2025-12-13 16:21:08'),
(16, 26, 'Employee Picked', 'Immidiate', '2025-12-13 16:21:08', '2025-12-13 16:21:08'),
(17, 26, 'Employee Dropped at Accommodation', 'Immidiate', '2025-12-13 16:21:08', '2025-12-13 16:21:08'),
(18, 26, 'Work Permit Medical', 'Immidiate', '2025-12-13 16:21:08', '2025-12-13 16:21:08');

-- --------------------------------------------------------

--
-- Table structure for table `parent_attendaces`
--

DROP TABLE IF EXISTS `parent_attendaces`;
CREATE TABLE IF NOT EXISTS `parent_attendaces` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `roster_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `Shift_id` bigint UNSIGNED NOT NULL,
  `Emp_id` int UNSIGNED NOT NULL,
  `OverTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CheckingTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DayWiseTotalHours` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CheckingOutTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `Status` enum('On-Time','Late','Absent','Present','DayOff','ShortLeave','HalfDayLeave','FullDayLeave') COLLATE utf8mb4_unicode_ci NOT NULL,
  `CheckInCheckOut_Type` enum('Manual','Geofencing','Biometric') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Allowed values: Manual, Geofencing, Biometric',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `OTApproved_By` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `OTStatus` enum('Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_attendaces_emp_id_foreign` (`Emp_id`),
  KEY `parent_attendaces_resort_id_foreign` (`resort_id`),
  KEY `parent_attendaces_shift_id_foreign` (`Shift_id`),
  KEY `parent_attendaces_roster_id_foreign` (`roster_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parent_attendaces`
--

INSERT INTO `parent_attendaces` (`id`, `roster_id`, `resort_id`, `Shift_id`, `Emp_id`, `OverTime`, `CheckingTime`, `DayWiseTotalHours`, `CheckingOutTime`, `date`, `Status`, `CheckInCheckOut_Type`, `created_by`, `modified_by`, `created_at`, `updated_at`, `OTApproved_By`, `note`, `OTStatus`) VALUES
(2149, 1938, 26, 20, 183, NULL, '4:00', '08:00', '12:00', '2025-12-01', 'Present', 'Manual', 254, 254, '2026-01-01 08:29:01', '2026-01-01 08:29:58', NULL, NULL, NULL),
(2150, 1938, 26, 20, 183, NULL, '13:00', '02:00', '15:00', '2025-12-01', 'Present', 'Manual', 254, 254, '2026-01-01 08:30:15', '2026-01-01 08:30:40', NULL, NULL, NULL),
(2151, 1938, 26, 20, 183, NULL, '01:00', '02:00', '03:00', '2025-12-02', 'Present', 'Manual', 254, 254, '2026-01-01 08:44:38', '2026-01-01 08:44:59', NULL, NULL, NULL),
(2152, 1938, 26, 20, 183, NULL, '04:00', '08:00', '12:00', '2025-12-02', 'Present', 'Manual', 254, 254, '2026-01-01 08:45:25', '2026-01-01 08:45:33', NULL, NULL, NULL),
(2153, 1938, 26, 20, 183, NULL, '03:00', '10:00', '13:00', '2025-12-03', 'Present', 'Manual', 254, 254, '2026-01-01 08:46:50', '2026-01-01 08:47:14', NULL, NULL, NULL),
(2154, 1938, 26, 20, 183, NULL, '02:00', '10:00', '12:00', '2025-12-04', 'Present', 'Manual', 254, 254, '2026-01-01 08:48:16', '2026-01-01 08:48:29', NULL, NULL, NULL),
(2155, 1938, 26, 20, 183, NULL, '02:00', '08:00', '10:00', '2025-12-05', 'Present', 'Manual', 254, 254, '2026-01-01 08:49:36', '2026-01-01 08:49:56', NULL, NULL, NULL),
(2157, 1938, 26, 20, 183, NULL, '03:00', '09:00', '12:00', '2025-12-01', 'Present', 'Manual', 254, 254, '2026-01-01 09:56:08', '2026-01-01 10:02:15', NULL, NULL, NULL),
(2158, 1938, 26, 20, 183, NULL, '03:00', '09:00', '12:00', '2026-01-01', 'Present', 'Manual', 254, 254, '2026-01-01 13:31:58', '2026-01-01 13:32:28', NULL, NULL, NULL),
(2159, 1938, 26, 20, 183, NULL, '13:00', '01:00', '14:00', '2026-01-01', 'Present', 'Manual', 254, 254, '2026-01-01 13:33:27', '2026-01-01 13:33:40', NULL, NULL, NULL),
(2165, 1938, 26, 20, 183, NULL, '04:00', '08:00', '12:00', '2026-01-02', 'Present', 'Manual', 254, 254, '2026-01-05 12:35:00', '2026-01-05 12:35:19', NULL, NULL, NULL),
(2166, 1938, 26, 20, 183, NULL, '14:00', '02:00', '16:00', '2026-01-02', 'Present', 'Manual', 254, 254, '2026-01-05 12:35:48', '2026-01-05 12:35:55', NULL, NULL, NULL),
(2167, 1938, 26, 20, 183, NULL, '04:00', '08:00', '12:00', '2026-01-03', 'Present', 'Manual', 254, 254, '2026-01-05 12:39:57', '2026-01-05 12:40:12', NULL, NULL, NULL),
(2168, 1938, 26, 20, 183, NULL, '14:00', '02:00', '16:00', '2026-01-03', 'Present', 'Manual', 254, 254, '2026-01-05 12:40:26', '2026-01-05 12:40:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parent_surveys`
--

DROP TABLE IF EXISTS `parent_surveys`;
CREATE TABLE IF NOT EXISTS `parent_surveys` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Surevey_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Start_date` date DEFAULT NULL,
  `End_date` date DEFAULT NULL,
  `Recurring_survey` enum('Daily','Weekly','Monthly','Quarterly','Annually') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Weekly',
  `Reminder_notification` int NOT NULL DEFAULT '7',
  `Min_response` int NOT NULL DEFAULT '1',
  `Allow_edit` enum('yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `Status` enum('Publish','SaveAsDraft','OnGoing','Complete') COLLATE utf8mb4_unicode_ci DEFAULT 'SaveAsDraft',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `survey_privacy_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_surveys_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `shopkeeper_id` int UNSIGNED NOT NULL,
  `order_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emp_id` int UNSIGNED NOT NULL,
  `purchased_date` date NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('Consented','Pending Consent','Pending','Paid','Partial Paid','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending Consent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cash_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payroll_deducted` decimal(10,2) NOT NULL DEFAULT '0.00',
  `qr_code` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `payments_shopkeeper_id_foreign` (`shopkeeper_id`),
  KEY `payments_emp_id_foreign` (`emp_id`),
  KEY `payments_product_id_foreign` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `shopkeeper_id`, `order_id`, `emp_id`, `purchased_date`, `product_id`, `quantity`, `price`, `status`, `created_at`, `updated_at`, `cash_paid`, `payroll_deducted`, `qr_code`) VALUES
(44, 49, 'ORD-US6H1DSR', 189, '2025-11-26', 46, 1, 200.00, 'Consented', '2025-11-26 23:03:38', '2025-12-07 15:16:48', 0.00, 0.00, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAgKADAAQAAAABAAAAgAAAAABIjgR3AAAMc0lEQVR4Ae2bgXIcubEERYf+/5fla1/kXrpZ04Pd5SkUBBjx3EBVdmEGC0N8JP3x48ePX3/938tfv3790/7x8XGbk/hXtL5QWpvcyXNO4uz3Mfldr7mz4J7V6Ev5fY0r5k7/zx1w/O+9Az95vbvTBkf1aUajrmatcuSyZuqzBkefPbTOoFPpgWOOf1ef5VMea5c35U3eXe65AdIObaQ9bgDe2acOzXU6bXjOSJrz+hjeetLs9zE8z8G8c8yTTy9Mn6NTe8Ydn/roIYs57F2948l1zrkBvBsbjs8B2PBD9yt/+ifA5rPjuyuo8nwNwSctrQ1Hn/Os0QvP3DV5KYMe83BJm3i8qikDP3lJg3+nnhvgnd37Br1fegOk/eDk4nnu/wbhJw2PXjOThkf/M5U1yKA6I2n49npWMUmjN1X45L2jnRvgnd37Br3nAHyDD/GdV/j0T8BXXzXk+Up89YFTVtKmfPjE+Bk97mzKgE8e/fYSjwb/bHX+au+5AVZ36ptyjxvg3dPn/fFJJBeNuXlriesa88qgd9Jg7ta0Tx69zK/WdK+ZGtNL1jNasfVFL1l/q3//J5611fG5AVZ36ptyH3+dqH9+of8vviSnNC2H5+XNJd9sjc13z/2vcs4g31nJTxwavDPwfmc9N8Dv3O0/cK1zAP7AD+V3PtLjm8C06HRNTV7KmjRfg+Sax8djXgyaeTRz+HjMryocGdQrvvv0X/HoE+dMOGuvZtBX9dwA3o0Nx/VXnP/7JpCTxUmrvZi0aa/uMnov63S9z8k1nzT6VjzYXr1G91Ju0nrfvzVfXRvOz3FuAO/GhuNzADb80P3Kj58DpOsBMF2HiU8cGfATU2ziusac7Kp3ubD0Jh4P1rnJMzeN01rwz+Z+RZYzzg3AJ7Fp/fRN4Dv7wGn2CSMPj/ldnTKS57y+VuI74/4a0wPH3ByetWfHKZeMu3x67zjyUj03QNqVjbSf0ynCS/uxeurgyGJemWgp3xo98MzN4N1p9CbevXDWGE8ejCtruS9p7unjiV/xnOfnODeAd2bD8TkAG37ofuWfXAfTNeKGzicPxh5j1mHeK73mPO48Hn3lJ40+POZV6U0eGkzxaDXmyz5ar6nPTPdTppnkkweXGLxizw3Ajm1aP/0gyKeD05M09it51uCoZNY8cfbpgcNjXj4a7O+oXr+vl54HfvJ6Ts1X+cSRx9pXeecGYKc2recAbPrB89qPPwjhqpiuE5qqJh7NXB8nxmsmn4zJg3GFX813L2P3olEnD6Zq4tKz0YNHvcqAX63Oo+fcAOzEpvXT7wKm01p71P10qjrjvb3j8Z3RNebONW+9xubhrHU+zenreZ01172vWNP5Pc8ea3emdHPnBmCnNq3nAGz6wfPaj18GIaQrA6/qnW/WvK+dK6Z0OK/TNebOX+X72u/M/RwrOXe838HvVmN6O3O3Ln09j75zA7ATm9bH7wJW358T6JNFLx4VvSqa+9DMvTqecr0OHPXu2czxbGgpFw3G+fS7mrNe48nrbM1ZO3mlpbxzA1zt1ib643cB6X05MT5ZXWNe/XDWyF3xYKs6o/cyN5/GZKzyU0byUm5aE80Zq73uqbGzyLAGj8fcvfbODeAd2nB8DsCGH7pfeembQF8xXB9ozCsUzQvYt17j5JFhL2lkJQ8N5q7CpzXptYfmmjLw6YVBr7qq0UMW86uachN7boC0Kxtpj98G8s4+YdMpgjODRlZV+9Y9NkNG0tzTx+bxUhZeqs6gN3GT5gy4lAVnr2vMKwcuaayzWp1xboDVXfum3DkA3/SDXX2tT78LSI1cP/Z8jVi/GpPhPo/pm7SUQR9ezXuGPXhX+DvOPXdjZ5HvHvvoScMjw0zXmNNzVZ1xboCrXdpEH38SmPaAU8YpYl5s0lIG2qs8/VW9Pjq5zF3hzaCZw3/WcwbjlJU0+KnSZ2b1Gek1f24A7+SG4/FPwtKJWdkj+orltKExLw+txl/55TW+Knf1Wb9yba/5bK57+x4469wAfXc2m58DsNkH3l/3008CDfiqQF+5WlJf0sh0TRxrJo9eGOZXdeJSfuJXOPfB32n9memz7gzrNU580pxxboC+i5vNHz8I4qT4dLAXeMyroiXe3OSnjIknNzFkwdxV8ymv96/yZCX+TmNNMqiluxcOzVz3mLvSV9q5AbwzG47PAdjwQ/crj98EGuzjdO3ATJ6vH7ikkVUVH94e48mjH7bqxNunN/F4zl0dr/TeMemZ+vqJce65AfqObTYffxfgk/LqvnACpyyYWgPOWl8bpnS4pNFnD+0rKmtXFmugMb9bB94cvfbQzK2MnZH4cwOkXdlIOwdgow87verjr4K5Klavmlf59BBek1xz+HhUMx533h7jlEFfMd23R8ZU3Z967fccvNU+OPoqD83ZSTs3gHdow/Hj18GvvrtPHRk+afjW4KgwzKuax0djbj6NE/+sRq7XJAOvKj4eczNpDO+MxKGZR2OtyYOtau7cAN6ZDcefbgBO0zt74RP2bB697kN79pmc0XudCWcNfsWDrTplJI785FlL49SbuK75Gc8N0Hdns/k5AJt94P11H/8EcJ34ekDrTTWHM7OiwVQGvUkr/0//mp47edZW3o39MeuM5Jutsfnu1fzcAGlXNtKW/iDE+8GJ4vQxLwbNfB+vML3n3bmfkSw/R/LhqHc8GXDU6u8emV9Vp3w/B+vB1/zcAOzKpvUcgE0/eF57/HXwA/qo7xXzl68YXy2Z/n/VvThk2EODcYWbGPNpTEbyyE0MXvUlP+V1bcqYvJ7T5+7tnp/13AB9dzabP34bmN7bJ6X7eD5paJ19d95zvSZjM2jvrPtsxsTzbBPjZ4WjL3nWEmd/Gp8bYNqdDbzHH4WuniI4Tqn3CA2mPDS45FmDS5WsVT5xKQMtrZky4JI3ZdFXld5Vnl76aj71mqMXnlr6uQHYnU3rOQCbfvC89tL/GwhcletjumLMT+OUAc86NYezBkeFqfnEwd9V8shiftU3cSuec1mLvvImzb2M3YtGBvOq5wbwbmw4fnwTyLv75HBirHUOpnSPr7gp6yqDrFTTmmhprZQxaWSZSblweMyrjzGeNTznM7ZHb9ISj5YqWeWdGyDt0EbaOQAbfdjpVT/9QUiCntV8xfTe6QrrLHP3oK3U9Bxk2Usa+XjMq9I7eeYZ3/H4Uz5ZVeHQ6Gfea+fLPzdA36XN5uMNkE4MpwyP+Tv7RtZVRl/DPF7SyLOH5vpqBn3OYpzWNI8/aTBkVjWPnji8xONVPTeAd2PD8TkAG37ofuXx5wAGGXPdpKsleWj0uw/PGhwe86pJs894hUtr0l+1Z5jHo7rPHDqa+aTBU2FqTi8VxtU8unl8a+cGYKc2rY9vAnl/TknNfVLwp+peuJ5hpnvVY58M6sRPHv2uibfPmOcxn7TOM7+q5JF1xU16z2BePSnXPrnnBmAnNq2P3wam05FO0bRPr2a4L61p/2r91HfFlu7M1Gu/eDPd637Nzbi3vNUvZ9CTsuCSR19VOGvnBvBubDg+B2DDD92v/PgnwCJjrox0tSRv0sh0nXLNrYyd1Z+D+UrOFeN8mJQLZy9pZLh2jnkxznPP1Zhe9yXt3ABXO7iJ/ukHQX5vTow1xpMHUxWOk8jczLNjZ5BLvctyb2edAWcNHg2mdDQqrL3Em2NsDo06eV6bsXk0sqqeG8C7seH4HIANP3S/8vg/DTN4NfYVw9hXDRo15ZjHN49vDe7VSmb1p1z7xfR513pG4quHr86ju95l4K9kOdf8uQG8MxuOH98E+lSs7AOnL7HOmrjUmzTnle/M7qX+pK32JY717aGxVvI6A3tVU4ZZ+9Y99prw1s4N4N3acPz4bWA6HWk/OsfcrE8YOtzkFZv8nsHcNfVNa7p3ZUxWsWmtKcO9nXMWnLXOPzsns/rItXZugGd39Jvx5wB8sw/02dd5fBP4bGPi0xUDh8e8qq8idDTzaDCT51xz9JJlDw2mKn7yzDGeuNWsiZs8ngGm5jzPnXZuAHZv0/qlNwCnLu0lnk8kXNLwquKTYY/x5MFUJctaGpO3ysOt9sGntdHMkIv3SnUe/ecGYCc2recAbPrB89qf/gn4iquGcFeun5RvDc69+Mkzx3iVg0+1Z/AMZjtjz3zi8O0lzZnPjMkls3qTdm6AZ3b1G7KPG4DT8dXvOOXi+ZSy/qqWMnovTGXjWWPNqZong1p99vsczgwa1WujmbfPuPv09fXhUz03QNqVjbRzADb6sNOr/hdY6JReNdjnHAAAAABJRU5ErkJggg=='),
(45, 49, 'ORD-FQAHYBUH', 177, '2025-12-10', 46, 2, 400.00, 'Pending Consent', '2025-12-10 17:56:34', '2025-12-10 17:56:34', 0.00, 0.00, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAQAElEQVR4AeycgXbbSK5Ec+f//3nXpc4loSZIyY4zk0i9J7UFFAqg2EJ6PZ533j8/fvz436/gf+U/zinSNrtqxvofsX65+p/V7NFf2Vpl62rmYbXK0YOq/WqceVf41fnpzwJ88PrzriewLcDHpv34DLoDs7/W1IAfcA9rj7jOS1z9yb8C2D9LnWcMo25en9FpMPzVZ9z551o8ajKMmYBSy+n9DOqQbQGquOL3OYHDAgCHv6mwa1dHA7sPRqzfDTUPw/DAztED2DUYcfRnAMPvMyt3/TD8sLM9MLTaB0dNf/UZw9E/14DDDaznWYbxHOi5m3NYgM60tNc9gbUAr/vdPvVm37oAXoOVrz6Fvs5jrTKMq636YWjVZ6wPhgd2tlbZvrB64gCOvdEFjLq5/WG1jlOfAfez0gdDm71fze371gVw6OK/5wS+dQHgfEvhWIPntPk4YfTB/oMT7Brcx3P/WQ57nx4YWv4WzoBRg+PnsD8Mw5dYwHOafp9t/l38rQvwXR9qzfn3TmAtwL931n/kkw4L4FVzxldvYQ+M6w12tk9PWO0Rw5iTnhlwrF3Nm/trXvvgfi6MHKi2LQZuvz9x3lYoAQwP7P+ToT9crF8KM+MK3dDDAnSmpb3uCWwLAPt2wuO4OxIYfXUL9anB8MD+t0BP+MoHoze+Z+CszgtjFtCVt9/KAXd/szMTjlo75KeYnuBnekcwZkF/Hpph+Mwrw6jBc1x7twWo4opf9wTmN/snm/krmAfOubNn/Su5s2DfdOfAuaansrPCVT+L4Xw+HP/2Zq5wpnlYrTKMZ1TNOD2BeTj5r2LdADnJN8ZagDf+8vPqn14AGNcUDM4Q4XUEowY7z554rzRrYRhzEgfpnRFdWDOvDGMWHLn6jJ3VsZ4wHOfB0FI/w6O59sFxFtxrMHLAttsPsMCNFWHkwI9PL8CP9Z+XOoFtAWBsRX07OGrW3VwYHthZT2XY6zBi6zBy2Nla+OpZMHriEzA0+yrrqVoX65NhzASU7tgZd+LPBLj9DYTn+GfbXc+VZq0yjGdVzdjPGt4WwOLi9zqBtQBv8n2fvea2ALkOgmpMHnQajCsmdVF9xnPNPKyncvQZcHzW7OlmVM3YPhgzoWf9MOr2ha0lFjB81r6DnV25zlWvmrG1ytYqbwtQxRW/zwlsCwBjg+HI9Thg1N0sGDkcfxtW+zq/dWthtcrRAzXYnwkjthaGocE5Z96M9AoYvXMOQwcstTzP/kzeDvwp1jk/pe2Hxa6mpzKw9WwLUA0rfp8T2BbA7eleHfaN0QdDMw/D0OoMGBoMrjVjGDXY2dp3cD7bjDoXxnOrp9Y/EzsDxkx4nj/znM4L+7Ou6rW2LUAVV/w+J7AW4H2+6/ZNP70AMK4Zr7puqrXKnQ/uZ1U/jBocuc6qPcbW51x9Zn2wP0uPNfOwGux+tdQD8zOOJ6h1GPOiBzByIOkNwPYD3E34+K86w/hDvv2B3T/XYvj0AqRp4XVO4LAAbknl+rrqMDar1oxh1GBna5XnWcBWthbexCYAbn8jmtImwfBAzxrzLKH2LMOYrR9GDijdPidwY58DIwc2n7VN+AiAW99HuP2Bew1GDjs7K2xjYnFYAE2L3+ME1gK8x/d8+pb/wH5dAKfGueAVMuufyYHTaw1GDXhqJHCbBftvJGFoftZwNwyGD3aefekVMHzm4Su/tfgEHGfMNfvC1hKfQU9lGM+BntcNcHaab6Jv/1fB3fvC2JpnN8oZ1a8mX9X0hKvPOPoZ9ITPPNFTDxLPiD5j9pzl9sE4M9jZHjhq1io7qzKM3qoZ2wvDAyg95HUDPDyiv9Pw7KdeC/DsSb2ob1sArxNg+2Gqe2d9cuep2uyDfb61jmH3wYidW/1qlWH49V3V4rEOow9QujwL4LSeucJh5pXhOAN2DUZsD4wccOzGesKKiYVa5W0Bqrji9zmB7R8Du1d2c4Bt0+E+1lMZdk83Vw2GzzwMQ6vzjFOf0dXUYMyCna3Nc+YcRs+s19xZYbj3w8hh59oLQ++0zJsBR7+9cKzBUXOmfeF1A+QU3hiHBXBLwnC+RakHMDzAdozRxSb+DNTDP6U7ih4A262jIXpgXhl2P4zYenoEjBrsbE1/ZWtw7dcn1xnGsM9Qq2wvDF9X0xOG4UscdP6qdfFhATrT0l73BNYCvNh3+9nX2RYAxnVSB+RamQHDB4Orv4vh3gcjh/139l3fsxqMedXvZ67aHOsJW0s8A8b8quuHUYMjV7+xfZWthaueGI5zYdfiqYC9Bse4eo23BVBY/F4nsP27gGxgUF8fxhZVzTjeGdZg9MH+t3z2Jofhsy8MQ0tdwNDgyHoqZ06glliowT7LWmUYdTUYOaB0x86VaxG4/UBrrTKMGlBbbnH13YSP/+q0D/n2p9a6+Gb6+K9aWzfAx4G885+1AO/87X+8+7YAwO2a+tAu/3h9wNEPQ9MThqE5FEYO+/88WKsM577MFfbA7ocRW6sMo2Z/GIYGO0c/AwxfNxeONed0fmth64kD83DyAMZ8IPIdgNt3CNzpV8m2AFemVXvdE3jq3wV0r59tDGoteVA1Y+C2nakLGJqesLXEAobPGowc0LL9P3bUE7YI3J4N17eO/o5hn2E9zxBqHcPovarB8ACdrdV8NvAjMA8nD2pj8hnrBqgn9IbxYQHmDflKXs8x2xhU7Sr2eekRs189rL/juS/5s754A/2JZ1gL57NURBPq5mG1OlMt9TNUvx4187DaIz4swKOGVX+tE1gL8Frf56ff5vI3gV5Jdapax/pyBQk1WT3sDGvhTosepGdG9BlXM6x1PM85y7teP1fXY632Xfm6mlqdMcd6wtZ8dlgtdbFuAE/iTXn7x8BsSOCWhJMH3dlED7paekU8gT71cPTAWjj5jOhnyJyg1u2v2hzrCc+1mmd2ULX0zLCubh5Of2CtcvRnkDkznDPrya9q9XnrBshp/cX41Y++FuBXT/Av7z/8EOjVEb56N6+RzpNeYV2/etjaI7ZXn3k4cwJrlaMHVTNOr1DrOP1BV7P/Eac/6Hzd3HiDrlY151XNuKtlZqAnvG6AnMIbY1uAbEZQz+Jqi+KdYa99la9qdU7tMZ57zR/x3B9/fdYcpy6sOaNjPZXn/tTstfYrnHln+MrcbQG+0rx6/v4TWAvw93+Hv/QG2+8BnOJ1FVbrOPWgqz2reZVljlCrM9Q61md/5Wdq8dQe4+i/E77L1TP0hLvP1WlX8/Rnnlg3wNWJ/cG17/poT/1joJtT2Q2qmnH34fRX7nxqz/qunumsju0LX9Wt1c9jnN4Z+quu31pla5XtrT5ja2F7rHUcn+j86wboTu2NtO1nALfDbQmr1fNQSz3oaldaeoQ+Z4bnWjxqcjSRnsA8nPwMqQddPfoMfbOe3Frl6DO6z91pc1/NfUbVvhr77PC6Ab56ii/StxbgRb7Ir77GtgC5DoI6KHnQaVdXkrVw+gNnRBNqqQu1yld++/SEa29iPeHkQWKRPEjvDD2POP3B3J88epBYJA+6uXq6WnrOUP16nBW2bi28LUCShfc7gcMCZFNm1GOx1m1T9X01nufnOc6y1rGeZ7nOyDPO4LzqV6tsf9Wu4jpvju2b9eTWKvvs1GdUn7WqHRagFlf8+iewFuD1v+PLN9x+E+j14HVS2VrYaYln2KMnrCfxGfSEzzxV9zlh9cRCTc5coaY3PNf0hLtap8UbZN6MK396xNxXcz3OClu3Zv6I9YfXDZBTeGNsC+DWZLPE1bnor2xf1eb4amatOSvsjFo3thafsCbrCas9Ymel5wzdDPu6Wjen+uztWF+doc+aeVjtEW8L8Mi46q95Ap/+dwEeQ7YsMK8cfYb1usHPxs5yhnlY7VfYz5F5Qu1qrt6wPvuiCWvmla2F7ZWjCbWuV01PWM3+ytbC6waoJ/MHx7/ro60F+F0n+5fM3RYg10bw6HPHU9H5H9W7HrVcS4F5ZedWLd6gavrk1K9gr/6wfmsdxyf0y4/8XV3tqzPsC8+zoglr4W0Bkiy83wlsvwjy1d2SsJpbHlZLPTAPpx4kFsmDeGfoqbpa5fQH+rpa1fTJtdbF+irPvq7WafmcZ6j+zmP9qqYn3PnU5s9fcz3hdQPUk3nDeC3AG37p9ZW3BciVEtSicfQZuT6CWZ9zZ8QbmFeOPqPWnVk146uano7n553lX53fPbNqz8zVE669Z3F8ovP4jrW2LUAVV/znnMDv/iTbbwK7B3Ubo+ammZ9xN1fNGeaVrYXVfYZ5WO2K4xP6zM84zw30V+56rFtLr1CrPPtrzVjPGevrnmOPnnDnWzdATuaNsRbgjb/8vPrh9wBeHeEYZniNpB7M9TnXP+vJ0x/oqZz6V1HnJO7mRJ9RfflcwexJri91ET24qukNxzvD3llPbu1ZTk+QZwl7o4t1A3gqb8qXPwS6Jd3ZdDW1yl3vrLmhYWuJhZpz1cPWOk59hjOqX4+1sHVrla11nN6gq1WtzjO2Pufq4cyeEf0ZdHPXDfDMyb2w5/AzQN0uN+aKu7OpfuvOrTU1PZWthWtP4jNfvIH1xIF55cwR8QS1njyomnH0wPwR+5zqS/8M69ED83DyIPGMbn6n2WctvG4AT+VNeS3Am37xvva2ALkOZuTKCTRXjh5UzTi6OJuZuv7Ksz+59fQE5mecnsB6esSVZq3y3JdaZgeJRfLA3L5wp8U7Q59c62od5xnBVS11UX3bAlRxxe9zApcL4AZ2x3FVq/5u66w/O2P2OzM81zJTrePUz1D9etTyLHGl2VdZ/2fZ54XtrXPnWM8Z66/1ywWoxhW/5gmsBXjN7/Xpt9p+E5hr5iuoT/KK6VhfrXXP01dZX9WMnWce1t/VUg/0hJOfwRmV9XaatcwVapW7WuadofYadzOsPcvrBnj2pF7Uty3A2ead6d15uJFXXPu62bVurM+56uFO05/6GfSEO49zO9bf1dT0VM6zZuivbE+nWQs7K/EZ9ITrPONtAc4GLP21T+CwAG7GGV8dR7Zsxuyvc+dazTufs6uv02pv4upPfobq6+Zat988rF+OJjp/p81+Z4Xnmv2V9Zxx5sw4LMBZ89Jf8wTWArzm9/r0W/2WBeiupfnqSV59c5y6mGuP3s6+Z/nRvNTrrORB1T77GdMf1BnG0b8C+8P2z58rubXwb1mADF743An8V+5/bQGyeTOyqUH38tVrPd7AvHL1G1s3P2N9lfWqmYfVKudzBVW7iuMNOk/0IM8SyYPOr6Y3rFY5/UHV/rUFqA9d8Z9zAmsB/pzv4j/5JIcFyBVxha9+SmfW/lxVQdX0VbYeb1BryQM9YeuJA/MzjmfG7K11a3muqPU57vxd36zZF55r8zOSxyeSn8FZ4cMCnDUt/TVPYFuAbMNn8NnjcLYbWrnO0le5ehN3tTpjjqvfePYkz2yhT05dqOkNz5re8FyLP/qM6MGsf3eeZ4htAb77IWve33ECawH+4+/pv378/wEAfwgdzQAAAANJREFUAP//R+xcXQAAAAZJREFUAwAXB5cL1zHXmAAAAABJRU5ErkJggg=='),
(46, 49, 'ORD-ABCLZJ6T', 177, '2025-12-11', 46, 3, 600.00, 'Consented', '2025-12-11 15:55:30', '2025-12-11 15:56:58', 0.00, 0.00, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAP/klEQVR4AeybgZbbxrFEdfP///yeS+MLFAcDkLuS44iEjyvVXV3dAIetyXqd/OfHjx//9yv4v/rrlTll38LuU2zN2NqK9YTnerQZsyf57HmWp+cM3avnq5p9Z9zzvhtnAf7qvf/+1BPYFuCvLfvxFVwdWM/Rp2bebC0M/AC6vMXAaS29Ah596s0wPLDz9qC/Ar1/hT//Nm/+WTj5j1d9J+0/ZVi/289i/Uc/65W4Wn9sC9DiHX/OCRwWAPatg2N8dTRuH+x9+mHX4DzW76wV6zlje2A858ynrr8ZznvhWINHDUYOO/u85tUz1dr3Sgz7s+AYr2YcFmBlurX3PYF7Ad73u33pk/3WBYBx7XiFhee3iCasmYfVYMyCnVe1lQajJ/MCGDmg/eEHXkXg5w+ZgNKSMzMANn/yAIaWWKyGwPBd1ewPw7l/NeOZZv23LoBDb/5zTuAfWQAY2wocTgI4/KlpU7b9DDB6uw5HrefNMQw/HLm9PkMNjn49YX0y7P7UA2vh5AHsvuhniDc4q39X/0cW4Lsvc/f990/gXoD//pn/Tz3xsAC5Zq7wytuv+l/pO/PAuCatw8iB7Yc52DV9K169m1r7YcxT0xNWa4bhT32Gvtbh6Nf3Xe75q3g197AAK9Otve8JbAsAYyPhNX71SGDMcyNXfTA8wFYGTn9YdFYYhi+x2IZcBDD6gKXrK7NWA4Dt/a3Drjkfjpr+Zhi+1oxh1OA1ti+8LUCSG+9/AvMn/I+b+F3ugc6AfRNnzTzcvcYwelMXMLTZAyg98Flfm/SEWz+LgcOf6JUXhm9Vy7MEDJ952J7EgXk4eZBYJP9V3DeAp/mhfC/Ah37xfuzLBYBxTWluhvNa+16J+xpb+bs+xys/nL8bjBocuWfBqM/P67z9rSfu2lUM4zlw5O6DUW/NGEYNjqwnDMf65QKk6cZ7n8B/YGyFHxNGDte/ZMmWB/Y1Rxfqcx4dxrMSi1d8MPpgf0f7w86Qo4mVZq35ygfj+e2HR83+cPuMoz+D3rDexGfQ03zmVb9vAE/iQ/legA/54s8+5vZ7AHi8wtIAQ1tdKTBqsHN6zgDDd1ZXh9d8X/H3+8OY35qxM1cMow/YysDhdwOwazDirWERwPDAa7wYsfz3IXCcZ6+fN3zfAJ7Kh/L2Q2C24Qx9NjA2a+WFUWu/sX7z8EqLPkMfjPnmzd0DwweDu2YPjBrs3D7Yddh/2LQ/3P6rGB5nwZ5nzhlWM2HvtQ5DM3/GMPzA/f8L+PHhfx1+BoB9OzwbuNZg1PU3w2MNRg47t98/Da3B8F7V2m985bd2xq/M0POMfUb71GB8Njjyyt+asbPMm601d/3+GaBP4wPjewE+8Evvj3z4IbCLxn19XMX6Yb/O1ORVP+x+GLH+sD2JZ1iD0Qf7D2ywazBi/T0HRq21OYbhgX2+s8Kzf5XHJ57V43vmgf2dgAd7+gNg+0dVGHF0cd8AD8f2ecn2Q6Af3c0Iw9gYa2EYGgyONiO9M/TA6AOUtl9kpEcR2DZ3pcFeB7Q8ZWCbCyO2CUYO+59yGJqeM867B2f16DBmwc7pEfGcAUZP16/6YPj1hO2FUQPufwz88eF/3f8VcC/A4wnAfj08Vh6zXCkB7P7kwaPzMUtdwOhth7Vm660Zr2ow5s6eeNWecbzPAOM5sPOqB0a9az4fRg121qcnrPYrnDkz7hvgV070DXq/vQAwNrY3yvOAUYMj6wnbm1jA6DFvhmPNGTBqcPwBTk8Yhq/nXsXpCa48qcUTJD5D6kKPebM1GO8Kx88EaNt+iN6EJwGw/SD87QV48oy7/C+fwKuPvxfg1ZN6U9/2m0AY18Kzz9lXVeJX/SsfHJ+ZmUH7YfiiBzByoG1bDPy84uINYOTAwQP89MIjb8aLILPFhW0rweMz4DHfjH8Hzg7D8CYWf9suCUYfsPTdN8DyWD5HPCyA29UMbH9KPBrYNRixte6dNRhe2H+w0ROGvQ4jdh6MPL4r6F95rDWvfGpwfCYMDY7sXPvDaitO/Qywzz/ztL6a3xqMed1zWIAu3vH7n8D27wLcFBhbAmyf3loY+HkbJJ4BowZH3oZVAMNX0mU4Py+5DYmF2orh/Jn2h+feaDPaYw3GfPMwDA12tjd1AaO+qs0eQNvP7wN44K24CJwVvm+AxQF9knQvwJt921/9OId/DMy1IODxWoH9BzcYtWcPdNbKt6qtNDh/Fhxr8Kg5sxmGB1i92nadWgQO2mqemn3hlRY9gONc2DV4jJ0VTv8Z4LEPWFrvG2B5LJ8jHhYA2DY9WxasjiN6AEd/dAF7HViN2p4He93+5mXz3yKwzflb2nLYazBiPWGfkVioyephOJ8BowY7p2cGjLrzw3oSB+bh5AGMPiDytwBsZ3NYgG9NvJv+2BO4F+CP/ep+z4sffg/QY2FcFbl6hHU41mBosLP+uT86DF9iAecajBrsbJ/zwystemCtGca81uY4vcIajD5AaWO9YeDnlbsV/wqiB3+F29/Jg02oAMaM1AU8amW/DO0P3zfA5VG9f/FyAbIhQR8DfG/rYPT1rMwOvqqlR9gLYz7s/6hq7VcYxtyeMT87NXj0wcjh+n1g98FjnLlX8D2AH0F7ra20eMXlAnTzHb/nCWy/CLr6eG5LWF/iGdaa5000D+tLLNSafY6aeVjN/nD0M+hf8apHX9fU8qwZ1prtfaZ1PXHPTh44qzn6GVYz2nvfAH0aHxjfC/CBX3p/5MsF8Jrphr5S5lhf685Q03PG+ptf7T2b2bqzVtw+4ytfv6N+ufte1bon8Wp+9DP4nOaeYdz9lwvQg+74PU9g+0WQH6+3w9has9vUmrG1sDMSn8G+sP7m6GfQd1Y/0/tdzjytr/w+O6w3cdD+5MEzzfo8K71qzbP/q7X03zdAn9ofGP/qK98L8Ksn+If3b78HyHUQvPp5ci0FK390saqr6clzhbVma/q7ZqwnrHbltxbWf8XxCX151hn0nLF9Z/XoesLJZ8zvM9eT62mOLu4bwJP4UN4WwA3pc8jmBSst+gx9ratdzbcWtte+cPQg8RlSF2ee6M5fcepnWPl9Xti+xIF52N7EM+Kdob91+6ytWM8z7rnbAjxruuvveQL3Arzn9/rypzr8HqCvlqspfY3M8VVf1+zrZ6q1z7qanrBac/SgNePogfkznp/9zG89zxBqzb9asz/s3MRCrdnP0nzfAH1Cf1D8u151WwC3YjXYrWrW37zqfUXrufp7rnU1Pc3WwuqJA/vDyYPEQr95eKVFD6xljogemOsJR58RPdAfTn4G++Obsaqt5ujr2rYALd7x55zA9osgt2PFvXEezcpn7Yq7r+fO8crn3Nmb3Fo4eeCMaEItdbHS9Mt6w2r2hdXk+IRas7X0CuvWVqwnPPeZN8d3hfsGuDqdD6jdC/ABX/LVR7xcAK+gvlLU5B6utvLr0xNWa79ac9e/EveMOe451lozvqrlM4jZZ96sN6yeeIY136HZ2qs8z07evZcL0MY7fs8T2H4RlM0I+mO6eSvNWnqEWvvnWE+z/WH1xGKe8dXcOc09o/WzeOW/0vwcYWcm/gp6vvGKnb+qrZ7XvvsG6NP4wPhegA/80vsjbwvgVeF18irbF171RA9WNV8kdbHSVr1q+s1XrKe5fT57xd1jrM88vNKiB1e1fo94G6uas8J6Ewfm4e6d43jFtgBpuvF5J7D9JtAtcTPCV8eRemBfOHmw6os+Y+VTyzxx1bfyOONX2LnOMG+21tx1Y+vmzdbC6omD/tzJAz3N0b+L+wb47sm9Sd+2AG5bf64rzQ1s/5V2VVvN8Nlh686INkNP8+xJbj2xcG7z7DMP29cc/Suw9ys98doXTn6G1Gfo7c+5LYDFm/83T+Cfeqt7Af6pk/1D5m6/Cbx6375KvD5aM3aGebM1+8PWrYXVUhfRG+rNXTfuuvGqptY8v4d5WJ8zw2qpz0g9aH3lV5PTI1aa86w129esv/m+AfrUPjA+LEBvjOdxpekJu1ntN7YW3ww94bmWPHqQeMZqrpo898y5vmY9anm+mGvxqM2e6KkH1s44niA9QWJhj3lYTU7PK9AfPizAKwNuz/ucwL0A7/NdfuuTbL8JtDtXi1hp1mQ9Z6wv101gHk4edG/yoLV4z9C+Oc6cYNXb3nhmWFfvGWp6VqynuWcYX/V27crfPmP9zb6LnvB9A+QU/ofxT7/a5QK4Mc2+UGtzrKfZTWyvWvtWsT2rmpqesNpqfuqBnmb94dYTp0ckP0N6g7O6urPiFbNmfsb2zTPbby2sv/lyAdJ0471P4F6A9/5+n366wwKcXR9Osu41ot5sLdx64mhinqUejneG/ubZk9x64sA8nDzIM2ZEF/EG5u1VS32GtWZ727vSuiexnnDyGc5Tj2+GnrC+xOKwAJpu/owT2BbAjegNujoC/Vee1PSt2GfFN8Na8+x5Ne8Zxqv3aU2fz7iq6Wm2P6yeWDjPvFl/c9eNrZs7MzzX4okeWAtvC5DkxuedwOHfBmZDRLbmK/D47A/P/XrCqc+IPkPPrHc+Pyd5142dlfoV9MvtdYa1sPXEgZ5w8hmzf64nT++M6MIZeszDep7xfQM8O6E3r98L8OZf8LOPty1Aro0ZXi2rIVe1nqNP7ln6WtPX3PXE9oX1RRdqK37F0315RmBfOHmQWNhjvmI9Z2yP9TxDzDU94VVtpc2z4tkWIMmNzzuBywVYbYxHdFXTE5595uHUv4NsvbDfPKyWZwTm4eRnSF3oMW/OMwI9YevRA/Nw8iA+Ef0MVx5rK+55eV7QvuRB+y4XoI13/J4ncC/Ae36vL3+q7X8QkqvhO+gned30HOvWzJ+x/rDexDOsfZVX79gzrKuZh1faV99r9ifP3EaeJVo3vqrpWXGeJe4bYHVCH6RtC+BGvMpXZ9Qz3NIrvpqVmvMSBz0r+YzZP9eT6wknf4b4hF7zcL9T4mhCf/SvwP6wM5qjB63NcT8v3qC1bQHmxjv/jBM4LEBvxyp+5Vi6T382LzB/xj3D2J7MmWEtPPujCWsr1tOsrzVja2Hf56qmp1l/WD1xkLkieWC+4tRnODNsT2JxWIB5wJ2/9wncC/De3+/TT/dbF2B1xXjVWFu9kZ7m9qmrOSustuLUg6456xnbo8/8GetvzjsEz3rjCbrX2F7zFesJW888sdJ+6wLkwTe+dwL/VtdvXQA3bPVhVjU1N7TZWng1b9bimzF7kvczjKPPcJae5tmb3HriwDycfEb0YNY7T134Pl3/auys7vutC9CD7/jPOIF7Af6M7+kfe8vDAnjVnPF338Trp+c6a6XpD+tLHJifcTzBWf0VPf2B3n7HVaxPbo9as/WVlucGXftqnP7A54SdkVgcFkDTzZ9xAtsCZFu+glePx5kr/1Vt5Xdrm1czup64ZyUPWvsdcWYGzvK9wtGDxEJfdDFr5mfsLNk54bOeWd8WYC7c+WecwL0A//L3/G8//v8BAAD//0rxKr4AAAAGSURBVAMABC7/1W5OTPoAAAAASUVORK5CYII='),
(47, 49, 'ORD-COL9HM4T', 189, '2025-12-12', 46, 1, 200.00, 'Consented', '2025-12-12 01:48:21', '2025-12-12 01:49:20', 0.00, 0.00, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAgKADAAQAAAABAAAAgAAAAABIjgR3AAAMc0lEQVR4Ae2bgXIcubEERYf+/5fla1/kXrpZ04Pd5SkUBBjx3EBVdmEGC0N8JP3x48ePX3/938tfv3790/7x8XGbk/hXtL5QWpvcyXNO4uz3Mfldr7mz4J7V6Ev5fY0r5k7/zx1w/O+9Az95vbvTBkf1aUajrmatcuSyZuqzBkefPbTOoFPpgWOOf1ef5VMea5c35U3eXe65AdIObaQ9bgDe2acOzXU6bXjOSJrz+hjeetLs9zE8z8G8c8yTTy9Mn6NTe8Ydn/roIYs57F2948l1zrkBvBsbjs8B2PBD9yt/+ifA5rPjuyuo8nwNwSctrQ1Hn/Os0QvP3DV5KYMe83BJm3i8qikDP3lJg3+nnhvgnd37Br1fegOk/eDk4nnu/wbhJw2PXjOThkf/M5U1yKA6I2n49npWMUmjN1X45L2jnRvgnd37Br3nAHyDD/GdV/j0T8BXXzXk+Up89YFTVtKmfPjE+Bk97mzKgE8e/fYSjwb/bHX+au+5AVZ36ptyjxvg3dPn/fFJJBeNuXlriesa88qgd9Jg7ta0Tx69zK/WdK+ZGtNL1jNasfVFL1l/q3//J5611fG5AVZ36ptyH3+dqH9+of8vviSnNC2H5+XNJd9sjc13z/2vcs4g31nJTxwavDPwfmc9N8Dv3O0/cK1zAP7AD+V3PtLjm8C06HRNTV7KmjRfg+Sax8djXgyaeTRz+HjMryocGdQrvvv0X/HoE+dMOGuvZtBX9dwA3o0Nx/VXnP/7JpCTxUmrvZi0aa/uMnov63S9z8k1nzT6VjzYXr1G91Ju0nrfvzVfXRvOz3FuAO/GhuNzADb80P3Kj58DpOsBMF2HiU8cGfATU2ziusac7Kp3ubD0Jh4P1rnJMzeN01rwz+Z+RZYzzg3AJ7Fp/fRN4Dv7wGn2CSMPj/ldnTKS57y+VuI74/4a0wPH3ByetWfHKZeMu3x67zjyUj03QNqVjbSf0ynCS/uxeurgyGJemWgp3xo98MzN4N1p9CbevXDWGE8ejCtruS9p7unjiV/xnOfnODeAd2bD8TkAG37ofuWfXAfTNeKGzicPxh5j1mHeK73mPO48Hn3lJ40+POZV6U0eGkzxaDXmyz5ar6nPTPdTppnkkweXGLxizw3Ajm1aP/0gyKeD05M09it51uCoZNY8cfbpgcNjXj4a7O+oXr+vl54HfvJ6Ts1X+cSRx9pXeecGYKc2recAbPrB89qPPwjhqpiuE5qqJh7NXB8nxmsmn4zJg3GFX813L2P3olEnD6Zq4tKz0YNHvcqAX63Oo+fcAOzEpvXT7wKm01p71P10qjrjvb3j8Z3RNebONW+9xubhrHU+zenreZ01172vWNP5Pc8ea3emdHPnBmCnNq3nAGz6wfPaj18GIaQrA6/qnW/WvK+dK6Z0OK/TNebOX+X72u/M/RwrOXe838HvVmN6O3O3Ln09j75zA7ATm9bH7wJW358T6JNFLx4VvSqa+9DMvTqecr0OHPXu2czxbGgpFw3G+fS7mrNe48nrbM1ZO3mlpbxzA1zt1ib643cB6X05MT5ZXWNe/XDWyF3xYKs6o/cyN5/GZKzyU0byUm5aE80Zq73uqbGzyLAGj8fcvfbODeAd2nB8DsCGH7pfeembQF8xXB9ozCsUzQvYt17j5JFhL2lkJQ8N5q7CpzXptYfmmjLw6YVBr7qq0UMW86uachN7boC0Kxtpj98G8s4+YdMpgjODRlZV+9Y9NkNG0tzTx+bxUhZeqs6gN3GT5gy4lAVnr2vMKwcuaayzWp1xboDVXfum3DkA3/SDXX2tT78LSI1cP/Z8jVi/GpPhPo/pm7SUQR9ezXuGPXhX+DvOPXdjZ5HvHvvoScMjw0zXmNNzVZ1xboCrXdpEH38SmPaAU8YpYl5s0lIG2qs8/VW9Pjq5zF3hzaCZw3/WcwbjlJU0+KnSZ2b1Gek1f24A7+SG4/FPwtKJWdkj+orltKExLw+txl/55TW+Knf1Wb9yba/5bK57+x4469wAfXc2m58DsNkH3l/3008CDfiqQF+5WlJf0sh0TRxrJo9eGOZXdeJSfuJXOPfB32n9memz7gzrNU580pxxboC+i5vNHz8I4qT4dLAXeMyroiXe3OSnjIknNzFkwdxV8ymv96/yZCX+TmNNMqiluxcOzVz3mLvSV9q5AbwzG47PAdjwQ/crj98EGuzjdO3ATJ6vH7ikkVUVH94e48mjH7bqxNunN/F4zl0dr/TeMemZ+vqJce65AfqObTYffxfgk/LqvnACpyyYWgPOWl8bpnS4pNFnD+0rKmtXFmugMb9bB94cvfbQzK2MnZH4cwOkXdlIOwdgow87verjr4K5Klavmlf59BBek1xz+HhUMx533h7jlEFfMd23R8ZU3Z967fccvNU+OPoqD83ZSTs3gHdow/Hj18GvvrtPHRk+afjW4KgwzKuax0djbj6NE/+sRq7XJAOvKj4eczNpDO+MxKGZR2OtyYOtau7cAN6ZDcefbgBO0zt74RP2bB697kN79pmc0XudCWcNfsWDrTplJI785FlL49SbuK75Gc8N0Hdns/k5AJt94P11H/8EcJ34ekDrTTWHM7OiwVQGvUkr/0//mp47edZW3o39MeuM5Jutsfnu1fzcAGlXNtKW/iDE+8GJ4vQxLwbNfB+vML3n3bmfkSw/R/LhqHc8GXDU6u8emV9Vp3w/B+vB1/zcAOzKpvUcgE0/eF57/HXwA/qo7xXzl68YXy2Z/n/VvThk2EODcYWbGPNpTEbyyE0MXvUlP+V1bcqYvJ7T5+7tnp/13AB9dzabP34bmN7bJ6X7eD5paJ19d95zvSZjM2jvrPtsxsTzbBPjZ4WjL3nWEmd/Gp8bYNqdDbzHH4WuniI4Tqn3CA2mPDS45FmDS5WsVT5xKQMtrZky4JI3ZdFXld5Vnl76aj71mqMXnlr6uQHYnU3rOQCbfvC89tL/GwhcletjumLMT+OUAc86NYezBkeFqfnEwd9V8shiftU3cSuec1mLvvImzb2M3YtGBvOq5wbwbmw4fnwTyLv75HBirHUOpnSPr7gp6yqDrFTTmmhprZQxaWSZSblweMyrjzGeNTznM7ZHb9ISj5YqWeWdGyDt0EbaOQAbfdjpVT/9QUiCntV8xfTe6QrrLHP3oK3U9Bxk2Usa+XjMq9I7eeYZ3/H4Uz5ZVeHQ6Gfea+fLPzdA36XN5uMNkE4MpwyP+Tv7RtZVRl/DPF7SyLOH5vpqBn3OYpzWNI8/aTBkVjWPnji8xONVPTeAd2PD8TkAG37ofuXx5wAGGXPdpKsleWj0uw/PGhwe86pJs894hUtr0l+1Z5jHo7rPHDqa+aTBU2FqTi8VxtU8unl8a+cGYKc2rY9vAnl/TknNfVLwp+peuJ5hpnvVY58M6sRPHv2uibfPmOcxn7TOM7+q5JF1xU16z2BePSnXPrnnBmAnNq2P3wam05FO0bRPr2a4L61p/2r91HfFlu7M1Gu/eDPd637Nzbi3vNUvZ9CTsuCSR19VOGvnBvBubDg+B2DDD92v/PgnwCJjrox0tSRv0sh0nXLNrYyd1Z+D+UrOFeN8mJQLZy9pZLh2jnkxznPP1Zhe9yXt3ABXO7iJ/ukHQX5vTow1xpMHUxWOk8jczLNjZ5BLvctyb2edAWcNHg2mdDQqrL3Em2NsDo06eV6bsXk0sqqeG8C7seH4HIANP3S/8vg/DTN4NfYVw9hXDRo15ZjHN49vDe7VSmb1p1z7xfR513pG4quHr86ju95l4K9kOdf8uQG8MxuOH98E+lSs7AOnL7HOmrjUmzTnle/M7qX+pK32JY717aGxVvI6A3tVU4ZZ+9Y99prw1s4N4N3acPz4bWA6HWk/OsfcrE8YOtzkFZv8nsHcNfVNa7p3ZUxWsWmtKcO9nXMWnLXOPzsns/rItXZugGd39Jvx5wB8sw/02dd5fBP4bGPi0xUDh8e8qq8idDTzaDCT51xz9JJlDw2mKn7yzDGeuNWsiZs8ngGm5jzPnXZuAHZv0/qlNwCnLu0lnk8kXNLwquKTYY/x5MFUJctaGpO3ysOt9sGntdHMkIv3SnUe/ecGYCc2recAbPrB89qf/gn4iquGcFeun5RvDc69+Mkzx3iVg0+1Z/AMZjtjz3zi8O0lzZnPjMkls3qTdm6AZ3b1G7KPG4DT8dXvOOXi+ZSy/qqWMnovTGXjWWPNqZong1p99vsczgwa1WujmbfPuPv09fXhUz03QNqVjbRzADb6sNOr/hdY6JReNdjnHAAAAABJRU5ErkJggg=='),
(48, 49, 'ORD-LBTA3NEC', 189, '2025-12-13', 46, 3, 600.00, 'Pending Consent', '2025-12-13 12:00:48', '2025-12-13 12:00:48', 0.00, 0.00, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAgKADAAQAAAABAAAAgAAAAABIjgR3AAAMY0lEQVR4Ae2cgY4buRFE7cD//8vOdQ5Pftcq9XC8inEQuUDSZNXr4gzF0Guvne/fvn37+dd/fvvr589f7d+/f7/MSfzvaH0hr+284uzR15lXHHyqKQPOa8Ld1egjs1fndW91/p9V8HCfuQM/eK2r0wZHnU6fs+CskZE0vFRXszqX1oFJ65RGDxzzV3zX7/K9v+asXeMpb/Kqt38599wAfXc2mz9uAN7bpwPNdTpteCkjac5lTAbzqkmz38fwrMm8c8yTTy9Mn6NTe8YVn/roIYs57FW94sl1zrkBvBsbjs8B2PBD9ys//RJg8+6YK8hXDRpZyUsavCucM5NGT/LQqLBVnWu9xubhkkbf5BWTMuhNXtLgv1LPDfCV3fuA3rfeAOwHp5W5qz3/rwQmaXj0mpk0+lzhraUxa8BTzSYN317PKiZp9KYKn7yvaOcG+MrufUDvOQAf8CF+5RWefgl4x1WTMnwl/u4Dk+uspE358IlxrsedTRnwyaPfXuLR4O9W56/2nhtgdac+lHvcAF89fa/2h1xOJ3Pz1hLXNeaVQW/SvEYf01e6e+HQ4JiXnzT6qDA1p/d3tJ5HFnpV51pfGZ8bYGWXPpj5/teJ+vUD/Te9qE8k8WjMvRSeNXPJN1tj83ipL3GJh0sZ8DA1X+XohXcG3p+s5wb4k7v9L1zrHIB/4YfyJx/p8U1gWnS6piYvZU2ar0FyzePjMS8GzXzS8CcPpiqc17Lfx52jv3N9PnHOhLNGFh5zV/OJOzeAd2vDcf0tzv99E8hJ8SmZtLt75dzeyzpd7/OUsdLrPnhrfZ2awyWPXjNJS73/D211bTg/w7kBvBsbjs8B2PBD9ys//hwgXQ+AvurQEp+4zpsh465GH9lVnWG9j+lNPF7Ks9czr+ZpLXru5r4jyxnnBuCT2LQ+fRP4lX3gNPuEkYfHvOrE3fWc29eastw3jd+RkfJTLlx/D3QqvVccfKrnBki7spH2YzpFeGk/Vk8dHFnMK5MxXmke17y+Eve3M3t3s8hcXdP8NOY5eI9ik3Y3A37KwoOt6uc4N4B3ZsPxOQAbfuh+5R9cB1wVzA15jA+fPBh7jKc+mKrmPDZjzmvCW6MPj3lVuOTBwdQ8cfbp6TX1mel+yjSTfPLgEoNX7LkB2LFN69MfBPl0cHqSxn4lzxoclcyaJ84+PXDJg3l3ndbES2umZ4SfvLtZ5lMuPmvXPHHnBmCnNq3nAGz6wfPaj78QwlWRrglg18SjmetjM6xlzeOpF48M5q5kmUEz97tj504ZieM5Jg+mshM3rZk85+GfG4Cd2LQ+/SwgnTSfnO7bYw87g1418fbpTdyK5yzGzpoy4FOlrzzyrrSeQ1/XX82v8nueeTI7U7q5cwOwU5vWcwA2/eB57ccPgxCuarpSph54XzvwaDCle/yKo8+8+/DRmJNXNWn26bXWxyuMe+6u6Xx6rTn71Zi+8lPvuQFe7dwm+uObQN43nZJ0iqxNvXhU912tRQ+ceycPHuaqruZe5ZTP2s5ES/0TZ4/eKQvmVU155wZ4tVub6E/fA/iUcNqotSf41tgrPOauE5889zKeuOTxPPaS9tX86meNlI/GOubpKw0Ojeo+GGdYg029yTs3ALuyaT0HYNMPntcevwmcrhY8XzVohFe1X3Mz3SufL3NoiYezh7bSB1N1ynjFobOmM/CoMDWHswY3VfrMfCXj3ADeyQ3HTzeA94DT5hOGBjd5xdinh0qWGTSYq+rezpI1MdUD1/s9Txmpb+Imr9bCJ5d5eatasXe+zg1wZ7c+kD0H4AM/1Duv9Pg7gTT52kHj+mFeFc7eigbjrDROuYlDS7w1uGn9xNPnmjLoxWNefWjOsG/d46s+fLKYOyON4cs7N0DaoY208QbwSWFPOGWTB3tVySCzeLTUC2cGzbx96zVOPEzqg588+l9VeskqLmmv+q3TZ8256HD2knZuAHZs0/r4bWA6HUlb2Sf6ivUJrLm9mq983c3o/Moad5jpHd65tte5m+ve/m7OOjdA353N5ucAbPaB99d9/LuAbtTcVwX+ytVyt4/sqzXJpbpveq5V7m6u+b6+53BXmp/zai86+4pnbfN+jnMDeGc2HD/9hRCfDvYjnSK0xNNXFR/eHmMY83ipmk/+iubnWclLfOpDS/yVxnOTQS3dvXBo5rrH3JW+0s4N4J3ZcHwOwIYful/58f8QgpiuEzzXiZu8lOErKfXiJ488GOZV4SfPvMe9l7mZlGt/Gq/0XjHpmfqaiXHuuQH6jm02f/qTQL+/T4r1O+N+AlOmGXxrfT2Y0hOHj8e853x1Tn7lsAYa86s14M3Raw/N3MrYGYk/N0DalY20cwA2+rDTqz79OcDqVTNdLXczzKdcfDxqeqHSJh+PzNWMK77nsE7pqdf+1Lvike9MNPcn7dwA3qENx4+fBaTTwX74ZKElPnHwqZKR+vCqDx+NuTPxrng4Z0waaySevqs13UseNWXguZJhHn/yYKom7twA3qENx+NvA9kPnzpOEV6qd3ln0Ot10MytjJ3ReWfCWYNf8WCrThmJIz951tI49Saua37GcwP03dlsfg7AZh94f92nXwJ8PaQrxn6FmcGbNBj3Jq0/6L9xPj138qytvI/3Ed4ZyYejmkdzPTeAd2PD8eMPgjgpV6eq+/TV3nUv7ecKk/q+ovkZyfFzJB+OesWTAUet/u6R+a465fs5WA++5ucGYFc2recAbPrB89pP/zQMw9VXhvUa+4pJHP7kOROOvvLQzDGGmxjYV5WM5JObGLzqS37K69qUMXk9p8/d2z0/67kB+u5sNn/6K2F+f58U6zXG80lLGn14zO/U3us1GZtBu7NGZ+9mTDzPNjFeH46+5FlLnP1pfG6AaXc28B4/DVw9RXCcUu/RikZ/9cFbc14f3+VTbspA6+vVPGXAJW/Kos+5qzy9XnPqNUcvPLX0cwOwO5vWcwA2/eB57aXfBgJX5fqYrpjJc1bi8Fmn5nDW4KgwNZ84+KtKHlnMX/VN3IrnXNair7xJcy9j96KRwbzquQG8GxuOH98E8u4+OZwYa52DKZ1x4ifPPBzrXNXEd835V3nd71nlpzw4PObFM8azhlda/7JHb9Los4eWKlnlnRsg7dBG2jkAG33Y6VWf/kJIgu5qvmK4ltCYVybaVb57rlj75Kd+vOLxJw2meDhrpdcX3t+zf/73FY9PBvN/pvyawaHc5avv3ADs3qb16ZtA70M/YeVxyvCYuy+NE4dGVupb1ZxBLtUZ5qxfjVf7WPOKx4ev9bvG/NWz0QtHNQ9Tmscw5wZgJzat5wBs+sHz2k+/BKRrBLgqfrpO7norfFrb2spzsM5Vn/3e43XwqO4zh45mPmnwVJiauxe/a+YTg+++cwOwU5vWx28DeX9OSc19UvCn6l64npEY2Kvas4onb/JSbuLNreSmDPqclcb0rvIrGWQWm3Ltk3duAHZi0/r07wJ8StIpmvbJvXBTxiqfOPKp0zowr+pKr5n0PPZrHTPdKz9ppfvLGejuYwzHHHalnhtgZZc+mDkH4IM/3JVXG/9CyHS1JG/S0sNwZdGXmKTRVx69SUu9dzXn9l7Wtg5vL2nuYdw55uU7D36q9LovaecGmHZxA2+8Ae6+P6eNk+b+Fc/8O8Y8B2tXJlrKv8s5y70pu6+deOf1DPgVxr3mybB/bgDvxobjcwA2/ND9yuM/DTP4auwrhrGvGjTqq5yumyfPGjwe89XqvikXzzxrWINLHppr5+0xdj6aK/5KlvvMnxvAO7Ph+PHTQJ+KlX3g9CXWWROXepPmvPJTZmdSjrWv8KzvDDTWSF5nYF/VlGHWvnWPvSa8tXMDeLc2HD9+GphOR9qPzjE36xOGDpc8mKtKRuJSLnzyUsakkVVMysOfvJRvfspIvSsamcWylrVzA6zs4gcz5wB88Ie78mqPbwJX4CsmXTH04DF39ZWEbr77k1f98ObITR4aTFV6k2eujxO/mjVxk8czwNSc57jSzg3A7m1a33oDcOrSXuJdnUh64Zlf1VXe60+Z5K3ycKt98NMzmCF34q8858GeG4Cd2LSeA7DpB89rP/0S8I6rhnBXrp+Ubw2OWhn41pzdx6tc7/O8Z/AMZjye+O5VH3n2kuY17ozJJbN6k3ZugDu7+oHs4wbgdLz7HadcPJ/Saf3EpYzOwVQ2nrVpzVWPXHjn4yUNj76qaObtM+4+feV3j55ezw3Qd2Sz+TkAm33g/XX/CxmZ1AlrPo1+AAAAAElFTkSuQmCC');

-- --------------------------------------------------------

--
-- Table structure for table `payment_requests`
--

DROP TABLE IF EXISTS `payment_requests`;
CREATE TABLE IF NOT EXISTS `payment_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Requestd_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Request_date` date NOT NULL,
  `Reason` text COLLATE utf8mb4_unicode_ci,
  `Status` enum('Pending','SendtoFinance','Approved','Rejact','Reason') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_by` int UNSIGNED DEFAULT NULL,
  `modified_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_requests_requestd_id_unique` (`Requestd_id`),
  KEY `payment_requests_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_request_children`
--

DROP TABLE IF EXISTS `payment_request_children`;
CREATE TABLE IF NOT EXISTS `payment_request_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Requested_Id` bigint UNSIGNED DEFAULT NULL,
  `Employee_id` int UNSIGNED NOT NULL,
  `WorkPermitDate` date DEFAULT NULL,
  `LastWorkPermitDate` date DEFAULT NULL,
  `WorkPermitAmt` decimal(10,2) DEFAULT NULL,
  `WorkPermitShow` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `WorkPermitStep` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `QuotaslotDate` date DEFAULT NULL,
  `LastQuotaslotDate` date DEFAULT NULL,
  `QuotaslotAmt` decimal(10,2) DEFAULT NULL,
  `QuotaslotShow` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `QuotaslotStep` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `InsuranceDate` date DEFAULT NULL,
  `LastInsuranceDate` date DEFAULT NULL,
  `InsurancePrimume` decimal(10,2) DEFAULT NULL,
  `InsuranceShow` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `InsuranceStep` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `MedicalReportDate` date DEFAULT NULL,
  `LastMedicalReportDate` date DEFAULT NULL,
  `MedicalReportFees` decimal(10,2) DEFAULT NULL,
  `MedicalReportShow` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `MedicalReportStep` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `VisaDate` date DEFAULT NULL,
  `LastVisaDate` date DEFAULT NULL,
  `VisaAmt` decimal(10,2) DEFAULT NULL,
  `VisaShow` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `VisaStep` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `ChildStatus` enum('Pending','Complete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `OngoingSteps` int NOT NULL DEFAULT '0',
  `OverallSteps` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_request_children_employee_id_foreign` (`Employee_id`),
  KEY `payment_request_children_requested_id_foreign` (`Requested_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_payroll` decimal(10,2) NOT NULL,
  `total_employees` int NOT NULL,
  `draft_date` date NOT NULL,
  `payment_date` date NOT NULL,
  `city_ledger_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payroll_unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT '$',
  `status` enum('draft','locked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `resort_id`, `start_date`, `end_date`, `total_payroll`, `total_employees`, `draft_date`, `payment_date`, `city_ledger_file`, `payroll_unit`, `status`, `created_at`, `updated_at`) VALUES
(13, 26, '2025-11-15', '2025-12-14', 0.00, 0, '0000-00-00', '0000-00-00', NULL, '$', 'draft', '2025-12-12 23:39:00', '2025-12-12 23:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_advance`
--

DROP TABLE IF EXISTS `payroll_advance`;
CREATE TABLE IF NOT EXISTS `payroll_advance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `request_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_amount` decimal(12,2) NOT NULL,
  `request_date` date NOT NULL,
  `pourpose` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Approved','Rejected','In-Progress') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `recovery_status` enum('Pending','In Progress','Scheduled','Completed','Failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending' COMMENT 'Status of the recovery process',
  `priority` enum('High','Low','Medium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Low',
  `hr_status` enum('Pending','Approved','Rejected','Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `hr_action_date` date DEFAULT NULL,
  `finance_status` enum('Pending','Approved','Rejected','Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `finance_action_date` date DEFAULT NULL,
  `gm_status` enum('Pending','Approved','Rejected','Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `gm_action_date` date DEFAULT NULL,
  `remarks` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hr_approved_by` int UNSIGNED DEFAULT NULL,
  `finance_approved_by` int UNSIGNED DEFAULT NULL,
  `gm_approved_by` int UNSIGNED DEFAULT NULL,
  `reject_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_date` date DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_advance_employee_id_foreign` (`employee_id`),
  KEY `payroll_advance_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_advance_attachments`
--

DROP TABLE IF EXISTS `payroll_advance_attachments`;
CREATE TABLE IF NOT EXISTS `payroll_advance_attachments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `payroll_advance_id` bigint UNSIGNED NOT NULL,
  `attachments` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `modified_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_advance_attachments_resort_id_foreign` (`resort_id`),
  KEY `payroll_advance_attachments_payroll_advance_id_foreign` (`payroll_advance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_advance_guarantor`
--

DROP TABLE IF EXISTS `payroll_advance_guarantor`;
CREATE TABLE IF NOT EXISTS `payroll_advance_guarantor` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_advance_id` bigint UNSIGNED NOT NULL,
  `guarantor_id` int UNSIGNED NOT NULL COMMENT 'employee_id',
  `status` enum('Pending','Approved','Rejected','Hold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_by` bigint DEFAULT NULL,
  `modified_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_advance_guarantor_payroll_advance_id_foreign` (`payroll_advance_id`),
  KEY `payroll_advance_guarantor_guarantor_id_foreign` (`guarantor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_attendance_activity_log`
--

DROP TABLE IF EXISTS `payroll_attendance_activity_log`;
CREATE TABLE IF NOT EXISTS `payroll_attendance_activity_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED DEFAULT NULL,
  `payroll_id` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED DEFAULT NULL COMMENT 'User who made the change',
  `employee_id` int UNSIGNED NOT NULL COMMENT 'Employee whose data was changed',
  `field` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Field that was updated',
  `old_value` text COLLATE utf8mb4_unicode_ci COMMENT 'Previous value',
  `new_value` text COLLATE utf8mb4_unicode_ci COMMENT 'Updated value',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_attendance_activity_log_user_id_foreign` (`user_id`),
  KEY `payroll_attendance_activity_log_employee_id_foreign` (`employee_id`),
  KEY `payroll_attendance_activity_log_resort_id_foreign` (`resort_id`),
  KEY `payroll_attendance_activity_log_payroll_id_foreign` (`payroll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_config`
--

DROP TABLE IF EXISTS `payroll_config`;
CREATE TABLE IF NOT EXISTS `payroll_config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `cutoff_day` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_config_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_config`
--

INSERT INTO `payroll_config` (`id`, `resort_id`, `cutoff_day`, `created_at`, `updated_at`) VALUES
(3, 26, 28, '2025-12-13 15:20:02', '2025-12-13 15:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

DROP TABLE IF EXISTS `payroll_deductions`;
CREATE TABLE IF NOT EXISTS `payroll_deductions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Emp_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attendance_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `city_ledger` decimal(10,2) NOT NULL DEFAULT '0.00',
  `staff_shop` decimal(10,2) NOT NULL DEFAULT '0.00',
  `advance_loan` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Advance loan/salary amount deducted from the employee''s payroll',
  `pension` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ewt` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_deductions_employee_id_foreign` (`employee_id`),
  KEY `payroll_deductions_payroll_id_foreign` (`payroll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_employees`
--

DROP TABLE IF EXISTS `payroll_employees`;
CREATE TABLE IF NOT EXISTS `payroll_employees` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Emp_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int UNSIGNED NOT NULL,
  `department` int UNSIGNED NOT NULL,
  `section` int UNSIGNED DEFAULT NULL,
  `paymentMethod` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_employees_payroll_id_foreign` (`payroll_id`),
  KEY `payroll_employees_employee_id_foreign` (`employee_id`),
  KEY `payroll_employees_position_foreign` (`position`),
  KEY `payroll_employees_department_foreign` (`department`),
  KEY `payroll_employees_section_foreign` (`section`)
) ENGINE=InnoDB AUTO_INCREMENT=375 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_employees`
--

INSERT INTO `payroll_employees` (`id`, `payroll_id`, `employee_id`, `Emp_id`, `position`, `department`, `section`, `paymentMethod`, `created_at`, `updated_at`) VALUES
(360, 13, 170, 'DR-1', 145, 79, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(361, 13, 171, 'DR-2', 143, 79, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(362, 13, 173, 'DR-4', 152, 80, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(363, 13, 174, 'DR-5', 150, 80, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(364, 13, 176, 'DR-7', 148, 80, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(365, 13, 177, 'DR-8', 149, 80, NULL, 'Cash', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(366, 13, 179, 'DR-10', 151, 78, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(367, 13, 180, 'DR-11', 152, 80, NULL, 'Cash', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(368, 13, 182, 'DR-13', 144, 79, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(369, 13, 183, 'DR-14', 152, 80, NULL, 'Cash', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(370, 13, 184, 'DR-15', 146, 78, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(371, 13, 186, 'DR-17', 152, 80, NULL, 'Cash', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(372, 13, 187, 'DR-18', 142, 81, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(373, 13, 188, 'DR-19', 147, 78, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13'),
(374, 13, 189, 'DR-20', 152, 80, NULL, 'Bank', '2025-12-12 23:39:13', '2025-12-12 23:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_recovery_schedule`
--

DROP TABLE IF EXISTS `payroll_recovery_schedule`;
CREATE TABLE IF NOT EXISTS `payroll_recovery_schedule` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_advance_id` bigint UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `repayment_date` date NOT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci COMMENT 'Remark for the recovery schedule',
  `amount` decimal(12,2) NOT NULL,
  `interest` decimal(12,2) DEFAULT NULL,
  `interest_amount` decimal(12,2) DEFAULT NULL,
  `remaining_balance` decimal(12,2) DEFAULT NULL,
  `status` enum('Pending','Paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_by` bigint DEFAULT NULL,
  `modified_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_recovery_schedule_payroll_advance_id_foreign` (`payroll_advance_id`),
  KEY `payroll_recovery_schedule_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_reviews`
--

DROP TABLE IF EXISTS `payroll_reviews`;
CREATE TABLE IF NOT EXISTS `payroll_reviews` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Emp_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_charge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `regularOTPay` decimal(10,2) NOT NULL DEFAULT '0.00',
  `holidayOTPay` decimal(10,2) NOT NULL DEFAULT '0.00',
  `earnings_basic` decimal(10,2) NOT NULL DEFAULT '0.00',
  `earned_salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `earnings_overtime` decimal(10,2) NOT NULL DEFAULT '0.00',
  `earnings_allowance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_reviews_payroll_id_foreign` (`payroll_id`),
  KEY `payroll_reviews_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_review_allowances`
--

DROP TABLE IF EXISTS `payroll_review_allowances`;
CREATE TABLE IF NOT EXISTS `payroll_review_allowances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_review_id` int UNSIGNED NOT NULL,
  `allowance_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_unit` enum('MVR','USD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_review_allowances_payroll_review_id_foreign` (`payroll_review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_service_charges`
--

DROP TABLE IF EXISTS `payroll_service_charges`;
CREATE TABLE IF NOT EXISTS `payroll_service_charges` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Emp_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_working_days` int DEFAULT NULL,
  `service_charge_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_service_charges_employee_id_foreign` (`employee_id`),
  KEY `payroll_service_charges_payroll_id_foreign` (`payroll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_time_and_attandance`
--

DROP TABLE IF EXISTS `payroll_time_and_attandance`;
CREATE TABLE IF NOT EXISTS `payroll_time_and_attandance` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Emp_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `present_days` int NOT NULL,
  `absent_days` int NOT NULL,
  `leave_types` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regular_ot_hours` double(8,2) DEFAULT NULL,
  `holiday_ot_hours` double(8,2) DEFAULT NULL,
  `total_ot` double(8,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_time_and_attandance_employee_id_foreign` (`employee_id`),
  KEY `payroll_time_and_attandance_payroll_id_foreign` (`payroll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peformance_meetings`
--

DROP TABLE IF EXISTS `peformance_meetings`;
CREATE TABLE IF NOT EXISTS `peformance_meetings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conference_links` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `resort_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `peformance_meetings_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_salary_increment`
--

DROP TABLE IF EXISTS `people_salary_increment`;
CREATE TABLE IF NOT EXISTS `people_salary_increment` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `increment_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_salary` decimal(12,2) NOT NULL,
  `new_salary` decimal(12,2) NOT NULL,
  `increment_amount` decimal(12,2) NOT NULL,
  `pay_increase_type` enum('Percentage','Fixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Percentage',
  `value` decimal(5,2) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected','Hold','Change-Request') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `remarks` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL COMMENT 'date which is hold this increment',
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `people_salary_increment_employee_id_foreign` (`employee_id`),
  KEY `people_salary_increment_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `people_salary_increment_status`
--

DROP TABLE IF EXISTS `people_salary_increment_status`;
CREATE TABLE IF NOT EXISTS `people_salary_increment_status` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `people_salary_increment_id` bigint UNSIGNED NOT NULL,
  `approval_rank` enum('Finance','GM') COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved_by` int UNSIGNED DEFAULT NULL,
  `status` enum('Pending','Hold','Approved','Rejected','Change-Request') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `remarks` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reject_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_date` date DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `modified_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_salary_increment_id` (`people_salary_increment_id`),
  KEY `fk_approved_by` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_cycles`
--

DROP TABLE IF EXISTS `performance_cycles`;
CREATE TABLE IF NOT EXISTS `performance_cycles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Cycle_Name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Start_Date` date NOT NULL,
  `End_Date` date NOT NULL,
  `CycleSummary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Self_Review_Templete` int DEFAULT NULL,
  `Manager_Review_Templete` int DEFAULT NULL,
  `Self_Activity_Start_Date` date DEFAULT NULL,
  `Self_Activity_End_Date` date DEFAULT NULL,
  `Manager_Activity_Start_Date` date DEFAULT NULL,
  `Manager_Activity_End_Date` date DEFAULT NULL,
  `CycleReminders` enum('ON','OFF') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OFF',
  `status` enum('Pending','OnGoing','Close') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_cycles_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_kpi_children`
--

DROP TABLE IF EXISTS `performance_kpi_children`;
CREATE TABLE IF NOT EXISTS `performance_kpi_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `kpi_parents_id` bigint UNSIGNED NOT NULL,
  `budget` double(8,2) DEFAULT NULL,
  `weightage` double(8,2) DEFAULT NULL,
  `score` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_kpi_children_kpi_parents_id_foreign` (`kpi_parents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_kpi_parents`
--

DROP TABLE IF EXISTS `performance_kpi_parents`;
CREATE TABLE IF NOT EXISTS `performance_kpi_parents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `property_goal` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PropertyGoalbudget` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PropertyGoalweightage` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PropertyGoalscore` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_kpi_parents_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_meeting_contents`
--

DROP TABLE IF EXISTS `performance_meeting_contents`;
CREATE TABLE IF NOT EXISTS `performance_meeting_contents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_meeting_contents_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_review_types`
--

DROP TABLE IF EXISTS `performance_review_types`;
CREATE TABLE IF NOT EXISTS `performance_review_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `category_title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_weightage` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_review_types_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_template_forms`
--

DROP TABLE IF EXISTS `performance_template_forms`;
CREATE TABLE IF NOT EXISTS `performance_template_forms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `FormName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Department_id` int DEFAULT NULL,
  `Division_id` int DEFAULT NULL,
  `Section_id` int DEFAULT NULL,
  `Position_id` int DEFAULT NULL,
  `form_structure` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_template_forms_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performa_child_cycles`
--

DROP TABLE IF EXISTS `performa_child_cycles`;
CREATE TABLE IF NOT EXISTS `performa_child_cycles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_cycle_id` bigint UNSIGNED NOT NULL,
  `Emp_main_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Self_review_date` date DEFAULT NULL,
  `Manager_review_date` date DEFAULT NULL,
  `Manager_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performa_child_cycles_parent_cycle_id_foreign` (`Parent_cycle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` smallint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `order`, `created_at`, `updated_at`) VALUES
(1, 'View', 1, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(2, 'Create', 2, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(3, 'Edit', 3, '2025-12-23 14:08:26', '2025-12-23 14:08:26'),
(4, 'Delete', 4, '2025-12-23 14:08:26', '2025-12-23 14:08:26');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
CREATE TABLE IF NOT EXISTS `positions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_id` int UNSIGNED NOT NULL,
  `section_id` int NOT NULL DEFAULT '0',
  `position_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_reserved` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Indicates if the position is reserved for a Local or Expat',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `positions_dept_id_foreign` (`dept_id`)
) ENGINE=InnoDB AUTO_INCREMENT=411 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `dept_id`, `section_id`, `position_title`, `created_at`, `updated_at`, `code`, `short_title`, `status`, `is_reserved`, `created_by`, `modified_by`) VALUES
(246, 46, 0, 'Rooms Director', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ROOMS_DIRECTOR', 'Rooms Dire', 'active', 'No', 1, 1),
(247, 46, 0, 'Operations Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'OPERATIONS_MANAGER', 'Operations', 'active', 'No', 1, 1),
(248, 46, 0, 'Front Office Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FRONT_OFFICE_MANAGER', 'Front Offi', 'active', 'No', 1, 1),
(249, 46, 0, 'Front Desk Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FRONT_DESK_MANAGER', 'Front Desk', 'active', 'No', 1, 1),
(250, 46, 0, 'Assistant Front Desk Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ASSISTANT_FRONT_DESK_MANAGER', 'Assistant ', 'active', 'No', 1, 1),
(251, 46, 0, 'Night Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'NIGHT_MANAGER', 'Night Mana', 'active', 'No', 1, 1),
(252, 46, 0, 'Director Of Guest Services', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_GUEST_SERVICES', 'Director O', 'active', 'No', 1, 1),
(253, 46, 0, 'Guest Services Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GUEST_SERVICES_MANAGER', 'Guest Serv', 'active', 'No', 1, 1),
(254, 46, 0, 'Chef Concierge', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CHEF_CONCIERGE', 'Chef Conci', 'active', 'No', 1, 1),
(255, 46, 0, 'Executive Housekeeper', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'EXECUTIVE_HOUSEKEEPER', 'Executive ', 'active', 'No', 1, 1),
(256, 46, 0, 'Director Of Housekeeping', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_HOUSEKEEPING', 'Director O', 'active', 'No', 1, 1),
(257, 46, 0, 'Housekeeping Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOUSEKEEPING_MANAGER', 'Housekeepi', 'active', 'No', 1, 1),
(258, 46, 0, 'Director Of Reservations', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_RESERVATIONS', 'Director O', 'active', 'No', 1, 1),
(259, 46, 0, 'Reservations Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RESERVATIONS_MANAGER', 'Reservatio', 'active', 'No', 1, 1),
(260, 46, 0, 'Transportation Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'TRANSPORTATION_MANAGER', 'Transporta', 'active', 'No', 1, 1),
(261, 46, 0, 'Club Floor Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CLUB_FLOOR_MANAGER', 'Club Floor', 'active', 'No', 1, 1),
(262, 47, 0, 'Desk Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DESK_CLERK', 'Desk Clerk', 'active', 'No', 1, 1),
(263, 47, 0, 'Night Desk Clerk (Former Night Auditor)', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'NIGHT_DESK_CLERK__FORMER_NIGHT_AUDITOR_', 'Night Desk', 'active', 'No', 1, 1),
(264, 47, 0, 'Bell Captain', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BELL_CAPTAIN', 'Bell Capta', 'active', 'No', 1, 1),
(265, 47, 0, 'Bell/Luggage Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BELL_LUGGAGE_ATTENDANT', 'Bell/Lugga', 'active', 'No', 1, 1),
(266, 47, 0, 'Door Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DOOR_ATTENDANT', 'Door Atten', 'active', 'No', 1, 1),
(267, 47, 0, 'Dispatcher', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DISPATCHER', 'Dispatcher', 'active', 'No', 1, 1),
(268, 47, 0, 'Concierge', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CONCIERGE', 'Concierge', 'active', 'No', 1, 1),
(269, 48, 0, 'Guest Services Representative', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GUEST_SERVICES_REPRESENTATIVE', 'Guest Serv', 'active', 'No', 1, 1),
(270, 48, 0, 'Activities Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACTIVITIES_ATTENDANT', 'Activities', 'active', 'No', 1, 1),
(271, 48, 0, 'Guest Services Coordinator', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GUEST_SERVICES_COORDINATOR', 'Guest Serv', 'active', 'No', 1, 1),
(272, 49, 0, 'Floor Supervisor', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FLOOR_SUPERVISOR', 'Floor Supe', 'active', 'No', 1, 1),
(273, 49, 0, 'Room Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ROOM_ATTENDANT', 'Room Atten', 'active', 'No', 1, 1),
(274, 49, 0, 'House Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOUSE_ATTENDANT', 'House Atte', 'active', 'No', 1, 1),
(275, 49, 0, 'Public Area Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PUBLIC_AREA_ATTENDANT', 'Public Are', 'active', 'No', 1, 1),
(276, 49, 0, 'Turn-Down Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'TURN-DOWN_ATTENDANT', 'Turn-Down ', 'active', 'No', 1, 1),
(277, 49, 0, 'Night Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'NIGHT_ATTENDANT', 'Night Atte', 'active', 'No', 1, 1),
(278, 49, 0, 'Sewing Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SEWING_ATTENDANT', 'Sewing Att', 'active', 'No', 1, 1),
(279, 49, 0, 'Uniform Room Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'UNIFORM_ROOM_ATTENDANT', 'Uniform Ro', 'active', 'No', 1, 1),
(280, 49, 0, 'Housekeeping/Linen Runner', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOUSEKEEPING_LINEN_RUNNER', 'Housekeepi', 'active', 'No', 1, 1),
(281, 50, 0, 'Linen Control Supervisor', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'LINEN_CONTROL_SUPERVISOR', 'Linen Cont', 'active', 'No', 1, 1),
(282, 50, 0, 'Linen Room Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'LINEN_ROOM_ATTENDANT', 'Linen Room', 'active', 'No', 1, 1),
(283, 50, 0, 'Uniform Room Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'UNIFORM_ROOM_ATTENDANT', 'Uniform Ro', 'active', 'No', 1, 1),
(284, 51, 0, 'Reservations Agent', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RESERVATIONS_AGENT', 'Reservatio', 'active', 'No', 1, 1),
(285, 51, 0, 'Guest Historian', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GUEST_HISTORIAN', 'Guest Hist', 'active', 'No', 1, 1),
(286, 52, 0, 'Driver', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DRIVER', 'Driver', 'active', 'No', 1, 1),
(287, 53, 0, 'Club Floor Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CLUB_FLOOR_ATTENDANT', 'Club Floor', 'active', 'No', 1, 1),
(288, 53, 0, 'Breakfast Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BREAKFAST_ATTENDANT', 'Breakfast ', 'active', 'No', 1, 1),
(289, 54, 0, 'Director Of Food And Beverage', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_FOOD_AND_BEVERAGE', 'Director O', 'active', 'No', 1, 1),
(290, 54, 0, 'Food And Beverage Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FOOD_AND_BEVERAGE_MANAGER', 'Food And B', 'active', 'No', 1, 1),
(291, 54, 0, 'Director Of Venues', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_VENUES', 'Director O', 'active', 'No', 1, 1),
(292, 54, 0, 'Restaurant Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RESTAURANT_MANAGER', 'Restaurant', 'active', 'No', 1, 1),
(293, 54, 0, 'Beverage Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BEVERAGE_MANAGER', 'Beverage M', 'active', 'No', 1, 1),
(294, 54, 0, 'Director Of Convention Services', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_CONVENTION_SERVICES', 'Director O', 'active', 'No', 1, 1),
(295, 54, 0, 'Convention Services Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CONVENTION_SERVICES_MANAGER', 'Convention', 'active', 'No', 1, 1),
(296, 55, 0, 'Executive Chef', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'EXECUTIVE_CHEF', 'Executive ', 'active', 'No', 1, 1),
(297, 55, 0, 'Executive Sous Chef', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'EXECUTIVE_SOUS_CHEF', 'Executive ', 'active', 'No', 1, 1),
(298, 55, 0, 'Sous Chef', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SOUS_CHEF', 'Sous Chef', 'active', 'No', 1, 1),
(299, 55, 0, 'Chef De Cuisine', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CHEF_DE_CUISINE', 'Chef De Cu', 'active', 'No', 1, 1),
(300, 55, 0, 'Pastry Chef', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PASTRY_CHEF', 'Pastry Che', 'active', 'No', 1, 1),
(301, 55, 0, 'Kitchen Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'KITCHEN_MANAGER', 'Kitchen Ma', 'active', 'No', 1, 1),
(302, 55, 0, 'Executive Steward', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'EXECUTIVE_STEWARD', 'Executive ', 'active', 'No', 1, 1),
(303, 55, 0, 'Stewarding Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'STEWARDING_MANAGER', 'Stewarding', 'active', 'No', 1, 1),
(304, 56, 0, 'Captain', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CAPTAIN', 'Captain', 'active', 'No', 1, 1),
(305, 56, 0, 'Bartender', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BARTENDER', 'Bartender', 'active', 'No', 1, 1),
(306, 56, 0, 'Server', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SERVER', 'Server', 'active', 'No', 1, 1),
(307, 56, 0, 'Busperson', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BUSPERSON', 'Busperson', 'active', 'No', 1, 1),
(308, 56, 0, 'Porter', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PORTER', 'Porter', 'active', 'No', 1, 1),
(309, 56, 0, 'Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ATTENDANT', 'Attendant', 'active', 'No', 1, 1),
(310, 56, 0, 'Runner', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RUNNER', 'Runner', 'active', 'No', 1, 1),
(311, 56, 0, 'Houseperson', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOUSEPERSON', 'Houseperso', 'active', 'No', 1, 1),
(312, 57, 0, 'Chef', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CHEF', 'Chef', 'active', 'No', 1, 1),
(313, 57, 0, 'Garde Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GARDE_MANAGER', 'Garde Mana', 'active', 'No', 1, 1),
(314, 57, 0, 'Chef De Partie', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CHEF_DE_PARTIE', 'Chef De Pa', 'active', 'No', 1, 1),
(315, 57, 0, 'Cook', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'COOK', 'Cook', 'active', 'No', 1, 1),
(316, 57, 0, 'Pastry Cook', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PASTRY_COOK', 'Pastry Coo', 'active', 'No', 1, 1),
(317, 57, 0, 'Butcher', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BUTCHER', 'Butcher', 'active', 'No', 1, 1),
(318, 57, 0, 'Baker', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BAKER', 'Baker', 'active', 'No', 1, 1),
(319, 57, 0, 'Steward', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'STEWARD', 'Steward', 'active', 'No', 1, 1),
(320, 57, 0, 'Cleaner', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CLEANER', 'Cleaner', 'active', 'No', 1, 1),
(321, 58, 0, 'Sommelier', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SOMMELIER', 'Sommelier', 'active', 'No', 1, 1),
(322, 58, 0, 'Maître D’', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MAîTRE_D_', 'Maître D', 'active', 'No', 1, 1),
(323, 58, 0, 'Host(ess)', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOST_ESS_', 'Host(ess)', 'active', 'No', 1, 1),
(324, 58, 0, 'Captain', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CAPTAIN', 'Captain', 'active', 'No', 1, 1),
(325, 58, 0, 'Bartender', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BARTENDER', 'Bartender', 'active', 'No', 1, 1),
(326, 58, 0, 'Server', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SERVER', 'Server', 'active', 'No', 1, 1),
(327, 58, 0, 'Busperson', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BUSPERSON', 'Busperson', 'active', 'No', 1, 1),
(328, 58, 0, 'Porter', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PORTER', 'Porter', 'active', 'No', 1, 1),
(329, 58, 0, 'Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ATTENDANT', 'Attendant', 'active', 'No', 1, 1),
(330, 58, 0, 'Runner', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RUNNER', 'Runner', 'active', 'No', 1, 1),
(331, 58, 0, 'Cashier', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CASHIER', 'Cashier', 'active', 'No', 1, 1),
(332, 58, 0, 'Houseperson', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOUSEPERSON', 'Houseperso', 'active', 'No', 1, 1),
(333, 59, 0, 'Director Of Golf Course Maintenance', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_GOLF_COURSE_MAINTENANCE', 'Director O', 'active', 'No', 1, 1),
(334, 59, 0, 'Director Of Golf', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_GOLF', 'Director O', 'active', 'No', 1, 1),
(335, 59, 0, 'Golf Pro', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_PRO', 'Golf Pro', 'active', 'No', 1, 1),
(336, 59, 0, 'Golf Pro Shop Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_PRO_SHOP_MANAGER', 'Golf Pro S', 'active', 'No', 1, 1),
(337, 59, 0, 'Retail Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RETAIL_MANAGER', 'Retail Man', 'active', 'No', 1, 1),
(338, 59, 0, 'Golf Course Maintenance Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_COURSE_MAINTENANCE_MANAGER', 'Golf Cours', 'active', 'No', 1, 1),
(339, 60, 0, 'Golf Instructor', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_INSTRUCTOR', 'Golf Instr', 'active', 'No', 1, 1),
(340, 60, 0, 'Greens Keeper', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GREENS_KEEPER', 'Greens Kee', 'active', 'No', 1, 1),
(341, 60, 0, 'Golf Course Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_COURSE_ATTENDANT', 'Golf Cours', 'active', 'No', 1, 1),
(342, 60, 0, 'Caddy', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CADDY', 'Caddy', 'active', 'No', 1, 1),
(343, 60, 0, 'Golf Ranger', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_RANGER', 'Golf Range', 'active', 'No', 1, 1),
(344, 60, 0, 'Golf Pro Assistant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_PRO_ASSISTANT', 'Golf Pro A', 'active', 'No', 1, 1),
(345, 60, 0, 'Instructor', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'INSTRUCTOR', 'Instructor', 'active', 'No', 1, 1),
(346, 60, 0, 'Starter', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'STARTER', 'Starter', 'active', 'No', 1, 1),
(347, 61, 0, 'Greens Supervisor', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GREENS_SUPERVISOR', 'Greens Sup', 'active', 'No', 1, 1),
(348, 61, 0, 'Greens Keeper', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GREENS_KEEPER', 'Greens Kee', 'active', 'No', 1, 1),
(349, 61, 0, 'Gardener', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GARDENER', 'Gardener', 'active', 'No', 1, 1),
(350, 61, 0, 'General Maintenance', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GENERAL_MAINTENANCE', 'General Ma', 'active', 'No', 1, 1),
(351, 61, 0, 'Driver', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DRIVER', 'Driver', 'active', 'No', 1, 1),
(352, 61, 0, 'Mechanic', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MECHANIC', 'Mechanic', 'active', 'No', 1, 1),
(353, 61, 0, 'Golf Cart Maintenance', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_CART_MAINTENANCE', 'Golf Cart ', 'active', 'No', 1, 1),
(354, 61, 0, 'Repair Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'REPAIR_ATTENDANT', 'Repair Att', 'active', 'No', 1, 1),
(355, 61, 0, 'Golf Cart Storage Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_CART_STORAGE_ATTENDANT', 'Golf Cart ', 'active', 'No', 1, 1),
(356, 62, 0, 'Golf Cashier', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_CASHIER', 'Golf Cashi', 'active', 'No', 1, 1),
(357, 62, 0, 'Locker Room Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'LOCKER_ROOM_ATTENDANT', 'Locker Roo', 'active', 'No', 1, 1),
(358, 62, 0, 'Club Storage Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CLUB_STORAGE_ATTENDANT', 'Club Stora', 'active', 'No', 1, 1),
(359, 62, 0, 'Golf Pro Shop Cashier', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_PRO_SHOP_CASHIER', 'Golf Pro S', 'active', 'No', 1, 1),
(360, 62, 0, 'Golf Pro Shop Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GOLF_PRO_SHOP_ATTENDANT', 'Golf Pro S', 'active', 'No', 1, 1),
(361, 62, 0, 'Sales Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SALES_CLERK', 'Sales Cler', 'active', 'No', 1, 1),
(362, 63, 0, 'Managing Director', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'MANAGING_DIRECTOR', 'Managing D', 'active', 'No', 1, 1),
(363, 63, 0, 'General Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GENERAL_MANAGER', 'General Ma', 'active', 'No', 1, 1),
(364, 63, 0, 'Resident Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RESIDENT_MANAGER', 'Resident M', 'active', 'No', 1, 1),
(365, 63, 0, 'Hotel Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HOTEL_MANAGER', 'Hotel Mana', 'active', 'No', 1, 1),
(366, 63, 0, 'Director Of Operations', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_OPERATIONS', 'Director O', 'active', 'No', 1, 1),
(367, 63, 0, 'Quality Assurance Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'QUALITY_ASSURANCE_MANAGER', 'Quality As', 'active', 'No', 1, 1),
(368, 63, 0, 'Controller', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CONTROLLER', 'Controller', 'active', 'No', 1, 1),
(369, 63, 0, 'Director Of Finance', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_FINANCE', 'Director O', 'active', 'No', 1, 1),
(370, 63, 0, 'Assistant Director Of Finance', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ASSISTANT_DIRECTOR_OF_FINANCE', 'Assistant ', 'active', 'No', 1, 1),
(371, 63, 0, 'Assistant Controller', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ASSISTANT_CONTROLLER', 'Assistant ', 'active', 'No', 1, 1),
(372, 63, 0, 'Accounting Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTING_MANAGER', 'Accounting', 'active', 'No', 1, 1),
(373, 63, 0, 'Credit Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CREDIT_MANAGER', 'Credit Man', 'active', 'No', 1, 1),
(374, 63, 0, 'Chief Accountant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CHIEF_ACCOUNTANT', 'Chief Acco', 'active', 'No', 1, 1),
(375, 63, 0, 'Accounts Receivable Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTS_RECEIVABLE_MANAGER', 'Accounts R', 'active', 'No', 1, 1),
(376, 63, 0, 'Financial Analyst', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'FINANCIAL_ANALYST', 'Financial ', 'active', 'No', 1, 1),
(377, 63, 0, 'Audit Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'AUDIT_MANAGER', 'Audit Mana', 'active', 'No', 1, 1),
(378, 63, 0, 'Accounts Payable Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTS_PAYABLE_MANAGER', 'Accounts P', 'active', 'No', 1, 1),
(379, 63, 0, 'Cost Controller', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'COST_CONTROLLER', 'Cost Contr', 'active', 'No', 1, 1),
(380, 63, 0, 'Profit Improvement Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PROFIT_IMPROVEMENT_MANAGER', 'Profit Imp', 'active', 'No', 1, 1),
(381, 63, 0, 'Director Of Purchasing', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_PURCHASING', 'Director O', 'active', 'No', 1, 1),
(382, 63, 0, 'Director Of Security', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_SECURITY', 'Director O', 'active', 'No', 1, 1),
(383, 63, 0, 'Director Of Human Resources', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'DIRECTOR_OF_HUMAN_RESOURCES', 'Director O', 'active', 'No', 1, 1),
(384, 63, 0, 'Human Resources Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HUMAN_RESOURCES_MANAGER', 'Human Reso', 'active', 'No', 1, 1),
(385, 63, 0, 'Training Director', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'TRAINING_DIRECTOR', 'Training D', 'active', 'No', 1, 1),
(386, 63, 0, 'Benefits Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BENEFITS_MANAGER', 'Benefits M', 'active', 'No', 1, 1),
(387, 63, 0, 'Employee Relations Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'EMPLOYEE_RELATIONS_MANAGER', 'Employee R', 'active', 'No', 1, 1),
(388, 63, 0, 'Employment Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'EMPLOYMENT_MANAGER', 'Employment', 'active', 'No', 1, 1),
(389, 63, 0, 'Package Room Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PACKAGE_ROOM_MANAGER', 'Package Ro', 'active', 'No', 1, 1),
(390, 63, 0, 'Security Manager', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SECURITY_MANAGER', 'Security M', 'active', 'No', 1, 1),
(391, 64, 0, 'Accounts Payable Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTS_PAYABLE_CLERK', 'Accounts P', 'active', 'No', 1, 1),
(392, 64, 0, 'Accounts Receivable Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTS_RECEIVABLE_CLERK', 'Accounts R', 'active', 'No', 1, 1),
(393, 64, 0, 'General Cashier', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GENERAL_CASHIER', 'General Ca', 'active', 'No', 1, 1),
(394, 64, 0, 'Paymaster', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PAYMASTER', 'Paymaster', 'active', 'No', 1, 1),
(395, 64, 0, 'Staff Accountant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'STAFF_ACCOUNTANT', 'Staff Acco', 'active', 'No', 1, 1),
(396, 64, 0, 'Group Billing Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GROUP_BILLING_CLERK', 'Group Bill', 'active', 'No', 1, 1),
(397, 64, 0, 'Accounting Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'ACCOUNTING_CLERK', 'Accounting', 'active', 'No', 1, 1),
(398, 65, 0, 'Human Resources Coordinator', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'HUMAN_RESOURCES_COORDINATOR', 'Human Reso', 'active', 'No', 1, 1),
(399, 65, 0, 'Benefits Coordinator', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BENEFITS_COORDINATOR', 'Benefits C', 'active', 'No', 1, 1),
(400, 66, 0, 'Buyer', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'BUYER', 'Buyer', 'active', 'No', 1, 1),
(401, 66, 0, 'Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'CLERK', 'Clerk', 'active', 'No', 1, 1),
(402, 66, 0, 'Receiving Agent', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RECEIVING_AGENT', 'Receiving ', 'active', 'No', 1, 1),
(403, 66, 0, 'Storekeeper', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'STOREKEEPER', 'Storekeepe', 'active', 'No', 1, 1),
(404, 66, 0, 'Purchasing Agent', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PURCHASING_AGENT', 'Purchasing', 'active', 'No', 1, 1),
(405, 66, 0, 'Purchasing Coordinator', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PURCHASING_COORDINATOR', 'Purchasing', 'active', 'No', 1, 1),
(406, 66, 0, 'Storeroom And Receiving', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'STOREROOM_AND_RECEIVING', 'Storeroom ', 'active', 'No', 1, 1),
(407, 66, 0, 'General Storeroom Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'GENERAL_STOREROOM_ATTENDANT', 'General St', 'active', 'No', 1, 1),
(408, 66, 0, 'Receiving Clerk', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'RECEIVING_CLERK', 'Receiving ', 'active', 'No', 1, 1),
(409, 66, 0, 'Package Room Attendant', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'PACKAGE_ROOM_ATTENDANT', 'Package Ro', 'active', 'No', 1, 1),
(410, 67, 0, 'Security Officer', '2025-10-28 09:20:08', '2025-10-28 09:20:08', 'SECURITY_OFFICER', 'Security O', 'active', 'No', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `position_monthly_data`
--

DROP TABLE IF EXISTS `position_monthly_data`;
CREATE TABLE IF NOT EXISTS `position_monthly_data` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `manning_response_id` bigint UNSIGNED NOT NULL,
  `position_id` int UNSIGNED NOT NULL,
  `month` int NOT NULL,
  `headcount` int NOT NULL,
  `vacantcount` int NOT NULL,
  `filledcount` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `position_monthly_data_manning_response_id_foreign` (`manning_response_id`),
  KEY `position_monthly_data_position_id_foreign` (`position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `position_monthly_data`
--

INSERT INTO `position_monthly_data` (`id`, `manning_response_id`, `position_id`, `month`, `headcount`, `vacantcount`, `filledcount`, `created_at`, `updated_at`) VALUES
(2965, 54, 139, 1, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2966, 54, 139, 2, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2967, 54, 139, 3, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2968, 54, 139, 4, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2969, 54, 139, 5, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2970, 54, 139, 6, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2971, 54, 139, 7, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2972, 54, 139, 8, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2973, 54, 139, 9, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2974, 54, 139, 10, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2975, 54, 139, 11, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2976, 54, 139, 12, 1, 0, 1, '2025-11-09 06:09:55', '2025-11-09 06:09:55'),
(2977, 55, 141, 1, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2978, 55, 141, 2, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2979, 55, 141, 3, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2980, 55, 141, 4, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2981, 55, 141, 5, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2982, 55, 141, 6, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2983, 55, 141, 7, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2984, 55, 141, 8, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2985, 55, 141, 9, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2986, 55, 141, 10, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2987, 55, 141, 11, 2, 1, 1, '2025-11-09 06:12:25', '2025-11-09 06:12:25'),
(2988, 55, 141, 12, 2, 1, 1, '2025-11-09 06:12:26', '2025-11-09 06:12:26'),
(3025, 56, 143, 1, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3026, 56, 143, 2, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3027, 56, 143, 3, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3028, 56, 143, 4, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3029, 56, 143, 5, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3030, 56, 143, 6, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3031, 56, 143, 7, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3032, 56, 143, 8, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3033, 56, 143, 9, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3034, 56, 143, 10, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3035, 56, 143, 11, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3036, 56, 143, 12, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3037, 56, 144, 1, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3038, 56, 144, 2, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3039, 56, 144, 3, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3040, 56, 144, 4, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3041, 56, 144, 5, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3042, 56, 144, 6, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3043, 56, 144, 7, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3044, 56, 144, 8, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3045, 56, 144, 9, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3046, 56, 144, 10, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3047, 56, 144, 11, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3048, 56, 144, 12, 1, 0, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3049, 56, 145, 1, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3050, 56, 145, 2, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3051, 56, 145, 3, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3052, 56, 145, 4, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3053, 56, 145, 5, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3054, 56, 145, 6, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3055, 56, 145, 7, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3056, 56, 145, 8, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3057, 56, 145, 9, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3058, 56, 145, 10, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3059, 56, 145, 11, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3060, 56, 145, 12, 2, 1, 1, '2025-11-15 12:37:20', '2025-11-15 12:37:20'),
(3109, 58, 146, 1, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3110, 58, 146, 2, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3111, 58, 146, 3, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3112, 58, 146, 4, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3113, 58, 146, 5, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3114, 58, 146, 6, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3115, 58, 146, 7, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3116, 58, 146, 8, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3117, 58, 146, 9, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3118, 58, 146, 10, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3119, 58, 146, 11, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3120, 58, 146, 12, 2, 1, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3121, 58, 147, 1, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3122, 58, 147, 2, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3123, 58, 147, 3, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3124, 58, 147, 4, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3125, 58, 147, 5, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3126, 58, 147, 6, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3127, 58, 147, 7, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3128, 58, 147, 8, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3129, 58, 147, 9, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3130, 58, 147, 10, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3131, 58, 147, 11, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3132, 58, 147, 12, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3133, 58, 151, 1, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3134, 58, 151, 2, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3135, 58, 151, 3, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3136, 58, 151, 4, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3137, 58, 151, 5, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3138, 58, 151, 6, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3139, 58, 151, 7, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3140, 58, 151, 8, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3141, 58, 151, 9, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3142, 58, 151, 10, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3143, 58, 151, 11, 1, 0, 1, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3144, 58, 151, 12, 1, 0, 0, '2025-11-15 12:49:43', '2025-11-15 12:49:43'),
(3145, 57, 148, 1, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3146, 57, 148, 2, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3147, 57, 148, 3, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3148, 57, 148, 4, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3149, 57, 148, 5, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3150, 57, 148, 6, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3151, 57, 148, 7, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3152, 57, 148, 8, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3153, 57, 148, 9, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3154, 57, 148, 10, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3155, 57, 148, 11, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3156, 57, 148, 12, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3157, 57, 149, 1, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3158, 57, 149, 2, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3159, 57, 149, 3, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3160, 57, 149, 4, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3161, 57, 149, 5, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3162, 57, 149, 6, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3163, 57, 149, 7, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3164, 57, 149, 8, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3165, 57, 149, 9, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3166, 57, 149, 10, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3167, 57, 149, 11, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3168, 57, 149, 12, 1, 0, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3169, 57, 150, 1, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3170, 57, 150, 2, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3171, 57, 150, 3, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3172, 57, 150, 4, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3173, 57, 150, 5, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3174, 57, 150, 6, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3175, 57, 150, 7, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3176, 57, 150, 8, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3177, 57, 150, 9, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3178, 57, 150, 10, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3179, 57, 150, 11, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3180, 57, 150, 12, 2, 1, 1, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3181, 57, 152, 1, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3182, 57, 152, 2, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3183, 57, 152, 3, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3184, 57, 152, 4, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3185, 57, 152, 5, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3186, 57, 152, 6, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3187, 57, 152, 7, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3188, 57, 152, 8, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3189, 57, 152, 9, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3190, 57, 152, 10, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3191, 57, 152, 11, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15'),
(3192, 57, 152, 12, 6, 1, 5, '2025-12-05 13:28:15', '2025-12-05 13:28:15');

-- --------------------------------------------------------

--
-- Table structure for table `probationary_learning_programs`
--

DROP TABLE IF EXISTS `probationary_learning_programs`;
CREATE TABLE IF NOT EXISTS `probationary_learning_programs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED NOT NULL,
  `completion_days` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `probationary_learning_programs_resort_id_foreign` (`resort_id`),
  KEY `probationary_learning_programs_program_id_foreign` (`program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `probation_letter_templates`
--

DROP TABLE IF EXISTS `probation_letter_templates`;
CREATE TABLE IF NOT EXISTS `probation_letter_templates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `type` enum('success','failed','promotion','experience','offer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `probation_letter_templates_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `probation_letter_templates`
--

INSERT INTO `probation_letter_templates` (`id`, `resort_id`, `type`, `subject`, `content`, `placeholers`, `created_at`, `updated_at`) VALUES
(6, 26, 'offer', 'Offer of Employment – {{position_title}} at {{resort_name}}', '<p>Dear {{employee_name}},</p>\n\n<p>Congratulations! {{resort_name}} is pleased to offer you the position of {{position_title}} in the {{Department_title}} department. Your employment is scheduled to commence on {{date}}, subject to completion of all required onboarding formalities.</p>\n\n<p>Your employment type will be {{employment_type}}, and your reporting department will be {{Department_title}}. Your employee code {{employee_code}} will be used for all internal records and future correspondence.</p>\n\n<p>Please note that your probation period is until {{probation_end_date}}. Details of your compensation, benefits, and other terms and conditions of employment will be provided in your formal employment letter.</p>\n\n<p>If you accept this offer, kindly confirm your acceptance by replying to this email at your earliest convenience.</p>\n\n<p>We look forward to welcoming you to {{resort_name}} and wish you every success in your new role.</p>\n\n<p>Best regards,<br />\nHR Department<br />\n{{resort_name}}</p>', '', '2025-12-13 15:28:44', '2025-12-13 15:28:44'),
(7, 26, 'success', 'Probation Completion Confirmation – {{employee_name}} at {{resort_name}}', '<p>Dear {{employee_name}},</p>\n\n<p>We are delighted to inform you that you have successfully completed your probation period at {{resort_name}}. Your performance, dedication, and contribution to the {{Department_title}} department have been exemplary.</p>\n\n<p>As you are now a confirmed employee, your employment status has been changed from probationary to permanent. Your employee code {{employee_code}} remains the same, and you continue to hold the position of {{position_title}}.</p>\n\n<p>Your permanent contract will be issued shortly, and you will receive a formal letter outlining your updated terms and conditions of employment. All benefits and entitlements as per our company policy will continue to apply.</p>\n\n<p>We are confident that you will continue to demonstrate the same level of excellence and professionalism that you have shown during your probation period. We look forward to a long and productive working relationship with you.</p>\n\n<p>Should you have any questions or require clarification on any matters, please do not hesitate to contact the HR Department.</p>\n\n<p>Once again, congratulations on successfully completing your probation period. Welcome to {{resort_name}} as a permanent member of our team.</p>\n\n<p>Best regards,<br />\nHR Department<br />\n{{resort_name}}</p>', '', '2025-12-13 15:30:01', '2025-12-13 15:30:01'),
(8, 26, '', 'Employment Status Update – {{employee_name}} at {{resort_name}}', '<p>Dear {{employee_name}},</p>\n\n<p>We regret to inform you that after a thorough review of your performance during the probation period at {{resort_name}}, we have decided not to proceed with your employment beyond the probation term.</p>\n\n<p>The probation period was designed to assess your suitability for the {{position_title}} role in the {{Department_title}} department. Unfortunately, despite our support and guidance, your performance has not met the required standards and expectations for this position.</p>\n\n<p>Your employment with {{resort_name}}, employee code {{employee_code}}, will be terminated effective {{probation_end_date}}. This decision is final and made in accordance with your employment contract and our company policies.</p>\n\n<p>During your final week of employment, please ensure that you:<br />\n- Complete all outstanding tasks<br />\n- Hand over all company materials and equipment<br />\n- Participate in an exit interview with our HR Department<br />\n- Arrange the return of access cards and identification</p>\n\n<p>Your final salary payment, including any accrued benefits owed to you, will be processed in accordance with applicable labor laws and regulations.</p>\n\n<p>We wish you the best of luck in your future endeavors. Should you have any questions regarding the termination or exit procedures, please contact the HR Department immediately.</p>\n\n<p>Best regards,<br />\nHR Department<br />\n{{resort_name}}</p>', '', '2025-12-13 15:32:18', '2025-12-13 15:32:18'),
(9, 26, 'promotion', 'Promotion Notification – {{employee_name}} at {{resort_name}}', '<p>Dear {{employee_name}},</p>\n\n<p>It is with great pleasure that we inform you of your promotion to the position of {{new_position}} at {{resort_name}}. This recognition reflects your outstanding performance, dedication, and valuable contributions to the {{current_department}} department.</p>\n\n<p>Your promotion is effective from {{date}}, and your employee code {{employee_code}} will remain unchanged in our system. This new role represents a significant milestone in your career progression with us and demonstrates our confidence in your abilities and leadership potential.</p>\n\n<p>Role and Responsibilities:<br />\nAs {{new_position}}, you will be responsible for overseeing strategic initiatives within {{new_department}} and working collaboratively with cross-functional teams. Your proven track record and expertise make you well-suited for this challenging and rewarding role.</p>\n\n<p>Compensation and Benefits:<br />\nDetails regarding your updated salary, allowances, and benefits structure will be provided in a formal letter from our Finance Department. Your new compensation package reflects the increased scope and responsibilities of your promoted position.</p>\n\n<p>The revised employment contract incorporating your new role and terms will be prepared and shared with you shortly. Please review it carefully and confirm your acceptance.</p>\n\n<p>We are confident that you will bring the same level of excellence and professionalism to this new role as you have consistently demonstrated. We look forward to seeing you flourish in this position and make continued contributions to {{resort_name}}.</p>\n\n<p>Should you have any questions or require further clarification, please do not hesitate to contact the HR Department.</p>\n\n<p>Congratulations once again on your well-deserved promotion.</p>\n\n<p>Best regards,<br />\nHR Department<br />\n{{resort_name}}</p>', '', '2025-12-13 15:34:49', '2025-12-13 15:34:49'),
(10, 26, 'experience', 'Employment Certificate / Experience Letter – {{employee_name}}', '<p>TO WHOM IT MAY CONCERN,</p>\n\n<p>This is to certify that {{employee_name}}, holding employee code {{employee_code}}, has been employed with {{resort_name}} in the capacity of {{position_title}} in the {{Department_title}} department.</p>\n\n<p>Period of Employment:<br />\nDate of Joining: {{date}}<br />\nDate of Separation: {{last_working_day}}<br />\nDuration of Service: [As per employment records]</p>\n\n<p>Employment Status:<br />\nEmployee Code: {{employee_code}}<br />\nEmployment Type: {{employment_type}}<br />\nDepartment: {{Department_title}}<br />\nPosition Held: {{position_title}}</p>\n\n<p>Work Experience and Responsibilities:<br />\nDuring the tenure of employment, {{employee_name}} has discharged his/her duties in a professional and dedicated manner. {{employee_name}} has demonstrated strong work ethic, reliability, and commitment to the organization&#39;s objectives. The employee has gained valuable experience in various aspects of {{Department_title}} operations.</p>\n\n<p>Performance and Conduct:<br />\n{{employee_name}} has consistently maintained a professional demeanor and has worked collaboratively with colleagues. The employee has adhered to all company policies and procedures and has been a valued member of the team.</p>\n\n<p>Skills and Competencies:<br />\nDuring the employment period, {{employee_name}} has developed competencies in areas relevant to the {{position_title}} position and has contributed meaningfully to the department&#39;s success.</p>\n\n<p>This certificate is issued for the purpose of serving as a verification of employment and experience. The employee is free to pursue further employment opportunities, and {{resort_name}} wishes {{employee_name}} the very best in future endeavors.</p>\n\n<p>Issued Date: {{date}}</p>\n\n<p>Signature: _____________________<br />\nName: _____________________<br />\nDesignation: HR Manager<br />\n{{resort_name}}</p>\n\n<p>Note: This is an electronically generated certificate and is valid as per {{resort_name}} employment records.</p>', '', '2025-12-13 15:36:06', '2025-12-13 15:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `shopkeeper_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qr_code` blob,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_shopkeeper_id_foreign` (`shopkeeper_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `shopkeeper_id`, `name`, `price`, `qr_code`, `created_at`, `updated_at`) VALUES
(46, 49, 'Cigarette', 200.00, 0x89504e470d0a1a0a0000000d4948445200000080000000800806000000c33e61cb000000017352474200aece1ce900000044655849664d4d002a00000008000187690004000000010000001a000000000003a00100030000000100010000a00200040000000100000080a0030004000000010000008000000000488e04770000067d494441547801ed9deb6edc3a0c849b83bcff2be7543f16703f8fa359ae9d8dc5295064490d875788c2a2978f3f7ffe7cfdfdfde3bfbebee66e3f3e3e7671cdec940d49661cc43f6487fb817dfc747c55781ffcaffefcef5582d8dfbb0219807bf7efe5e833002f97f0de049f0cdfd959b4716467cf11a36271308c873ce420fe4826cf116eaba72f87c3c16c7db89f19cbb0cb0de0566f515c0660d1c6ba696500dc4a2d8adbbd0198a7da1bc428b9b2c768e3f826861c233607a372a08e3c3cbf4aaefa55b5608cb9015891667206a059c3996e068015692667009a359ce94e1f813478b7cc878df340726c2a18d642c5425edabc5bce0df0ee0ebcd97f06e0cd0d78b7fb0cc0bb3bf066ffbffa0da0f627f7acc2b0a6b4e1f99089a9f02a9b0aaf8aef2a5d6e80ab2a7b13de0cc04d1a7555981980ab2a7b13de0cc04d1a755598d347a07ad85c158cf360623c151b153f791466a63b8343f960ce0a53d5e506a8566e11bb0cc0228daca69101a8566e11bbdd1be0aa3d7656bd181ff723cf87dfab30cc897ec6b98a8776942b36e470e5dc006ea516c56500166dac9b5606c0add4a2b84fb5b756cb953b55e54c8caa8183a19df255c1d0e62c3937c05995bc294f06e0a68d3b2bec0cc05995bc294f06e0a68d3b2becdd174115e2cae348f9e18349f13a18c53dd339bc338c13af8a43d96d71f4bb3dfbee3379154f6e80ef2ad8e02c03d0a0c9dfa59801f8ae3a0dcec6bfc3f6edbfd7a6f606778baa93b253b857754e2c8e0fc6ab788921afb22146c93fc5abe2cb0da03ad248970168d46c956a064055a5912e03d0a8d92ad5dd17417c90a887038968c3f321ff148f8a85beab1895d75657e59dc5a778b77ec76772f0fc48ce0d70549926fa0c4093461fa5990138aa4c13fdc7dffdf2cf1741ce2e8189b57f68a3ea4bdfcac6c190bb62438e21938718152f314abe8a57f9a22e37002bd24cce00346b38d3cd00b022cde4e91ba0bad75847ee39879736e454f255bcc317b92bf15563567667e872039c51c51b7364006edcbc3342cf009c51c51b7364006edcbc3342ffac3c6468c3c7910a8c18720c1b621c1e8599e9941f15cf8c87e755de77face0dc02e36933300cd1ace743300ac483379f707429cfcb9ebd40e7330335fe498e1c7b98ac5b1a32fc5a3740ef716433fdbb3c7e733fc3cb8663f7303cc2ab4f8790660f106cfd2cb00cc2ab4f8790660f106cfd22b3d022b8f14e7f1c360959f19cfec7cf850bcd45579980365fa19e7335fea5cf1d09723e70670aab4302603b070739dd432004e9516c6ecde006adf307f629c7d440c39e8e34826cf116eaba72fca035be1ddfa788583be191fcfe9d795c93bec7203b8d55b14970158b4b16e5a1900b7528be232008b36d64d6bf708a4a17a80f031417970d04e61e88bb263e3f87130f47d96ece4405f4ebcc4501e9c8eefdc00ac7e333903d0ace14c3703c08a3493777f35ecaafcd58eaaf872f61a792bbe951ff210c3f31107318c6dc8ca4ee1b63a87778b1f9f959fdc00ac52333903d0ace14c3703c08a34934bdf03546a54d959ca8fda635b9cf2a3745b9b2b3fcfe255be196f8563f03a76b90154071ae932008d9aad52cd00a8aa34d265001a355ba55afa0f2348a41e1bb3870ccfc979242b5f5bec55bcc347857b16ef36f6673e576251fcb90154551ae932008d9aad52cd00a8aa34d29df2bf873bfbc8c138fb923c8e0d31e450fd7630b4a31f9e0ff92a5ee59bbe14263780ea52235d06a051b355aa1900559546ba0c40a366ab54a75f0429a333747ca00c4e3e521466e69b1c034f9e2b31b3f818cbc0331e62783ef3f1cc796e8067aab5203603b060539f492903f04cb516c4eefe4410f7cf593957f69863c378298ff8c9e36054dee451988a4ec5f32c8fe270e2cd0df06ca517c36700166be8b3e964009eadd862f8dd1b80f9397b843643563b49e19ed591d789ef0c1b274e271607e3f87230cc5bd9e406505569a4cb00346ab64a3503a0aad248970168d46c95eaf411a88caed2f1d15279309163c45ae151368afb8c5a9097be79ae72a28d8a4bf1e40650956aa4cb00346ab64a3503a0aad248f7abde004eddb9ebd45e9bf1546c669c47e7155f8e8d8361ad288f9873031c75ae893e03d0a4d1476966008e2ad3449f0168d2e8a334a78f40e7b17144feaabee25b3d741c1e65378bdfe12587e3a7c24b3fae9c1bc0add4a2b80cc0a28d75d3ca00b8955a14f7abfe66106becec4bda28993bd5e1a5cde075ec94ff994ef9dada9ce557f9c90db0ad74c3cf1980864ddfa69c01d856a3e1e70c40c3a66f53fe1fb3fd1618317ea91d0000000049454e44ae426082, '2025-11-26 22:44:51', '2025-11-26 22:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `professionalforms`
--

DROP TABLE IF EXISTS `professionalforms`;
CREATE TABLE IF NOT EXISTS `professionalforms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `FormName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_structure` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professionalforms_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `public_holidays`
--

DROP TABLE IF EXISTS `public_holidays`;
CREATE TABLE IF NOT EXISTS `public_holidays` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `holiday_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `public_holidays`
--

INSERT INTO `public_holidays` (`id`, `name`, `holiday_date`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(10, 'New Year’s Day', '01-01-2026', NULL, 'active', 11, 11, '2025-12-06 13:12:18', '2025-12-06 13:12:18'),
(11, 'First of Ramazan', '18-01-2026', NULL, 'active', 11, 11, '2025-12-06 13:12:52', '2025-12-06 13:12:52'),
(12, 'Eid-ul-Fitr', '20-03-2026', NULL, 'active', 11, 11, '2025-12-06 13:13:17', '2025-12-06 13:13:17'),
(13, 'Eid-ul-Fitr Holiday', '21-03-2026', NULL, 'active', 11, 11, '2025-12-06 13:13:44', '2025-12-06 13:13:44'),
(14, 'Labour Day / May Day', '01-05-2026', NULL, 'active', 11, 11, '2025-12-06 13:14:13', '2025-12-06 13:14:13'),
(15, 'Hajj Day', '26-05-2026', NULL, 'active', 11, 11, '2025-12-06 13:14:41', '2025-12-06 13:14:41'),
(16, 'Eid-ul Al’haa', '27-05-2026', NULL, 'active', 11, 11, '2025-12-06 13:15:01', '2025-12-06 13:15:01'),
(17, 'Eid-ul Al’haa Holiday', '28-05-2026', NULL, 'active', 11, 11, '2025-12-06 13:15:20', '2025-12-06 13:15:20'),
(18, 'Islamic New Year', '17-06-2026', NULL, 'active', 11, 11, '2025-12-06 13:15:40', '2025-12-06 13:15:40'),
(19, 'Independence Day', '26-06-2026', NULL, 'active', 11, 11, '2025-12-06 13:16:02', '2025-12-06 13:16:02'),
(20, 'Day Maldives Embraced Islam', '14-09-2026', NULL, 'active', 11, 11, '2025-12-06 13:16:44', '2025-12-06 13:16:44'),
(21, 'Victory Day', '03-11-2026', NULL, 'active', 11, 11, '2025-12-06 13:17:10', '2025-12-06 13:17:10'),
(22, 'Republic Day​', '11-11-2026', NULL, 'active', 11, 11, '2025-12-06 13:17:26', '2025-12-06 13:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaires`
--

DROP TABLE IF EXISTS `questionnaires`;
CREATE TABLE IF NOT EXISTS `questionnaires` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `Division_id` int UNSIGNED NOT NULL,
  `Department_id` int UNSIGNED NOT NULL,
  `Position_id` int UNSIGNED NOT NULL,
  `video` enum('Yes','No') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `questionnaires_resort_id_foreign` (`Resort_id`),
  KEY `questionnaires_division_id_foreign` (`Division_id`),
  KEY `questionnaires_department_id_foreign` (`Department_id`),
  KEY `questionnaires_position_id_foreign` (`Position_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questionnaires`
--

INSERT INTO `questionnaires` (`id`, `Resort_id`, `Division_id`, `Department_id`, `Position_id`, `video`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(12, 26, 76, 78, 146, 'No', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(13, 26, 76, 78, 146, 'No', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_children`
--

DROP TABLE IF EXISTS `questionnaire_children`;
CREATE TABLE IF NOT EXISTS `questionnaire_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Q_Parent_id` bigint UNSIGNED NOT NULL,
  `Question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `questionType` enum('single','multiple','Radio') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ans` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `questionnaire_children_q_parent_id_foreign` (`Q_Parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `questionnaire_children`
--

INSERT INTO `questionnaire_children` (`id`, `Q_Parent_id`, `Question`, `questionType`, `options`, `ans`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 12, 'What is your experience level in HR management and employee relations?', 'single', NULL, '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(2, 12, 'Describe your experience with recruitment and onboarding processes in the hospitality industry.', 'single', NULL, '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(3, 12, 'How would you handle employee conflicts and performance management in a diverse hospitality environment?', 'single', NULL, '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(4, 12, 'Which of the following areas are you confident in managing?', 'multiple', '[null,null,null]', '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(5, 12, 'What certifications or training have you completed related to HR?', 'multiple', '[null,null,null]', '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(6, 12, 'Which of these soft skills are most important for an HR Coordinator?', 'multiple', '[null,null,null]', '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(7, 12, 'What is your preferred communication style with employees?', 'single', NULL, '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(8, 12, 'How do you prioritize multiple HR tasks in a busy environment?', 'Radio', NULL, '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(9, 12, 'What is your understanding of labor laws and compliance requirements?', 'Radio', NULL, '1', 259, 259, '2025-12-08 10:42:20', '2025-12-08 10:42:20'),
(10, 13, 'What is your experience level in HR management and employee relations?', 'single', NULL, '1', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37'),
(11, 13, 'Describe your experience with recruitment and onboarding processes in the hospitality industry.', 'single', NULL, '1', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37'),
(12, 13, 'How would you handle employee conflicts and performance management in a diverse hospitality environment?', 'single', NULL, '1', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37'),
(13, 13, 'Which document is mandatory before an expatriate employee can legally start work in a Maldives resort or hospital?', 'multiple', '[\"Work Permit and Employment Approval\",\"Police Clearance Certificate\",\"Educational Certificate Attestation\"]', '1', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37'),
(14, 13, 'What is the most important HR responsibility to ensure accurate payroll processing in a resort or hospital environment?', 'multiple', '[\"Updating staff uniforms and ID cards\",\"Verifying attendance, approved leave, and overtime records\",\"Conducting employee engagement activities\"]', '1', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37'),
(15, 13, 'Who is primarily responsible for maintaining accurate employee personal files and contracts in a resort or hospital setting?', 'Radio', '[\"Updating staff uniforms and ID cards\",\"Verifying attendance, approved leave, and overtime records\",\"Conducting employee engagement activities\"]', '1', 259, 259, '2025-12-08 10:50:37', '2025-12-08 10:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `quota_slot_renewals`
--

DROP TABLE IF EXISTS `quota_slot_renewals`;
CREATE TABLE IF NOT EXISTS `quota_slot_renewals` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Month` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Currency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Payment_Date` date DEFAULT NULL,
  `Due_Date` date DEFAULT NULL,
  `Status` enum('Paid','Unpaid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unpaid',
  `Reciept_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ReceiptNumber` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PaymentType` enum('Lumpsum','Installment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Installment',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quota_slot_renewals_resort_id_foreign` (`resort_id`),
  KEY `quota_slot_renewals_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registered_devices`
--

DROP TABLE IF EXISTS `registered_devices`;
CREATE TABLE IF NOT EXISTS `registered_devices` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `emp_id` int UNSIGNED NOT NULL,
  `device_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registered_devices_device_id_unique` (`device_id`),
  KEY `registered_devices_emp_id_foreign` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resignation_meeting_schedule`
--

DROP TABLE IF EXISTS `resignation_meeting_schedule`;
CREATE TABLE IF NOT EXISTS `resignation_meeting_schedule` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resignationId` bigint UNSIGNED DEFAULT NULL,
  `title` text COLLATE utf8mb4_unicode_ci,
  `meeting_date` date DEFAULT NULL,
  `meeting_time` time DEFAULT NULL,
  `meeting_with` enum('HOD','HR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HOD',
  `status` enum('Pending','Completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resignation_meeting_schedule_resignationid_foreign` (`resignationId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resortholidays`
--

DROP TABLE IF EXISTS `resortholidays`;
CREATE TABLE IF NOT EXISTS `resortholidays` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `PublicHolidaydate` date DEFAULT NULL,
  `PublicHolidayName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `HolidayId` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resortholidays_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resortholidays`
--

INSERT INTO `resortholidays` (`id`, `resort_id`, `PublicHolidaydate`, `PublicHolidayName`, `HolidayId`, `created_by`, `modified_by`, `created_at`, `updated_at`, `description`) VALUES
(24, 26, '2025-12-31', 'New Year', '0', 240, 240, '2025-11-17 13:41:54', '2025-11-17 13:41:54', NULL),
(25, 26, '2025-12-25', 'Crismas', '0', 259, 259, '2025-12-26 10:21:14', '2025-12-26 10:21:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resorts`
--

DROP TABLE IF EXISTS `resorts`;
CREATE TABLE IF NOT EXISTS `resorts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_prefix` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Position_access` int DEFAULT NULL,
  `resort_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address1` text COLLATE utf8mb4_unicode_ci,
  `address2` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resort_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_it_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_it_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `same_billing_address` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `billing_address1` text COLLATE utf8mb4_unicode_ci,
  `billing_address2` text COLLATE utf8mb4_unicode_ci,
  `billing_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_pincode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_package` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contract_start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contract_end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_users` int DEFAULT NULL,
  `headoffice_address1` text COLLATE utf8mb4_unicode_ci,
  `headoffice_address2` text COLLATE utf8mb4_unicode_ci,
  `headoffice_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headoffice_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headoffice_country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headoffice_pincode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_preference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Support_SLA` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resorts_resort_email_unique` (`resort_email`),
  UNIQUE KEY `resorts_resort_id_unique` (`resort_id`),
  UNIQUE KEY `resorts_resort_it_email_unique` (`resort_it_email`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resorts`
--

INSERT INTO `resorts` (`id`, `resort_name`, `resort_prefix`, `Position_access`, `resort_email`, `resort_phone`, `address1`, `address2`, `city`, `state`, `zip`, `country`, `logo`, `email_logo`, `status`, `created_by`, `modified_by`, `deleted_at`, `created_at`, `updated_at`, `resort_id`, `resort_it_email`, `resort_it_phone`, `same_billing_address`, `billing_address1`, `billing_address2`, `billing_city`, `billing_state`, `billing_pincode`, `billing_country`, `tin`, `payment_method`, `invoice_email`, `payment_status`, `due_date`, `invoice_status`, `service_package`, `contract_start_date`, `contract_end_date`, `no_of_users`, `headoffice_address1`, `headoffice_address2`, `headoffice_city`, `headoffice_state`, `headoffice_country`, `headoffice_pincode`, `support_preference`, `Support_SLA`) VALUES
(25, 'Spring Resort', 'SR', 383, 'spring@yopmail.com', '4597600454', 'A-24 vai-Marve Creek, Manori island,', 'Manori - Gorai Rd, Malad West, Mumbai, Maharashtra', 'Mumbai', 'Kaafu Atoll (North Malé Atoll)', '8600057', 'Maldives', 'brand_logo.png', NULL, 'active', 11, 11, NULL, '2025-10-28 13:26:03', '2025-10-28 13:26:03', '829bf926d5', 'spring52@yopmail.com', '6400666645', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'TIN6588999', 'Cash', 'spring@yopmail.com', 'paid', '28-10-2025', 'paid', 'package1', '28-10-2025', '31-08-2026', 32, 'A-48 Ahmedabad near karkaria lack', 'Opp kakaria pond Ahmedabad', 'ahmedabad', 'ahmedabad', 'Maldives', '8600057', 'Email,Phone,LiveChat', '24/7 support'),
(26, 'Demo Resort', 'DR', 383, 'ameytamshetty@gmail.com', '9118585', 'Lot 11256', 'Rabargus Magu', 'Hulhumalé', 'Kaafu Atoll (North Malé Atoll)', '23000', 'Maldives', 'brand_logo.png', NULL, 'active', 11, 11, NULL, '2025-11-11 18:41:16', '2025-11-12 11:22:32', '87fca1b014', 'developer@thewisdom.ai', '9226622', 'yes', NULL, NULL, NULL, NULL, NULL, NULL, 'TIN123', 'Cash', 'ameytamshetty@live.com', 'paid', '30-11-2025', 'sent', 'package1', '11-11-2025', '11-11-2026', 100, 'Lot 11200', 'Mahafanu', 'Malé', 'Kaa', 'Maldives', '23001', 'Email,Phone,LiveChat', 'Business Hours only');

-- --------------------------------------------------------

--
-- Table structure for table `resorts_child_notifications`
--

DROP TABLE IF EXISTS `resorts_child_notifications`;
CREATE TABLE IF NOT EXISTS `resorts_child_notifications` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `response` enum('Yes','No','Pending','Approval','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Parent_msg_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Position_id` int NOT NULL,
  `Department_id` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resorts_child_notifications_parent_msg_id_foreign` (`Parent_msg_id`)
) ENGINE=InnoDB AUTO_INCREMENT=345 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resorts_child_notifications`
--

INSERT INTO `resorts_child_notifications` (`id`, `response`, `created_at`, `updated_at`, `Parent_msg_id`, `Position_id`, `Department_id`, `created_by`, `modified_by`) VALUES
(340, 'Yes', '2025-11-06 16:51:46', '2025-11-09 06:09:55', 'SR413172861', 139, 75, 233, 233),
(341, 'Yes', '2025-11-06 16:51:46', '2025-11-09 06:12:26', 'SR413172861', 141, 77, 233, 233),
(342, 'Yes', '2025-11-15 12:01:35', '2025-12-05 13:28:15', 'DR620587438', 149, 80, 259, 259),
(343, 'Yes', '2025-11-15 12:01:35', '2025-11-15 12:49:43', 'DR620587438', 151, 78, 259, 259),
(344, 'Yes', '2025-11-15 12:01:35', '2025-11-15 12:37:20', 'DR620587438', 144, 79, 259, 259);

-- --------------------------------------------------------

--
-- Table structure for table `resorts_parent_notifications`
--

DROP TABLE IF EXISTS `resorts_parent_notifications`;
CREATE TABLE IF NOT EXISTS `resorts_parent_notifications` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int NOT NULL,
  `user_type` enum('super','sub') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sub',
  `user_id` int DEFAULT NULL,
  `message_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_subject` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `modified_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Active','OnLeave','Probationary','Terminated','Inactive','Retired','Resigned','Suspended','transferred','contractual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `resorts_parent_notifications_message_id_unique` (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resorts_parent_notifications`
--

INSERT INTO `resorts_parent_notifications` (`id`, `resort_id`, `user_type`, `user_id`, `message_id`, `message_subject`, `created_by`, `modified_by`, `created_at`, `updated_at`, `status`) VALUES
(142, 25, 'super', 233, 'SR413172861', 'This is a Manning Request for additional manpower requirements. Please review and update your response accordingly.', 233, 233, '2025-11-06 16:51:46', '2025-11-06 16:51:46', 'Active'),
(143, 26, 'sub', 259, 'DR620587438', 'Dear all\r\nplease submit the manning requisition for the next year', 259, 259, '2025-11-15 12:01:35', '2025-11-15 12:01:35', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `resort_admins`
--

DROP TABLE IF EXISTS `resort_admins`;
CREATE TABLE IF NOT EXISTS `resort_admins` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `resort_id` int NOT NULL,
  `Position_access` int DEFAULT NULL,
  `Is_It` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('super','sub') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_master_admin` tinyint NOT NULL DEFAULT '0',
  `is_employee` tinyint NOT NULL DEFAULT '0',
  `address_line_1` text COLLATE utf8mb4_unicode_ci,
  `address_line_2` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `menu_type` enum('horizontal','vertical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'horizontal',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resort_admins_email_unique` (`email`),
  KEY `resort_admins_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=261 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_admins`
--

INSERT INTO `resort_admins` (`id`, `role_id`, `resort_id`, `Position_access`, `Is_It`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `type`, `remember_token`, `is_master_admin`, `is_employee`, `address_line_1`, `address_line_2`, `city`, `state`, `zip`, `country`, `profile_picture`, `menu_type`, `created_by`, `modified_by`, `deleted_at`, `created_at`, `updated_at`, `gender`, `personal_phone`, `status`, `signature_img`) VALUES
(233, 0, 25, 383, 'no', 'Kiran', 'Sh', 'Patel', 'spring5@yopmail.com', '$2y$10$KvjUjsUeBCvl8iiMNdTF2OAt4kGOwDYt.g/wjtyTUpye55GwuUmZe', 'super', NULL, 1, 0, 'A-48 Ahmedabad near karkaria lack', 'A-48 Ahmedabad near karkaria lack', 'Vadodara', 'Kaafu Atoll (North Malé Atoll)', '78954444', 'Maldives', 'user_image_2.png', 'horizontal', NULL, NULL, NULL, '2025-10-28 13:26:03', '2025-10-28 13:26:03', 'female', '8960005556', 'active', ''),
(238, 0, 25, NULL, 'no', 'HR', 'Test', 'User', 'hrtest@wisdom.ai', '$2y$10$KvjUjsUeBCvl8iiMNdTF2OAt4kGOwDYt.g/wjtyTUpye55GwuUmZe', 'sub', NULL, 0, 1, '123 Long Street Beach', 'Male', 'Male', 'Male', '20290', 'Maldives', '0', 'horizontal', 233, 233, NULL, '2025-11-01 06:29:45', '2025-11-01 06:29:45', 'male', '9865896523', 'Active', ''),
(239, 0, 25, NULL, 'no', 'FB', 'Test', 'User', 'fbtest@wisdom.ai', '$2y$10$KvjUjsUeBCvl8iiMNdTF2OAt4kGOwDYt.g/wjtyTUpye55GwuUmZe', 'sub', NULL, 0, 1, '124 Long Street Beach', 'Male', 'Male', 'Male', '20290', 'Maldives', '0', 'horizontal', 233, 238, NULL, '2025-11-06 15:38:22', '2025-11-06 15:55:45', 'female', '9865896526', 'Active', ''),
(240, 0, 26, 383, 'no', 'Amey', NULL, 'Tamshetti', 'amey.tamshetti@gmail.com', '$2y$10$gVgLCdzMHnw8t6WxfH0UauxygPmRb4SzIOxfuV5IFPymzpmD3jXnK', 'super', NULL, 1, 0, 'Line 1', 'Line 2', 'Mumbai', 'Kaafu Atoll (North Malé Atoll)', '23002', 'Maldives', 'B&W.png', 'vertical', NULL, 240, NULL, '2025-11-11 18:41:16', '2025-11-12 22:52:38', 'male', '8197933447', 'active', ''),
(241, 0, 26, NULL, 'no', 'Aminath', 'Zainab', 'Abdul', 'sing.blowup0u@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Bodu Hiyaa', 'Hulhumalé', 'Malé', NULL, '20100', 'Maldives', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:40', '2025-11-19 11:22:52', 'female', '+960 782 3456', 'Active', ''),
(242, 0, 26, NULL, 'no', 'Ibrahim', 'Faisal', 'Manik', 'borough_worlds5w@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Galolhu', 'Hulhumeedhoo', 'Malé', 'Kaafu', '20100', 'Maldives', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:40', '2025-12-04 22:57:49', 'male', '7557890', 'Active', ''),
(243, 0, 26, NULL, 'no', 'Sarah', 'Louise', 'Johnson', 'protein_smirks.5x@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '789 Main Street', 'Suite 200', 'Chicago', 'Illinois', '60601', 'USA', '0', 'horizontal', 240, 240, NULL, '2025-11-13 16:48:40', '2025-11-13 16:48:40', 'female', '+1 312-555-0145', 'Active', ''),
(244, 0, 26, NULL, 'no', 'Anastasia', 'Sergeyevna', 'Volkova', 'platter-trigram6t@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Ulitsa Tverskaya, 10', 'Kv. 45', 'Moscow', 'Moscow', '125009', 'Russia', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:40', '2025-12-04 23:30:59', 'female', '+7 495 123-45-67', 'Active', ''),
(245, 0, 26, NULL, 'no', 'Dmitri', 'Andreevich', 'Petrov', 'rewards_voodoo7b@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Nevsky Prospekt, 25', 'Kv. 12', 'Saint Petersburg', 'Saint Petersburg', '191023', 'Russia', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 16:15:01', 'male', '+7 812 987-65-43', 'Active', ''),
(246, 0, 26, NULL, 'no', 'Svetlana', 'Ivanovna', 'Kuznetsova', 'tempo_yang_5f@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Leninsky Prospekt, 15', 'Kv. 88', 'Moscow', 'Moscow', '119334', 'Russia', '0', 'horizontal', 240, 240, NULL, '2025-11-13 16:48:41', '2025-11-13 16:48:41', 'female', '+7 499 555-1234', 'Active', ''),
(247, 0, 26, NULL, 'no', 'Rajesh', 'Kumar', 'Patel', 'opuses_hubs.5x@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '15 Park Street', NULL, 'Mumbai', 'Maharashtra', '400001', 'India', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 16:43:35', 'male', '+91 22 2345 6789', 'Active', ''),
(248, 0, 26, NULL, 'no', 'Priya', 'Devi', 'Sharma', 'caliph-nursing-3q@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '27 Connaught Place', 'Floor 3', 'New Delhi', 'Delhi', '110001', 'India', '87fca1b014/public/categorized/DR-8/Profile/WhatsApp Image 2025-11-14 at 18.30.54.jpeg', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-18 19:18:05', 'female', '911145678901', 'Active', ''),
(249, 0, 26, NULL, 'no', 'Arjun', 'Vijay', 'Reddy', 'realest.rots-00@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '10 Brigade Road', NULL, 'Bangalore', 'Karnataka', '560001', 'India', '0', 'horizontal', 240, 240, NULL, '2025-11-13 16:48:41', '2025-11-13 16:48:41', 'male', '+91 80 3456 7890', 'Active', ''),
(250, 0, 26, NULL, 'no', 'Fatima', 'Ahmed', 'Naseer', 'stayer-banners-3d@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Villingili', 'Addu City', 'Hithadhoo', NULL, '20202', 'Maldives', 'app/87fca1b014/public/categorized/DR-10/Profile/Photo - V3844972.jpg', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-15 20:29:35', 'female', '+960 773 1234', 'Active', ''),
(251, 0, 26, NULL, 'no', 'Mohamed', 'Asif', 'Shareef', 'gloppy.plaice.1i@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Funadhoo', 'Alif Dhaal Atoll', 'Funadhoo', NULL, '20702', 'Maldives', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 19:46:26', 'male', '+960 799 8765', 'Active', ''),
(252, 0, 26, NULL, 'no', 'Mikhail', 'Dmitrievich', 'Orlov', 'drawn.godson.3k@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Kurortnyy microdistrict, 5', 'Kv. 33', 'Sochi', 'Sochi', '354000', 'Russia', '0', 'horizontal', 240, 240, NULL, '2025-11-13 16:48:41', '2025-11-13 16:48:41', 'male', '+7 840 555-6789', 'Active', ''),
(253, 0, 26, NULL, 'no', 'Elena', 'Vladimirovna', 'Morozova', 'florals.metric-14@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Krasny Prospekt, 30', 'Kv. 77', 'Novosibirsk', 'Novosibirsk', '630090', 'Russia', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 20:52:17', 'female', '+7 383 555-2345', 'Active', ''),
(254, 0, 26, NULL, 'no', 'Deepika', 'Anil', 'Iyer', 'pegged.77.strike@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '1 Marina Beach Road', NULL, 'Chennai', 'Tamil Nadu', '600001', 'India', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 21:53:18', 'female', '+91 44 5678 9012', 'Active', ''),
(255, 0, 26, NULL, 'no', 'John', 'Michael', 'Carter', 'plusher.joys6r@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '345 Las Vegas Blvd', NULL, 'Las Vegas', 'Nevada', '89109', 'USA', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:41', '2025-12-04 22:33:15', 'male', '+1 702-555-0156', 'Active', ''),
(256, 0, 26, NULL, 'no', 'Yulia', 'Alexandrovna', 'Sokolova', 'coops.bijou-0p@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Samarskaya Street, 12', 'Kv. 50', 'Samara', 'Samara', '443001', 'Russia', '0', 'horizontal', 240, 240, NULL, '2025-11-13 16:48:41', '2025-11-13 16:48:41', 'female', '+7 846 555-3456', 'Active', ''),
(257, 0, 26, NULL, 'no', 'Laila', 'Mohamed', 'Hassan', 'seams-tell-9t@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'Feydhoo', 'Laamu Atoll', 'Feydhoo', NULL, '20601', 'Maldives', '0', 'horizontal', 240, 259, NULL, '2025-11-13 16:48:42', '2025-12-04 15:18:45', 'female', '+960 765 4321', 'Active', ''),
(258, 0, 26, NULL, 'no', 'James', 'Robert', 'Wilson', 'coeds17_mutter@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '123 Park Avenue', 'Apt 5B', 'New York City', 'New York', '10001', 'USA', '0', 'horizontal', 240, 259, NULL, '2025-11-13 17:37:28', '2025-12-03 20:02:05', 'male', '+1 212-555-0198', 'Active', ''),
(259, 0, 26, NULL, 'no', 'Olivia', 'Marie', 'Davis', 'cottons.brink2p@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, '789 Golden Gate Ave', 'Unit 10', 'San Francisco', 'California', '94102', 'USA', '87fca1b014/public/categorized/DR-19/Profile/DSC_0031_sd_PP.jpeg', 'horizontal', 240, 259, NULL, '2025-11-13 17:49:29', '2025-12-12 23:36:06', 'female', '415550123', 'Active', ''),
(260, 0, 26, NULL, 'no', 'Rani', NULL, 'Khan', '09-wearers-primary@icloud.com', '$2y$10$g4aJ19Dm/FihZaziindLLu9GFco96ERuQkQ92lNmgyQtwUVycjJau', 'sub', NULL, 0, 1, 'CDM Building', '5th floor', 'Colombo', NULL, '20202', 'Sri Lanka', 'app/87fca1b014/public/categorized/DR-20/Profile/Photo - V3844972.jpg', 'horizontal', 240, 259, NULL, '2025-11-13 22:07:31', '2025-12-15 17:55:51', 'female', '+91949870987', 'Active', '');

-- --------------------------------------------------------

--
-- Table structure for table `resort_admin_password_resets`
--

DROP TABLE IF EXISTS `resort_admin_password_resets`;
CREATE TABLE IF NOT EXISTS `resort_admin_password_resets` (
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `resort_admin_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_admin_password_resets`
--

INSERT INTO `resort_admin_password_resets` (`email`, `token`, `created_at`) VALUES
('09-wearers-primary@icloud.com', '$2y$10$kZOBY63DnBfnL8s2QfFgzex/QpW72UOork89Ylltqi7v8KnxTUTvq', '2025-11-20 13:40:14');

-- --------------------------------------------------------

--
-- Table structure for table `resort_benefit_grid_child`
--

DROP TABLE IF EXISTS `resort_benefit_grid_child`;
CREATE TABLE IF NOT EXISTS `resort_benefit_grid_child` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `benefit_grid_id` int UNSIGNED NOT NULL,
  `leave_cat_id` int UNSIGNED NOT NULL,
  `rank` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocated_days` int NOT NULL,
  `eligible_emp_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_benefit_grid_child_benefit_grid_id_foreign` (`benefit_grid_id`),
  KEY `resort_benefit_grid_child_leave_cat_id_foreign` (`leave_cat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1899 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_benefit_grid_child`
--

INSERT INTO `resort_benefit_grid_child` (`id`, `benefit_grid_id`, `leave_cat_id`, `rank`, `allocated_days`, `eligible_emp_type`, `created_at`, `updated_at`) VALUES
(1789, 48, 57, '5', 30, 'all', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1790, 48, 58, '5', 10, 'all', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1791, 48, 59, '5', 60, 'female', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1792, 48, 60, '5', 3, 'male', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1793, 48, 61, '5', 1, 'all', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1794, 48, 62, '5', 30, 'all', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1795, 48, 64, '5', 5, 'muslim', '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(1804, 49, 57, '4', 30, 'all', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1805, 49, 58, '4', 10, 'all', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1806, 49, 59, '4', 60, 'female', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1807, 49, 60, '4', 3, 'male', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1808, 49, 61, '4', 1, 'all', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1809, 49, 62, '4', 30, 'all', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1810, 49, 64, '4', 5, 'muslim', '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(1811, 50, 57, '2', 30, 'all', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1812, 50, 58, '2', 10, 'all', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1813, 50, 59, '2', 60, 'female', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1814, 50, 60, '2', 3, 'male', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1815, 50, 61, '2', 1, 'all', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1816, 50, 62, '2', 30, 'all', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1817, 50, 63, '2', 12, 'all', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1818, 50, 64, '2', 5, 'muslim', '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(1819, 51, 57, '1', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1820, 51, 57, '3', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1821, 51, 57, '7', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1822, 51, 57, '8', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1823, 51, 58, '1', 10, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1824, 51, 58, '3', 10, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1825, 51, 58, '7', 10, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1826, 51, 58, '8', 10, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1827, 51, 59, '1', 60, 'female', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1828, 51, 59, '3', 60, 'female', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1829, 51, 59, '7', 60, 'female', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1830, 51, 59, '8', 60, 'female', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1831, 51, 60, '1', 3, 'male', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1832, 51, 60, '3', 3, 'male', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1833, 51, 60, '7', 3, 'male', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1834, 51, 60, '8', 3, 'male', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1835, 51, 61, '1', 1, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1836, 51, 61, '3', 1, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1837, 51, 61, '7', 1, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1838, 51, 61, '8', 1, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1839, 51, 62, '1', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1840, 51, 62, '3', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1841, 51, 62, '7', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1842, 51, 62, '8', 30, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1843, 51, 63, '1', 12, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1844, 51, 63, '3', 12, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1845, 51, 63, '7', 12, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1846, 51, 63, '8', 12, 'all', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1847, 51, 64, '1', 5, 'muslim', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1848, 51, 64, '3', 5, 'muslim', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1849, 51, 64, '7', 5, 'muslim', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1850, 51, 64, '8', 5, 'muslim', '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(1851, 52, 57, '6', 30, 'all', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1852, 52, 58, '6', 10, 'all', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1853, 52, 59, '6', 60, 'female', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1854, 52, 60, '6', 3, 'male', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1855, 52, 61, '6', 1, 'all', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1856, 52, 62, '6', 30, 'all', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1857, 52, 63, '6', 12, 'all', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1858, 52, 64, '6', 5, 'muslim', '2025-11-13 11:30:50', '2025-11-13 11:30:50'),
(1891, 47, 57, '6', 30, 'all', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1892, 47, 58, '6', 10, 'all', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1893, 47, 59, '6', 60, 'female', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1894, 47, 60, '6', 3, 'male', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1895, 47, 61, '6', 1, 'all', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1896, 47, 62, '6', 30, 'all', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1897, 47, 63, '6', 0, 'all', '2025-11-19 10:26:36', '2025-11-19 10:26:36'),
(1898, 47, 64, '6', 5, 'muslim', '2025-11-19 10:26:36', '2025-11-19 10:26:36');

-- --------------------------------------------------------

--
-- Table structure for table `resort_benifit_grid`
--

DROP TABLE IF EXISTS `resort_benifit_grid`;
CREATE TABLE IF NOT EXISTS `resort_benifit_grid` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `emp_grade` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rank` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `salary_period` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_charge` int DEFAULT NULL,
  `ramadan_bonus` int DEFAULT NULL,
  `ramadan_bonus_eligibility` varchar(191) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uniform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `health_care_insurance` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `day_off_per_week` int DEFAULT NULL,
  `working_hrs_per_week` int DEFAULT NULL,
  `emergency_leave` int DEFAULT NULL,
  `birthday_leave` int DEFAULT NULL,
  `public_holiday_per_year` int DEFAULT NULL,
  `paid_seak_leave_per_year` int DEFAULT NULL,
  `paid_companssionate_leave_per_year` int DEFAULT NULL,
  `paid_maternity_leave_per_year` int DEFAULT NULL,
  `paid_paternity_leave_per_year` int DEFAULT NULL,
  `paid_worked_public_holiday_and_friday` int DEFAULT NULL,
  `relocation_ticket` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `max_excess_luggage_relocation_expense` int DEFAULT NULL,
  `ticket_upon_termination` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `meals_per_day` int DEFAULT NULL,
  `accommodation_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `furniture_and_fixtures` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `housekeeping` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `laundry` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `internet_access` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `telephone` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `annual_leave` int DEFAULT NULL,
  `annual_leave_ticket` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `rest_and_relaxation_leave_per_year` int DEFAULT NULL,
  `no_of_r_and_r_leave` int DEFAULT NULL,
  `total_rest_and_relaxation_leave_per_year` int DEFAULT NULL,
  `rest_and_relaxation_allowance` int DEFAULT NULL,
  `paid_circumcision_leave_per_year` int DEFAULT NULL,
  `overtime` enum('yes','n/a') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `salary_paid_in` enum('USD','MVR') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MVR',
  `loan_and_salary_advanced` enum('yes','n/a') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `sports_and_entertainment_facilities` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `free_return_flight_to_male_per_year` int DEFAULT NULL,
  `food_and_beverages_discount` int DEFAULT NULL,
  `alchoholic_beverages_discount` int DEFAULT NULL,
  `spa_discount` int DEFAULT NULL,
  `dive_center_discount` int DEFAULT NULL,
  `water_sports_discount` int DEFAULT NULL,
  `friends_with_benefit_discount` int DEFAULT NULL,
  `standard_staff_rate_for_single` int DEFAULT NULL,
  `standard_staff_rate_for_double` int DEFAULT NULL,
  `staff_rate_for_seaplane_male` int DEFAULT NULL,
  `male_subsistence_allowance` int DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_benifit_grid_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resort_benifit_grid`
--

INSERT INTO `resort_benifit_grid` (`id`, `resort_id`, `emp_grade`, `rank`, `contract_status`, `effective_date`, `salary_period`, `service_charge`, `ramadan_bonus`, `ramadan_bonus_eligibility`, `uniform`, `health_care_insurance`, `day_off_per_week`, `working_hrs_per_week`, `emergency_leave`, `birthday_leave`, `public_holiday_per_year`, `paid_seak_leave_per_year`, `paid_companssionate_leave_per_year`, `paid_maternity_leave_per_year`, `paid_paternity_leave_per_year`, `paid_worked_public_holiday_and_friday`, `relocation_ticket`, `max_excess_luggage_relocation_expense`, `ticket_upon_termination`, `meals_per_day`, `accommodation_status`, `furniture_and_fixtures`, `housekeeping`, `linen`, `laundry`, `internet_access`, `telephone`, `annual_leave`, `annual_leave_ticket`, `rest_and_relaxation_leave_per_year`, `no_of_r_and_r_leave`, `total_rest_and_relaxation_leave_per_year`, `rest_and_relaxation_allowance`, `paid_circumcision_leave_per_year`, `overtime`, `salary_paid_in`, `loan_and_salary_advanced`, `sports_and_entertainment_facilities`, `free_return_flight_to_male_per_year`, `food_and_beverages_discount`, `alchoholic_beverages_discount`, `spa_discount`, `dive_center_discount`, `water_sports_discount`, `friends_with_benefit_discount`, `standard_staff_rate_for_single`, `standard_staff_rate_for_double`, `staff_rate_for_seaplane_male`, `male_subsistence_allowance`, `custom_fields`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(47, 26, '6', '6', 'single', '11/19/2025', 'monthly', 0, 3000, NULL, 'yes', 'yes', 1, 6, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'no', 0, 'yes', 6, 'Four Share', 'yes', 'once a week', 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket', 'once a week', 'yes', 'no', NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'yes', 'USD', 'yes', 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments', 2, 50, 50, 25, 25, 25, 20, 100, 200, 40, NULL, '[]', 'active', 240, 259, '2025-11-13 11:11:32', '2025-11-19 10:26:13'),
(48, 26, '5', '5', 'single', '11/13/2025', 'monthly', 0, 3000, NULL, 'yes', 'yes', 1, 6, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'no', NULL, 'yes', 6, 'Four Share', 'yes', 'twice a week', 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket', 'twice a week', 'yes', 'no', NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'yes', 'USD', 'yes', 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments', 2, 50, 50, 25, 25, 25, 20, 100, 200, 40, NULL, '[]', 'active', 240, 240, '2025-11-13 11:16:05', '2025-11-13 11:16:05'),
(49, 26, '4', '4', 'single', '11/13/2025', 'monthly', 0, 3000, NULL, 'yes', 'yes', 1, 6, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'no', 0, 'yes', 6, 'Double Share', 'yes', 'twice a week', 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket', 'twice a week', 'yes', 'yes', NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'n/a', 'USD', 'yes', 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments', 2, 50, 50, 25, 25, 25, 20, 100, 200, 40, NULL, '[]', 'active', 240, 240, '2025-11-13 11:20:13', '2025-11-13 11:20:13'),
(50, 26, '2', '2', 'married', '11/13/2025', 'monthly', 0, 3000, NULL, 'yes', 'yes', 1, 6, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'yes', 200, 'yes', 6, 'Single Share', 'yes', '3 a week', 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket', '3 a week', 'yes', 'yes', NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'n/a', 'USD', '', 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments', 4, 50, 50, 25, 25, 25, 20, 100, 300, 40, NULL, '[]', 'active', 240, 240, '2025-11-13 11:24:17', '2025-11-13 11:24:17'),
(51, 26, '1', '1,3,7,8', 'married', '11/13/2025', 'monthly', 0, 3000, NULL, 'yes', 'yes', 1, 6, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'yes', 300, 'yes', 6, 'Single Share', 'yes', '3 a week', 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket', '3 a week', 'yes', 'yes', NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'n/a', 'USD', '', 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments', 2, 50, 50, 25, 25, 25, 20, 100, 200, 40, NULL, '[]', 'active', 240, 240, '2025-11-13 11:28:22', '2025-11-13 11:28:22'),
(52, 26, '8', '6', 'married', '11/13/2025', 'monthly', 0, 3000, NULL, 'yes', 'yes', 1, 6, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'yes', 300, 'yes', 6, 'Single Share', 'yes', '3 a week', 'Bed sheet & pillow cover,Bath towel,Bath mat,Bedsheet,Blanket', '3 a week', 'yes', 'yes', NULL, 'yes', NULL, NULL, NULL, NULL, NULL, 'n/a', 'USD', '', 'Billiard,Football,Volleyball,Fishing trips,Table tennis,Beach access,Karaoke,Staff gym,Outdoor cinema,Fifa & PubG tournaments', 4, 50, 50, 25, 25, 25, 20, 100, 200, 40, NULL, '[]', 'active', 240, 240, '2025-11-13 11:30:50', '2025-11-13 11:30:50');

-- --------------------------------------------------------

--
-- Table structure for table `resort_budget_costs`
--

DROP TABLE IF EXISTS `resort_budget_costs`;
CREATE TABLE IF NOT EXISTS `resort_budget_costs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `cost_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `particulars` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `amount_unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_payroll_allowance` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_budget_costs_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_budget_costs`
--

INSERT INTO `resort_budget_costs` (`id`, `resort_id`, `cost_title`, `particulars`, `amount`, `amount_unit`, `cost_type`, `frequency`, `details`, `status`, `is_payroll_allowance`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(163, 26, 'Operational Cost', 'Language Allowance', 50.00, 'USD', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:05:08', '2025-11-13 14:05:08'),
(164, 26, 'Operational Cost', 'Male - Based / Airport Based Allowance', 300.00, 'USD', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:05:44', '2025-11-13 14:05:44'),
(165, 26, 'Operational Cost', 'Ramadan Bonus', 3000.00, 'MVR', 'Fixed', 'Year', 'Muslim Only', 'active', 0, 240, 240, '2025-11-13 14:06:30', '2025-11-13 14:06:30'),
(166, 26, 'Operational Cost', 'Fire Brigade Allowance', 50.00, 'USD', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:07:03', '2025-11-13 14:07:03'),
(167, 26, 'Operational Cost', 'Food Cost', 6.00, 'USD', 'Fixed', 'Daily', 'Both', 'active', 0, 240, 240, '2025-11-13 14:07:33', '2025-11-13 14:07:33'),
(168, 26, 'Operational Cost', 'Pension - Employer Contibution', 7.00, '%', 'Fixed', 'Month', 'Locals Only', 'active', 1, 240, 240, '2025-11-13 14:08:20', '2025-11-13 14:08:20'),
(169, 26, 'Operational Cost', 'Ovetime - Holiday', 1.50, '%', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:09:39', '2025-11-13 14:09:39'),
(170, 26, 'Operational Cost', 'Overtime - Normal', 1.25, '%', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:10:07', '2025-11-13 14:10:07'),
(171, 26, 'Operational Cost', 'R And R Allowance', 42.00, 'USD', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:10:38', '2025-11-13 14:10:38'),
(172, 26, 'Operational Cost', 'R And R Allowance - Excom', 125.00, 'USD', 'Fixed', 'Month', 'Both', 'active', 1, 240, 240, '2025-11-13 14:11:10', '2025-11-13 14:11:10'),
(173, 26, 'Operational Cost', 'Ticket - Annual Leave', 700.00, 'USD', 'Variable', 'Year', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:11:56', '2025-11-13 14:11:56'),
(174, 26, 'Operational Cost', 'Ticket - Annual Leave - Local', 400.00, 'USD', 'Variable', 'Year', 'Locals Only', 'active', 0, 240, 259, '2025-11-13 14:12:34', '2025-12-07 21:00:39'),
(175, 26, 'Operational Cost', 'Medical Insurance - International', 3000.00, 'USD', 'Variable', 'Year', 'Both', 'active', 0, 240, 240, '2025-11-13 14:13:10', '2025-11-13 14:13:10'),
(176, 26, 'Operational Cost', 'Work Permit Fee', 350.00, 'MVR', 'Fixed', 'Month', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:13:40', '2025-11-13 14:13:40'),
(177, 26, 'Operational Cost', 'Medical Insurance', 150.00, 'USD', 'Variable', 'Year', 'Both', 'active', 0, 240, 240, '2025-11-13 14:14:15', '2025-11-13 14:14:15'),
(178, 26, 'Recruitment Cost', 'Relocation / Luggage Allowance', 100.00, 'USD', 'Variable', 'One time cost', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:15:05', '2025-11-13 14:15:05'),
(179, 26, 'Recruitment Cost', 'Accomodation In Male City', 100.00, 'USD', 'Variable', 'One time cost', 'Both', 'active', 0, 240, 240, '2025-11-13 14:15:32', '2025-11-13 14:15:32'),
(180, 26, 'Recruitment Cost', 'Work Visa Medical Test', 50.00, 'USD', 'Fixed', 'Year', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:16:27', '2025-11-13 14:16:27'),
(181, 26, 'Recruitment Cost', 'Seaplane And Boat - Arrival', 40.00, 'USD', 'Fixed', 'One time cost', 'Both', 'active', 0, 240, 240, '2025-11-13 14:17:08', '2025-11-13 14:17:08'),
(182, 26, 'Recruitment Cost', 'Expat Insurance', 50.00, 'USD', 'Fixed', 'Year', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:17:31', '2025-11-13 14:17:31'),
(183, 26, 'Recruitment Cost', 'Recrutment Fee', 50.00, 'USD', 'Variable', 'One time cost', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:18:11', '2025-11-13 14:18:11'),
(184, 26, 'Recruitment Cost', 'Arrival Ticket - Relocation/Arrival', 700.00, 'USD', 'Variable', 'One time cost', 'Both', 'active', 0, 240, 240, '2025-11-13 14:18:37', '2025-11-13 14:18:37'),
(185, 26, 'Recruitment Cost', 'Quota Slot Payment', 2000.00, 'MVR', 'Fixed', 'Year', 'Xpat Only', 'active', 0, 240, 240, '2025-11-13 14:19:04', '2025-11-13 14:19:04');

-- --------------------------------------------------------

--
-- Table structure for table `resort_deductions`
--

DROP TABLE IF EXISTS `resort_deductions`;
CREATE TABLE IF NOT EXISTS `resort_deductions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `deduction_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deduction_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maximum_limit` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_deductions_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_departments`
--

DROP TABLE IF EXISTS `resort_departments`;
CREATE TABLE IF NOT EXISTS `resort_departments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `division_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_departments_division_id_foreign` (`division_id`),
  KEY `resort_departments_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_departments`
--

INSERT INTO `resort_departments` (`id`, `resort_id`, `division_id`, `name`, `code`, `short_name`, `status`, `slug`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(75, 25, 72, 'Human Resources', 'HR', 'HR', 'active', 'human-resources', 233, 233, '2025-10-30 11:51:46', '2025-10-30 11:51:46'),
(77, 25, 75, 'Management—Service', 'MGS', 'MG Service', 'active', 'management-service', 233, 233, '2025-11-06 15:26:18', '2025-11-06 15:26:18'),
(78, 26, 76, 'Human Resources', 'HR_1', 'HR', 'active', 'human-resources-1', 240, 240, '2025-11-13 13:36:09', '2025-11-13 13:36:09'),
(79, 26, 76, 'Accounting', 'Acc_1', 'Acc', 'active', 'accounting', 240, 240, '2025-11-13 13:37:03', '2025-11-13 13:37:03'),
(80, 26, 77, 'F and B Service', 'F and B_1', 'FB', 'active', 'f-and-b-service', 240, 240, '2025-11-13 13:38:56', '2025-11-13 13:38:56'),
(81, 26, 76, 'Executive Office', 'EO_1', 'EO', 'active', 'executive-office', 240, 240, '2025-11-13 13:42:36', '2025-11-13 13:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `resort_divisions`
--

DROP TABLE IF EXISTS `resort_divisions`;
CREATE TABLE IF NOT EXISTS `resort_divisions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_divisions`
--

INSERT INTO `resort_divisions` (`id`, `resort_id`, `name`, `code`, `short_name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`, `slug`) VALUES
(72, 25, 'Administrative and General', 'AD&GE', 'Admin General', 'active', 233, 233, '2025-10-30 11:51:06', '2025-10-30 11:51:06', 'administrative-and-general'),
(75, 25, 'Food and Beverage', 'FB', 'F&B', 'active', 233, 233, '2025-11-06 15:24:52', '2025-11-06 15:24:52', 'food-and-beverage'),
(76, 26, 'Administrative and General', 'Admin_1', 'A&G', 'active', 240, 240, '2025-11-13 13:34:28', '2025-11-13 13:34:28', 'administrative-and-general-1'),
(77, 26, 'Food and Beverage', 'F&B_1', 'F&B', 'active', 240, 240, '2025-11-13 13:34:56', '2025-11-13 13:34:56', 'food-and-beverage-1');

-- --------------------------------------------------------

--
-- Table structure for table `resort_earnings`
--

DROP TABLE IF EXISTS `resort_earnings`;
CREATE TABLE IF NOT EXISTS `resort_earnings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `allow_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allow_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_earnings_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_employee_budget_cost_configurations`
--

DROP TABLE IF EXISTS `resort_employee_budget_cost_configurations`;
CREATE TABLE IF NOT EXISTS `resort_employee_budget_cost_configurations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `resort_budget_cost_id` int UNSIGNED NOT NULL,
  `value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `department_id` int UNSIGNED NOT NULL,
  `position_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `year` int DEFAULT NULL,
  `month` int DEFAULT NULL COMMENT 'Month (1-12)',
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `current_salary` decimal(15,2) DEFAULT NULL,
  `hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Hours for percentage-based calculations like overtime',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rebcc_resort_cost_fk` (`resort_budget_cost_id`),
  KEY `rebcc_emp_resort_year_idx` (`employee_id`,`resort_id`,`year`),
  KEY `rebcc_emp_resort_year_month_idx` (`employee_id`,`resort_id`,`year`,`month`)
) ENGINE=MyISAM AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_employee_budget_cost_configurations`
--

INSERT INTO `resort_employee_budget_cost_configurations` (`id`, `employee_id`, `resort_budget_cost_id`, `value`, `currency`, `department_id`, `position_id`, `resort_id`, `year`, `month`, `basic_salary`, `current_salary`, `hours`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(6, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(5, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(7, 184, 169, 232.86, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 70.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(8, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(9, 184, 173, 700.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(10, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(11, 184, 177, 150.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(12, 184, 180, 50.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(13, 184, 181, 40.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(14, 184, 182, 50.00, 'USD', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(15, 184, 185, 11.28, 'MVR', 78, 146, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:44:51', '2025-12-07 21:44:51'),
(96, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:55:00', '2025-12-07 21:55:00'),
(27, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:08', '2025-12-07 21:47:08'),
(34, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:35', '2025-12-07 21:47:35'),
(41, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:48:44', '2025-12-07 21:48:44'),
(48, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:49:09', '2025-12-07 21:49:09'),
(95, 184, 170, 6.14, 'USD', 78, 146, 26, 2026, 2, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:55:00', '2025-12-07 21:55:00'),
(94, 184, 169, 147.32, 'USD', 78, 146, 26, 2026, 2, NULL, NULL, 40.00, 259, 259, '2025-12-07 21:55:00', '2025-12-07 21:55:00'),
(93, 184, 167, 168.00, 'USD', 78, 146, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:55:00', '2025-12-07 21:55:00'),
(92, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:55:00', '2025-12-07 21:55:00'),
(55, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:07', '2025-12-07 21:50:07'),
(28, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:08', '2025-12-07 21:47:08'),
(29, 184, 169, 166.33, 'USD', 78, 146, 26, 2026, 3, NULL, NULL, 50.00, 259, 259, '2025-12-07 21:47:08', '2025-12-07 21:47:08'),
(30, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 3, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:47:08', '2025-12-07 21:47:08'),
(31, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:08', '2025-12-07 21:47:08'),
(32, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:08', '2025-12-07 21:47:08'),
(62, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:36', '2025-12-07 21:50:36'),
(35, 184, 167, 180.00, 'USD', 78, 146, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:35', '2025-12-07 21:47:35'),
(36, 184, 169, 137.50, 'USD', 78, 146, 26, 2026, 4, NULL, NULL, 40.00, 259, 259, '2025-12-07 21:47:35', '2025-12-07 21:47:35'),
(37, 184, 170, 5.73, 'USD', 78, 146, 26, 2026, 4, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:47:35', '2025-12-07 21:47:35'),
(38, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:35', '2025-12-07 21:47:35'),
(39, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:47:35', '2025-12-07 21:47:35'),
(68, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:18', '2025-12-07 21:51:18'),
(42, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:48:44', '2025-12-07 21:48:44'),
(43, 184, 169, 266.13, 'USD', 78, 146, 26, 2026, 5, NULL, NULL, 80.00, 259, 259, '2025-12-07 21:48:44', '2025-12-07 21:48:44'),
(44, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 5, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:48:44', '2025-12-07 21:48:44'),
(45, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:48:44', '2025-12-07 21:48:44'),
(46, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:48:44', '2025-12-07 21:48:44'),
(74, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:53', '2025-12-07 21:51:53'),
(49, 184, 167, 180.00, 'USD', 78, 146, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:49:09', '2025-12-07 21:49:09'),
(50, 184, 169, 171.88, 'USD', 78, 146, 26, 2026, 6, NULL, NULL, 50.00, 259, 259, '2025-12-07 21:49:09', '2025-12-07 21:49:09'),
(51, 184, 170, 5.73, 'USD', 78, 146, 26, 2026, 6, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:49:09', '2025-12-07 21:49:09'),
(52, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:49:09', '2025-12-07 21:49:09'),
(53, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:49:09', '2025-12-07 21:49:09'),
(80, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:52:15', '2025-12-07 21:52:15'),
(56, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:07', '2025-12-07 21:50:07'),
(57, 184, 169, 166.33, 'USD', 78, 146, 26, 2026, 7, NULL, NULL, 50.00, 259, 259, '2025-12-07 21:50:07', '2025-12-07 21:50:07'),
(58, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 7, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:50:07', '2025-12-07 21:50:07'),
(59, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:07', '2025-12-07 21:50:07'),
(60, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:07', '2025-12-07 21:50:07'),
(86, 184, 164, 300.00, 'USD', 78, 146, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:53:01', '2025-12-07 21:53:01'),
(63, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:36', '2025-12-07 21:50:36'),
(64, 184, 169, 133.06, 'USD', 78, 146, 26, 2026, 8, NULL, NULL, 40.00, 259, 259, '2025-12-07 21:50:36', '2025-12-07 21:50:36'),
(65, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 8, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:50:36', '2025-12-07 21:50:36'),
(66, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:36', '2025-12-07 21:50:36'),
(67, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:50:36', '2025-12-07 21:50:36'),
(69, 184, 167, 180.00, 'USD', 78, 146, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:18', '2025-12-07 21:51:18'),
(70, 184, 169, 171.88, 'USD', 78, 146, 26, 2026, 9, NULL, NULL, 50.00, 259, 259, '2025-12-07 21:51:18', '2025-12-07 21:51:18'),
(71, 184, 170, 5.73, 'USD', 78, 146, 26, 2026, 9, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:51:18', '2025-12-07 21:51:18'),
(72, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:18', '2025-12-07 21:51:18'),
(73, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:18', '2025-12-07 21:51:18'),
(75, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:53', '2025-12-07 21:51:53'),
(76, 184, 169, 166.33, 'USD', 78, 146, 26, 2026, 10, NULL, NULL, 50.00, 259, 259, '2025-12-07 21:51:53', '2025-12-07 21:51:53'),
(77, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 10, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:51:53', '2025-12-07 21:51:53'),
(78, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:53', '2025-12-07 21:51:53'),
(79, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:51:53', '2025-12-07 21:51:53'),
(81, 184, 167, 180.00, 'USD', 78, 146, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:52:15', '2025-12-07 21:52:15'),
(82, 184, 169, 206.25, 'USD', 78, 146, 26, 2026, 11, NULL, NULL, 60.00, 259, 259, '2025-12-07 21:52:15', '2025-12-07 21:52:15'),
(83, 184, 170, 5.73, 'USD', 78, 146, 26, 2026, 11, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:52:15', '2025-12-07 21:52:15'),
(84, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:52:15', '2025-12-07 21:52:15'),
(85, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:52:15', '2025-12-07 21:52:15'),
(87, 184, 167, 186.00, 'USD', 78, 146, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:53:01', '2025-12-07 21:53:01'),
(88, 184, 169, 133.06, 'USD', 78, 146, 26, 2026, 12, NULL, NULL, 40.00, 259, 259, '2025-12-07 21:53:01', '2025-12-07 21:53:01'),
(89, 184, 170, 5.54, 'USD', 78, 146, 26, 2026, 12, NULL, NULL, 2.00, 259, 259, '2025-12-07 21:53:01', '2025-12-07 21:53:01'),
(90, 184, 176, 22.70, 'MVR', 78, 146, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:53:01', '2025-12-07 21:53:01'),
(91, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:53:01', '2025-12-07 21:53:01'),
(97, 184, 185, 10.77, 'MVR', 78, 146, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:55:00', '2025-12-07 21:55:00'),
(123, 188, 183, 50.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(122, 188, 182, 50.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(121, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(120, 188, 180, 50.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(119, 188, 178, 100.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(118, 188, 177, 150.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(117, 188, 176, 22.70, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(116, 188, 175, 3000.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(115, 188, 173, 700.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(114, 188, 172, 1200.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(113, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(109, 188, 167, 168.00, 'USD', 78, 147, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:16', '2025-12-07 21:59:16'),
(110, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:16', '2025-12-07 21:59:16'),
(111, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:16', '2025-12-07 21:59:16'),
(112, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 2, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:16', '2025-12-07 21:59:16'),
(124, 188, 185, 11.28, 'USD', 78, 147, 26, 2026, 1, NULL, NULL, 0.00, 259, 259, '2025-12-07 21:59:33', '2025-12-07 21:59:33'),
(166, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:16:01', '2025-12-07 22:16:01'),
(165, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:16:01', '2025-12-07 22:16:01'),
(164, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:16:01', '2025-12-07 22:16:01'),
(128, 188, 167, 180.00, 'USD', 78, 147, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:01:04', '2025-12-07 22:01:04'),
(129, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:01:04', '2025-12-07 22:01:04'),
(130, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:01:04', '2025-12-07 22:01:04'),
(131, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 4, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:01:04', '2025-12-07 22:01:04'),
(132, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:00', '2025-12-07 22:02:00'),
(133, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:00', '2025-12-07 22:02:00'),
(134, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:00', '2025-12-07 22:02:00'),
(135, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 5, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:00', '2025-12-07 22:02:00'),
(136, 188, 167, 180.00, 'USD', 78, 147, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:42', '2025-12-07 22:02:42'),
(137, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:42', '2025-12-07 22:02:42'),
(138, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:42', '2025-12-07 22:02:42'),
(139, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 6, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:02:42', '2025-12-07 22:02:42'),
(140, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:04:49', '2025-12-07 22:04:49'),
(141, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:04:49', '2025-12-07 22:04:49'),
(142, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:04:49', '2025-12-07 22:04:49'),
(143, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 7, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:04:49', '2025-12-07 22:04:49'),
(144, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:10:12', '2025-12-07 22:10:12'),
(145, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:10:12', '2025-12-07 22:10:12'),
(146, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:10:12', '2025-12-07 22:10:12'),
(147, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 8, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:10:12', '2025-12-07 22:10:12'),
(148, 188, 167, 180.00, 'USD', 78, 147, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:01', '2025-12-07 22:11:01'),
(149, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:01', '2025-12-07 22:11:01'),
(150, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:01', '2025-12-07 22:11:01'),
(151, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 9, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:01', '2025-12-07 22:11:01'),
(152, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:37', '2025-12-07 22:11:37'),
(153, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:37', '2025-12-07 22:11:37'),
(154, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:37', '2025-12-07 22:11:37'),
(155, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 10, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:11:37', '2025-12-07 22:11:37'),
(156, 188, 167, 180.00, 'USD', 78, 147, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:04', '2025-12-07 22:12:04'),
(157, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:04', '2025-12-07 22:12:04'),
(158, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:04', '2025-12-07 22:12:04'),
(159, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 11, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:04', '2025-12-07 22:12:04'),
(160, 188, 167, 186.00, 'USD', 78, 147, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:46', '2025-12-07 22:12:46'),
(161, 188, 176, 22.70, 'MVR', 78, 147, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:46', '2025-12-07 22:12:46'),
(162, 188, 181, 40.00, 'USD', 78, 147, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:46', '2025-12-07 22:12:46'),
(163, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 12, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:12:46', '2025-12-07 22:12:46'),
(167, 188, 185, 10.77, 'MVR', 78, 147, 26, 2026, 3, NULL, NULL, 0.00, 259, 259, '2025-12-07 22:16:01', '2025-12-07 22:16:01');

-- --------------------------------------------------------

--
-- Table structure for table `resort_geo_locations`
--

DROP TABLE IF EXISTS `resort_geo_locations`;
CREATE TABLE IF NOT EXISTS `resort_geo_locations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_geo_locations_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_interal_pages_permissions`
--

DROP TABLE IF EXISTS `resort_interal_pages_permissions`;
CREATE TABLE IF NOT EXISTS `resort_interal_pages_permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `Dept_id` int UNSIGNED NOT NULL,
  `position_id` int UNSIGNED NOT NULL,
  `Permission_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `page_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_interal_pages_permissions_dept_id_foreign` (`Dept_id`),
  KEY `resort_interal_pages_permissions_page_id_foreign` (`page_id`),
  KEY `resort_interal_pages_permissions_position_id_foreign` (`position_id`),
  KEY `resort_interal_pages_permissions_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=75010 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_interal_pages_permissions`
--

INSERT INTO `resort_interal_pages_permissions` (`id`, `resort_id`, `Dept_id`, `position_id`, `Permission_id`, `created_at`, `updated_at`, `page_id`) VALUES
(69011, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 2),
(69012, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 2),
(69013, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 2),
(69014, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 2),
(69015, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 3),
(69016, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 3),
(69017, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 3),
(69018, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 3),
(69019, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 4),
(69020, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 4),
(69021, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 4),
(69022, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 4),
(69023, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 5),
(69024, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 5),
(69025, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 5),
(69026, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 5),
(69027, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 6),
(69028, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 6),
(69029, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 6),
(69030, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 6),
(69031, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 7),
(69032, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 7),
(69033, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 7),
(69034, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 7),
(69035, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 14),
(69036, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 14),
(69037, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 14),
(69038, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 14),
(69039, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 15),
(69040, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 15),
(69041, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 15),
(69042, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 15),
(69043, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 51),
(69044, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 51),
(69045, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 51),
(69046, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 51),
(69047, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 52),
(69048, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 52),
(69049, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 52),
(69050, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 52),
(69051, 25, 75, 139, 1, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 53),
(69052, 25, 75, 139, 2, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 53),
(69053, 25, 75, 139, 3, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 53),
(69054, 25, 75, 139, 4, '2025-11-01 09:54:49', '2025-11-01 09:54:49', 53),
(69055, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 54),
(69056, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 54),
(69057, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 54),
(69058, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 54),
(69059, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 55),
(69060, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 55),
(69061, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 55),
(69062, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 55),
(69063, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 56),
(69064, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 56),
(69065, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 56),
(69066, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 56),
(69067, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 57),
(69068, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 57),
(69069, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 57),
(69070, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 57),
(69071, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 115),
(69072, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 115),
(69073, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 115),
(69074, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 115),
(69075, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 9),
(69076, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 9),
(69077, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 9),
(69078, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 9),
(69079, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 10),
(69080, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 10),
(69081, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 10),
(69082, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 10),
(69083, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 11),
(69084, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 11),
(69085, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 11),
(69086, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 11),
(69087, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 12),
(69088, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 12),
(69089, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 12),
(69090, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 12),
(69091, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 13),
(69092, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 13),
(69093, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 13),
(69094, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 13),
(69095, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 18),
(69096, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 18),
(69097, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 18),
(69098, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 18),
(69099, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 19),
(69100, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 19),
(69101, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 19),
(69102, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 19),
(69103, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 20),
(69104, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 20),
(69105, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 20),
(69106, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 20),
(69107, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 21),
(69108, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 21),
(69109, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 21),
(69110, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 21),
(69111, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 22),
(69112, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 22),
(69113, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 22),
(69114, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 22),
(69115, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 82),
(69116, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 82),
(69117, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 82),
(69118, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 82),
(69119, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 84),
(69120, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 84),
(69121, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 84),
(69122, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 84),
(69123, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 85),
(69124, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 85),
(69125, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 85),
(69126, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 85),
(69127, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 86),
(69128, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 86),
(69129, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 86),
(69130, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 86),
(69131, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 87),
(69132, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 87),
(69133, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 87),
(69134, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 87),
(69135, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 88),
(69136, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 88),
(69137, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 88),
(69138, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 88),
(69139, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 89),
(69140, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 89),
(69141, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 89),
(69142, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 89),
(69143, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 90),
(69144, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 90),
(69145, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 90),
(69146, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 90),
(69147, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 92),
(69148, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 92),
(69149, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 92),
(69150, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 92),
(69151, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 93),
(69152, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 93),
(69153, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 93),
(69154, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 93),
(69155, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 94),
(69156, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 94),
(69157, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 94),
(69158, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 94),
(69159, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 95),
(69160, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 95),
(69161, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 95),
(69162, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 95),
(69163, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 96),
(69164, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 96),
(69165, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 96),
(69166, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 96),
(69167, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 97),
(69168, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 97),
(69169, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 97),
(69170, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 97),
(69171, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 98),
(69172, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 98),
(69173, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 98),
(69174, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 98),
(69175, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 99),
(69176, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 99),
(69177, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 99),
(69178, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 99),
(69179, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 116),
(69180, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 116),
(69181, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 116),
(69182, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 116),
(69183, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 117),
(69184, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 117),
(69185, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 117),
(69186, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 117),
(69187, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 17),
(69188, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 17),
(69189, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 17),
(69190, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 17),
(69191, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 23),
(69192, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 23),
(69193, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 23),
(69194, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 23),
(69195, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 24),
(69196, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 24),
(69197, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 24),
(69198, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 24),
(69199, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 25),
(69200, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 25),
(69201, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 25),
(69202, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 25),
(69203, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 26),
(69204, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 26),
(69205, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 26),
(69206, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 26),
(69207, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 27),
(69208, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 27),
(69209, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 27),
(69210, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 27),
(69211, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 28),
(69212, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 28),
(69213, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 28),
(69214, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 28),
(69215, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 29),
(69216, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 29),
(69217, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 29),
(69218, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 29),
(69219, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 45),
(69220, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 45),
(69221, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 45),
(69222, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 45),
(69223, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 46),
(69224, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 46),
(69225, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 46),
(69226, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 46),
(69227, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 47),
(69228, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 47),
(69229, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 47),
(69230, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 47),
(69231, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 48),
(69232, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 48),
(69233, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 48),
(69234, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 48),
(69235, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 49),
(69236, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 49),
(69237, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 49),
(69238, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 49),
(69239, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 50),
(69240, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 50),
(69241, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 50),
(69242, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 50),
(69243, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 81),
(69244, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 81),
(69245, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 81),
(69246, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 81),
(69247, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 41),
(69248, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 41),
(69249, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 41),
(69250, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 41),
(69251, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 42),
(69252, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 42),
(69253, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 42),
(69254, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 42),
(69255, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 43),
(69256, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 43),
(69257, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 43),
(69258, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 43),
(69259, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 44),
(69260, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 44),
(69261, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 44),
(69262, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 44),
(69263, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 79),
(69264, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 79),
(69265, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 79),
(69266, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 79),
(69267, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 16),
(69268, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 16),
(69269, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 16),
(69270, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 16),
(69271, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 58),
(69272, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 58),
(69273, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 58),
(69274, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 58),
(69275, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 59),
(69276, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 59),
(69277, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 59),
(69278, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 59),
(69279, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 60),
(69280, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 60),
(69281, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 60),
(69282, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 60),
(69283, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 61),
(69284, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 61),
(69285, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 61),
(69286, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 61),
(69287, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 69),
(69288, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 69),
(69289, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 69),
(69290, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 69),
(69291, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 112),
(69292, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 112),
(69293, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 112),
(69294, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 112),
(69295, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 113),
(69296, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 113),
(69297, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 113),
(69298, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 113),
(69299, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 114),
(69300, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 114),
(69301, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 114),
(69302, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 114),
(69303, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 30),
(69304, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 30),
(69305, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 30),
(69306, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 30),
(69307, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 31),
(69308, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 31),
(69309, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 31),
(69310, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 31),
(69311, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 32),
(69312, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 32),
(69313, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 32),
(69314, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 32),
(69315, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 33),
(69316, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 33),
(69317, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 33),
(69318, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 33),
(69319, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 34),
(69320, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 34),
(69321, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 34),
(69322, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 34),
(69323, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 35),
(69324, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 35),
(69325, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 35),
(69326, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 35),
(69327, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 36),
(69328, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 36),
(69329, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 36),
(69330, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 36),
(69331, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 37),
(69332, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 37),
(69333, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 37),
(69334, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 37),
(69335, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 38),
(69336, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 38),
(69337, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 38),
(69338, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 38),
(69339, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 39),
(69340, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 39),
(69341, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 39),
(69342, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 39),
(69343, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 40),
(69344, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 40),
(69345, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 40),
(69346, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 40),
(69347, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 67),
(69348, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 67),
(69349, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 67),
(69350, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 67),
(69351, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 70),
(69352, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 70),
(69353, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 70),
(69354, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 70),
(69355, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 71),
(69356, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 71),
(69357, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 71),
(69358, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 71),
(69359, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 72),
(69360, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 72),
(69361, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 72),
(69362, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 72),
(69363, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 73),
(69364, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 73),
(69365, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 73),
(69366, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 73),
(69367, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 74),
(69368, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 74),
(69369, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 74),
(69370, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 74),
(69371, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 65),
(69372, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 65),
(69373, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 65),
(69374, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 65),
(69375, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 66),
(69376, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 66),
(69377, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 66),
(69378, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 66),
(69379, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 91),
(69380, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 91),
(69381, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 91),
(69382, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 91),
(69383, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 1),
(69384, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 1),
(69385, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 1),
(69386, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 1),
(69387, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 100),
(69388, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 100),
(69389, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 100),
(69390, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 100),
(69391, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 101),
(69392, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 101),
(69393, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 101),
(69394, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 101),
(69395, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 102),
(69396, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 102),
(69397, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 102),
(69398, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 102),
(69399, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 103),
(69400, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 103),
(69401, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 103),
(69402, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 103),
(69403, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 104),
(69404, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 104),
(69405, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 104),
(69406, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 104),
(69407, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 105),
(69408, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 105),
(69409, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 105),
(69410, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 105),
(69411, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 106),
(69412, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 106),
(69413, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 106),
(69414, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 106),
(69415, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 107),
(69416, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 107),
(69417, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 107),
(69418, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 107),
(69419, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 108),
(69420, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 108),
(69421, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 108),
(69422, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 108),
(69423, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 109),
(69424, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 109),
(69425, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 109),
(69426, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 109),
(69427, 25, 75, 139, 1, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 110),
(69428, 25, 75, 139, 2, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 110),
(69429, 25, 75, 139, 3, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 110),
(69430, 25, 75, 139, 4, '2025-11-01 09:54:50', '2025-11-01 09:54:50', 110),
(69439, 25, 77, 141, 1, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 2),
(69440, 25, 77, 141, 2, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 2),
(69441, 25, 77, 141, 3, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 2),
(69442, 25, 77, 141, 4, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 2),
(69443, 25, 77, 141, 1, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 3),
(69444, 25, 77, 141, 2, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 3),
(69445, 25, 77, 141, 3, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 3),
(69446, 25, 77, 141, 4, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 3),
(69447, 25, 77, 141, 1, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 4),
(69448, 25, 77, 141, 2, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 4),
(69449, 25, 77, 141, 3, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 4),
(69450, 25, 77, 141, 4, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 4),
(69451, 25, 77, 141, 1, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 51),
(69452, 25, 77, 141, 2, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 51),
(69453, 25, 77, 141, 3, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 51),
(69454, 25, 77, 141, 4, '2025-11-09 14:41:42', '2025-11-09 14:41:42', 51),
(71272, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 2),
(71273, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 2),
(71274, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 2),
(71275, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 2),
(71276, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 3),
(71277, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 3),
(71278, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 3),
(71279, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 3),
(71280, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 4),
(71281, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 4),
(71282, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 4),
(71283, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 4),
(71284, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 14),
(71285, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 14),
(71286, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 14),
(71287, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 14),
(71288, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 15),
(71289, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 15),
(71290, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 15),
(71291, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 15),
(71292, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 9),
(71293, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 9),
(71294, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 9),
(71295, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 9),
(71296, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 12),
(71297, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 12),
(71298, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 12),
(71299, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 12),
(71300, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 11),
(71301, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 11),
(71302, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 11),
(71303, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 11),
(71304, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 22),
(71305, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 22),
(71306, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 22),
(71307, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 22),
(71308, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 17),
(71309, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 17),
(71310, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 17),
(71311, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 17),
(71312, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 25),
(71313, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 25),
(71314, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 25),
(71315, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 25),
(71316, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 27),
(71317, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 27),
(71318, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 27),
(71319, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 27),
(71320, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 29),
(71321, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 29),
(71322, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 29),
(71323, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 29),
(71324, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 31),
(71325, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 31),
(71326, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 31),
(71327, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 31),
(71328, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 67),
(71329, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 67),
(71330, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 67),
(71331, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 67),
(71332, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 51),
(71333, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 51),
(71334, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 51),
(71335, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 51),
(71336, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 53),
(71337, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 53),
(71338, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 53),
(71339, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 53),
(71340, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 54),
(71341, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 54),
(71342, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 54),
(71343, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 54),
(71344, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 55),
(71345, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 55),
(71346, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 55),
(71347, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 55),
(71348, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 56),
(71349, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 56),
(71350, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 56),
(71351, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 56),
(71352, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 115),
(71353, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 115),
(71354, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 115),
(71355, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 115),
(71356, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 99),
(71357, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 99),
(71358, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 99),
(71359, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 99),
(71360, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 46),
(71361, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 46),
(71362, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 46),
(71363, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 46),
(71364, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 41),
(71365, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 41),
(71366, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 41),
(71367, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 41),
(71368, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 42),
(71369, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 42),
(71370, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 42),
(71371, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 42),
(71372, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 43),
(71373, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 43),
(71374, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 43),
(71375, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 43),
(71376, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 79),
(71377, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 79),
(71378, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 79),
(71379, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 79),
(71380, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 59),
(71381, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 59),
(71382, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 59),
(71383, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 59),
(71384, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 60),
(71385, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 60),
(71386, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 60),
(71387, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 60),
(71388, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 70),
(71389, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 70),
(71390, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 70),
(71391, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 70),
(71392, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 101),
(71393, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 101),
(71394, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 101),
(71395, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 101),
(71396, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 102),
(71397, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 102),
(71398, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 102),
(71399, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 102),
(71400, 26, 79, 144, 1, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 110),
(71401, 26, 79, 144, 2, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 110),
(71402, 26, 79, 144, 3, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 110),
(71403, 26, 79, 144, 4, '2025-11-14 16:35:26', '2025-11-14 16:35:26', 110),
(71404, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 2),
(71405, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 2),
(71406, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 2),
(71407, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 2),
(71408, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 3),
(71409, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 3),
(71410, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 3),
(71411, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 3),
(71412, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 4),
(71413, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 4),
(71414, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 4),
(71415, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 4),
(71416, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 14),
(71417, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 14),
(71418, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 14),
(71419, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 14),
(71420, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 15),
(71421, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 15),
(71422, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 15),
(71423, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 15),
(71424, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 9),
(71425, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 9),
(71426, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 9),
(71427, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 9),
(71428, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 12),
(71429, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 12),
(71430, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 12),
(71431, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 12),
(71432, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 11),
(71433, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 11),
(71434, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 11),
(71435, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 11),
(71436, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 22),
(71437, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 22),
(71438, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 22),
(71439, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 22),
(71440, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 17),
(71441, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 17),
(71442, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 17),
(71443, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 17),
(71444, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 23),
(71445, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 23),
(71446, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 23),
(71447, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 23),
(71448, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 24),
(71449, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 24),
(71450, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 24),
(71451, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 24),
(71452, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 25),
(71453, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 25),
(71454, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 25),
(71455, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 25),
(71456, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 27),
(71457, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 27),
(71458, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 27),
(71459, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 27),
(71460, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 29),
(71461, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 29),
(71462, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 29),
(71463, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 29),
(71464, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 31),
(71465, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 31),
(71466, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 31),
(71467, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 31),
(71468, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 67),
(71469, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 67),
(71470, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 67),
(71471, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 67),
(71472, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 51),
(71473, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 51),
(71474, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 51),
(71475, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 51),
(71476, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 53),
(71477, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 53),
(71478, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 53),
(71479, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 53),
(71480, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 54),
(71481, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 54),
(71482, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 54),
(71483, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 54),
(71484, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 55),
(71485, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 55),
(71486, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 55),
(71487, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 55),
(71488, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 56),
(71489, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 56),
(71490, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 56),
(71491, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 56),
(71492, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 115),
(71493, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 115),
(71494, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 115),
(71495, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 115),
(71496, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 92),
(71497, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 92),
(71498, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 92),
(71499, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 92),
(71500, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 93),
(71501, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 93),
(71502, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 93),
(71503, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 93),
(71504, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 94),
(71505, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 94),
(71506, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 94),
(71507, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 94),
(71508, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 95),
(71509, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 95),
(71510, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 95),
(71511, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 95),
(71512, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 99),
(71513, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 99),
(71514, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 99),
(71515, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 99),
(71516, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 45),
(71517, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 45);
INSERT INTO `resort_interal_pages_permissions` (`id`, `resort_id`, `Dept_id`, `position_id`, `Permission_id`, `created_at`, `updated_at`, `page_id`) VALUES
(71518, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 45),
(71519, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 45),
(71520, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 46),
(71521, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 46),
(71522, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 46),
(71523, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 46),
(71524, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 47),
(71525, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 47),
(71526, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 47),
(71527, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 47),
(71528, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 81),
(71529, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 81),
(71530, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 81),
(71531, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 81),
(71532, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 41),
(71533, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 41),
(71534, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 41),
(71535, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 41),
(71536, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 42),
(71537, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 42),
(71538, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 42),
(71539, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 42),
(71540, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 43),
(71541, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 43),
(71542, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 43),
(71543, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 43),
(71544, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 79),
(71545, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 79),
(71546, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 79),
(71547, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 79),
(71548, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 59),
(71549, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 59),
(71550, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 59),
(71551, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 59),
(71552, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 60),
(71553, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 60),
(71554, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 60),
(71555, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 60),
(71556, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 101),
(71557, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 101),
(71558, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 101),
(71559, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 101),
(71560, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 102),
(71561, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 102),
(71562, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 102),
(71563, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 102),
(71564, 26, 79, 143, 1, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 110),
(71565, 26, 79, 143, 2, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 110),
(71566, 26, 79, 143, 3, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 110),
(71567, 26, 79, 143, 4, '2025-11-15 12:24:58', '2025-11-15 12:24:58', 110),
(71568, 26, 81, 142, 1, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 2),
(71569, 26, 81, 142, 2, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 2),
(71570, 26, 81, 142, 3, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 2),
(71571, 26, 81, 142, 4, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 2),
(71572, 26, 81, 142, 1, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 3),
(71573, 26, 81, 142, 2, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 3),
(71574, 26, 81, 142, 3, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 3),
(71575, 26, 81, 142, 4, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 3),
(71576, 26, 81, 142, 1, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 4),
(71577, 26, 81, 142, 2, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 4),
(71578, 26, 81, 142, 3, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 4),
(71579, 26, 81, 142, 4, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 4),
(71580, 26, 81, 142, 1, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 14),
(71581, 26, 81, 142, 2, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 14),
(71582, 26, 81, 142, 3, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 14),
(71583, 26, 81, 142, 4, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 14),
(71584, 26, 81, 142, 1, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 15),
(71585, 26, 81, 142, 2, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 15),
(71586, 26, 81, 142, 3, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 15),
(71587, 26, 81, 142, 4, '2025-11-18 21:35:29', '2025-11-18 21:35:29', 15),
(71913, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 2),
(71914, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 2),
(71915, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 2),
(71916, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 2),
(71917, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 3),
(71918, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 3),
(71919, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 3),
(71920, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 3),
(71921, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 14),
(71922, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 14),
(71923, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 14),
(71924, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 14),
(71925, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 15),
(71926, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 15),
(71927, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 15),
(71928, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 15),
(71929, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 17),
(71930, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 17),
(71931, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 17),
(71932, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 17),
(71933, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 23),
(71934, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 23),
(71935, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 23),
(71936, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 23),
(71937, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 24),
(71938, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 24),
(71939, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 24),
(71940, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 24),
(71941, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 25),
(71942, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 25),
(71943, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 25),
(71944, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 25),
(71945, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 27),
(71946, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 27),
(71947, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 27),
(71948, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 27),
(71949, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 29),
(71950, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 29),
(71951, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 29),
(71952, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 29),
(71953, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 31),
(71954, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 31),
(71955, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 31),
(71956, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 31),
(71957, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 67),
(71958, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 67),
(71959, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 67),
(71960, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 67),
(71961, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 45),
(71962, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 45),
(71963, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 45),
(71964, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 45),
(71965, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 46),
(71966, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 46),
(71967, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 46),
(71968, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 46),
(71969, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 47),
(71970, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 47),
(71971, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 47),
(71972, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 47),
(71973, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 81),
(71974, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 81),
(71975, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 81),
(71976, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 81),
(71977, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 43),
(71978, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 43),
(71979, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 43),
(71980, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 79),
(71981, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 79),
(71982, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 79),
(71983, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 79),
(71984, 26, 80, 148, 1, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 59),
(71985, 26, 80, 148, 2, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 59),
(71986, 26, 80, 148, 3, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 59),
(71987, 26, 80, 148, 4, '2025-12-17 14:46:06', '2025-12-17 14:46:06', 59),
(74132, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 7),
(74133, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 7),
(74134, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 7),
(74135, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 7),
(74136, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 15),
(74137, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 15),
(74138, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 15),
(74139, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 15),
(74140, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 14),
(74141, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 14),
(74142, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 14),
(74143, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 14),
(74144, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 2),
(74145, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 2),
(74146, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 2),
(74147, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 2),
(74148, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 3),
(74149, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 3),
(74150, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 3),
(74151, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 3),
(74152, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 4),
(74153, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 4),
(74154, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 4),
(74155, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 4),
(74156, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 51),
(74157, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 51),
(74158, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 51),
(74159, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 51),
(74160, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 52),
(74161, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 52),
(74162, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 52),
(74163, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 52),
(74164, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 54),
(74165, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 54),
(74166, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 54),
(74167, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 54),
(74168, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 55),
(74169, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 55),
(74170, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 55),
(74171, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 55),
(74172, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 56),
(74173, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 56),
(74174, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 56),
(74175, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 56),
(74176, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 115),
(74177, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 115),
(74178, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 115),
(74179, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 115),
(74180, 26, 78, 151, 1, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 13),
(74181, 26, 78, 151, 2, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 13),
(74182, 26, 78, 151, 3, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 13),
(74183, 26, 78, 151, 4, '2025-12-24 08:53:07', '2025-12-24 08:53:07', 13),
(74184, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 18),
(74185, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 18),
(74186, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 18),
(74187, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 18),
(74188, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 19),
(74189, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 19),
(74190, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 19),
(74191, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 19),
(74192, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 9),
(74193, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 9),
(74194, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 9),
(74195, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 9),
(74196, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 11),
(74197, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 11),
(74198, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 11),
(74199, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 11),
(74200, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 12),
(74201, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 12),
(74202, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 12),
(74203, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 12),
(74204, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 21),
(74205, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 21),
(74206, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 21),
(74207, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 21),
(74208, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 22),
(74209, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 22),
(74210, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 22),
(74211, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 22),
(74212, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 20),
(74213, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 20),
(74214, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 20),
(74215, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 20),
(74216, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 84),
(74217, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 84),
(74218, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 84),
(74219, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 84),
(74220, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 86),
(74221, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 86),
(74222, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 86),
(74223, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 86),
(74224, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 87),
(74225, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 87),
(74226, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 87),
(74227, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 87),
(74228, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 88),
(74229, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 88),
(74230, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 88),
(74231, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 88),
(74232, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 82),
(74233, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 82),
(74234, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 82),
(74235, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 82),
(74236, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 90),
(74237, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 90),
(74238, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 90),
(74239, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 90),
(74240, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 89),
(74241, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 89),
(74242, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 89),
(74243, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 89),
(74244, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 92),
(74245, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 92),
(74246, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 92),
(74247, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 92),
(74248, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 93),
(74249, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 93),
(74250, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 93),
(74251, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 93),
(74252, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 94),
(74253, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 94),
(74254, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 94),
(74255, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 94),
(74256, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 95),
(74257, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 95),
(74258, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 95),
(74259, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 95),
(74260, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 96),
(74261, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 96),
(74262, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 96),
(74263, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 96),
(74264, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 98),
(74265, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 98),
(74266, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 98),
(74267, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 98),
(74268, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 116),
(74269, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 116),
(74270, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 116),
(74271, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 116),
(74272, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 97),
(74273, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 97),
(74274, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 97),
(74275, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 97),
(74276, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 99),
(74277, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 99),
(74278, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 99),
(74279, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 99),
(74280, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 117),
(74281, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 117),
(74282, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 117),
(74283, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 117),
(74284, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 26),
(74285, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 26),
(74286, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 26),
(74287, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 26),
(74288, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 29),
(74289, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 29),
(74290, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 29),
(74291, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 29),
(74292, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 23),
(74293, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 23),
(74294, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 23),
(74295, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 23),
(74296, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 17),
(74297, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 17),
(74298, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 17),
(74299, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 17),
(74300, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 118),
(74301, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 118),
(74302, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 118),
(74303, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 118),
(74304, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 25),
(74305, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 25),
(74306, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 25),
(74307, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 25),
(74308, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 24),
(74309, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 24),
(74310, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 24),
(74311, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 24),
(74312, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 27),
(74313, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 27),
(74314, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 27),
(74315, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 27),
(74316, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 45),
(74317, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 45),
(74318, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 45),
(74319, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 45),
(74320, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 46),
(74321, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 46),
(74322, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 46),
(74323, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 46),
(74324, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 47),
(74325, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 47),
(74326, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 47),
(74327, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 47),
(74328, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 48),
(74329, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 48),
(74330, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 48),
(74331, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 48),
(74332, 26, 78, 151, 1, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 49),
(74333, 26, 78, 151, 2, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 49),
(74334, 26, 78, 151, 3, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 49),
(74335, 26, 78, 151, 4, '2025-12-24 08:53:08', '2025-12-24 08:53:08', 49),
(74336, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 81),
(74337, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 81),
(74338, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 81),
(74339, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 81),
(74340, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 41),
(74341, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 41),
(74342, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 41),
(74343, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 41),
(74344, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 42),
(74345, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 42),
(74346, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 42),
(74347, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 42),
(74348, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 43),
(74349, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 43),
(74350, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 43),
(74351, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 43),
(74352, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 79),
(74353, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 79),
(74354, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 79),
(74355, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 79),
(74356, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 16),
(74357, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 16),
(74358, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 16),
(74359, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 16),
(74360, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 58),
(74361, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 58),
(74362, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 58),
(74363, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 58),
(74364, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 59),
(74365, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 59),
(74366, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 59),
(74367, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 59),
(74368, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 60),
(74369, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 60),
(74370, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 60),
(74371, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 60),
(74372, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 69),
(74373, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 69),
(74374, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 69),
(74375, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 69),
(74376, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 112),
(74377, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 112),
(74378, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 112),
(74379, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 112),
(74380, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 113),
(74381, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 113),
(74382, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 113),
(74383, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 113),
(74384, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 114),
(74385, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 114),
(74386, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 114),
(74387, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 114),
(74388, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 63),
(74389, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 63),
(74390, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 63),
(74391, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 63),
(74392, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 64),
(74393, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 64),
(74394, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 64),
(74395, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 64),
(74396, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 68),
(74397, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 68),
(74398, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 68),
(74399, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 68),
(74400, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 91),
(74401, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 91),
(74402, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 91),
(74403, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 91),
(74404, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 70),
(74405, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 70),
(74406, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 70),
(74407, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 70),
(74408, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 72),
(74409, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 72),
(74410, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 72),
(74411, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 72),
(74412, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 73),
(74413, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 73),
(74414, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 73),
(74415, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 73),
(74416, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 74),
(74417, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 74),
(74418, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 74),
(74419, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 74),
(74420, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 1),
(74421, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 1),
(74422, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 1),
(74423, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 1),
(74424, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 111),
(74425, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 111),
(74426, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 111),
(74427, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 111),
(74428, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 8),
(74429, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 8),
(74430, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 8),
(74431, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 8),
(74432, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 65),
(74433, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 65),
(74434, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 65),
(74435, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 65),
(74436, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 66),
(74437, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 66),
(74438, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 66),
(74439, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 66),
(74440, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 100),
(74441, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 100),
(74442, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 100),
(74443, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 100),
(74444, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 105),
(74445, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 105),
(74446, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 105),
(74447, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 105),
(74448, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 108),
(74449, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 108),
(74450, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 108),
(74451, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 108),
(74452, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 102),
(74453, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 102),
(74454, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 102),
(74455, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 102),
(74456, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 101),
(74457, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 101),
(74458, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 101),
(74459, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 101),
(74460, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 103),
(74461, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 103),
(74462, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 103),
(74463, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 103),
(74464, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 106),
(74465, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 106),
(74466, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 106),
(74467, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 106),
(74468, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 104),
(74469, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 104),
(74470, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 104),
(74471, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 104),
(74472, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 107),
(74473, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 107),
(74474, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 107),
(74475, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 107),
(74476, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 110),
(74477, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 110),
(74478, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 110),
(74479, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 110),
(74480, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 78),
(74481, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 78),
(74482, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 78),
(74483, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 78),
(74484, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 76),
(74485, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 76),
(74486, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 76),
(74487, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 76),
(74488, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 77),
(74489, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 77),
(74490, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 77),
(74491, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 77),
(74492, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 75),
(74493, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 75),
(74494, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 75),
(74495, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 75),
(74496, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 83),
(74497, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 83),
(74498, 26, 78, 151, 3, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 83),
(74499, 26, 78, 151, 4, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 83),
(74500, 26, 78, 151, 1, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 80),
(74501, 26, 78, 151, 2, '2025-12-24 08:53:09', '2025-12-24 08:53:09', 80),
(74502, 26, 78, 151, 3, '2025-12-24 08:53:10', '2025-12-24 08:53:10', 80),
(74503, 26, 78, 151, 4, '2025-12-24 08:53:10', '2025-12-24 08:53:10', 80),
(74504, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 6),
(74505, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 6),
(74506, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 6),
(74507, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 6),
(74508, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 7),
(74509, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 7),
(74510, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 7),
(74511, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 7),
(74512, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 15),
(74513, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 15),
(74514, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 15),
(74515, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 15),
(74516, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 14),
(74517, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 14),
(74518, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 14),
(74519, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 14),
(74520, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 2),
(74521, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 2),
(74522, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 2),
(74523, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 2),
(74524, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 3),
(74525, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 3),
(74526, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 3),
(74527, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 3),
(74528, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 4),
(74529, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 4),
(74530, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 4),
(74531, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 4),
(74532, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 5),
(74533, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 5),
(74534, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 5),
(74535, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 5),
(74536, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 51),
(74537, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 51),
(74538, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 51),
(74539, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 51),
(74540, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 52),
(74541, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 52),
(74542, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 52),
(74543, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 52),
(74544, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 54),
(74545, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 54),
(74546, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 54),
(74547, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 54),
(74548, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 55),
(74549, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 55),
(74550, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 55),
(74551, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 55),
(74552, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 56),
(74553, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 56),
(74554, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 56),
(74555, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 56),
(74556, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 115),
(74557, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 115),
(74558, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 115),
(74559, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 115),
(74560, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 57),
(74561, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 57),
(74562, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 57),
(74563, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 57),
(74564, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 53),
(74565, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 53),
(74566, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 53),
(74567, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 53),
(74568, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 13),
(74569, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 13),
(74570, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 13),
(74571, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 13),
(74572, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 18),
(74573, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 18),
(74574, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 18),
(74575, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 18),
(74576, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 19),
(74577, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 19),
(74578, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 19),
(74579, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 19),
(74580, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 9),
(74581, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 9),
(74582, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 9),
(74583, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 9),
(74584, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 11),
(74585, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 11),
(74586, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 11),
(74587, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 11),
(74588, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 12),
(74589, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 12),
(74590, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 12),
(74591, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 12),
(74592, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 21),
(74593, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 21),
(74594, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 21),
(74595, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 21),
(74596, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 22),
(74597, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 22),
(74598, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 22),
(74599, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 22),
(74600, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 20),
(74601, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 20),
(74602, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 20),
(74603, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 20),
(74604, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 10),
(74605, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 10),
(74606, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 10),
(74607, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 10),
(74608, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 84),
(74609, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 84),
(74610, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 84),
(74611, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 84),
(74612, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 86),
(74613, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 86),
(74614, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 86),
(74615, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 86),
(74616, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 87),
(74617, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 87),
(74618, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 87),
(74619, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 87),
(74620, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 88),
(74621, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 88),
(74622, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 88),
(74623, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 88),
(74624, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 82),
(74625, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 82),
(74626, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 82),
(74627, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 82),
(74628, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 85),
(74629, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 85),
(74630, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 85),
(74631, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 85),
(74632, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 90),
(74633, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 90),
(74634, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 90),
(74635, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 90),
(74636, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 89),
(74637, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 89),
(74638, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 89),
(74639, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 89),
(74640, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 92),
(74641, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 92),
(74642, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 92),
(74643, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 92),
(74644, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 93),
(74645, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 93),
(74646, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 93),
(74647, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 93),
(74648, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 94),
(74649, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 94),
(74650, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 94),
(74651, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 94),
(74652, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 95),
(74653, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 95),
(74654, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 95),
(74655, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 95),
(74656, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 96),
(74657, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 96),
(74658, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 96),
(74659, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 96),
(74660, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 98),
(74661, 26, 78, 147, 2, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 98),
(74662, 26, 78, 147, 3, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 98),
(74663, 26, 78, 147, 4, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 98),
(74664, 26, 78, 147, 1, '2025-12-24 08:53:27', '2025-12-24 08:53:27', 116),
(74665, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 116),
(74666, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 116),
(74667, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 116),
(74668, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 97);
INSERT INTO `resort_interal_pages_permissions` (`id`, `resort_id`, `Dept_id`, `position_id`, `Permission_id`, `created_at`, `updated_at`, `page_id`) VALUES
(74669, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 97),
(74670, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 97),
(74671, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 97),
(74672, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 99),
(74673, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 99),
(74674, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 99),
(74675, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 99),
(74676, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 117),
(74677, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 117),
(74678, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 117),
(74679, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 117),
(74680, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 26),
(74681, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 26),
(74682, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 26),
(74683, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 26),
(74684, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 29),
(74685, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 29),
(74686, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 29),
(74687, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 29),
(74688, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 23),
(74689, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 23),
(74690, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 23),
(74691, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 23),
(74692, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 17),
(74693, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 17),
(74694, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 17),
(74695, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 17),
(74696, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 118),
(74697, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 118),
(74698, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 118),
(74699, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 118),
(74700, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 25),
(74701, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 25),
(74702, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 25),
(74703, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 25),
(74704, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 24),
(74705, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 24),
(74706, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 24),
(74707, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 24),
(74708, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 27),
(74709, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 27),
(74710, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 27),
(74711, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 27),
(74712, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 28),
(74713, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 28),
(74714, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 28),
(74715, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 28),
(74716, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 45),
(74717, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 45),
(74718, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 45),
(74719, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 45),
(74720, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 46),
(74721, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 46),
(74722, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 46),
(74723, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 46),
(74724, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 47),
(74725, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 47),
(74726, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 47),
(74727, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 47),
(74728, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 48),
(74729, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 48),
(74730, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 48),
(74731, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 48),
(74732, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 49),
(74733, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 49),
(74734, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 49),
(74735, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 49),
(74736, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 50),
(74737, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 50),
(74738, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 50),
(74739, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 50),
(74740, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 81),
(74741, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 81),
(74742, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 81),
(74743, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 81),
(74744, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 41),
(74745, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 41),
(74746, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 41),
(74747, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 41),
(74748, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 42),
(74749, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 42),
(74750, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 42),
(74751, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 42),
(74752, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 43),
(74753, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 43),
(74754, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 43),
(74755, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 43),
(74756, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 44),
(74757, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 44),
(74758, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 44),
(74759, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 44),
(74760, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 79),
(74761, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 79),
(74762, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 79),
(74763, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 79),
(74764, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 61),
(74765, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 61),
(74766, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 61),
(74767, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 61),
(74768, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 16),
(74769, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 16),
(74770, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 16),
(74771, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 16),
(74772, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 58),
(74773, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 58),
(74774, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 58),
(74775, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 58),
(74776, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 59),
(74777, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 59),
(74778, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 59),
(74779, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 59),
(74780, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 60),
(74781, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 60),
(74782, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 60),
(74783, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 60),
(74784, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 69),
(74785, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 69),
(74786, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 69),
(74787, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 69),
(74788, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 112),
(74789, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 112),
(74790, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 112),
(74791, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 112),
(74792, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 113),
(74793, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 113),
(74794, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 113),
(74795, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 113),
(74796, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 114),
(74797, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 114),
(74798, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 114),
(74799, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 114),
(74800, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 62),
(74801, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 62),
(74802, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 62),
(74803, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 62),
(74804, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 63),
(74805, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 63),
(74806, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 63),
(74807, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 63),
(74808, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 64),
(74809, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 64),
(74810, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 64),
(74811, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 64),
(74812, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 68),
(74813, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 68),
(74814, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 68),
(74815, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 68),
(74816, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 91),
(74817, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 91),
(74818, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 91),
(74819, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 91),
(74820, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 70),
(74821, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 70),
(74822, 26, 78, 147, 3, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 70),
(74823, 26, 78, 147, 4, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 70),
(74824, 26, 78, 147, 1, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 72),
(74825, 26, 78, 147, 2, '2025-12-24 08:53:28', '2025-12-24 08:53:28', 72),
(74826, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 72),
(74827, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 72),
(74828, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 73),
(74829, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 73),
(74830, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 73),
(74831, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 73),
(74832, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 74),
(74833, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 74),
(74834, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 74),
(74835, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 74),
(74836, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 71),
(74837, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 71),
(74838, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 71),
(74839, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 71),
(74840, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 1),
(74841, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 1),
(74842, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 1),
(74843, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 1),
(74844, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 111),
(74845, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 111),
(74846, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 111),
(74847, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 111),
(74848, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 8),
(74849, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 8),
(74850, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 8),
(74851, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 8),
(74852, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 65),
(74853, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 65),
(74854, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 65),
(74855, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 65),
(74856, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 66),
(74857, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 66),
(74858, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 66),
(74859, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 66),
(74860, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 100),
(74861, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 100),
(74862, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 100),
(74863, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 100),
(74864, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 105),
(74865, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 105),
(74866, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 105),
(74867, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 105),
(74868, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 108),
(74869, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 108),
(74870, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 108),
(74871, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 108),
(74872, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 102),
(74873, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 102),
(74874, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 102),
(74875, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 102),
(74876, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 101),
(74877, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 101),
(74878, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 101),
(74879, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 101),
(74880, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 103),
(74881, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 103),
(74882, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 103),
(74883, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 103),
(74884, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 106),
(74885, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 106),
(74886, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 106),
(74887, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 106),
(74888, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 104),
(74889, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 104),
(74890, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 104),
(74891, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 104),
(74892, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 107),
(74893, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 107),
(74894, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 107),
(74895, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 107),
(74896, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 110),
(74897, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 110),
(74898, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 110),
(74899, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 110),
(74900, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 109),
(74901, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 109),
(74902, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 109),
(74903, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 109),
(74904, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 78),
(74905, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 78),
(74906, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 78),
(74907, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 78),
(74908, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 76),
(74909, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 76),
(74910, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 76),
(74911, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 76),
(74912, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 77),
(74913, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 77),
(74914, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 77),
(74915, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 77),
(74916, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 75),
(74917, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 75),
(74918, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 75),
(74919, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 75),
(74920, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 83),
(74921, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 83),
(74922, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 83),
(74923, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 83),
(74924, 26, 78, 147, 1, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 80),
(74925, 26, 78, 147, 2, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 80),
(74926, 26, 78, 147, 3, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 80),
(74927, 26, 78, 147, 4, '2025-12-24 08:53:29', '2025-12-24 08:53:29', 80),
(74928, 26, 80, 149, 1, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 15),
(74929, 26, 80, 149, 1, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 14),
(74930, 26, 80, 149, 1, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 2),
(74931, 26, 80, 149, 1, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 3),
(74932, 26, 80, 149, 1, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 13),
(74933, 26, 80, 149, 2, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 13),
(74934, 26, 80, 149, 3, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 13),
(74935, 26, 80, 149, 4, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 13),
(74936, 26, 80, 149, 1, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 9),
(74937, 26, 80, 149, 2, '2025-12-24 12:17:19', '2025-12-24 12:17:19', 9),
(74938, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 9),
(74939, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 9),
(74940, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 11),
(74941, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 11),
(74942, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 11),
(74943, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 11),
(74944, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 12),
(74945, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 12),
(74946, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 12),
(74947, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 12),
(74948, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 22),
(74949, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 22),
(74950, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 22),
(74951, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 22),
(74952, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 20),
(74953, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 20),
(74954, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 20),
(74955, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 20),
(74956, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 29),
(74957, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 29),
(74958, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 29),
(74959, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 29),
(74960, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 23),
(74961, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 23),
(74962, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 23),
(74963, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 23),
(74964, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 17),
(74965, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 17),
(74966, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 17),
(74967, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 17),
(74968, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 118),
(74969, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 118),
(74970, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 118),
(74971, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 118),
(74972, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 25),
(74973, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 25),
(74974, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 25),
(74975, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 25),
(74976, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 24),
(74977, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 27),
(74978, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 27),
(74979, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 27),
(74980, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 27),
(74981, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 45),
(74982, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 45),
(74983, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 45),
(74984, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 45),
(74985, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 46),
(74986, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 46),
(74987, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 46),
(74988, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 46),
(74989, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 47),
(74990, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 47),
(74991, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 47),
(74992, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 47),
(74993, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 49),
(74994, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 49),
(74995, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 49),
(74996, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 49),
(74997, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 81),
(74998, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 81),
(74999, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 81),
(75000, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 81),
(75001, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 43),
(75002, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 79),
(75003, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 79),
(75004, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 79),
(75005, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 79),
(75006, 26, 80, 149, 1, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 59),
(75007, 26, 80, 149, 2, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 59),
(75008, 26, 80, 149, 3, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 59),
(75009, 26, 80, 149, 4, '2025-12-24 12:17:20', '2025-12-24 12:17:20', 59);

-- --------------------------------------------------------

--
-- Table structure for table `resort_languages`
--

DROP TABLE IF EXISTS `resort_languages`;
CREATE TABLE IF NOT EXISTS `resort_languages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_name` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_image` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_image_svg` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_modules`
--

DROP TABLE IF EXISTS `resort_modules`;
CREATE TABLE IF NOT EXISTS `resort_modules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_module_permissions`
--

DROP TABLE IF EXISTS `resort_module_permissions`;
CREATE TABLE IF NOT EXISTS `resort_module_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_notifications`
--

DROP TABLE IF EXISTS `resort_notifications`;
CREATE TABLE IF NOT EXISTS `resort_notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `module` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('unread','read','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `request_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_notifications_resort_id_foreign` (`resort_id`),
  KEY `resort_notifications_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13380 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_notifications`
--

INSERT INTO `resort_notifications` (`id`, `resort_id`, `user_id`, `module`, `type`, `message`, `status`, `request_id`, `created_by`, `created_at`, `updated_at`) VALUES
(13343, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee John Carter has a basic salary  USD below the minimum wage.', 'deleted', 0, 248, '2025-11-17 15:44:42', '2025-12-10 23:41:15'),
(13344, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee John Carter has a basic salary  USD below the minimum wage.', 'deleted', 0, 240, '2025-11-17 16:01:37', '2025-12-18 14:19:10'),
(13345, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee John Carter has a basic salary  USD below the minimum wage.', 'deleted', 0, 240, '2025-11-17 16:03:04', '2025-12-18 14:19:15'),
(13346, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee John Carter has a basic salary  USD below the minimum wage.', 'deleted', 0, 240, '2025-11-17 16:03:13', '2025-12-18 14:19:16'),
(13347, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee Rani Khan has a basic salary  USD below the minimum wage.', 'deleted', 0, 248, '2025-11-17 18:32:20', '2025-12-18 14:19:11'),
(13348, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee James Wilson has a basic salary  USD below the minimum wage.', 'deleted', 0, 259, '2025-11-18 15:38:27', '2025-12-18 14:19:12'),
(13349, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee Anastasia Volkova has a basic salary  USD below the minimum wage.', 'deleted', 0, 259, '2025-11-18 22:33:55', '2025-12-18 14:19:14'),
(13350, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee Ibrahim Manik has a basic salary  USD below the minimum wage.', 'deleted', 0, 259, '2025-11-18 22:34:09', '2025-12-18 14:19:03'),
(13351, 26, 177, 'People Management (Minimum Wage)', 'People Management Minimum Wage Compliance Breached', 'Employee Ibrahim Manik has a basic salary  USD below the minimum wage.', 'deleted', 0, 259, '2025-11-18 22:34:36', '2025-12-18 14:19:01'),
(13354, 26, 189, 'People - Announcement', 'New Announcement: Employee of the Month', 'Congratulations Rani Khan! Congrats Rani for becoming Employee of the Month. We wish you continued the success', 'deleted', 0, 259, '2025-11-27 21:49:33', '2025-12-18 14:18:52'),
(13355, 26, 189, 'People - Announcement', 'New Announcement: Employee of the Month', 'Congratulations Rani Khan! Congrats Rani for becoming Employee of the Month. We wish you continued the success', 'deleted', 0, 259, '2025-11-27 21:49:36', '2025-12-10 23:32:24'),
(13356, 26, 179, 'Resignation', 'Resignation', 'A resignation request has been submitted by Rani Khan.', 'read', NULL, 260, '2025-12-02 00:30:59', '2025-12-15 18:32:29'),
(13378, 26, 189, 'Boarding Pass', 'Boarding Pass Rejected', 'A boarding pass request has been Rejected by Priya Sharma.', 'read', NULL, 248, '2025-12-18 15:03:02', '2025-12-18 16:29:08'),
(13379, 26, 189, 'Boarding Pass', 'Boarding Pass Approved', 'A boarding pass request has been Approved by Priya Sharma.', 'read', NULL, 248, '2025-12-18 16:35:00', '2025-12-19 13:54:23');

-- --------------------------------------------------------

--
-- Table structure for table `resort_pagewise_permissions`
--

DROP TABLE IF EXISTS `resort_pagewise_permissions`;
CREATE TABLE IF NOT EXISTS `resort_pagewise_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `Module_id` bigint UNSIGNED NOT NULL,
  `page_permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_pagewise_permissions_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_pagewise_permissions`
--

INSERT INTO `resort_pagewise_permissions` (`id`, `resort_id`, `Module_id`, `page_permission_id`, `created_at`, `updated_at`) VALUES
(8386, 25, 1, 2, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8387, 25, 1, 3, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8388, 25, 1, 4, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8389, 25, 1, 5, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8390, 25, 1, 6, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8391, 25, 1, 7, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8392, 25, 1, 14, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8393, 25, 1, 15, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8402, 25, 3, 9, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8403, 25, 3, 10, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8404, 25, 3, 11, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8405, 25, 3, 12, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8406, 25, 3, 13, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8407, 25, 3, 18, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8408, 25, 3, 19, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8430, 25, 5, 17, '2025-11-01 07:06:01', '2025-11-01 07:06:01'),
(8450, 25, 9, 16, '2025-11-01 07:06:02', '2025-11-01 07:06:02'),
(8479, 25, 18, 1, '2025-11-01 07:06:02', '2025-11-01 07:06:02'),
(8491, 26, 1, 2, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8492, 26, 1, 3, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8493, 26, 1, 4, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8494, 26, 1, 5, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8495, 26, 1, 6, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8496, 26, 1, 7, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8497, 26, 1, 14, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8507, 26, 3, 9, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8510, 26, 3, 12, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8584, 26, 18, 1, '2025-11-12 11:12:14', '2025-11-12 11:12:14'),
(8596, 26, 1, 2, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8597, 26, 1, 3, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8598, 26, 1, 4, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8599, 26, 1, 5, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8600, 26, 1, 6, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8601, 26, 1, 7, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8602, 26, 1, 14, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8612, 26, 3, 9, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8615, 26, 3, 12, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8616, 26, 3, 13, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8640, 26, 5, 17, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8660, 26, 9, 16, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8689, 26, 18, 1, '2025-11-12 11:19:21', '2025-11-12 11:19:21'),
(8701, 25, 1, 2, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8702, 25, 1, 3, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8703, 25, 1, 4, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8704, 25, 1, 5, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8705, 25, 1, 6, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8706, 25, 1, 7, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8707, 25, 1, 14, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8708, 25, 1, 15, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8709, 25, 2, 51, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8710, 25, 2, 52, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8711, 25, 2, 53, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8712, 25, 2, 54, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8713, 25, 2, 55, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8714, 25, 2, 56, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8715, 25, 2, 57, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8716, 25, 2, 115, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8717, 25, 3, 9, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8718, 25, 3, 10, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8719, 25, 3, 11, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8720, 25, 3, 12, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8721, 25, 3, 13, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8722, 25, 3, 18, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8723, 25, 3, 19, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8724, 25, 3, 20, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8725, 25, 3, 21, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8726, 25, 3, 22, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8727, 25, 4, 82, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8728, 25, 4, 84, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8729, 25, 4, 85, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8730, 25, 4, 86, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8731, 25, 4, 87, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8732, 25, 4, 88, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8733, 25, 4, 89, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8734, 25, 4, 90, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8735, 25, 4, 92, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8736, 25, 4, 93, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8737, 25, 4, 94, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8738, 25, 4, 95, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8739, 25, 4, 96, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8740, 25, 4, 97, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8741, 25, 4, 98, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8742, 25, 4, 99, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8743, 25, 4, 116, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8744, 25, 4, 117, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8745, 25, 5, 17, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8746, 25, 5, 23, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8747, 25, 5, 24, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8748, 25, 5, 25, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8749, 25, 5, 26, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8750, 25, 5, 27, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8751, 25, 5, 28, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8752, 25, 5, 29, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8753, 25, 6, 45, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8754, 25, 6, 46, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8755, 25, 6, 47, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8756, 25, 6, 48, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8757, 25, 6, 49, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8758, 25, 6, 50, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8759, 25, 6, 81, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8760, 25, 7, 41, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8761, 25, 7, 42, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8762, 25, 7, 43, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8763, 25, 7, 44, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8764, 25, 7, 79, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8765, 25, 8, 16, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8766, 25, 8, 58, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8767, 25, 8, 59, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8768, 25, 8, 60, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8769, 25, 8, 61, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8770, 25, 8, 69, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8771, 25, 8, 112, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8772, 25, 8, 113, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8773, 25, 8, 114, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8774, 25, 9, 30, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8775, 25, 9, 31, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8776, 25, 9, 32, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8777, 25, 9, 33, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8778, 25, 9, 34, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8779, 25, 9, 35, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8780, 25, 9, 36, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8781, 25, 9, 37, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8782, 25, 9, 38, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8783, 25, 9, 39, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8784, 25, 9, 40, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8785, 25, 9, 67, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8786, 25, 10, 70, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8787, 25, 10, 71, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8788, 25, 10, 72, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8789, 25, 10, 73, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8790, 25, 10, 74, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8791, 25, 11, 65, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8792, 25, 11, 66, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8793, 25, 12, 91, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8794, 25, 13, 1, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8795, 25, 14, 100, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8796, 25, 14, 101, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8797, 25, 14, 102, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8798, 25, 14, 103, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8799, 25, 14, 104, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8800, 25, 14, 105, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8801, 25, 14, 106, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8802, 25, 14, 107, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8803, 25, 14, 108, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8804, 25, 14, 109, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8805, 25, 14, 110, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8806, 25, 15, 62, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8807, 25, 15, 63, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8808, 25, 15, 64, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8809, 25, 15, 68, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8810, 25, 16, 75, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8811, 25, 16, 76, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8812, 25, 16, 77, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8813, 25, 16, 78, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8814, 25, 17, 80, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8815, 25, 17, 83, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8816, 25, 18, 111, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8817, 25, 19, 8, '2025-11-12 16:28:53', '2025-11-12 16:28:53'),
(8818, 26, 1, 2, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8819, 26, 1, 3, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8820, 26, 1, 4, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8821, 26, 1, 5, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8822, 26, 1, 6, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8823, 26, 1, 7, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8824, 26, 1, 14, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8834, 26, 3, 9, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8837, 26, 3, 12, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8838, 26, 3, 13, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8862, 26, 5, 17, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8882, 26, 8, 16, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8911, 26, 13, 1, '2025-11-12 16:30:20', '2025-11-12 16:30:20'),
(8935, 26, 1, 2, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8936, 26, 1, 3, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8937, 26, 1, 4, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8938, 26, 1, 5, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8939, 26, 1, 6, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8940, 26, 1, 7, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8941, 26, 1, 14, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8951, 26, 3, 9, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8954, 26, 3, 12, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8955, 26, 3, 13, '2025-12-24 08:51:44', '2025-12-24 08:51:44'),
(8979, 26, 5, 17, '2025-12-24 08:51:45', '2025-12-24 08:51:45'),
(9000, 26, 9, 16, '2025-12-24 08:51:45', '2025-12-24 08:51:45'),
(9002, 26, 13, 1, '2025-12-24 08:51:45', '2025-12-24 08:51:45'),
(9020, 26, 1, 2, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9021, 26, 1, 3, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9022, 26, 1, 4, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9023, 26, 1, 5, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9024, 26, 1, 6, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9025, 26, 1, 7, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9026, 26, 1, 14, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9027, 26, 1, 15, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9028, 26, 2, 51, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9029, 26, 2, 52, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9030, 26, 2, 53, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9031, 26, 2, 54, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9032, 26, 2, 55, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9033, 26, 2, 56, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9034, 26, 2, 57, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9035, 26, 2, 115, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9036, 26, 3, 9, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9037, 26, 3, 10, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9038, 26, 3, 11, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9039, 26, 3, 12, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9040, 26, 3, 13, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9041, 26, 3, 18, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9042, 26, 3, 19, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9043, 26, 3, 20, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9044, 26, 3, 21, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9045, 26, 3, 22, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9046, 26, 4, 82, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9047, 26, 4, 84, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9048, 26, 4, 85, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9049, 26, 4, 86, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9050, 26, 4, 87, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9051, 26, 4, 88, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9052, 26, 4, 89, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9053, 26, 4, 90, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9054, 26, 4, 92, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9055, 26, 4, 93, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9056, 26, 4, 94, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9057, 26, 4, 95, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9058, 26, 4, 96, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9059, 26, 4, 97, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9060, 26, 4, 98, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9061, 26, 4, 99, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9062, 26, 4, 116, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9063, 26, 4, 117, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9064, 26, 5, 17, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9065, 26, 5, 23, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9066, 26, 5, 24, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9067, 26, 5, 25, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9068, 26, 5, 26, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9069, 26, 5, 27, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9070, 26, 5, 28, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9071, 26, 5, 29, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9072, 26, 5, 118, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9073, 26, 6, 45, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9074, 26, 6, 46, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9075, 26, 6, 47, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9076, 26, 6, 48, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9077, 26, 6, 49, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9078, 26, 6, 50, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9079, 26, 6, 81, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9080, 26, 7, 41, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9081, 26, 7, 42, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9082, 26, 7, 43, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9083, 26, 7, 44, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9084, 26, 7, 79, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9085, 26, 9, 16, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9086, 26, 12, 91, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9087, 26, 13, 1, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9088, 26, 14, 100, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9089, 26, 14, 101, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9090, 26, 14, 102, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9091, 26, 14, 103, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9092, 26, 14, 104, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9093, 26, 14, 105, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9094, 26, 14, 106, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9095, 26, 14, 107, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9096, 26, 14, 108, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9097, 26, 14, 109, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9098, 26, 14, 110, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9099, 26, 16, 75, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9100, 26, 16, 76, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9101, 26, 16, 77, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9102, 26, 16, 78, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9103, 26, 17, 80, '2025-12-24 08:52:11', '2025-12-24 08:52:11'),
(9104, 26, 17, 83, '2025-12-24 08:52:11', '2025-12-24 08:52:11');

-- --------------------------------------------------------

--
-- Table structure for table `resort_permissions`
--

DROP TABLE IF EXISTS `resort_permissions`;
CREATE TABLE IF NOT EXISTS `resort_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` smallint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_positions`
--

DROP TABLE IF EXISTS `resort_positions`;
CREATE TABLE IF NOT EXISTS `resort_positions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `dept_id` int UNSIGNED NOT NULL,
  `section_id` int UNSIGNED DEFAULT NULL,
  `Rank` int NOT NULL,
  `position_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_of_positions` int DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_reserved` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Indicates if the position is reserved for a Local or Expat',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_positions_dept_id_foreign` (`dept_id`),
  KEY `resort_positions_resort_id_foreign` (`resort_id`),
  KEY `resort_positions_section_id_foreign` (`section_id`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_positions`
--

INSERT INTO `resort_positions` (`id`, `resort_id`, `dept_id`, `section_id`, `Rank`, `position_title`, `no_of_positions`, `code`, `short_title`, `status`, `is_reserved`, `slug`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(139, 25, 75, NULL, 2, 'Director Of Human Resources', 1, 'DHR', 'HR Director', 'active', 'No', 'director-of-human-resources', 233, 233, '2025-10-30 12:03:58', '2025-11-06 15:29:56'),
(141, 25, 77, NULL, 2, 'Director Of Food And Beverage', 0, 'DOFB', 'FB Director', 'active', 'No', 'director-of-food-and-beverage', 233, 233, '2025-11-06 15:29:14', '2025-11-06 15:29:38'),
(142, 26, 81, NULL, 8, 'General Manager', NULL, 'GM_1', 'GM', 'active', 'No', 'general-manager', 240, 240, '2025-11-13 13:43:09', '2025-11-13 13:43:09'),
(143, 26, 79, NULL, 1, 'Director Of Finance', NULL, 'DOF_1', 'DOF', 'active', 'No', 'director-of-finance', 240, 240, '2025-11-13 13:44:24', '2025-11-13 13:44:24'),
(144, 26, 79, NULL, 2, 'Finance Manager', NULL, 'FM_1', 'Fin Mgr', 'active', 'No', 'finance-manager', 240, 240, '2025-11-13 13:46:13', '2025-11-13 13:46:13'),
(145, 26, 79, NULL, 6, 'Accounting Clerk', NULL, 'AC_1', 'AC', 'active', 'No', 'accounting-clerk', 240, 240, '2025-11-13 13:47:24', '2025-11-13 13:47:24'),
(146, 26, 78, NULL, 6, 'Human Resources Coordinator', NULL, 'HRC_1', 'HR Cor', 'active', 'No', 'human-resources-coordinator', 240, 240, '2025-11-13 13:48:44', '2025-11-13 13:48:44'),
(147, 26, 78, NULL, 1, 'Director Of Human Resources', NULL, 'DOHR_1', 'DOHR', 'active', 'No', 'director-of-human-resources-1', 240, 240, '2025-11-13 13:50:01', '2025-11-13 13:50:01'),
(148, 26, 80, NULL, 1, 'Executive Chef', NULL, 'EC_1', 'EC', 'active', 'No', 'executive-chef', 240, 240, '2025-11-13 13:52:48', '2025-11-13 13:52:48'),
(149, 26, 80, NULL, 2, 'Food And Beverage Manager', NULL, 'FB Mgr_1', 'FB Mgr', 'active', 'No', 'food-and-beverage-manager', 240, 240, '2025-11-13 13:53:42', '2025-11-13 13:53:42'),
(150, 26, 80, NULL, 6, 'Commis', NULL, 'Com_1', 'Com', 'active', 'No', 'commis', 240, 240, '2025-11-13 13:54:42', '2025-11-13 13:54:42'),
(151, 26, 78, NULL, 2, 'Human Resources Manager', NULL, 'HRM_1', 'HRM', 'active', 'No', 'human-resources-manager', 240, 240, '2025-11-13 13:56:33', '2025-11-13 13:56:33'),
(152, 26, 80, 39, 6, 'Waitress', NULL, 'Waitress_1', 'Waitress', 'active', 'No', 'waitress', 240, 240, '2025-11-13 14:02:13', '2025-11-13 14:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `resort_position_module_permissions`
--

DROP TABLE IF EXISTS `resort_position_module_permissions`;
CREATE TABLE IF NOT EXISTS `resort_position_module_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `position_id` bigint UNSIGNED NOT NULL,
  `module_permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_reports`
--

DROP TABLE IF EXISTS `resort_reports`;
CREATE TABLE IF NOT EXISTS `resort_reports` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `AiInsights` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `resort_id` int UNSIGNED NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `query_params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_reports_resort_id_foreign` (`resort_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `resort_reports`
--

INSERT INTO `resort_reports` (`id`, `name`, `from_date`, `to_date`, `AiInsights`, `resort_id`, `description`, `query_params`, `created_at`, `updated_at`) VALUES
(1, 'Mal and Female', '2025-11-01', '2025-11-14', NULL, 26, 'Gender headcount', '{\"table\":\"employees\",\"columns\":[\"rank\"],\"relation_tables\":[],\"filters\":[]}', '2025-11-14 16:46:38', '2025-11-14 16:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `resort_roles`
--

DROP TABLE IF EXISTS `resort_roles`;
CREATE TABLE IF NOT EXISTS `resort_roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_roles_resort_id_index` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_roles_modules_permissions`
--

DROP TABLE IF EXISTS `resort_roles_modules_permissions`;
CREATE TABLE IF NOT EXISTS `resort_roles_modules_permissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` bigint NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `module_permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_sections`
--

DROP TABLE IF EXISTS `resort_sections`;
CREATE TABLE IF NOT EXISTS `resort_sections` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `dept_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_sections_dept_id_foreign` (`dept_id`),
  KEY `resort_sections_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_sections`
--

INSERT INTO `resort_sections` (`id`, `resort_id`, `dept_id`, `name`, `code`, `short_name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(39, 26, 80, 'Main Restaurant', 'MR_1', 'Res', 'active', 240, 240, '2025-11-13 13:40:45', '2025-11-13 13:40:45'),
(40, 26, 78, 'Admin', 'Admin_1', 'Admin', 'active', 240, 240, '2025-11-13 13:41:14', '2025-11-13 13:41:14');

-- --------------------------------------------------------

--
-- Table structure for table `resort_service_charges`
--

DROP TABLE IF EXISTS `resort_service_charges`;
CREATE TABLE IF NOT EXISTS `resort_service_charges` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `month` tinyint UNSIGNED NOT NULL,
  `year` smallint UNSIGNED NOT NULL,
  `service_charge` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_service_charges_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resort_site_settings`
--

DROP TABLE IF EXISTS `resort_site_settings`;
CREATE TABLE IF NOT EXISTS `resort_site_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int NOT NULL,
  `currency` enum('MVR','Dollar') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MVR',
  `MVRtoDoller` double NOT NULL,
  `DollertoMVR` double NOT NULL,
  `MVR_img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Doller_img` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Footer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `FinalApproval` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `header_img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `footer_img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_site_settings_resort_id_index` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_site_settings`
--

INSERT INTO `resort_site_settings` (`id`, `resort_id`, `currency`, `MVRtoDoller`, `DollertoMVR`, `MVR_img`, `Doller_img`, `Footer`, `FinalApproval`, `header_img`, `footer_img`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(11, 26, 'MVR', 0.065, 15.42, 'maldives-currency-icon-new.svg', 'doller-currency-icon.svg', 'Copyright © 2025. All Rights Reserved.', '8', NULL, NULL, 240, 259, '2025-11-11 21:28:21', '2025-12-12 23:39:33');

-- --------------------------------------------------------

--
-- Table structure for table `resort_transportations`
--

DROP TABLE IF EXISTS `resort_transportations`;
CREATE TABLE IF NOT EXISTS `resort_transportations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `transportation_option` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resort_transportations_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_transportations`
--

INSERT INTO `resort_transportations` (`id`, `resort_id`, `transportation_option`, `created_at`, `updated_at`) VALUES
(5, 26, 'Seaplane', '2025-11-23 10:46:24', '2025-11-23 10:46:24'),
(6, 26, 'Speedboat', '2025-11-23 10:46:24', '2025-11-23 10:46:24'),
(7, 26, 'Domestic Flight', '2025-11-23 10:46:24', '2025-11-23 10:46:24'),
(8, 26, 'International Flight', '2025-11-23 10:46:24', '2025-11-23 10:46:24');

-- --------------------------------------------------------

--
-- Table structure for table `resort_vacant_budget_costs`
--

DROP TABLE IF EXISTS `resort_vacant_budget_costs`;
CREATE TABLE IF NOT EXISTS `resort_vacant_budget_costs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `position_id` int UNSIGNED NOT NULL,
  `department_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `year` int DEFAULT NULL,
  `vacant_index` int NOT NULL DEFAULT '1',
  `details` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `current_salary` decimal(15,2) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rvbc_pos_resort_year_idx` (`position_id`,`resort_id`,`year`,`vacant_index`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_vacant_budget_costs`
--

INSERT INTO `resort_vacant_budget_costs` (`id`, `position_id`, `department_id`, `resort_id`, `year`, `vacant_index`, `details`, `basic_salary`, `current_salary`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 146, 78, 26, 2026, 1, 'Xpat Only', 500.00, 0.00, 259, 259, '2025-11-28 15:01:30', '2025-12-07 17:06:57'),
(2, 147, 78, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:01:44', '2025-11-28 15:01:44'),
(3, 151, 78, 26, 2026, 1, 'Xpat Only', 0.00, 500.00, 259, 259, '2025-11-28 15:01:47', '2025-12-05 15:26:38'),
(4, 143, 79, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:01:52', '2025-11-28 15:01:52'),
(5, 144, 79, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:01:54', '2025-11-28 15:01:54'),
(6, 145, 79, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:01:57', '2025-11-28 15:01:57'),
(7, 148, 80, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:02:04', '2025-11-28 15:02:04'),
(8, 148, 80, 26, 2026, 2, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:02:04', '2025-11-28 15:02:04'),
(9, 149, 80, 26, 2026, 2, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:02:06', '2025-11-28 15:02:06'),
(10, 149, 80, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:02:06', '2025-11-28 15:02:06'),
(11, 150, 80, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:02:09', '2025-11-28 15:02:09'),
(12, 150, 80, 26, 2026, 2, NULL, 0.00, 0.00, 259, 259, '2025-11-28 15:02:09', '2025-11-28 15:02:09'),
(13, 152, 80, 26, 2026, 1, NULL, 0.00, 0.00, 259, 259, '2025-12-05 05:31:25', '2025-12-05 05:31:25');

-- --------------------------------------------------------

--
-- Table structure for table `resort_vacant_budget_cost_configurations`
--

DROP TABLE IF EXISTS `resort_vacant_budget_cost_configurations`;
CREATE TABLE IF NOT EXISTS `resort_vacant_budget_cost_configurations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `vacant_budget_cost_id` bigint UNSIGNED NOT NULL,
  `resort_budget_cost_id` int UNSIGNED NOT NULL,
  `value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `basic_salary` decimal(15,2) NOT NULL DEFAULT '0.00',
  `current_salary` decimal(15,2) NOT NULL DEFAULT '0.00',
  `hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Hours for percentage-based calculations like overtime',
  `department_id` int UNSIGNED NOT NULL,
  `position_id` int UNSIGNED NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `year` int DEFAULT NULL,
  `month` int DEFAULT NULL COMMENT 'Month (1-12)',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rvbcc_resort_cost_fk` (`resort_budget_cost_id`),
  KEY `rvbcc_vacant_resort_year_idx` (`vacant_budget_cost_id`,`resort_id`,`year`),
  KEY `rvbcc_vacant_resort_year_month_idx` (`vacant_budget_cost_id`,`resort_id`,`year`,`month`)
) ENGINE=MyISAM AUTO_INCREMENT=697 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resort_vacant_budget_cost_configurations`
--

INSERT INTO `resort_vacant_budget_cost_configurations` (`id`, `vacant_budget_cost_id`, `resort_budget_cost_id`, `value`, `currency`, `basic_salary`, `current_salary`, `hours`, `department_id`, `position_id`, `resort_id`, `year`, `month`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(695, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 10, 259, 259, '2025-12-07 17:06:57', '2025-12-07 17:06:57'),
(671, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 11, 259, 259, '2025-12-07 17:02:58', '2025-12-07 17:02:58'),
(36, 3, 185, 130.00, 'MVR', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(35, 3, 183, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(34, 3, 182, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(33, 3, 181, 40.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(32, 3, 180, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(31, 3, 179, 100.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(30, 3, 177, 150.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(29, 3, 176, 22.75, 'MVR', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(28, 3, 173, 700.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(27, 3, 167, 6.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(26, 3, 166, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(25, 3, 165, 195.00, 'MVR', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(24, 3, 164, 300.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(23, 3, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 151, 26, 2026, 1, 259, 259, '2025-12-05 15:26:38', '2025-12-05 15:26:38'),
(18, 3, 170, 0.00, 'USD', 0.00, 0.00, 312.00, 78, 151, 26, 2026, 2, 259, 259, '2025-12-05 15:02:35', '2025-12-05 15:02:35'),
(19, 3, 170, 0.00, 'USD', 0.00, 0.00, 312.00, 78, 151, 26, 2026, 3, 259, 259, '2025-12-05 15:02:35', '2025-12-05 15:02:35'),
(20, 3, 170, 0.00, 'USD', 0.00, 0.00, 312.00, 78, 151, 26, 2026, 4, 259, 259, '2025-12-05 15:02:35', '2025-12-05 15:02:35'),
(21, 3, 170, 0.00, 'USD', 0.00, 0.00, 312.00, 78, 151, 26, 2026, 5, 259, 259, '2025-12-05 15:02:35', '2025-12-05 15:02:35'),
(22, 3, 170, 0.00, 'USD', 0.00, 0.00, 312.00, 78, 151, 26, 2026, 6, 259, 259, '2025-12-05 15:02:35', '2025-12-05 15:02:35'),
(690, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 4, 259, 259, '2025-12-07 17:06:00', '2025-12-07 17:06:00'),
(642, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 6, 259, 259, '2025-12-07 17:02:24', '2025-12-07 17:02:24'),
(677, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 12, 259, 259, '2025-12-07 17:03:06', '2025-12-07 17:03:06'),
(636, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 5, 259, 259, '2025-12-07 17:02:18', '2025-12-07 17:02:18'),
(648, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 7, 259, 259, '2025-12-07 17:02:30', '2025-12-07 17:02:30'),
(654, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 8, 259, 259, '2025-12-07 17:02:37', '2025-12-07 17:02:37'),
(660, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 9, 259, 259, '2025-12-07 17:02:45', '2025-12-07 17:02:45'),
(676, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 12, 259, 259, '2025-12-07 17:03:06', '2025-12-07 17:03:06'),
(670, 1, 170, 5.21, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 11, 259, 259, '2025-12-07 17:02:58', '2025-12-07 17:02:58'),
(694, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 10, 259, 259, '2025-12-07 17:06:57', '2025-12-07 17:06:57'),
(652, 1, 169, 120.97, 'USD', 0.00, 0.00, 40.00, 78, 146, 26, 2026, 8, 259, 259, '2025-12-07 17:02:37', '2025-12-07 17:02:37'),
(659, 1, 170, 5.21, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 9, 259, 259, '2025-12-07 17:02:45', '2025-12-07 17:02:45'),
(669, 1, 169, 187.50, 'USD', 0.00, 0.00, 60.00, 78, 146, 26, 2026, 11, 259, 259, '2025-12-07 17:02:58', '2025-12-07 17:02:58'),
(689, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 4, 259, 259, '2025-12-07 17:06:00', '2025-12-07 17:06:00'),
(635, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 5, 259, 259, '2025-12-07 17:02:18', '2025-12-07 17:02:18'),
(641, 1, 170, 5.21, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 6, 259, 259, '2025-12-07 17:02:24', '2025-12-07 17:02:24'),
(688, 1, 170, 5.21, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 4, 259, 259, '2025-12-07 17:06:00', '2025-12-07 17:06:00'),
(653, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 8, 259, 259, '2025-12-07 17:02:37', '2025-12-07 17:02:37'),
(658, 1, 169, 156.25, 'USD', 0.00, 0.00, 50.00, 78, 146, 26, 2026, 9, 259, 259, '2025-12-07 17:02:45', '2025-12-07 17:02:45'),
(544, 1, 185, 11.15, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(509, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(684, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 3, 259, 259, '2025-12-07 17:03:30', '2025-12-07 17:03:30'),
(687, 1, 169, 125.00, 'USD', 0.00, 0.00, 40.00, 78, 146, 26, 2026, 4, 259, 259, '2025-12-07 17:06:00', '2025-12-07 17:06:00'),
(634, 1, 169, 241.94, 'USD', 0.00, 0.00, 80.00, 78, 146, 26, 2026, 5, 259, 259, '2025-12-07 17:02:18', '2025-12-07 17:02:18'),
(640, 1, 169, 156.25, 'USD', 0.00, 0.00, 50.00, 78, 146, 26, 2026, 6, 259, 259, '2025-12-07 17:02:24', '2025-12-07 17:02:24'),
(647, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 7, 259, 259, '2025-12-07 17:02:30', '2025-12-07 17:02:30'),
(646, 1, 169, 151.21, 'USD', 0.00, 0.00, 50.00, 78, 146, 26, 2026, 7, 259, 259, '2025-12-07 17:02:30', '2025-12-07 17:02:30'),
(508, 1, 176, 22.70, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(675, 1, 169, 120.97, 'USD', 0.00, 0.00, 40.00, 78, 146, 26, 2026, 12, 259, 259, '2025-12-07 17:03:06', '2025-12-07 17:03:06'),
(693, 1, 169, 151.21, 'USD', 0.00, 0.00, 50.00, 78, 146, 26, 2026, 10, 259, 259, '2025-12-07 17:06:57', '2025-12-07 17:06:57'),
(683, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 3, 259, 259, '2025-12-07 17:03:30', '2025-12-07 17:03:30'),
(543, 1, 183, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(645, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 7, 259, 259, '2025-12-07 17:02:30', '2025-12-07 17:02:30'),
(651, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 8, 259, 259, '2025-12-07 17:02:37', '2025-12-07 17:02:37'),
(657, 1, 167, 180.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 9, 259, 259, '2025-12-07 17:02:45', '2025-12-07 17:02:45'),
(692, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 10, 259, 259, '2025-12-07 17:06:57', '2025-12-07 17:06:57'),
(668, 1, 167, 180.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 11, 259, 259, '2025-12-07 17:02:58', '2025-12-07 17:02:58'),
(674, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 12, 259, 259, '2025-12-07 17:03:06', '2025-12-07 17:03:06'),
(542, 1, 182, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(541, 1, 180, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(507, 1, 170, 5.58, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(682, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 3, 259, 259, '2025-12-07 17:03:30', '2025-12-07 17:03:30'),
(686, 1, 167, 180.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 4, 259, 259, '2025-12-07 17:06:00', '2025-12-07 17:06:00'),
(633, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 5, 259, 259, '2025-12-07 17:02:18', '2025-12-07 17:02:18'),
(639, 1, 167, 180.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 6, 259, 259, '2025-12-07 17:02:24', '2025-12-07 17:02:24'),
(506, 1, 169, 133.93, 'USD', 0.00, 0.00, 40.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(681, 1, 169, 151.21, 'USD', 0.00, 0.00, 50.00, 78, 146, 26, 2026, 3, 259, 259, '2025-12-07 17:03:30', '2025-12-07 17:03:30'),
(632, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 5, 259, 259, '2025-12-07 17:02:18', '2025-12-07 17:02:18'),
(638, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 6, 259, 259, '2025-12-07 17:02:24', '2025-12-07 17:02:24'),
(644, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 7, 259, 259, '2025-12-07 17:02:30', '2025-12-07 17:02:30'),
(650, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 8, 259, 259, '2025-12-07 17:02:37', '2025-12-07 17:02:37'),
(656, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 9, 259, 259, '2025-12-07 17:02:45', '2025-12-07 17:02:45'),
(667, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 11, 259, 259, '2025-12-07 17:02:58', '2025-12-07 17:02:58'),
(673, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 12, 259, 259, '2025-12-07 17:03:06', '2025-12-07 17:03:06'),
(540, 1, 179, 100.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(539, 1, 177, 150.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(538, 1, 176, 22.70, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(537, 1, 173, 700.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(536, 1, 170, 5.04, 'USD', 0.00, 0.00, 2.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(535, 1, 169, 211.69, 'USD', 0.00, 0.00, 70.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(534, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(533, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 1, 259, 259, '2025-12-07 16:51:20', '2025-12-07 16:51:20'),
(505, 1, 167, 168.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(504, 1, 165, 194.55, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(680, 1, 167, 186.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 3, 259, 259, '2025-12-07 17:03:30', '2025-12-07 17:03:30'),
(679, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 3, 259, 259, '2025-12-07 17:03:30', '2025-12-07 17:03:30'),
(503, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 2, 259, 259, '2025-12-07 16:47:59', '2025-12-07 16:47:59'),
(685, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 4, 259, 259, '2025-12-07 17:06:00', '2025-12-07 17:06:00'),
(637, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 5, 259, 259, '2025-12-07 17:02:18', '2025-12-07 17:02:18'),
(643, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 6, 259, 259, '2025-12-07 17:02:24', '2025-12-07 17:02:24'),
(649, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 7, 259, 259, '2025-12-07 17:02:30', '2025-12-07 17:02:30'),
(655, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 8, 259, 259, '2025-12-07 17:02:37', '2025-12-07 17:02:37'),
(661, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 9, 259, 259, '2025-12-07 17:02:45', '2025-12-07 17:02:45'),
(691, 1, 163, 50.00, 'USD', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 10, 259, 259, '2025-12-07 17:06:57', '2025-12-07 17:06:57'),
(672, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 11, 259, 259, '2025-12-07 17:02:58', '2025-12-07 17:02:58'),
(678, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 12, 259, 259, '2025-12-07 17:03:06', '2025-12-07 17:03:06'),
(696, 1, 185, 10.77, 'MVR', 0.00, 0.00, 0.00, 78, 146, 26, 2026, 10, 259, 259, '2025-12-07 17:06:57', '2025-12-07 17:06:57');

-- --------------------------------------------------------

--
-- Table structure for table `right_to_be_accompanieds`
--

DROP TABLE IF EXISTS `right_to_be_accompanieds`;
CREATE TABLE IF NOT EXISTS `right_to_be_accompanieds` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `RightToBeAccompanied` enum('Allow','Denied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Allow',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `right_to_be_accompanieds_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_dashboard` enum('admin','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `default_dashboard`, `status`, `created_at`, `updated_at`) VALUES
(10, 'super', 'admin', 'active', '2025-10-28 13:21:31', '2025-10-28 13:21:31');

-- --------------------------------------------------------

--
-- Table structure for table `salary_increments`
--

DROP TABLE IF EXISTS `salary_increments`;
CREATE TABLE IF NOT EXISTS `salary_increments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int UNSIGNED NOT NULL,
  `previous_salary` decimal(12,2) NOT NULL,
  `new_salary` decimal(12,2) NOT NULL,
  `increment_amount` decimal(12,2) NOT NULL,
  `increment_percentage` decimal(5,2) NOT NULL,
  `increment_type` enum('annual','promotion','performance','adjustment','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_date` date NOT NULL,
  `created_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_increments_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
CREATE TABLE IF NOT EXISTS `sections` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sections_dept_id_foreign` (`dept_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_providers`
--

DROP TABLE IF EXISTS `service_providers`;
CREATE TABLE IF NOT EXISTS `service_providers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_providers_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `header_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `footer_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` text COLLATE utf8mb4_unicode_ci,
  `admin_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contents` text COLLATE utf8mb4_unicode_ci,
  `date_format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Y-m-d',
  `time_format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '12',
  `currency_symbol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '$',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `site_title`, `site_logo`, `header_logo`, `footer_logo`, `admin_logo`, `site_favicon`, `email_address`, `facebook_link`, `instagram_link`, `youtube_link`, `linkedin_link`, `address_1`, `address_2`, `contact_number`, `website`, `admin_email`, `support_email`, `contents`, `date_format`, `time_format`, `currency_symbol`, `created_at`, `updated_at`) VALUES
(2, 'Wisdom AI HRVMS', 'logo.png', 'header_logo.png', NULL, NULL, 'favicon.png', 'amey.tamshetti@gmail.com', NULL, NULL, NULL, NULL, '123 Business Street, City Center', 'Suite 401, Tech Park', '9876543210', 'https://wisdomai.com', 'admin@wisdomai.com', 'support@wisdomai.com', 'Welcome to Wisdom AI HRVMS.', 'd-m-Y', 'H:i', '₹', '2025-10-28 13:17:26', '2025-11-11 18:30:23');

-- --------------------------------------------------------

--
-- Table structure for table `severity_stores`
--

DROP TABLE IF EXISTS `severity_stores`;
CREATE TABLE IF NOT EXISTS `severity_stores` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `SeverityName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `severity_stores_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_settings`
--

DROP TABLE IF EXISTS `shift_settings`;
CREATE TABLE IF NOT EXISTS `shift_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `ShiftName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `StartTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `EndTime` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `TotalHours` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_settings_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shift_settings`
--

INSERT INTO `shift_settings` (`id`, `resort_id`, `ShiftName`, `StartTime`, `EndTime`, `created_by`, `modified_by`, `created_at`, `updated_at`, `TotalHours`) VALUES
(20, 26, 'Morning Shift', '03:00', '11:00', 240, 259, '2025-11-17 13:39:30', '2026-01-01 09:18:09', '8:0'),
(21, 26, 'Afternoon Shift', '12:00', '20:00', 240, 259, '2025-11-17 13:39:30', '2026-01-01 09:18:09', '8:0'),
(22, 26, 'Evening Shift', '20:00', '04:00', 240, 259, '2025-11-17 13:39:30', '2026-01-01 09:18:09', '8:0');

-- --------------------------------------------------------

--
-- Table structure for table `shopkeepers`
--

DROP TABLE IF EXISTS `shopkeepers`;
CREATE TABLE IF NOT EXISTS `shopkeepers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_no` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopkeepers_email_unique` (`email`),
  KEY `shopkeepers_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shopkeepers`
--

INSERT INTO `shopkeepers` (`id`, `resort_id`, `name`, `email`, `password`, `contact_no`, `profile_photo`, `created_at`, `updated_at`) VALUES
(49, 26, 'Tuck Shop', 'Clot-sudoku.0w@icloud.com', '$2y$10$0r0fxcKvudoB5jSyc8UR0OjJuy4yZw1deDK55BbiQMoaC.nFtkT3C', '9226622960', '桂芳.png', '2025-11-26 22:05:18', '2025-12-12 01:43:12');

-- --------------------------------------------------------

--
-- Table structure for table `shopkeeper_password_resets`
--

DROP TABLE IF EXISTS `shopkeeper_password_resets`;
CREATE TABLE IF NOT EXISTS `shopkeeper_password_resets` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shopkeeper_password_resets_email_index` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sos_child_emergency_types`
--

DROP TABLE IF EXISTS `sos_child_emergency_types`;
CREATE TABLE IF NOT EXISTS `sos_child_emergency_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `emergency_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `modified_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_child_emergency_types_emergency_id_foreign` (`emergency_id`),
  KEY `sos_child_emergency_types_team_id_foreign` (`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sos_emergency_types`
--

DROP TABLE IF EXISTS `sos_emergency_types`;
CREATE TABLE IF NOT EXISTS `sos_emergency_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_emergency_types_resort_id_foreign` (`resort_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sos_history`
--

DROP TABLE IF EXISTS `sos_history`;
CREATE TABLE IF NOT EXISTS `sos_history` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `emergency_id` bigint UNSIGNED NOT NULL,
  `emp_initiated_by` int UNSIGNED NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longitude` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Completed','Active','Pending','Rejected','Real-Active','In-Progress','Drill-Active','Drill-Rejected','Drill-Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `date` date NOT NULL,
  `time` time NOT NULL,
  `emergency_description` text COLLATE utf8mb4_unicode_ci,
  `sos_approved_by` int UNSIGNED DEFAULT NULL,
  `sos_approved_time` time DEFAULT NULL,
  `sos_approved_date` date DEFAULT NULL,
  `employee_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejected_message` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mass_instructions` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_history_resort_id_foreign` (`resort_id`),
  KEY `sos_history_emergency_id_foreign` (`emergency_id`),
  KEY `sos_history_emp_initiated_by_foreign` (`emp_initiated_by`),
  KEY `sos_history_sos_approved_by_foreign` (`sos_approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=447 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sos_history_employee_status`
--

DROP TABLE IF EXISTS `sos_history_employee_status`;
CREATE TABLE IF NOT EXISTS `sos_history_employee_status` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sos_history_id` bigint UNSIGNED NOT NULL,
  `emp_id` int UNSIGNED NOT NULL,
  `mass_instruction` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Safe','Unsafe','Unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unknown',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_history_employee_status_sos_history_id_foreign` (`sos_history_id`),
  KEY `sos_history_employee_status_emp_id_foreign` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10680 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sos_role_management`
--

DROP TABLE IF EXISTS `sos_role_management`;
CREATE TABLE IF NOT EXISTS `sos_role_management` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permission` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_role_management_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sos_role_management`
--

INSERT INTO `sos_role_management` (`id`, `resort_id`, `name`, `permission`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(20, 26, 'Acknowledger', '1', 259, 259, '2025-11-27 21:31:58', '2025-11-27 21:31:58');

-- --------------------------------------------------------

--
-- Table structure for table `sos_teams`
--

DROP TABLE IF EXISTS `sos_teams`;
CREATE TABLE IF NOT EXISTS `sos_teams` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_teams_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sos_teams`
--

INSERT INTO `sos_teams` (`id`, `resort_id`, `name`, `description`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(44, 26, 'Fire Team', 'Use this team in case of fire only', 259, 259, '2025-11-27 21:32:37', '2025-11-27 21:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `sos_team_members`
--

DROP TABLE IF EXISTS `sos_team_members`;
CREATE TABLE IF NOT EXISTS `sos_team_members` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `emp_id` int UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_team_members_resort_id_foreign` (`resort_id`),
  KEY `sos_team_members_team_id_foreign` (`team_id`),
  KEY `sos_team_members_role_id_foreign` (`role_id`),
  KEY `sos_team_members_emp_id_foreign` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sos_team_members`
--

INSERT INTO `sos_team_members` (`id`, `resort_id`, `team_id`, `emp_id`, `role_id`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(82, 26, 44, 255, 20, 259, 259, '2025-11-27 21:32:37', '2025-11-27 21:32:37'),
(83, 26, 44, 260, 20, 259, 259, '2025-11-27 21:32:37', '2025-11-27 21:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `sos_team_member_activity`
--

DROP TABLE IF EXISTS `sos_team_member_activity`;
CREATE TABLE IF NOT EXISTS `sos_team_member_activity` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sos_history_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `emp_id` int UNSIGNED NOT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Acknowledged','Unacknowledged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unacknowledged',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sos_team_member_activity_sos_history_id_foreign` (`sos_history_id`),
  KEY `sos_team_member_activity_team_id_foreign` (`team_id`),
  KEY `sos_team_member_activity_emp_id_foreign` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=467 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
CREATE TABLE IF NOT EXISTS `states` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `states_country_id_foreign` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_consolidate_budget_children`
--

DROP TABLE IF EXISTS `store_consolidate_budget_children`;
CREATE TABLE IF NOT EXISTS `store_consolidate_budget_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_SCB_id` bigint UNSIGNED NOT NULL,
  `header` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `Data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_consolidate_budget_children_parent_scb_id_foreign` (`Parent_SCB_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_consolidate_budget_parents`
--

DROP TABLE IF EXISTS `store_consolidate_budget_parents`;
CREATE TABLE IF NOT EXISTS `store_consolidate_budget_parents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Year` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Resort_id` text COLLATE utf8mb4_unicode_ci,
  `file` varchar(70) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_manning_response_children`
--

DROP TABLE IF EXISTS `store_manning_response_children`;
CREATE TABLE IF NOT EXISTS `store_manning_response_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_SMRP_id` bigint UNSIGNED NOT NULL,
  `Emp_id` int UNSIGNED NOT NULL,
  `Current_Basic_salary` double(8,2) NOT NULL,
  `Proposed_Basic_salary` double(8,2) NOT NULL,
  `Months` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_manning_response_children_parent_smrp_id_foreign` (`Parent_SMRP_id`),
  KEY `store_manning_response_children_emp_id_foreign` (`Emp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `store_manning_response_children`
--

INSERT INTO `store_manning_response_children` (`id`, `Parent_SMRP_id`, `Emp_id`, `Current_Basic_salary`, `Proposed_Basic_salary`, `Months`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 6, 184, 550.00, 0.00, '[{\"month\":\"1\",\"salary\":\"180\"},{\"month\":\"2\",\"salary\":\"180\"},{\"month\":\"3\",\"salary\":\"180\"},{\"month\":\"4\",\"salary\":\"180\"},{\"month\":\"5\",\"salary\":\"180\"},{\"month\":\"6\",\"salary\":\"180\"},{\"month\":\"7\",\"salary\":\"180\"},{\"month\":\"8\",\"salary\":\"180\"},{\"month\":\"9\",\"salary\":\"180\"},{\"month\":\"10\",\"salary\":\"180\"},{\"month\":\"11\",\"salary\":\"180\"},{\"month\":\"12\",\"salary\":\"180\"}]', 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(2, 6, 188, 5000.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(3, 6, 179, 2200.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(4, 7, 176, 6500.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(5, 7, 177, 1200.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(6, 7, 174, 750.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(7, 7, 173, 600.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(8, 7, 180, 550.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(9, 7, 183, 650.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(10, 7, 186, 600.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(11, 7, 189, 350.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(12, 8, 171, 7500.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(13, 8, 182, 2500.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(14, 8, 170, 600.00, 0.00, NULL, 240, 259, '2025-11-15 13:50:13', '2025-12-12 23:38:02'),
(15, 9, 171, 7500.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(16, 9, 182, 2500.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(17, 9, 170, 600.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(18, 10, 176, 6500.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(19, 10, 177, 1200.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(20, 10, 174, 750.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(21, 10, 173, 600.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(22, 10, 180, 550.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(23, 10, 183, 650.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(24, 10, 186, 600.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(25, 10, 189, 350.00, 0.00, NULL, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(26, 11, 187, 8000.00, 0.00, NULL, 259, 259, '2025-12-03 19:53:33', '2025-12-07 21:22:47');

-- --------------------------------------------------------

--
-- Table structure for table `store_manning_response_parents`
--

DROP TABLE IF EXISTS `store_manning_response_parents`;
CREATE TABLE IF NOT EXISTS `store_manning_response_parents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `Budget_id` bigint UNSIGNED NOT NULL,
  `Department_id` int UNSIGNED NOT NULL,
  `Total_Department_budget` double(8,2) NOT NULL DEFAULT '0.00',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_manning_response_parents_resort_id_foreign` (`Resort_id`),
  KEY `store_manning_response_parents_budget_id_foreign` (`Budget_id`),
  KEY `store_manning_response_parents_department_id_foreign` (`Department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_manning_response_parents`
--

INSERT INTO `store_manning_response_parents` (`id`, `Resort_id`, `Budget_id`, `Department_id`, `Total_Department_budget`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(6, 26, 58, 78, 6660.00, 240, 259, '2025-11-15 13:50:13', '2025-12-05 13:48:32'),
(7, 26, 57, 80, 0.00, 240, 259, '2025-11-15 13:50:13', '2025-12-05 13:48:32'),
(8, 26, 56, 79, 0.00, 240, 259, '2025-11-15 13:50:13', '2025-12-05 13:48:32'),
(9, 26, 0, 79, 0.00, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(10, 26, 0, 80, 0.00, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47'),
(11, 26, 0, 81, 0.00, 259, 259, '2025-11-21 10:38:22', '2025-12-07 21:22:47');

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

DROP TABLE IF EXISTS `support`;
CREATE TABLE IF NOT EXISTS `support` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticketID` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resort_id` int UNSIGNED NOT NULL,
  `support_preference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `subject` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('New','On Hold','In Progress','Close') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New',
  `assigned_to` bigint UNSIGNED DEFAULT '1',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_resort_id_foreign` (`resort_id`),
  KEY `support_category_id_foreign` (`category_id`),
  KEY `support_assigned_to_foreign` (`assigned_to`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_categories`
--

DROP TABLE IF EXISTS `support_categories`;
CREATE TABLE IF NOT EXISTS `support_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_chat_messages`
--

DROP TABLE IF EXISTS `support_chat_messages`;
CREATE TABLE IF NOT EXISTS `support_chat_messages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `support_id` bigint UNSIGNED NOT NULL,
  `sender_id` bigint UNSIGNED NOT NULL,
  `sender_type` enum('admin','employee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_id` bigint UNSIGNED NOT NULL,
  `receiver_type` enum('admin','employee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `message_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `in_reply_to` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_email` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_chat_messages_message_id_unique` (`message_id`),
  KEY `support_chat_messages_support_id_foreign` (`support_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `sender` enum('admin','employee') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` bigint UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_messages_ticket_id_foreign` (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_employees`
--

DROP TABLE IF EXISTS `survey_employees`;
CREATE TABLE IF NOT EXISTS `survey_employees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_survey_id` bigint UNSIGNED NOT NULL,
  `Emp_id` int NOT NULL DEFAULT '7',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `emp_status` enum('yes','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `Complete_time` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_employees_parent_survey_id_foreign` (`Parent_survey_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_questions`
--

DROP TABLE IF EXISTS `survey_questions`;
CREATE TABLE IF NOT EXISTS `survey_questions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_survey_id` bigint UNSIGNED NOT NULL,
  `Question_Type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Total_Option_Json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `Question_Text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Question_Complusory` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yes',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_questions_parent_survey_id_foreign` (`Parent_survey_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_results`
--

DROP TABLE IF EXISTS `survey_results`;
CREATE TABLE IF NOT EXISTS `survey_results` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_survey_id` bigint UNSIGNED NOT NULL,
  `Survey_emp_ta_id` bigint UNSIGNED NOT NULL,
  `Question_id` bigint UNSIGNED NOT NULL,
  `Emp_Ans` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_results_parent_survey_id_foreign` (`Parent_survey_id`),
  KEY `survey_results_question_id_foreign` (`Question_id`),
  KEY `survey_results_survey_emp_ta_id_foreign` (`Survey_emp_ta_id`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ta_email_templates`
--

DROP TABLE IF EXISTS `ta_email_templates`;
CREATE TABLE IF NOT EXISTS `ta_email_templates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `TempleteName` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `MailTemplete` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `MailSubject` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Placeholders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  KEY `ta_email_templates_resort_id_foreign` (`Resort_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ta_email_templates`
--

INSERT INTO `ta_email_templates` (`id`, `Resort_id`, `TempleteName`, `MailTemplete`, `created_at`, `updated_at`, `MailSubject`, `Placeholders`) VALUES
(1, 26, 'Selection Email Template', '<p>Dear {{candidate_name}},</p>\n\n<p>You are shortlisted for the {{interview_round}} for {{position_title}} at {{resort_name}}.</p>\n\n<p>Interview type: {{interview_type}}</p>\n\n<p>Date: {{interview_date}}</p>\n\n<p>Time: {{interview_time}}</p>\n\n<p>Link: {{interview_link}}</p>\n\n<p>Department: {{department}}</p>\n\n<p>Please confirm your availability.</p>\n\n<p>&nbsp;</p>\n\n<p>Regards,</p>\n\n<p>HR Team</p>\n\n<p>{{resort_name}}</p>', '2025-12-08 10:14:52', '2025-12-08 10:14:52', 'Selection Confirmation – {{position_title}}', '[\"candidate_name\",\"interview_round\",\"position_title\",\"resort_name\",\"interview_type\",\"interview_date\",\"interview_time\",\"interview_link\",\"department\",\"resort_name\"]'),
(2, 26, 'Rejection Email Template', '<p>Dear {{candidate_name}},</p>\n\n<p>&nbsp;</p>\n\n<p>Thank you for applying for {{position_title}} at {{resort_name}}.</p>\n\n<p>&nbsp;</p>\n\n<p>We have completed the selection process on {{completion_date}}. At this time, you were not selected.</p>\n\n<p>&nbsp;</p>\n\n<p>We wish you success in your career ahead.</p>\n\n<p>&nbsp;</p>\n\n<p>Regards,</p>\n\n<p>HR Team</p>\n\n<p>{{resort_name}}</p>', '2025-12-08 10:15:39', '2025-12-08 10:15:39', 'Update on Your Application – {{position_title}}', '[\"candidate_name\",\"position_title\",\"resort_name\",\"completion_date\",\"resort_name\"]');

-- --------------------------------------------------------

--
-- Table structure for table `temp_language_video_store`
--

DROP TABLE IF EXISTS `temp_language_video_store`;
CREATE TABLE IF NOT EXISTS `temp_language_video_store` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `video` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `os` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ipAddress` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `temp_language_video_store_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terms_and_conditions`
--

DROP TABLE IF EXISTS `terms_and_conditions`;
CREATE TABLE IF NOT EXISTS `terms_and_conditions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `terms_and_condition` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `terms_and_conditions_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_agents`
--

DROP TABLE IF EXISTS `ticket_agents`;
CREATE TABLE IF NOT EXISTS `ticket_agents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `agents_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_agents_agents_email_unique` (`agents_email`),
  KEY `ticket_agents_resort_id_foreign` (`Resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_agents`
--

INSERT INTO `ticket_agents` (`id`, `Resort_id`, `agents_email`, `name`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(18, 26, 'ameytamshetty@gmail.com', 'AST Travels', 259, 259, '2025-11-23 10:47:33', '2025-11-23 10:47:33'),
(19, 26, 'amey.tamshetti@gmail.com', 'Flight Easy', 259, 259, '2025-11-23 10:48:24', '2025-11-23 10:48:24');

-- --------------------------------------------------------

--
-- Table structure for table `total_expensess_since_joings`
--

DROP TABLE IF EXISTS `total_expensess_since_joings`;
CREATE TABLE IF NOT EXISTS `total_expensess_since_joings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employees_id` int UNSIGNED NOT NULL,
  `Deposit_Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Total_work_permit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Total_slot_Payment` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Total_insurance_Payment` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Total_Work_Permit_Medical_Payment` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Date` date DEFAULT NULL,
  `Year` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Total_Visa_Payment` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `total_expensess_since_joings_employees_id_foreign` (`employees_id`),
  KEY `total_expensess_since_joings_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_attendance`
--

DROP TABLE IF EXISTS `training_attendance`;
CREATE TABLE IF NOT EXISTS `training_attendance` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_schedule_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `program_type` enum('scheduled','mandatory','requested','probationary') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  PRIMARY KEY (`id`),
  KEY `training_attendance_training_schedule_id_foreign` (`training_schedule_id`),
  KEY `training_attendance_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_feedback_form`
--

DROP TABLE IF EXISTS `training_feedback_form`;
CREATE TABLE IF NOT EXISTS `training_feedback_form` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `form_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_feedback_form_resort_id_foreign` (`resort_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_feedback_responses`
--

DROP TABLE IF EXISTS `training_feedback_responses`;
CREATE TABLE IF NOT EXISTS `training_feedback_responses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` int UNSIGNED NOT NULL,
  `training_id` int UNSIGNED NOT NULL,
  `participant_id` int UNSIGNED NOT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_feedback_responses_form_id_foreign` (`form_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_participants`
--

DROP TABLE IF EXISTS `training_participants`;
CREATE TABLE IF NOT EXISTS `training_participants` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_schedule_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `train_feedback_form_id` int UNSIGNED DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` enum('Pending','Present','Absent','Late') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_participants_employee_id_foreign` (`employee_id`),
  KEY `training_participants_training_schedule_id_foreign` (`training_schedule_id`),
  KEY `fk_train_feedback_form_id` (`train_feedback_form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_schedules`
--

DROP TABLE IF EXISTS `training_schedules`;
CREATE TABLE IF NOT EXISTS `training_schedules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `training_id` int UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('Scheduled','Ongoing','Completed','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `training_schedules_resort_id_foreign` (`resort_id`),
  KEY `training_schedules_training_id_foreign` (`training_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_accommodations`
--

DROP TABLE IF EXISTS `transfer_accommodations`;
CREATE TABLE IF NOT EXISTS `transfer_accommodations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `NewAccommodation_id` int DEFAULT NULL,
  `OldAccommodation_id` int DEFAULT NULL,
  `Reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `OldDate` date DEFAULT NULL,
  `NewdDate` date DEFAULT NULL,
  `Emp_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_accommodations_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_tickets`
--

DROP TABLE IF EXISTS `travel_tickets`;
CREATE TABLE IF NOT EXISTS `travel_tickets` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `leave_request_id` int UNSIGNED DEFAULT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `ticket_file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Sent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `uploaded_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `travel_tickets_resort_id_foreign` (`resort_id`),
  KEY `travel_tickets_leave_request_id_foreign` (`leave_request_id`),
  KEY `travel_tickets_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_anotification_children`
--

DROP TABLE IF EXISTS `t_anotification_children`;
CREATE TABLE IF NOT EXISTS `t_anotification_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Parent_ta_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('Hold','Approved','Rejected','Active','Expired','ForwardedToNext') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `holding_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `Approved_By` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `t_anotification_children_parent_ta_id_foreign` (`Parent_ta_id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_anotification_parents`
--

DROP TABLE IF EXISTS `t_anotification_parents`;
CREATE TABLE IF NOT EXISTS `t_anotification_parents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Resort_id` int UNSIGNED NOT NULL,
  `V_id` int UNSIGNED DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `t_anotification_parents_resort_id_foreign` (`Resort_id`),
  KEY `t_anotification_parents_v_id_foreign` (`V_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_pic` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vacancies`
--

DROP TABLE IF EXISTS `vacancies`;
CREATE TABLE IF NOT EXISTS `vacancies` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `budgeted` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Resort_id` int UNSIGNED NOT NULL,
  `department` int UNSIGNED NOT NULL,
  `required_starting_date` date NOT NULL,
  `position` int UNSIGNED NOT NULL,
  `reporting_to` int UNSIGNED NOT NULL,
  `rank` int NOT NULL,
  `division` int UNSIGNED NOT NULL,
  `section` int UNSIGNED DEFAULT NULL,
  `employee_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Total_position_required` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_provider_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_unit` enum('MVR','USD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MVR',
  `salary` decimal(12,2) DEFAULT NULL,
  `food` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accomodation` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transportation` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budgeted_salary` decimal(12,2) NOT NULL,
  `propsed_salary` decimal(12,2) NOT NULL,
  `budgeted_accomodation` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allowance` decimal(12,2) DEFAULT NULL,
  `service_charge` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `uniform` enum('YES','NO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `medical` decimal(12,2) DEFAULT NULL,
  `insurance` decimal(12,2) DEFAULT NULL,
  `pension` decimal(12,2) DEFAULT NULL,
  `recruitment` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required_local` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employee` int UNSIGNED DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vacancies_reporting_to_foreign` (`reporting_to`),
  KEY `vacancies_division_foreign` (`division`),
  KEY `vacancies_position_foreign` (`position`),
  KEY `vacancies_department_foreign` (`department`),
  KEY `vacancies_section_foreign` (`section`),
  KEY `vacancies_resort_id_foreign` (`Resort_id`),
  KEY `vacancies_employee_foreign` (`employee`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_questions`
--

DROP TABLE IF EXISTS `video_questions`;
CREATE TABLE IF NOT EXISTS `video_questions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Q_Parent_id` bigint UNSIGNED NOT NULL,
  `lang_id` bigint UNSIGNED NOT NULL,
  `VideoQuestion` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_questions_lang_id_foreign` (`lang_id`),
  KEY `video_questions_q_parent_id_foreign` (`Q_Parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_config_reminders`
--

DROP TABLE IF EXISTS `visa_config_reminders`;
CREATE TABLE IF NOT EXISTS `visa_config_reminders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Work_Permit_Fee_reminder` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InActive',
  `Work_Permit_Fee` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Slot_Fee_reminder` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InActive',
  `Slot_Fee` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Insurance` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Insurance_reminder` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InActive',
  `Medical_reminder` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InActive',
  `Medical` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Visa_reminder` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InActive',
  `Visa` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Passport` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Passport_reminder` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'InActive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_config_reminders_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_document_segmentations`
--

DROP TABLE IF EXISTS `visa_document_segmentations`;
CREATE TABLE IF NOT EXISTS `visa_document_segmentations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `document_id` bigint UNSIGNED NOT NULL,
  `DocumentName` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_document_segmentations_document_id_foreign` (`document_id`),
  KEY `visa_document_segmentations_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_document_types`
--

DROP TABLE IF EXISTS `visa_document_types`;
CREATE TABLE IF NOT EXISTS `visa_document_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `documentname` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_document_types_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_employee_expiry_data`
--

DROP TABLE IF EXISTS `visa_employee_expiry_data`;
CREATE TABLE IF NOT EXISTS `visa_employee_expiry_data` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `File_child_id` bigint UNSIGNED DEFAULT NULL,
  `DocumentName` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Ai_extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_employee_expiry_data_resort_id_foreign` (`resort_id`),
  KEY `visa_employee_expiry_data_employee_id_foreign` (`employee_id`),
  KEY `visa_employee_expiry_data_file_child_id_foreign` (`File_child_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_fee_amounts`
--

DROP TABLE IF EXISTS `visa_fee_amounts`;
CREATE TABLE IF NOT EXISTS `visa_fee_amounts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `nationality` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AmountbeforExp` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AmountafterExp` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_fee_amounts_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_nationalities`
--

DROP TABLE IF EXISTS `visa_nationalities`;
CREATE TABLE IF NOT EXISTS `visa_nationalities` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `nationality` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amt` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_nationalities_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_renewals`
--

DROP TABLE IF EXISTS `visa_renewals`;
CREATE TABLE IF NOT EXISTS `visa_renewals` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Visa_Number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `WP_No` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `visa_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_renewals_resort_id_foreign` (`resort_id`),
  KEY `visa_renewals_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_renewal_children`
--

DROP TABLE IF EXISTS `visa_renewal_children`;
CREATE TABLE IF NOT EXISTS `visa_renewal_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `visa_renewal_id` bigint UNSIGNED NOT NULL,
  `Visa_Number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `WP_No` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `visa_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_renewal_children_visa_renewal_id_foreign` (`visa_renewal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_transection_histories`
--

DROP TABLE IF EXISTS `visa_transection_histories`;
CREATE TABLE IF NOT EXISTS `visa_transection_histories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_wallet` bigint UNSIGNED DEFAULT NULL,
  `from_wallet` bigint UNSIGNED DEFAULT NULL,
  `Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `to_wallet_realAmt` decimal(15,2) DEFAULT NULL,
  `from_wallet_realAmt` decimal(15,2) DEFAULT NULL,
  `Employee_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Payment_Date` date DEFAULT NULL,
  `file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `visa_transection_histories_transaction_id_unique` (`transaction_id`),
  KEY `visa_transection_histories_to_wallet_foreign` (`to_wallet`),
  KEY `visa_transection_histories_from_wallet_foreign` (`from_wallet`),
  KEY `visa_transection_histories_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_wallets`
--

DROP TABLE IF EXISTS `visa_wallets`;
CREATE TABLE IF NOT EXISTS `visa_wallets` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `WalletName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Payment_Date` date DEFAULT NULL,
  `Status` enum('Active','InActive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_wallets_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_xpact_amounts`
--

DROP TABLE IF EXISTS `visa_xpact_amounts`;
CREATE TABLE IF NOT EXISTS `visa_xpact_amounts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `Xpact_WalletName` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Xpact_Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `Xpact_Payment_Date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visa_xpact_amounts_resort_id_foreign` (`resort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `websockets_statistics_entries`
--

DROP TABLE IF EXISTS `websockets_statistics_entries`;
CREATE TABLE IF NOT EXISTS `websockets_statistics_entries` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peak_connection_count` int NOT NULL,
  `websocket_message_count` int NOT NULL,
  `api_message_count` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_experience_applicant_form`
--

DROP TABLE IF EXISTS `work_experience_applicant_form`;
CREATE TABLE IF NOT EXISTS `work_experience_applicant_form` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_form_id` bigint UNSIGNED NOT NULL,
  `job_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_country_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_work_exp` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_start_date` date DEFAULT NULL,
  `work_end_date` date DEFAULT NULL,
  `job_description_work` text COLLATE utf8mb4_unicode_ci,
  `currently_working` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_experience_applicant_form_applicant_form_id_foreign` (`applicant_form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_permits`
--

DROP TABLE IF EXISTS `work_permits`;
CREATE TABLE IF NOT EXISTS `work_permits` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Month` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Currency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Payment_Date` date DEFAULT NULL,
  `Due_Date` date DEFAULT NULL,
  `Work_Permit_Number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Status` enum('Paid','Unpaid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unpaid',
  `Reciept_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ReceiptNumber` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PaymentType` enum('Lumpsum','Installment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Installment',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_permits_resort_id_foreign` (`resort_id`),
  KEY `work_permits_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_permit_medical_renewals`
--

DROP TABLE IF EXISTS `work_permit_medical_renewals`;
CREATE TABLE IF NOT EXISTS `work_permit_medical_renewals` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_id` int UNSIGNED NOT NULL,
  `employee_id` int UNSIGNED NOT NULL,
  `Reference_Number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Cost` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Currency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Medical_Center_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `medical_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_permit_medical_renewals_resort_id_foreign` (`resort_id`),
  KEY `work_permit_medical_renewals_employee_id_foreign` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_permit_medical_renewal_children`
--

DROP TABLE IF EXISTS `work_permit_medical_renewal_children`;
CREATE TABLE IF NOT EXISTS `work_permit_medical_renewal_children` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `permit_medical_id` bigint UNSIGNED NOT NULL,
  `Reference_Number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Cost` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Medical_Center_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `medical_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Amt` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_permit_medical_renewal_children_permit_medical_id_foreign` (`permit_medical_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accommodation_types`
--
ALTER TABLE `accommodation_types`
  ADD CONSTRAINT `accommodation_types_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `action_stores`
--
ALTER TABLE `action_stores`
  ADD CONSTRAINT `action_stores_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`);

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_title_foreign` FOREIGN KEY (`title`) REFERENCES `announcement_category` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_category`
--
ALTER TABLE `announcement_category`
  ADD CONSTRAINT `announcement_category_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_notification`
--
ALTER TABLE `announcement_notification`
  ADD CONSTRAINT `announcement_notification_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_notification_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_notification_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applicant_inter_view_details`
--
ALTER TABLE `applicant_inter_view_details`
  ADD CONSTRAINT `applicant_inter_view_details_applicant_id_foreign` FOREIGN KEY (`Applicant_id`) REFERENCES `applicant_form_data` (`id`),
  ADD CONSTRAINT `applicant_inter_view_details_applicantstatus_id_foreign` FOREIGN KEY (`ApplicantStatus_id`) REFERENCES `applicant_wise_statuses` (`id`),
  ADD CONSTRAINT `applicant_inter_view_details_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`);

--
-- Constraints for table `applicant_languages`
--
ALTER TABLE `applicant_languages`
  ADD CONSTRAINT `applicant_languages_applicant_form_id_foreign` FOREIGN KEY (`applicant_form_id`) REFERENCES `applicant_form_data` (`id`);

--
-- Constraints for table `applicant_wise_statuses`
--
ALTER TABLE `applicant_wise_statuses`
  ADD CONSTRAINT `applicant_wise_statuses_applicant_id_foreign` FOREIGN KEY (`Applicant_id`) REFERENCES `applicant_form_data` (`id`);

--
-- Constraints for table `application_links`
--
ALTER TABLE `application_links`
  ADD CONSTRAINT `application_links_resort_id_foreign` FOREIGN KEY (`Resort_id`) REFERENCES `resorts` (`id`),
  ADD CONSTRAINT `application_links_ta_child_id_foreign` FOREIGN KEY (`ta_child_id`) REFERENCES `t_anotification_children` (`id`);

--
-- Constraints for table `assing_accommodations`
--
ALTER TABLE `assing_accommodations`
  ADD CONSTRAINT `assing_accommodations_available_a_id_foreign` FOREIGN KEY (`available_a_id`) REFERENCES `available_accommodation_models` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assing_accommodations_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_parameters`
--
ALTER TABLE `attendance_parameters`
  ADD CONSTRAINT `attendance_parameters_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_file_id_foreign` FOREIGN KEY (`file_id`) REFERENCES `child_file_management` (`id`),
  ADD CONSTRAINT `audit_logs_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`);

--
-- Constraints for table `available_accommodation_inv_items`
--
ALTER TABLE `available_accommodation_inv_items`
  ADD CONSTRAINT `available_accommodation_inv_items_available_acc_id_foreign` FOREIGN KEY (`Available_Acc_id`) REFERENCES `available_accommodation_models` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `available_accommodation_inv_items_item_id_foreign` FOREIGN KEY (`Item_id`) REFERENCES `inventory_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `available_accommodation_models`
--
ALTER TABLE `available_accommodation_models`
  ADD CONSTRAINT `available_accommodation_models_accommodation_type_id_foreign` FOREIGN KEY (`Accommodation_type_id`) REFERENCES `accommodation_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `available_accommodation_models_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_hours`
--
ALTER TABLE `business_hours`
  ADD CONSTRAINT `business_hours_resort_id_foreign` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
