<?php

namespace BizProfile\Core;

use PDO;
use PDOException;

/**
 * InstallationStateService
 *
 * Reads and manages the server-side install/onboarding/publish state.
 * Singleton state stored in app_installation_state table.
 *
 * States:
 *   installation: schema_missing, schema_ready, owner_provisioned, owner_activated, operational, error
 *   onboarding:   not_started, in_progress, awaiting_email_verification, ready_for_admin, complete
 *   publication:  unpublished, preview, published, maintenance
 */
class InstallationStateService
{
    private const STATE_SCHEMA_MISSING = 'schema_missing';
    private const STATE_SCHEMA_READY = 'schema_ready';
    private const STATE_OWNER_PROVISIONED = 'owner_provisioned';
    private const STATE_OWNER_ACTIVATED = 'owner_activated';
    private const STATE_OPERATIONAL = 'operational';
    private const STATE_ERROR = 'error';

    private const ONBOARDING_NOT_STARTED = 'not_started';
    private const ONBOARDING_IN_PROGRESS = 'in_progress';
    private const ONBOARDING_AWAITING_EMAIL_VERIFICATION = 'awaiting_email_verification';
    private const ONBOARDING_READY_FOR_ADMIN = 'ready_for_admin';
    private const ONBOARDING_COMPLETE = 'complete';

    private const PUBLICATION_UNPUBLISHED = 'unpublished';
    private const PUBLICATION_PREVIEW = 'preview';
    private const PUBLICATION_PUBLISHED = 'published';
    private const PUBLICATION_MAINTENANCE = 'maintenance';

    private ?array $state = null;
    private bool $dbAvailable = false;

    public function __construct()
    {
        $this->loadState();
    }

    private function loadState(): void
    {
        try {
            $this->dbAvailable = true;

            // Ensure table exists
            $tables = Database::query("SHOW TABLES LIKE 'app_installation_state'");
            if (empty($tables)) {
                $this->state = [
                    'state' => self::STATE_SCHEMA_MISSING,
                    'onboarding_state' => self::ONBOARDING_NOT_STARTED,
                    'publication_state' => self::PUBLICATION_UNPUBLISHED,
                    'authorized_owner_email_hash' => null,
                    'smtp_verified' => 0,
                    'instance_id' => null,
                ];
                return;
            }

            $row = Database::query("SELECT * FROM app_installation_state LIMIT 1");
            if (empty($row)) {
                $this->state = [
                    'state' => self::STATE_SCHEMA_READY,
                    'onboarding_state' => self::ONBOARDING_NOT_STARTED,
                    'publication_state' => self::PUBLICATION_UNPUBLISHED,
                    'authorized_owner_email_hash' => null,
                    'smtp_verified' => 0,
                    'instance_id' => null,
                ];
            } else {
                $this->state = $row[0];
            }
        } catch (PDOException $e) {
            $this->dbAvailable = false;
            $this->state = [
                'state' => self::STATE_ERROR,
                'onboarding_state' => self::ONBOARDING_NOT_STARTED,
                'publication_state' => self::PUBLICATION_UNPUBLISHED,
                'authorized_owner_email_hash' => null,
                'smtp_verified' => 0,
                'instance_id' => null,
                'db_error' => $e->getMessage(),
            ];
            error_log("InstallationStateService: DB unavailable - " . $e->getMessage());
        }
    }

    public function isDbAvailable(): bool
    {
        return $this->dbAvailable;
    }

    public function isSchemaMissing(): bool
    {
        return $this->state['state'] ?? self::STATE_SCHEMA_MISSING === self::STATE_SCHEMA_MISSING;
    }

    public function isError(): bool
    {
        return ($this->state['state'] ?? '') === self::STATE_ERROR;
    }

    public function isOwnerProvisioned(): bool
    {
        return in_array($this->state['state'] ?? '', [self::STATE_OWNER_PROVISIONED, self::STATE_OWNER_ACTIVATED, self::STATE_OPERATIONAL], true);
    }

    public function isOwnerActivated(): bool
    {
        return in_array($this->state['state'] ?? '', [self::STATE_OWNER_ACTIVATED, self::STATE_OPERATIONAL], true);
    }

