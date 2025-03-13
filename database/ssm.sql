-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 05:45 PM
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
-- Table structure for table `activity`
--

CREATE TABLE `activity` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(255) NOT NULL,
  `user` varchar(15) DEFAULT NULL,
  `user_id` int(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity`
--

INSERT INTO `activity` (`id`, `message`, `created_at`, `status`, `user`, `user_id`) VALUES
(1, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2024-12-04 17:30:09', 'Completed', 'owner', 1),
(2, 'Expense Added to Business: Shoes                                            for Fixed Expense amounting to 123', '2024-12-04 17:38:49', 'Completed', 'owner', 1),
(3, 'Sale Added at Business: Monkey Business - Product: Secret, Quantity: 2, Total Sales: 6000', '2024-12-04 17:50:01', 'Completed', 'owner', 1),
(4, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2024-12-05 11:48:51', 'Completed', 'owner', 1),
(5, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2024-12-06 15:50:09', 'Completed', 'owner', 1),
(6, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2024-12-06 15:50:20', 'Completed', 'owner', 1),
(7, 'Expense Added to Business: Shoes                                            for Fixed Expense amounting to 200', '2024-12-06 15:51:17', 'Completed', 'owner', 1),
(8, 'Expense Added into Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                       ', '2024-12-06 15:51:44', 'Completed', 'owner', 1),
(9, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 44', '2024-12-06 18:39:46', 'Completed', 'owner', 1),
(10, 'Expense Added to Business:  for Food Expense amounting to 44', '2024-12-06 18:43:04', 'Completed', 'owner', 1),
(11, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 444', '2024-12-06 18:45:27', 'Completed', 'owner', 1),
(12, 'Expense Added to Business: Freelance for Fixed Expense amounting to 343234', '2024-12-06 18:46:12', 'Completed', 'owner', 1),
(13, 'Expense Added into Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                       ', '2024-12-06 18:46:32', 'Completed', 'owner', 1),
(14, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2024-12-06 18:53:20', 'Completed', 'owner', 1),
(15, 'Sale Added at Business: Monkey Business - Product: Secret, Quantity: 2, Total Sales: 6000', '2024-12-06 18:53:44', 'Completed', 'owner', 1),
(16, 'Expense Added into Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                       ', '2024-12-06 18:55:42', 'Completed', 'owner', 1),
(18, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2024-12-06 18:58:36', 'Completed', 'owner', 1),
(19, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2024-12-06 18:58:47', 'Completed', 'owner', 1),
(20, 'Sale Added at Business: Monkey Business - Product: Secret, Quantity: 2, Total Sales: 6000', '2024-12-07 01:42:44', 'Completed', 'owner', 1),
(21, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 100', '2024-12-07 01:50:59', 'Completed', 'owner', 1),
(22, 'Expense Added to Business: Shoes                                            for Operating Expense amounting to 200', '2024-12-07 01:51:16', 'Completed', 'owner', 1),
(23, 'Expense Added into Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                       ', '2024-12-07 01:51:37', 'Completed', 'owner', 1),
(24, 'Expense Added into Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                       ', '2024-12-07 01:52:22', 'Completed', 'owner', 1),
(25, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 100', '2024-12-07 01:59:17', 'Completed', 'owner', 1),
(26, 'Sale Added at Business: Shoes                                            - Product: DRose Shoes, Quantity: 22, Total Sales: 8778', '2024-12-07 03:19:19', 'Completed', 'owner', 1),
(27, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-02-05 13:48:33', 'Completed', 'owner', 1),
(28, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-02-05 14:13:51', 'Completed', 'owner', 1),
(29, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 200', '2025-02-05 14:15:30', 'Completed', 'owner', 1),
(30, 'Expense Added into Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                       ', '2025-02-05 14:15:56', 'Completed', 'owner', 1),
(31, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 22', '2025-02-11 13:19:51', 'Completed', 'owner', 1),
(32, 'Expense Added to Business: Monkey Business test for Food Expense amounting to 22', '2025-02-11 13:26:07', 'Completed', 'owner', 1),
(33, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2025-02-11 13:28:57', 'Completed', 'owner', 1),
(34, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2025-02-11 13:29:48', 'Completed', 'owner', 1),
(35, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2025-02-11 13:30:07', 'Completed', 'owner', 1),
(36, 'Sale Added at Business: Monkey King - Product: Secret, Quantity: 2, Total Sales: 6000', '2025-02-11 13:31:07', 'Completed', 'owner', 1),
(37, 'New Manager Added: Shoyo D Hinata', '2025-02-11 06:32:36', 'Completed', 'owner', 1),
(38, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-02-13 11:07:33', 'Completed', 'owner', 1),
(39, 'Expense Added into Branch: Pershing (Business: Fast Food)', '2025-02-14 09:10:42', 'Completed', 'owner', 1),
(40, 'Sale Added at Branch: Pilar Street (Business: Shoes                                           ) - Product: DRose Shoes, Quantity: 2, Total Sales: 798', '2025-02-15 12:59:02', 'Completed', 'owner', 1),
(41, 'Sale Added at Branch: Pilar Street (Business: Shoes                                           ) - Product: DRose Shoes, Quantity: 1, Total Sales: 399', '2025-02-15 13:50:05', 'Completed', 'owner', 1),
(42, 'Sale Added at Branch: Pilar Street (Business: Shoes                                           ) - Product: DRose Shoes, Quantity: 2, Total Sales: 798', '2025-02-15 13:54:58', 'Completed', 'owner', 1),
(43, 'New Manager Added: Ryota D Ayako', '2025-02-25 04:24:07', 'Completed', 'owner', 1),
(44, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2025-02-25 12:37:19', 'Completed', 'owner', 1),
(45, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-02-25 12:37:33', 'Completed', 'owner', 1),
(46, 'Sale Added at Business: Shoes                                            - Product: DRose Shoes, Quantity: 2, Total Sales: 798', '2025-02-25 12:39:25', 'Completed', 'owner', 1),
(47, 'Sale Added at Branch: Pilar Street (Business: Shoes                                           ) - Product: DRose Shoes, Quantity: 2, Total Sales: 798', '2025-02-25 12:39:33', 'Completed', 'owner', 1),
(48, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-03-06 05:08:05', 'Completed', 'owner', 1),
(49, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-03-06 08:03:53', 'Completed', 'owner', 1),
(50, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-03-06 20:27:05', 'Completed', 'owner', 1),
(51, 'Sale Added at Business: Printing Shop                                                                                                                                                                                                                          ', '2025-03-06 20:28:33', 'Completed', 'owner', 1),
(52, 'Sale Added at Branch: WMSU (Business: Printing Shop                                                                                                                                                                                                            ', '2025-03-06 20:28:48', 'Completed', 'owner', 1),
(53, 'Expense Added to Business: Shoes                                            for Food Expense amounting to 20000', '2025-03-06 23:21:52', 'Completed', 'owner', 1),
(54, 'Expense Added into Branch: Pilar Street (Business: Fast Food)', '2025-03-06 23:22:33', 'Completed', 'owner', 1),
(55, 'Sale Added at Business: Fast Food - Product: Y1, Quantity: 20, Total Sales: 40', '2025-03-07 03:25:11', 'Completed', 'owner', 1),
(56, 'Sale Added at Branch: Pershing (Business: Fast Food) - Product: Y1, Quantity: 12, Total Sales: 24', '2025-03-07 03:25:43', 'Completed', 'owner', 1);

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `business_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`id`, `location`, `business_id`, `created_at`, `updated_at`, `manager_id`) VALUES
(2, 'Pilar Street', 4, '2024-11-26 22:28:16', '2024-11-30 21:34:04', NULL),
(10, 'Pilar Street', 5, '2024-11-30 23:53:11', '2025-02-11 21:33:06', NULL),
(11, 'Pershing', 5, '2024-11-30 23:56:29', NULL, NULL),
(13, 'WMSU', 2, '2024-12-03 23:05:50', '2025-02-26 00:34:35', 5),
(14, 'test branch12', 14, '2025-02-11 21:16:45', '2025-02-11 21:22:29', NULL),
(15, 'test 2', 14, '2025-02-11 21:18:57', NULL, NULL),
(16, 'san jose', 23, '2025-03-07 02:04:05', NULL, NULL),
(17, 'cawa cawa', 23, '2025-03-07 02:04:05', NULL, NULL),
(18, 'kasanyangan', 23, '2025-03-07 02:04:05', NULL, NULL);

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `business`
--

