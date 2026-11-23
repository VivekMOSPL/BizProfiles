<?php

session_start();

define('BASE_PATH', __DIR__ . '/..');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);

// Security Headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS support for API
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if (APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self'; frame-ancestors 'self';");
}

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

spl_autoload_register(function ($class) {
    $prefix = 'BizProfile\\';
    $base_dir = BASE_PATH . '/includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

$dotenv = __DIR__ . '/../.env';
if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Core/Database.php';
require_once __DIR__ . '/Core/Request.php';
require_once __DIR__ . '/Core/Response.php';
require_once __DIR__ . '/Core/Router.php';
require_once __DIR__ . '/Core/Controller.php';
require_once __DIR__ . '/Core/Model.php';
require_once __DIR__ . '/Core/Security.php';
require_once __DIR__ . '/Core/Theme.php';
require_once __DIR__ . '/Core/ThemeSelector.php';
require_once __DIR__ . '/Core/InstallationStateService.php';
require_once __DIR__ . '/Core/SetupGate.php';
require_once __DIR__ . '/Core/OwnerClaimService.php';
require_once __DIR__ . '/Core/OtpChallengeService.php';
require_once __DIR__ . '/Core/MailTransport.php';
require_once __DIR__ . '/Core/Mail/MailDriverInterface.php';
require_once __DIR__ . '/Core/Mail/PhpMailerTransport.php';
require_once __DIR__ . '/Core/Mail/SmtpTransport.php';
require_once __DIR__ . '/Core/Mail/ApiTransport.php';
require_once __DIR__ . '/Core/Mail/MailManager.php';
require_once __DIR__ . '/Core/SetupDraftRepository.php';
require_once __DIR__ . '/Core/AuthSessionService.php';
require_once __DIR__ . '/Core/AuditService.php';

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = \BizProfile\Core\Security::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id'] ?? null);
    }
}