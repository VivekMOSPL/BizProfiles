-- BizProfile M1 Migration: Installation state, owner claims, OTP challenges, onboarding sessions
-- Apply AFTER bizprofile.sql (run on the same `bizprofile` database)
-- Safe to re-run: uses IF NOT EXISTS / column existence checks

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: app_installation_state (singleton)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_installation_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` varchar(64) NOT NULL,
  `state` enum('schema_missing','schema_ready','owner_provisioned','owner_activated','operational','error') NOT NULL DEFAULT 'schema_ready',
  `onboarding_state` enum('not_started','in_progress','awaiting_email_verification','ready_for_admin','complete') NOT NULL DEFAULT 'not_started',
  `publication_state` enum('unpublished','preview','published','maintenance') NOT NULL DEFAULT 'unpublished',
  `authorized_owner_email_hash` varchar(255) DEFAULT NULL COMMENT 'Keyed hash of provisioned owner email (lookup only)',
  `authorized_owner_email_encrypted` varchar(512) DEFAULT NULL COMMENT 'Encrypted email for OTP delivery',
  `smtp_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: owner_claims
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `owner_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `claim_id` varchar(64) NOT NULL,
  `email_normalized` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `mobile_e164` varchar(20) DEFAULT NULL,
  `status` enum('provisional','activated','rejected','expired') NOT NULL DEFAULT 'provisional',
  `otp_verified` tinyint(1) NOT NULL DEFAULT '0',
  `activated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `claim_id` (`claim_id`),
  UNIQUE KEY `email_normalized` (`email_normalized`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: verification_challenges
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `verification_challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `challenge_id` varchar(64) NOT NULL,
  `owner_claim_id` int(11) DEFAULT NULL,
  `email_normalized` varchar(255) NOT NULL,
  `purpose` enum('owner_activation','returning_owner','email_change') NOT NULL DEFAULT 'owner_activation',
  `otp_hash` varchar(255) NOT NULL COMMENT 'Salted/peppered OTP verifier, never plaintext',
  `attempts` int(11) NOT NULL DEFAULT '0',
  `max_attempts` int(11) NOT NULL DEFAULT '5',
  `expires_at` datetime NOT NULL,
  `consumed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `challenge_id` (`challenge_id`),
  KEY `email_normalized` (`email_normalized`),
  KEY `expires_at` (`expires_at`),
  KEY `purpose` (`purpose`),
  CONSTRAINT `fk_verification_challenges_owner_claim` FOREIGN KEY (`owner_claim_id`) REFERENCES `owner_claims` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: onboarding_sessions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `onboarding_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `claim_id` int(11) DEFAULT NULL,
  `step_data` json DEFAULT NULL COMMENT 'Draft onboarding answers',
  `current_step` varchar(50) DEFAULT NULL,
  `completed_steps` json DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `claim_id` (`claim_id`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_onboarding_sessions_claim` FOREIGN KEY (`claim_id`) REFERENCES `owner_claims` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert default installation state row
-- --------------------------------------------------------
INSERT INTO `app_installation_state` (`instance_id`, `state`, `onboarding_state`, `publication_state`, `smtp_verified`)
SELECT 'default', 'schema_ready', 'not_started', 'unpublished', 0
WHERE NOT EXISTS (SELECT 1 FROM `app_installation_state`);

COMMIT;
