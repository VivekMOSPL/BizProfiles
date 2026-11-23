<?php

namespace BizProfile\Core;

/**
 * SetupGate
 *
 * Route pre-dispatch gate that enforces installation/onboarding/publication
 * state boundaries per Master SRS v0.4 section C.
 *
 * Route handling:
 *   - DB unavailable            -> HTTP 503 setup-unavailable
 *   - schema missing            -> setup-unavailable page (CLI installer required)
 *   - no activated owner        -> setup landing (limited /setup routes allowed)
 *   - owner activated,          -> public site when published; neutral pending page when
 *     unpublished                                           unpublished (except authenticated owner preview/admin)
 *   - GET /admin/login never    -> discloses unverified draft data
 */
class SetupGate
{
    private InstallationStateService $stateService;
    private array $allowedSetupRoutes = [
        '/setup',
        '/setup/identify',
        '/setup/otp/send',
        '/setup/otp/verify',
        '/setup/resume',
    ];
    private array $allowedAdminRoutes = [
        '/admin/login',
        '/admin/logout',
    ];

    public function __construct(InstallationStateService $stateService)
    {
        $this->stateService = $stateService;
    }

    /**
     * Evaluate the gate before route dispatch.
     * Returns a response array ['halt' => true, 'code' => int, 'view' => string, 'data' => array]
     * or ['halt' => false] to continue normal routing.
     */
    public function evaluate(string $uri, string $method, bool $isOwnerAuthenticated = false): array
    {
        // DB unavailable -> 503
        if (!$this->stateService->isDbAvailable()) {
            return [
                'halt' => true,
                'code' => 503,
                'view' => 'setup/unavailable',
                'data' => ['reason' => 'database_unavailable'],
            ];
        }

        // Schema missing -> setup-unavailable (CLI installer required)
        if ($this->stateService->isSchemaMissing()) {
            return [
                'halt' => true,
                'code' => 200,
                'view' => 'setup/unavailable',
                'data' => ['reason' => 'schema_missing'],
            ];
        }

        // Error state -> 503
        if ($this->stateService->isError()) {
            return [
                'halt' => true,
                'code' => 503,
                'view' => 'setup/unavailable',
                'data' => ['reason' => 'error'],
            ];
        }

        // No activated owner -> setup landing
        if (!$this->stateService->isOwnerActivated()) {
            // Allow setup routes
            if (in_array($uri, $this->allowedSetupRoutes, true)) {
                return ['halt' => false];
            }

            // Allow admin login/logout (unauthenticated access)
            if (in_array($uri, $this->allowedAdminRoutes, true) && !$isOwnerAuthenticated) {
                return ['halt' => false];
            }

            // Block admin routes for unauthenticated users
            if (strpos($uri, '/admin/') === 0 && !$isOwnerAuthenticated) {
                return [
                    'halt' => true,
                    'code' => 200,
                    'view' => 'setup/pending',
                    'data' => ['reason' => 'owner_not_activated'],
                ];
            }

            // Everything else -> setup landing page
            return [
                'halt' => true,
                'code' => 200,
                'view' => 'setup/pending',
                'data' => ['reason' => 'owner_not_activated'],
            ];
        }

        // Owner activated but unpublished -> neutral pending page except authenticated owner/admin
        if ($this->stateService->isUnpublished()) {
            if ($isOwnerAuthenticated) {
                return ['halt' => false];
            }

            // Allow public health/static assets
            if ($uri === '/' || $uri === '/api/system/health' || strpos($uri, '/assets/') === 0) {
                return ['halt' => false];
            }

            return [
                'halt' => true,
                'code' => 200,
                'view' => 'setup/pending',
                'data' => ['reason' => 'site_unpublished'],
            ];
        }

        // Published and owner activated -> normal routing
        return ['halt' => false];
    }

    public function isSetupRoute(string $uri): bool
    {
        return in_array($uri, $this->allowedSetupRoutes, true) || strpos($uri, '/setup/') === 0;
    }
}
