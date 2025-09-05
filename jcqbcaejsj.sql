-- Adminer 4.7.8 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `purchases`;
CREATE TABLE `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `package_type` enum('hotspot','user_manager') DEFAULT 'hotspot',
  `purchased_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdraw','purchase','transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `role` enum('customer','vendor','admin') DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `password`, `otp`, `verified`, `role`, `created_at`) VALUES
(1,	'Ù…Ø­Ù…Ø¯ Ø¹Ù„ÙŠ',	'moha',	'moha@gmail.com',	'$2y$10$0mh9m8cX3qdDEJfLHgb1l.Oy/I.42sQ8IbHk5dj7pDaum1Izpxp9m',	NULL,	0,	'customer',	'2025-09-03 21:12:54'),
(2,	'Ø§Ø­Ù…Ø¯ Ø¹Ù„ÙŠ',	'ahmed',	'ahmed@gmail.com',	'$2y$10$0taJ6IgTM1hFMuqLqei81ege3g8k0F6FR3sByWr4sueOcCUdlaRw6',	NULL,	0,	'customer',	'2025-09-03 21:13:46'),
(4,	'وليد محمد',	'leedo',	'admin@site.com',	'$2y$10$T8GS0EC0MJECFK68J/kpQ.vTBt3PoHFxrcCWcLAbaqv6XjXr6XcP6',	NULL,	1,	'admin',	'2025-09-04 11:03:24'),
(5,	'محمد علي',	'moh',	'moh@gmail.com',	'$2y$10$vUncz1P5ATCHsPE8d0ObleqCAcMgMwELywMlHpwWvzd01Rs586W1i',	NULL,	0,	'customer',	'2025-09-04 11:30:18');

DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transfer_type` enum('bank','ajil') NOT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `admin_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `wallet_transactions` (`id`, `user_id`, `amount`, `transfer_type`, `proof_image`, `status`, `requested_at`, `admin_note`) VALUES
(1,	2,	5000.00,	'ajil',	'',	'pending',	'2025-09-04 10:50:15',	NULL);

-- 2025-09-04 13:50:24
