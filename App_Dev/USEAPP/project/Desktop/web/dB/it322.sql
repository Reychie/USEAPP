-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 12:40 AM
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
-- Database: `it322`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendanceId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `date` date NOT NULL,
  `timeIn` time DEFAULT NULL,
  `timeOut` time DEFAULT NULL,
  `status` enum('Present','Late','Absent') NOT NULL,
  `location` varchar(255) DEFAULT 'Office',
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendanceId`, `userId`, `date`, `timeIn`, `timeOut`, `status`, `location`, `reason`) VALUES
(1, 3, '2025-04-11', '07:34:54', '07:35:22', 'Present', '8.485869709303087,124.65710754203064', NULL),
(2, 4, '2025-04-11', '08:05:46', '08:20:33', 'Present', 'Location not available', NULL),
(4, 4, '2025-05-16', '07:03:42', '07:05:14', 'Present', '8.48588614566683,124.65699818289522', NULL),
(5, 10, '2025-05-29', '10:25:25', '10:25:29', 'Late', 'Location not available', NULL),
(6, 3, '2025-05-01', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(7, 3, '2025-05-02', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(8, 3, '2025-05-05', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(9, 3, '2025-05-06', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(10, 3, '2025-05-07', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(11, 3, '2025-05-08', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(12, 3, '2025-05-09', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(13, 3, '2025-05-12', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(14, 3, '2025-05-13', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(15, 3, '2025-05-14', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(16, 3, '2025-05-15', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(17, 3, '2025-05-16', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(18, 3, '2025-05-19', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(19, 3, '2025-05-20', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(20, 3, '2025-05-21', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(21, 3, '2025-05-22', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(22, 3, '2025-05-23', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(23, 3, '2025-05-26', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(24, 3, '2025-05-27', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(25, 3, '2025-05-28', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(26, 3, '2025-05-29', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(27, 3, '2025-05-30', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(28, 3, '2025-05-01', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(29, 3, '2025-05-02', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(30, 3, '2025-05-05', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(31, 3, '2025-05-06', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(32, 3, '2025-05-07', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(33, 3, '2025-05-08', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(34, 3, '2025-05-09', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(35, 3, '2025-05-12', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(36, 3, '2025-05-13', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(37, 3, '2025-05-14', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(38, 3, '2025-05-15', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(39, 3, '2025-05-16', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(40, 3, '2025-05-19', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(41, 3, '2025-05-20', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(42, 3, '2025-05-21', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(43, 3, '2025-05-22', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(44, 3, '2025-05-23', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(45, 3, '2025-05-26', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(46, 3, '2025-05-27', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(47, 3, '2025-05-28', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(48, 3, '2025-05-29', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(49, 3, '2025-05-30', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(50, 3, '2025-05-01', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(51, 3, '2025-05-02', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(52, 3, '2025-05-05', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(53, 3, '2025-05-06', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(54, 3, '2025-05-07', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(55, 3, '2025-05-08', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(56, 3, '2025-05-09', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(57, 3, '2025-05-12', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(58, 3, '2025-05-13', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(59, 3, '2025-05-14', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(60, 3, '2025-05-15', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(61, 3, '2025-05-16', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(62, 3, '2025-05-19', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(63, 3, '2025-05-20', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(64, 3, '2025-05-21', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(65, 3, '2025-05-22', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(66, 3, '2025-05-23', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(67, 3, '2025-05-26', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(68, 3, '2025-05-27', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(69, 3, '2025-05-28', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(70, 3, '2025-05-29', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(71, 3, '2025-05-30', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(72, 3, '2025-05-01', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(73, 3, '2025-05-02', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(74, 3, '2025-05-05', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(75, 3, '2025-05-06', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(76, 3, '2025-05-07', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(77, 3, '2025-05-08', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(78, 3, '2025-05-09', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(79, 3, '2025-05-12', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(80, 3, '2025-05-13', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(81, 3, '2025-05-14', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(82, 3, '2025-05-15', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(83, 3, '2025-05-16', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(84, 3, '2025-05-19', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(85, 3, '2025-05-20', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(86, 3, '2025-05-21', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(87, 3, '2025-05-22', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(88, 3, '2025-05-23', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(89, 3, '2025-05-26', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(90, 3, '2025-05-27', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(91, 3, '2025-05-28', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(92, 3, '2025-05-29', '06:32:03', '05:32:03', 'Present', 'Office', NULL),
(93, 3, '2025-05-30', '06:32:03', '05:32:03', 'Present', 'Office', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payslip`
--

