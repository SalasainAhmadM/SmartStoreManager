-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2024 at 02:30 AM
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
(3, 'San Jose Cawa Cawa 2', 2, '2024-11-26 22:46:38', '2024-11-30 21:40:55', NULL),
(4, 'Pilar Street', 3, '2024-11-26 22:47:55', '2024-11-30 21:33:59', NULL),
(8, 'Governor Lim Avenue', 2, '2024-11-28 20:43:21', '2024-11-30 23:25:31', 2),
(9, 'Pilar Street', 2, '2024-11-28 20:49:03', '2024-11-29 09:52:36', NULL),
(10, 'Pilar Street', 5, '2024-11-30 23:53:11', NULL, NULL),
(11, 'Pershing', 5, '2024-11-30 23:56:29', NULL, NULL),
(12, 'San Jose Cawa Cawa', 6, '2024-11-30 23:59:46', NULL, 3);

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

INSERT INTO `business` (`id`, `name`, `description`, `asset`, `employee_count`, `expense_type`, `expenses`, `image`, `created_at`, `updated_at`, `owner_id`, `manager_id`) VALUES
(2, 'Printing Shop                                                                                                                                                                                                                                                  ', 'Print and Sublimation', '20000', '20', NULL, NULL, NULL, '2024-11-25 23:07:18', '2024-11-30 21:30:25', 1, NULL),
(3, 'Monkey Business', 'Print and Sublimation', '11', '11', NULL, NULL, NULL, '2024-11-25 23:13:19', '2024-11-25 23:17:05', 1, NULL),
(4, 'Shoes                                           ', 'Print and Sublimation', '111', '11', NULL, NULL, NULL, '2024-11-25 23:59:48', '2024-11-26 00:59:06', 1, NULL),
(5, 'Fast Food', 'Chicken Jjoy with Ricd', '2000000', '20', NULL, NULL, NULL, '2024-11-26 01:26:00', NULL, 1, NULL),
(6, 'Freelance', 'Web and App Commissions', '1222', '2', NULL, NULL, NULL, '2024-11-30 21:45:34', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category` ENUM('business', 'branch') NOT NULL,
  `category_id` int(11) NOT NULL,
  `expense_type` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `owner_id` int(11) NOT NULL
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
(2, 'managertest1@gmail.com', 'testmanager', 'Akagi', 'D', 'Haruko', '2_1732810299.jpg', 'Male', '22', '2000-06-23', 'Di Makita Street 123', '666665', '2024-11-28 00:14:55', 1, '$2y$10$xRzWm7zxYAgT2mB7mdTXmOYjJnFwCs4zH5QkCZN2sdte/KhPQo9MO'),
(3, 'managertest2@gmail.com', 'testmanager', 'Ryota', '', 'Ayako', '3_1732810784.jpg', '', '', NULL, 'Di Makita Street', '1234511', '2024-11-28 00:38:34', 1, '$2y$10$9.tOr4PtuOK9jhGVaW45T.1aEqBoyzwc7fWZwbCiWmGgR/0ukptp2');

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
(9, 1, 3, 'Hey', '2024-11-28 15:21:17', 'owner', 0);

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
(2, 'testusername2', 'binimaloi3522@gmail.com', 'Garp', 'D', 'Monkey', 'Female', '22', '1993-02-28', 'Grandline', '122323', '2024-11-25 22:51:33', '2_1732811018.jpg', '$2y$10$xRzWm7zxYAgT2mB7mdTXmOYjJnFwCs4zH5QkCZN2sdte/KhPQo9MO', 0);

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
(1, 'Basketball Jersey', 'For Basketball2', '399', 'Jersey', '2024-11-26 01:33:26', '2024-11-28 21:01:16', 2),
(2, 'Secret', 'A secret product', '3000', 'Secret Product', '2024-11-26 09:00:22', '2024-11-26 09:03:51', 3),
(3, 'T-shirt', 'Teessss', '499', 'Shirt', '2024-11-26 09:08:58', '2024-11-26 09:12:43', 2),
(4, 'Hoodie', 'Long Sleeve Hoodie', '599', 'Sublimation', '2024-11-26 09:13:33', '2024-11-26 09:13:43', 2);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `total_sales` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity`
--
ALTER TABLE `activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `business`
--
ALTER TABLE `business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
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
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
