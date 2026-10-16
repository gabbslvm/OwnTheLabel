-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 03:25 PM
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
-- Database: `otl_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `postal` varchar(10) DEFAULT NULL,
  `payment_method` enum('cod','gcash','card') NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `shipping` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `image`, `is_active`, `is_featured`, `created_at`) VALUES
(1, 'Hoodie 1', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie1.jpg', 1, 1, '2026-10-16 12:39:41'),
(2, 'Hoodie 2', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie2.jpg', 1, 0, '2026-10-16 12:39:41'),
(3, 'Hoodie 3', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie3.jpg', 1, 0, '2026-10-16 12:39:41'),
(4, 'Hoodie 4', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie4.jpg', 1, 0, '2026-10-16 12:39:41'),
(5, 'Hoodie 5', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie5.jpg', 1, 0, '2026-10-16 12:39:41'),
(6, 'Hoodie 6', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie6.jpg', 1, 0, '2026-10-16 12:39:41'),
(7, 'Hoodie 7', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie7.jpg', 1, 0, '2026-10-16 12:39:41'),
(8, 'Hoodie 8', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie8.jpg', 1, 0, '2026-10-16 12:39:41'),
(9, 'Hoodie 9', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie9.jpg', 1, 0, '2026-10-16 12:39:41'),
(10, 'Hoodie 10', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie10.jpg', 1, 0, '2026-10-16 12:39:41'),
(11, 'Hoodie 11', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie11.jpg', 1, 0, '2026-10-16 12:39:41'),
(12, 'Hoodie 12', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie12.jpg', 1, 0, '2026-10-16 12:39:41'),
(13, 'Hoodie 13', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie13.jpg', 1, 0, '2026-10-16 12:39:41'),
(14, 'Hoodie 14', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie14.jpg', 1, 0, '2026-10-16 12:39:41'),
(15, 'Hoodie 15', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie15.jpg', 1, 0, '2026-10-16 12:39:41'),
(16, 'Hoodie 16', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie16.jpg', 1, 0, '2026-10-16 12:39:41'),
(17, 'Hoodie 17', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie17.jpg', 1, 0, '2026-10-16 12:39:41'),
(18, 'Hoodie 18', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie18.jpg', 1, 0, '2026-10-16 12:39:41'),
(19, 'Hoodie 19', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie19.jpg', 1, 0, '2026-10-16 12:39:41'),
(20, 'Hoodie 20', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie20.jpg', 1, 0, '2026-10-16 12:39:41'),
(21, 'Hoodie 21', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie21.jpg', 1, 0, '2026-10-16 12:39:41'),
(22, 'Hoodie 22', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie22.jpg', 1, 0, '2026-10-16 12:39:41'),
(23, 'Hoodie 23', 'Hoodies', 1299.00, 'img/products/hoodies/hoodie23.jpg', 1, 0, '2026-10-16 12:39:41'),
(24, 'Shirt 1', 'Shirts', 699.00, 'img/products/shirts/shirt1.jpg', 1, 1, '2026-10-16 12:39:41'),
(25, 'Shirt 2', 'Shirts', 699.00, 'img/products/shirts/shirt2.jpg', 1, 0, '2026-10-16 12:39:41'),
(26, 'Shirt 3', 'Shirts', 699.00, 'img/products/shirts/shirt3.jpg', 1, 0, '2026-10-16 12:39:41'),
(27, 'Shirt 4', 'Shirts', 699.00, 'img/products/shirts/shirt4.jpg', 1, 0, '2026-10-16 12:39:41'),
(28, 'Shirt 5', 'Shirts', 699.00, 'img/products/shirts/shirt5.jpg', 1, 0, '2026-10-16 12:39:41'),
(29, 'Shirt 6', 'Shirts', 699.00, 'img/products/shirts/shirt6.jpg', 1, 0, '2026-10-16 12:39:41'),
(30, 'Shirt 7', 'Shirts', 699.00, 'img/products/shirts/shirt7.jpg', 1, 0, '2026-10-16 12:39:41'),
(31, 'Shirt 8', 'Shirts', 699.00, 'img/products/shirts/shirt8.jpg', 1, 0, '2026-10-16 12:39:41'),
(32, 'Shirt 9', 'Shirts', 699.00, 'img/products/shirts/shirt9.jpg', 1, 0, '2026-10-16 12:39:41'),
(33, 'Shirt 10', 'Shirts', 699.00, 'img/products/shirts/shirt10.jpg', 1, 0, '2026-10-16 12:39:41'),
(34, 'Short 1', 'Shorts', 899.00, 'img/products/shorts/short1.jpg', 1, 1, '2026-10-16 12:39:41'),
(35, 'Short 2', 'Shorts', 899.00, 'img/products/shorts/short2.jpg', 1, 0, '2026-10-16 12:39:41'),
(36, 'Short 3', 'Shorts', 899.00, 'img/products/shorts/short3.jpg', 1, 0, '2026-10-16 12:39:41'),
(37, 'Short 4', 'Shorts', 899.00, 'img/products/shorts/short4.jpg', 1, 0, '2026-10-16 12:39:41'),
(38, 'Short 5', 'Shorts', 899.00, 'img/products/shorts/short5.jpg', 1, 0, '2026-10-16 12:39:41'),
(39, 'Short 6', 'Shorts', 899.00, 'img/products/shorts/short6.jpg', 1, 0, '2026-10-16 12:39:41'),
(40, 'Short 7', 'Shorts', 899.00, 'img/products/shorts/short7.jpg', 1, 0, '2026-10-16 12:39:41'),
(41, 'Short 8', 'Shorts', 899.00, 'img/products/shorts/short8.jpg', 1, 0, '2026-10-16 12:39:41'),
(42, 'Short 9', 'Shorts', 899.00, 'img/products/shorts/short9.jpg', 1, 0, '2026-10-16 12:39:41'),
(43, 'Barong 1', 'Barong', 1899.00, 'img/products/barong/barong1.jpg', 1, 1, '2026-10-16 12:39:41'),
(44, 'Barong 2', 'Barong', 1899.00, 'img/products/barong/barong2.jpg', 1, 0, '2026-10-16 12:39:41'),
(45, 'Barong 3', 'Barong', 1899.00, 'img/products/barong/barong3.jpg', 1, 0, '2026-10-16 12:39:41'),
(46, 'Barong 4', 'Barong', 1899.00, 'img/products/barong/barong4.jpg', 1, 0, '2026-10-16 12:39:41'),
(47, 'Barong 5', 'Barong', 1899.00, 'img/products/barong/barong5.jpg', 1, 0, '2026-10-16 12:39:41'),
(48, 'Barong 6', 'Barong', 1899.00, 'img/products/barong/barong6.jpg', 1, 0, '2026-10-16 12:39:41'),
(49, 'Barong 7', 'Barong', 1899.00, 'img/products/barong/barong7.jpg', 1, 0, '2026-10-16 12:39:41'),
(50, 'Barong 8', 'Barong', 1899.00, 'img/products/barong/barong8.jpg', 1, 0, '2026-10-16 12:39:41'),
(51, 'Barong 9', 'Barong', 1899.00, 'img/products/barong/barong9.jpg', 1, 0, '2026-10-16 12:39:41'),
(52, 'Barong 10', 'Barong', 1899.00, 'img/products/barong/barong10.jpg', 1, 0, '2026-10-16 12:39:41'),
(53, 'Barong 11', 'Barong', 1899.00, 'img/products/barong/barong11.jpg', 1, 0, '2026-10-16 12:39:41'),
(54, 'Barong 12', 'Barong', 1899.00, 'img/products/barong/barong12.jpg', 1, 0, '2026-10-16 12:39:41'),
(55, 'Barong 13', 'Barong', 1899.00, 'img/products/barong/barong13.jpg', 1, 0, '2026-10-16 12:39:41'),
(56, 'Barong 14', 'Barong', 1899.00, 'img/products/barong/barong14.jpg', 1, 0, '2026-10-16 12:39:41'),
(57, 'Polo 1', 'Polo', 999.00, 'img/products/polo/polo1.png', 1, 0, '2026-10-16 12:39:41'),
(58, 'Polo 2', 'Polo', 999.00, 'img/products/polo/polo2.png', 1, 0, '2026-10-16 12:39:41'),
(59, 'Polo 3', 'Polo', 999.00, 'img/products/polo/polo3.png', 1, 0, '2026-10-16 12:39:41'),
(60, 'Polo 4', 'Polo', 999.00, 'img/products/polo/polo4.png', 1, 0, '2026-10-16 12:39:41');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(20) NOT NULL,
  `color` varchar(30) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock`) VALUES