INSERT INTO `business` (`id`, `name`, `description`, `asset`, `employee_count`, `created_at`, `updated_at`, `owner_id`, `manager_id`) VALUES
(2, 'Printing Shop                                                                                                                                                                                                                                                  ', 'Print and Sublimation', '20000', '20', '2024-11-25 23:07:18', '2025-02-11 21:21:16', 1, 4),
(3, 'Monkey King', 'Print and Sublimation', '11', '11', '2024-11-25 23:13:19', '2024-11-25 23:17:05', 1, NULL),
(4, 'Shoes                                           ', 'Print and Sublimation', '111', '11', '2024-11-25 23:59:48', '2024-11-26 00:59:06', 1, NULL),
(5, 'Fast Food', 'Chicken Jjoy with Ricd', '2000000', '20', '2024-11-26 01:26:00', NULL, 1, NULL),
(6, 'Freelance', 'Web and App Commissions', '1222', '2', '2024-11-30 21:45:34', NULL, 1, NULL),
(12, 'test promt                                                ', 'Print and Sublimation', '121412', '22', '2024-12-02 10:02:18', '2025-02-14 22:53:31', 1, NULL),
(14, 'Monkey Business test', '123', '111', '11', '2025-02-11 21:16:19', NULL, 1, NULL),
(23, 'test', '123', '12', '1', '2025-03-07 02:04:05', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_type` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `owner_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `category` enum('business','branch') NOT NULL,
  `month` int(2) NOT NULL,
  `user_role` enum('Manager','Owner') NOT NULL DEFAULT 'Owner'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_type`, `amount`, `description`, `created_at`, `owner_id`, `category_id`, `category`, `month`, `user_role`) VALUES
(6, 'Food Expense', '20000', 'test', '2025-03-07 07:21:52', 1, 2, 'business', 3, 'Owner'),
(7, 'Food Expense', '199', 'Chicken Joy', '2025-03-07 07:22:33', 1, 10, 'branch', 3, 'Owner'),
(10, '6 - Food Expense', '1111', '111', '2025-03-07 11:10:11', 1, 12, 'business', 3, 'Owner'),
(11, '5 - Capital Expense', '2222', '222', '2025-03-07 11:10:11', 1, 11, 'branch', 3, 'Owner');

-- --------------------------------------------------------

--
-- Table structure for table `expense_type`
--

CREATE TABLE `expense_type` (
  `id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `is_custom` tinyint(4) NOT NULL,
  `created_at` datetime NOT NULL,
  `owner_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `expense_type`
--

INSERT INTO `expense_type` (`id`, `type_name`, `is_custom`, `created_at`, `owner_id`) VALUES
(1, 'Fixed Expense', 0, '0000-00-00 00:00:00', NULL),
(2, 'Variable Expense', 0, '0000-00-00 00:00:00', NULL),
(3, 'Operating Expense', 0, '0000-00-00 00:00:00', NULL),
(4, 'Non-operating Expense', 0, '0000-00-00 00:00:00', NULL),
(5, 'Capital Expense', 0, '0000-00-00 00:00:00', NULL),
(6, 'Food Expense', 1, '2024-12-03 22:59:27', 1);

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
  `birthday` date DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `owner_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager`
--

INSERT INTO `manager` (`id`, `email`, `user_name`, `first_name`, `middle_name`, `last_name`, `image`, `gender`, `age`, `birthday`, `address`, `contact_number`, `created_at`, `owner_id`, `password`) VALUES
(4, 'managertest3@gmail.com', 'testmanager3', 'Shoyo', 'D', 'Hinata', '', '', '', NULL, 'Di Makita Street', '12345', '2025-02-11 21:32:36', 1, '$2y$10$qiLBoq6qPZiRtbejpM2HK./C.OArpW9piVYcGdBozQeSrhZ9uuRsq'),
(5, 'managertest@gmail.com', 'testmanager', 'Ryota', 'D', 'Ayako', '', '', '', NULL, 'Di Makita Street', '12345', '2025-02-25 19:24:07', 1, '$2y$10$gfVZtx3hhpg95O/UNdUuSeMr1sYvBuLpRauBNqD1FX2nHFcpfLHYy'),
(6, 'managertest2@gmail.com', 'testmanager2', 'Ryu', 'D', 'Ayako', '', '', '', NULL, 'Di Makita Street', '12345', '2025-02-25 19:24:07', 1, '$2y$10$gfVZtx3hhpg95O/UNdUuSeMr1sYvBuLpRauBNqD1FX2nHFcpfLHYy');

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
(6, 2, 1, 'this is not fair', '2024-11-27 17:46:00', 'manager', 1),
(7, 2, 1, 'yow', '2024-11-28 15:13:33', 'manager', 1),
(8, 2, 1, 'yooooow', '2024-11-28 15:13:37', 'manager', 1),
(9, 1, 3, 'Hey', '2024-11-28 15:21:17', 'owner', 0),
(10, 1, 2, 'Hey', '2025-02-05 13:28:24', 'owner', 0);

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
  `birthday` date DEFAULT NULL,
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

INSERT INTO `owner` (`id`, `user_name`, `email`, `first_name`, `middle_name`, `last_name`, `gender`, `age`, `birthday`, `address`, `contact_number`, `created_at`, `image`, `password`, `is_new_owner`) VALUES
(1, 'testusername', 'binimaloi352@gmail.com', 'Sengokuw', 'D', 'Business', 'Female', '22', '2024-11-03', 'Earth', '12344', '2024-11-25 22:51:33', '1_1732805415.jpg', '$2y$10$xRzWm7zxYAgT2mB7mdTXmOYjJnFwCs4zH5QkCZN2sdte/KhPQo9MO', 0),
(2, 'testusername2', 'binimaloi3522@gmail.com', 'Garp', 'D', 'Monkey', 'Female', '22', '1993-02-28', 'Grandline', '122323', '2024-11-25 22:51:33', '2_1732811018.jpg', '$2y$10$xRzWm7zxYAgT2mB7mdTXmOYjJnFwCs4zH5QkCZN2sdte/KhPQo9MO', 0),
(3, 'testusername2', 'binimal11oi3522@gmail.com', 'Garp', 'D', 'Monkey', 'Female', '22', '1993-02-28', 'Grandline', '122323', '2024-11-25 22:51:33', '2_1732811018.jpg', '$2y$10$xRzWm7zxYAgT2mB7mdTXmOYjJnFwCs4zH5QkCZN2sdte/KhPQo9MO', 0);

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
  `business_id` int(11) NOT NULL,
  `size` varchar(250) DEFAULT NULL,
  `status` enum('Available','Unavailable') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `type`, `created_at`, `updated_at`, `business_id`, `size`, `status`) VALUES
(1, 'Basketball Jersey', 'For Basketball', '400', 'Jersey', '2024-11-26 01:33:26', '2025-03-06 12:33:50', 2, 'XL', 'Available'),
(2, 'Secret', 'A secret product', '3000', 'Secret Product', '2024-11-26 09:00:22', '2024-11-26 09:03:51', 3, 'Secret size', 'Available'),
(3, 'T-shirt', 'Teessss', '499', 'Shirt', '2024-11-26 09:08:58', '2024-11-26 09:12:43', 2, 'M', 'Available'),
(4, 'Hoodie', 'Long Sleeve Hoodie', '599', 'Sublimation', '2024-11-26 09:13:33', '2024-11-26 09:13:43', 2, 'L', 'Available'),
(5, 'DRose Shoes', 'NBA', '399', 'Shoes', '2024-12-02 00:50:05', NULL, 4, '9.5', 'Available'),
(6, 'Site', 'Web App', '599', 'Commission', '2024-11-26 09:13:33', '2024-11-26 09:13:43', 6, '99 bytes', 'Available'),
(8, 'APP', 'Web App', '599', 'Commission', '2024-11-26 09:13:33', '2024-11-26 09:13:43', 6, '2 gb', 'Available'),
(9, 'Secret test2', '123', '22', 'Jersey', '2025-02-11 21:19:22', '2025-02-11 21:22:41', 14, 'XXS', 'Available'),
(26, 'Basketball Jersey', 'For Sample', '200', 'Coat', '2025-03-03 08:06:40', NULL, 2, 'Big', 'Available'),
(27, 'Basketball Jersey 23', 'New Test', '340', 'Jersey', '2025-03-04 15:30:56', NULL, 2, 'XXL', 'Available'),
(28, 'coke', 'thirsty', '24', 'drink', '2025-03-07 02:04:05', NULL, 23, NULL, 'Available'),
(29, 'Y1', 'Regular Berjer', '2', 'Food', '2025-03-07 04:24:51', NULL, 5, 'Regular', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `product_availability`
--

CREATE TABLE `product_availability` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `business_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `status` enum('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_availability`
--

INSERT INTO `product_availability` (`id`, `product_id`, `business_id`, `branch_id`, `status`, `note`, `created_at`) VALUES
(21, 5, 4, 2, 'Available', NULL, '2025-03-05 11:25:28'),
(67, 1, 2, NULL, 'Unavailable', NULL, '2025-03-06 13:08:36'),
(68, 3, 2, NULL, 'Available', NULL, '2025-03-06 13:08:36'),
(69, 4, 2, NULL, 'Available', NULL, '2025-03-06 13:08:36'),
(70, 26, 2, NULL, 'Available', NULL, '2025-03-06 13:08:36'),
(71, 27, 2, NULL, 'Available', NULL, '2025-03-06 13:08:36'),
(72, 1, 2, 13, 'Unavailable', NULL, '2025-03-06 15:48:39'),
(73, 3, 2, 13, 'Unavailable', NULL, '2025-03-06 15:48:39'),
(74, 4, 2, 13, 'Available', NULL, '2025-03-06 15:48:39'),
(75, 26, 2, 13, 'Available', NULL, '2025-03-06 15:48:39'),
(76, 27, 2, 13, 'Unavailable', NULL, '2025-03-06 15:48:39'),
(78, 29, 5, 10, 'Available', NULL, '2025-03-07 04:24:51'),
(79, 29, 5, 11, 'Available', NULL, '2025-03-07 04:24:51'),
(80, 29, 5, NULL, 'Unavailable', NULL, '2025-03-07 11:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `total_sales` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `user_role` enum('Manager','Owner') NOT NULL DEFAULT 'Owner',
  `type` enum('business','branch') NOT NULL DEFAULT 'business'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `quantity`, `total_sales`, `date`, `created_at`, `product_id`, `branch_id`, `user_role`, `type`) VALUES
(1, '2', '1198', '2025-03-07', '2025-03-07 04:27:05', 4, 13, 'Owner', 'branch'),
(2, '2', '1198', '2025-03-07', '2025-03-07 04:28:33', 4, 0, 'Owner', 'business'),
(3, '1', '200', '2025-03-07', '2025-03-07 04:28:48', 26, 13, 'Owner', 'branch'),
(4, '1', '599', '2025-03-07', '2025-03-07 06:47:20', 4, 13, 'Owner', 'branch'),
(5, '20', '11980', '2025-03-06', '2025-03-07 06:47:20', 4, 13, 'Owner', 'branch'),
(7, '10', '240', '2025-03-07', '2025-03-07 07:01:44', 28, 0, 'Owner', 'business'),
(8, '10', '4000', '2025-03-07', '2025-03-07 07:02:15', 1, 13, 'Owner', 'branch'),
(9, '30', '14970', '2025-03-07', '2025-03-07 07:02:15', 3, 0, 'Owner', 'business'),
(10, '50', '29950', '2025-03-07', '2025-03-07 07:02:15', 4, 0, 'Owner', 'business'),
(13, '10', '4000', '2025-03-07', '2025-03-07 07:04:28', 1, 13, 'Owner', 'branch'),
(14, '30', '14970', '2025-03-07', '2025-03-07 07:04:28', 3, 0, 'Owner', 'business'),
(15, '50', '29950', '2025-03-07', '2025-03-07 07:04:28', 4, 0, 'Owner', 'business'),
(16, '20', '40', '2025-03-07', '2025-03-07 11:25:11', 29, 0, 'Owner', 'business'),
(17, '12', '24', '2025-03-07', '2025-03-07 11:25:43', 29, 11, 'Owner', 'branch');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity`
--
ALTER TABLE `activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_branch_business` (`business_id`),
  ADD KEY `fk_branch_manager` (`manager_id`);

--
-- Indexes for table `business`
--
ALTER TABLE `business`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_business_owner` (`owner_id`),
  ADD KEY `fk_business_manager` (`manager_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expenses_owner` (`owner_id`);

--
-- Indexes for table `expense_type`
--
ALTER TABLE `expense_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expense_type_owner` (`owner_id`);

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
-- Indexes for table `product_availability`
--
ALTER TABLE `product_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_product` (`product_id`),
  ADD KEY `fk_sales_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity`
--
ALTER TABLE `activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `business`
--
ALTER TABLE `business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `expense_type`
--
ALTER TABLE `expense_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `manager`
--
ALTER TABLE `manager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `product_availability`
--
ALTER TABLE `product_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch`
--
ALTER TABLE `branch`
  ADD CONSTRAINT `fk_branch_business` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_branch_manager` FOREIGN KEY (`manager_id`) REFERENCES `manager` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `business`
--
ALTER TABLE `business`
  ADD CONSTRAINT `fk_business_manager` FOREIGN KEY (`manager_id`) REFERENCES `manager` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_business_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `expense_type`
--
ALTER TABLE `expense_type`
  ADD CONSTRAINT `fk_expense_type_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

--
-- Constraints for table `product_availability`
--
ALTER TABLE `product_availability`
  ADD CONSTRAINT `product_availability_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_availability_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `business` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_availability_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
