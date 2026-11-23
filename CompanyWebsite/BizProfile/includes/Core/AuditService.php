<?php

namespace BizProfile\Core;

use PDO;
use PDOException;

/**
 * AuditService
 *
 * Immutable audit events for setup, provisioning, recovery, claims and verification.
 * Uses existing activity_logs table.
 */
class AuditService
{
    public function log(string $action, ?int $userId, string $description, string $ipAddress = '', array $context = []): bool
    {
        try {
            $db = Database::getInstance();
            $contextJson = !empty($context) ? json_encode($context) : null;

            return (bool) Database::execute(
                "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                 VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [$userId, $action, $description, $ipAddress]
            );
        } catch (PDOException $e) {
            error_log("AuditService::log error: " . $e->getMessage());
            return false;
        }
    }

    public function logProvisioning(string $emailHash, string $ipAddress, string $reason = ''): bool
    {
        return $this->log('owner_provisioned', null, "Owner email provisioned (hash: {$emailHash})" . ($reason ? " - {$reason}" : ''), $ipAddress);
    }

    public function logClaimCreated(string $emailNormalized, string $claimId, string $ipAddress): bool
    {
        return $this->log('owner_claim_created', null, "Provisional claim created for {$emailNormalized} (claim: {$claimId})", $ipAddress);
    }

    public function logOtpChallenge(string $emailNormalized, string $purpose, string $ipAddress): bool
    {
        return $this->log('otp_challenge_created', null, "OTP challenge created for {$emailNormalized} (purpose: {$purpose})", $ipAddress);
    }

    public function logOtpVerified(?int $claimId, string $ipAddress): bool
    {
        return $this->log('otp_verified', $claimId, "OTP verified for claim {$claimId}", $ipAddress);
    }

    public function logOwnerActivated(?int $claimId, string $ipAddress): bool
    {
        return $this->log('owner_activated', $claimId, "Owner activated for claim {$claimId}", $ipAddress);
    }

    public function logRecovery(string $action, string $emailHash, string $ipAddress, string $reason): bool
    {
        return $this->log('owner_recovery', null, "Owner recovery: {$action} (hash: {$emailHash}) - {$reason}", $ipAddress);
    }

    public function getRecent(int $limit = 50): array
    {
        try {
            $db = Database::getInstance();
            return $db->query(
                "SELECT * FROM activity_logs
                 WHERE action IN ('owner_provisioned','owner_claim_created','otp_challenge_created','otp_verified','owner_activated','owner_recovery')
                 ORDER BY created_at DESC LIMIT ?",
                [$limit]
            );
        } catch (PDOException $e) {
            error_log("AuditService::getRecent error: " . $e->getMessage());
            return [];
        }
    }
}
