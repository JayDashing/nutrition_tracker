-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2025 at 04:51 AM
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
-- Database: `nutrack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `duration` int(11) NOT NULL,
  `calories_burned` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(10) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `age` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `username`, `activity_type`, `duration`, `calories_burned`, `created_at`, `gender`, `weight_kg`, `height_cm`, `age`) VALUES
(40, 'John Alfred', 'Soccer', 60, 520, '2025-03-07 03:23:50', 'male', 52.00, 13.00, 20),
(42, 'Jay Dash', 'Cycling', 60, 560, '2025-03-08 07:10:44', 'male', 56.00, 52.00, 20);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `announcement_text` text NOT NULL,
  `importance` int(11) NOT NULL CHECK (`importance` between 1 and 3),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `announcement_text`, `importance`, `created_at`, `created_by`) VALUES
(4, 'Greeting Message', 'Welcome to NutriTrack! I’m truly grateful and excited that you’ve chosen to use the NutriTrack website. It brings me great joy to see you engaging with this platform, and I sincerely hope it helps you achieve your health and nutrition goals. Your support and participation mean a lot, and I look forward to providing you with the best experience possible. Thank you for being a part of the NutriTrack community!', 1, '2025-03-07 16:21:52', 'nutriadmin');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_reads`
--

INSERT INTO `announcement_reads` (`id`, `username`, `announcement_id`, `read_at`) VALUES
(1, 'Jay Dash', 4, '2025-03-08 03:54:36'),
(2, 'Jay Dash', 13, '2025-03-08 03:54:36'),
(5, 'Jay Dash', 14, '2025-03-08 03:55:04'),
(8, 'John Alfred', 4, '2025-03-08 11:34:13'),
(14, 'Jay Dash', 15, '2025-03-08 12:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `feedback_text` text NOT NULL,
  `submission_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_database`
--

CREATE TABLE `food_database` (
  `id` int(11) NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `calories_per_100g` float NOT NULL,
  `protein` float NOT NULL,
  `carbs` float NOT NULL,
  `fat` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_database`
--

INSERT INTO `food_database` (`id`, `food_name`, `calories_per_100g`, `protein`, `carbs`, `fat`, `created_at`) VALUES
(1, 'Bacon', 541, 37, 1.4, 42, '2025-03-02 13:11:03'),
(2, 'Ham', 145, 21, 1.5, 6, '2025-03-02 16:18:33'),
(3, 'Egg', 143, 89, 7, 6.8, '2025-03-05 00:50:29'),
(4, 'Fish', 124, 176, 6, 15, '2025-03-06 00:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `goal_type` varchar(50) NOT NULL,
  `goal_name` varchar(255) NOT NULL,
  `goal_amount` int(11) NOT NULL,
  `target_value` int(11) NOT NULL,
  `progress_value` double NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goals`
--

INSERT INTO `goals` (`id`, `username`, `goal_type`, `goal_name`, `goal_amount`, `target_value`, `progress_value`, `created_at`) VALUES
(5, 'Jay Dash', 'daily', 'Jay Dash Goal', 2000, 2000, 247, '2025-03-08 06:06:19'),
(6, 'John Alfred', 'daily', 'My Goal', 2000, 2000, 209, '2025-03-08 06:56:36');

-- --------------------------------------------------------

--
-- Table structure for table `meals`
--

CREATE TABLE `meals` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `meal_name` varchar(255) NOT NULL,
  `calories` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `portion_size` float DEFAULT 1,
  `portion_unit` varchar(20) DEFAULT 'serving',
  `food_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meals`
--

INSERT INTO `meals` (`id`, `username`, `meal_name`, `calories`, `created_at`, `portion_size`, `portion_unit`, `food_id`) VALUES
(118, 'John Alfred', 'Bacon', 541, '2025-03-06 16:27:31', 100, 'g', 1),
(120, 'John Alfred', 'Egg', 143, '2025-03-07 03:24:03', 100, 'g', 3),
(121, 'John Alfred', 'Fish', 124, '2025-03-07 05:02:55', 100, 'g', 4),
(122, 'John Alfred', 'Fish', 124, '2025-03-07 05:02:59', 100, 'g', 4),
(124, 'John Alfred', '2 eggs with toast and coffee', 209, '2025-03-08 06:56:24', 1, 'serving', 0),
(126, 'Jay Dash', '2 waffles with a milk', 559, '2025-03-08 07:07:05', 1, 'serving', 0),
(127, 'Jay Dash', 'Fish', 124, '2025-03-08 07:10:02', 100, 'g', 4),
(128, 'Jay Dash', 'Fish', 124, '2025-03-08 07:10:16', 100, 'g', 4);

-- --------------------------------------------------------

--
-- Table structure for table `meal_shares`
--

CREATE TABLE `meal_shares` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `calories` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `share_progress`
--

CREATE TABLE `share_progress` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'active',
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(64) NOT NULL DEFAULT '',
  `reset_token_expiry` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `reset_expires` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `created_at`, `password`, `role`, `profile_picture`, `status`, `last_login`, `reset_token`, `reset_token_expiry`, `reset_expires`) VALUES
(12, 'nutriadmin', 'nutriadmin@gmail.com', '2025-02-20 13:00:17', '$2y$10$Zgjq99O/1dsVUWMlEBcrPumwFwomCAKU55R3nwhr0BWBMbePB1wsq', 'admin', 'uploads/profile_pictures/nutriadmin_1740674284.jpg', 'active', '2025-03-09 11:13:46', '', '0000-00-00 00:00:00', 0),
(42, 'John Alfred', 'eborda.johnalfred@gmail.com', '2025-03-05 22:57:13', '$2y$10$pYzo2QRxdzmMhqZXD6UPG.3Sk0.hofpfqL5YD/WFdBEdWU8oV2Iam', 'user', NULL, 'active', '2025-03-08 19:34:09', '', '2025-03-07 07:11:55', 0),
(49, 'Jay Dash', 'j.eborda.536024@umindanao.edu.ph', '2025-03-06 17:48:47', '$2y$10$aCx4DR5Dgk6nqgIACdhQ6OhRtw/aSd2FVTbmV5SqWx5NXYz1LEGmK', 'user', 'uploads/profile_pictures/Jay Dash_1741405534.jpg', 'active', '2025-03-08 20:45:21', '', '2025-03-07 16:21:28', 0);

-- --------------------------------------------------------

--
-- Table structure for table `water_intake`
--

CREATE TABLE `water_intake` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `glasses` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `water_intake`
--

INSERT INTO `water_intake` (`id`, `username`, `glasses`, `created_at`, `updated_at`) VALUES
(1, 'Jay Dash', 8, '2025-03-08 14:46:33', '2025-03-08 20:42:34'),
(2, 'John Alfred', 0, '2025-03-08 14:55:01', '2025-03-08 14:56:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activities_ibfk_1` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`username`,`announcement_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `food_database`
--
ALTER TABLE `food_database`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meals_ibfk_1` (`username`);

--
-- Indexes for table `meal_shares`
--
ALTER TABLE `meal_shares`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `share_progress`
--
ALTER TABLE `share_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `water_intake`
--
ALTER TABLE `water_intake`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `food_database`
--
ALTER TABLE `food_database`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `meal_shares`
--
ALTER TABLE `meal_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `share_progress`
--
ALTER TABLE `share_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `water_intake`
--
ALTER TABLE `water_intake`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