(1, 1, 'Small', 'Black', 10),
(2, 1, 'Medium', 'Black', 10),
(3, 1, 'Large', 'Black', 10),
(4, 2, 'Small', 'Black', 10),
(5, 2, 'Medium', 'Black', 10),
(6, 2, 'Large', 'Black', 10),
(7, 3, 'Small', 'Black', 10),
(8, 3, 'Medium', 'Black', 10),
(9, 3, 'Large', 'Black', 10),
(10, 4, 'Small', 'Black', 10),
(11, 4, 'Medium', 'Black', 10),
(12, 4, 'Large', 'Black', 10),
(13, 5, 'Small', 'Black', 10),
(14, 5, 'Medium', 'Black', 10),
(15, 5, 'Large', 'Black', 10),
(16, 6, 'Small', 'Black', 10),
(17, 6, 'Medium', 'Black', 10),
(18, 6, 'Large', 'Black', 10),
(19, 7, 'Small', 'Black', 10),
(20, 7, 'Medium', 'Black', 10),
(21, 7, 'Large', 'Black', 10),
(22, 8, 'Small', 'Black', 10),
(23, 8, 'Medium', 'Black', 10),
(24, 8, 'Large', 'Black', 10),
(25, 9, 'Small', 'Black', 10),
(26, 9, 'Medium', 'Black', 10),
(27, 9, 'Large', 'Black', 10),
(28, 10, 'Small', 'Black', 10),
(29, 10, 'Medium', 'Black', 10),
(30, 10, 'Large', 'Black', 10),
(31, 11, 'Small', 'Black', 10),
(32, 11, 'Medium', 'Black', 10),
(33, 11, 'Large', 'Black', 10),
(34, 12, 'Small', 'Black', 10),
(35, 12, 'Medium', 'Black', 10),
(36, 12, 'Large', 'Black', 10),
(37, 13, 'Small', 'Black', 10),
(38, 13, 'Medium', 'Black', 10),
(39, 13, 'Large', 'Black', 10),
(40, 14, 'Small', 'Black', 10),
(41, 14, 'Medium', 'Black', 10),
(42, 14, 'Large', 'Black', 10),
(43, 15, 'Small', 'Black', 10),
(44, 15, 'Medium', 'Black', 10),
(45, 15, 'Large', 'Black', 10),
(46, 16, 'Small', 'Black', 10),
(47, 16, 'Medium', 'Black', 10),
(48, 16, 'Large', 'Black', 10),
(49, 17, 'Small', 'Black', 10),
(50, 17, 'Medium', 'Black', 10),
(51, 17, 'Large', 'Black', 10),
(52, 18, 'Small', 'Black', 10),
(53, 18, 'Medium', 'Black', 10),
(54, 18, 'Large', 'Black', 10),
(55, 19, 'Small', 'Black', 10),
(56, 19, 'Medium', 'Black', 10),
(57, 19, 'Large', 'Black', 10),
(58, 20, 'Small', 'Black', 10),
(59, 20, 'Medium', 'Black', 10),
(60, 20, 'Large', 'Black', 10),
(61, 21, 'Small', 'Black', 10),
(62, 21, 'Medium', 'Black', 10),
(63, 21, 'Large', 'Black', 10),
(64, 22, 'Small', 'Black', 10),
(65, 22, 'Medium', 'Black', 10),
(66, 22, 'Large', 'Black', 10),
(67, 23, 'Small', 'Black', 10),
(68, 23, 'Medium', 'Black', 10),
(69, 23, 'Large', 'Black', 10),
(70, 24, 'Small', 'Black', 10),
(71, 24, 'Medium', 'Black', 10),
(72, 24, 'Large', 'Black', 10),
(73, 25, 'Small', 'Black', 10),
(74, 25, 'Medium', 'Black', 10),
(75, 25, 'Large', 'Black', 10),
(76, 26, 'Small', 'Black', 10),
(77, 26, 'Medium', 'Black', 10),
(78, 26, 'Large', 'Black', 10),
(79, 27, 'Small', 'Black', 10),
(80, 27, 'Medium', 'Black', 10),
(81, 27, 'Large', 'Black', 10),
(82, 28, 'Small', 'Black', 10),
(83, 28, 'Medium', 'Black', 10),
(84, 28, 'Large', 'Black', 10),
(85, 29, 'Small', 'Black', 10),
(86, 29, 'Medium', 'Black', 10),
(87, 29, 'Large', 'Black', 10),
(88, 30, 'Small', 'Black', 10),
(89, 30, 'Medium', 'Black', 10),
(90, 30, 'Large', 'Black', 10),
(91, 31, 'Small', 'Black', 10),
(92, 31, 'Medium', 'Black', 10),
(93, 31, 'Large', 'Black', 10),
(94, 32, 'Small', 'Black', 10),
(95, 32, 'Medium', 'Black', 10),
(96, 32, 'Large', 'Black', 10),
(97, 33, 'Small', 'Black', 10),
(98, 33, 'Medium', 'Black', 10),
(99, 33, 'Large', 'Black', 10),
(100, 34, 'Small', 'Black', 10),
(101, 34, 'Medium', 'Black', 10),
(102, 34, 'Large', 'Black', 10),
(103, 35, 'Small', 'Black', 10),
(104, 35, 'Medium', 'Black', 10),
(105, 35, 'Large', 'Black', 10),
(106, 36, 'Small', 'Black', 10),
(107, 36, 'Medium', 'Black', 10),
(108, 36, 'Large', 'Black', 10),
(109, 37, 'Small', 'Black', 10),
(110, 37, 'Medium', 'Black', 10),
(111, 37, 'Large', 'Black', 10),
(112, 38, 'Small', 'Black', 10),
(113, 38, 'Medium', 'Black', 10),
(114, 38, 'Large', 'Black', 10),
(115, 39, 'Small', 'Black', 10),
(116, 39, 'Medium', 'Black', 10),
(117, 39, 'Large', 'Black', 10),
(118, 40, 'Small', 'Black', 10),
(119, 40, 'Medium', 'Black', 10),
(120, 40, 'Large', 'Black', 10),
(121, 41, 'Small', 'Black', 10),
(122, 41, 'Medium', 'Black', 10),
(123, 41, 'Large', 'Black', 10),
(124, 42, 'Small', 'Black', 10),
(125, 42, 'Medium', 'Black', 10),
(126, 42, 'Large', 'Black', 10),
(127, 43, 'Small', 'Black', 10),
(128, 43, 'Medium', 'Black', 10),
(129, 43, 'Large', 'Black', 10),
(130, 44, 'Small', 'Black', 10),
(131, 44, 'Medium', 'Black', 10),
(132, 44, 'Large', 'Black', 10),
(133, 45, 'Small', 'Black', 10),
(134, 45, 'Medium', 'Black', 10),
(135, 45, 'Large', 'Black', 10),
(136, 46, 'Small', 'Black', 10),
(137, 46, 'Medium', 'Black', 10),
(138, 46, 'Large', 'Black', 10),
(139, 47, 'Small', 'Black', 10),
(140, 47, 'Medium', 'Black', 10),
(141, 47, 'Large', 'Black', 10),
(142, 48, 'Small', 'Black', 10),
(143, 48, 'Medium', 'Black', 10),
(144, 48, 'Large', 'Black', 10),
(145, 49, 'Small', 'Black', 10),
(146, 49, 'Medium', 'Black', 10),
(147, 49, 'Large', 'Black', 10),
(148, 50, 'Small', 'Black', 10),
(149, 50, 'Medium', 'Black', 10),
(150, 50, 'Large', 'Black', 10),
(151, 51, 'Small', 'Black', 10),
(152, 51, 'Medium', 'Black', 10),
(153, 51, 'Large', 'Black', 10),
(154, 52, 'Small', 'Black', 10),
(155, 52, 'Medium', 'Black', 10),
(156, 52, 'Large', 'Black', 10),
(157, 53, 'Small', 'Black', 10),
(158, 53, 'Medium', 'Black', 10),
(159, 53, 'Large', 'Black', 10),
(160, 54, 'Small', 'Black', 10),
(161, 54, 'Medium', 'Black', 10),
(162, 54, 'Large', 'Black', 10),
(163, 55, 'Small', 'Black', 10),
(164, 55, 'Medium', 'Black', 10),
(165, 55, 'Large', 'Black', 10),
(166, 56, 'Small', 'Black', 10),
(167, 56, 'Medium', 'Black', 10),
(168, 56, 'Large', 'Black', 10),
(169, 57, 'Small', 'Black', 10),
(170, 57, 'Medium', 'Black', 10),
(171, 57, 'Large', 'Black', 10),
(172, 58, 'Small', 'Black', 10),
(173, 58, 'Medium', 'Black', 10),
(174, 58, 'Large', 'Black', 10),
(175, 59, 'Small', 'Black', 10),
(176, 59, 'Medium', 'Black', 10),
(177, 59, 'Large', 'Black', 10),
(178, 60, 'Small', 'Black', 10),
(179, 60, 'Medium', 'Black', 10),
(180, 60, 'Large', 'Black', 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Admin', 'admin@otl.com', '$2y$10$.rNNGTyjSqdDDUMtRxDhm.945bUsHQwqmBU1vdJvjDGPirqNaWLqq', 'admin', 'active'),
(2, 'Juan Dela Cruz', 'customer@otl.com', '$2y$10$4WFPxoEi2edqGF3SyfOO8uzNYWyJkPa25OilI3Sf//yoPnQbDLAoC', 'customer', 'active'),
(7, 'Jireh Salvame', 'jgsalvame@gmail.com', '$2y$10$7zFjOY9N8Q1uLS3lrI7shewzOAOzbA8/l.nRWEw0TVeBb2/mMl1DK', 'customer', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
