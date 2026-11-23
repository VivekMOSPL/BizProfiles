<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\InstallationStateService;
use BizProfile\Core\OwnerClaimService;
use BizProfile\Core\OtpChallengeService;
use BizProfile\Core\MailTransport;
use BizProfile\Core\AuthSessionService;
use BizProfile\Core\AuditService;

class VerificationController extends Controller
{
    private OtpChallengeService $otpService;
    private OwnerClaimService $claimService;
    private InstallationStateService $stateService;
    private AuthSessionService $session;
    private AuditService $audit;

    public function __construct()
    {
        $this->otpService = new OtpChallengeService(new MailTransport());
        $this->claimService = new OwnerClaimService();
        $this->stateService = new InstallationStateService();
        $this->session = new AuthSessionService();
        $this->audit = new AuditService();
    }

    /**
     * POST /setup/otp/send - Send OTP to the provisioned email.
     * No bypass on SMTP failure - activation blocked if email not delivered.
     */
    public function sendOtp(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->adminView('setup/otp', [
                'csrf_token' => $this->session->getCsrfToken(),
                'step' => 'send',
                'email' => $_SESSION['setup_email'] ?? '',
                'error' => $_SESSION['otp_error'] ?? null,
            ]);
            unset($_SESSION['otp_error']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        $email = $_SESSION['setup_email'] ?? $_POST['email'] ?? '';
        $purpose = 'owner_activation';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (!$this->session->validateCsrfToken($csrfToken)) {
            $_SESSION['otp_error'] = 'Invalid request. Please try again.';
            $this->redirect('/setup/otp/send');
            return;
        }

        $email = OwnerClaimService::normalizeEmail($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['otp_error'] = 'Invalid email address.';
            $this->redirect('/setup/otp/send');
            return;
        }

        // Get claim ID if available
        $claim = $this->claimService->getClaimByEmail($email);
        $claimId = $claim ? (int) $claim['id'] : null;

        // Create OTP challenge (this also attempts to send email)
        $challengeId = $this->otpService->createChallengeWithHash(
            $email,
            $purpose,
            $claimId,
            $ip
        );

        if (!$challengeId) {
            $_SESSION['otp_error'] = 'Unable to send verification code at this time. Please try again later.';
            $this->redirect('/setup/otp/send');
            return;
        }

        $_SESSION['setup_challenge_id'] = $challengeId;
        $_SESSION['setup_email'] = $email;

        $this->audit->logOtpChallenge($email, $purpose, $ip);

        $this->redirect('/setup/otp/verify');
    }

    /**
     * GET/POST /setup/otp/verify - Verify OTP and activate owner.
     */
    public function verifyOtp(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $challengeId = $_SESSION['setup_challenge_id'] ?? '';
        $email = $_SESSION['setup_email'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        $otp = $_POST['otp'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (empty($challengeId)) {
                $this->redirect('/setup/otp/send');
                return;
            }
            $this->adminView('setup/otp', [
                'csrf_token' => $this->session->getCsrfToken(),
                'step' => 'verify',
                'email' => $email,
                'error' => $_SESSION['otp_error'] ?? null,
            ]);
            unset($_SESSION['otp_error']);
            return;
        }

        if (!$this->session->validateCsrfToken($csrfToken)) {
            $_SESSION['otp_error'] = 'Invalid request. Please try again.';
            $this->redirect('/setup/otp/verify');
            return;
        }

        if (empty($challengeId) || empty($otp)) {
            $_SESSION['otp_error'] = 'Please enter the verification code.';
            $this->redirect('/setup/otp/verify');
            return;
        }

        $result = $this->otpService->verifyOtp($challengeId, $otp, $ip);

        if (!$result['valid']) {
            $_SESSION['otp_error'] = $this->getErrorMessage($result['reason']);
            $this->redirect('/setup/otp/verify');
            return;
        }

        // OTP verified - mark claim as OTP verified
        $claim = $this->claimService->getClaimByEmail($email);
        if ($claim) {
            $this->claimService->markOtpVerified($claim['claim_id']);
        }

        $this->audit->logOtpVerified($claim ? (int) $claim['id'] : null, $ip);

        // Check if email matches the provisioned authorized email
        $isProvisioned = $this->claimService->isProvisioned($email);

        if (!$isProvisioned) {
            // Email doesn't match provisioned email - neutral response, no activation
            $_SESSION['otp_error'] = 'Verification code is valid, but this email is not authorized for owner activation.';
            $this->redirect('/setup/otp/verify');
            return;
        }

        // Activate owner (singleton guaranteed by OwnerClaimService::activateOwner)
        $activated = $this->claimService->activateOwner($claim['claim_id'], $ip);

        if (!$activated) {
            $_SESSION['otp_error'] = 'Unable to activate owner account. Another activation may be in progress.';
            $this->redirect('/setup/otp/verify');
            return;
        }

        $this->audit->logOwnerActivated($claim ? (int) $claim['id'] : null, $ip);

        // Create owner session and redirect to admin
        $this->session->createOwnerSession(
            $claim['id'],
            $email,
            'owner'
        );

        // Clear setup session data
        unset($_SESSION['setup_email'], $_SESSION['setup_challenge_id'], $_SESSION['setup_session_id'], $_SESSION['setup_claim_id']);

        $this->redirect('/admin/dashboard');
    }

    private function getErrorMessage(string $reason): string
    {
        return match ($reason) {
            'challenge_not_found' => 'Verification challenge not found. Please request a new code.',
            'challenge_consumed' => 'This verification code has already been used. Please request a new code.',
            'challenge_expired' => 'Verification code has expired. Please request a new code.',
            'max_attempts_exceeded' => 'Too many failed attempts. Please request a new code.',
            'invalid_otp' => 'Invalid verification code. Please try again.',
            default => 'Verification failed. Please try again.',
        };
    }
}
