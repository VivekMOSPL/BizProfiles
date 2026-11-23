<?php

namespace BizProfile\Core\Mail;

/**
 * SmtpTransport
 *
 * Alternative SMTP driver using PHP mail() fallback when stream socket
 * is not available or when the host blocks direct SMTP connections.
 */
class SmtpTransport implements MailDriverInterface
{
    public function isConfigured(): bool
    {
        return !empty($_ENV['MAIL_HOST'] ?? '');
    }

    public function sendOtpEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        if (!$this->isConfigured()) {
            error_log("SmtpTransport: SMTP not configured; email not sent to {$toEmail}");
            return false;
        }

        $subject = $this->getSubject($purpose);
        $body = $this->getBody($otp, $purpose);
        $headers = $this->getHeaders();

        $result = @mail($toEmail, $subject, $body, $headers);
        if ($result === false) {
            error_log("SmtpTransport: mail() failed for {$toEmail}");
        }

        return (bool) $result;
    }

    public function sendVerificationEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        return $this->sendOtpEmail($toEmail, $otp, $purpose);
    }

    private function getHeaders(): string
    {
        $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bizprofile.com';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'BizProfile';
        return "From: {$fromName} <{$fromAddress}>\r\n"
            . "Reply-To: {$fromAddress}\r\n"
            . "X-Mailer: BizProfile-Smtp/1.0\r\n";
    }

    private function getSubject(string $purpose): string
    {
        $appName = $_ENV['APP_NAME'] ?? 'BizProfile';
        return match ($purpose) {
            'owner_activation' => "{$appName} - Owner Activation Code",
            'returning_owner' => "{$appName} - Setup Resume Verification Code",
            'email_change' => "{$appName} - Email Change Verification Code",
            default => "{$appName} - Verification Code",
        };
    }

    private function getBody(string $otp, string $purpose): string
    {
        $appName = $_ENV['APP_NAME'] ?? 'BizProfile';
        $purposeText = match ($purpose) {
            'owner_activation' => 'activate your owner account',
            'returning_owner' => 'resume your setup',
            'email_change' => 'verify your email change',
            default => 'verify your identity',
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>{$appName}</h2>
    <p>Your verification code for {$purposeText} is:</p>
    <div style="background:#f5f7fa;padding:1rem;border-radius:4px;text-align:center;font-size:1.5rem;font-weight:bold;letter-spacing:2px;">
        {$otp}
    </div>
    <p>This code expires in 10 minutes.</p>
    <p>If you did not request this code, please ignore this email.</p>
</body>
</html>
HTML;
    }
}
