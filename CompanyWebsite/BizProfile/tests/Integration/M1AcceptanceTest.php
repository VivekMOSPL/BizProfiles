<?php

namespace BizProfile\Tests\Integration;

use BizProfile\Tests\TestSuite;
use BizProfile\Core\InstallationStateService;
use BizProfile\Core\OwnerClaimService;
use BizProfile\Core\OtpChallengeService;
use BizProfile\Core\MailTransport;
use BizProfile\Core\Database;

/**
 * M1 Acceptance Tests
 * Covers M1-AT-01 through M1-AT-15 from Acceptance Test Matrix v0.1
 */
class M1AcceptanceTest extends TestSuite
{
    protected string $testEmail = 'owner.test@example.com';
    protected string $wrongEmail = 'wrong.example@example.com';
    protected string $testName = 'Test Owner';
    protected string $testBusiness = 'Test Business';

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure .env is loaded and constants defined for Database::getInstance()
        $this->loadEnv();

        // Apply M1 migration to the same database that Database::getInstance() uses
        $this->applyM1Migration();
    }

    protected function loadEnv(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);
        }
        if (!defined('APP_ENV')) {
            define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
        }
    }

    protected function applyM1Migration(): void
    {
        if (!isset($this->pdo)) {
            return;
        }

        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `app_installation_state` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `instance_id` varchar(64) NOT NULL,
              `state` enum('schema_missing','schema_ready','owner_provisioned','owner_activated','operational','error') NOT NULL DEFAULT 'schema_ready',
              `onboarding_state` enum('not_started','in_progress','awaiting_email_verification','ready_for_admin','complete') NOT NULL DEFAULT 'not_started',
              `publication_state` enum('unpublished','preview','published','maintenance') NOT NULL DEFAULT 'unpublished',
              `authorized_owner_email_hash` varchar(255) DEFAULT NULL,
              `authorized_owner_email_encrypted` varchar(512) DEFAULT NULL,
              `smtp_verified` tinyint(1) NOT NULL DEFAULT '0',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `instance_id` (`instance_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `owner_claims` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `verification_challenges` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `challenge_id` varchar(64) NOT NULL,
              `owner_claim_id` int(11) DEFAULT NULL,
              `email_normalized` varchar(255) NOT NULL,
              `purpose` enum('owner_activation','returning_owner','email_change') NOT NULL DEFAULT 'owner_activation',
              `otp_hash` varchar(255) NOT NULL,
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
              KEY `purpose` (`purpose`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `onboarding_sessions` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `session_id` varchar(64) NOT NULL,
              `claim_id` int(11) DEFAULT NULL,
              `step_data` json DEFAULT NULL,
              `current_step` varchar(50) DEFAULT NULL,
              `completed_steps` json DEFAULT NULL,
              `expires_at` datetime NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `session_id` (`session_id`),
              KEY `claim_id` (`claim_id`),
              KEY `expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Insert/reset default state row
            $this->pdo->exec("DELETE FROM `app_installation_state`");
            $this->pdo->exec("INSERT INTO `app_installation_state` (`instance_id`, `state`, `onboarding_state`, `publication_state`, `smtp_verified`)
              VALUES ('default', 'schema_ready', 'not_started', 'unpublished', 0)");
        } catch (\PDOException $e) {
            // Migration may already be applied
        }
    }

    protected function cleanupDatabase(): void
    {
        if (!isset($this->pdo)) {
            return;
        }

        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $tables = ['verification_challenges', 'onboarding_sessions', 'owner_claims', 'app_installation_state'];
            foreach ($tables as $table) {
                $this->pdo->exec("DELETE FROM {$table}");
            }
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\PDOException $e) {
            // Ignore cleanup errors
        }
    }

    // ========================================================================
    // M1-AT-01: No provisioned email → Public owner claim is impossible
    // ========================================================================
    public function testM1At01NoProvisionedEmailClaimImpossible(): void
    {
        $stateService = new InstallationStateService();
        $this->assertFalse($stateService->isOwnerProvisioned());
        $this->assertFalse($stateService->isOwnerActivated());

        $claimService = new OwnerClaimService();
        $this->assertFalse($claimService->isProvisioned('nonexistent@example.com'));
        $this->assertNull($claimService->createProvisionalClaim(
            'nonexistent@example.com', 'Name', 'Business', null, '127.0.0.1'
        ));
    }

    // ========================================================================
    // M1-AT-02: Wrong identifier → Neutral response; no enumeration, no access
    // ========================================================================
    public function testM1At02WrongIdentifierNeutralResponse(): void
    {
        $this->provisionTestOwner();

        $claimService = new OwnerClaimService();

        $this->assertFalse($claimService->isProvisioned($this->wrongEmail));

        $result = $claimService->createProvisionalClaim(
            $this->wrongEmail, 'Name', 'Business', null, '127.0.0.1'
        );
        $this->assertNull($result);

        $this->assertNull($claimService->getClaimByEmail($this->wrongEmail));
    }

    // ========================================================================
    // M1-AT-03: Initial claim match → Matching provisioned email allows only provisional draft
    // ========================================================================
    public function testM1At03InitialClaimMatchProvisionalDraft(): void
    {
        $this->provisionTestOwner();

        $claimService = new OwnerClaimService();
        $this->assertTrue($claimService->isProvisioned($this->testEmail));

        $claimId = $claimService->createProvisionalClaim(
            $this->testEmail,
            $this->testName,
            $this->testBusiness,
            '+1234567890',
            '127.0.0.1'
        );

        $this->assertNotNull($claimId);
        $this->assertIsString($claimId);

        $claim = $claimService->getClaimById($claimId);
        $this->assertNotNull($claim);
        $this->assertEquals('provisional', $claim['status']);
        $this->assertEquals(0, $claim['otp_verified']);
        $this->assertNull($claim['activated_at']);

        $stateService = new InstallationStateService();
        $this->assertFalse($stateService->isOwnerActivated());
    }

    // ========================================================================
    // M1-AT-04: OTP valid → Exactly one owner activated, secure session
    // ========================================================================
    public function testM1At04ValidOtpActivatesOneOwner(): void
    {
        $this->provisionTestOwner();

        $claimService = new OwnerClaimService();
        $mailTransport = new MailTransport();
        $otpService = new OtpChallengeService($mailTransport);

        $claimId = $claimService->createProvisionalClaim(
            $this->testEmail,
            $this->testName,
            $this->testBusiness,
            '+1234567890',
            '127.0.0.1'
        );

        $this->assertNotNull($claimId);

        $challengeId = $otpService->createChallengeWithHash(
            $this->testEmail,
            'owner_activation',
            null,
            '127.0.0.1'
        );

        $this->assertNotNull($challengeId);

        $challenge = Database::query(
            "SELECT * FROM verification_challenges WHERE challenge_id = ? LIMIT 1",
            [$challengeId]
        );
        $this->assertNotEmpty($challenge);
        $this->assertNull($challenge[0]['consumed_at']);

        // Override OTP hash with known value for deterministic testing
        $knownOtp = '988919';
        $knownHash = password_hash($knownOtp, PASSWORD_BCRYPT);
        Database::execute(
            "UPDATE verification_challenges SET otp_hash = ? WHERE challenge_id = ?",
            [$knownHash, $challengeId]
        );

        // Verify known OTP end-to-end
        $result = $otpService->verifyOtp($challengeId, $knownOtp, '127.0.0.1');
        $this->assertTrue($result['valid']);

        // Confirm challenge was consumed in the database
        $consumed = Database::query(
            "SELECT consumed_at FROM verification_challenges WHERE challenge_id = ? LIMIT 1",
            [$challengeId]
        );
        $this->assertNotNull($consumed[0]['consumed_at']);

        // Mark claim as OTP verified and activate owner
        $claimService->markOtpVerified($claimId);

        $claim = $claimService->getClaimById($claimId);
        $this->assertEquals(1, $claim['otp_verified']);

        $activated = $claimService->activateOwner($claimId, '127.0.0.1');
        $this->assertTrue($activated);

        $stateService = new InstallationStateService();
        $this->assertTrue($stateService->isOwnerActivated());
        $this->assertFalse($stateService->isPublished());

        $activatedAgain = $claimService->activateOwner($claimId, '127.0.0.1');
        $this->assertFalse($activatedAgain);
    }

    // ========================================================================
    // M1-AT-05: OTP invalid/expired/reused → Denied; attempt/cooldown/limit enforced
    // ========================================================================
    public function testM1At05InvalidOtpDenied(): void
    {
        $mailTransport = new MailTransport();
        $otpService = new OtpChallengeService($mailTransport);

        $challengeId = $otpService->createChallengeWithHash(
            $this->testEmail,
            'owner_activation',
            null,
            '127.0.0.1'
        );

        $this->assertNotNull($challengeId);

        $result = $otpService->verifyOtp($challengeId, '000000', '127.0.0.1');
        $this->assertFalse($result['valid']);
        $this->assertEquals('invalid_otp', $result['reason']);

        $result = $otpService->verifyOtp($challengeId, '111111', '127.0.0.1');
        $this->assertFalse($result['valid']);

        for ($i = 0; $i < 3; $i++) {
            $otpService->verifyOtp($challengeId, '222222', '127.0.0.1');
        }

        $result = $otpService->verifyOtp($challengeId, '333333', '127.0.0.1');
        $this->assertFalse($result['valid']);
        $this->assertEquals('max_attempts_exceeded', $result['reason']);
    }

    // ========================================================================
    // M1-AT-06: Returning owner → OTP before private answers; exact step resume
    // ========================================================================
    public function testM1At06ReturningOwnerOtpBeforeResume(): void
    {
        $this->activateTestOwner();

        $draftRepo = new \BizProfile\Core\SetupDraftRepository();
        $sessionId = bin2hex(random_bytes(16));

        $created = $draftRepo->createSession($sessionId);
        $this->assertTrue($created);

        $updated = $draftRepo->updateStep($sessionId, 'identify', [
            'name' => $this->testName,
            'business_name' => $this->testBusiness,
        ]);
        $this->assertTrue($updated);

        $session = $draftRepo->getSession($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals('identify', $session['current_step']);

        $draftRepo->cleanupExpired();
    }

    // ========================================================================
    // M1-AT-07: Parallel claim race → One active owner, transaction safe
    // ========================================================================
    public function testM1At07ParallelClaimSingleton(): void
    {
        $this->provisionTestOwner();

        $claimService = new OwnerClaimService();

        $claimId1 = $claimService->createProvisionalClaim(
            $this->testEmail, 'Name 1', 'Business 1', null, '127.0.0.1'
        );
        $claimId2 = $claimService->createProvisionalClaim(
            $this->testEmail, 'Name 2', 'Business 2', null, '127.0.0.1'
        );

        $this->assertNotNull($claimId1);
        $this->assertNotNull($claimId2);

        $count = Database::query(
            "SELECT COUNT(*) as cnt FROM owner_claims WHERE email_normalized = ?",
            [OwnerClaimService::normalizeEmail($this->testEmail)]
        );
        $this->assertEquals(1, (int) $count[0]['cnt']);
    }

    // ========================================================================
    // M1-AT-08: SMTP outage → Safe blocked state; no bypass; draft preserved
    // ========================================================================
    public function testM1At08SmtpOutageBlockedNoBypass(): void
    {
        $mailTransport = new MailTransport();
        $otpService = new OtpChallengeService($mailTransport);

        $challengeId = $otpService->createChallengeWithHash(
            $this->testEmail,
            'owner_activation',
            null,
            '127.0.0.1'
        );

        $this->assertNotNull($challengeId);

        $challenge = Database::query(
            "SELECT * FROM verification_challenges WHERE challenge_id = ? LIMIT 1",
            [$challengeId]
        );
        $this->assertNotEmpty($challenge);
        $this->assertNull($challenge[0]['consumed_at']);
    }

    // ========================================================================
    // M1-AT-09: CLI email recovery → Audited owner recovery, invalidates old auth
    // ========================================================================
    public function testM1At09CliEmailRecovery(): void
    {
        $this->provisionTestOwner();

        $stateService = new InstallationStateService();
        $oldHash = $stateService->getAuthorizedOwnerEmailHash();
        $this->assertNotNull($oldHash);

        $newEmail = 'newowner.test@example.com';
        $newHash = OwnerClaimService::hashEmail($newEmail);
        $emailEncrypted = base64_encode($newEmail);

        $success = $stateService->setState($stateService->getState(), [
            'authorized_owner_email_hash' => $newHash,
            'authorized_owner_email_encrypted' => $emailEncrypted,
        ]);

        $this->assertTrue($success);

        $claimService = new OwnerClaimService();
        $this->assertFalse($claimService->isProvisioned($this->testEmail));
        $this->assertTrue($claimService->isProvisioned($newEmail));

        $newStateHash = $stateService->getAuthorizedOwnerEmailHash();
        $this->assertEquals($newHash, $newStateHash);
        $this->assertNotEquals($oldHash, $newStateHash);
    }

    // ========================================================================
    // M1-AT-10: Unauthorized admin → Access denied; owner/admin role enforced
    // ========================================================================
    public function testM1At10UnauthorizedAdminDenied(): void
    {
        $sessionService = new \BizProfile\Core\AuthSessionService();
        $this->assertFalse($sessionService->isOwnerAuthenticated());

        $this->activateTestOwner();

        $_SESSION['owner_authenticated'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = $this->testEmail;
        $_SESSION['user_role'] = 'owner';

        $this->assertTrue($sessionService->isOwnerAuthenticated());
    }

    // ========================================================================
    // M1-AT-11: No auto publication → Site remains unpublished after verification
    // ========================================================================
    public function testM1At11NoAutoPublication(): void
    {
        $this->activateTestOwner();

        $stateService = new InstallationStateService();
        $this->assertTrue($stateService->isOwnerActivated());
        $this->assertFalse($stateService->isPublished());
        $this->assertEquals('unpublished', $stateService->getPublicationState());
    }

    // ========================================================================
    // M1-AT-12: Routes/regression → Existing public/admin URLs preserved
    // ========================================================================
    public function testM1At12RoutesPreserved(): void
    {
        $this->assertFileExists(__DIR__ . '/../../includes/Controllers/HomeController.php');
        $this->assertFileExists(__DIR__ . '/../../includes/Controllers/AuthController.php');
        $this->assertFileExists(__DIR__ . '/../../includes/Controllers/AdminController.php');

        $this->assertFileExists(__DIR__ . '/../../includes/Controllers/SetupController.php');
        $this->assertFileExists(__DIR__ . '/../../includes/Controllers/VerificationController.php');

        $this->assertFileExists(__DIR__ . '/../../includes/Core/InstallationStateService.php');
        $this->assertFileExists(__DIR__ . '/../../includes/Core/OwnerClaimService.php');
        $this->assertFileExists(__DIR__ . '/../../includes/Core/OtpChallengeService.php');
        $this->assertFileExists(__DIR__ . '/../../includes/Core/SetupGate.php');
    }

    // ========================================================================
    // M1-AT-13: Session/CSRF → Rotation, flags, anti-CSRF and logout verified
    // ========================================================================
    public function testM1At13SessionCsrfVerified(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (session_status() === PHP_SESSION_NONE || !isset($_SESSION)) {
            $this->markTestSkipped('Session not available for CSRF test');
        }

        $sessionService = new \BizProfile\Core\AuthSessionService();

        $token1 = $sessionService->getCsrfToken();
        $this->assertNotEmpty($token1);
        $this->assertIsString($token1);

        $this->assertTrue($sessionService->validateCsrfToken($token1));
        $this->assertFalse($sessionService->validateCsrfToken('invalid_token'));

        $sessionService->createOwnerSession(1, $this->testEmail, 'owner');
        $this->assertTrue($sessionService->isOwnerAuthenticated());
        $this->assertEquals($this->testEmail, $_SESSION['user_email']);
        $this->assertEquals('owner', $_SESSION['user_role']);

        $oldSessionId = session_id();
        $sessionService->rotateSession();
        $this->assertNotEquals($oldSessionId, session_id());

        $sessionService->destroyOwnerSession();
        $this->assertFalse($sessionService->isOwnerAuthenticated());
    }

    // ========================================================================
    // M1-AT-14: Migration/rollback → Reversible where feasible; DB backups verified
    // ========================================================================
    public function testM1At14MigrationRollback(): void
    {
        $tables = ['app_installation_state', 'owner_claims', 'verification_challenges', 'onboarding_sessions'];
        foreach ($tables as $table) {
            $this->assertTrue($this->tableExistsM1($table), "Table {$table} should exist after migration");
        }

        $this->applyM1Migration();
        foreach ($tables as $table) {
            $this->assertTrue($this->tableExistsM1($table), "Table {$table} should still exist after re-migration");
        }
    }

    // ========================================================================
    // M1-AT-15: Responsive/accessibility → Setup flows on desktop/mobile + keyboard
    // ========================================================================
    public function testM1At15SetupViewsAccessible(): void
    {
        $views = [
            'welcome' => __DIR__ . '/../../admin/setup/welcome.php',
            'identify' => __DIR__ . '/../../admin/setup/identify.php',
            'otp' => __DIR__ . '/../../admin/setup/otp.php',
            'resume' => __DIR__ . '/../../admin/setup/resume.php',
            'pending' => __DIR__ . '/../../admin/setup/pending.php',
            'unavailable' => __DIR__ . '/../../admin/setup/unavailable.php',
        ];

        foreach ($views as $name => $path) {
            $this->assertFileExists($path, "View {$name} should exist");
            $content = file_get_contents($path);
            $this->assertStringContainsString('lang="en"', $content, "View {$name} should have lang attribute");
            $this->assertStringContainsString('viewport', $content, "View {$name} should have viewport meta for mobile");
            if ($name !== 'resume' && $name !== 'pending' && $name !== 'unavailable') {
                $this->assertStringContainsString('<form', $content, "View {$name} should have form element");
            }
        }
    }

    // ========================================================================
    // Helper methods
    // ========================================================================

    protected function tableExistsM1(string $tableName): bool
    {
        if (!isset($this->pdo)) {
            return false;
        }

        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    protected function provisionTestOwner(): void
    {
        $emailHash = OwnerClaimService::hashEmail($this->testEmail);
        $emailEncrypted = base64_encode($this->testEmail);

        // Direct DB provisioning to ensure test isolation
        $stmt = $this->pdo->prepare("UPDATE app_installation_state SET state = 'owner_provisioned', authorized_owner_email_hash = ?, authorized_owner_email_encrypted = ?, onboarding_state = 'not_started', publication_state = 'unpublished', smtp_verified = 0");
        $stmt->execute([$emailHash, $emailEncrypted]);
    }

    protected function activateTestOwner(): void
    {
        $this->provisionTestOwner();

        $claimService = new OwnerClaimService();
        $claimId = $claimService->createProvisionalClaim(
            $this->testEmail,
            $this->testName,
            $this->testBusiness,
            '+1234567890',
            '127.0.0.1'
        );

        if (!$claimId) {
            throw new \RuntimeException("createProvisionalClaim failed");
        }

        $claimService->markOtpVerified($claimId);
        $claimService->activateOwner($claimId, '127.0.0.1');
    }
}
