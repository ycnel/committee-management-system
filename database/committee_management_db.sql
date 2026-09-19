-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2026 at 09:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `committee_management_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `session_duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `session_duration_seconds`, `created_at`) VALUES
(1, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-07-27 15:49:27'),
(2, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-27 15:49:43'),
(3, 1, 'Insert', 'Created committee #1 (Lansoy)', NULL, NULL, NULL, '2026-07-27 15:52:59'),
(4, 1, 'Insert', 'Created committee #2 (Nath)', NULL, NULL, NULL, '2026-07-27 16:12:53'),
(5, 1, 'Insert', 'Created committee #3 (boknoy)', NULL, NULL, NULL, '2026-07-27 16:13:05'),
(6, 1, 'Update', 'Updated committee #3 (boknoy)', NULL, NULL, NULL, '2026-07-27 16:13:14'),
(7, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-07-27 16:16:14'),
(8, 3, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-27 16:16:39'),
(9, 3, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-07-27 16:16:50'),
(10, 2, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-27 16:17:10'),
(11, 2, 'Insert', 'Created committee #4 (Hatdog)', NULL, NULL, NULL, '2026-07-27 16:17:53'),
(12, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-07-27 16:18:34'),
(13, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-27 16:18:47'),
(14, 1, 'Insert', 'Saved performance snapshot for \"boknoy\" (July 2026).', NULL, NULL, NULL, '2026-07-27 16:19:33'),
(15, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-07-27 16:20:26'),
(16, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-27 17:32:09'),
(17, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-07-27 21:53:22'),
(18, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-27 21:53:54'),
(19, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-07-28 13:04:16'),
(20, 1, 'Insert', 'Created task #16 (TEST)', NULL, NULL, NULL, '2026-07-28 13:05:00'),
(21, 1, 'Export', 'Exported Committee Report Excel (all committees)', NULL, NULL, NULL, '2026-07-28 16:27:40'),
(22, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-03 13:27:08'),
(23, 1, 'Update', 'Marked task #16 (TEST) as completed.', NULL, NULL, NULL, '2026-08-03 13:27:41'),
(24, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-03 13:28:18'),
(25, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-03 16:52:13'),
(26, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-03 16:52:20'),
(27, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-03 16:52:36'),
(28, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-03 16:54:08'),
(29, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-06 14:55:10'),
(30, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-06 14:55:18'),
(31, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-07 06:29:26'),
(32, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-07 11:15:35'),
(33, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-07 11:54:42'),
(34, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-07 12:10:00'),
(35, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-07 12:10:15'),
(36, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-07 12:10:28'),
(37, 1, 'Update', 'Updated Smart AI Workload Distribution factor weights.', NULL, NULL, NULL, '2026-08-07 14:11:02'),
(38, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-07 16:18:03'),
(39, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-07 21:39:40'),
(40, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 08:09:31'),
(41, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-08 08:36:39'),
(42, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 08:37:05'),
(43, 1, 'Update', 'Updated user #6 (lance@cmas.local)', NULL, NULL, NULL, '2026-08-08 09:22:13'),
(44, 1, 'Update', 'Updated user #3 (reyes@cmas.local)', NULL, NULL, NULL, '2026-08-08 09:22:44'),
(45, 1, 'Update', 'Updated user #9 (member7@cmas.local)', NULL, NULL, NULL, '2026-08-08 09:22:53'),
(46, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-08 09:23:08'),
(47, 6, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 09:23:15'),
(48, 6, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-08 09:24:44'),
(49, NULL, 'Login Failed', 'Email: princess@cmas.com', NULL, NULL, NULL, '2026-08-08 09:24:56'),
(50, NULL, 'Login Failed', 'Email: princess@cmas.local', NULL, NULL, NULL, '2026-08-08 09:25:10'),
(51, NULL, 'Login Failed', 'Email: princess@cmas.local', NULL, NULL, NULL, '2026-08-08 09:25:22'),
(52, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-08 09:25:42'),
(53, NULL, 'Login Failed', 'Email: princess@cmas.com', NULL, NULL, NULL, '2026-08-08 09:26:01'),
(54, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-08 09:26:22'),
(55, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 09:26:40'),
(56, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-08 09:26:57'),
(57, 3, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 09:27:08'),
(58, 3, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-08 09:27:43'),
(59, 6, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 09:27:52'),
(60, 6, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-08 09:28:52'),
(61, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-08 09:28:58'),
(62, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-08 09:29:05'),
(63, 1, 'Insert', 'Saved performance snapshot for \"Committee on Budget and Appropriations\" (August 2026).', NULL, NULL, NULL, '2026-08-08 10:03:23'),
(64, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-10 23:33:07'),
(65, 1, 'Update', 'Updated committee #6 (Committee on Budget and Appropriations)', NULL, NULL, NULL, '2026-08-10 23:34:10'),
(66, 1, 'Insert', 'Created task #17 (TEST)', NULL, NULL, NULL, '2026-08-10 23:35:16'),
(67, 1, 'Export', 'Exported Committee Report PDF (all committees)', NULL, NULL, NULL, '2026-08-11 00:17:40'),
(68, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-11 09:41:20'),
(69, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-11 09:41:27'),
(70, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 09:41:34'),
(71, 1, 'Insert', 'Saved performance snapshot for \"boknoy\" (August 2026).', NULL, NULL, NULL, '2026-08-11 11:01:23'),
(72, 1, 'Insert', 'Saved performance snapshot for \"Nath\" (August 2026).', NULL, NULL, NULL, '2026-08-11 11:01:38'),
(73, 1, 'Delete', 'Deleted task #17 (TEST)', NULL, NULL, NULL, '2026-08-11 15:43:13'),
(74, 1, 'Delete', 'Deleted task #16 (TEST)', NULL, NULL, NULL, '2026-08-11 15:43:16'),
(75, 1, 'Update', 'Marked task #2 (Inspect barangay health centers) as completed.', NULL, NULL, NULL, '2026-08-11 15:43:21'),
(76, 1, 'Update', 'Marked task #8 (Review curfew ordinance complaints) as completed.', NULL, NULL, NULL, '2026-08-11 15:43:22'),
(77, 1, 'Update', 'Marked task #11 (Review road widening proposal) as completed.', NULL, NULL, NULL, '2026-08-11 15:43:24'),
(78, 1, 'Update', 'Marked task #4 (Review supplemental budget request) as completed.', NULL, NULL, NULL, '2026-08-11 15:43:26'),
(79, 1, 'Update', 'Marked task #7 (Coordinate disaster preparedness drill) as completed.', NULL, NULL, NULL, '2026-08-11 15:43:28'),
(80, 1, 'Update', 'Marked task #1 (Draft sanitation ordinance revision) as completed.', NULL, NULL, NULL, '2026-08-11 15:43:30'),
(81, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 19:32:23'),
(82, 1, 'Delete', 'Deleted committee #3 (boknoy)', NULL, NULL, NULL, '2026-08-11 19:42:40'),
(83, 1, 'Delete', 'Deleted committee #4 (Hatdog)', NULL, NULL, NULL, '2026-08-11 19:42:49'),
(84, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 19:43:15'),
(85, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-11 19:45:05'),
(86, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-11 19:45:11'),
(87, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 19:45:19'),
(88, 1, 'Delete', 'Deleted committee #1 (Lansoy)', NULL, NULL, NULL, '2026-08-11 19:57:17'),
(89, 1, 'Delete', 'Deleted committee #2 (Nath)', NULL, NULL, NULL, '2026-08-11 19:57:20'),
(90, 1, 'Insert', 'Created committee #9 (Committee on Public Works and Infrastructure)', NULL, NULL, NULL, '2026-08-11 19:59:03'),
(91, 1, 'Insert', 'Princess Ann Reyes assigned to committee \"Committee on Public Works and Infrastructure\" as Chairperson.', NULL, NULL, NULL, '2026-08-11 20:02:26'),
(92, 1, 'Insert', 'Lance Lerin assigned to committee \"Committee on Public Works and Infrastructure\" as Vice Chairperson.', NULL, NULL, NULL, '2026-08-11 20:02:38'),
(93, 1, 'Insert', 'Created user #11 (mestiolalei@gmail.com)', NULL, NULL, NULL, '2026-08-11 20:03:32'),
(94, 1, 'Insert', 'Nathaniel Lei Mestiola assigned to committee \"Committee on Public Works and Infrastructure\" as Member.', NULL, NULL, NULL, '2026-08-11 20:03:51'),
(95, 1, 'Insert', 'Created task #18 (Road Condition Assessment and Repair Planning)', NULL, NULL, NULL, '2026-08-11 20:08:18'),
(96, 1, 'Update', 'Updated task #1 (Draft sanitation ordinance revision)', NULL, NULL, NULL, '2026-08-11 20:08:48'),
(97, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 20:15:07'),
(98, 3, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 20:15:14'),
(99, 3, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 20:19:41'),
(100, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-11 20:19:49'),
(101, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 20:19:56'),
(102, 1, 'Insert', 'Saved performance snapshot for \"Committee on Budget and Appropriations\" (August 2026).', NULL, NULL, NULL, '2026-08-11 20:20:50'),
(103, 1, 'Insert', 'Created jurisdiction #5 (Environmental Management)', NULL, NULL, NULL, '2026-08-11 20:26:28'),
(104, 1, 'Export', 'Exported Committee Report PDF (committee #8)', NULL, NULL, NULL, '2026-08-11 20:29:23'),
(105, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 20:30:50'),
(106, NULL, 'Login Failed', 'Email: reyes@cmas.local', NULL, NULL, NULL, '2026-08-11 20:31:16'),
(107, NULL, 'Login Failed', 'Email: reyes@cmas.local', NULL, NULL, NULL, '2026-08-11 20:31:22'),
(108, NULL, 'Login Failed', 'Email: reyes@cmas.local', NULL, NULL, NULL, '2026-08-11 20:31:32'),
(109, 3, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 20:31:42'),
(110, 3, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 20:32:36'),
(111, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-11 20:32:46'),
(112, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 20:32:55'),
(113, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 20:46:19'),
(114, 11, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 20:46:24'),
(115, 11, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-11 21:03:14'),
(116, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-11 21:03:25'),
(117, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-12 17:33:44'),
(118, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-12 17:34:00'),
(119, 1, 'Login', 'User logged in successfully.', NULL, NULL, NULL, '2026-08-17 20:40:16'),
(120, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-20 06:56:14'),
(121, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-20 06:56:23'),
(122, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-20 06:59:34'),
(123, NULL, 'Login Failed', 'Email: lance@cmas.local', NULL, NULL, NULL, '2026-08-20 06:59:59'),
(124, 6, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-20 07:00:49'),
(125, 6, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-20 07:01:06'),
(126, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-22 08:08:26'),
(127, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-22 08:08:36'),
(128, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-22 08:08:53'),
(129, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-08-22 08:10:08'),
(130, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-22 08:10:26'),
(131, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-22 08:10:31'),
(132, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-22 12:17:43'),
(133, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-22 12:17:57'),
(134, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-22 12:18:02'),
(135, 1, 'Insert', 'Created task #19 (TEST)', NULL, NULL, NULL, '2026-08-22 12:20:24'),
(136, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-22 12:26:22'),
(137, 6, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-22 12:26:35'),
(138, 6, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-22 12:26:43'),
(139, 6, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-22 12:27:41'),
(140, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-22 12:27:53'),
(141, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-22 12:28:05'),
(142, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-22 12:28:15'),
(143, NULL, 'Account Locked', 'Email: admin@cmas.local locked for 5 minutes after repeated failed attempts.', NULL, NULL, NULL, '2026-08-22 12:28:15'),
(144, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-22 12:39:10'),
(145, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-22 12:39:17'),
(146, 1, 'Insert', 'Saved performance snapshot for \"Committee on Public Works and Infrastructure\" (August 2026).', NULL, NULL, NULL, '2026-08-22 12:51:36'),
(147, 1, 'Update', 'Updated Smart AI Workload Distribution factor weights.', NULL, NULL, NULL, '2026-08-22 12:53:59'),
(148, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-22 14:09:57'),
(149, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-22 14:10:46'),
(150, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-22 14:11:14'),
(151, 1, 'Insert', 'Saved performance snapshot for \"Committee on Peace and Order\" (August 2026).', NULL, NULL, NULL, '2026-08-22 14:27:57'),
(152, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-24 14:31:05'),
(153, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-24 14:31:17'),
(154, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-24 14:31:31'),
(155, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-24 14:31:42'),
(156, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-24 22:19:40'),
(157, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-26 11:42:32'),
(158, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', NULL, NULL, NULL, '2026-08-26 11:45:24'),
(159, NULL, 'Login Failed', 'Email: lancyhr1@gmail.com', NULL, NULL, NULL, '2026-08-26 11:46:49'),
(160, NULL, 'Login Failed', 'Email: lancyhr1@gmail.com', NULL, NULL, NULL, '2026-08-26 11:47:05'),
(161, NULL, 'Login Failed', 'Email: sdfsd', NULL, NULL, NULL, '2026-08-26 11:47:29'),
(162, NULL, 'Login Failed', 'Email: dsdfsdf', NULL, NULL, NULL, '2026-08-26 11:48:22'),
(163, NULL, 'Login Failed', 'Email: sdfs', NULL, NULL, NULL, '2026-08-26 11:48:34'),
(164, NULL, 'Login Failed', 'Email: sfdsdq', NULL, NULL, NULL, '2026-08-26 11:48:58'),
(165, NULL, 'Login Failed', 'Email: sdfq', NULL, NULL, NULL, '2026-08-26 11:49:21'),
(166, NULL, 'Login Failed', 'Email: sdf', NULL, NULL, NULL, '2026-08-26 11:49:50'),
(167, NULL, 'Login Failed', 'Email: sdfs', NULL, NULL, NULL, '2026-08-26 11:50:13'),
(168, NULL, 'Login Failed', 'Email: sdfdsf', NULL, NULL, NULL, '2026-08-26 11:50:31'),
(169, NULL, 'Login Failed', 'Email: sdsdfsd', NULL, NULL, NULL, '2026-08-26 11:50:34'),
(170, NULL, 'Login Failed', 'Email: asdf', NULL, NULL, NULL, '2026-08-26 11:50:38'),
(171, NULL, 'Login Failed', 'Email: asdf', NULL, NULL, NULL, '2026-08-26 11:50:41'),
(172, NULL, 'Login Failed', 'Email: adsf', NULL, NULL, NULL, '2026-08-26 11:50:45'),
(173, NULL, 'Login Failed', 'Email: asdf', NULL, NULL, NULL, '2026-08-26 11:50:50'),
(174, NULL, 'Account Locked', 'Email: asdf locked for 5 minutes after repeated failed attempts.', NULL, NULL, NULL, '2026-08-26 11:50:50'),
(175, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-26 11:51:49'),
(176, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-26 11:51:55'),
(177, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-26 11:53:43'),
(178, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-26 11:55:47'),
(179, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-26 11:55:55'),
(180, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-27 14:04:38'),
(181, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-27 14:04:45'),
(182, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-27 14:39:02'),
(183, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-27 14:40:11'),
(184, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-27 14:40:23'),
(185, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-27 14:40:30'),
(186, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-27 14:44:54'),
(187, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-08-27 16:30:07'),
(188, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', NULL, NULL, NULL, '2026-08-27 16:31:05'),
(189, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', NULL, NULL, NULL, '2026-08-27 16:31:15'),
(190, NULL, 'Account Locked', 'Email: lerinlance88@gmail.com locked for 5 minutes after repeated failed attempts.', NULL, NULL, NULL, '2026-08-27 16:31:15'),
(191, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-27 16:41:07'),
(192, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-27 16:41:19'),
(193, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-27 17:05:02'),
(194, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-27 17:05:23'),
(195, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-27 17:05:30'),
(196, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-27 19:13:16'),
(197, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-27 19:19:49'),
(198, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-27 19:19:56'),
(199, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-28 00:13:37'),
(200, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-28 00:14:03'),
(201, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-28 00:14:09'),
(202, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-28 01:26:29'),
(203, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-28 01:42:10'),
(204, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-28 01:42:15'),
(205, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-28 08:33:38'),
(206, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-28 08:37:24'),
(207, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-28 08:37:28'),
(208, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-08-28 08:40:45'),
(209, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-28 10:16:05'),
(210, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-28 10:16:10'),
(211, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-28 19:36:31'),
(212, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-28 19:36:37'),
(213, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-29 20:41:09'),
(214, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-29 20:41:13'),
(215, 1, 'Insert', 'Created task #20 (Road Condition Assessment and Repair Planning)', NULL, NULL, NULL, '2026-08-29 21:02:34'),
(216, 1, 'Delete', 'Deleted task #10 (Inspect drainage rehabilitation project)', NULL, NULL, NULL, '2026-08-29 21:03:12'),
(217, 1, 'Update', 'Updated task #3 (Compile Q2 health program report)', NULL, NULL, NULL, '2026-08-29 21:05:37'),
(218, 1, 'Insert', 'Created task #21 (Anti Riot in Evening)', NULL, NULL, NULL, '2026-08-29 21:07:26'),
(219, 1, 'Update', 'Marked task #21 (Anti Riot in Evening) as completed.', NULL, NULL, NULL, '2026-08-29 21:07:44'),
(220, 1, 'Update', 'Marked task #5 (Prepare fund utilization summary) as completed.', NULL, NULL, NULL, '2026-08-29 21:08:02'),
(221, 1, 'Update', 'Marked task #1 (Draft sanitation ordinance revision) as completed.', NULL, NULL, NULL, '2026-08-29 21:08:03'),
(222, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-30 18:14:17'),
(223, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-30 18:14:22'),
(224, 1, 'Insert', 'Created task #22 (Draft sanitation ordinance revision)', NULL, NULL, NULL, '2026-08-30 18:16:06'),
(225, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-08-31 13:09:52'),
(226, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-08-31 13:09:56'),
(227, 1, 'Insert', 'Created task #23 (Free Check Up for Adults)', NULL, NULL, NULL, '2026-08-31 13:23:41'),
(228, 1, 'Export', 'Exported Committee Report Excel (all committees)', NULL, NULL, NULL, '2026-08-31 13:46:31'),
(229, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-01 18:06:42'),
(230, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-01 18:06:48'),
(231, 1, 'Update', 'Marked task #3 (Compile Q2 health program report) as completed.', NULL, NULL, NULL, '2026-09-01 22:16:01'),
(232, 1, 'Update', 'Marked task #23 (Free Check Up for Adults) as completed.', NULL, NULL, NULL, '2026-09-01 22:16:02'),
(233, 1, 'Insert', 'Saved performance snapshot for \"Committee on Budget and Appropriations\" (September 2026).', NULL, NULL, NULL, '2026-09-01 22:58:40'),
(234, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-01 23:07:12'),
(235, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-01 23:07:32'),
(236, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-01 23:07:48'),
(237, 1, 'Delete', 'Deleted task #12 (Close out streetlight installation project)', NULL, NULL, NULL, '2026-09-01 23:08:36'),
(238, 1, 'Delete', 'Deleted task #9 (Submit crime statistics report)', NULL, NULL, NULL, '2026-09-01 23:12:33'),
(239, 1, 'Insert', 'Created task #24 (Submit crime statistics report)', NULL, NULL, NULL, '2026-09-01 23:13:51'),
(240, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 04:50:20'),
(241, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-02 04:50:35'),
(242, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 04:52:56'),
(243, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 04:53:01'),
(244, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 04:56:46'),
(245, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 04:56:57'),
(246, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 04:57:00'),
(247, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 04:58:40'),
(248, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 04:59:04'),
(249, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 04:59:09'),
(250, 2, 'Delete', 'Deleted task #6 (Finalize FY budget hearing schedule)', NULL, NULL, NULL, '2026-09-02 04:59:58'),
(251, 2, 'Insert', 'Created task #25 (Finalize FY budget hearing schedule)', NULL, NULL, NULL, '2026-09-02 05:00:46'),
(252, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 05:08:46'),
(253, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 05:08:57'),
(254, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 05:09:01'),
(255, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 05:10:13'),
(256, 5, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 05:10:24'),
(257, 5, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 05:10:29'),
(258, 5, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 05:11:42'),
(259, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 05:11:54'),
(260, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 05:11:58'),
(261, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:13:27'),
(262, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:13:31'),
(263, NULL, 'Login Failed', 'Email: staff@cmas.local', NULL, NULL, NULL, '2026-09-02 18:14:12'),
(264, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:14:26'),
(265, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:14:30'),
(266, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 18:14:48'),
(267, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:15:05'),
(268, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:15:08'),
(269, 2, 'Delete', 'Deleted task #2 (Inspect barangay health centers)', NULL, NULL, NULL, '2026-09-02 18:16:54'),
(270, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 18:17:26'),
(271, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:17:38'),
(272, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:17:41'),
(273, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 18:18:18'),
(274, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:18:33'),
(275, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:18:36'),
(276, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 18:20:44'),
(277, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:38:58'),
(278, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:39:05'),
(279, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-02 18:39:12'),
(280, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-02 18:39:26'),
(281, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-02 18:39:33'),
(282, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-03 20:38:13'),
(283, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-03 20:38:18'),
(284, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-03 20:38:35'),
(285, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-03 20:38:49'),
(286, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-03 20:38:53'),
(287, NULL, 'Login Failed', 'Email: staff@cmas.local', NULL, NULL, NULL, '2026-09-04 14:08:17'),
(288, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-04 14:08:32'),
(289, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-04 14:08:42'),
(290, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-04 14:53:15'),
(291, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-04 14:53:20'),
(292, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-04 14:53:28'),
(293, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-04 14:53:32'),
(294, NULL, 'Account Locked', 'Email: admin@cmas.local locked for 5 minutes after repeated failed attempts.', NULL, NULL, NULL, '2026-09-04 14:53:32'),
(295, NULL, 'Login Failed', 'Email: staff@cmas.local', NULL, NULL, NULL, '2026-09-04 14:54:24'),
(296, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-04 14:54:33'),
(297, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-04 14:54:38'),
(298, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-04 16:39:52'),
(299, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-04 16:40:00'),
(300, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-04 16:40:05'),
(301, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-04 17:08:08'),
(302, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-04 17:08:41'),
(303, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-04 17:08:45'),
(304, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-04 17:19:31'),
(305, NULL, 'Login Failed', 'Email: member7@cmas.local', NULL, NULL, NULL, '2026-09-04 17:19:43'),
(306, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:36:03'),
(307, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:37:27'),
(308, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:37:32'),
(309, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:39:14'),
(310, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:39:21'),
(311, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:39:28'),
(312, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:45:15'),
(313, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:45:27'),
(314, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:45:38'),
(315, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:45:56'),
(316, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:46:20'),
(317, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:46:50'),
(318, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:48:03'),
(319, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:48:22'),
(320, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:48:32'),
(321, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:49:36'),
(322, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:49:48'),
(323, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:49:59'),
(324, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:50:10'),
(325, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 16:56:33'),
(326, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 16:56:43'),
(327, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 16:56:52'),
(328, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 17:16:27'),
(329, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 17:16:34'),
(330, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 18:57:20'),
(331, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 19:28:53'),
(332, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 19:29:00'),
(333, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 21:21:27'),
(334, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-07 22:21:34'),
(335, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 22:21:49'),
(336, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 22:21:56'),
(337, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-07 22:57:17'),
(338, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-07 22:59:57'),
(339, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-07 23:00:05'),
(340, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-08 02:07:37'),
(341, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 02:07:49'),
(342, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-08 02:07:57'),
(343, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-08 02:18:52'),
(344, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 02:19:23'),
(345, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 02:23:25'),
(346, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 02:24:09'),
(347, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-08 02:24:17'),
(348, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-08 02:25:00'),
(349, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 02:25:19'),
(350, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-08 02:25:27'),
(351, 2, 'Update', 'Marked task #19 (TEST) as completed.', NULL, NULL, NULL, '2026-09-08 02:41:17'),
(352, 2, 'Update', 'Marked task #18 (Road Condition Assessment and Repair Planning) as completed.', NULL, NULL, NULL, '2026-09-08 02:41:41'),
(353, 2, 'Insert', 'Created task #26 (Road Condition Assessment and Repair Planning)', NULL, NULL, NULL, '2026-09-08 02:56:17'),
(354, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 11:55:58'),
(355, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-08 11:56:08'),
(356, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-08 11:58:08'),
(357, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 12:01:03'),
(358, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-08 12:01:15'),
(359, NULL, 'Login Failed', 'Email: staff@cmas.local', NULL, NULL, NULL, '2026-09-08 22:42:46'),
(360, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-08 22:42:58'),
(361, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-08 22:43:07'),
(362, 2, 'Update', 'Marked task #24 (Submit crime statistics report) as completed.', NULL, NULL, NULL, '2026-09-08 22:50:36'),
(363, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-09 00:08:29'),
(364, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-09 00:08:47'),
(365, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-09 00:09:17'),
(366, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-09 00:09:37'),
(367, 2, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-09-09 00:10:20'),
(368, 2, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-09 00:10:27'),
(369, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-09 00:10:34'),
(370, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-09 11:36:55'),
(371, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-09 11:37:03'),
(372, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:16:15'),
(373, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-11 21:16:57'),
(374, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:17:10'),
(375, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-11 21:17:20'),
(376, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:18:59'),
(377, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-11 21:19:08'),
(378, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:24:58'),
(379, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-11 21:25:07'),
(380, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:25:29'),
(381, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-11 21:25:36'),
(382, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:30:55'),
(383, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-11 21:31:03'),
(384, 2, 'Insert', 'Created task #27 (Road Condition Assessment and Repair Planning)', NULL, NULL, NULL, '2026-09-11 21:34:13'),
(385, 2, 'Update', 'Marked task #20 (Road Condition Assessment and Repair Planning) as completed.', NULL, NULL, NULL, '2026-09-11 21:34:48'),
(386, 2, 'Update', 'Marked task #22 (Draft sanitation ordinance revision) as completed.', NULL, NULL, NULL, '2026-09-11 21:36:30'),
(387, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-11 21:42:05'),
(388, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-11 21:42:25'),
(389, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-11 21:42:34'),
(390, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-15 13:59:42'),
(391, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-15 14:00:05'),
(392, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-16 10:03:16'),
(393, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-16 10:03:24'),
(394, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 12:33:28'),
(395, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 12:33:39'),
(396, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 12:35:50'),
(397, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 12:36:07'),
(398, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 12:36:30'),
(399, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 12:40:17'),
(400, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 12:40:34'),
(401, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 12:40:44'),
(402, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 12:46:36'),
(403, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 12:51:26'),
(404, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 12:51:34'),
(405, NULL, 'Login Failed', 'Email: member5@cmas.local', NULL, NULL, NULL, '2026-09-17 12:52:35'),
(406, 1, 'Delete', 'Deleted user #7 (Jose Ramirez)', NULL, NULL, NULL, '2026-09-17 12:53:15'),
(407, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 12:53:22'),
(408, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 12:53:50'),
(409, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 12:53:57'),
(410, 1, 'Update', 'Updated user #10 (grace@cmas.local)', NULL, NULL, NULL, '2026-09-17 12:54:48'),
(411, 1, 'Update', 'Updated user #10 (grace@cmas.local)', NULL, NULL, NULL, '2026-09-17 12:54:56'),
(412, NULL, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 12:55:09'),
(413, 1, 'Delete', 'Deleted user #10 (Grace)', NULL, NULL, NULL, '2026-09-17 12:55:27'),
(414, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 12:58:24'),
(415, NULL, 'Login Failed', 'Email: grace@cmas.local', NULL, NULL, NULL, '2026-09-17 12:58:36'),
(416, NULL, 'Login Failed', 'Email: grace@cmas.local', NULL, NULL, NULL, '2026-09-17 12:58:54'),
(417, NULL, 'Login Failed', 'Email: grace@cmas.local', NULL, NULL, NULL, '2026-09-17 12:59:10'),
(418, NULL, 'Account Locked', 'Email: grace@cmas.local locked for 5 minutes after repeated failed attempts.', NULL, NULL, NULL, '2026-09-17 12:59:10'),
(419, NULL, 'Login Failed', 'Email: lance@cmas.local', NULL, NULL, NULL, '2026-09-17 12:59:56'),
(420, 6, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 13:00:15'),
(421, 6, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 13:00:23'),
(422, 6, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 13:11:07'),
(423, 6, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 13:11:46'),
(424, 6, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 13:12:05'),
(425, 6, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 13:12:14'),
(426, 6, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 13:15:30'),
(427, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 13:15:48'),
(428, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 13:16:05'),
(429, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 13:31:06'),
(430, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 13:31:19'),
(431, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 13:31:32'),
(432, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 15:38:48'),
(433, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', NULL, NULL, NULL, '2026-09-17 15:39:10'),
(434, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', NULL, NULL, NULL, '2026-09-17 15:39:30'),
(435, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', NULL, NULL, NULL, '2026-09-17 15:39:46'),
(436, NULL, 'Account Locked', 'Email: lerinlance88@gmail.com locked for 5 minutes after repeated failed attempts.', NULL, NULL, NULL, '2026-09-17 15:39:46'),
(437, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-17 15:40:31'),
(438, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 15:40:56'),
(439, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 15:41:12'),
(440, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 15:42:49'),
(441, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 21:08:10'),
(442, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 21:15:20'),
(443, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 21:15:27'),
(444, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 22:07:08'),
(445, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:07:22'),
(446, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:07:29'),
(447, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 22:10:09'),
(448, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:10:24'),
(449, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:10:33'),
(450, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 22:11:34'),
(451, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:11:52'),
(452, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:12:03'),
(453, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:12:22'),
(454, 2, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 22:14:08'),
(455, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:14:26'),
(456, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:14:34'),
(457, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:15:37'),
(458, NULL, 'Login Failed', 'Email: staff@cmas.local', NULL, NULL, NULL, '2026-09-17 22:16:58'),
(459, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:17:08'),
(460, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:17:17'),
(461, 2, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:17:40'),
(462, 2, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:17:47'),
(463, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:36:51'),
(464, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-09-17 22:41:13'),
(465, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 22:41:36'),
(466, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-09-17 22:42:13'),
(467, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:48:16'),
(468, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:48:24'),
(469, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 22:58:18'),
(470, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-09-17 22:58:49'),
(471, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 22:58:57'),
(472, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 22:59:04'),
(473, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:03:14'),
(474, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-09-17 23:04:15'),
(475, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 23:04:22'),
(476, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 23:04:30'),
(477, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:16:30'),
(478, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 23:16:36'),
(479, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:16:47'),
(480, 1, 'OTP Verification Failed', 'Incorrect code. Please try again.', NULL, NULL, NULL, '2026-09-17 23:16:58'),
(481, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 23:17:21'),
(482, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 23:17:28'),
(483, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:20:43'),
(484, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 23:20:51'),
(485, 1, 'Update', 'Updated user #1 (admin@cmas.local)', NULL, NULL, NULL, '2026-09-17 23:23:00'),
(486, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-17 23:24:14'),
(487, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-17 23:24:25'),
(488, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:24:43'),
(489, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 23:25:22'),
(490, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 23:25:29'),
(491, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:28:13'),
(492, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 23:29:17'),
(493, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', NULL, NULL, NULL, '2026-09-17 23:30:48'),
(494, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 23:30:53'),
(495, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-17 23:31:01'),
(496, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-17 23:37:03'),
(497, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-17 23:41:56'),
(498, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 01:09:01'),
(499, 1, 'OTP Generated', 'A new OTP was issued (resend).', NULL, NULL, NULL, '2026-09-18 01:09:35');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `session_duration_seconds`, `created_at`) VALUES
(500, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-18 01:09:44'),
(501, 1, 'Update', 'Updated user #1 (lerinlance88@gmail.com)', NULL, NULL, NULL, '2026-09-18 01:10:15'),
(502, 1, 'Update', 'Updated user #3 (reyes.kaluret@gmail.com)', NULL, NULL, NULL, '2026-09-18 01:14:32'),
(503, 1, 'Update', 'Updated user #11 (nathaniel.lei20@gmail.com)', NULL, NULL, NULL, '2026-09-18 01:15:22'),
(504, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-18 01:15:42'),
(505, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 01:16:05'),
(506, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 01:47:26'),
(507, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-18 01:47:54'),
(508, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-18 01:49:54'),
(509, 3, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 01:52:03'),
(510, 3, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-18 01:52:20'),
(511, NULL, 'Login Failed', 'Email: admin@cmas.local', NULL, NULL, NULL, '2026-09-18 02:01:13'),
(512, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 02:02:08'),
(513, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-18 02:02:22'),
(514, 1, 'Logout', 'User logged out.', NULL, NULL, NULL, '2026-09-18 02:04:17'),
(515, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 02:04:35'),
(516, 1, 'Login', 'User logged in successfully (OTP verified).', NULL, NULL, NULL, '2026-09-18 02:04:53'),
(517, 1, 'OTP Generated', 'OTP issued for login verification.', NULL, NULL, NULL, '2026-09-18 02:10:50'),
(518, 1, 'Logout', 'User logged out. Session duration: 259 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:15:22'),
(519, 11, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:15:38'),
(520, 11, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:16:17'),
(521, 3, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:31:50'),
(522, 3, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:32:17'),
(523, 3, 'Update', 'Princess Ann Reyes\'s role in \"Committee on Public Works and Infrastructure\" changed to Member.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:39:30'),
(524, 3, 'Update', 'Lance Lerin\'s role in \"Committee on Public Works and Infrastructure\" changed to Member.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:39:33'),
(525, 3, 'Delete', 'Lance Lerin removed from committee \"Committee on Public Works and Infrastructure\".', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:39:45'),
(526, 3, 'Delete', 'Princess Ann Reyes removed from committee \"Committee on Public Works and Infrastructure\".', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:39:54'),
(527, 3, 'Insert', 'Created task #28 (Road Condition Assessment and Repair Planning)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:40:30'),
(528, 11, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:53:18'),
(529, 3, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:55:36'),
(530, 11, 'OTP Verification Failed', 'This code has expired. Please request a new one.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:56:09'),
(531, 3, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 02:56:13'),
(532, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 03:00:22'),
(533, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 03:00:49'),
(534, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 03:12:12'),
(535, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 03:12:33'),
(536, 1, 'Update', 'Updated user #1 (lerinlance88@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 03:13:01'),
(537, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:42:31'),
(538, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:43:36'),
(539, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:43:56'),
(540, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:44:42'),
(541, NULL, 'Login Failed', 'Email: admin@cmas.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, '2026-09-18 09:45:31'),
(542, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:46:10'),
(543, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:47:36'),
(544, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:47:43'),
(545, 1, 'Logout', 'User logged out. Session duration: 326 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:53:09'),
(546, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:53:20'),
(547, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:55:00'),
(548, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:57:21'),
(549, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:57:35'),
(550, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:57:42'),
(551, 1, 'Logout', 'User logged out. Session duration: 126 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 09:59:48'),
(552, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:01:38'),
(553, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:01:52'),
(554, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:01:58'),
(555, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:02:04'),
(556, 1, 'OTP Verification Failed', 'This code has expired. Please request a new one.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:02:11'),
(557, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:02:25'),
(558, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:02:33'),
(559, 1, 'Logout', 'User logged out. Session duration: 226 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:06:19'),
(560, 3, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:06:51'),
(561, 3, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:07:05'),
(562, 3, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:07:11'),
(563, 3, 'Logout', 'User logged out. Session duration: 34 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:07:45'),
(564, 11, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:08:07'),
(565, 11, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:08:22'),
(566, 11, 'OTP Verification Failed', 'This code has expired. Please request a new one.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:08:43'),
(567, 11, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:08:51'),
(568, 11, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 10:08:58'),
(569, 3, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:10:54'),
(570, 3, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:11:10'),
(571, 3, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:11:18'),
(572, NULL, 'Login Failed', 'Email: reyes@cmas.local', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:20:24'),
(573, 3, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:20:38'),
(574, 3, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:20:53'),
(575, 3, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:21:01'),
(576, 3, 'Insert', 'Created committee #10 (Committee on Environment)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:29:34'),
(577, 3, 'Insert', 'Lance Lerin assigned to committee \"Committee on Environment\" as Chairperson.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:34:42'),
(578, 3, 'Insert', 'Created task #29 (Tree planting)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:37:04'),
(579, 3, 'Logout', 'User logged out. Session duration: 1004 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:37:45'),
(580, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:38:16'),
(581, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:38:34'),
(582, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:38:41'),
(583, 1, 'Logout', 'User logged out. Session duration: 32 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:39:13'),
(584, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:39:25'),
(585, 6, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:39:40'),
(586, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 11:39:47'),
(587, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 18:19:04'),
(588, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 18:19:27'),
(589, 1, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 18:21:35'),
(590, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 18:21:53'),
(591, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 18:47:38'),
(592, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 18:47:52'),
(593, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:23:03'),
(594, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:23:27'),
(595, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:42:10'),
(596, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:42:28'),
(597, 3, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:43:34'),
(598, 3, 'OTP Generated', 'A new OTP was issued (resend).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:47:43'),
(599, 1, 'Update', 'Updated user #6 (lerinlance47@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:52:09'),
(600, 1, 'Update', 'Updated user #6 (lerinlance47@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:54:49'),
(601, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:55:02'),
(602, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 19:55:51'),
(603, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 21:56:45'),
(604, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 21:57:02'),
(605, NULL, 'Login Failed', 'Email: lerinlance47@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 22:04:21'),
(606, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 22:05:37'),
(607, 1, 'Logout', 'User logged out. Session duration: 545 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 22:06:07'),
(608, 6, 'OTP Verification Failed', 'Incorrect code. Please try again.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 22:06:31'),
(609, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-18 22:06:55'),
(610, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:19:48'),
(611, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:20:09'),
(612, 1, 'Update', 'Updated user #8 (xcnal47@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:22:57'),
(613, 1, 'Logout', 'User logged out. Session duration: 177 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:23:06'),
(614, 8, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:23:20'),
(615, 8, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:23:32'),
(616, 8, 'Logout', 'User logged out. Session duration: 161 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:26:13'),
(617, NULL, 'Login Failed', 'Email: lerinlance47@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:26:42'),
(618, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:28:28'),
(619, 6, 'OTP Verification Failed', 'Incorrect code. Please try again.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:28:56'),
(620, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 06:29:14'),
(621, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:24:33'),
(622, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:28:26'),
(623, NULL, 'Login Failed', 'Email: lerinlance88@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:29:00'),
(624, NULL, 'Account Locked', 'Email: lerinlance88@gmail.com locked for 5 minutes after repeated failed attempts.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:29:00'),
(625, NULL, 'Login Failed', 'Email: lancyhr1@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:29:08'),
(626, NULL, 'Account Locked', 'Email: lancyhr1@gmail.com locked for 5 minutes after repeated failed attempts.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:29:08'),
(627, NULL, 'Login Failed', 'Email: not-a-real-user@example.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, '2026-09-19 11:29:50'),
(628, NULL, 'Login Failed', 'Email: not-a-real-user@example.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, '2026-09-19 11:30:33'),
(629, NULL, 'Login Failed', 'Email: not-a-real-user@example.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, '2026-09-19 11:31:02'),
(630, NULL, 'Account Locked', 'Email: not-a-real-user@example.com locked for 5 minutes after repeated failed attempts.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, '2026-09-19 11:31:02'),
(631, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:33:14'),
(632, 6, 'OTP Verification Failed', 'Incorrect code. Please try again.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:33:48'),
(633, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:34:06'),
(634, 6, 'Logout', 'User logged out. Session duration: 210 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:37:36'),
(635, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:37:53'),
(636, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 11:38:06'),
(637, 1, 'Logout', 'User logged out. Session duration: 1351 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:00:37'),
(638, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:01:02'),
(639, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:01:23'),
(640, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:19:48'),
(641, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:20:47'),
(642, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:57:14'),
(643, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:57:59'),
(644, 6, 'OTP Verification Failed', 'Incorrect code. Please try again.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:58:22'),
(645, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 12:58:35'),
(646, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 14:06:39'),
(647, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 14:06:54'),
(648, 1, 'Logout', 'User logged out. Session duration: 272 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 14:11:26'),
(649, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 14:11:50'),
(650, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 14:13:07'),
(651, 6, 'Logout', 'User logged out. Session duration: 352 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 14:18:59'),
(652, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 15:05:45'),
(653, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 15:06:17'),
(654, 6, 'Export', 'Exported Workload Report CSV (all committees)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 15:21:37'),
(655, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 15:27:10'),
(656, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 15:27:26'),
(657, 6, 'Update', 'Updated committee #6 (Committee on Budget and Appropriation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 15:30:32'),
(658, 1, 'Logout', 'User logged out. Session duration: 2789 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 16:13:55'),
(659, NULL, 'Login Failed', 'Email: xcnal47@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 16:14:09'),
(660, NULL, 'Login Failed', 'Email: xcnal47@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 16:14:34'),
(661, 8, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 16:14:57'),
(662, 8, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 16:15:48'),
(663, 6, 'Update', 'Updated task #19 (TEST)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 16:17:00'),
(664, 8, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 17:16:39'),
(665, 8, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 17:17:16'),
(666, 6, 'Insert', 'Created task #30 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 17:45:43'),
(667, 6, 'Update', 'Marked task #30 (Department of Education Funding Request Evaluation) as completed.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 17:46:20'),
(668, 6, 'Update', 'Updated task #30 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 17:46:35'),
(669, 6, 'Insert', 'Created task #31 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 17:49:01'),
(670, NULL, 'Login Failed', 'Email: xcnal47@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 19:05:15'),
(671, 8, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 19:05:33'),
(672, 8, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 19:06:52'),
(673, 6, 'Logout', 'User logged out. Session duration: 16964 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 19:49:01'),
(674, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 19:49:18'),
(675, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 19:49:36'),
(676, 1, 'Logout', 'User logged out. Session duration: 781 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 20:02:37'),
(677, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 20:03:06'),
(678, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 20:03:39'),
(679, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 20:45:33'),
(680, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 20:47:10'),
(681, 6, 'Update', 'Updated own profile background information.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 20:57:05'),
(682, 6, 'Logout', 'User logged out. Session duration: 5587 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 21:36:46'),
(683, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 21:37:01'),
(684, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 21:37:14'),
(685, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 23:19:32'),
(686, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-19 23:20:07'),
(687, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:20:28'),
(688, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:20:46'),
(689, 1, 'Logout', 'User logged out. Session duration: 55 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:21:41'),
(690, 6, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:22:17'),
(691, 6, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:23:30'),
(692, 6, 'Delete', 'Deleted committee #7 (Committee on Peace and Order)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:27:11'),
(693, 6, 'Delete', 'Deleted committee #9 (Committee on Public Works and Infrastructure)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:28:42'),
(694, 6, 'Insert', 'Created committee #11 (Committee on Public Works and Infrastructure)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:29:02'),
(695, 6, 'Delete', 'Deleted task #31 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:36:09'),
(696, 6, 'Insert', 'Created task #32 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:37:50'),
(697, 6, 'Delete', 'Deleted task #30 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:38:07'),
(698, 6, 'Delete', 'Deleted task #32 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:38:10'),
(699, 6, 'Insert', 'Created task #33 (Department of Education Funding Request Evaluation)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:38:35'),
(700, 6, 'Logout', 'User logged out. Session duration: 1283 seconds.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:44:53'),
(701, 1, 'OTP Generated', 'OTP issued for login verification.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:45:18'),
(702, 1, 'OTP Verification Failed', 'Incorrect code. Please try again.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:45:37'),
(703, 1, 'Login', 'User logged in successfully (OTP verified).', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', NULL, '2026-09-20 02:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `recommendation_id` int(11) NOT NULL,
  `committee_id` int(11) NOT NULL,
  `workload_id` int(11) DEFAULT NULL,
  `recommended_member_id` int(11) DEFAULT NULL,
  `recommended_score` decimal(5,2) DEFAULT NULL,
  `confidence` enum('Low','Medium','High') DEFAULT NULL,
  `candidates_json` text NOT NULL,
  `ai_available` tinyint(1) DEFAULT NULL,
  `ai_recommended_member_id` int(11) DEFAULT NULL,
  `ai_generated_fields` text DEFAULT NULL,
  `ai_confidence` enum('Low','Medium','High') DEFAULT NULL,
  `ai_risk_assessment` enum('Low','Medium','High') DEFAULT NULL,
  `ai_reasoning` text DEFAULT NULL,
  `ai_alternative_ids` varchar(255) DEFAULT NULL,
  `agreement_status` enum('Yes','No','N/A') DEFAULT 'N/A',
  `ai_model_used` varchar(100) DEFAULT NULL,
  `ai_response_time_ms` int(11) DEFAULT NULL,
  `ai_warning` varchar(255) DEFAULT NULL,
  `candidates_hash` varchar(64) DEFAULT NULL,
  `was_overridden` tinyint(1) NOT NULL DEFAULT 0,
  `admin_followed_ai` tinyint(1) DEFAULT NULL,
  `final_member_id` int(11) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_recommendations`
--

INSERT INTO `ai_recommendations` (`recommendation_id`, `committee_id`, `workload_id`, `recommended_member_id`, `recommended_score`, `confidence`, `candidates_json`, `ai_available`, `ai_recommended_member_id`, `ai_generated_fields`, `ai_confidence`, `ai_risk_assessment`, `ai_reasoning`, `ai_alternative_ids`, `agreement_status`, `ai_model_used`, `ai_response_time_ms`, `ai_warning`, `candidates_hash`, `was_overridden`, `admin_followed_ai`, `final_member_id`, `generated_by`, `generated_at`) VALUES
(1, 5, NULL, 4, 56.00, 'Low', '{\"committee_id\":5,\"recommended\":{\"committee_member_id\":4,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":58,\"role_weight\":50},\"normalized\":{\"active_committees\":0,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":0},\"suitability_score\":56,\"reasoning\":[\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":3,\"user_id\":5,\"full_name\":\"Pedro Reyes\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":0,\"timeliness\":50,\"overdue_count\":1,\"assignment_recency\":43,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":40,\"completion_rate\":0,\"current_workload\":100,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":55.2,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Current Workload (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":2,\"user_id\":3,\"full_name\":\"Princess Ann Reyes\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":8,\"active_committees\":2,\"completion_rate\":0,\"timeliness\":50,\"overdue_count\":1,\"assignment_recency\":33,\"role_weight\":100},\"normalized\":{\"active_committees\":0,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":36,\"reasoning\":[\"Strong on Committee Role Weight (100\\/100).\",\"Strong on On-Time Completion Rate (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"Low\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-10 23:34:37\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-10 23:34:37'),
(2, 5, NULL, 4, 56.00, 'Low', '{\"committee_id\":5,\"recommended\":{\"committee_member_id\":4,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":58,\"role_weight\":50},\"normalized\":{\"active_committees\":0,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":0},\"suitability_score\":56,\"reasoning\":[\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":3,\"user_id\":5,\"full_name\":\"Pedro Reyes\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":0,\"timeliness\":50,\"overdue_count\":1,\"assignment_recency\":43,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":40,\"completion_rate\":0,\"current_workload\":100,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":55.2,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Current Workload (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":2,\"user_id\":3,\"full_name\":\"Princess Ann Reyes\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":8,\"active_committees\":2,\"completion_rate\":0,\"timeliness\":50,\"overdue_count\":1,\"assignment_recency\":33,\"role_weight\":100},\"normalized\":{\"active_committees\":0,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":36,\"reasoning\":[\"Strong on Committee Role Weight (100\\/100).\",\"Strong on On-Time Completion Rate (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"Low\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-10 23:34:42\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-10 23:34:42'),
(3, 5, NULL, 4, 56.00, 'Low', '{\"committee_id\":5,\"recommended\":{\"committee_member_id\":4,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":58,\"role_weight\":50},\"normalized\":{\"active_committees\":0,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":0},\"suitability_score\":56,\"reasoning\":[\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":3,\"user_id\":5,\"full_name\":\"Pedro Reyes\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":0,\"timeliness\":50,\"overdue_count\":1,\"assignment_recency\":43,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":40,\"completion_rate\":0,\"current_workload\":100,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":55.2,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Current Workload (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":2,\"user_id\":3,\"full_name\":\"Princess Ann Reyes\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":8,\"active_committees\":2,\"completion_rate\":0,\"timeliness\":50,\"overdue_count\":1,\"assignment_recency\":33,\"role_weight\":100},\"normalized\":{\"active_committees\":0,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":36,\"reasoning\":[\"Strong on Committee Role Weight (100\\/100).\",\"Strong on On-Time Completion Rate (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"Low\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-10 23:34:42\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-10 23:34:42'),
(4, 8, NULL, NULL, 76.00, 'High', '{\"committee_id\":8,\"recommended\":{\"committee_member_id\":13,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":66.7,\"timeliness\":50,\"overdue_count\":0,\"assignment_recency\":63,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":11,\"user_id\":10,\"full_name\":\"Grace Torres\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":7,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":25,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":9.5,\"completion_rate\":0,\"current_workload\":30,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":0},\"suitability_score\":38.8,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]},{\"committee_member_id\":12,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":10,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":21,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":0},\"suitability_score\":20,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (50\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-10 23:34:45\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-10 23:34:45'),
(5, 8, NULL, NULL, 76.00, 'High', '{\"committee_id\":8,\"recommended\":{\"committee_member_id\":13,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":66.7,\"timeliness\":50,\"overdue_count\":0,\"assignment_recency\":63,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":11,\"user_id\":10,\"full_name\":\"Grace Torres\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":7,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":25,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":9.5,\"completion_rate\":0,\"current_workload\":30,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":0},\"suitability_score\":38.8,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]},{\"committee_member_id\":12,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":10,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":21,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":0},\"suitability_score\":20,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (50\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-10 23:34:47\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-10 23:34:47'),
(7, 8, NULL, NULL, 76.00, 'High', '{\"committee_id\":8,\"recommended\":{\"committee_member_id\":13,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":66.7,\"timeliness\":50,\"overdue_count\":0,\"assignment_recency\":63,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":11,\"user_id\":10,\"full_name\":\"Grace Torres\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":7,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":25,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":9.5,\"completion_rate\":0,\"current_workload\":30,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":0},\"suitability_score\":38.8,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]},{\"committee_member_id\":12,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":10,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":21,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":0},\"suitability_score\":20,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (50\\/100).\",\"Weaker on On-Time Completion Rate (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-10 23:35:06\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-10 23:35:06'),
(9, 5, NULL, 2, 84.00, 'Low', '{\"committee_id\":5,\"recommended\":{\"committee_member_id\":2,\"user_id\":3,\"full_name\":\"Princess Ann Reyes\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":34,\"role_weight\":100},\"normalized\":{\"active_committees\":0,\"assignment_recency\":0,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":84,\"reasoning\":[\"Strong on Task Completion Rate (100\\/100).\",\"Strong on Current Workload (100\\/100).\",\"Weaker on Fairness \\/ Assignment Recency (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":3,\"user_id\":5,\"full_name\":\"Pedro Reyes\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":44,\"role_weight\":75},\"normalized\":{\"active_committees\":100,\"assignment_recency\":40,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":83.2,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on Fairness \\/ Assignment Recency (40\\/100).\"]},{\"committee_member_id\":4,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":59,\"role_weight\":50},\"normalized\":{\"active_committees\":0,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":68,\"reasoning\":[\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]}],\"confidence\":\"Low\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-11 20:00:33\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-11 20:00:33'),
(15, 6, NULL, 5, 92.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":33,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":92,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on Fairness \\/ Assignment Recency (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":63,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":48,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":50,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":28,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (50\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-20 06:58:18\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-08-20 06:58:18'),
(16, 6, 19, 5, 92.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":35,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":92,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Task Completion Rate (100\\/100).\",\"Weaker on Fairness \\/ Assignment Recency (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":65,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":50,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":50,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":28,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (50\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-22 12:19:48\"}', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, NULL, NULL, 0, NULL, 5, 1, '2026-08-22 12:19:48'),
(17, 6, NULL, 7, 76.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":70,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":5,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":83.3,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":60.7,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":55,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":76.9,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":30.2,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (76.9\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-27 14:19:27\"}', 1, NULL, NULL, 'High', 'Low', '[\"The candidate with the highest rule-based score (30.2) is recommended.\",\"The candidate has the lowest overdue tasks (1) and the highest on-time rate (0).\"]', '7,5', 'No', 'qwen2.5:1.5b', 29442, NULL, 'd721c55d6577652bb3044a1bcdc681c307d94d17996e9f5f5621c22754b28269', 0, NULL, NULL, 1, '2026-08-27 14:19:57'),
(18, 6, NULL, 7, 76.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":70,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":5,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":83.3,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":60.7,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":55,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":76.9,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":30.2,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (76.9\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-27 14:19:57\"}', 1, NULL, NULL, 'High', 'Low', '[\"The candidate with the highest rule-based score (30.2) is recommended.\",\"The candidate has the lowest overdue tasks (1) and the highest on-time rate (0).\"]', '7,5', 'No', 'qwen2.5:1.5b', 29442, NULL, 'd721c55d6577652bb3044a1bcdc681c307d94d17996e9f5f5621c22754b28269', 0, NULL, NULL, 1, '2026-08-27 14:19:57'),
(19, 6, NULL, 7, 76.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":70,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":5,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":83.3,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":60.7,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":55,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":76.9,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":30.2,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (76.9\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-27 14:19:57\"}', 1, NULL, NULL, 'High', 'Low', '[\"The candidate with the highest rule-based score (30.2) is recommended.\",\"The candidate has the lowest overdue tasks (1) and the highest on-time rate (0).\"]', '7,5', 'No', 'qwen2.5:1.5b', 29442, NULL, 'd721c55d6577652bb3044a1bcdc681c307d94d17996e9f5f5621c22754b28269', 0, NULL, NULL, 1, '2026-08-27 14:19:57'),
(20, 8, NULL, 12, 72.00, 'Medium', '{\"committee_id\":8,\"recommended\":{\"committee_member_id\":12,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":38,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":0,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":72,\"reasoning\":[\"Strong on Task Completion Rate (100\\/100).\",\"Strong on Current Workload (100\\/100).\",\"Weaker on Fairness \\/ Assignment Recency (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":13,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":80,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":0,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":60,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},{\"committee_member_id\":11,\"user_id\":10,\"full_name\":\"Grace Torres\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":7,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":42,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":9.5,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":44.8,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"Medium\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-27 14:23:29\"}', 1, NULL, NULL, 'Low', 'Low', '[\"The member with the lowest workload points and the highest completion rate is recommended.\",\"The member has the highest rule-based score, indicating a higher likelihood of meeting deadlines and completing tasks on time.\"]', '12,13', 'No', 'qwen2.5:1.5b', 14735, NULL, '8a2585af238a2bf24e2143089ef469980bac83a0f53629325cfa4f3871d08868', 0, NULL, NULL, 1, '2026-08-27 14:23:43');
INSERT INTO `ai_recommendations` (`recommendation_id`, `committee_id`, `workload_id`, `recommended_member_id`, `recommended_score`, `confidence`, `candidates_json`, `ai_available`, `ai_recommended_member_id`, `ai_generated_fields`, `ai_confidence`, `ai_risk_assessment`, `ai_reasoning`, `ai_alternative_ids`, `agreement_status`, `ai_model_used`, `ai_response_time_ms`, `ai_warning`, `candidates_hash`, `was_overridden`, `admin_followed_ai`, `final_member_id`, `generated_by`, `generated_at`) VALUES
(21, 8, NULL, 12, 72.00, 'Medium', '{\"committee_id\":8,\"recommended\":{\"committee_member_id\":12,\"user_id\":6,\"full_name\":\"Lance Lerin\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":38,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":0,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":72,\"reasoning\":[\"Strong on Task Completion Rate (100\\/100).\",\"Strong on Current Workload (100\\/100).\",\"Weaker on Fairness \\/ Assignment Recency (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":13,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":80,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":0,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":60,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},{\"committee_member_id\":11,\"user_id\":10,\"full_name\":\"Grace Torres\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":7,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":42,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":9.5,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":44.8,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"Medium\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-27 14:23:44\"}', 1, NULL, NULL, 'Low', 'Low', '[\"The member with the lowest workload points and the highest completion rate is recommended.\",\"The member has the highest rule-based score, indicating a higher likelihood of meeting deadlines and completing tasks on time.\"]', '12,13', 'No', 'qwen2.5:1.5b', 14735, NULL, '8a2585af238a2bf24e2143089ef469980bac83a0f53629325cfa4f3871d08868', 0, NULL, NULL, 1, '2026-08-27 14:23:44'),
(22, 6, NULL, 7, 76.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":70,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":5,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":83.3,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":60.7,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":55,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":76.9,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":30.2,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (76.9\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-27 17:43:08\"}', 1, NULL, NULL, 'Medium', 'Low', '[\"The candidate with the highest rule-based score and the lowest overdue tasks has the highest confidence level.\"]', '7,5', 'No', 'qwen2.5:1.5b', 25539, NULL, 'd721c55d6577652bb3044a1bcdc681c307d94d17996e9f5f5621c22754b28269', 0, NULL, NULL, 1, '2026-08-27 17:43:34'),
(23, 6, NULL, 7, 76.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":71,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":6,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":83.3,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":60.7,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":56,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":76.9,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":30.2,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (76.9\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-28 01:45:16\"}', 1, NULL, NULL, 'High', 'Low', '[\"The candidate with the highest rule-based score (30.2) is recommended.\",\"The candidate has the lowest overdue tasks (1) and the highest on-time completion rate (0).\"]', '5,7', 'No', 'qwen2.5:1.5b', 21408, NULL, '369be8b04daa5b8816bbb66aa42fe4a7b1f0b9e822340e33aa280374faa7e766', 0, NULL, NULL, 1, '2026-08-28 01:45:38'),
(24, 6, NULL, 7, 76.00, 'High', '{\"committee_id\":6,\"recommended\":{\"committee_member_id\":7,\"user_id\":8,\"full_name\":\"Liza Fernandez\",\"member_role\":\"Member\",\"raw\":{\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"timeliness\":0,\"overdue_count\":0,\"assignment_recency\":71,\"role_weight\":50},\"normalized\":{\"active_committees\":100,\"assignment_recency\":100,\"completion_rate\":100,\"current_workload\":100,\"overdue_count\":100,\"role_weight\":0,\"timeliness\":100},\"suitability_score\":76,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (100\\/100).\",\"Weaker on Committee Role Weight (0\\/100).\"]},\"alternatives\":[{\"committee_member_id\":5,\"user_id\":4,\"full_name\":\"Maria Santos\",\"member_role\":\"Chairperson\",\"raw\":{\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":6,\"role_weight\":100},\"normalized\":{\"active_committees\":100,\"assignment_recency\":0,\"completion_rate\":0,\"current_workload\":83.3,\"overdue_count\":0,\"role_weight\":100,\"timeliness\":100},\"suitability_score\":60.7,\"reasoning\":[\"Strong on Active Committee Count (100\\/100).\",\"Strong on Committee Role Weight (100\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]},{\"committee_member_id\":6,\"user_id\":7,\"full_name\":\"Jose Ramirez\",\"member_role\":\"Vice Chairperson\",\"raw\":{\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"timeliness\":0,\"overdue_count\":1,\"assignment_recency\":56,\"role_weight\":75},\"normalized\":{\"active_committees\":0,\"assignment_recency\":76.9,\"completion_rate\":0,\"current_workload\":0,\"overdue_count\":0,\"role_weight\":50,\"timeliness\":100},\"suitability_score\":30.2,\"reasoning\":[\"Strong on On-Time Completion Rate (100\\/100).\",\"Strong on Fairness \\/ Assignment Recency (76.9\\/100).\",\"Weaker on Overdue Task Count (0\\/100).\"]}],\"confidence\":\"High\",\"weights_used\":{\"active_committees\":{\"label\":\"Active Committee Count\",\"description\":\"Number of committees the member currently sits on.\",\"direction\":\"lower_is_better\",\"weight\":0.08,\"raw_weight\":10},\"assignment_recency\":{\"label\":\"Fairness \\/ Assignment Recency\",\"description\":\"Days since the member\'s last new task assignment (rotates work fairly).\",\"direction\":\"higher_is_better\",\"weight\":0.08,\"raw_weight\":10},\"completion_rate\":{\"label\":\"Task Completion Rate\",\"description\":\"Share of assigned tasks the member has completed.\",\"direction\":\"higher_is_better\",\"weight\":0.16,\"raw_weight\":20},\"current_workload\":{\"label\":\"Current Workload\",\"description\":\"Total active workload points already carried (Pending\\/In Progress tasks).\",\"direction\":\"lower_is_better\",\"weight\":0.2,\"raw_weight\":25},\"overdue_count\":{\"label\":\"Overdue Task Count\",\"description\":\"Number of currently overdue tasks the member is carrying.\",\"direction\":\"lower_is_better\",\"weight\":0.12,\"raw_weight\":15},\"role_weight\":{\"label\":\"Committee Role Weight\",\"description\":\"Admin-configurable trust multiplier by role (Chairperson\\/Vice\\/Member).\",\"direction\":\"higher_is_better\",\"weight\":0.24,\"raw_weight\":30},\"timeliness\":{\"label\":\"On-Time Completion Rate\",\"description\":\"Share of completed tasks finished on or before their due date.\",\"direction\":\"higher_is_better\",\"weight\":0.12,\"raw_weight\":15}},\"generated_at\":\"2026-08-28 10:19:50\"}', 1, NULL, NULL, 'High', 'Low', '[\"The candidate with the highest rule-based score (30.2) is recommended.\",\"The candidate has the lowest overdue tasks (1) and the highest on-time rate (0).\"]', '7,5', 'No', 'qwen2.5:1.5b', 22265, NULL, '369be8b04daa5b8816bbb66aa42fe4a7b1f0b9e822340e33aa280374faa7e766', 0, NULL, NULL, 1, '2026-08-28 10:20:13'),
(26, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Sanitation ordinance revision\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":7},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":57},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":72}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5:1.5b', 30362, 'AI task generation is currently unavailable. Please fill in the task details manually.', '27aae0aec44167d2665e2c6e417247917f8a1fa521006b74aaf046b57a7e0187', 0, NULL, NULL, 1, '2026-08-29 21:01:30'),
(27, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":7},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":6,\"active_committees\":2,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":57},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":72}]}', 1, NULL, '{\"description\":\"The Vice Chairperson, Jose Ramirez, has the highest completion rate and on-time rate, indicating he is the most efficient and reliable member for this task. His lower workload and fewer overdue tasks also make him the best choice.\",\"priority\":\"Urgent\",\"workload_points\":80,\"due_date\":\"2026-09-04\",\"status\":\"Pending\"}', NULL, NULL, 'Jose Ramirez is the most efficient and reliable member, with a high completion rate and on-time rate, making him the best choice for the urgent task of road condition assessment and repair planning.', NULL, 'N/A', 'qwen2.5:1.5b', 14669, NULL, '092c194d973f111d288387085d19b393ffb4379f98f0ea202c466a45713d1d56', 0, 1, NULL, 1, '2026-08-29 21:01:58'),
(37, 5, 3, NULL, NULL, NULL, '{\"task_title\":\"Compile Q2 health program report\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":8,\"active_committees\":3,\"completion_rate\":33.3,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":52},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":62},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":77}]}', 1, 2, '{\"description\":\"The Chairperson has a higher workload and lower completion rate compared to the other members, indicating a need for a comprehensive report. The Chairperson also has the longest time since their last assignment, making them the most suitable candidate for this urgent task.\",\"priority\":\"Urgent\",\"workload_points\":85,\"due_date\":\"2026-09-15\",\"status\":\"Pending\"}', NULL, NULL, 'Princess Ann Reyes, as the Chairperson, has a higher workload and lower completion rate compared to the other members, indicating a need for a comprehensive report. Additionally, the Chairperson has the longest time since their last assignment, making them the most suitable candidate for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 16412, NULL, 'b1198fa30d3c35605b14d4670dccd8c0a56863fcb593abd20a1df758855863f8', 0, 1, 2, 1, '2026-08-29 21:04:51'),
(39, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Finalize FY budget hearing schedule\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":7},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":80,\"active_committees\":2,\"completion_rate\":66.7,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":0},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":72}]}', 1, NULL, '{\"description\":\"The Vice Chairperson, Jose Ramirez, has the highest completion rate and on-time rate, making him the most suitable for finalizing the FY budget hearing schedule. He has been actively involved in the committee for the longest time and has the least workload, ensuring he can manage the task efficiently.\",\"priority\":\"Urgent\",\"workload_points\":75,\"due_date\":\"2026-09-05\",\"status\":\"Pending\"}', NULL, NULL, 'Jose Ramirez is the most qualified for this task due to his high completion rate and on-time rate, combined with the least workload and the shortest days since last assignment. His expertise and experience make him the best choice for finalizing the FY budget hearing schedule.', NULL, 'N/A', 'qwen2.5:1.5b', 17848, NULL, 'acef35beff0fbce8a41aa04c00df1d8b562f0300cb5967cccc27e7a388d7c8c4', 0, NULL, NULL, 1, '2026-08-29 21:09:30'),
(40, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Finalize FY budget hearing schedule\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":7},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":80,\"active_committees\":2,\"completion_rate\":66.7,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":0},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":72}]}', 1, NULL, '{\"description\":\"The Vice Chairperson, Jose Ramirez, has the highest completion rate and on-time rate, making him the most suitable for finalizing the FY budget hearing schedule. He has been actively involved in the committee for the longest time and has the least workload, ensuring he can manage the task efficiently.\",\"priority\":\"Urgent\",\"workload_points\":75,\"due_date\":\"2026-09-05\",\"status\":\"Pending\"}', NULL, NULL, 'Jose Ramirez is the most qualified for this task due to his high completion rate and on-time rate, combined with the least workload and the shortest days since last assignment. His expertise and experience make him the best choice for finalizing the FY budget hearing schedule.', NULL, 'N/A', 'qwen2.5:1.5b', 17848, NULL, 'acef35beff0fbce8a41aa04c00df1d8b562f0300cb5967cccc27e7a388d7c8c4', 0, NULL, NULL, 1, '2026-08-29 21:09:41'),
(41, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Finalize FY budget hearing schedule\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":7},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":80,\"active_committees\":2,\"completion_rate\":66.7,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":0},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":72}]}', 1, NULL, '{\"description\":\"The Vice Chairperson, Jose Ramirez, has the highest completion rate and on-time rate, making him the most suitable for finalizing the FY budget hearing schedule. He has been actively involved in the committee for the longest time and has the least workload, ensuring he can manage the task efficiently.\",\"priority\":\"Urgent\",\"workload_points\":75,\"due_date\":\"2026-09-05\",\"status\":\"Pending\"}', NULL, NULL, 'Jose Ramirez is the most qualified for this task due to his high completion rate and on-time rate, combined with the least workload and the shortest days since last assignment. His expertise and experience make him the best choice for finalizing the FY budget hearing schedule.', NULL, 'N/A', 'qwen2.5:1.5b', 17848, NULL, 'acef35beff0fbce8a41aa04c00df1d8b562f0300cb5967cccc27e7a388d7c8c4', 0, NULL, NULL, 1, '2026-08-29 21:09:42'),
(43, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Draft sanitation ordinance revision\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":8},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":80,\"active_committees\":2,\"completion_rate\":66.7,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":1},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":73}]}', 1, NULL, '{\"description\":\"Drafting a sanitation ordinance revision requires significant research and coordination, especially given the Vice Chairperson\'s workload and experience.\",\"priority\":\"Urgent\",\"workload_points\":75,\"due_date\":\"2026-09-05\",\"status\":\"Pending\"}', NULL, NULL, 'The Vice Chairperson, Jose Ramirez, has a moderate workload and a completion rate of 66.7%, indicating he is capable of handling this task. His experience and the urgency of the task make him the most suitable candidate. His 1 day of overdue tasks and 1 day of work since the last assignment place him at the top of the list for this task.', NULL, 'N/A', 'qwen2.5:1.5b', 29641, NULL, '2a948d6b9a02406dca1625fda9114a798b15dd6522a744ee661655071874277b', 0, 1, NULL, 1, '2026-08-30 18:16:01'),
(44, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Inspect barangay health centers\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":85,\"active_committees\":3,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":54},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":64},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5-coder:7b', 36085, 'AI task generation is currently unavailable. Please fill in the task details manually.', '1be9b8ac6d4074556b774bf63a3eccfd74429b77f52532b0e30e66fe1ac64fc4', 0, NULL, NULL, 1, '2026-08-31 13:16:27'),
(45, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Inspect barangay health centers\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":85,\"active_committees\":3,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":54},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":64},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5-coder:7b', 36764, 'AI task generation is currently unavailable. Please fill in the task details manually.', '1be9b8ac6d4074556b774bf63a3eccfd74429b77f52532b0e30e66fe1ac64fc4', 0, NULL, NULL, 1, '2026-08-31 13:17:27'),
(47, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":85,\"active_committees\":3,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":54},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":64},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5-coder:7b', 30390, 'AI task generation is currently unavailable. Please fill in the task details manually.', '768af479ff680e68e2acb2a64a57352734195724616b62a88d1275342af0ee7f', 0, NULL, NULL, 1, '2026-08-31 13:20:48'),
(48, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":85,\"active_committees\":3,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":54},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":64},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5:1.5b', 30381, 'AI task generation is currently unavailable. Please fill in the task details manually.', '768af479ff680e68e2acb2a64a57352734195724616b62a88d1275342af0ee7f', 0, NULL, NULL, 1, '2026-08-31 13:22:33'),
(49, 5, 23, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":85,\"active_committees\":3,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":54},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":64},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, 4, '{\"description\":\"A task to ensure all adults in the community receive a free health check-up, which is crucial for maintaining public health and reducing the burden on the healthcare system.\",\"priority\":\"Urgent\",\"workload_points\":85,\"due_date\":\"2026-09-14\",\"status\":\"Pending\"}', NULL, NULL, 'Member Lance Lerin has the highest completion rate and on-time rate, indicating he is already well-organized and efficient. Despite his high workload, he is still able to complete tasks on time, making him the best candidate to take on this urgent task. His high completion rate suggests he is proactive and efficient, which is crucial for ensuring timely and effective completion of the free health check-up for adults.', NULL, 'N/A', 'qwen2.5:1.5b', 15957, NULL, '768af479ff680e68e2acb2a64a57352734195724616b62a88d1275342af0ee7f', 0, 1, 4, 1, '2026-08-31 13:23:09'),
(54, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Finalize FY budget hearing schedule\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":1,\"days_since_last_assignment\":11},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":155,\"active_committees\":2,\"completion_rate\":33.3,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":3},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"Finalize the FY budget hearing schedule for the Committee on Budget and Appropriations\",\"priority\":\"Urgent\",\"workload_points\":85,\"due_date\":\"2026-09-13\",\"status\":\"Pending\"}', NULL, NULL, 'The Vice Chairperson Jose Ramirez has the highest workload with 155 tasks, the highest completion rate of 33.3%, and the highest on-time rate of 50%. Despite having the longest days since last assignment (3 days), he has the lowest current workload and the least overdue tasks. This combination makes him the most suitable candidate for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 23106, NULL, '265751f52ed42b00d2631655303301816bb268e016143100d760c1aacf734f30', 0, 1, NULL, 2, '2026-09-02 05:00:33'),
(55, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Inspect barangay health centers\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":75,\"on_time_rate\":33.3,\"overdue_tasks\":0,\"days_since_last_assignment\":56},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":2}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5:1.5b', 2263, 'AI task generation is currently unavailable. Please fill in the task details manually.', '7103b41efc665441c6982a9e735b29e3c19f19ea232929cc2793cb0bda806626', 0, NULL, NULL, 2, '2026-09-02 18:17:09'),
(56, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Inspect barangay health centers\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":75,\"on_time_rate\":33.3,\"overdue_tasks\":0,\"days_since_last_assignment\":56},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":2}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5:1.5b', 2248, 'AI task generation is currently unavailable. Please fill in the task details manually.', '7103b41efc665441c6982a9e735b29e3c19f19ea232929cc2793cb0bda806626', 0, NULL, NULL, 2, '2026-09-02 18:17:13'),
(57, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Inspect barangay health centers\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":75,\"on_time_rate\":33.3,\"overdue_tasks\":0,\"days_since_last_assignment\":56},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":2}]}', 1, 4, '{\"description\":\"Inspect barangay health centers and ensure all centers are up to date with health regulations.\",\"priority\":\"Urgent\",\"workload_points\":75,\"due_date\":\"2026-10-02\",\"status\":\"Pending\"}', NULL, NULL, 'Member Lance Lerin has the highest completion rate (100%) and the lowest on-time rate (50%), indicating he is the most capable of completing the task efficiently and ensuring timely inspections. He also has the lowest current workload and has not been assigned new work in a long time, making him the most fair choice for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 21440, NULL, '7103b41efc665441c6982a9e735b29e3c19f19ea232929cc2793cb0bda806626', 0, NULL, NULL, 2, '2026-09-02 18:19:19'),
(58, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":17},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":240,\"active_committees\":2,\"completion_rate\":25,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":6},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"Assess road conditions and plan repairs for the committee\'s jurisdiction.\",\"priority\":\"Urgent\",\"workload_points\":50,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Member Jose Ramirez has the highest completion rate at 25%, indicating he has been more efficient in completing tasks. He also has the lowest current workload and the shortest days since last assignment, making him the most suitable for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 25087, NULL, '3eec3fdba41757195a9499b9a26852c990faf81fedf4e4bbd43330de7d8bfdda', 0, NULL, NULL, 2, '2026-09-08 02:49:20'),
(59, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":17},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":240,\"active_committees\":2,\"completion_rate\":25,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":6},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"Assess road conditions and plan repairs for the committee\'s jurisdiction.\",\"priority\":\"Urgent\",\"workload_points\":50,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Member Jose Ramirez has the highest completion rate at 25%, indicating he has been more efficient in completing tasks. He also has the lowest current workload and the shortest days since last assignment, making him the most suitable for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 25087, NULL, '3eec3fdba41757195a9499b9a26852c990faf81fedf4e4bbd43330de7d8bfdda', 0, NULL, NULL, 2, '2026-09-08 02:51:38'),
(60, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":17},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":240,\"active_committees\":2,\"completion_rate\":25,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":6},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"Assess road conditions and plan repairs for the committee\'s jurisdiction.\",\"priority\":\"Urgent\",\"workload_points\":50,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Member Jose Ramirez has the highest completion rate at 25%, indicating he has been more efficient in completing tasks. He also has the lowest current workload and the shortest days since last assignment, making him the most suitable for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 25087, NULL, '3eec3fdba41757195a9499b9a26852c990faf81fedf4e4bbd43330de7d8bfdda', 0, NULL, NULL, 2, '2026-09-08 02:54:16'),
(61, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":17},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":240,\"active_committees\":2,\"completion_rate\":25,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":6},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"Assess road conditions and plan repairs for the committee\'s jurisdiction.\",\"priority\":\"Urgent\",\"workload_points\":50,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Member Jose Ramirez has the highest completion rate at 25%, indicating he has been more efficient in completing tasks. He also has the lowest current workload and the shortest days since last assignment, making him the most suitable for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 25087, NULL, '3eec3fdba41757195a9499b9a26852c990faf81fedf4e4bbd43330de7d8bfdda', 0, 1, NULL, 2, '2026-09-08 02:55:40'),
(63, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":25,\"overdue_tasks\":0,\"days_since_last_assignment\":62},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":8}]}', 1, 3, '{\"description\":\"The Vice Chairperson, Pedro Reyes, is recommended due to his lower current workload, fewer overdue tasks, and higher on_time_rate, making him the most suitable for this task.\",\"priority\":\"Urgent\",\"workload_points\":50,\"due_date\":\"2026-09-15\",\"status\":\"Pending\"}', NULL, NULL, 'Pedro Reyes has the lowest current workload, fewer overdue tasks, and a higher on_time_rate compared to the other members. He is also the only one with a positive completion rate, which is crucial for ensuring the task is completed on time.', NULL, 'N/A', 'qwen2.5:1.5b', 10055, NULL, 'e9ffca2a33aff23214caf717bcddae50c2e37bc85ba155c2f459345f644552b9', 0, NULL, NULL, 2, '2026-09-08 02:59:34'),
(64, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Prepare fund utilization summary\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":17},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":290,\"active_committees\":2,\"completion_rate\":20,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":0},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"Prepare a detailed fund utilization summary for the committee.\",\"priority\":\"Urgent\",\"workload_points\":80,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Jose Ramirez has the highest workload with 290 tasks, the lowest completion rate (20%), and the highest number of overdue tasks (2). He is also the only member with a completion rate lower than 100%, indicating he has been working on this task for a long time without completing it. His high workload and low completion rate make him the most suitable candidate for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 10890, NULL, '933f31b9be30b0ad98a296e440991fc4f27b2acde8cd713446ef43c677b42b16', 0, NULL, NULL, 2, '2026-09-08 03:00:09'),
(65, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":20},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":290,\"active_committees\":2,\"completion_rate\":20,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":3},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5:1.5b', 2253, 'AI task generation is currently unavailable. Please fill in the task details manually.', '01d86cfc7fe5a164a8b3745892bcf3896a1107e89ea48cfdf5b069f9a6658ebd', 0, NULL, NULL, 2, '2026-09-11 21:32:37'),
(66, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":20},{\"member_id\":6,\"name\":\"Jose Ramirez\",\"role\":\"Vice Chairperson\",\"current_workload\":290,\"active_committees\":2,\"completion_rate\":20,\"on_time_rate\":0,\"overdue_tasks\":2,\"days_since_last_assignment\":3},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, NULL, '{\"description\":\"The Vice Chairperson, Jose Ramirez, has a significant workload and a low completion rate, indicating a need for urgent attention. His high overdue tasks and long days since last assignment make him the most suitable candidate for this task.\",\"priority\":\"Urgent\",\"workload_points\":60,\"due_date\":\"2026-10-01\",\"status\":\"Pending\"}', NULL, NULL, 'Jose Ramirez\'s high workload and overdue tasks, combined with his low completion and on-time rates, make him the most urgent candidate for the road condition assessment and repair planning task. His high days since last assignment further emphasizes the need for immediate attention.', NULL, 'N/A', 'qwen2.5:1.5b', 28585, NULL, '01d86cfc7fe5a164a8b3745892bcf3896a1107e89ea48cfdf5b069f9a6658ebd', 0, 1, NULL, 2, '2026-09-11 21:33:54');
INSERT INTO `ai_recommendations` (`recommendation_id`, `committee_id`, `workload_id`, `recommended_member_id`, `recommended_score`, `confidence`, `candidates_json`, `ai_available`, `ai_recommended_member_id`, `ai_generated_fields`, `ai_confidence`, `ai_risk_assessment`, `ai_reasoning`, `ai_alternative_ids`, `agreement_status`, `ai_model_used`, `ai_response_time_ms`, `ai_warning`, `candidates_hash`, `was_overridden`, `admin_followed_ai`, `final_member_id`, `generated_by`, `generated_at`) VALUES
(67, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":26},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'qwen2.5:1.5b', 2260, 'AI task generation is currently unavailable. Please fill in the task details manually.', 'b659cc450444e315b6ccf2ac2973bc66920c9e5744fe2c0f2513d67d590f17c2', 0, NULL, NULL, 2, '2026-09-17 13:33:37'),
(68, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Road Condition Assessment and Repair Planning\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":26},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, 7, '{\"description\":\"This task involves assessing and planning for road conditions and repairs. Maria Santos is the Chairperson and has the highest completion rate and on-time rate, making her the most suitable for this task due to her leadership and reliability.\",\"priority\":\"Urgent\",\"workload_points\":85,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Liza Fernandez has the highest workload, most overdue tasks, and the longest time since her last assignment, indicating she is the least suitable for this urgent task. Maria Santos, on the other hand, is the Chairperson with a high completion rate and on-time rate, making her the most appropriate choice.', NULL, 'N/A', 'qwen2.5:1.5b', 22827, NULL, 'b659cc450444e315b6ccf2ac2973bc66920c9e5744fe2c0f2513d67d590f17c2', 0, NULL, NULL, 2, '2026-09-17 13:35:44'),
(73, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Draft sanitation ordinance revision\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":27},{\"member_id\":7,\"name\":\"Liza Fernandez\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, 7, '{\"description\":\"The task of drafting a sanitation ordinance revision is particularly urgent as it requires immediate attention to address community concerns and ensure compliance with new regulations.\",\"priority\":\"Urgent\",\"workload_points\":60,\"due_date\":\"2026-10-05\",\"status\":\"Pending\"}', NULL, NULL, 'Liza Fernandez has a lower current workload, fewer overdue tasks, and a higher completion rate compared to Maria Santos, making her the most suitable candidate for this urgent task. Her higher completion rate and lower days_since_last_assignment indicate that she is more efficient and reliable, ensuring that the task can be completed on time.', NULL, 'N/A', 'qwen2.5:1.5b', 21182, NULL, '4c1db58059d5b2d0c4c7c4bfbd8aa1964a58d20dab5348fe2e82c391275afb48', 0, NULL, NULL, 3, '2026-09-18 11:35:59'),
(74, 10, 29, NULL, NULL, NULL, '{\"task_title\":\"Tree planting\",\"members\":[{\"member_id\":17,\"name\":\"Lance Lerin\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":100,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999}]}', 1, 17, '{\"description\":\"Committee members are encouraged to participate in tree planting to contribute to the environment. This task is vital for the committee\'s environmental goals.\",\"priority\":\"Urgent\",\"workload_points\":50,\"due_date\":\"2026-10-01\",\"status\":\"Pending\"}', NULL, NULL, 'Lance Lerin, the Chairperson, has the highest workload and the longest time since the last assignment. This makes him the most suitable candidate for this urgent task.', NULL, 'N/A', 'qwen2.5:1.5b', 7616, NULL, '26606b0ec20db6276801cd5f7effcc7c061800f7b346fe4c5619d5e10396d328', 0, 1, 17, 3, '2026-09-18 11:36:42'),
(75, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":2,\"completion_rate\":100,\"on_time_rate\":25,\"overdue_tasks\":0,\"days_since_last_assignment\":72},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":66.7,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":18}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'gemini-2.5-flash', 3160, 'AI task generation is currently unavailable. Please fill in the task details manually.', '04f8ca690916e9f150701e4d10b0b37dd4e50ffc6037f38f658e35e8afc7e2e6', 0, NULL, NULL, 6, '2026-09-18 19:56:34'),
(76, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":2,\"completion_rate\":100,\"on_time_rate\":25,\"overdue_tasks\":0,\"days_since_last_assignment\":72},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":66.7,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":18}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'gemini-2.5-flash', 5667, 'AI task generation is currently unavailable. Please fill in the task details manually.', '04f8ca690916e9f150701e4d10b0b37dd4e50ffc6037f38f658e35e8afc7e2e6', 0, NULL, NULL, 6, '2026-09-18 19:56:59'),
(77, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":2,\"completion_rate\":100,\"on_time_rate\":25,\"overdue_tasks\":0,\"days_since_last_assignment\":72},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":66.7,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":18}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null}', NULL, NULL, '', NULL, 'N/A', 'gemini-2.5-flash', 3050, 'AI task generation is currently unavailable. Please fill in the task details manually.', '04f8ca690916e9f150701e4d10b0b37dd4e50ffc6037f38f658e35e8afc7e2e6', 0, NULL, NULL, 6, '2026-09-18 19:57:23'),
(78, 5, NULL, NULL, NULL, NULL, '{\"task_title\":\"Free Check Up for Adults\",\"members\":[{\"member_id\":2,\"name\":\"Princess Ann Reyes\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":2,\"completion_rate\":100,\"on_time_rate\":25,\"overdue_tasks\":0,\"days_since_last_assignment\":72},{\"member_id\":3,\"name\":\"Pedro Reyes\",\"role\":\"Vice Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999},{\"member_id\":4,\"name\":\"Lance Lerin\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":3,\"completion_rate\":66.7,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":18}]}', 1, 3, '{\"description\":\"Organize and coordinate a community health event offering free medical check-ups and basic health assessments for adult residents.\",\"priority\":\"Medium\",\"workload_points\":50,\"due_date\":\"2026-10-18\",\"status\":\"Pending\"}', NULL, NULL, 'Pedro Reyes is recommended because he currently has zero active workload, is involved in only one active committee, and has gone the longest without a task assignment (999 days).', NULL, 'N/A', 'gemini-3.6-flash', 10695, NULL, '04f8ca690916e9f150701e4d10b0b37dd4e50ffc6037f38f658e35e8afc7e2e6', 0, NULL, NULL, 6, '2026-09-18 20:00:05'),
(80, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"TEST\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":28,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"TEST\",\"priority\":\"Medium\",\"status\":\"Completed\",\"due_date\":\"2026-08-22\"},{\"title\":\"Review supplemental budget request\",\"priority\":\"Urgent\",\"status\":\"Completed\",\"due_date\":\"2026-07-30\"}]},{\"member_id\":7,\"name\":\"Xcnal\",\"role\":\"Member\",\"current_workload\":0,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":50,\"overdue_tasks\":0,\"days_since_last_assignment\":999,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[]}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"workload_points\":null,\"due_date\":null,\"status\":null,\"expertise_match\":null,\"experience_match\":null,\"workload_factor\":null,\"committee_relevance\":null,\"overall_relevance\":null}', NULL, NULL, '', NULL, 'N/A', 'gemini-3.6-flash', 9819, 'AI task generation is currently unavailable. Please fill in the task details manually.', 'a468f08a867852616aaee267a2ecca93c8d74ff8c480b8b5c292f4bba2757c2b', 0, NULL, NULL, 6, '2026-09-19 16:16:36'),
(81, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Department of Education Funding Request Evaluation\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"active_assignments\":0,\"active_committees\":1,\"completion_rate\":100,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":63,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"Review supplemental budget request\",\"priority\":\"Urgent\",\"status\":\"Completed\",\"due_date\":\"2026-07-30\"}]},{\"member_id\":7,\"name\":\"Xcnal\",\"role\":\"Member\",\"active_assignments\":1,\"active_committees\":1,\"completion_rate\":0,\"on_time_rate\":50,\"overdue_tasks\":1,\"days_since_last_assignment\":28,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"TEST\",\"priority\":\"Medium\",\"status\":\"In Progress\",\"due_date\":\"2026-08-22\"}]}]}', 1, 5, '{\"description\":\"Evaluate the Department of Education\'s funding request to determine its fiscal viability and alignment with committee budget allocations.\",\"priority\":\"Medium\",\"due_date\":\"2026-10-10\",\"status\":\"Pending\",\"expertise_match\":80,\"experience_match\":80,\"workload_factor\":100,\"committee_relevance\":95,\"overall_relevance\":88}', NULL, NULL, 'Maria Santos is recommended as she currently has no active assignments, no overdue tasks, and a 100% completion rate. She also has direct experience reviewing budget requests and has gone 63 days without a new assignment.', NULL, 'N/A', 'gemini-3.6-flash', 8491, NULL, 'a018b1729b9292cbc6761d7fa80714989ec3ef92e8af6072a431e0fd2210e880', 0, 1, 5, 6, '2026-09-19 17:45:26'),
(82, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Department of Education Funding Request Evaluation\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"active_assignments\":1,\"active_committees\":1,\"completion_rate\":50,\"on_time_rate\":0,\"overdue_tasks\":0,\"days_since_last_assignment\":0,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"Department of Education Funding Request Evaluation\",\"priority\":\"Medium\",\"status\":\"In Progress\",\"due_date\":\"2026-10-10\"},{\"title\":\"Review supplemental budget request\",\"priority\":\"Urgent\",\"status\":\"Completed\",\"due_date\":\"2026-07-30\"}]},{\"member_id\":7,\"name\":\"Xcnal\",\"role\":\"Member\",\"active_assignments\":1,\"active_committees\":1,\"completion_rate\":0,\"on_time_rate\":50,\"overdue_tasks\":1,\"days_since_last_assignment\":28,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"TEST\",\"priority\":\"Medium\",\"status\":\"In Progress\",\"due_date\":\"2026-08-22\"}]}]}', 0, NULL, '{\"description\":\"\",\"priority\":null,\"due_date\":null,\"status\":null,\"expertise_match\":null,\"experience_match\":null,\"workload_factor\":null,\"committee_relevance\":null,\"overall_relevance\":null}', NULL, NULL, '', NULL, 'N/A', 'gemini-3.6-flash', 1572, 'AI task generation is currently unavailable. Please fill in the task details manually.', 'dc0052531fa59065cd724222cd7adda59b6070a403a5a5d2ab2b3a62cdc2d8b4', 0, NULL, NULL, 6, '2026-09-19 17:48:32'),
(83, 6, NULL, NULL, NULL, NULL, '{\"task_title\":\"Department of Education Funding Request Evaluation\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"active_assignments\":2,\"active_committees\":1,\"days_since_last_assignment\":1,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"Department of Education Funding Request Evaluation\",\"priority\":\"Medium\",\"due_date\":\"2026-10-10\"},{\"title\":\"Review supplemental budget request\",\"priority\":\"Urgent\",\"due_date\":\"2026-07-30\"}]},{\"member_id\":7,\"name\":\"Xcnal\",\"role\":\"Member\",\"active_assignments\":1,\"active_committees\":1,\"days_since_last_assignment\":29,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"TEST\",\"priority\":\"Medium\",\"due_date\":\"2026-08-22\"}]}]}', 1, 7, '{\"description\":\"Evaluate the budgetary proposal and funding requirements for the Department of Education to ensure proper resource allocation and fiscal compliance.\",\"priority\":\"Medium\",\"due_date\":\"2026-10-10\",\"expertise_match\":50,\"experience_match\":50,\"workload_factor\":85,\"committee_relevance\":80,\"overall_relevance\":68}', NULL, NULL, 'Profile data is limited for both candidates. Member 7 is recommended based on lower active workload (1 assignment versus 2) and longer recency since the last assignment (29 days).', NULL, 'N/A', 'gemini-3.6-flash', 8723, NULL, '1df197778bfc0b4c8775309e5ef93b4f039eed32b117db7447ae54df6e18796e', 0, 1, 7, 6, '2026-09-20 02:36:27'),
(84, 6, 33, NULL, NULL, NULL, '{\"task_title\":\"Department of Education Funding Request Evaluation\",\"members\":[{\"member_id\":5,\"name\":\"Maria Santos\",\"role\":\"Chairperson\",\"active_assignments\":1,\"active_committees\":1,\"days_since_last_assignment\":64,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"Review supplemental budget request\",\"priority\":\"Urgent\",\"due_date\":\"2026-07-30\"}]},{\"member_id\":7,\"name\":\"Xcnal\",\"role\":\"Member\",\"active_assignments\":1,\"active_committees\":1,\"days_since_last_assignment\":29,\"background\":{\"highest_education\":null,\"degree_course\":null,\"school_university\":null,\"major_specialization\":null,\"certifications_training\":null,\"current_profession\":null,\"years_experience\":null,\"previous_positions\":null,\"previous_organizations\":null,\"government_experience\":null,\"primary_expertise\":null,\"secondary_expertise\":null,\"knowledge_areas\":null,\"relevant_skills\":null,\"committee_expertise\":null,\"expertise_keywords\":null},\"previous_assignments\":[{\"title\":\"TEST\",\"priority\":\"Medium\",\"due_date\":\"2026-08-22\"}]}]}', 1, 5, '{\"description\":\"Evaluate the Department of Education\'s funding request to ensure fiscal compliance, proper budget allocation, and alignment with national education priorities.\",\"priority\":\"Medium\",\"due_date\":\"2026-10-10\",\"expertise_match\":50,\"experience_match\":50,\"workload_factor\":85,\"committee_relevance\":90,\"overall_relevance\":72}', NULL, NULL, 'Profile data is limited for available members. Maria Santos is selected as Chairperson of the Committee on Budget and Appropriation, having a longer recency gap since her last task assignment (64 days) and a relevant prior task involving budget request reviews.', NULL, 'N/A', 'gemini-3.6-flash', 6703, NULL, '86a64016ab4db42b6f8b90c656ddc8eebef9cd9d29f89d7f33688a4669ee2c9a', 0, 1, 5, 6, '2026-09-20 02:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `ai_system_settings`
--

CREATE TABLE `ai_system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_system_settings`
--

INSERT INTO `ai_system_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('ollama_enabled', '1', 1, '2026-09-01 23:09:21'),
('ollama_model', 'qwen2.5:1.5b', 1, '2026-09-01 23:09:21'),
('ollama_timeout', '30', 1, '2026-09-01 23:09:21'),
('ollama_url', 'http://localhost:11434', 1, '2026-09-01 23:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `ai_weight_config`
--

CREATE TABLE `ai_weight_config` (
  `factor_key` varchar(50) NOT NULL,
  `factor_label` varchar(150) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight` tinyint(3) UNSIGNED NOT NULL DEFAULT 10,
  `direction` enum('lower_is_better','higher_is_better') NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_weight_config`
--

INSERT INTO `ai_weight_config` (`factor_key`, `factor_label`, `description`, `weight`, `direction`, `is_enabled`, `updated_by`, `updated_at`) VALUES
('active_committees', 'Active Committee Count', 'Number of committees the member currently sits on.', 10, 'lower_is_better', 1, 1, '2026-08-22 12:53:59'),
('assignment_recency', 'Fairness / Assignment Recency', 'Days since the member\'s last new task assignment (rotates work fairly).', 10, 'higher_is_better', 1, 1, '2026-08-22 12:53:59'),
('completion_rate', 'Task Completion Rate', 'Share of assigned tasks the member has completed.', 20, 'higher_is_better', 1, 1, '2026-08-22 12:53:59'),
('current_workload', 'Current Workload', 'Total active workload points already carried (Pending/In Progress tasks).', 25, 'lower_is_better', 1, 1, '2026-08-22 12:53:59'),
('overdue_count', 'Overdue Task Count', 'Number of currently overdue tasks the member is carrying.', 15, 'lower_is_better', 1, 1, '2026-08-22 12:53:59'),
('role_weight', 'Committee Role Weight', 'Admin-configurable trust multiplier by role (Chairperson/Vice/Member).', 30, 'higher_is_better', 1, 1, '2026-08-22 12:53:59'),
('timeliness', 'On-Time Completion Rate', 'Share of completed tasks finished on or before their due date.', 15, 'higher_is_better', 1, 1, '2026-08-22 12:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `committees`
--

CREATE TABLE `committees` (
  `committee_id` int(11) NOT NULL,
  `committee_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `jurisdiction_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','Dissolved') NOT NULL DEFAULT 'Active',
  `date_created` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committees`
--

INSERT INTO `committees` (`committee_id`, `committee_name`, `description`, `jurisdiction_id`, `status`, `date_created`, `created_by`, `created_at`, `updated_at`) VALUES
(5, 'Committee on Health and Sanitation', 'Handles public health programs, hospital services, and sanitation ordinances.', 1, 'Active', '2026-04-29', 1, '2026-07-28 13:04:03', '2026-07-28 13:04:03'),
(6, 'Committee on Budget and Appropriation', 'Reviews the annual budget, fund releases, and supplemental appropriations.', 2, 'Active', '2026-05-14', 1, '2026-07-28 13:04:03', '2026-09-19 15:30:32'),
(8, 'Committee on Infrastructure and Public Works', 'Reviews road, drainage, and public facility construction projects.', 4, 'Active', '2026-06-13', 1, '2026-07-28 13:04:03', '2026-07-28 13:04:03'),
(10, 'Committee on Environment', 'dsdsffsdfgsd', 5, 'Active', '2026-09-18', 3, '2026-09-18 11:29:34', '2026-09-18 11:29:34'),
(11, 'Committee on Public Works and Infrastructure', 'TEST', 2, 'Active', '2026-09-20', 6, '2026-09-20 02:29:02', '2026-09-20 02:29:02');

-- --------------------------------------------------------

--
-- Table structure for table `committee_members`
--

CREATE TABLE `committee_members` (
  `committee_member_id` int(11) NOT NULL,
  `committee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `member_role` enum('Chairperson','Vice Chairperson','Member') NOT NULL DEFAULT 'Member',
  `assigned_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_members`
--

INSERT INTO `committee_members` (`committee_member_id`, `committee_id`, `user_id`, `member_role`, `assigned_date`, `status`, `created_at`) VALUES
(2, 5, 3, 'Chairperson', '2026-05-29', 'Active', '2026-07-28 13:04:03'),
(3, 5, 5, 'Vice Chairperson', '2026-05-29', 'Active', '2026-07-28 13:04:03'),
(4, 5, 6, 'Member', '2026-05-29', 'Active', '2026-07-28 13:04:03'),
(5, 6, 4, 'Chairperson', '2026-05-29', 'Active', '2026-07-28 13:04:03'),
(7, 6, 8, 'Member', '2026-05-29', 'Active', '2026-07-28 13:04:03'),
(12, 8, 6, 'Vice Chairperson', '2026-05-29', 'Active', '2026-07-28 13:04:03'),
(17, 10, 6, 'Chairperson', '2026-09-18', 'Active', '2026-09-18 11:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `committee_performance`
--

CREATE TABLE `committee_performance` (
  `performance_id` int(11) NOT NULL,
  `committee_id` int(11) NOT NULL,
  `evaluation_period` varchar(100) NOT NULL,
  `total_tasks` int(11) NOT NULL DEFAULT 0,
  `completed_tasks` int(11) NOT NULL DEFAULT 0,
  `pending_tasks` int(11) NOT NULL DEFAULT 0,
  `overdue_tasks` int(11) NOT NULL DEFAULT 0,
  `completion_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_performance`
--

INSERT INTO `committee_performance` (`performance_id`, `committee_id`, `evaluation_period`, `total_tasks`, `completed_tasks`, `pending_tasks`, `overdue_tasks`, `completion_rate`, `remarks`, `generated_at`) VALUES
(2, 5, 'Demo Data Snapshot', 3, 1, 1, 1, 33.33, 'Seeded demo snapshot.', '2026-06-28 13:04:04'),
(3, 6, 'Demo Data Snapshot', 3, 1, 1, 0, 33.33, 'Seeded demo snapshot.', '2026-06-28 13:04:04'),
(4, 6, 'August 2026', 4, 2, 2, 2, 50.00, 'Auto-generated snapshot from live workload data.', '2026-08-08 10:03:23'),
(7, 6, 'August 2026', 3, 2, 1, 1, 66.67, 'Auto-generated snapshot from live workload data.', '2026-08-11 20:20:50'),
(10, 6, 'September 2026', 6, 3, 3, 1, 50.00, 'Auto-generated snapshot from live workload data.', '2026-09-01 22:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `committee_reports`
--

CREATE TABLE `committee_reports` (
  `report_id` int(11) NOT NULL,
  `committee_id` int(11) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_type` enum('Committee','Workload','Performance','Monthly','Annual') NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_reports`
--

INSERT INTO `committee_reports` (`report_id`, `committee_id`, `generated_by`, `report_title`, `report_type`, `file_path`, `generated_at`) VALUES
(1, NULL, 1, 'Committee Report', 'Committee', 'committee-committee-report-20260728-162740.xls', '2026-07-28 16:27:40'),
(2, NULL, 1, 'Committee Report', 'Committee', 'committee-committee-report-20260811-001740.pdf', '2026-08-11 00:17:40'),
(3, 8, 1, 'Committee Report', 'Committee', 'committee-committee-report-20260811-202923.pdf', '2026-08-11 20:29:23'),
(4, NULL, 1, 'Committee Report', 'Committee', 'committee-committee-report-20260831-134631.xls', '2026-08-31 13:46:31'),
(5, NULL, 6, 'Workload Report', 'Workload', 'committee-workload-report-2026-09-19.csv', '2026-09-19 15:21:37');

-- --------------------------------------------------------

--
-- Table structure for table `jurisdictions`
--

CREATE TABLE `jurisdictions` (
  `jurisdiction_id` int(11) NOT NULL,
  `jurisdiction_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `scope_definition` text DEFAULT NULL,
  `covered_areas` text DEFAULT NULL,
  `primary_responsibilities` text DEFAULT NULL,
  `typical_legislative_matters` text DEFAULT NULL,
  `outside_scope` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jurisdictions`
--

INSERT INTO `jurisdictions` (`jurisdiction_id`, `jurisdiction_name`, `category`, `description`, `scope_definition`, `covered_areas`, `primary_responsibilities`, `typical_legislative_matters`, `outside_scope`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Health and Sanitation', 'Social Services', 'Oversees public health programs, sanitation, and hospital services.', 'Matters concerning public health protection, sanitation services, disease prevention, health facilities, and local policies that support safe and healthy communities.', 'Public health programs\nSanitation and hygiene\nDisease prevention and health promotion\nPublic health facilities and services\nFood, water, and environmental health concerns', 'Review local measures that protect community health\nExamine reports and recommendations on sanitation and health services\nConsider access, quality, and delivery of local health programs\nCoordinate legislative review of health-related concerns with relevant offices and stakeholders', 'Health and sanitation ordinances\nPublic health programs and service policies\nLocal measures for disease prevention\nRegulation of sanitation-related practices\nResolutions concerning health facilities and community health initiatives', 'Matters primarily concerning budgeting, infrastructure, public safety, or environmental management unless they have a direct health component\nClinical or professional decisions reserved for qualified health authorities\nMatters under the exclusive authority of national government agencies', 'This is a general legislative scope description for local committee reference and is not an official statement of legal authority. Related matters may be coordinated with other jurisdictions when responsibilities overlap.', 'Active', 1, '2026-07-27 15:49:23', '2026-09-19 12:27:36'),
(2, 'Budget and Appropriations', 'Finance', 'Reviews and recommends the annual budget and fund allocations.', 'Matters concerning the preparation, review, authorization, and monitoring of public funds, local revenues, expenditures, and financial policies of the local government.', 'Annual and supplemental budgets\nPublic expenditures and appropriations\nLocal revenues, fees, and charges\nFinancial controls and fiscal reporting\nAllocation of funds to programs, services, and projects', 'Review proposed budgets and requests for appropriations\nExamine whether proposed expenditures support approved programs and services\nReview financial reports and fiscal recommendations\nConsider local revenue and expenditure policies\nAssess the financial implications of proposed measures', 'Annual budget ordinances\nSupplemental budget measures\nAppropriation ordinances and resolutions\nLocal revenue and fee measures\nPolicies on expenditure controls, fund allocation, and fiscal reporting', 'Technical implementation of accounting procedures assigned to authorized finance offices\nMatters primarily concerning health, public safety, infrastructure, or environmental policy unless their funding is under review\nNational appropriations and fiscal matters outside local legislative authority', 'This description presents a general local legislative scope and does not establish official fiscal authority or replace applicable budgeting, auditing, and procurement rules.', 'Active', 1, '2026-07-27 15:49:23', '2026-09-19 12:27:36'),
(3, 'Peace and Order', 'Public Safety', 'Oversees police matters, public safety, and disaster preparedness.', 'Matters concerning public safety, peace and order, emergency preparedness, community security, and local policies that help protect residents and maintain orderly communities.', 'Peace and order programs\nCommunity safety and crime prevention\nEmergency preparedness and response\nDisaster risk reduction coordination\nPublic safety facilities, services, and local enforcement support', 'Review local policies supporting peace, order, and public safety\nExamine reports on safety conditions and emergency readiness\nConsider measures that improve community protection and incident response\nReview coordination arrangements among local safety offices and stakeholders\nMonitor legislative concerns affecting public order', 'Peace and order ordinances\nPublic safety and emergency preparedness measures\nDisaster risk reduction policies\nCommunity safety programs\nResolutions concerning local enforcement support and emergency response', 'Operational investigations or enforcement decisions assigned to law enforcement and emergency authorities\nMatters primarily concerning health, transport, budgeting, or infrastructure unless they directly affect public safety\nCriminal prosecution and national security matters outside local legislative authority', 'This is a general local legislative scope description. It supports committee classification and review but does not confer enforcement powers or replace the mandates of competent authorities.', 'Active', 1, '2026-07-27 15:49:23', '2026-09-19 12:27:36'),
(4, 'Infrastructure and Public Works', 'Infrastructure', 'Reviews infrastructure projects, roads, and public facilities.', 'Matters concerning the planning, development, maintenance, and improvement of public infrastructure, public works, roads, facilities, and related local development policies.', 'Roads, streets, bridges, and drainage\nPublic buildings and facilities\nFlood control and related public works\nInfrastructure planning and project implementation\nConstruction, maintenance, and accessibility of local facilities', 'Review infrastructure and public works proposals\nExamine project plans, implementation reports, and maintenance needs\nConsider whether public facilities respond to community needs\nReview policies for safe, accessible, and sustainable local infrastructure\nAssess the public works implications of proposed legislative measures', 'Infrastructure project ordinances and resolutions\nRoad, drainage, and public facility policies\nPublic works programs and funding requests\nMeasures concerning construction, maintenance, and accessibility\nLocal development policies involving public facilities', 'Private construction approvals and technical permitting decisions assigned to authorized offices\nMatters primarily concerning transport operations, environmental policy, or budgeting unless directly related to a public works proposal\nNational infrastructure projects outside local legislative authority', 'This description is a general local legislative scope reference. Technical design, procurement, and project administration remain subject to the applicable offices, standards, and approval processes.', 'Active', 1, '2026-07-27 15:49:23', '2026-09-19 12:27:36'),
(5, 'Environmental Management', 'Environment', 'Covers matters concerning environmental protection, waste management, pollution control, conservation, green spaces, and programs promoting a clean and sustainable city.', 'Matters concerning environmental protection, waste management, pollution prevention, conservation, and local policies that promote a clean, resilient, and sustainable community.', 'Solid waste management and cleanliness\nPollution prevention and control\nWater, air, and land protection\nConservation of natural resources and green spaces\nClimate resilience and environmental awareness', 'Review local environmental policies and programs\nExamine reports on waste management, pollution, and environmental conditions\nConsider measures that protect natural resources and public spaces\nReview community environmental education and conservation initiatives\nAssess environmental considerations in proposed local policies and projects', 'Solid waste management ordinances\nEnvironmental protection and cleanliness measures\nPollution control policies\nConservation and green-space programs\nResolutions concerning climate resilience, environmental education, and sustainable local practices', 'Technical environmental permits and enforcement actions assigned to authorized environmental offices\nMatters primarily concerning health, infrastructure, or public safety unless there is a direct environmental component\nNational environmental regulation and protected-area decisions outside local legislative authority', 'This is a general legislative scope description for local reference and does not replace national environmental laws, agency regulations, technical assessments, or official permitting requirements.', 'Active', 1, '2026-08-11 20:26:28', '2026-09-19 12:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `login_security`
--

CREATE TABLE `login_security` (
  `email` varchar(255) NOT NULL,
  `failed_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_security`
--

INSERT INTO `login_security` (`email`, `failed_attempts`, `locked_until`, `last_attempt_at`) VALUES
('admin@cmas.local', 2, NULL, '2026-09-18 09:45:31'),
('adsf', 1, NULL, '2026-08-26 11:50:45'),
('asdf', 0, '2026-08-26 11:55:50', '2026-08-26 11:50:50'),
('dsdfsdf', 1, NULL, '2026-08-26 11:48:22'),
('grace@cmas.local', 0, '2026-09-17 13:04:10', '2026-09-17 12:59:10'),
('lance@cmas.local', 0, NULL, '2026-09-18 11:39:25'),
('lancyhr1@gmail.com', 0, '2026-09-19 11:34:08', '2026-09-19 11:29:08'),
('lerinlance47@gmail.com', 0, NULL, '2026-09-20 02:22:14'),
('lerinlance88@gmail.com', 0, NULL, '2026-09-20 02:45:14'),
('member3@cmas.local', 0, NULL, '2026-09-02 05:10:24'),
('member5@cmas.local', 1, NULL, '2026-09-17 12:52:35'),
('member7@cmas.local', 1, NULL, '2026-09-04 17:19:43'),
('nathaniel.lei20@gmail.com', 0, NULL, '2026-09-18 10:08:06'),
('not-a-real-user@example.com', 0, '2026-09-19 11:36:02', '2026-09-19 11:31:02'),
('reyes.kaluret@gmail.com', 0, NULL, '2026-09-18 19:43:30'),
('reyes@cmas.local', 1, NULL, '2026-09-18 11:20:24'),
('sdf', 1, NULL, '2026-08-26 11:49:50'),
('sdfdsf', 1, NULL, '2026-08-26 11:50:31'),
('sdfq', 1, NULL, '2026-08-26 11:49:21'),
('sdfs', 2, NULL, '2026-08-26 11:50:13'),
('sdfsd', 1, NULL, '2026-08-26 11:47:29'),
('sdsdfsd', 1, NULL, '2026-08-26 11:50:34'),
('sfdsdq', 1, NULL, '2026-08-26 11:48:58'),
('staff@cmas.local', 0, NULL, '2026-09-17 22:17:39'),
('xcnal47@gmail.com', 0, NULL, '2026-09-19 19:05:30');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `activity_log_id` int(11) DEFAULT NULL,
  `message` varchar(500) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `recipient_user_id`, `activity_log_id`, `message`, `url`, `read_at`, `created_at`) VALUES
(1, 8, NULL, 'Assigned task: TEST', 'http://localhost/committee-management-system/modules/workload/task.php?id=19', '2026-09-19 16:23:05', '2026-08-22 12:20:24'),
(2, 11, NULL, 'Assigned task: Road Condition Assessment and Repair Planning', 'http://localhost/committee-management-system/modules/workload/task.php?id=28', NULL, '2026-09-18 02:40:30'),
(3, 6, NULL, 'Assigned task: Tree planting', 'http://localhost/committee-management-system/modules/workload/task.php?id=29', '2026-09-19 16:22:56', '2026-09-18 11:37:04'),
(4, 4, 666, 'New task assigned to you: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/task.php?id=30', NULL, '2026-09-19 17:45:43'),
(5, 4, 667, 'Task completed: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/task.php?id=30', NULL, '2026-09-19 17:46:20'),
(6, 4, 668, 'Task updated: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/task.php?id=30', NULL, '2026-09-19 17:46:35'),
(7, 8, 669, 'New task assigned to you: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/task.php?id=31', '2026-09-19 19:21:25', '2026-09-19 17:49:01'),
(8, 8, 695, 'Task deleted: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/index.php', NULL, '2026-09-20 02:36:09'),
(9, 8, 696, 'New task assigned to you: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/task.php?id=32', NULL, '2026-09-20 02:37:50'),
(10, 4, 697, 'Task deleted: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/index.php', NULL, '2026-09-20 02:38:07'),
(11, 8, 698, 'Task deleted: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/index.php', NULL, '2026-09-20 02:38:10'),
(12, 4, 699, 'New task assigned to you: Department of Education Funding Request Evaluation', 'http://localhost/committee-management-system/modules/workload/task.php?id=33', NULL, '2026-09-20 02:38:35');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `otp_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`otp_id`, `user_id`, `otp_hash`, `expires_at`, `is_used`, `attempts`, `created_at`) VALUES
(1, 1, '$2y$10$dBec5L8I/q3by8sqzWH/f.Zwhx0gW4tr4OnvF7l5Jsz9g4kYaRvt2', '2026-08-20 06:56:44', 1, 1, '2026-08-20 06:56:14'),
(2, 6, '$2y$10$9vxdyI7tLot75Q9yJppbmOX5uMhEPQY9b5wirimM2hcHvhbDgXKLK', '2026-08-20 07:01:19', 1, 1, '2026-08-20 07:00:49'),
(3, 1, '$2y$10$4YvcjG6wj3YBW7i8hpWB1e2w3KsheUZU9ECdjZr7nPipTH0rxKC3a', '2026-08-22 08:09:23', 1, 0, '2026-08-22 08:08:53'),
(4, 1, '$2y$10$n20pd21b/C.6IMLt4IvGI.MD8tqlBZx.2OOounXi/9bboqsyDqFPm', '2026-08-22 08:10:56', 1, 1, '2026-08-22 08:10:26'),
(5, 1, '$2y$10$EBLDMIygAxQ6pCDoG4aEReHHauWm.XmjbTh0KIjVjASfvufOExBhC', '2026-08-22 12:18:27', 1, 1, '2026-08-22 12:17:57'),
(6, 6, '$2y$10$CQXqWsOAhTFhBMZZ4NoaMu.EViHn/t0BHZhedoNKjN3lPh50Uyvrq', '2026-08-22 12:27:05', 1, 1, '2026-08-22 12:26:35'),
(7, 1, '$2y$10$SVc9wMP0iTiJARO3Tkk7iuIL9EQz74OZswkj9pWuZiq5u/wV9.G2e', '2026-08-22 12:39:40', 1, 1, '2026-08-22 12:39:10'),
(8, 1, '$2y$10$K44zfervya5Wx6fbb5xlV.aEeck/rX3NtUH5/hpCvkP.WkF9i/Y0K', '2026-08-22 14:11:16', 1, 1, '2026-08-22 14:10:46'),
(9, 1, '$2y$10$dkRXFsHAsf1K/RjByulNmeOEy.nQrUnzr2btGBU6kuLQolIGxhmB2', '2026-08-24 14:32:01', 1, 1, '2026-08-24 14:31:31'),
(10, 1, '$2y$10$6CCmcikPOktxMxds0BlC5.YhykaxGbO5L3N4PmHxSyxYOn9Ea65te', '2026-08-26 11:52:19', 1, 1, '2026-08-26 11:51:49'),
(11, 1, '$2y$10$0G0KzRQPYJ7dGuQrjCoz4.RB9eWZf4hV58YWwPKVKKSmD7Is5OcVK', '2026-08-26 11:56:17', 1, 1, '2026-08-26 11:55:47'),
(12, 1, '$2y$10$IIY3Ob2Rah9.LiPKU6KwIeaFOk4fX29qbc2gkUPGIxqUNYIanWk0u', '2026-08-27 14:05:08', 1, 1, '2026-08-27 14:04:38'),
(13, 1, '$2y$10$MO2accvIpx76m86Zwf7vNeGJUQeEFi1N.0mGOwE4zij/1rfdpoE7O', '2026-08-27 14:40:53', 1, 1, '2026-08-27 14:40:23'),
(14, 1, '$2y$10$I/hmNl9/dSVwR6XXZgtlN.v68TmSNEGiSwqSO9U.9s3cQuyyErLXW', '2026-08-27 16:41:37', 1, 1, '2026-08-27 16:41:07'),
(15, 1, '$2y$10$8FqH8y0FVczLa9Iv0NolHe.g6OwcZwV./EJiSznf/7Q.pFirl5cjK', '2026-08-27 17:05:53', 1, 1, '2026-08-27 17:05:23'),
(16, 1, '$2y$10$/abYzwAd8W8KO.UAgvvbZ.WUMtSBvOsauGp71SMzu6epKqIQUp1/u', '2026-08-27 19:20:19', 1, 1, '2026-08-27 19:19:49'),
(17, 1, '$2y$10$5FSI8r/4JzD2Q.hq5R2FgebZw0Hbh.Aba0DguWiD3qP.QU.XzDc4.', '2026-08-28 00:14:33', 1, 1, '2026-08-28 00:14:03'),
(18, 1, '$2y$10$Kxjxw4KiTvusj90zyDEke.rFj7h6zwUCsR0lsbvP/51Gga/unav2m', '2026-08-28 01:42:40', 1, 1, '2026-08-28 01:42:10'),
(19, 1, '$2y$10$3xgza4fEPOMRX37ONrao7OQf3AuFUsBp.FB6xT013.Qbpm1oU2yLS', '2026-08-28 08:37:54', 1, 1, '2026-08-28 08:37:24'),
(20, 1, '$2y$10$C3ZzVlw7ha.IWqu2MUGsTOSQ8viGJ.wBnANwyEo46YT8LYukl/V.O', '2026-08-28 10:16:35', 1, 1, '2026-08-28 10:16:05'),
(21, 1, '$2y$10$eQoXI8qkubkTraykNLNT2ujT7y6iZuaBZbpSHETjEG5ttQ6WbYxU2', '2026-08-28 19:37:01', 1, 1, '2026-08-28 19:36:31'),
(22, 1, '$2y$10$EobDD7.HDg5jOEoSlEyb2uV5qGNS9Xbw5LSkBCmneJfH11MW43Xb.', '2026-08-29 20:41:39', 1, 1, '2026-08-29 20:41:09'),
(23, 1, '$2y$10$Nv2zaR94gTtlKB0z3EWLE.sKPWrYrulwkaK6nqcdRZ0nZ/HmWJAim', '2026-08-30 18:14:47', 1, 1, '2026-08-30 18:14:17'),
(24, 1, '$2y$10$swXCB5.T.RHZjGiH6HVtfOBFL4tdM3Vxe/mIyaXMUeIfuvzeMw5Ze', '2026-08-31 13:10:22', 1, 1, '2026-08-31 13:09:52'),
(25, 1, '$2y$10$Jtgdd7GIazOtruKIcCErs.sAupTPIETtTDv8iSYG5QoPsmjxP8e5O', '2026-09-01 18:07:12', 1, 1, '2026-09-01 18:06:42'),
(26, 1, '$2y$10$DiiTIsiIN2iQJQ7gkCjc4.b8RV8UC6bdQqynQpAbjeJzV8odwXC.y', '2026-09-01 23:08:02', 1, 1, '2026-09-01 23:07:32'),
(27, 1, '$2y$10$4fJ1exCnCi1EHS8SzgyBPuWhc4JTj6vtV1CKwDPtkYjRQ0LFvdjiu', '2026-09-02 04:53:26', 1, 1, '2026-09-02 04:52:56'),
(28, 2, '$2y$10$adpgWlzBuRd3DWA6qdNbxO4I.nHVomSTRi4070nx0W/gRQSnUQeey', '2026-09-02 04:57:27', 1, 1, '2026-09-02 04:56:57'),
(29, 2, '$2y$10$uwRDP.IiTmNuu5bPA7BVtulugdQ3IAmVSAihcBpqPZEjaYjFRZQiK', '2026-09-02 04:59:34', 1, 1, '2026-09-02 04:59:04'),
(30, 1, '$2y$10$zfKUjAm4A5XAj2J24jePv.7iV2pLiH591uRjL0JSlsz6On07EWzeq', '2026-09-02 05:09:27', 1, 1, '2026-09-02 05:08:57'),
(31, 5, '$2y$10$g3wHwBrFC36joE.MK.Cche8EJ7zGY.w7dXTvL5TvEejFFtIeFUaHS', '2026-09-02 05:10:54', 1, 1, '2026-09-02 05:10:24'),
(32, 1, '$2y$10$xQw1jKgGEoUx9JLuwmTNe.uZcgodmqZDJPKCTI/Qfa8mU1vXwF64W', '2026-09-02 05:12:24', 1, 1, '2026-09-02 05:11:54'),
(33, 1, '$2y$10$wgF1TV28LVC2gtoN8VKvOuNg/Jc27iH34QGiuOIBvk23tsMAzE.uG', '2026-09-02 18:13:57', 1, 1, '2026-09-02 18:13:27'),
(34, 2, '$2y$10$0bp3H1s2YFdtQ1CQnCd8dOj.8y.7WoNY/Y7otSWNQ8RhcrmU8.QAO', '2026-09-02 18:14:56', 1, 1, '2026-09-02 18:14:26'),
(35, 2, '$2y$10$eAi42KFoEEMu.Z3cPX4uvup6HsRz7cR49vTZR4pENZf30wSG5bWlG', '2026-09-02 18:15:35', 1, 1, '2026-09-02 18:15:05'),
(36, 1, '$2y$10$SB1pjjWmp3g4tfdeKE3E9OUmO0wt4BwlddlkPptmsZVAXv0fLqgnu', '2026-09-02 18:18:08', 1, 1, '2026-09-02 18:17:38'),
(37, 2, '$2y$10$KP2AzujeEA8p6FlsAmXiYeIMUXbmwpR1zhBaJTayB66QU80o.wuXy', '2026-09-02 18:19:02', 1, 1, '2026-09-02 18:18:32'),
(38, 1, '$2y$10$1CiVMiJrluPTVNhjMSHNVuQuvhbIC9UBxHmLx9tR3yIbHbIl3VgI6', '2026-09-02 18:39:28', 1, 1, '2026-09-02 18:38:58'),
(39, 2, '$2y$10$vM74jN8AsjMBvFuiIZfUuuUg81NoHpQQzJaoHW6YFK3iH1FBq3Cua', '2026-09-02 18:39:56', 1, 1, '2026-09-02 18:39:26'),
(40, 1, '$2y$10$SLsa.hb2ICpgcXURJUmuwO.izOFjfGrsnrXuItEoICHI86u77nRYi', '2026-09-03 20:38:43', 1, 1, '2026-09-03 20:38:13'),
(41, 2, '$2y$10$JUHolXvmfzprYeoDE9LNaewO0BTjZDd96bXvWHdnO3OKihS6YZRhS', '2026-09-03 20:39:19', 1, 1, '2026-09-03 20:38:49'),
(42, 2, '$2y$10$BGCWZ03Rs1dZDV1pawNCVe4GU5k42.yJeYWfMhollAZRTDsVCu1aC', '2026-09-04 14:09:02', 1, 1, '2026-09-04 14:08:32'),
(43, 2, '$2y$10$qlEoUfTIGGi20vxKdavgE.ks13oCr3xQ5olY2AvUq/PBvRlV6IJKm', '2026-09-04 14:55:03', 1, 1, '2026-09-04 14:54:33'),
(44, 2, '$2y$10$KUzrec1eaGUPJr0ZPUAALuIh0hNYnNJvU1nw0pkxoQKPnfgH47nFm', '2026-09-04 16:40:30', 1, 1, '2026-09-04 16:40:00'),
(45, 1, '$2y$10$BnRU/ZMGz.gfxbptsgTMeu4OtHm8AjXr7kueA2J6p63h2F6KSK.mG', '2026-09-04 17:09:11', 1, 1, '2026-09-04 17:08:41'),
(46, 1, '$2y$10$JWo4p/AtB5ug0OedthrSaeUVweN8Jb8bAr5jBK.SchjLP/v7fOqXS', '2026-09-07 16:36:33', 1, 0, '2026-09-07 16:36:03'),
(47, 1, '$2y$10$J.iSfz/55PEIu4BU/ukm4uMFXUfBqiyyoCaqDoDaN9K8EJIaSPO3u', '2026-09-07 16:37:57', 1, 1, '2026-09-07 16:37:27'),
(48, 1, '$2y$10$SFfjghwWBmbD4SNvLJxbROttiklA/qkPXGRIuGFBCjam9G79Myh16', '2026-09-07 16:39:51', 1, 1, '2026-09-07 16:39:21'),
(49, 1, '$2y$10$/Yygiw5gKAfD5p6bjfsR/.G/xccnTMGt4MRzDrdkH.8ILcY.qemA2', '2026-09-07 16:45:57', 1, 1, '2026-09-07 16:45:27'),
(50, 1, '$2y$10$QUNENReMS8EC.PGHleoFiuZ5xtYL./gXDsRgT0A84fXEdaVZT8C0W', '2026-09-07 16:46:50', 1, 1, '2026-09-07 16:46:20'),
(51, 1, '$2y$10$gxyv8SeuXsywJE34nx6EiuDRWxMjM1AnPe1NnzVlgAX53GvTOnfaC', '2026-09-07 16:48:52', 1, 1, '2026-09-07 16:48:22'),
(52, 1, '$2y$10$LzSI9oAfSNxk1QA8toF3PulF4REI9jrnUaEtQKO7o0znWYhv86XJm', '2026-09-07 16:50:18', 1, 1, '2026-09-07 16:49:48'),
(53, 1, '$2y$10$y2X66.N6qOtzGUg6549sQuvJK4380AwL3SQecA6j8Q1Goey7vnzb6', '2026-09-07 16:57:03', 1, 1, '2026-09-07 16:56:33'),
(54, 2, '$2y$10$RIG5V4.gPSMP64kmWAfYj.6/o1G7RJUuHCX7Upqo.kKvLuhkFJrTK', '2026-09-07 17:16:57', 1, 1, '2026-09-07 17:16:27'),
(55, 1, '$2y$10$JrDGMZ.P4sFWkV0o4.HJZ.YXixLtmOgqh1fqXMzAR.ptxPokWidiW', '2026-09-07 19:29:23', 1, 1, '2026-09-07 19:28:53'),
(56, 1, '$2y$10$aIwgct1JJbyhOd/McwrJKuEFZp2fblHnJq4XQ9tWOCiREw7K8Dv0C', '2026-09-07 22:22:19', 1, 1, '2026-09-07 22:21:49'),
(57, 1, '$2y$10$TABQwhPOBJXWSA/rPhZTAuTQePCvMHXFPND/SxOKYDuYJwth353Ea', '2026-09-07 23:00:27', 1, 1, '2026-09-07 22:59:57'),
(58, 2, '$2y$10$Nw6PYA7ZrXcbNQK.H1oqf.VsDoyAjOrqdyjCEkGbgq0rfQjDdpKZq', '2026-09-08 02:08:19', 1, 1, '2026-09-08 02:07:49'),
(59, 2, '$2y$10$2kvGV3ehr34sgYn80DBeIe3nWdh2pSJseKwWi0exaXV6xo9CVqg0W', '2026-09-08 02:19:53', 1, 0, '2026-09-08 02:19:23'),
(60, 2, '$2y$10$ETxybnG7T7ysitLYKplzSuTBYmFBIfC34DIHlkvJhfRFaorR9Hz3G', '2026-09-08 02:23:55', 1, 0, '2026-09-08 02:23:25'),
(61, 2, '$2y$10$rD43n95AhiFqQ0VCO/utdejuxyXvI5PXyeUeOQsW/5huUlqHYqTjm', '2026-09-08 02:24:39', 1, 1, '2026-09-08 02:24:09'),
(62, 2, '$2y$10$bGpXNVNg/tZ1jTlh5VE7ke9Jxf2Kx68UqdmFil8CP3/7UqFp6k1V6', '2026-09-08 02:25:49', 1, 1, '2026-09-08 02:25:19'),
(63, 1, '$2y$10$lordHbUGpIL959m.qwPfleV1/Ho6eWU7Gz2OaXsaSJgtZbsb9uhSe', '2026-09-08 11:56:28', 1, 1, '2026-09-08 11:55:58'),
(64, 1, '$2y$10$ytbM2yLSdWwhyF15twhcbuGp.T.sND0OBlDRFFSddOV3RYAYx7EHe', '2026-09-08 12:01:33', 1, 1, '2026-09-08 12:01:03'),
(65, 2, '$2y$10$KQQtMKdvCNHTfshIInHNReLqg5YMr/BlUzETeF4FRXRGT/B9mPx5G', '2026-09-08 22:43:28', 1, 1, '2026-09-08 22:42:58'),
(66, 1, '$2y$10$8pgSPByPuS49rihS6HbvoOFCTAIwPrVeG41Q0Yl.TZ9FP.oRHyyBC', '2026-09-09 00:08:59', 1, 1, '2026-09-09 00:08:29'),
(67, 2, '$2y$10$tN69zyH44d/qnPnTP1lXveOXyK24vj0YqEFL496WFNWeBuxubUMUK', '2026-09-09 00:10:07', 1, 0, '2026-09-09 00:09:37'),
(68, 2, '$2y$10$zlIHjGWpvSHN.12zJtyGA.cxFkSK1eO8axglCWt3z.l/gy.XIbcRu', '2026-09-09 00:10:57', 1, 1, '2026-09-09 00:10:27'),
(69, 1, '$2y$10$Ips8UTmMZ40UgsduR9VLyOJLVtzGY5C0EBGkA74Y0zXNgfvvs7okO', '2026-09-09 11:37:25', 1, 1, '2026-09-09 11:36:55'),
(70, 1, '$2y$10$fPA1eM.vegrVELpXX88RLuW1xtl582MLOwOU0XeRuaDBVwaiOKW/y', '2026-09-11 21:16:45', 1, 0, '2026-09-11 21:16:15'),
(71, 1, '$2y$10$Lj87kgUsdt8As9.RHPkcI.ggV/OvunI4h6v6jCLXAkExJBP5PN1LS', '2026-09-11 21:17:40', 1, 1, '2026-09-11 21:17:10'),
(72, 1, '$2y$10$w5SYSA8U9STWpjTgVpgY5u8LjIxez6EpOfkDOljl6gOr3YtzzM6ce', '2026-09-11 21:19:29', 1, 1, '2026-09-11 21:18:59'),
(73, 1, '$2y$10$VxVUxoLmW3eF9j7bKPA9ROVK.nRsKyEIC3txRHgK5EYQ0fn3mO52m', '2026-09-11 21:25:28', 1, 1, '2026-09-11 21:24:58'),
(74, 1, '$2y$10$8dHgCn/hnwqn8SddvzwmbepG3ZRXyUbG94jFhgshRGtwBP2Q3SYBa', '2026-09-11 21:25:59', 1, 1, '2026-09-11 21:25:29'),
(75, 2, '$2y$10$Vve64N3uyoujCY2Ba2lta.A1vqHB5bvsQRX0X47rS8vMOI3tjKMLO', '2026-09-11 21:31:25', 1, 1, '2026-09-11 21:30:55'),
(76, 2, '$2y$10$ckgZNT3OUbCXNwQcr1sMtuPWXMO.1mpSAnRJku0lT7vglUkxoZIm.', '2026-09-11 21:42:55', 1, 1, '2026-09-11 21:42:25'),
(77, 2, '$2y$10$E98AI0Gg2qVpu0Z0iLesi.5qvAk64Y6v0lZ2xGe8j/1fA2NB0hKw2', '2026-09-15 14:00:12', 1, 1, '2026-09-15 13:59:42'),
(78, 1, '$2y$10$YvEgNdDUJHxz/T3uSpx34O/zQdBGwMNaFwGwxYzzkMUaegLsXbWt6', '2026-09-16 10:03:46', 1, 1, '2026-09-16 10:03:16'),
(79, 1, '$2y$10$gxgFZodzOQS4Qr0AAqNt5OD0xfdoa/HHwuXmn6sBf/glNXSgugYV6', '2026-09-17 12:33:58', 1, 1, '2026-09-17 12:33:28'),
(80, 2, '$2y$10$Oba1KmnYGG2o/GQorYDYte6ZpDburHOjucVxygLT2Fy285gzZdq1e', '2026-09-17 12:36:37', 1, 1, '2026-09-17 12:36:07'),
(81, 1, '$2y$10$pQo1r8i7HX.pmLABUfTgeeKfr1wrSVY4dcVojeuBWJ0T06YgmD9F6', '2026-09-17 12:41:04', 1, 1, '2026-09-17 12:40:34'),
(82, 1, '$2y$10$W3DSLHpHuQrQLtP9.lsTouSmEIKlOONzxRKnBFzN18oKjFh4pOrcu', '2026-09-17 12:51:56', 1, 1, '2026-09-17 12:51:26'),
(83, 1, '$2y$10$6DtSFz6tPITGl5JaGzd2dOOkGQUuc0kAwR3PGHn7OizpsUuH9iwsi', '2026-09-17 12:54:20', 1, 1, '2026-09-17 12:53:50'),
(85, 6, '$2y$10$t8lzOiZeeKUqikgtchQAh.yMSMmiSOJMpTuhIerbKuqPiDs655rm.', '2026-09-17 13:00:45', 1, 1, '2026-09-17 13:00:15'),
(86, 6, '$2y$10$ot2xoh5j35VbNYksa75NG.62FXLdX4Sg/aVWPNEEBiEZBWN30JUzu', '2026-09-17 13:12:16', 1, 1, '2026-09-17 13:11:46'),
(87, 6, '$2y$10$scWIjdUQsUf4aIdaoelN9.tsybwbIuvxebwaWvrOeem.CO8MWB9/e', '2026-09-17 13:12:44', 1, 0, '2026-09-17 13:12:14'),
(88, 1, '$2y$10$.wExqkT2QCcmihyoeurmeuAXVCht4NzqeIshFlYox062YuOBshuSq', '2026-09-17 13:16:18', 1, 1, '2026-09-17 13:15:48'),
(89, 2, '$2y$10$6YX9eDjHPsWySRT9Mk9F6efpPuNTHZHwHwsp0by37NE323rOOTSMW', '2026-09-17 13:31:49', 1, 1, '2026-09-17 13:31:19'),
(90, 1, '$2y$10$FzbImz8HFNRMt3r1oIlh/OKlDmjzVU6pXZIa.iPQmv/P2ExAKzL.i', '2026-09-17 15:41:26', 1, 1, '2026-09-17 15:40:56'),
(91, 1, '$2y$10$nwd./.pkUYJULBbShByY6.3pq5mgFBU82AIFkWQxbzsWxHCkQ5Nwm', '2026-09-17 21:08:40', 1, 0, '2026-09-17 21:08:10'),
(92, 1, '$2y$10$uQqXK5N6/vILY24jwlAO6OmI/cuVjfH3m7Me4e4MxJ5qg65deAQfq', '2026-09-17 21:15:50', 1, 1, '2026-09-17 21:15:20'),
(93, 1, '$2y$10$3PYTsaf5Fpn57Hav80XY2eg2OoMqa15svoE10c414IfXYVXt./J2a', '2026-09-17 22:07:52', 1, 1, '2026-09-17 22:07:22'),
(94, 1, '$2y$10$fZKo1px3JgUR5HSDkqNX3ultgQx56NUj5lemC26e48gNBLh.3tqem', '2026-09-17 22:10:54', 1, 1, '2026-09-17 22:10:24'),
(95, 2, '$2y$10$yZXNHivKouCRMxpq3X8RgOHJwH29EbHa47C84pfZydvc6Umawswte', '2026-09-17 22:12:22', 1, 1, '2026-09-17 22:11:52'),
(96, 2, '$2y$10$CiD/C2ekKf42nq4.M/xV4eq8uQMZFgEl9npnTPY4RiH0eAOBxBRFC', '2026-09-17 22:12:52', 1, 0, '2026-09-17 22:12:22'),
(97, 2, '$2y$10$xBswtrKPlYbb8ND0QwljV.s0yA.YYad9Dc9pkoLjsyIdlZHf9TBdG', '2026-09-17 22:14:56', 1, 1, '2026-09-17 22:14:26'),
(98, 2, '$2y$10$WqL1DQikLvm2IoTJgNqLgOzeGlguYNaR7DFVi1F490jzWhOwdnsMe', '2026-09-17 22:16:07', 1, 0, '2026-09-17 22:15:37'),
(99, 2, '$2y$10$NCSzi4xndRpNIPLZyLlVJeyarP7QJJCoDw9Fid7VD2QphrodQxHDa', '2026-09-17 22:17:38', 1, 1, '2026-09-17 22:17:08'),
(100, 2, '$2y$10$en77RS2JUzi1jBzNmzfl.e0.jAs2iWxqrrdzCN9PtJ2wl91w.y5.2', '2026-09-17 22:18:10', 1, 1, '2026-09-17 22:17:40'),
(101, 1, '$2y$10$zU3dxxAncoljRiTjMD8nJOnh.wA/AfQI1kZStYvC.iSu5xjk8/4ZK', '2026-09-17 22:37:21', 1, 0, '2026-09-17 22:36:51'),
(102, 1, '$2y$10$S8u7IJrwfwSTuYCgszcT9O9S7d11uWA/Fa6eHcgFbJYfGH70vo.A2', '2026-09-17 22:42:06', 1, 0, '2026-09-17 22:41:36'),
(103, 1, '$2y$10$MyCvO8JbS52R.pWcFV8eiuUL6g1CXWUFC6YSQcbdGdE94jDTOPaD6', '2026-09-17 22:48:46', 1, 1, '2026-09-17 22:48:16'),
(104, 1, '$2y$10$ue41PFbZHhFmIIbrEihtYuXJXlPqZumbFDNLDqDF5OfcPzVogNXMO', '2026-09-17 22:58:48', 1, 0, '2026-09-17 22:58:18'),
(105, 1, '$2y$10$h8zfo8CL8N3KHVG9Fspbtekuy2q6OF5YtMFDTuxQh7WPaocJvmkyu', '2026-09-17 22:59:27', 1, 1, '2026-09-17 22:58:57'),
(106, 1, '$2y$10$89.Sscu7n/sz1trVXkFzpegF80s5X.2SQxnvrXytqiKlDvfnXh3fm', '2026-09-17 23:03:44', 1, 0, '2026-09-17 23:03:14'),
(107, 1, '$2y$10$Q.fPRyaUDa0IeHHPPf1P9e315NLsdv1HEC.yMs/1P1b2n0jozT9au', '2026-09-17 23:04:52', 1, 1, '2026-09-17 23:04:22'),
(108, 1, '$2y$10$on2n4fIO76/h6Px.0uVUPuRmhJsUPjUpGQB8dDlg7IVi32UQ7BU/y', '2026-09-17 23:17:00', 1, 1, '2026-09-17 23:16:30'),
(109, 1, '$2y$10$ORL9Rs8SuBmsgWpZksGV.u/JPR7a8vYYbbv16Q7L.D3eoF5ulSnfy', '2026-09-17 23:17:17', 1, 1, '2026-09-17 23:16:47'),
(110, 1, '$2y$10$t0iNeXPtWI5nlmNDCiGWYu54cxP6MC/gzw2b.3Wci.33ORXexJR2u', '2026-09-17 23:17:51', 1, 1, '2026-09-17 23:17:21'),
(111, 1, '$2y$10$.rmprFm3GZqO1OAQRqnCOePZbBDKNOj4wNvqOX8aAtmVQGBXd98du', '2026-09-17 23:21:13', 1, 1, '2026-09-17 23:20:43'),
(112, 1, '$2y$10$2mFhMwQ6ntcRMyTKsFgaaOaJyYGlmLObKZLbjmFgfhWg6ZJdxuLUG', '2026-09-17 23:25:13', 1, 0, '2026-09-17 23:24:43'),
(113, 1, '$2y$10$r/B4kZw1rBFvJmpxiTu.F.BwSTWDOXLZi3pZ5DCMSDRNU/6LBeHxm', '2026-09-17 23:25:52', 1, 1, '2026-09-17 23:25:22'),
(114, 1, '$2y$10$ZQVsQHneRKjlX57fWHAzFerguipNybiroQ8ORxJrcBjru9bqVOs5C', '2026-09-17 23:28:43', 1, 0, '2026-09-17 23:28:13'),
(115, 1, '$2y$10$r48iSL1239fquPAEw1qTF.dKv.g6RkwuIXsAGaFRJDMneWJ/CcO7q', '2026-09-17 23:29:47', 1, 0, '2026-09-17 23:29:17'),
(116, 1, '$2y$10$rd5o9en38eYT1z9BOEKlbudBFxn/2pgOTAo0b4flVRzUJb8kc5w1O', '2026-09-17 23:31:23', 1, 1, '2026-09-17 23:30:53'),
(117, 1, '$2y$10$HOcRxD9HE.vVUAEV5K3KoOMQVNxKXhXDYGvN2rnEFbOEIhOPnoOZK', '2026-09-17 23:37:33', 1, 0, '2026-09-17 23:37:03'),
(118, 1, '$2y$10$W.g7831XS9316MGi4DagROJcDyUIeP/G/K4kRXDJqPcsUPmj9rGNy', '2026-09-17 23:42:26', 1, 0, '2026-09-17 23:41:56'),
(119, 1, '$2y$10$bP1w9sqOKpl8HsFXcnJr7.KE8wWTDHZ2Zlz7D8K0lGKh9uDjsurVm', '2026-09-18 01:09:31', 1, 0, '2026-09-18 01:09:01'),
(120, 1, '$2y$10$NDGx40dpL4/yxG.HjX0fO.Y5yohlgkTDgWUZn8tflw9NmWEcfHz3i', '2026-09-18 01:10:05', 1, 1, '2026-09-18 01:09:35'),
(121, 1, '$2y$10$vm3N7jgwoQj2HuEcG.TmbeB40PeU8uFkNhCyxIj6M/BUYxSMCuzz6', '2026-09-18 01:16:35', 1, 0, '2026-09-18 01:16:05'),
(122, 1, '$2y$10$Ua67UMFrqxeO2IswRLBsYOt08C7sr1QFZCWFfAAHLbTO6SchNWUDS', '2026-09-18 01:25:08', 1, 0, '2026-09-18 01:24:38'),
(123, 1, '$2y$10$CJogZYzKq/1TywjNKHSrSO2aefpEpZyFIKfRhR4WK6.HNzRgPJXWC', '2026-09-18 01:25:27', 1, 0, '2026-09-18 01:24:57'),
(124, 1, '$2y$10$na1B8/IWqYpnpfcIUvW6ne68Jcja6Vk6K1p84sTe7IAqkOJ7ijs.i', '2026-09-18 01:27:48', 1, 0, '2026-09-18 01:27:18'),
(125, 1, '$2y$10$jjgNbnc0VHO7dG5a2phOD.mFL4ktDVBGep4VjHM00LpAILFRzqX8.', '2026-09-18 01:37:03', 1, 0, '2026-09-18 01:36:33'),
(126, 1, '$2y$10$5CWBbgp3bikFrVuAW0VzgeGXegdyHU6PoDGsy56jfFmXyBWtvPB6S', '2026-09-18 01:39:23', 1, 0, '2026-09-18 01:38:53'),
(127, 1, '$2y$10$XOAJFpZAKh4Ms8iMfIxPT.blmH3NTC4DMlflTIQ2awuGN.KpTHavm', '2026-09-18 01:47:54', 1, 1, '2026-09-18 01:47:24'),
(128, 3, '$2y$10$JbY/KbeGePt9ksBLP7.aTeh.tv408VfDySTpBw6SUvvMp1HpsE70y', '2026-09-18 01:52:30', 1, 1, '2026-09-18 01:52:00'),
(129, 1, '$2y$10$SKMmyohofjjwq6H9is1luOQ11yVXYLXUkiEvY7ne4AdGjeDNuzPXa', '2026-09-18 02:02:35', 1, 1, '2026-09-18 02:02:05'),
(130, 1, '$2y$10$lTXHfc8YemwXhsibJd8aAe2U9dsPv2XXyp/QJm3M14ALydJ0BpQt.', '2026-09-18 02:05:32', 1, 1, '2026-09-18 02:04:32'),
(131, 1, '$2y$10$MgWK0SuVqx/Xh82msT95k.dBHrqsc29d2VDA7/m./OkK5GmVfvg/O', '2026-09-18 02:11:47', 1, 1, '2026-09-18 02:10:47'),
(132, 11, '$2y$10$GYhdUymOnJMc1Raf0JZnF.hrdV2rZUNxqSAQOsZv1zQeNuXVj.OaC', '2026-09-18 02:16:35', 1, 1, '2026-09-18 02:15:35'),
(133, 3, '$2y$10$v6PBHT80dfML8iKER57Ym.E007V10IY70iYx3fL08BsgZhzjHBXVy', '2026-09-18 02:32:46', 1, 1, '2026-09-18 02:31:46'),
(134, 11, '$2y$10$HcEEA7YIRAQN1736LLZ9Ie5.bY2UAXH/Xkzekj253vd8d3uT5bQ.6', '2026-09-18 02:54:11', 1, 0, '2026-09-18 02:53:11'),
(135, 3, '$2y$10$7ybQKjkibzETXmT/bLYWeOS40rHN5snHZ/3sqld9AvCz8PoTy8r62', '2026-09-18 02:56:31', 1, 1, '2026-09-18 02:55:31'),
(136, 1, '$2y$10$duVSwz01iw0nBVsGmtPCzO8bsh71re0MxpehJPWf.MoIhLUeIX6Ca', '2026-09-18 03:01:18', 1, 1, '2026-09-18 03:00:18'),
(137, 1, '$2y$10$9GXty0UYA2rgLKiCKqrcIu6mJpLQ.mG6NMA4ikqjL2DpNJ4cPHDHq', '2026-09-18 03:13:09', 1, 1, '2026-09-18 03:12:09'),
(138, 1, '$2y$10$SEsxxcKW.9sO4g3al9e9w.ar8Ap7xSIWWwZfMbpEKm/DkBvBIbMm2', '2026-09-18 09:43:31', 1, 0, '2026-09-18 09:42:31'),
(139, 1, '$2y$10$vvuDMHBNOinNw8/FGHWOyOy4HZmyqC7RF3JWihYgJvE2W4DokYxUK', '2026-09-18 09:44:36', 1, 0, '2026-09-18 09:43:36'),
(140, 1, '$2y$10$.Hk27GNsGn6W6PTR4MTX/uWXk3rCChkAoT.d/8UcXybJGfBOc143W', '2026-09-18 09:44:56', 1, 0, '2026-09-18 09:43:56'),
(141, 1, '$2y$10$0ht.i1MR.GXeTZmNyot3Sux3Ul2GqcHa9f2EllRfGv1zy20Hz2KAO', '2026-09-18 09:45:42', 1, 0, '2026-09-18 09:44:42'),
(142, 1, '$2y$10$sQO1mo5Rs.oOa1Aih/MYNeS942Doq/yhWt0pXQDnp3whW/cBirV9e', '2026-09-18 09:47:10', 1, 0, '2026-09-18 09:46:10'),
(143, 1, '$2y$10$wSmM3pRo5mJhuakJ5xF4De2AfrSD4tRbMniahlZiFOg2dj0JY4kua', '2026-09-18 09:48:36', 1, 1, '2026-09-18 09:47:36'),
(144, 1, '$2y$10$H1oux./om/AWPkeBN1z/yeWKKL4xUhIp8gsp4LCTQbFGO6bCCYMmS', '2026-09-18 09:54:17', 1, 0, '2026-09-18 09:53:17'),
(145, 1, '$2y$10$fwSVzNY8FJ67Ar8rbWCLyugpRktNBu1UPD03oCURtwjt.kMWuouD.', '2026-09-18 09:56:00', 1, 0, '2026-09-18 09:55:00'),
(146, 1, '$2y$10$.pnQADzolPBCeTMxJN1S9uMbpJyLQ0JrVHQwoi5dBZVQicK2L9m8K', '2026-09-18 09:57:31', 1, 0, '2026-09-18 09:57:21'),
(147, 1, '$2y$10$2.XFy2rA66hzsgjQF469kuUbY0/qeYVbX80/abg1k/tzytPSZt2E6', '2026-09-18 09:57:45', 1, 1, '2026-09-18 09:57:35'),
(148, 1, '$2y$10$xH0XVApThOH/mNt0UrKtXO.k5i77LyCmevnbHWhGeoq2yw1L8Kgqu', '2026-09-18 10:01:48', 1, 0, '2026-09-18 10:01:38'),
(149, 1, '$2y$10$YLlVUHyFYAqL0H40E8j8e.JauOSdiZwR9RHbvpQo80R5PQOt1WO5y', '2026-09-18 10:01:57', 1, 0, '2026-09-18 10:01:52'),
(150, 1, '$2y$10$Ox4qY0iv2wcghh9ed5sGrOm2JdTypRouTvh3/Hss7b863flCu0IGG', '2026-09-18 10:02:09', 1, 0, '2026-09-18 10:02:04'),
(151, 1, '$2y$10$k4jK0lK4t2gnDvW.uBTbWOL6mZCK/6wzb6zLAE6bo.3G7f/HS9tza', '2026-09-18 10:02:35', 1, 1, '2026-09-18 10:02:25'),
(152, 3, '$2y$10$x/4jIvi8ggPgs32GjdNdKe4q6CesZnJHUw3pj0EWmrhnmK39NDq1S', '2026-09-18 10:07:01', 1, 0, '2026-09-18 10:06:51'),
(153, 3, '$2y$10$MEHjt7FB.ZVIhtsf.50rA./5oxWjZBrGART0jNAE.DeRQphpK/bb.', '2026-09-18 10:07:15', 1, 1, '2026-09-18 10:07:05'),
(154, 11, '$2y$10$kcpgjwoamdj9NNe4Gy/8Pu4u7aVIwB9VbKNyD1.a2QkHcIfsmun/q', '2026-09-18 10:08:17', 1, 0, '2026-09-18 10:08:07'),
(155, 11, '$2y$10$miDwIbNogX79vXIFzh1ix.nM0uk74jdbfU.hCdmNC.vbfGZdvD1vm', '2026-09-18 10:08:32', 1, 0, '2026-09-18 10:08:22'),
(156, 11, '$2y$10$TExZD9frcZXJsK3axjnPCe62glkQpynrixq8znpJgD1B6f56AfJJi', '2026-09-18 10:09:01', 1, 1, '2026-09-18 10:08:51'),
(157, 3, '$2y$10$M0dstvs/abM8N0Rst7E7MuE1JHJeZXcnqry3P7v.ylCu5PB4BP7mi', '2026-09-18 11:11:04', 1, 0, '2026-09-18 11:10:54'),
(158, 3, '$2y$10$xxxo59D1CKMv1HR0IBR8Zuy/rFvKIll4RvQdFg3u0XJxZ2TkPJK72', '2026-09-18 11:11:20', 1, 1, '2026-09-18 11:11:10'),
(159, 3, '$2y$10$WNzCNC4ii1XAf6bFKugMGO9e/zlNj.9Vq3l8RAjuSe8QBNA2qpHP.', '2026-09-18 11:20:48', 1, 0, '2026-09-18 11:20:38'),
(160, 3, '$2y$10$gT7iieK/anXCn2RNDskWSe1dHwI/merS5iFpV1uixztllbpeLD5rS', '2026-09-18 11:21:03', 1, 1, '2026-09-18 11:20:53'),
(161, 1, '$2y$10$JL40z2b4.a/F1VhuyTXtN.kp8Zj.XMqfeZHZ51Ce0.Hi7NiZdTYcm', '2026-09-18 11:38:26', 1, 0, '2026-09-18 11:38:16'),
(162, 1, '$2y$10$A8lL9hDSAx3oZUrqnG7WW.P/YJw6Hx1uJLyMQlc2XzNSsoXriPozG', '2026-09-18 11:38:44', 1, 1, '2026-09-18 11:38:34'),
(163, 6, '$2y$10$uyavhhR.obTG1tqE2AaOT.6C/LxxrMp0bgVMcbHt/FO0B4.CPua7y', '2026-09-18 11:39:35', 1, 0, '2026-09-18 11:39:25'),
(164, 6, '$2y$10$T/VNocYMcu7JTgkTUK1FLuHREWVtaBdGwSW91If24fCU4jxoyE56.', '2026-09-18 11:39:50', 1, 1, '2026-09-18 11:39:40'),
(165, 1, '$2y$10$5K9b8l0NXHksCx1sU1D6ke.GumfPQ6TwMITT0xr8VoZW/0IWJ5QL.', '2026-09-18 18:19:34', 1, 0, '2026-09-18 18:19:24'),
(166, 1, '$2y$10$iKinN0rubFnVlA5J0kx2jOtds1jX.VvM7Y72PPcdiF/NEwEnpNPuG', '2026-09-18 18:22:32', 1, 1, '2026-09-18 18:21:32'),
(167, 1, '$2y$10$raD.45OlNm.4jx9ADoBxuOno9kcLlXB.sgz9etwVyKa8nfNa/0myi', '2026-09-18 18:48:33', 1, 1, '2026-09-18 18:47:33'),
(168, 1, '$2y$10$hZeB2nAPpinV4bmG3wh03OoCjmB16LtYwsDT36x9zyCBqioNj7YsO', '2026-09-18 19:23:59', 1, 1, '2026-09-18 19:22:59'),
(169, 1, '$2y$10$h28ov/W1TdXSssjXs4/Aa.mFTXuAbNXWB5Sv8oPxxqD8Owi9Oydwq', '2026-09-18 19:43:07', 1, 1, '2026-09-18 19:42:07'),
(170, 3, '$2y$10$vxoUweSP83LjciQtLMLcq.bIGu19fP2xH145hfk/jRWGtUoymYK7C', '2026-09-18 19:44:30', 1, 0, '2026-09-18 19:43:30'),
(171, 3, '$2y$10$rF9FW0RqllHFdhAfOFQ2AunxQ9roZsI9SWnQSiydgS/DsrnW7jaP2', '2026-09-18 19:52:39', 0, 0, '2026-09-18 19:47:39'),
(172, 6, '$2y$10$FMzG6W3Oa2hj9K/Mf.tU9evtkE/67NK/YSdV8qTcThl9FLAgRZdw2', '2026-09-18 19:59:58', 1, 1, '2026-09-18 19:54:58'),
(173, 1, '$2y$10$Ll3EnGV0kAcfp7Ut.rzMsOYIPwvcKzaC.N3I.NpZJOwvsHVpxnCkW', '2026-09-18 22:01:37', 1, 1, '2026-09-18 21:56:37'),
(174, 6, '$2y$10$eb3FRdlZOtr9kwstELOu0eH/j1SNfDzITBDZ.b18aoFpDjgRtmqie', '2026-09-18 22:10:34', 1, 2, '2026-09-18 22:05:34'),
(175, 1, '$2y$10$CbEV4NTt.2SDr1BgEstBQuEu9PsN3pcL93EHkhlgYHHIjBHiDtHmm', '2026-09-18 22:14:02', 1, 0, '2026-09-18 22:09:02'),
(176, 1, '$2y$10$Pigs1u8GBMJR6SNga91Mdu2JSwUN2i9dQVHGxPwVcgj2.5i7lbobS', '2026-09-19 06:24:43', 1, 1, '2026-09-19 06:19:43'),
(177, 8, '$2y$10$UkuyfSvqclo9.1h5RxSlVu8JG.EYlVjmHeAKOuKtuFXXJBoqn.S8W', '2026-09-19 06:28:17', 1, 1, '2026-09-19 06:23:17'),
(178, 6, '$2y$10$0hvxrND7C0jtzwXn8HDLa.JRS/io/KhF2RwJNOuWceMqTAVMVfsfC', '2026-09-19 06:33:24', 1, 2, '2026-09-19 06:28:24'),
(179, 6, '$2y$10$WbWtu7nbN5WLfMuJPt9SpONbd4gLwvvX219.QFl4PIWImgi/h8eLW', '2026-09-19 11:38:11', 1, 2, '2026-09-19 11:33:11'),
(180, 1, '$2y$10$TyQs6KXHkMxz3IeR9pWNauYWLj6byWV33NQtEjpEAwWq.RxtnQ9KO', '2026-09-19 11:42:49', 1, 1, '2026-09-19 11:37:49'),
(181, 6, '$2y$10$7e2kozKvya6m7QQOIkxLzua.LfXn9M3zBzdBh0dI1V8K.mm/AzTQG', '2026-09-19 12:05:59', 1, 1, '2026-09-19 12:00:59'),
(182, 6, '$2y$10$ZvbkbkLRsqw2QbDjEiVI3e8nxPiw1LVhEXkgkpJtMCzc45V3PzsDK', '2026-09-19 12:24:45', 1, 1, '2026-09-19 12:19:45'),
(183, 1, '$2y$10$Pgc8eMgDBoIYQHUV9QdPxuD.GTC0qhwEFRW9.FcoI.KQVjnjDqZfG', '2026-09-19 13:02:10', 1, 0, '2026-09-19 12:57:10'),
(184, 6, '$2y$10$PHaPzZ7JyCiWHEbJetICVOtXGefr/eIn.RUzLl47D2vFyp7Nzos4e', '2026-09-19 13:02:55', 1, 2, '2026-09-19 12:57:55'),
(185, 1, '$2y$10$4Ud/tVYaukYiTC7Ljuece.mN02mAskWyKptQXq2UulSrv9KElev5m', '2026-09-19 14:11:36', 1, 1, '2026-09-19 14:06:36'),
(186, 6, '$2y$10$YtjyQ3dyl445s5FJKWnZNu.jCZwbR52zd2rSn.fXCmKXuhRp7ssr.', '2026-09-19 14:16:47', 1, 1, '2026-09-19 14:11:47'),
(187, 6, '$2y$10$xQDZ78KHRlFpPiRHFIjyEeF2PAIfVsFnuSHN5RPzXuGxeNrgG3wxC', '2026-09-19 15:10:42', 1, 1, '2026-09-19 15:05:42'),
(188, 1, '$2y$10$jmnaYuJwixY2Lvv/CU4Zfe4FAVAkB7mBD7I2J0otpUeP68noBZ8v6', '2026-09-19 15:32:08', 1, 1, '2026-09-19 15:27:08'),
(189, 8, '$2y$10$PMc7RnH6xv8Qb8VqMROf4ueP520Ve5N4u8TFjzd4ICLAIZr2N0lci', '2026-09-19 16:19:53', 1, 1, '2026-09-19 16:14:53'),
(190, 8, '$2y$10$2QDu5GWBp06jxCWO7F6Rhe0L/aFUmeZaNkycyByymYLZeqThaUb9C', '2026-09-19 17:21:36', 1, 1, '2026-09-19 17:16:36'),
(191, 8, '$2y$10$8iq/EvjDAqxkQwZx4DWwt.5UOBwbscc2T0Zwvl.SASP6pNJpwnaeC', '2026-09-19 19:10:30', 1, 1, '2026-09-19 19:05:30'),
(192, 1, '$2y$10$5cH16fffbn4bXn8gto/iGe1Ka42zoKhSC.4iueDjCFYYNqpK5jVgq', '2026-09-19 19:54:14', 1, 1, '2026-09-19 19:49:14'),
(193, 6, '$2y$10$cFG9JIZMr2EKtdRgQKjAfO9fSsVenDIqDU0cuyMU6AAFq04sWtvVe', '2026-09-19 20:08:02', 1, 1, '2026-09-19 20:03:02'),
(194, 1, '$2y$10$KgG3dsGnlEPyjfoevUn8UOQ.k8hvQtNdBdnOGvSJz0V4P.uQkRoPG', '2026-09-19 20:50:30', 1, 1, '2026-09-19 20:45:30'),
(195, 1, '$2y$10$rHxulUWUf.Y25eY30qoiT.5qmtzfZAmgEhLzTfTWBHdyIow4SVIki', '2026-09-19 21:41:57', 1, 1, '2026-09-19 21:36:57'),
(196, 6, '$2y$10$w1JlPjVMq1alxm2v.y7zWez6UZCkoc89MiTadWgtDKcyA3l0dcTnm', '2026-09-19 23:24:29', 1, 1, '2026-09-19 23:19:29'),
(197, 1, '$2y$10$ujBfG4gq0VW/fWrjARIoxeob4Wroa7cc.3XdwcVb9aRy6lAsfrfqO', '2026-09-20 02:25:26', 1, 1, '2026-09-20 02:20:26'),
(198, 6, '$2y$10$LXH6a2PtMR5XfDrS5yD8F.fZWkmHx6Gj3vV4U8KaSW8L16qiLK6Uy', '2026-09-20 02:27:14', 1, 1, '2026-09-20 02:22:14'),
(199, 1, '$2y$10$BX3WVdrP8pkEye7HbmAO5eW4iNOSpyeDcX2KPhm4c8GPg8ZCqR24a', '2026-09-20 02:50:14', 1, 2, '2026-09-20 02:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Administrator'),
(2, 'Committee Chairperson'),
(3, 'Committee Member'),
(4, 'Super Admin');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `current_session_token` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role_id`, `status`, `current_session_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrator', 'lerinlance88@gmail.com', '$2y$10$6VAt7t.5DEUyhInUVwnBU.21lt3h3cpxSdVGks5SdrksNg5okG7iG', 1, 'Active', 'e3f063960417e3570c7aa3e476e504abeb46b2b4d245d7deedf874bb02115e98', '2026-07-27 15:49:22', '2026-09-20 02:46:05'),
(2, 'Lancey', 'staff@cmas.local', '$2y$10$Rhiyl6ijErXiqQ2u.lClRu2jN0Cel.h4BKrZPhwbDam.bN2UErdli', 2, 'Active', 'bb28faa08da9e5839c53fb2305a6633e1aa7f32a4eb959acc3e54a0f50fe9594', '2026-07-27 15:49:22', '2026-09-17 22:17:47'),
(3, 'Princess Ann Reyes', 'reyes.kaluret@gmail.com', '$2y$10$RimDY2Gg2vpLwjOx5Eh/2e24strBM9wj5V8PiyV/bxWI.Depe1ACm', 2, 'Active', NULL, '2026-07-27 15:49:22', '2026-09-18 11:37:45'),
(4, 'Maria Santos', 'member2@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active', NULL, '2026-07-27 15:49:22', '2026-07-27 15:49:22'),
(5, 'Pedro Reyes', 'member3@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Active', NULL, '2026-07-28 13:04:03', '2026-07-28 13:04:03'),
(6, 'Lance Lerin', 'lerinlance47@gmail.com', '$2y$10$k2jirrdxhdW3NAXY7u9wzuwAzZF6w6fColeH4mBIH8.XJ7ol8u8yu', 2, 'Active', NULL, '2026-07-28 13:04:03', '2026-09-20 02:44:53'),
(8, 'Xcnal', 'xcnal47@gmail.com', '$2y$10$agMzJhhprbYYl/5ULTnX5.8NLjuj73DTRYSM.YKP8TWXwVkvXxgiO', 3, 'Active', '22d38be37ceb44b52afb92ef268d9b9f0fd26279f8f505981c9064e1e287de12', '2026-07-28 13:04:03', '2026-09-19 19:06:52'),
(9, 'Carlos Mendoza', 'member7@cmas.local', '$2y$10$eDWSrr687sOeMoBVuxHpj.0iAUP3G5Hdjonl1gPKmA2quRe5Reyz2', 3, 'Inactive', NULL, '2026-07-28 13:04:03', '2026-08-08 09:22:53'),
(11, 'Nathaniel Lei Mestiola', 'nathaniel.lei20@gmail.com', '$2y$10$RK7fsUw.MBEAOOn43li4ieDPYXG7Vnw6FWNug/42NW.a7GfiBlzEe', 3, 'Active', 'da5623feb76579242a728e36ff00168564afb9b031954e9abd5bdc3b46217b45', '2026-08-11 20:03:32', '2026-09-18 10:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `user_background`
--

CREATE TABLE `user_background` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `highest_education` varchar(150) DEFAULT NULL,
  `degree_course` varchar(255) DEFAULT NULL,
  `school_university` varchar(255) DEFAULT NULL,
  `major_specialization` varchar(255) DEFAULT NULL,
  `certifications_training` text DEFAULT NULL,
  `current_profession` varchar(255) DEFAULT NULL,
  `years_experience` smallint(5) UNSIGNED DEFAULT NULL,
  `previous_positions` text DEFAULT NULL,
  `previous_organizations` text DEFAULT NULL,
  `government_experience` text DEFAULT NULL,
  `primary_expertise` varchar(255) DEFAULT NULL,
  `secondary_expertise` text DEFAULT NULL,
  `knowledge_areas` text DEFAULT NULL,
  `relevant_skills` text DEFAULT NULL,
  `committee_expertise` text DEFAULT NULL,
  `expertise_keywords` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_background`
--

INSERT INTO `user_background` (`id`, `user_id`, `highest_education`, `degree_course`, `school_university`, `major_specialization`, `certifications_training`, `current_profession`, `years_experience`, `previous_positions`, `previous_organizations`, `government_experience`, `primary_expertise`, `secondary_expertise`, `knowledge_areas`, `relevant_skills`, `committee_expertise`, `expertise_keywords`, `created_at`, `updated_at`) VALUES
(3, 6, 'Master\'s Degree', 'Master of Public Administration', 'Manila Metropolitan University', 'Public Policy and Local Governance', '', '', NULL, '', '', '', '', '', '', '', '', '', '2026-09-19 20:57:05', '2026-09-19 20:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `workload_assignments`
--

CREATE TABLE `workload_assignments` (
  `workload_id` int(11) NOT NULL,
  `committee_member_id` int(11) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `workload_points` int(11) NOT NULL DEFAULT 1,
  `assigned_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Overdue') NOT NULL DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workload_assignments`
--

INSERT INTO `workload_assignments` (`workload_id`, `committee_member_id`, `task_title`, `task_description`, `priority`, `workload_points`, `assigned_date`, `due_date`, `completion_date`, `status`, `created_at`) VALUES
(1, 2, 'Draft sanitation ordinance revision', 'Update the city sanitation code to reflect new DOH guidelines.', 'High', 8, '2026-07-08', '2026-08-02', '2026-08-29', 'Completed', '2026-07-28 13:04:04'),
(3, 2, 'Compile Q2 health program report', 'The Chairperson has a higher workload and lower completion rate compared to the other members, indicating a need for a comprehensive report. The Chairperson also has the longest time since their last assignment, making them the most suitable candidate for this urgent task.', 'Urgent', 85, '2026-06-13', '2026-09-15', '2026-09-01', 'Completed', '2026-07-28 13:04:04'),
(4, 5, 'Review supplemental budget request', 'Evaluate the requested supplemental appropriation for disaster relief.', 'Urgent', 10, '2026-07-18', '2026-07-30', '2026-08-11', 'Completed', '2026-07-28 13:04:04'),
(11, 12, 'Review road widening proposal', 'Evaluate the feasibility study for the main avenue widening project.', 'Urgent', 10, '2026-07-20', '2026-07-29', '2026-08-11', 'Completed', '2026-07-28 13:04:04'),
(19, 7, 'TEST', 'sdfksdfks', 'Medium', 1, '2026-08-22', '2026-08-22', NULL, 'In Progress', '2026-08-22 12:20:24'),
(23, 4, 'Free Check Up for Adults', 'A task to ensure all adults in the community receive a free health check-up, which is crucial for maintaining public health and reducing the burden on the healthcare system.', 'Urgent', 85, '2026-08-31', '2026-09-14', '2026-09-01', 'Completed', '2026-08-31 13:23:41'),
(29, 17, 'Tree planting', 'Committee members are encouraged to participate in tree planting to contribute to the environment. This task is vital for the committee\'s environmental goals.', 'Urgent', 50, '2026-09-18', '2026-10-01', NULL, 'Pending', '2026-09-18 11:37:04'),
(33, 5, 'Department of Education Funding Request Evaluation', 'Evaluate the Department of Education\'s funding request to ensure fiscal compliance, proper budget allocation, and alignment with national education priorities.', 'Medium', 1, '2026-09-20', '2026-10-10', NULL, 'Pending', '2026-09-20 02:38:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`recommendation_id`),
  ADD KEY `fk_air_workload` (`workload_id`),
  ADD KEY `fk_air_recommended_member` (`recommended_member_id`),
  ADD KEY `fk_air_final_member` (`final_member_id`),
  ADD KEY `fk_air_generated_by` (`generated_by`),
  ADD KEY `fk_air_ai_recommended_member` (`ai_recommended_member_id`),
  ADD KEY `idx_air_committee_hash` (`committee_id`,`candidates_hash`);

--
-- Indexes for table `ai_system_settings`
--
ALTER TABLE `ai_system_settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `fk_aisys_user` (`updated_by`);

--
-- Indexes for table `ai_weight_config`
--
ALTER TABLE `ai_weight_config`
  ADD PRIMARY KEY (`factor_key`),
  ADD KEY `fk_aiw_user` (`updated_by`);

--
-- Indexes for table `committees`
--
ALTER TABLE `committees`
  ADD PRIMARY KEY (`committee_id`),
  ADD UNIQUE KEY `uq_committee_name` (`committee_name`),
  ADD KEY `fk_committee_jurisdiction` (`jurisdiction_id`),
  ADD KEY `fk_committee_creator` (`created_by`);

--
-- Indexes for table `committee_members`
--
ALTER TABLE `committee_members`
  ADD PRIMARY KEY (`committee_member_id`),
  ADD UNIQUE KEY `uq_committee_user` (`committee_id`,`user_id`),
  ADD KEY `fk_cm_user` (`user_id`);

--
-- Indexes for table `committee_performance`
--
ALTER TABLE `committee_performance`
  ADD PRIMARY KEY (`performance_id`),
  ADD KEY `fk_perf_committee` (`committee_id`);

--
-- Indexes for table `committee_reports`
--
ALTER TABLE `committee_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_report_committee` (`committee_id`),
  ADD KEY `fk_report_user` (`generated_by`);

--
-- Indexes for table `jurisdictions`
--
ALTER TABLE `jurisdictions`
  ADD PRIMARY KEY (`jurisdiction_id`),
  ADD UNIQUE KEY `uq_jurisdiction_name` (`jurisdiction_name`),
  ADD KEY `fk_jurisdiction_creator` (`created_by`);

--
-- Indexes for table `login_security`
--
ALTER TABLE `login_security`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD UNIQUE KEY `uq_notification_activity_recipient` (`activity_log_id`,`recipient_user_id`),
  ADD KEY `idx_notifications_recipient` (`recipient_user_id`,`read_at`,`created_at`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `idx_otp_user_active` (`user_id`,`is_used`,`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- Indexes for table `user_background`
--
ALTER TABLE `user_background`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_background_user` (`user_id`);

--
-- Indexes for table `workload_assignments`
--
ALTER TABLE `workload_assignments`
  ADD PRIMARY KEY (`workload_id`),
  ADD UNIQUE KEY `uq_workload_member_task_due` (`committee_member_id`,`task_title`,`due_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=704;

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `recommendation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `committees`
--
ALTER TABLE `committees`
  MODIFY `committee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `committee_members`
--
ALTER TABLE `committee_members`
  MODIFY `committee_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `committee_performance`
--
ALTER TABLE `committee_performance`
  MODIFY `performance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `committee_reports`
--
ALTER TABLE `committee_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jurisdictions`
--
ALTER TABLE `jurisdictions`
  MODIFY `jurisdiction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_background`
--
ALTER TABLE `user_background`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `workload_assignments`
--
ALTER TABLE `workload_assignments`
  MODIFY `workload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `fk_air_ai_recommended_member` FOREIGN KEY (`ai_recommended_member_id`) REFERENCES `committee_members` (`committee_member_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_air_committee` FOREIGN KEY (`committee_id`) REFERENCES `committees` (`committee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_air_final_member` FOREIGN KEY (`final_member_id`) REFERENCES `committee_members` (`committee_member_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_air_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_air_recommended_member` FOREIGN KEY (`recommended_member_id`) REFERENCES `committee_members` (`committee_member_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_air_workload` FOREIGN KEY (`workload_id`) REFERENCES `workload_assignments` (`workload_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ai_system_settings`
--
ALTER TABLE `ai_system_settings`
  ADD CONSTRAINT `fk_aisys_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ai_weight_config`
--
ALTER TABLE `ai_weight_config`
  ADD CONSTRAINT `fk_aiw_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `committees`
--
ALTER TABLE `committees`
  ADD CONSTRAINT `fk_committee_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_committee_jurisdiction` FOREIGN KEY (`jurisdiction_id`) REFERENCES `jurisdictions` (`jurisdiction_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `committee_members`
--
ALTER TABLE `committee_members`
  ADD CONSTRAINT `fk_cm_committee` FOREIGN KEY (`committee_id`) REFERENCES `committees` (`committee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `committee_performance`
--
ALTER TABLE `committee_performance`
  ADD CONSTRAINT `fk_perf_committee` FOREIGN KEY (`committee_id`) REFERENCES `committees` (`committee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `committee_reports`
--
ALTER TABLE `committee_reports`
  ADD CONSTRAINT `fk_report_committee` FOREIGN KEY (`committee_id`) REFERENCES `committees` (`committee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `jurisdictions`
--
ALTER TABLE `jurisdictions`
  ADD CONSTRAINT `fk_jurisdiction_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_activity` FOREIGN KEY (`activity_log_id`) REFERENCES `activity_logs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notification_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_background`
--
ALTER TABLE `user_background`
  ADD CONSTRAINT `fk_user_background_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `workload_assignments`
--
ALTER TABLE `workload_assignments`
  ADD CONSTRAINT `fk_wl_member` FOREIGN KEY (`committee_member_id`) REFERENCES `committee_members` (`committee_member_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
