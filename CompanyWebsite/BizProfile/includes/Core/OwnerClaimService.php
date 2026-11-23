<?php

namespace BizProfile\Core;

use PDO;
use PDOException;

/**
 * OwnerClaimService
 *
 * Manages owner claim lifecycle per D04:
 * - Matches provisioned email without trusting browser-only state
 * - Creates provisional data without owner privileges
 * - Guarantees singleton owner activation (database uniqueness + transaction)
 * - Neutral responses to prevent account enumeration
 */
class OwnerClaimService
{
    public function isProvisioned(string $emailNormalized): bool
    {
        $stateService = new InstallationStateService();
        $hash = $stateService->getAuthorizedOwnerEmailHash();

        if (!$hash) {
            return false;
        }

        return self::verifyEmailHash($emailNormalized, $hash);
    }

    public static function verifyEmailHash(string $emailNormalized, string $storedHash): bool
    {
        $expected = self::hashEmail($emailNormalized);
        return hash_equals($storedHash, $expected);
    }

    public static function hashEmail(string $emailNormalized): string
    {
        $pepper = $_ENV['SETUP_EMAIL_PEPPER'] ?? 'bizprofile-setup-pepper-change-in-production';
        return hash_hmac('sha256', $emailNormalized, $pepper);
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function normalizeMobile(?string $mobile): ?string
    {
        if ($mobile === null || $mobile === '') {
            return null;
        }
        $digits = preg_replace('/[^0-9+]/', '', $mobile);
        if (strpos($digits, '+') !== 0) {
            $digits = '+' . $digits;
        }
        return $digits;
    }

    /**
     * Create a provisional owner claim. Returns claim ID or null on failure.
     * Only allowed if the email matches the provisioned authorized email.
     */
    public function createProvisionalClaim(
        string $emailNormalized,
        string $name,
        string $businessName,
        ?string $mobileE164,
        string $ipAddress
    ): ?string {
        if (!$this->isProvisioned($emailNormalized)) {
            return null;
        }

        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();

            // Singleton check: exactly one active owner per instance
            $existing = Database::query(
                "SELECT id FROM owner_claims WHERE email_normalized = ? AND status = 'activated' LIMIT 1",
                [$emailNormalized]
            );
            if (!empty($existing)) {
                $pdo->rollBack();
                return null; // Already activated
            }

            // Check for existing provisional claim for this email (update instead of duplicate)
            $existingProvisional = Database::query(
                "SELECT id, claim_id FROM owner_claims WHERE email_normalized = ? AND status = 'provisional' LIMIT 1",
                [$emailNormalized]
            );

            $claimId = bin2hex(random_bytes(16));

            if (!empty($existingProvisional)) {
                Database::execute(
                    "UPDATE owner_claims SET name = ?, business_name = ?, mobile_e164 = ?, status = 'provisional',
                     otp_verified = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                    [$name, $businessName, $mobileE164, $existingProvisional[0]['id']]
                );
                $pdo->commit();
                return $existingProvisional[0]['claim_id'];
            }

            Database::execute(
                "INSERT INTO owner_claims (claim_id, email_normalized, name, business_name, mobile_e164, status, otp_verified)
                 VALUES (?, ?, ?, ?, ?, 'provisional', 0)",
                [$claimId, $emailNormalized, $name, $businessName, $mobileE164]
            );

            $this->audit('owner_claim_created', null, "Provisional claim created for {$emailNormalized}", $ipAddress);

            $pdo->commit();
            return $claimId;
        } catch (PDOException $e) {
            error_log("OwnerClaimService::createProvisionalClaim error: " . $e->getMessage());
            return null;
        }
    }

    public function getClaimByEmail(string $emailNormalized): ?array
    {
        try {
            $row = Database::query(
                "SELECT * FROM owner_claims WHERE email_normalized = ? LIMIT 1",
                [$emailNormalized]
            );
            return empty($row) ? null : $row[0];
        } catch (PDOException $e) {
            error_log("OwnerClaimService::getClaimByEmail error: " . $e->getMessage());
            return null;
        }
    }

    public function getClaimById(string $claimId): ?array
    {
        try {
            $row = Database::query(
                "SELECT * FROM owner_claims WHERE claim_id = ? LIMIT 1",
                [$claimId]
            );
            return empty($row) ? null : $row[0];
        } catch (PDOException $e) {
            error_log("OwnerClaimService::getClaimById error: " . $e->getMessage());
            return null;
        }
    }

    public function markOtpVerified(string $claimId): bool
    {
        try {
            Database::execute(
                "UPDATE owner_claims SET otp_verified = 1, updated_at = CURRENT_TIMESTAMP WHERE claim_id = ?",
                [$claimId]
            );

            $claim = $this->getClaimById($claimId);
            if ($claim) {
                $this->audit('otp_verified', null, "OTP verified for claim {$claimId}", $_SERVER['REMOTE_ADDR'] ?? 'cli');
            }

            return true;
        } catch (PDOException $e) {
            error_log("OwnerClaimService::markOtpVerified error: " . $e->getMessage());
            return false;
        }
    }

    public function activateOwner(string $claimId, string $ipAddress): bool
    {
        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();

            $claim = $this->getClaimById($claimId);
            if (!$claim || $claim['status'] !== 'provisional' || !$claim['otp_verified']) {
                $pdo->rollBack();
                return false;
            }

            // Singleton guarantee: check no other activated owner exists for this email
            $existing = Database::query(
                "SELECT id FROM owner_claims WHERE email_normalized = ? AND status = 'activated' LIMIT 1",
                [$claim['email_normalized']]
            );
            if (!empty($existing)) {
                $pdo->rollBack();
                return false;
            }

            Database::execute(
                "UPDATE owner_claims SET status = 'activated', otp_verified = 1, activated_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                 WHERE claim_id = ?",
                [$claimId]
            );

            // Activate installation state
            $stateService = new InstallationStateService();
            $stateService->activateOwner();

            $this->audit('owner_activated', null, "Owner activated for claim {$claimId}", $ipAddress);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            error_log("OwnerClaimService::activateOwner error: " . $e->getMessage());
            return false;
        }
    }

    public function hasActivatedOwner(): bool
    {
        $stateService = new InstallationStateService();
        return $stateService->isOwnerActivated();
    }

    private function audit(string $action, ?int $userId, string $description, string $ipAddress): void
    {
        try {
            Database::execute(
                "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
                [$userId, $action, $description, $ipAddress]
            );
        } catch (PDOException $e) {
            error_log("OwnerClaimService::audit error: " . $e->getMessage());
        }
    }
}
