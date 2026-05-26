-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 26, 2026 at 09:08 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travelease`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int DEFAULT '1',
  `total_price` decimal(12,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in`, `check_out`, `guests`, `total_price`, `status`, `created_at`) VALUES
(9, 1, 5, '2026-05-24', '2026-06-01', 4, 8360000.00, 'confirmed', '2026-05-24 04:44:37');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `star_rating` tinyint NOT NULL,
  `review_score` decimal(3,1) NOT NULL,
  `review_count` int DEFAULT '0',
  `badge` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_url` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `name`, `location`, `star_rating`, `review_score`, `review_count`, `badge`, `image_url`, `description`) VALUES
(1, 'Azure Bay Resort', 'Uluwatu, Bali', 5, 8.9, 1248, 'Bestseller', 'https://lh3.googleusercontent.com/aida-public/AB6AXuA0u6VQ2wDrmZB81vIyBU_2NnFX61g6_-GbCCijcl1rf9j7zG1-_SG52lzvFIGrIA8y4sR8Pz6gF4OJ3rZDhVinqZgX73rMjtLUAiTs7AzrE9eGPO_rHdN1dfiYIN5FavaZIJ9gGENwYPj8Y56YiGMklbwFyUNB2AYHDZK4u-TcX-BdWKi3qY44i2CykD5UxwZORtyozjOYR7U1EsNU3q0_0k4rTgfoFVoGpckRwA1tn1cQdjheIOxJrZqX33IZekajUIkx6GspWLg', NULL),
(2, 'Royal Ubud Suites', 'Ubud, Bali', 4, 9.2, 856, 'Member Deal', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCY07F6voJ5PPO6OLamJLrtCShRBkrsXbnkf1Tfl1z31cNKmlVz-lgv-xhqu9eE_bpaiDHanpe7kZGUmvtc7aCyDgt702_HMpmYYF8V2u3EXe9Ychl2tPY5J_j5ZoUbrn55b9aZCDhexURux_UvTLLTUWyz3UChOWoAEnb5wRjwUhoI_3gYYgwgyN9GiT1-gqzvBX16KDwklE2fnpSKwc2uUqKFhVhvUuPbf-9cucsTolh7sSRsGUD0h4jNbS2igYl9ekczp682CMw', NULL),
(3, 'Seminyak Sands Hotel', 'Seminyak, Bali', 4, 8.5, 2102, NULL, 'https://lh3.googleusercontent.com/aida-public/AB6AXuAG2HE7z4AgtCi2VewufQPs4HFxnMGUXLNn3JY75cRfMPnfx8yUz4mALHTxel35AikhDPNaIPn9xFV32-ho9phw3zpjzGKfdkXV9VOmioPILzAkzm80jykGEbHiWwLUmYz8o6KWCqqC1wWRnyasCepf1bqsM321pZFrehVvpf-zOfUxmpeEIAF80YBJtXrwbRFpJakmf9jvvFZfsmP0yVLNht1ZqvpgvQcmSlsobQCHD4rJYk2QWVYwiYh6rYL2XKFGHPo38S7uDzE', NULL),
(4, 'Park Hyatt Jakarta', 'Menteng, Jakarta', 4, 7.8, 1000, NULL, 'https://www.awayinstyle.com/wp-content/uploads/2022/11/Park-Hyatt-Jakarta-Drone_1-scaled.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `hotel_id` int NOT NULL,
  `room_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `price_per_night` decimal(12,2) NOT NULL,
  `original_price` decimal(12,2) DEFAULT NULL,
  `stock` int DEFAULT '10',
  `facilities` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_url` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `hotel_id`, `room_type`, `price_per_night`, `original_price`, `stock`, `facilities`, `image_url`) VALUES
(1, 1, 'Deluxe Ocean View', 1850000.00, 2450000.00, 6, 'wifi,pool,breakfast', NULL),
(2, 1, 'Suite Premier', 3200000.00, 4000000.00, 5, 'wifi,pool,breakfast,spa', NULL),
(3, 2, 'Garden Villa', 1200000.00, 1500000.00, 8, 'wifi,spa,gym', NULL),
(4, 2, 'Royal Suite', 2100000.00, 2600000.00, 7, 'wifi,spa,gym,pool', NULL),
(5, 3, 'Superior Room', 950000.00, 1100000.00, 11, 'wifi,smarttv,bar', NULL),
(6, 3, 'Deluxe Pool Access', 1400000.00, 1700000.00, 6, 'wifi,pool,smarttv,bar', NULL),
(7, 4, 'King', 3599750.00, NULL, 4, 'wifi,tv,ac,minibar', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('user','admin') COLLATE utf8mb4_general_ci DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `role`) VALUES
(1, 'sky', 'sky@gmail.com', '$2y$10$zC2h/b9t9jjH0M/sDiyCuupeEvfCucGbpVGJ6gIEQ7aAAE6fit2Q6', '2026-05-24 00:01:57', 'user'),
(2, 'admin', 'admin@gmail.com', '$2y$10$/7pekoCVuFX/yO2TXGywsuW5.UcOyOd3bq/Kstzdlvw/Qgrn6viBS', '2026-05-24 03:19:08', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
