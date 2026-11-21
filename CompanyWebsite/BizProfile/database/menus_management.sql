-- BizProfile Menus Management - Migration
-- Add menus management tables to support configurable page visibility
-- Apply AFTER database/enhanced_requirements_tables.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: menu_categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: menu_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `link` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `page_type` enum('static','dynamic','external','custom') NOT NULL DEFAULT 'static',
  `content_source` text,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `show_on_menus` text,
  `show_on_roles` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `slug` (`slug"),
  KEY `category_id` (`category_id"),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id") REFERENCES `menu_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: page_menu_config
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_menu_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(255) NOT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `custom_title` varchar(255) DEFAULT NULL,
  `custom_content` text,
  `template_override` varchar(100) DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text,
  `show_in_main_menu` tinyint(1) NOT NULL DEFAULT '0',
  `show_in_footer` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `page_slug` (`page_slug"),
  KEY `menu_item_id` (`menu_item_id"),
  CONSTRAINT `page_menu_config_ibfk_1` FOREIGN KEY (`menu_item_id") REFERENCES `menu_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: page_templates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `html_template` longtext,
  `is_core` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `slug" (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: custom_page_content
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `custom_page_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(255) NOT NULL,
  `content_type` enum('text','html','file','video','gallery') NOT NULL DEFAULT 'text',
  `content` longtext,
  `file_path` varchar(500) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  """

<arg_key>variable_placeholder_0</arg_key>
<arg_value>
  `auto_increment` int(11) NOT NULL AUTO_INCREMENT,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` text,
  `breadcrumb` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  """
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `page_slug" (`page_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Sample data for menu_categories
-- --------------------------------------------------------
INSERT INTO `menu_categories` (`name`, `slug`, `description`, `status`) VALUES
('Main Navigation', 'main_navigation', 'Main navigation menu for the website', 'active'),
('Footer Menu', 'footer_menu', 'Footer links and information', 'active'),
('Utility Menu', 'utility_menu', 'Utility pages like contact, login', 'active'),
('Service Menu', 'service_menu', 'Service and product links', 'active'),
('Information Menu', 'information_menu', 'Informational links', 'active');

-- --------------------------------------------------------
-- Sample data for menu_items
-- --------------------------------------------------------
INSERT INTO `menu_items` (`category_id`, `title`, `slug`, `link`, `icon`, `page_type`, `content_source`, `is_featured`, `sort_order`, `status`, `show_on_menus`, `show_on_roles`) VALUES
(1, 'Home', 'home', '/', 'fas fa-home', 'static', NULL, 1, 1, 'active', '["main_navigation"]', '["all"]'),
(1, 'About Us', 'about', '/about', 'fas fa-info-circle', 'static', NULL, 0, 2, 'active', '["main_navigation"]', '["all"]'),
(1, 'Services', 'services', '/services', 'fas fa-cogs', 'static', NULL, 1, 3, 'active', '["main_navigation", "service_menu"]', '["all"]'),
(1, 'Products', 'products', '/products', 'fas fa-box', 'static', NULL, 1, 4, 'active', '["main_navigation", "service_menu"]', '["all"]'),
(1, 'News', 'news', '/news', 'fas fa-newspaper', 'static', NULL, 0, 5, 'active', '["main_navigation", "information_menu"]', '["all"]'),
(1, 'Gallery', 'gallery', '/gallery', 'fas fa-images', 'static', NULL, 0, 6, 'active', '["main_navigation", "information_menu"]', '["all"]'),
(1, 'Careers', 'careers', '/careers', 'fas fa-briefcase', 'static', NULL, 0, 7, 'active', '["main_navigation", "utility_menu"]', '["all"]'),
(1, 'Contact Us', 'contact', '/contact', 'fas fa-envelope', 'static', NULL, 1, 8, 'active', '["main_navigation", "utility_menu"]', '["all"]'),
(2, 'Privacy Policy', 'privacy-policy', '/privacy-policy', 'fas fa-shield-alt', 'static', NULL, 0, 1, 'active', '["footer_menu"]', '["all"]'),
(2, 'Terms of Service', 'terms', '/terms', 'fas fa-file-contract', 'static', NULL, 0, 2, 'active', '["footer_menu"]', '["all"]'),
(2, 'Sitemap', 'sitemap', '/sitemap', 'fas fa-sitemap', 'static', NULL, 0, 3, 'active', '["footer_menu"]', '["all"]'),
(2, 'Accessibility', 'accessibility', '/accessibility', 'fas fa-universal-access', 'static', NULL, 0, 4, 'active', '["footer_menu"]', '["all"]'),
(3, 'Login', 'login', '/admin/login', 'fas fa-sign-in-alt', 'external', NULL, 0, 1, 'active', '["utility_menu"]', '["user"]'),
(3, 'Register', 'register', '/admin/register', 'fas fa-user-plus', 'external', NULL, 0, 2, 'active', '["utility_menu"]', '["guest"]'),
(4, 'Consulting', 'consulting', '/consulting', 'fas fa-comments', 'static', NULL, 1, 1, 'active', '["service_menu"]', '["all"]'),
(4, 'Support', 'support', '/support', 'fas fa-headset', 'static', NULL, 0, 2, 'active', '["service_menu"]', '["all"]'),
(5, 'FAQ', 'faq', '/faq', 'fas fa-question-circle', 'static', NULL, 0, 1, 'active', '["information_menu"]', '["all"]'),
(5, 'Getting Started', 'getting-started', '/getting-started', 'fas fa-play', 'static', NULL, 1, 2, 'active', '["information_menu"]', '["all"]'),
(5, 'Documentation', 'documentation', '/documentation', 'fas fa-book', 'static', NULL, 0, 3, 'active', '["information_menu"]', '["all"]');

-- --------------------------------------------------------
-- Sample data for page_templates
-- --------------------------------------------------------
INSERT INTO `page_templates` (`name`, `slug`, `html_template`, `is_core`, `status`) VALUES
('Default Page', 'default', '<div class="page-container"><h1>{{title}}</h1><div class="page-content">{{content}}</div></div>', 1, 'active'),
('Service Page', 'service', '<div class="service-page"><h1>{{title}}</h1><div class="service-content">{{content}}</div><div class="service-sidebar">{{sidebar}}</div></div>', 1, 'active'),
('News Page', 'news', '<div class="news-page"><h1>{{title}}</h1><div class="news-list">{{news_list}}</div><div class="news-sidebar">{{sidebar}}</div></div>', 1, 'active'),
('Contact Page', 'contact', '<div class="contact-page"><h1>{{title}}</h1><div class="contact-form">{{form}}</div><div class="contact-info">{{info}}</div></div>', 1, 'active'),
('Gallery Page', 'gallery', '<div class="gallery-page"><h1>{{title}}</h1><div class="gallery-grid">{{gallery}}</div><div class="gallery-sidebar">{{sidebar}}</div></div>', 1, 'active'),
('Careers Page', 'careers', '<div class="careers-page"><h1>{{title}}</h1><div class="careers-content">{{content}}</div><div class="careers-sidebar">{{sidebar}}</div></div>', 1, 'active');

-- --------------------------------------------------------
-- Sample data for page_menu_config
-- --------------------------------------------------------
INSERT INTO `page_menu_config` (`page_slug`, `menu_item_id`, `category`, `enabled`, `custom_title`, `custom_content`, `template_override`, `seo_title`, `seo_description`, `show_in_main_menu`, `show_in_footer`, `display_order`) VALUES
('home', 1, 'home', 1, NULL, NULL, NULL, 'Welcome to BizProfile', 'Your trusted business partner', 1, 0, 1),
('about', 2, 'about', 1, NULL, NULL, NULL, 'About Us - Company Profile', 'Learn more about our company and mission', 1, 0, 2),
('services', 3, 'services', 1, NULL, NULL, NULL, 'Our Services', 'Professional services we offer', 1, 0, 3),
('products', 4, 'products', 1, NULL, NULL, NULL, 'Our Products', 'Complete catalog of products and services', 1, 0, 4),
('news', 5, 'news', 1, NULL, NULL, NULL, 'Latest News & Updates', 'Stay updated with our latest news', 1, 0, 5),
('gallery', 6, 'gallery', 1, NULL, NULL, NULL, 'Photo Gallery', 'Visual showcase of our work', 1, 0, 6),
('careers', 7, 'careers', 1, NULL, NULL, NULL, 'Join Our Team', 'Career opportunities and job listings', 1, 0, 7),
('contact', 8, 'contact', 1, NULL, NULL, NULL, 'Contact Us', 'Get in touch with our team', 1, 0, 8),
('privacy-policy', 9, 'privacy', 1, NULL, NULL, NULL, 'Privacy Policy', 'Our privacy policy and data protection', 0, 1, 1),
('terms', 10, 'terms', 1, NULL, NULL, NULL, 'Terms of Service', 'Terms and conditions of use', 0, 1, 2),
('sitemap', 11, 'sitemap', 1, NULL, NULL, NULL, 'Site Map', 'Complete navigation of our website', 0, 1, 3),
('accessibility', 12, 'accessibility', 1, NULL, NULL, NULL, 'Accessibility Statement', 'Our commitment to accessibility', 0, 1, 4),
('consulting', 13, 'consulting', 1, NULL, NULL, NULL, 'Consulting Services', 'Professional consulting services', 0, 0, 1),
('support', 14, 'support', 1, NULL, NULL, NULL, 'Support Services', 'Customer support and help', 0, 0, 2),
('faq', 15, 'faq', 1, NULL, NULL, NULL, 'Frequently Asked Questions', 'Common questions and answers', 0, 0, 1),
('getting-started', 16, 'getting-started', 1, NULL, NULL, NULL, 'Getting Started', 'Quick start guide', 0, 0, 2),
('documentation', 17, 'documentation', 1, NULL, NULL, NULL, 'Documentation', 'Detailed documentation and guides', 0, 0, 3);

COMMIT;
