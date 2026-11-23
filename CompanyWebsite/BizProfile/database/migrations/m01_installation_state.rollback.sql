-- BizProfile M1 Migration Rollback: Drops M1-specific tables
-- Only run this to roll back m01_installation_state.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `onboarding_sessions`;
DROP TABLE IF EXISTS `verification_challenges`;
DROP TABLE IF EXISTS `owner_claims`;
DROP TABLE IF EXISTS `app_installation_state`;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
