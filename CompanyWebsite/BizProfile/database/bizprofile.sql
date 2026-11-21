-- BizProfile Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: bizprofile

-- --------------------------------------------------------

-- Table: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','editor') NOT NULL DEFAULT 'editor',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: website_settings
CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `footer` text,
  `address` text,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `google_map` text,
  `theme` varchar(50) NOT NULL DEFAULT 'blue_corporate',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pages
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: products
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: product_categories
CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: product_images
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: product_releases
CREATE TABLE `product_releases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext,
  `version` varchar(50) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: testimonials
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `content` text,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: clients
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: gallery
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `album` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: careers
CREATE TABLE `careers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext,
  `requirements` longtext,
  `location` varchar(255) DEFAULT NULL,
  `type` enum('full_time','part_time','contract','internship') NOT NULL DEFAULT 'full_time',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: job_applications
CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `career_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `resume` varchar(255) NOT NULL,
  `cover_letter` text,
  `status` enum('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `career_id` (`career_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: enquiries
CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` longtext,
  `status` enum('new','read','replied','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: feedback
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `content` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: newsletter
CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: visitors
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `page_url` varchar(255) NOT NULL,
  `visited_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`),
  KEY `visited_at` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: themes
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text,
  `colors` json DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: sliders
CREATE TABLE `sliders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: menus
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: seo_settings
CREATE TABLE `seo_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_type` enum('home','about','services','products','product_detail','news','news_detail','gallery','careers','contact','custom') NOT NULL,
  `page_id` int(11) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` text,
  `canonical_url` varchar(255) DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text,
  `og_image` varchar(255) DEFAULT NULL,
  `schema_markup` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: media
CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `type` enum('image','pdf','video','document') NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: logs
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` enum('debug','info','warning','error') NOT NULL,
  `message` text NOT NULL,
  `context` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: activity_logs
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: email_templates
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `variables` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: backup_logs
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: settings_history
CREATE TABLE `settings_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `setting_key` varchar(255) NOT NULL,
  `old_value` text,
  `new_value` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: user_sessions
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: api_keys
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `permissions` text,
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: product_attributes
CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(255) NOT NULL,
  `attribute_value` text,
  `sort_order` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample themes
INSERT INTO `themes` (`name`, `slug`, `description`, `colors`, `status`) VALUES
('Blue Corporate', 'blue_corporate', 'Professional blue theme for corporate websites', '{\"primary\": \"#007bff\", \"secondary\": \"#6c757d\", \"background\": \"#ffffff\", \"text\": \"#333333\"}', 'active'),
('Dark Professional', 'dark_professional', 'Dark theme for modern professional look', '{\"primary\": \"#0d6efd\", \"secondary\": \"#6c757d\", \"background\": \"#1a1a1a\", \"text\": \"#ffffff\"}', 'active'),
('Green Business', 'green_business', 'Green theme for eco-friendly businesses', '{\"primary\": \"#28a745\", \"secondary\": \"#6c757d\", \"background\": \"#ffffff\", \"text\": \"#333333\"}', 'active'),
('Orange Startup', 'orange_startup', 'Orange theme for energetic startups', '{\"primary\": \"#fd7e14\", \"secondary\": \"#6c757d\", \"background\": \"#ffffff\", \"text\": \"#333333\"}', 'active'),
('Purple Modern', 'purple_modern', 'Purple theme for creative businesses', '{\"primary\": \"#6f42c1\", \"secondary\": \"#6c757d\", \"background\": \"#ffffff\", \"text\": \"#333333\"}', 'active');

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Super Admin', 'admin@bizprofile.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active');

-- Insert default website settings
INSERT INTO `website_settings` (`company_name`, `footer`, `address`, `phone`, `email`, `theme`) VALUES
('BizProfile', '© 2024 BizProfile. All rights reserved.', '123 Business Street, City, Country', '+1 234 567 8900', 'info@bizprofile.com', 'blue_corporate');

-- Insert sample pages
INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`) VALUES
('Home', 'home', '<h1>Welcome to BizProfile</h1><p>Your trusted partner for business solutions.</p>', 'Home - BizProfile', 'Welcome to BizProfile - Your trusted partner for business solutions.', 'active'),
('About Us', 'about', '<h1>About Us</h1><p>Learn more about our company and mission.</p>', 'About Us - BizProfile', 'Learn more about BizProfile and our mission.', 'active'),
('Services', 'services', '<h1>Our Services</h1><p>Explore our comprehensive business services.</p>', 'Services - BizProfile', 'Explore our comprehensive business services.', 'active'),
('Contact Us', 'contact', '<h1>Contact Us</h1><p>Get in touch with our team.</p>', 'Contact Us - BizProfile', 'Contact BizProfile for inquiries and support.', 'active');

