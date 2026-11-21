-- BizProfile Enhanced Menus System
-- Apply AFTER menus_management.sql
-- Complete menus system for dynamic page visibility and configuration

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Add indexes and constraints to existing tables
-- --------------------------------------------------------

-- Add foreign key constraint to menu_items table
ALTER TABLE `menu_items` 
    ADD CONSTRAINT `fk_menu_items_category` 
    FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) 
    ON UPDATE CASCADE;

-- Add foreign key constraint to page_menu_config table  
ALTER TABLE `page_menu_config`
    ADD CONSTRAINT `fk_page_menu_config_menu_item` 
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) 
    ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Create page_builder table for rich content management
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_builder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(255) NOT NULL,
  `component_type` varchar(50) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_data` longtext,
  `component_settings` longtext,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  KEY `page_slug` (`page_slug"),
  KEY `component_type" (`component_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Create content_templates table for reusable content blocks
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `content_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext,
  `content_type` enum('text','html','markdown','component') NOT NULL DEFAULT 'text',
  `usage_count` int(11) NOT NULL DEFAULT '0',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Create theme_page_mapping table for theme-specific content
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `theme_page_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(255) NOT NULL,
  `theme_name` varchar(100) NOT NULL,
  `content_override` longtext,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  UNIQUE KEY `page_theme_unique" (`page_slug", `theme_name"),
  KEY `theme_name" (`theme_name")
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Create page_visibility_rules table for advanced visibility
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_visibility_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(255) NOT NULL,
  `rule_type` enum('user_role','user_group','date_range','ip_range','device_type','custom_condition') NOT NULL,
  `rule_value` text NOT NULL,
  `action` enum('show','hide','redirect') NOT NULL DEFAULT 'show',
  `redirect_url"` varchar(500) DEFAULT NULL,
  `is_active"` tinyint(1) NOT NULL DEFAULT '1',
  `created_at"` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at"` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id"),
  KEY `page_slug` (`page_slug"),
  KEY `rule_type" (`rule_type"),
  CONSTRAINT `page_visibility_rules_ibfk_1`
    FOREIGN KEY (`page_slug`) REFERENCES `menu_items` (`slug")
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Create dynamic_page_sessions table for session-based page access
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dynamic_page_sessions"` (
  `id"` int(11) NOT NULL AUTO_INCREMENT,
  `page_slug"` varchar(255) NOT NULL,
  `session_id"` varchar(255) NOT NULL,
  `user_ip"` varchar(45) DEFAULT NULL,
  `user_agent"` text,
  `accessed_at"` timestamp DEFAULT CURRENT_TIMESTAMP,
  `access_count"` int(11) NOT NULL DEFAULT '1',
  `last_accessed"` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_allowed"` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id"),
  KEY `page_slug" (`page_slug"),
  KEY `session_id" (`session_id"),
  KEY `accessed_at" (`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert sample content_templates
-- --------------------------------------------------------
INSERT INTO `content_templates" (`name`, `slug`, `content`, `content_type`, `tags`, `is_system`) VALUES
('Call to Action Button', 'cta-button', '<button class="btn btn-primary">Get Started Today</button>', 'html', 'cta,button', 1),
('Service Overview', 'service-overview', '<h2>Our Services</h2><p>We provide comprehensive solutions tailored to your needs.</p>', 'html', 'services,overview', 1),
('Testimonial Section', 'testimonial-section', '<div class="testimonials"><h2>What Our Clients Say</h2><div class="testimonial-slider">{{testimonials}}</div></div>', 'html', 'testimonials,slider', 1),
('Contact Information', 'contact-info', '<div class="contact-info"><h2>Get in Touch</h2><p>Phone: (555) 123-4567</p><p>Email: info@company.com</p></div>', 'html', 'contact,info', 1),
('Navigation Menu', 'navigation', '<nav class="main-navigation">{{menu}}</nav>', 'html', 'navigation,menu', 1),
('Footer Links', 'footer-links', '<div class="footer-links">{{footer_menu}}</div>', 'html', 'footer,links', 1),
('Featured Products', 'featured-products', '<div class="featured-products">{{featured_products}}</div>', 'html', 'products,featured', 1);

-- --------------------------------------------------------
-- Insert sample page_builder components
-- --------------------------------------------------------
INSERT INTO `page_builder" (`page_slug`, `component_type`, `component_name`, `component_data`, `component_settings", `sort_order", `is_active`) VALUES
('home', 'hero', 'Hero Section', '{"title": "Welcome to BizProfile", "subtitle": "Your Trusted Business Partner", "cta_text": "Get Started", "background_image": "/assets/images/hero-bg.jpg", "overlay": true}', '{"animation": "fadeIn", "delay": 0.3}', 1, 1),
('home', 'features', 'Features Grid', '{"features": [{"icon": "fas fa-lightbulb", "title": "Innovative Solutions", "description": "Cutting-edge technology and strategies"}, {"icon": "fas fa-users", "title": "Expert Team", "description": "Experienced professionals"}, {"icon": "fas fa-clock", "title", "24/7 Support", "description": "Always here to help"}, {"icon": "fas fa-star", "title": "Quality Assurance", "description": "Highest standards of quality"}]}', '{"columns": 4, "responsive": true, "animated": true}', 2, 1),
('home', 'about', 'About Section', '{"title": "About BizProfile", "content": "BizProfile is a comprehensive business management framework designed to streamline your operations and drive growth. Our platform integrates all essential tools you need to manage your business efficiently.", "image": "/assets/images/about.jpg", "image_position": "right"}', '{"layout": "standard", "content_alignment": "left"}', 3, 1),
('home', 'services', 'Services Showcase', '{"title": "Our Services", "subtitle": "Professional services tailored to your needs", "show_all_button": true, "category_filter": true}', '{"display_mode": "grid", "show_cta": true}', 4, 1),
('about', 'story', 'Company Story', '{"title": "Our Story", "content": "Founded in 2010, BizProfile has grown from a small startup to a leading business framework provider. Our journey is marked by continuous innovation and commitment to excellence.", "founder_image": "/assets/images/founder.jpg", "timeline": true}', '{"layout": "timeline", "theme": "light"}', 1, 1),
('about', 'mission', 'Mission & Values', '{"mission": "To empower businesses with comprehensive digital solutions", "values": [{"title": "Integrity", "description": "We conduct business with honesty and transparency"}, {"title": "Excellence", "description": "We strive for the highest quality in everything we do"}]}', '{"display_style": "cards", "show_mission": true, "show_values": true}', 2, 1),
('contact', 'contact_form', 'Contact Form', '{"title": "Get In Touch", "subtitle": "Have a question or ready to get started? Contact us today.", "form_id": "contact-form", "email": "info@company.com", "phone": "+1-555-123-4567"}', '{"layout": "full_width", "show_map": true}', 1, 1);

-- --------------------------------------------------------
-- Insert sample theme_page_mapping
-- --------------------------------------------------------
INSERT INTO `theme_page_mapping" (`page_slug`, `theme_name", `content_override`, `is_active`) VALUES
('home', 'blue_corporate', '<div class="hero-blue"><h1 style="color: #007bff;">Welcome to Blue Corporate Theme</h1></div>', 1),
('home', 'dark_professional', '<div class="hero-dark"><h1 style="color: #0d6efd;">Welcome to Dark Professional Theme</h1></div>', 1),
('home', 'green_business', '<div class="hero-green"><h1 style="color: #28a745;">Welcome to Green Business Theme</h1></div>', 1),
('about', 'purple_modern', '<div class="about-purple"><h1 style="color: #6f42c1;">About Us - Purple Modern</h1></div>', 1);

COMMIT;
