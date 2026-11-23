<?php

namespace BizProfile\Core;

use PDO;
use PDOException;

/**
 * SetupDraftRepository
 *
 * Draft onboarding persistence/resume scaffold for future M2.
 * Draft answers segregated by secure server session/claim ID.
 * No dynamic question bank yet (M2 scope).
 */
class SetupDraftRepository
{
    public function createSession(string $sessionId, int $expiresInSeconds = 86400): bool
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresInSeconds);

            Database::execute(
                "INSERT INTO onboarding_sessions (session_id, step_data, current_step, completed_steps, expires_at)
                 VALUES (?, '{}', NULL, '[]', ?)",
                [$sessionId, $expiresAt]
            );

            return true;
        } catch (PDOException $e) {
            error_log("SetupDraftRepository::createSession error: " . $e->getMessage());
            return false;
        }
    }

    public function getSession(string $sessionId): ?array
    {
        try {
            $row = Database::query(
                "SELECT * FROM onboarding_sessions WHERE session_id = ? AND expires_at > NOW() LIMIT 1",
                [$sessionId]
            );
            return empty($row) ? null : $row[0];
        } catch (PDOException $e) {
            error_log("SetupDraftRepository::getSession error: " . $e->getMessage());
            return null;
        }
    }

    public function updateStep(string $sessionId, string $step, array $stepData, array $completedSteps = []): bool
    {
        try {
            Database::execute(
                "UPDATE onboarding_sessions
                 SET step_data = ?, current_step = ?, completed_steps = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE session_id = ? AND expires_at > NOW()",
                [json_encode($stepData), $step, json_encode($completedSteps), $sessionId]
            );

            return true;
        } catch (PDOException $e) {
            error_log("SetupDraftRepository::updateStep error: " . $e->getMessage());
            return false;
        }
    }

    public function linkClaim(string $sessionId, int $claimId): bool
    {
        try {
            Database::execute(
                "UPDATE onboarding_sessions SET claim_id = ? WHERE session_id = ?",
                [$claimId, $sessionId]
            );
            return true;
        } catch (PDOException $e) {
            error_log("SetupDraftRepository::linkClaim error: " . $e->getMessage());
            return false;
        }
    }

    public function cleanupExpired(): void
    {
        try {
            Database::execute("DELETE FROM onboarding_sessions WHERE expires_at <= NOW()");
        } catch (PDOException $e) {
            error_log("SetupDraftRepository::cleanupExpired error: " . $e->getMessage());
        }
    }
}
