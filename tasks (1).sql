-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2026 at 05:02 PM
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
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `addedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `editedDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `Task`, `status`, `addedDate`, `editedDate`) VALUES
(1, '<br /><b>Warning</b>:  Undefined array key ', 0, '2026-08-25 13:06:21', '2026-08-25 13:10:04'),
(2, '<br /><b>Warning</b>:  Undefined array key ', 0, '2026-08-25 13:06:51', '2026-08-25 13:07:35'),
(3, 'test data', 0, '2026-08-25 13:10:15', '2026-08-25 13:25:27'),
(4, 'test data', 0, '2026-08-25 13:12:10', '2026-08-25 13:12:13'),
(5, 'test data', 0, '2026-08-25 13:25:36', '2026-08-25 13:28:31'),
(6, 'test data', 0, '2026-08-25 13:28:39', '2026-08-25 13:28:45'),
(7, 'test data', 0, '2026-08-25 13:28:52', '2026-08-25 13:30:02'),
(8, 'test 1', 0, '2026-08-25 13:29:10', '2026-08-25 13:29:34'),
(9, 'test data 02', 0, '2026-08-25 13:31:45', '2026-08-25 13:32:01'),
(10, 'Prepare a Glass door', 0, '2026-08-25 14:28:46', '2026-08-25 14:28:58'),
(11, 'Prepare a Glass door', 0, '2026-08-25 14:29:04', '2026-08-25 14:29:21'),
(12, 'test data', 0, '2026-08-25 14:42:11', '2026-08-25 14:42:19');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
