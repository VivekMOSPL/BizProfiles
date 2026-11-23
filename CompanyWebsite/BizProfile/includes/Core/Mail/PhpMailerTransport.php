<?php

namespace BizProfile\Core\Mail;

/**
 * PhpMailerTransport
 *
 * Default mail driver following PHPMailer patterns using PHP streams.
 * Self-contained: no external composer dependency required.
 * When phpmailer/phpmailer is installed via composer, this can be swapped
 * for the official library without changing calling code.
 *
 * Supports: SMTP AUTH, STARTTLS, plain HTML MIME messages.
 */
class PhpMailerTransport implements MailDriverInterface
{
    private string $host = '';
    private int $port = 25;
    private string $username = '';
    private string $password = '';
    private string $encryption = '';
    private string $fromAddress = 'noreply@bizprofile.com';
    private string $fromName = 'BizProfile';

    public function __construct()
    {
        $this->host = $_ENV['MAIL_HOST'] ?? '';
        $this->port = (int) ($_ENV['MAIL_PORT'] ?? 25);
        $this->username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->encryption = $_ENV['MAIL_ENCRYPTION'] ?? '';
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bizprofile.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'BizProfile';
    }

    public function isConfigured(): bool
    {
        return !empty($this->host) && $this->port > 0;
    }

    public function sendOtpEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        return $this->send($toEmail, $this->getSubject($purpose), $this->getBody($otp, $purpose));
    }

    public function sendVerificationEmail(string $toEmail, string $otp, string $purpose = 'owner_activation'): bool
    {
        return $this->sendOtpEmail($toEmail, $otp, $purpose);
    }

    private function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        if (!$this->isConfigured()) {
            error_log("PhpMailerTransport: SMTP not configured; email not sent to {$toEmail}");
            return false;
        }

        $smtp = null;
        try {
            $scheme = '';
            if ($this->encryption === 'ssl') {
                $scheme = 'ssl://';
            }

            $address = $scheme . $this->host . ':' . $this->port;
            $smtp = @stream_socket_client($address, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);

            if (!$smtp) {
                error_log("PhpMailerTransport: connection failed to {$address}: {$errstr} ({$errno})");
                return false;
            }

            stream_set_timeout($smtp, 30);
            $this->readResponse($smtp); // greeting

            $this->sendCommand($smtp, "EHLO localhost\r\n");
            if ($this->encryption === 'tls') {
                $this->sendCommand($smtp, "STARTTLS\r\n");
                $ok = stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$ok) {
                    error_log("PhpMailerTransport: STARTTLS failed");
                    return false;
                }
                $this->readResponse($smtp);
            }

            if (!empty($this->username) && !empty($this->password)) {
                $this->sendCommand($smtp, "AUTH LOGIN\r\n");
                $this->sendCommand($smtp, base64_encode($this->username) . "\r\n");
                $this->sendCommand($smtp, base64_encode($this->password) . "\r\n");
            }

            $this->sendCommand($smtp, "MAIL FROM:<{$this->fromAddress}>\r\n");
            $this->sendCommand($smtp, "RCPT TO:<{$toEmail}>\r\n");
            $this->sendCommand($smtp, "DATA\r\n");

            $headers = $this->buildHeaders($toEmail, $subject);
            $message = $headers . "\r\n" . $htmlBody . "\r\n.";
            $this->sendCommand($smtp, $message . "\r\n");

            $this->sendCommand($smtp, "QUIT\r\n");
            @fclose($smtp);

            return true;
        } catch (\Throwable $e) {
            error_log("PhpMailerTransport: error for {$toEmail}: " . $e->getMessage());
            if ($smtp !== null) {
                @fclose($smtp);
            }
            return false;
        }
    }

    private function buildHeaders(string $toEmail, string $subject): string
    {
        return "From: {$this->fromName} <{$this->fromAddress}>\r\n"
            . "To: <{$toEmail}>\r\n"
            . "Subject: {$subject}\r\n"
            . "Reply-To: {$this->fromAddress}\r\n"
            . "X-Mailer: BizProfile-PhpMailer/1.0\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n";
    }

    private function sendCommand($smtp, string $command): void
    {
        if ($smtp === null) {
            throw new \RuntimeException('SMTP connection not established');
        }
        $written = fwrite($smtp, $command);
        if ($written === false || $written === 0) {
            throw new \RuntimeException('SMTP write failed');
        }
        $this->readResponse($smtp);
    }

    private function readResponse($smtp): string
    {
        if ($smtp === null) {
            throw new \RuntimeException('SMTP connection not established');
        }
        $response = '';
        do {
            $line = fgets($smtp, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
        } while (strlen($line) >= 4 && substr($line, 3, 1) !== ' ');

        if (strlen($response) >= 3 && (int) substr($response, 0, 3) >= 400) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }

        return $response;
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
