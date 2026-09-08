-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2026 at 07:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `todo_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `emailaddress` varchar(255) NOT NULL,
  `ipaddress` varchar(45) DEFAULT NULL,
  `latlang` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `last_attempted_time` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `addedDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `emailaddress`, `ipaddress`, `latlang`, `location`, `last_attempted_time`, `status`, `addedDate`) VALUES
(1, 'rakeshreddy5834@gmail.com', '::1', 'Not Available', 'Not Available', '2026-08-27 09:16:55', 'SUCCESS', '2026-08-27 12:46:55'),
(2, 'rakesh@gmail.com', '::1', '17.476817791335172, 78.36327970228858', 'Hyderabad, Telangana, India', '2026-08-27 09:17:21', 'FAILED', '2026-08-27 12:47:21'),
(3, 'rakeshreddy5834@gmail.com', '::1', '17.476817791335172, 78.36327970228858', 'Hyderabad, Telangana, India', '2026-08-27 09:17:36', 'FAILED', '2026-08-27 12:47:36'),
(4, 'rakeshreddy5834@gmail.com', '::1', '17.476884532260357, 78.36331265548513', 'Hyderabad, Telangana, India', '2026-08-27 09:17:43', 'SUCCESS', '2026-08-27 12:47:43'),
(5, 'rakeshreddy5834@gmail.com', '::1', '17.47681948486438, 78.36325592893459', 'Hyderabad, Telangana, India', '2026-08-27 09:29:42', 'SUCCESS', '2026-08-27 12:59:42'),
(6, 'rakeshreddy5834@gmail.com', '::1', '17.47681948486438, 78.36325592893459', 'Hyderabad, Telangana, India', '2026-08-27 09:31:08', 'SUCCESS', '2026-08-27 13:01:08'),
(7, 'rakeshreddy@gmail.com', '::1', '17.476795101698283, 78.36325669165147', 'Hyderabad, Telangana, India', '2026-08-27 10:41:23', 'SUCCESS', '2026-08-27 14:11:23'),
(8, 'rakeshreddy5834@gmail.com', '::1', '17.47673775236546, 78.3634247568007', 'Not Available', '2026-08-27 10:41:56', 'SUCCESS', '2026-08-27 14:11:56'),
(9, 'rakeshreddy@gmail.com', '::1', '17.47673775236546, 78.3634247568007', 'Hyderabad, Telangana, India', '2026-08-27 10:42:07', 'SUCCESS', '2026-08-27 14:12:07'),
(10, 'rakeshyerra@gmail.com', '::1', '17.47673775236546, 78.3634247568007', 'Hyderabad, Telangana, India', '2026-08-27 10:42:29', 'FAILED', '2026-08-27 14:12:29'),
(11, 'rakeshreddy5834@gmail.com', '::1', '17.476775747634534, 78.36327174083382', 'Hyderabad, Telangana, India', '2026-08-27 10:42:52', 'SUCCESS', '2026-08-27 14:12:52'),
(12, 'rakeshreddyyerra@gmail.com', '::1', '17.47686651032743, 78.36324006047899', 'Hyderabad, Telangana, India', '2026-08-27 10:43:20', 'SUCCESS', '2026-08-27 14:13:20'),
(13, 'rakeshreddyyerra@gmail.com', '::1', 'Not Available', 'Not Available', '2026-08-27 10:43:59', 'SUCCESS', '2026-08-27 14:13:59'),
(14, 'rakeshreddy5834@gmail.com', '::1', '17.476824672349647, 78.36332002680957', 'Hyderabad, Telangana, India', '2026-08-27 10:44:18', 'SUCCESS', '2026-08-27 14:14:18'),
(15, 'rakeshreddy5834@gmail.com', '::1', '17.47686651032743, 78.36324006047899', 'Not Available', '2026-08-27 10:44:48', 'SUCCESS', '2026-08-27 14:14:48'),
(16, 'rakeshreddyyerra@gmail.com', '::1', '17.476589620379485, 78.36340277655576', 'Hyderabad, Telangana, India', '2026-08-27 10:44:54', 'SUCCESS', '2026-08-27 14:14:54'),
(17, 'rakeshreddyyerra@gmail.com', '::1', '17.476589620379485, 78.36340277655576', 'Hyderabad, Telangana, India', '2026-08-27 10:47:38', 'SUCCESS', '2026-08-27 14:17:38'),
(18, 'rakeshreddy5834@gmail.com', '::1', 'Not Available', 'Not Available', '2026-08-27 10:48:27', 'SUCCESS', '2026-08-27 14:18:27'),
(19, 'rakeshreddy5834@gmail.com', '127.0.0.1', '17.47677082401732, 78.36332915379627', 'Not Available', '2026-08-27 11:13:55', 'SUCCESS', '2026-08-27 14:43:55'),
(20, 'rakeshreddy2001@gmail.com', '127.0.0.1', '17.4768671752233, 78.36325548276172', 'Not Available', '2026-08-27 11:22:08', 'SUCCESS', '2026-08-27 14:52:08'),
(21, 'rakeshreddy2001@gmail.com', '127.0.0.1', '17.476874468978906, 78.36321952605293', 'Not Available', '2026-08-27 11:32:51', 'SUCCESS', '2026-08-27 15:02:51'),
(22, 'rakeshreddy2001@gmail.com', '127.0.0.1', 'Not Available', 'Hyderabad, Telangana, India', '2026-08-27 11:34:58', 'SUCCESS', '2026-08-27 15:04:58'),
(23, 'rakeshreddy2001@gmail.com', '::1', '17.476848227115976, 78.3632867015828', 'Hyderabad, Telangana, India', '2026-08-27 11:49:59', 'SUCCESS', '2026-08-27 15:19:59'),
(24, 'rakeshreddy2001@gmail.com', '::1', '17.476813936107348, 78.3632969337797', 'Hyderabad, Telangana, India', '2026-08-27 11:55:31', 'SUCCESS', '2026-08-27 15:25:31'),
(25, 'rakeshreddy5834@gmail.com', '::1', '17.476849100910375, 78.36327392459759', 'Hyderabad, Telangana, India', '2026-08-27 11:55:53', 'SUCCESS', '2026-08-27 15:25:53'),
(26, 'yerrarakeshreddy@gmail.com', '127.0.0.1', '17.476815458600992, 78.36329832353874', 'Hyderabad, Telangana, India', '2026-08-27 11:57:17', 'FAILED', '2026-08-27 15:27:17'),
(27, 'yerrarakeshreddy@gmail.com', '127.0.0.1', '17.476875631353725, 78.36327748233396', 'Hyderabad, Telangana, India', '2026-08-27 11:58:30', 'SUCCESS', '2026-08-27 15:28:30'),
(28, 'rakeshreddyyerra07@gmail.com', '::1', '17.476913725404508, 78.36329269815148', 'Hyderabad, Telangana, India', '2026-08-27 12:00:46', 'SUCCESS', '2026-08-27 15:30:46'),
(29, 'yerrarakeshreddy07@gmail.com', '::1', '17.476892673529274, 78.36331178850534', 'Hyderabad, Telangana, India', '2026-08-27 12:01:54', 'SUCCESS', '2026-08-27 15:31:54'),
(30, 'rakshith9346@gmail.com', '::1', '17.476885100094293, 78.3632930400641', 'Hyderabad, Telangana, India', '2026-08-27 12:03:47', 'FAILED', '2026-08-27 15:33:47'),
(31, 'rakshith9346@gmail.com', '::1', '17.47676977703162, 78.36329301725601', 'Hyderabad, Telangana, India', '2026-08-27 12:04:43', 'SUCCESS', '2026-08-27 15:34:43'),
(32, 'rakshith9346@gmail.com', '::1', '17.476777236682572, 78.36329689315454', 'Hyderabad, Telangana, India', '2026-08-27 12:21:54', 'SUCCESS', '2026-08-27 15:51:54'),
(33, 'rakshith9346@gmail.com', '::1', '17.476833400824315, 78.36323856358894', 'Hyderabad, Telangana, India', '2026-08-27 13:24:19', 'SUCCESS', '2026-08-27 16:54:19'),
(34, 'rakeshreddy20@gmail.com', '49.43.226.155', '17.476775705775772, 78.36317942400136', 'Hyderabad, Telangana, India', '2026-08-27 15:15:10', 'FAILED', '2026-08-27 18:45:10'),
(35, 'rakeshreddy20@gmail.com', '49.43.226.155', '17.476775705775772, 78.36317942400136', 'Not Available', '2026-08-27 15:15:11', 'FAILED', '2026-08-27 18:45:11'),
(36, 'rakeshreddy2001@gmail.com', '49.43.226.155', '17.476748464949118, 78.36319897623002', 'Hyderabad, Telangana, India', '2026-08-27 15:15:19', 'SUCCESS', '2026-08-27 18:45:19'),
(37, 'rakeshreddy2001@gmail.com', '49.43.226.155', '17.47670033320018, 78.3632482289174', 'Hyderabad, Telangana, India', '2026-08-27 15:32:15', 'SUCCESS', '2026-08-27 19:02:15'),
(38, 'rakeshreddy2001@gmail.com', '49.43.226.155', '17.47670033320018, 78.3632482289174', 'Hyderabad, Telangana, India', '2026-08-27 15:32:16', 'SUCCESS', '2026-08-27 19:02:16'),
(39, 'rakeshreddyyerra07@gmail.com', '::1', '17.476825021846423, 78.36323987215262', 'Hyderabad, Telangana, India', '2026-08-27 19:11:48', 'Failed', '2026-08-27 19:11:48'),
(40, 'rakeshreddyyerra07@gmail.com', '49.43.226.155', '17.476886097509436, 78.36320889419305', 'Hyderabad, Telangana, India', '2026-08-27 15:46:24', 'FAILED', '2026-08-27 19:16:24'),
(41, 'rakeshreddy5834@gmail.com', '49.43.226.155', '17.476886097509436, 78.36320889419305', 'Hyderabad, Telangana, India', '2026-08-27 15:46:31', 'FAILED', '2026-08-27 19:16:31'),
(42, 'rakeshreddyyerra@gmail.com', '49.43.226.155', '17.47689554161988, 78.36321733350026', 'Not Available', '2026-08-27 15:46:37', 'SUCCESS', '2026-08-27 19:16:37'),
(43, 'rakeshreddy5834@gmail.com', '49.43.226.155', '17.476793524211246, 78.36319666329872', 'Hyderabad, Telangana, India', '2026-08-27 15:47:33', 'SUCCESS', '2026-08-27 19:17:33'),
(44, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476717415150624, 78.36322960701173', 'Hyderabad, Telangana, India', '2026-08-28 18:36:27', 'SUCCESS', '2026-08-28 22:06:27'),
(45, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476717415150624, 78.36322960701173', 'Hyderabad, Telangana, India', '2026-08-28 18:37:08', 'SUCCESS', '2026-08-28 22:07:08'),
(46, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476680627314458, 78.36323444759984', 'Hyderabad, Telangana, India', '2026-08-28 19:32:54', 'SUCCESS', '2026-08-28 23:02:54'),
(47, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476680627314458, 78.36323444759984', 'Hyderabad, Telangana, India', '2026-08-28 19:32:56', 'SUCCESS', '2026-08-28 23:02:56'),
(48, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476657066663638, 78.36325644651788', 'Hyderabad, Telangana, India', '2026-08-28 19:34:21', 'FAILED', '2026-08-28 23:04:21'),
(49, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.47680446418567, 78.36324606154562', 'Hyderabad, Telangana, India', '2026-08-31 09:08:25', 'SUCCESS', '2026-08-31 12:38:25'),
(50, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.47680446418567, 78.36324606154562', 'Hyderabad, Telangana, India', '2026-08-31 09:13:20', 'SUCCESS', '2026-08-31 12:43:20'),
(51, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.47681354670369, 78.36325551963576', 'Hyderabad, Telangana, India', '2026-08-31 12:30:30', 'SUCCESS', '2026-08-31 16:00:30'),
(52, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 12:57:05', 'SUCCESS', '2026-08-31 16:27:05'),
(53, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:17', 'FAILED', '2026-08-31 16:30:17'),
(54, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:18', 'FAILED', '2026-08-31 16:30:18'),
(55, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:19', 'FAILED', '2026-08-31 16:30:19'),
(56, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:21', 'FAILED', '2026-08-31 16:30:21'),
(57, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:22', 'FAILED', '2026-08-31 16:30:22'),
(58, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:24', 'FAILED', '2026-08-31 16:30:24'),
(59, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:00:25', 'FAILED', '2026-08-31 16:30:25'),
(60, 'rakeshreddy5834@gmail.com', '49.43.225.63', '17.476848180548586, 78.36323873375076', 'Hyderabad, Telangana, India', '2026-08-31 13:10:29', 'SUCCESS', '2026-08-31 16:40:29'),
(61, 'rakeshreddy5834@gmail.com', '49.43.225.13', '17.476761147020422, 78.36323678481901', 'Hyderabad, Telangana, India', '2026-09-01 10:12:26', 'SUCCESS', '2026-09-01 13:42:26'),
(62, 'rakeshreddyyerra@gmail.com', '49.43.226.28', '17.47685739792758, 78.3632534851721', 'Hyderabad, Telangana, India', '2026-09-03 13:49:45', 'SUCCESS', '2026-09-03 17:19:45'),
(63, 'rakeshreddyyerra@gmail.com', '49.43.226.28', '17.47682028949901, 78.36327268291782', 'Hyderabad, Telangana, India', '2026-09-03 14:05:45', 'SUCCESS', '2026-09-03 17:35:45'),
(64, 'rakeshreddyyerra@gmail.com', '49.43.226.15', '17.47692623675459, 78.36318258292033', 'Hyderabad, Telangana, India', '2026-09-07 10:40:08', 'SUCCESS', '2026-09-07 14:10:08'),
(65, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.47692124788635, 78.36324733612283', 'Hyderabad, Telangana, India', '2026-09-07 11:21:02', 'SUCCESS', '2026-09-07 14:51:02'),
(66, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.47692124788635, 78.36324733612283', 'Hyderabad, Telangana, India', '2026-09-07 11:23:01', 'SUCCESS', '2026-09-07 14:53:01'),
(67, 'rakeshreddyyerra@gmail.com', '49.43.226.15', '17.47692124788635, 78.36324733612283', 'Hyderabad, Telangana, India', '2026-09-07 11:33:01', 'SUCCESS', '2026-09-07 15:03:01'),
(68, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.47691387383563, 78.36320749296377', 'Hyderabad, Telangana, India', '2026-09-07 11:33:42', 'SUCCESS', '2026-09-07 15:03:42'),
(69, 'rakeshreddyyerra@gmail.com', '49.43.226.15', '17.476900602020518, 78.36320258470217', 'Hyderabad, Telangana, India', '2026-09-07 11:38:47', 'SUCCESS', '2026-09-07 15:08:47'),
(70, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476819845958126, 78.36316625849666', 'Hyderabad, Telangana, India', '2026-09-07 12:22:21', 'SUCCESS', '2026-09-07 15:52:21'),
(71, 'rakeshreddyyerra@gmail.com', '49.43.226.15', '17.476819845958126, 78.36316625849666', 'Hyderabad, Telangana, India', '2026-09-07 12:39:07', 'SUCCESS', '2026-09-07 16:09:07'),
(72, 'rakeshreddy5834@gmail.com', '49.43.226.15', '17.476819845958126, 78.36316625849666', 'Hyderabad, Telangana, India', '2026-09-07 12:44:23', 'SUCCESS', '2026-09-07 16:14:23'),
(73, 'rakeshreddyyerra@gmail.com', '49.43.226.15', '17.476955460577216, 78.36323245250303', 'Hyderabad, Telangana, India', '2026-09-07 13:10:04', 'SUCCESS', '2026-09-07 16:40:04'),
(74, 'rakeshreddyyerra@gmail.com', '49.43.224.18', '17.476910811873434, 78.3632481366188', 'Hyderabad, Telangana, India', '2026-09-08 06:10:15', 'SUCCESS', '2026-09-08 09:40:15'),
(75, 'rakeshreddy5834@gmail.com', '49.43.224.18', '17.476910811873434, 78.3632481366188', 'Hyderabad, Telangana, India', '2026-09-08 06:13:47', 'SUCCESS', '2026-09-08 09:43:47'),
(76, 'rakeshreddyyerra@gmail.com', '49.43.224.18', '17.476895903053038, 78.36326716136772', 'Hyderabad, Telangana, India', '2026-09-08 07:04:40', 'SUCCESS', '2026-09-08 10:34:40'),
(77, 'rakeshreddy5834@gmail.com', '49.43.224.18', '17.476895903053038, 78.36326716136772', 'Hyderabad, Telangana, India', '2026-09-08 07:09:11', 'SUCCESS', '2026-09-08 10:39:11'),
(78, 'rakeshreddyyerra@gmail.com', '49.43.224.18', '17.476895903053038, 78.36326716136772', 'Hyderabad, Telangana, India', '2026-09-08 07:11:54', 'SUCCESS', '2026-09-08 10:41:54'),
(79, 'rakeshreddy5834@gmail.com', '49.43.224.18', '17.476875477940585, 78.36324579267776', 'Hyderabad, Telangana, India', '2026-09-08 07:17:15', 'SUCCESS', '2026-09-08 10:47:15'),
(80, 'rakeshreddyyerra@gmail.com', '49.43.224.18', '17.47688579390875, 78.36325894994293', 'Hyderabad, Telangana, India', '2026-09-08 07:18:26', 'SUCCESS', '2026-09-08 10:48:26'),
(81, 'rakeshreddy5834@gmail.com', '49.43.224.18', '17.476815555798552, 78.36328699300502', 'Hyderabad, Telangana, India', '2026-09-08 07:18:54', 'SUCCESS', '2026-09-08 10:48:54'),
(82, 'rakeshreddyyerra@gmail.com', '49.43.224.18', '17.476894202873794, 78.36328223571965', 'Hyderabad, Telangana, India', '2026-09-08 07:29:10', 'SUCCESS', '2026-09-08 10:59:10'),
(83, 'rakeshreddy5834@gmail.com', '49.43.224.18', '17.476894202873794, 78.36328223571965', 'Hyderabad, Telangana, India', '2026-09-08 07:33:57', 'SUCCESS', '2026-09-08 11:03:57'),
(84, 'rakeshreddyyerra@gmail.com', '49.43.224.18', '17.476807217583275, 78.36322304777096', 'Hyderabad, Telangana, India', '2026-09-08 07:43:35', 'SUCCESS', '2026-09-08 11:13:35'),
(85, 'rakeshreddy5834@gmail.com', '49.43.224.18', '17.476807217583275, 78.36322304777096', 'Hyderabad, Telangana, India', '2026-09-08 07:44:26', 'SUCCESS', '2026-09-08 11:14:26');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(5, 6, '554b262dd0f161bb7b3859b120c510dfb6b11415a49dde3902008a75b9362a51', '2026-08-26 15:36:24', '2026-08-26 12:36:24'),
(9, 1, 'e54ab6a8ef93938a3e25b619f7efe76f3a92a46355a62cf838017f9bd29baf0b', '2026-09-03 18:35:11', '2026-09-03 12:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `Task` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `priority` enum('High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `progress` enum('Todo','In Progress','Review','Done') NOT NULL DEFAULT 'Todo',
  `addedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `editedDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `Task`, `Description`, `status`, `is_completed`, `priority`, `progress`, `addedDate`, `editedDate`) VALUES
(6, 'test data', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:28:39', '2026-08-25 13:28:45'),
(7, 'test data', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:28:52', '2026-08-25 13:30:02'),
(8, 'test 1', NULL, 0, 0, 'Medium', 'Review', '2026-08-25 13:29:10', '2026-09-08 05:37:15'),
(9, 'test data 02', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:31:45', '2026-08-25 13:32:01'),
(10, 'Prepare a Glass door', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 14:28:46', '2026-08-25 14:28:58'),
(11, 'Prepare a Glass door', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 14:29:04', '2026-08-25 14:29:21'),
(12, 'test data', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 14:42:11', '2026-08-25 14:42:19'),
(13, 'test data', NULL, 2, 0, 'Medium', 'Todo', '2026-08-25 15:20:09', '2026-08-25 15:30:29'),
(14, 'Prepare a Glass doors', NULL, 2, 1, 'Medium', 'Todo', '2026-08-25 15:25:14', '2026-08-31 09:25:23'),
(15, 'Prepare a Glass doors', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 15:28:15', '2026-08-25 15:28:26'),
(16, 'Prepare a Glass door', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 15:30:43', '2026-08-25 15:30:51'),
(17, 'Prepare a Glass doors', NULL, 2, 0, 'Medium', 'Todo', '2026-08-25 15:31:01', '2026-08-25 15:31:01'),
(18, 'test data', NULL, 2, 0, 'Medium', 'Todo', '2026-08-25 15:31:08', '2026-08-25 15:31:14'),
(19, 'test data', NULL, 2, 0, 'Medium', 'Todo', '2026-08-25 15:34:02', '2026-08-25 15:34:10'),
(20, 'Testing Site', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 15:40:46', '2026-08-26 11:31:11'),
(21, 'test 1', NULL, 0, 0, 'Medium', 'In Progress', '2026-08-26 09:07:02', '2026-09-08 05:37:11'),
(23, 'Task manager', NULL, 2, 1, 'Medium', 'In Progress', '2026-08-26 11:59:18', '2026-09-07 09:58:30'),
(25, 'Test App', NULL, 1, 0, 'Medium', 'Todo', '2026-08-26 12:58:17', '2026-09-03 12:15:06'),
(26, 'test', NULL, 2, 0, 'Medium', 'Todo', '2026-08-26 13:59:01', '2026-08-26 13:59:12'),
(27, 'data tests', NULL, 2, 0, 'Medium', 'Todo', '2026-08-27 07:30:02', '2026-08-27 07:30:20'),
(28, 'data test', NULL, 2, 0, 'Medium', 'Review', '2026-08-27 07:31:44', '2026-09-03 11:50:08'),
(30, 'apps', NULL, 2, 1, 'Medium', 'In Progress', '2026-08-27 07:38:07', '2026-09-07 09:41:53'),
(31, 'Prepare a Glass door', 'Check measurements and prepare materials.', 1, 1, 'Low', 'Done', '2026-08-27 08:02:23', '2026-09-03 12:13:13'),
(33, 'test data', 'check the data', 1, 1, 'Medium', 'Todo', '2026-08-27 08:11:12', '2026-08-31 13:56:05'),
(34, 'data prepare', 'check the data', 1, 0, 'Medium', 'Review', '2026-08-27 08:17:04', '2026-09-03 11:50:06'),
(42, 'Prepare a Glass door', 'Prepare glass door for installation', 1, 1, 'Low', 'Done', '2026-08-27 09:09:20', '2026-09-03 12:13:07'),
(43, 'test data', 'app', 1, 0, 'Medium', 'Review', '2026-08-27 09:09:31', '2026-08-31 08:56:11'),
(45, 'Prepare a Glass door', 'Prepare Glass door and installed it', 1, 0, 'Medium', 'In Progress', '2026-08-27 13:46:56', '2026-08-31 10:08:59'),
(47, 'test data', 'test data', 1, 1, 'Medium', 'Review', '2026-08-28 17:30:44', '2026-09-03 11:50:03'),
(50, 'Size\r\nVerify measurements', NULL, 1, 1, 'Medium', 'In Progress', '2026-08-31 08:05:35', '2026-09-03 12:13:49'),
(52, 'Prepare a  door', 'test the door and installed it', 1, 1, 'Low', 'Done', '2026-08-31 09:10:09', '2026-09-03 12:12:59'),
(55, 'test data', 'data', 1, 1, 'Low', 'Done', '2026-08-31 09:38:40', '2026-08-31 09:42:24'),
(56, 'Prepare a Glass door', 'prepare based on requriement & installed it', 1, 1, 'Low', 'Done', '2026-08-31 09:40:44', '2026-08-31 09:41:47'),
(57, 'Install Main Door', 'Install main entrance', 1, 1, 'Low', 'Done', '2026-08-31 13:49:14', '2026-08-31 13:49:59'),
(58, 'Check Door Size', 'Size\r\nVerify measurements', 1, 1, 'Medium', 'Todo', '2026-08-31 13:50:58', '2026-09-01 08:14:55'),
(59, 'Prepare a Glass door', 'test the door', 2, 0, 'Medium', 'Todo', '2026-09-07 08:40:41', '2026-09-07 09:46:25'),
(60, 'Prepare a Glass door', 'test the data', 1, 1, 'Medium', 'Done', '2026-09-07 08:41:13', '2026-09-07 08:52:29'),
(61, 'test data', 'test', 1, 1, 'Low', 'Review', '2026-09-07 09:30:16', '2026-09-08 05:28:25');

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(150) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_attachments`
--

INSERT INTO `task_attachments` (`id`, `task_id`, `user_id`, `original_name`, `stored_name`, `file_path`, `file_type`, `file_size`, `uploaded_at`) VALUES
(4, 60, 1, 'Screenshot 2026-09-08 104627.png', 'task_60_6a9f9a4b0bb909.18710884.png', 'uploads/task_files/task_60_6a9f9a4b0bb909.18710884.png', 'image/png', 193420, '2026-09-08 10:46:59'),
(6, 47, 1, 'Screenshot 2026-09-08 104617.png', 'task_47_6a9f9ab1477b26.74964908.png', 'uploads/task_files/task_47_6a9f9ab1477b26.74964908.png', 'image/png', 124578, '2026-09-08 10:48:41'),
(8, 61, 1, 'Screenshot 2026-09-08 104627.png', 'task_61_6a9fa0a59be092.64348456.png', 'uploads/task_files/task_61_6a9fa0a59be092.64348456.png', 'image/png', 193420, '2026-09-08 11:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_comments`
--

INSERT INTO `task_comments` (`id`, `task_id`, `user_id`, `comment`, `created_at`, `updated_at`) VALUES
(1, 61, 5, 'Glass door', '2026-09-08 10:20:02', NULL),
(2, 59, 1, 'Test', '2026-09-08 10:45:45', NULL),
(3, 60, 1, 'door', '2026-09-08 10:46:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `reset_token`, `reset_token_expiry`, `role`) VALUES
(1, 'Rakesh Reddy', 'rakeshreddyyerra@gmail.com', '$2y$10$msK8j8OIuNBr3oHci0Rs2eYSK4nrc8gphBCFSbiuOwSNgXaDkp.bu', NULL, NULL, 'admin'),
(2, 'Rakesh Reddy Reddy', 'rakeshyerra2001@gmail.com', '$2y$10$6OfMxADvyGGsrdIu6VovZuHVOaZBc.RHrj5so36hagMASAo8EdB7S', NULL, NULL, 'user'),
(3, 'Rakesh Reddy Reddy', 'rakeshreddy2001@gmail.com', '$2y$10$L0V/RLwAEY0Lg0Js9hB/seQTD5JtSyskCt46f/EBlMIt/m9syuPBS', NULL, NULL, 'user'),
(4, 'Rakesh Reddy Reddy', 'rakeshreddy@gmail.com', '$2y$10$RBc5tIr9RmXwjY5FftBTxup8yKR9EPUEG2PNtyETWAywFqjABX3Ry', NULL, NULL, 'user'),
(5, 'Rakesh Reddy', 'rakeshreddy5834@gmail.com', '$2y$10$R/fgjFkfTBsy4OF.JwhhfOyrAqpucsqrzXNUDyQGIsm3kRoFRMHgy', NULL, NULL, 'user'),
(6, '[name]', '[email]', '[password]', NULL, NULL, 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `task_attachments`
--
ALTER TABLE `task_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
