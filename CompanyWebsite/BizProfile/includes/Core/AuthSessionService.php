<?php

namespace BizProfile\Core;

/**
 * AuthSessionService
 *
 * Secure session management for owner authentication.
 * Session rotation on verification/login, secure cookie flags, CSRF protection.
 */
class AuthSessionService
{
    public function createOwnerSession(int $ownerId, string $ownerEmail, string $role = 'owner'): void
    {
        $_SESSION['user_id'] = $ownerId;
        $_SESSION['user_email'] = $ownerEmail;
        $_SESSION['user_name'] = $ownerEmail;
        $_SESSION['user_role'] = $role;
        $_SESSION['owner_authenticated'] = true;
        $_SESSION['owner_authenticated_at'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        Security::regenerateSessionId();
    }

    public function isOwnerAuthenticated(): bool
    {
        return !empty($_SESSION['owner_authenticated'] ?? false)
            && !empty($_SESSION['user_id'] ?? null);
    }

    public function requireOwner(): void
    {
        if (!$this->isOwnerAuthenticated()) {
            http_response_code(403);
            echo 'Access denied. Owner authentication required.';
            exit;
        }
    }

    public function destroyOwnerSession(): void
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }

    public function rotateSession(): void
    {
        Security::regenerateSessionId();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['owner_authenticated_at'] = time();
    }

    public function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
