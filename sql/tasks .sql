-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 02:57 PM
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
(4, 'test data', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:12:10', '2026-08-25 13:12:13'),
(6, 'test data', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:28:39', '2026-08-25 13:28:45'),
(7, 'test data', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:28:52', '2026-08-25 13:30:02'),
(8, 'test 1', NULL, 0, 0, 'Medium', 'Todo', '2026-08-25 13:29:10', '2026-08-25 13:29:34'),
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
(21, 'test 1', NULL, 0, 0, 'Medium', 'Todo', '2026-08-26 09:07:02', '2026-08-26 11:31:05'),
(23, 'Task manager', NULL, 2, 0, 'Medium', 'Todo', '2026-08-26 11:59:18', '2026-08-26 12:08:45'),
(25, 'Test App', NULL, 2, 0, 'Medium', 'Todo', '2026-08-26 12:58:17', '2026-08-26 12:58:20'),
(26, 'test', NULL, 2, 0, 'Medium', 'Todo', '2026-08-26 13:59:01', '2026-08-26 13:59:12'),
(27, 'data tests', NULL, 2, 0, 'Medium', 'Todo', '2026-08-27 07:30:02', '2026-08-27 07:30:20'),
(28, 'data test', NULL, 2, 0, 'Medium', 'Todo', '2026-08-27 07:31:44', '2026-08-27 07:32:04'),
(29, 'glass door', NULL, 2, 0, 'Medium', 'Todo', '2026-08-27 07:37:37', '2026-08-27 07:40:10'),
(30, 'apps', NULL, 2, 1, 'Medium', 'Todo', '2026-08-27 07:38:07', '2026-08-31 08:55:13'),
(31, 'Prepare a Glass door', 'Check measurements and prepare materials.', 1, 1, 'Medium', 'Done', '2026-08-27 08:02:23', '2026-08-31 10:09:14'),
(33, 'test data', 'check the data', 2, 1, 'Medium', 'Todo', '2026-08-27 08:11:12', '2026-08-31 09:00:47'),
(34, 'data prepare', 'check the data', 2, 0, 'Medium', 'Todo', '2026-08-27 08:17:04', '2026-08-27 08:17:28'),
(35, 'test data', 'data', 2, 0, 'Medium', 'Todo', '2026-08-27 08:18:59', '2026-08-27 08:20:08'),
(36, 'test data', 'hgcfh', 2, 0, 'Medium', 'Todo', '2026-08-27 08:20:57', '2026-08-27 08:22:47'),
(38, 'test data', 'hfgh', 0, 1, 'Medium', 'Todo', '2026-08-27 08:43:06', '2026-08-31 09:01:01'),
(42, 'Prepare a Glass door', 'Prepare glass door for installation', 1, 1, 'Medium', 'Done', '2026-08-27 09:09:20', '2026-08-31 10:00:49'),
(43, 'test data', 'app', 1, 0, 'Medium', 'Review', '2026-08-27 09:09:31', '2026-08-31 08:56:11'),
(44, 'test data', 'n m', 0, 1, 'Medium', 'Todo', '2026-08-27 13:45:05', '2026-08-31 09:09:06'),
(45, 'Prepare a Glass door', 'Prepare Glass door and installed it', 1, 0, 'Medium', 'In Progress', '2026-08-27 13:46:56', '2026-08-31 10:08:59'),
(46, 'Prepare a Glass door', 'wcbkjna', 2, 1, 'Medium', 'Todo', '2026-08-28 16:37:45', '2026-08-31 09:00:54'),
(47, 'test data', 'test data', 1, 1, 'Medium', 'In Progress', '2026-08-28 17:30:44', '2026-08-31 10:30:51'),
(48, 'test', NULL, 1, 0, 'Medium', 'Todo', '2026-08-31 07:17:20', '2026-08-31 10:49:25'),
(49, 'Test Glass', NULL, 1, 1, 'Low', 'Done', '2026-08-31 07:19:48', '2026-08-31 10:33:10'),
(50, 'glass door', NULL, 1, 0, 'Medium', 'In Progress', '2026-08-31 08:05:35', '2026-08-31 08:39:58'),
(52, 'Prepare a  door', 'test the door and installed it', 1, 1, 'Medium', 'Done', '2026-08-31 09:10:09', '2026-08-31 10:21:20'),
(55, 'test data', 'data', 1, 1, 'Low', 'Done', '2026-08-31 09:38:40', '2026-08-31 09:42:24'),
(56, 'Prepare a Glass door', 'prepare based on requriement & installed it', 1, 1, 'Low', 'Done', '2026-08-31 09:40:44', '2026-08-31 09:41:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
