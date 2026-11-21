-- BizProfile Migration: Missing schema to satisfy enhanced_requirements.md
-- Apply AFTER bizprofile.sql (run on the same `bizprofile` database)
-- Safe to re-run: uses IF NOT EXISTS / column existence checks

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Add columns missing from existing tables
-- --------------------------------------------------------

-- users: theme preference for per-user theme selection
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `theme_preference` varchar(50) DEFAULT 'blue_corporate' AFTER `remember_token`;

-- product_releases: downloadable file, naming and audit columns used by ProductReleaseController
ALTER TABLE `product_releases`
  ADD COLUMN IF NOT EXISTS `download_file` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `download_file_name` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `created_by` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `updated_by` int(11) DEFAULT NULL;

-- products: featured image used by ProductServiceController
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `featured_image` varchar(255) DEFAULT NULL;

-- --------------------------------------------------------
-- Missing tables
-- --------------------------------------------------------

-- services: required by ServicesController / API
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext,
  `price` decimal(10,2) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- demo_requests: required by ProductServiceController->demoRequest()
CREATE TABLE IF NOT EXISTS `demo_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `product_interest` varchar(255) DEFAULT NULL,
  `service_interest` varchar(255) DEFAULT NULL,
  `message` text,
  `status` enum('pending','contacted','completed','cancelled') NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- newsletter_log: required by NewsletterController->logNewsletterSubscription()
CREATE TABLE IF NOT EXISTS `newsletter_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
