<?php

namespace BizProfile\Core\Mail;

use BizProfile\Core\Mail\ApiTransport;
use BizProfile\Core\Mail\PhpMailerTransport;
use BizProfile\Core\Mail\SmtpTransport;

/**
 * MailManager
 *
 * Selects and instantiates the configured mail driver.
 *
 * Drivers:
 *   phpmailer (default) - PHPMailer-pattern SMTP via streams
 *   smtp                - PHP mail() fallback
 *   api                 - HTTP API provider (placeholder)
 *
 * Usage:
 *   $mailer = MailManager::make();
 *   $mailer->sendOtpEmail('user@example.com', '123456', 'owner_activation');
 */
class MailManager
{
    public const DRIVER_PHPMAILER = 'phpmailer';
    public const DRIVER_SMTP = 'smtp';
    public const DRIVER_API = 'api';

    private static array $drivers = [
        self::DRIVER_PHPMAILER => PhpMailerTransport::class,
        self::DRIVER_SMTP => SmtpTransport::class,
        self::DRIVER_API => ApiTransport::class,
    ];

    public static function make(?string $driver = null): MailDriverInterface
    {
        if ($driver === null) {
            $driver = $_ENV['MAIL_DRIVER'] ?? self::DRIVER_PHPMAILER;
        }

        $driver = strtolower(trim($driver));

        if (!isset(self::$drivers[$driver])) {
            error_log("MailManager: unknown driver '{$driver}', falling back to phpmailer");
            $driver = self::DRIVER_PHPMAILER;
        }

        $class = self::$drivers[$driver];
        return new $class();
    }

    public static function isDriverConfigured(?string $driver = null): bool
    {
        $mailer = self::make($driver);
        return $mailer->isConfigured();
    }
}