    public function isPublished(): bool
    {
        return ($this->state['publication_state'] ?? '') === self::PUBLICATION_PUBLISHED;
    }

    public function isUnpublished(): bool
    {
        return ($this->state['publication_state'] ?? '') === self::PUBLICATION_UNPUBLISHED;
    }

    public function getState(): string
    {
        return $this->state['state'] ?? self::STATE_SCHEMA_READY;
    }

    public function getOnboardingState(): string
    {
        return $this->state['onboarding_state'] ?? self::ONBOARDING_NOT_STARTED;
    }

    public function getPublicationState(): string
    {
        return $this->state['publication_state'] ?? self::PUBLICATION_UNPUBLISHED;
    }

    public function getAuthorizedOwnerEmailHash(): ?string
    {
        return $this->state['authorized_owner_email_hash'] ?? null;
    }

    public function isSmtpVerified(): bool
    {
        return (bool) ($this->state['smtp_verified'] ?? 0);
    }

    public function getInstanceId(): ?string
    {
        return $this->state['instance_id'] ?? null;
    }

    public function getFullState(): array
    {
        return $this->state;
    }

    /**
     * Set installation state (used by CLI provisioning and activation)
     */
    public function setState(string $state, array $updates = []): bool
    {
        try {
            $db = Database::getInstance();

            $fields = ['state' => $state];
            foreach ($updates as $key => $value) {
                $fields[$key] = $value;
            }

$setParts = [];
            $params = [];
            foreach ($fields as $col => $val) {
                $setParts[] = "`{$col}` = ?";
                $params[] = $val;
            }

            $sql = "UPDATE app_installation_state SET " . implode(', ', $setParts);
            $result = (bool) Database::execute($sql, $params);

            if ($result) {
                // Refresh local state to reflect DB changes
                $this->loadState();
            }

            return $result;
        } catch (PDOException $e) {
            error_log("InstallationStateService::setState error: " . $e->getMessage());
            return false;
        }
    }

    public function provisionOwner(string $instanceId, string $emailHash, string $emailEncrypted): bool
    {
        try {
            $db = Database::getInstance();

            // Insert if no row exists, otherwise update
            $exists = Database::query("SELECT id FROM app_installation_state LIMIT 1");
            if (empty($exists)) {
                return (bool) Database::execute(
                    "INSERT INTO app_installation_state (instance_id, state, onboarding_state, publication_state,
                     authorized_owner_email_hash, authorized_owner_email_encrypted, smtp_verified)
                     VALUES (?, ?, ?, ?, ?, ?, 0)",
                    [$instanceId, self::STATE_OWNER_PROVISIONED, self::ONBOARDING_NOT_STARTED,
                     self::PUBLICATION_UNPUBLISHED, $emailHash, $emailEncrypted]
                );
            }

            return $this->setState(self::STATE_OWNER_PROVISIONED, [
                'instance_id' => $instanceId,
                'authorized_owner_email_hash' => $emailHash,
                'authorized_owner_email_encrypted' => $emailEncrypted,
                'onboarding_state' => self::ONBOARDING_NOT_STARTED,
                'publication_state' => self::PUBLICATION_UNPUBLISHED,
                'smtp_verified' => 0,
            ]);
        } catch (PDOException $e) {
            error_log("InstallationStateService::provisionOwner error: " . $e->getMessage());
            return false;
        }
    }

    public function activateOwner(): bool
    {
        return $this->setState(self::STATE_OWNER_ACTIVATED, [
            'onboarding_state' => self::ONBOARDING_COMPLETE,
            'publication_state' => self::PUBLICATION_UNPUBLISHED,
        ]);
    }

    public function setPublished(): bool
    {
        return $this->setState(self::STATE_OPERATIONAL, [
            'publication_state' => self::PUBLICATION_PUBLISHED,
        ]);
    }

    public function setSmtpVerified(bool $verified): bool
    {
        return $this->setState($this->getState(), ['smtp_verified' => $verified ? 1 : 0]);
    }

    public function setError(string $dbError = ''): bool
    {
        return $this->setState(self::STATE_ERROR, ['db_error' => $dbError]);
    }

    public function refresh(): void
    {
        $this->loadState();
    }
}
