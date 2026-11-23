<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\InstallationStateService;
use BizProfile\Core\OwnerClaimService;
use BizProfile\Core\OtpChallengeService;
use BizProfile\Core\MailTransport;
use BizProfile\Core\SetupDraftRepository;
use BizProfile\Core\AuthSessionService;
use BizProfile\Core\AuditService;

class SetupController extends Controller
{
    private InstallationStateService $stateService;
    private OwnerClaimService $claimService;
    private SetupDraftRepository $draftRepo;
    private AuditService $audit;
    private AuthSessionService $session;

    public function __construct()
    {
        $this->stateService = new InstallationStateService();
        $this->claimService = new OwnerClaimService();
        $this->draftRepo = new SetupDraftRepository();
        $this->audit = new AuditService();
        $this->session = new AuthSessionService();
    }

    /**
     * GET /setup - Setup landing / pending screen.
     * Shows welcome form if no activated owner, or pending page if unpublished.
     */
    public function index(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // If owner already activated, redirect to admin
        if ($this->stateService->isOwnerActivated()) {
            $this->redirect('/admin/dashboard');
            return;
        }

        // If schema missing, show unavailable
        if ($this->stateService->isSchemaMissing()) {
            $this->adminView('setup/unavailable', [
                'reason' => 'schema_missing',
                'message' => 'Database schema is missing. Run the CLI installer to initialize.',
            ]);
            return;
        }

        $provisioned = $this->stateService->getAuthorizedOwnerEmailHash() !== null;

        if (!$provisioned) {
            $this->adminView('setup/unavailable', [
                'reason' => 'owner_not_provisioned',
                'message' => 'No owner has been provisioned for this deployment. Contact the deployer.',
            ]);
            return;
        }

        // Show welcome/identify form
        $this->adminView('setup/welcome', [
            'csrf_token' => $this->session->getCsrfToken(),
        ]);
    }

    /**
     * GET/POST /setup/identify
     * Owner enters name, business, email, mobile. Server matches against provisioned email.
     * Neutral responses to prevent enumeration.
     */
    public function identify(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $csrfToken = $_POST['csrf_token'] ?? '';
        $email = $_POST['email'] ?? '';
        $name = $_POST['name'] ?? '';
        $businessName = $_POST['business_name'] ?? '';
        $mobile = $_POST['mobile'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->adminView('setup/identify', [
                'csrf_token' => $this->session->getCsrfToken(),
                'error' => $_SESSION['setup_error'] ?? null,
            ]);
            unset($_SESSION['setup_error']);
            return;
        }

        // CSRF validation
        if (!$this->session->validateCsrfToken($csrfToken)) {
            $_SESSION['setup_error'] = 'Invalid request. Please try again.';
            $this->redirect('/setup/identify');
            return;
        }

        // Input validation
        $email = OwnerClaimService::normalizeEmail($email);
        $mobileE164 = OwnerClaimService::normalizeMobile($mobile);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['setup_error'] = 'Please enter a valid email address.';
            $this->redirect('/setup/identify');
            return;
        }

        if (empty($name) || empty($businessName)) {
            $_SESSION['setup_error'] = 'Name and business name are required.';
            $this->redirect('/setup/identify');
            return;
        }

        // Neutral response: we never reveal whether the email matches
        // If it matches, create provisional claim; otherwise, still show success page
        $claimId = $this->claimService->createProvisionalClaim(
            $email,
            $name,
            $businessName,
            $mobileE164,
            $ip
        );

        if ($claimId) {
            // Create draft session
            $sessionId = bin2hex(random_bytes(16));
            $_SESSION['setup_session_id'] = $sessionId;
            $_SESSION['setup_claim_id'] = $claimId;
            $_SESSION['setup_email'] = $email;

            $this->draftRepo->createSession($sessionId);
            $this->draftRepo->linkClaim($sessionId, $claimId);

            $this->audit->logClaimCreated($email, $claimId, $ip);

            $_SESSION['setup_step'] = 'identify_complete';
            $this->redirect('/setup/otp/send');
        } else {
            // Neutral response: show the same OTP send page regardless of match
            // This prevents account enumeration
            $_SESSION['setup_email'] = $email;
            $_SESSION['setup_name'] = $name;
            $_SESSION['setup_business_name'] = $businessName;
            $_SESSION['setup_mobile'] = $mobileE164;

            $this->redirect('/setup/otp/send');
        }
    }

    /**
     * GET /setup/resume - Returning owner resumes their draft.
     * Requires OTP verification before disclosing any draft data.
     */
    public function resume(): void
    {
        if (!$this->stateService->isOwnerActivated()) {
            // Not activated yet - redirect to identify
            $this->redirect('/setup/identify');
            return;
        }

        $this->session->requireOwner();
        $this->adminView('setup/resume', [
            'csrf_token' => $this->session->getCsrfToken(),
        ]);
    }
}
