-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2026 at 01:26 PM
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
(33, 'rakshith9346@gmail.com', '::1', '17.476833400824315, 78.36323856358894', 'Hyderabad, Telangana, India', '2026-08-27 13:24:19', 'SUCCESS', '2026-08-27 16:54:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