-- Insert sample product categories
INSERT INTO `product_categories` (`name`, `slug`, `description`, `status`) VALUES
('Software Solutions', 'software-solutions', 'Enterprise software products', 'active'),
('Consulting Services', 'consulting-services', 'Business consulting packages', 'active'),
('Digital Products', 'digital-products', 'Digital tools and resources', 'active');

-- Insert sample products
INSERT INTO `products` (`name`, `slug`, `description`, `category_id`, `price`, `featured`, `status`) VALUES
('Enterprise Suite', 'enterprise-suite', 'Complete business management solution', 1, '999.00', 1, 'active'),
('Analytics Pro', 'analytics-pro', 'Advanced analytics and reporting tool', 1, '499.00', 1, 'active'),
('Business Consulting', 'business-consulting', 'Comprehensive business strategy consulting', 2, '1999.00', 0, 'active');

-- Insert sample testimonials
INSERT INTO `testimonials` (`name`, `company`, `content`, `status`) VALUES
('John Smith', 'Tech Corp', 'Excellent service and support. Highly recommended!', 'active'),
('Sarah Johnson', 'Digital Solutions', 'Professional team with great results.', 'active'),
('Mike Wilson', 'Innovate Inc', 'Transformed our business operations.', 'active');

-- Insert sample clients
INSERT INTO `clients` (`name`, `logo`, `website`, `status`) VALUES
('Microsoft', 'microsoft.png', 'https://microsoft.com', 'active'),
('Google', 'google.png', 'https://google.com', 'active'),
('Amazon', 'amazon.png', 'https://amazon.com', 'active');

-- Insert sample sliders
INSERT INTO `sliders` (`title`, `subtitle`, `image`, `link`, `sort_order`, `status`) VALUES
('Welcome to BizProfile', 'Your trusted business partner', 'slider1.jpg', '#about', 1, 'active'),
('Innovative Solutions', 'Modern technology for your business', 'slider2.jpg', '#services', 2, 'active');

-- Insert sample gallery images (20 items)
INSERT INTO `gallery` (`title`, `image`, `album`, `status`, `sort_order`) VALUES
('Office Workspace', 'gallery1.jpg', 'office', 'active', 1),
('Team Meeting', 'gallery2.jpg', 'office', 'active', 2),
('Conference Room', 'gallery3.jpg', 'office', 'active', 3),
('Product Showcase', 'gallery4.jpg', 'products', 'active', 4),
('Product Demo', 'gallery5.jpg', 'products', 'active', 5),
('Company Event 1', 'gallery6.jpg', 'events', 'active', 6),
('Company Event 2', 'gallery7.jpg', 'events', 'active', 7),
('Team Building', 'gallery8.jpg', 'events', 'active', 8),
('Annual Meetup', 'gallery9.jpg', 'events', 'active', 9),
('Award Ceremony', 'gallery10.jpg', 'events', 'active', 10),
('Client Visit 1', 'gallery11.jpg', 'clients', 'active', 11),
('Client Visit 2', 'gallery12.jpg', 'clients', 'active', 12),
('Training Session', 'gallery13.jpg', 'office', 'active', 13),
('Work Environment', 'gallery14.jpg', 'office', 'active', 14),
('Product Launch', 'gallery15.jpg', 'products', 'active', 15),
('Development Team', 'gallery16.jpg', 'office', 'active', 16),
('Marketing Team', 'gallery17.jpg', 'office', 'active', 17),
('Support Team', 'gallery18.jpg', 'office', 'active', 18),
('Leadership Meeting', 'gallery19.jpg', 'office', 'active', 19),
('Holiday Celebration', 'gallery20.jpg', 'events', 'active', 20);

