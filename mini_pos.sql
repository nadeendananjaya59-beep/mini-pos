-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2026 at 07:39 AM
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
-- Database: `mini_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Beverages'),
(3, 'Groceries'),
(2, 'Snacks'),
(4, 'Stationery');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `sku`, `price`, `quantity`, `low_stock_threshold`, `created_at`) VALUES
(1, 1, 'Mineral Water 500ml', 'BEV-001', 80.00, 120, 20, '2026-10-10 14:01:53'),
(2, 1, 'Orange Juice 1L', 'BEV-002', 450.00, 30, 10, '2026-10-10 14:01:53'),
(3, 1, 'Ginger Beer 400ml', 'BEV-003', 180.00, 8, 10, '2026-10-10 14:01:53'),
(4, 2, 'Potato Chips 100g', 'SNK-001', 250.00, 45, 15, '2026-10-10 14:01:53'),
(5, 2, 'Chocolate Bar 50g', 'SNK-002', 220.00, 60, 15, '2026-10-10 14:01:53'),
(6, 2, 'Biscuit Pack', 'SNK-003', 150.00, 4, 12, '2026-10-10 14:01:53'),
(7, 3, 'Basmati Rice 1kg', 'GRO-001', 520.00, 50, 10, '2026-10-10 14:01:53'),
(8, 3, 'Sugar 1kg', 'GRO-002', 260.00, 35, 10, '2026-10-10 14:01:53'),
(9, 3, 'Coconut Oil 750ml', 'GRO-003', 890.00, 5, 8, '2026-10-10 14:01:53'),
(10, 4, 'Exercise Book 80pg', 'STA-001', 120.00, 90, 20, '2026-10-10 14:01:53'),
(11, 4, 'Ball Point Pen (Blue)', 'STA-002', 40.00, 200, 50, '2026-10-10 14:01:53'),
(12, 4, 'Pencil HB', 'STA-003', 30.00, 15, 25, '2026-10-10 14:01:53');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `user_id`, `subtotal`, `discount`, `net_total`, `created_at`) VALUES
(1, 2, 1030.00, 30.00, 1000.00, '2026-10-07 14:01:53'),
(2, 2, 790.00, 0.00, 790.00, '2026-10-08 14:01:53'),
(3, 1, 1520.00, 120.00, 1400.00, '2026-10-09 14:01:53'),
(4, 2, 470.00, 0.00, 470.00, '2026-10-10 14:01:53'),
(5, 2, 970.00, 70.00, 900.00, '2026-10-10 14:01:53'),
(6, 2, 890.00, 890.00, 0.00, '2026-10-10 14:16:56');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `sale_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `qty`, `unit_price`, `line_total`) VALUES
(1, 1, 1, 5, 80.00, 400.00),
(2, 1, 7, 1, 520.00, 520.00),
(3, 1, 11, 2, 40.00, 80.00),
(4, 1, 12, 1, 30.00, 30.00),
(5, 2, 4, 2, 250.00, 500.00),
(6, 2, 5, 1, 220.00, 220.00),
(7, 2, 12, 1, 30.00, 30.00),
(8, 2, 11, 1, 40.00, 40.00),
(9, 3, 2, 2, 450.00, 900.00),
(10, 3, 8, 1, 260.00, 260.00),
(11, 3, 10, 3, 120.00, 360.00),
(12, 4, 5, 1, 220.00, 220.00),
(13, 4, 4, 1, 250.00, 250.00),
(14, 5, 9, 1, 890.00, 890.00),
(15, 5, 1, 1, 80.00, 80.00),
(16, 6, 9, 1, 890.00, 890.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin', '$2y$10$.jKfbKqp9pgGtXDiFEgTMOG.XpOROYAIL7O1AmUaBXGJK9TX5VSn6', 'admin', '2026-10-10 14:01:53'),
(2, 'Cashier Staff', 'staff', '$2y$10$4En0dzA84rIR3koS4dLCCOC08KStSSE0yms3mWxPEhGxf9YfcYbaO', 'staff', '2026-10-10 14:01:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_sku` (`sku`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sales_created` (`created_at`),
  ADD KEY `fk_sales_user` (`user_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_items_sale` (`sale_id`),
  ADD KEY `idx_items_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