CREATE TABLE `payslip` (
  `payslipId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `salaryId` int(11) NOT NULL,
  `pdfPath` varchar(255) NOT NULL,
  `generatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslip`
--

INSERT INTO `payslip` (`payslipId`, `userId`, `salaryId`, `pdfPath`, `generatedAt`, `status`) VALUES
(1, 3, 2, '../uploads/payslips/payslip_3_2_1748527029.pdf', '2025-05-29 10:48:50', 'Paid'),
(2, 4, 3, '../uploads/payslips/payslip_4_3_1748515730.pdf', '2025-05-29 10:48:50', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `salary`
--

CREATE TABLE `salary` (
  `salaryId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `basicSalary` decimal(10,2) NOT NULL DEFAULT 20000.00,
  `overtime` decimal(10,2) DEFAULT 0.00,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `totalSalary` decimal(10,2) NOT NULL,
  `daysWorked` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary`
--

INSERT INTO `salary` (`salaryId`, `userId`, `basicSalary`, `overtime`, `bonus`, `deductions`, `tax`, `month`, `year`, `totalSalary`, `daysWorked`) VALUES
(1, 10, 20000.00, 0.00, 0.00, 0.00, 0.00, 5, 2025, 20000.00, 0),
(2, 3, 20000.00, 0.00, 0.00, 0.00, 0.00, 5, 2025, 20000.00, 0),
(3, 4, 20000.00, 0.00, 0.00, 0.00, 0.00, 5, 2025, 20000.00, 0),
(4, 1, 20000.00, 0.00, 0.00, 0.00, 0.00, 5, 2025, 20000.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNumber` varchar(255) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `birthday` date NOT NULL,
  `verification` int(11) NOT NULL DEFAULT 0,
  `profilePicture` longblob DEFAULT NULL,
  `userRole` enum('admin','user') NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `firstName`, `lastName`, `email`, `password`, `phoneNumber`, `gender`, `birthday`, `verification`, `profilePicture`, `userRole`, `createdAt`) VALUES
(1, 'Zail', 'Bacor', 'zail@gmail.com', '123', '09567702554', 'Male', '2025-04-09', 0, NULL, 'admin', '2025-04-11 04:24:39'),
(3, 'Angelo', 'alejo', 'alejo@gmail.com', '123', '09567702554', 'Male', '2025-04-23', 0, NULL, 'user', '2025-04-11 05:30:08'),
(4, 'Sherly', 'Atillo', 'sherly@gmail.com', '123', '09567702554', 'Male', '2025-04-09', 0, NULL, 'user', '2025-04-11 05:54:35'),
(9, 'Billy', 'Bacor', 'billy@gmail.com', '123', '09567702554', 'Male', '2025-05-16', 0, NULL, 'user', '2025-05-16 05:06:41'),
(10, 'angelo', 'angelo', 'alejo.angeloreychie@gmail.com', '123', '09123226722', 'Male', '2025-05-28', 0, NULL, 'user', '2025-05-29 08:23:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendanceId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `payslip`
--
ALTER TABLE `payslip`
  ADD PRIMARY KEY (`payslipId`),
  ADD KEY `userId` (`userId`),
  ADD KEY `salaryId` (`salaryId`);

--
-- Indexes for table `salary`
--
ALTER TABLE `salary`
  ADD PRIMARY KEY (`salaryId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendanceId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `payslip`
--
ALTER TABLE `payslip`
  MODIFY `payslipId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `salary`
--
ALTER TABLE `salary`
  MODIFY `salaryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE;

--
-- Constraints for table `payslip`
--
ALTER TABLE `payslip`
  ADD CONSTRAINT `payslip_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslip_ibfk_2` FOREIGN KEY (`salaryId`) REFERENCES `salary` (`salaryId`) ON DELETE CASCADE;

--
-- Constraints for table `salary`
--
ALTER TABLE `salary`
  ADD CONSTRAINT `salary_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
