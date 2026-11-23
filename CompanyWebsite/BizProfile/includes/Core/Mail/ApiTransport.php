<?php

namespace BizProfile\Core\Mail;

/**
 * ApiTransport
 *
 * Placeholder driver for API-based email providers (Mailgun, SendGrid,
 * Amazon SES, etc.). To activate, implement the send() method with the
 * provider's HTTP API and set MAIL_DRIVER=api plus the provider's API key.
 */
class ApiTransport implements MailDriverInterface
{
    private string $apiKey = '';
    private string $apiEndpoint = '';

    public function __construct()
    {
        $this->apiKey = $_ENV['MAIL_API_KEY'] ?? '';
        $this->apiEndpoint = $_ENV['MAIL_API_ENDPOINT'] ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiEndpoint);
    }

    public function sendOtpEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        error_log("ApiTransport: API email driver not yet implemented; email not sent to {$toEmail}");
        return false;
    }

    public function sendVerificationEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        return $this->sendOtpEmail($toEmail, $otp, $purpose);
    }
}
