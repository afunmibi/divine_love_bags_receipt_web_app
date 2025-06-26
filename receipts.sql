-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 04:12 PM
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
-- Database: `divine_love_bags`
--

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `item`, `quantity`, `price`, `subtotal`, `date`) VALUES
(1, 'bag', 1, 4000.00, 4000.00, '2025-06-20 00:30:26'),
(2, 'sch bag', 2, 100000.00, 200000.00, '2025-06-20 00:30:26'),
(3, 'bag', 1, 4000.00, 4000.00, '2025-06-20 00:38:58'),
(4, 'sch bag', 2, 100000.00, 200000.00, '2025-06-20 00:38:58'),
(5, 'bag', 1, 4000.00, 4000.00, '2025-06-20 00:44:47'),
(6, 'sch bag', 2, 100000.00, 200000.00, '2025-06-20 00:44:47'),
(7, 'travel bags', 2, 5400.00, 10800.00, '2025-06-20 00:46:16'),
(8, 'purse', 1, 4000.00, 4000.00, '2025-06-20 00:51:29'),
(9, 'piano', 2, 3500.00, 7000.00, '2025-06-20 00:51:30'),
(10, 'purse', 1, 4000.00, 4000.00, '2025-06-24 23:08:32'),
(11, 'piano', 2, 3500.00, 7000.00, '2025-06-24 23:08:32'),
(12, 'purse', 1, 4000.00, 4000.00, '2025-06-24 23:18:40'),
(13, 'piano', 2, 3500.00, 7000.00, '2025-06-24 23:18:40'),
(14, 'purse', 1, 4000.00, 4000.00, '2025-06-24 23:18:57'),
(15, 'piano', 2, 3500.00, 7000.00, '2025-06-24 23:18:57'),
(16, 'bag', 1, 4000.00, 4000.00, '2025-06-24 23:20:32'),
(17, 'school bag', 1, 800.00, 800.00, '2025-06-24 23:21:04'),
(18, 'lunch bag', 2, 4000.00, 8000.00, '2025-06-24 23:21:04'),
(19, 'purse', 1, 4000.00, 4000.00, '2025-06-24 23:21:04'),
(20, 'piano', 2, 3500.00, 7000.00, '2025-06-24 23:21:04'),
(21, 'purse', 1, 4000.00, 4000.00, '2025-06-24 23:21:42'),
(22, 'piano', 2, 3500.00, 7000.00, '2025-06-24 23:21:42'),
(23, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:52:40'),
(24, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:52:44'),
(25, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:52:50'),
(26, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:52:56'),
(27, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:17'),
(28, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:17'),
(29, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:19'),
(30, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:19'),
(31, 'Lunch bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:30'),
(32, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:31'),
(33, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 07:53:36'),
(34, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 08:01:29'),
(35, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 08:01:32'),
(36, 'travel bags', 7, 7000.00, 49000.00, '2025-06-25 09:32:58'),
(37, 'school bag', 3, 5000.00, 15000.00, '2025-06-25 09:54:54'),
(38, 'school bag', 3, 5000.00, 15000.00, '2025-06-25 09:55:01'),
(39, 'school bag', 3, 5000.00, 15000.00, '2025-06-25 09:55:05'),
(40, 'school bag', 1, 5000.00, 5000.00, '2025-06-25 10:03:38'),
(41, 'travel bags', 1, 5000.00, 5000.00, '2025-06-25 10:19:35'),
(42, 'school bag', 1, 7000.00, 7000.00, '2025-06-25 10:19:35'),
(43, 'travel bags', 1, 4000.00, 4000.00, '2025-06-25 10:29:43'),
(44, 'school bag', 1, 4000.00, 4000.00, '2025-06-25 10:29:43'),
(45, 'lunch bag', 2, 5000.00, 10000.00, '2025-06-25 10:35:47'),
(46, 'school  bag', 1, 7000.00, 7000.00, '2025-06-25 10:35:47'),
(47, 'lunch bag', 2, 5000.00, 10000.00, '2025-06-25 10:47:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
