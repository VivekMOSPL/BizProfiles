-- BizProfile M2 Migration: Dynamic Industry Questionnaire
-- Apply AFTER m01_installation_state.sql
-- Safe to re-run: uses IF NOT EXISTS / column existence checks

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: questionnaires (questionnaire templates by industry)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questionnaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `industry` varchar(100) NOT NULL DEFAULT 'general',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `industry` (`industry`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: questions (individual questions within a questionnaire)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionnaire_id` int(11) NOT NULL,
  `question_key` varchar(100) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','textarea','select','radio','checkbox','email','tel','number','date','file') NOT NULL DEFAULT 'text',
  `options_json` json DEFAULT NULL COMMENT 'For select/radio/checkbox: stored options',
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `conditional_logic` json DEFAULT NULL COMMENT 'Show/hide based on previous answers',
  `help_text` varchar(500) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `questionnaire_question` (`questionnaire_id`, `question_key`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `question_type` (`question_type`),
  CONSTRAINT `fk_questions_questionnaire` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: questionnaire_responses (one response per user/submission)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questionnaire_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionnaire_id` int(11) NOT NULL,
  `respondent_name` varchar(255) DEFAULT NULL,
  `respondent_email` varchar(255) DEFAULT NULL,
  `respondent_ip` varchar(45) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Logged-in user if authenticated',
  `session_token` varchar(64) DEFAULT NULL COMMENT 'Anonymous session token',
  `is_complete` tinyint(1) NOT NULL DEFAULT '0',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `questionnaire_id` (`questionnaire_id`),
  KEY `user_id` (`user_id`),
  KEY `session_token` (`session_token`),
  KEY `submitted_at` (`submitted_at`),
  CONSTRAINT `fk_responses_questionnaire` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: response_answers (individual answers to questions)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `response_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL COMMENT 'Text/numeric/email/tel/date answers',
  `answer_json` json DEFAULT NULL COMMENT 'For checkbox/multi-select: array of values',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `response_question` (`response_id`, `question_id`),
  KEY `response_id` (`response_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `fk_answers_response` FOREIGN KEY (`response_id`) REFERENCES `questionnaire_responses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert default questionnaires for key industries
-- --------------------------------------------------------
INSERT IGNORE INTO `questionnaires` (`slug`, `title`, `description`, `industry`, `is_active`, `is_default`)
VALUES
  ('general', 'General Business Questionnaire', 'Standard business information gathering for any industry', 'general', 1, 1),
  ('manufacturing', 'Manufacturing Industry Questionnaire', 'Detailed manufacturing-specific questions for production businesses', 'manufacturing', 1, 0),
  ('professional_services', 'Professional Services Questionnaire', 'Questions tailored for consulting, legal, accounting and professional services', 'professional_services', 1, 0),
  ('ngo', 'NGO / Non-Profit Questionnaire', 'Questions for non-governmental organizations and charities', 'ngo', 1, 0);

-- --------------------------------------------------------
-- Insert default questions for the general questionnaire
-- --------------------------------------------------------
INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'business_name', 'What is your business name?', 'text', NULL, 1, 1, 'The legal or trading name of your business'
FROM questionnaires q WHERE q.slug = 'general';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'industry', 'What industry does your business operate in?', 'select',
 '["manufacturing","professional_services","retail","technology","healthcare","education","ngo","other"]',
 1, 2, 'Select the industry that best describes your business'
FROM questionnaires q WHERE q.slug = 'general';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'business_size', 'How many employees does your business have?', 'select',
 '["1-10","11-50","51-200","201-500","500+"]',
 1, 3, 'Total employee count including owners'
FROM questionnaires q WHERE q.slug = 'general';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'website_goal', 'What is the primary goal of your website?', 'select',
 '["brand_awareness","lead_generation","ecommerce","information","portfolio","other"]',
 1, 4, 'What do you want visitors to do on your site'
FROM questionnaires q WHERE q.slug = 'general';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'contact_email', 'What is the best email for business inquiries?', 'email', NULL, 1, 5, 'Used for contact form and notifications'
FROM questionnaires q WHERE q.slug = 'general';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'contact_phone', 'What is your business phone number?', 'tel', NULL, 0, 6, 'Optional phone number for customers'
FROM questionnaires q WHERE q.slug = 'general';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'additional_info', 'Any additional information about your business?', 'textarea', NULL, 0, 7, 'Special requirements, certifications, or unique selling points'
FROM questionnaires q WHERE q.slug = 'general';

-- --------------------------------------------------------
-- Insert manufacturing-specific questions
-- --------------------------------------------------------
INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'production_type', 'What type of manufacturing do you do?', 'select',
 '["discrete","process","hybrid"]',
 1, 1, 'Type of manufacturing process'
FROM questionnaires q WHERE q.slug = 'manufacturing';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'annual_capacity', 'What is your annual production capacity?', 'text', NULL, 0, 2, 'Units per year if applicable'
FROM questionnaires q WHERE q.slug = 'manufacturing';

INSERT IGNORE INTO `questions` (`questionnaire_id`, `question_key`, `question_text`, `question_type`, `options_json`, `is_required`, `sort_order`, `help_text`)
SELECT q.id, 'certifications', 'What certifications do you hold?', 'checkbox',
 '["ISO 9001","ISO 14001","CE","UL","FDA","Other"]',
 0, 3, 'Industry certifications and compliance'
FROM questionnaires q WHERE q.slug = 'manufacturing';

COMMIT;
