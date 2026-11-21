<?php

namespace BizProfile\Core;

class Security
{
    // CSRF Protection
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Input Sanitization
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeArray(array $input): array
    {
        return array_map([self::class, 'sanitize'], $input);
    }
    
    // Password Security
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    // File Upload Validation
    public static function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 10485760): array
    {
        $errors = [];
        
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'No file uploaded';
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error';
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type';
        }
        
        return $errors;
    }
    
    // JWT Authentication
    public static function generateJwtToken(array $payload, string $secret = null): string
    {
        $secret = $secret ?? ($_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production');
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(array_merge($payload, ['iat' => time(), 'exp' => time() + 3600])));
        $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $signature = base64_encode($signature);
        return "$header.$payload.$signature";
    }
    
    public static function verifyJwtToken(string $token, string $secret = null): ?array
    {
        $secret = $secret ?? ($_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production');
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$header, $payload, $signature] = $parts;
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', "$header.$payload", $secret, true);
        $expectedSignature = base64_encode($expectedSignature);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }
        
        $payload = json_decode(base64_decode($payload), true);
        
        // Check expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    // API Rate Limiting
    public static function rateLimit(string $key, int $maxAttempts = 100, int $window = 3600): bool
    {
        $cacheKey = 'rate_limit_' . md5($key);
        $currentTime = time();
        
        // Use Cache system if available
        if (class_exists('BizProfile\Core\Cache')) {
            $cache = new Cache();
            $attempts = $cache->get($cacheKey, $window) ?? [];
            
            // Remove expired attempts
            $attempts = array_filter($attempts, fn($time) => $time > $currentTime - $window);
            
            if (count($attempts) >= $maxAttempts) {
                return false;
            }
            
            $attempts[] = $currentTime;
            $cache->set($cacheKey, $attempts, $window);
            return true;
        }
        
        // Fallback to file-based rate limiting
        $cacheFile = BASE_PATH . '/storage/cache/' . md5($cacheKey) . '.json';
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $data = array_filter($data, fn($time) => $time > $currentTime - $window);
            
            if (count($data) >= $maxAttempts) {
                return false;
            }
            
            $data[] = $currentTime;
        } else {
            $data = [$currentTime];
        }
        
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($data));
        return true;
    }
    
    // CORS Support
    public static function cors(string $origin = '*'): void
    {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    // Security Headers
    public static function securityHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self'; frame-ancestors 'self';";
        header("Content-Security-Policy: $csp");
    }
    
    // API Key Management
    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    public static function validateApiKey(string $key): bool
    {
        try {
            $db = Database::getInstance();
            $result = $db->query("SELECT * FROM api_keys WHERE key = ? AND (last_used IS NULL OR last_used > DATE_SUB(NOW(), INTERVAL 1 HOUR))", [$key]);
            return !empty($result);
        } catch (Exception $e) {
            error_log("API key validation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Input Validation
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function validatePhone(string $phone): bool
    {
        return preg_match('/^\+?[0-9]{10,15}$/', $phone);
    }
    
    // Session Security
    public static function regenerateSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}