-- Insert sample careers (5 items)
INSERT INTO `careers` (`title`, `slug`, `description`, `requirements`, `location`, `type`, `status`, `sort_order`) VALUES
('Senior Software Developer', 'senior-software-developer', 'We are looking for an experienced software developer to join our team.', 'Bachelor degree in Computer Science. 3+ years experience in PHP, MySQL, JavaScript.', 'New York, NY', 'full_time', 'active', 1),
('Marketing Manager', 'marketing-manager', 'Lead our marketing initiatives and drive brand growth.', 'Bachelor degree in Marketing. 5+ years experience in digital marketing.', 'Remote', 'full_time', 'active', 2),
('UI/UX Designer', 'ui-ux-designer', 'Create beautiful and intuitive user interfaces.', 'Portfolio required. Experience with Figma and modern design tools.', 'San Francisco, CA', 'full_time', 'active', 3),
('Sales Executive', 'sales-executive', 'Drive sales and build client relationships.', '2+ years sales experience. Strong communication skills.', 'Chicago, IL', 'full_time', 'active', 4),
('Customer Support Specialist', 'customer-support-specialist', 'Provide excellent support to our customers.', 'Customer service experience. Problem-solving skills required.', 'Remote', 'part_time', 'active', 5);

-- Insert sample news articles (15 items)
INSERT INTO `product_releases` (`product_id`, `title`, `slug`, `content`, `version`, `release_date`, `status`) VALUES
(1, 'Enterprise Suite v3.0 Released', 'enterprise-suite-v3-0', '<p>We are excited to announce the release of Enterprise Suite version 3.0 with new features and improvements.</p>', '3.0', '2024-01-15', 'active'),
(1, 'Enterprise Suite Security Update', 'enterprise-suite-security-update', '<p>New security patches and enhancements have been applied to Enterprise Suite.</p>', '2.9.5', '2024-02-20', 'active'),
(2, 'Analytics Pro Integration Guide', 'analytics-pro-integration', '<p>Learn how to integrate Analytics Pro with your existing systems.</p>', '1.5', '2024-01-25', 'active'),
(2, 'Analytics Pro New Dashboard', 'analytics-pro-dashboard', '<p>New customizable dashboard features now available in Analytics Pro.</p>', '1.6', '2024-03-10', 'active'),
(NULL, 'Company Milestone Achieved', 'company-milestone', '<p>We have reached 1000+ satisfied customers milestone.</p>', NULL, '2024-02-01', 'active'),
(NULL, 'New Office Opening', 'new-office-opening', '<p>We are opening a new office in Austin, Texas.</p>', NULL, '2024-02-15', 'active'),
(NULL, 'Partnership Announcement', 'partnership-announcement', '<p>Excited to partner with leading industry players.</p>', NULL, '2024-03-01', 'active'),
(NULL, 'Annual Conference 2024', 'annual-conference-2024', '<p>Join us at our annual conference this year.</p>', NULL, '2024-03-15', 'active'),
(NULL, 'Sustainability Initiative', 'sustainability-initiative', '<p>Our new green initiative for sustainable business practices.</p>', NULL, '2024-03-20', 'active'),
(1, 'Enterprise Suite Mobile App', 'enterprise-suite-mobile', '<p>New mobile application for Enterprise Suite now available.</p>', '3.1', '2024-04-01', 'active'),
(2, 'Analytics Pro API v2.0', 'analytics-pro-api-v2', '<p>Introducing API version 2.0 with enhanced capabilities.</p>', '2.0', '2024-04-10', 'active'),
(NULL, 'Q1 Financial Results', 'q1-financial-results', '<p>Strong performance in Q1 with 25% growth.</p>', NULL, '2024-04-15', 'active'),
(NULL, 'New Feature Rollout', 'new-feature-rollout', '<p>Introducing exciting new features this quarter.</p>', NULL, '2024-04-20', 'active'),
(NULL, 'Customer Success Stories', 'customer-success-stories', '<p>Read about our customers success stories and testimonials.</p>', NULL, '2024-04-25', 'active'),
(NULL, 'Industry Recognition', 'industry-recognition', '<p>Received industry award for innovation excellence.</p>', NULL, '2024-05-01', 'active');

COMMIT;