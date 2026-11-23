<?php

namespace BizProfile\Core\Mail;

/**
 * MailDriverInterface
 *
 * Contract for all mail transport drivers.
 * Drivers: phpmailer (default), smtp, api.
 */
interface MailDriverInterface
{
    public function isConfigured(): bool;

    public function sendOtpEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool;

    public function sendVerificationEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool;
}
