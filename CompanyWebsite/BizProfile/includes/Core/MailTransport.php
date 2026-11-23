<?php

namespace BizProfile\Core;

use BizProfile\Core\Mail\MailManager;

/**
 * MailTransport
 *
 * Backward-compatible wrapper around MailManager.
 * Default driver is phpmailer (configurable via MAIL_DRIVER env).
 *
 * Per D04.4: SMTP outage must not bypass OTP or grant admin rights.
 * No bypass on failure - if mail cannot be sent, activation is blocked.
 */
class MailTransport
{
    private \BizProfile\Core\Mail\MailDriverInterface $driver;

    public function __construct()
    {
        $this->driver = MailManager::make();
    }

    public function isConfigured(): bool
    {
        return $this->driver->isConfigured();
    }

    public function sendOtpEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        return $this->driver->sendOtpEmail($toEmail, $otp, $purpose);
    }

    public function sendVerificationEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        return $this->driver->sendVerificationEmail($toEmail, $otp, $purpose);
    }
}
