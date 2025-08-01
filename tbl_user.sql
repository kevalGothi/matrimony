-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 01:48 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `matrimony`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `user_id` int(11) NOT NULL,
  `user_gen_id` varchar(255) NOT NULL,
  `user_religion` varchar(255) NOT NULL,
  `user_name` text NOT NULL,
  `user_gender` varchar(255) NOT NULL,
  `user_age` varchar(255) NOT NULL,
  `user_phone` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_pass` varchar(255) NOT NULL,
  `user_status` int(11) NOT NULL,
  `user_otp_status` int(11) NOT NULL,
  `user_payment_status` varchar(255) NOT NULL,
  `user_otp` int(11) NOT NULL,
  `user_city` text NOT NULL,
  `user_dob` varchar(255) NOT NULL,
  `user_height` varchar(255) NOT NULL,
  `user_weight` varchar(255) NOT NULL,
  `user_fatherName` text NOT NULL,
  `user_motherName` text NOT NULL,
  `user_create_date` datetime NOT NULL DEFAULT current_timestamp(),
  `user_address` varchar(255) NOT NULL,
  `user_jobType` varchar(255) NOT NULL,
  `user_companyName` varchar(255) NOT NULL,
  `user_currentResident` text NOT NULL,
  `user_salary` varchar(255) NOT NULL,
  `user_degree` varchar(255) NOT NULL,
  `user_school` varchar(255) NOT NULL,
  `user_collage` varchar(255) NOT NULL,
  `user_hobbies` text NOT NULL,
  `user_img` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`user_id`, `user_gen_id`, `user_religion`, `user_name`, `user_gender`, `user_age`, `user_phone`, `user_email`, `user_pass`, `user_status`, `user_otp_status`, `user_payment_status`, `user_otp`, `user_city`, `user_dob`, `user_height`, `user_weight`, `user_fatherName`, `user_motherName`, `user_create_date`, `user_address`, `user_jobType`, `user_companyName`, `user_currentResident`, `user_salary`, `user_degree`, `user_school`, `user_collage`, `user_hobbies`, `user_img`) VALUES
(4, 'SR/265321', '', 'SR', 'Male', '22', '7585869548', '', '123456', 0, 1, '1', 649569, '', '', '', '', '', '', '2025-04-21 10:46:53', '', '', '', '', '', '', '', '', '', ''),
(5, 'SS/656406', 'Islam', 'SS', 'Male', '27', '7585869548', '', '123456', 0, 0, '1', 318440, '', '', '', '', '', '', '2025-05-06 11:14:39', '', '', '', '', '', '', '', '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
