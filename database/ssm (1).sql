-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2024 at 01:35 PM
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
-- Database: `ssm`
--

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `business_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`id`, `location`, `business_id`, `created_at`, `updated_at`) VALUES
(2, 'Pilar Street', 4, '2024-11-26 22:28:16', NULL),
(3, 'San Jose Cawa Cawa 2', 2, '2024-11-26 22:46:38', '2024-11-27 23:42:13'),
(4, 'Pilar Street', 3, '2024-11-26 22:47:55', NULL),
(5, 'Governor Lim Avenue', 2, '2024-11-26 23:00:25', NULL),
(6, 'Governor Lim Avenue', 2, '2024-11-26 23:19:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `business`
--

CREATE TABLE `business` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `asset` varchar(255) NOT NULL,
  `employee_count` varchar(255) NOT NULL,
  `expense_type` varchar(255) DEFAULT NULL,
  `expenses` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `business`
--

INSERT INTO `business` (`id`, `name`, `description`, `asset`, `employee_count`, `expense_type`, `expenses`, `image`, `created_at`, `updated_at`, `owner_id`, `manager_id`) VALUES
(2, 'Printing Shop                                                                                                                                                                                                                  ', 'Print and Sublimation', '20000', '20', NULL, NULL, NULL, '2024-11-25 23:07:18', '2024-11-28 00:06:57', 1, NULL),
(3, 'Monkey Business', 'Print and Sublimation', '11', '11', NULL, NULL, NULL, '2024-11-25 23:13:19', '2024-11-25 23:17:05', 1, NULL),
(4, 'Shoes                                           ', 'Print and Sublimation', '111', '11', NULL, NULL, NULL, '2024-11-25 23:59:48', '2024-11-26 00:59:06', 1, NULL),
(5, 'Fast Food', 'Chicken Jjoy with Ricd', '2000000', '20', NULL, NULL, NULL, '2024-11-26 01:26:00', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `business_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `owner_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager`
--

INSERT INTO `manager` (`id`, `email`, `user_name`, `first_name`, `middle_name`, `last_name`, `image`, `gender`, `age`, `address`, `contact_number`, `created_at`, `owner_id`, `password`) VALUES
(2, 'managertest@gmail.com', 'testmanager', 'Akagi', '', 'Haruko', '', '', '', 'Di Makita Street', '66666', '2024-11-28 00:14:55', 1, '$2y$10$pUVltccp/J0bvIxMP5G4l.1GTMDKjkU.67ribI91U.oqlA9XSfd.i'),
(3, 'managertest1@gmail.com', 'testmanager', 'Ryota', '', 'Ayako', '', '', '', 'Di Makita Street', '1234511', '2024-11-28 00:38:34', 1, '$2y$10$9.tOr4PtuOK9jhGVaW45T.1aEqBoyzwc7fWZwbCiWmGgR/0ukptp2');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sender_type` enum('owner','manager') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `timestamp`, `sender_type`, `is_read`) VALUES
(1, 1, 2, '1', '2024-11-27 17:08:12', 'owner', 0),
(2, 1, 3, 'yow', '2024-11-27 17:08:22', 'owner', 0),
(3, 2, 1, '2', '2024-11-27 17:42:23', 'manager', 1),
(4, 2, 1, 'Welcome to the website. If you\'re here, you\'re likely looking to find random words. Random Word Generator is the perfect tool to help you do this. While this tool isn\'t a word creator, it is a word generator that will generate random words for a variety of activities or uses. Even better, it allows you to adjust the parameters of the random words to best fit your needs.', '2024-11-27 17:45:55', 'manager', 1),
(5, 2, 1, 'Welcome to the website. If you\'re here, you\'re likely looking to find random words. Random Word Generator is the perfect tool to help you do this. While this tool isn\'t a word creator, it is a word generator that will generate random words for a variety of activities or uses. Even better, it allows you to adjust the parameters of the random words to best fit your needs.', '2024-11-27 17:46:00', 'manager', 1),
(6, 2, 1, 'this is not fair', '2024-11-27 17:46:00', 'manager', 1);

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `birthday` DATE NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_new_owner` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `owner`
--

INSERT INTO `owner` (`id`, `user_name`, `email`, `first_name`, `middle_name`, `last_name`, `gender`, `age`, `address`, `contact_number`, `created_at`, `image`, `password`, `is_new_owner`) VALUES
(1, 'testusername', 'binimaloi352@gmail.com', 'Sengoku', 'D', 'Business', '', '', '', '', '2024-11-25 22:51:33', '', '$2y$10$xRzWm7zxYAgT2mB7mdTXmOYjJnFwCs4zH5QkCZN2sdte/KhPQo9MO', 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `business_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `type`, `created_at`, `updated_at`, `business_id`) VALUES
(1, 'Basketball Jersey', 'For Basketball2', '399', 'Jersey', '2024-11-26 01:33:26', '2024-11-26 23:22:02', 2),
(2, 'Secret', 'A secret product', '3000', 'Secret Product', '2024-11-26 09:00:22', '2024-11-26 09:03:51', 3),
(3, 'T-shirt', 'Teessss', '499', 'Shirt', '2024-11-26 09:08:58', '2024-11-26 09:12:43', 2),
(4, 'Hoodie', 'Long Sleeve Hoodie', '599', 'Sublimation', '2024-11-26 09:13:33', '2024-11-26 09:13:43', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_branch_business` (`business_id`);

--
-- Indexes for table `business`
--
ALTER TABLE `business`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_business_owner` (`owner_id`),
  ADD KEY `fk_business_manager` (`manager_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_employee_business` (`business_id`);

--
-- Indexes for table `manager`
--
ALTER TABLE `manager`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_manager_owner` (`owner_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token`),
  ADD UNIQUE KEY `Email` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_business` (`business_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `business`
--
ALTER TABLE `business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manager`
--
ALTER TABLE `manager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch`
--
ALTER TABLE `branch`
  ADD CONSTRAINT `fk_branch_business` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `business`
--
ALTER TABLE `business`
  ADD CONSTRAINT `fk_business_manager` FOREIGN KEY (`manager_id`) REFERENCES `manager` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_business_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_business` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `manager`
--
ALTER TABLE `manager`
  ADD CONSTRAINT `fk_manager_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_business` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
