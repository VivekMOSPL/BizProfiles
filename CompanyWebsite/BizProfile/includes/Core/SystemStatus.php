<?php

namespace BizProfile\Core;

use PDO;
use Exception;

/**
 * System Status and Monitoring Class
 * Provides comprehensive system health monitoring for BizProfile
 */
class SystemStatus
{
    private PDO $pdo;
    private array $config;
    private array $cacheDir;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->cacheDir = $config['cache_dir'] ?? 'storage/cache';
        
        // Initialize database connection
        $this->initializeDatabase();
    }
    
    /**
     * Initialize database connection for monitoring
     */
    private function initializeDatabase(): void
    {
        $dbConfig = $this->config['database'] ?? [];
        $host = $dbConfig['host'] ?? getenv('DB_HOST', 'localhost');
        $database = $dbConfig['database'] ?? getenv('DB_DATABASE', 'bizprofile');
        $username = $dbConfig['username'] ?? getenv('DB_USERNAME', 'root');
        $password = $dbConfig['password'] ?? getenv('DB_PASSWORD', '');
        
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->pdo = new PDO($dsn, $username, $password, $options);
    }
    
    /**
     * Get overall system status
     */
    public function getOverallStatus(): array
    {
        $status = [
            'status' => 'healthy',
            'message' => 'All systems operational',
            'checks' => []
        ];
        
        $checks = [
            'database' => [$this, 'checkDatabase'],
            'files' => [$this, 'checkFileSystem'],
            'cache' => [$this, 'checkCache'],
            'uploads' => [$this, 'checkUploads'],
            'api' => [$this, 'checkApi'],
            'theme' => [$this, 'checkThemeSystem'],
            'php' => [$this, 'checkPhpVersion'],
            'security' => [$this, 'checkSecurity']
        ];
        
        $overallHealthy = true;
        
        foreach ($checks as $checkName => $checkMethod) {
            try {
                $checkResult = $checkMethod();
                $status['checks'][$checkName] = $checkResult;
                
                if (!$checkResult['healthy']) {
                    $overallHealthy = false;
                    $status['status'] = 'degraded';
                }
                
                if ($checkResult['critical']) {
                    $overallHealthy = false;
                    $status['status'] = 'critical';
                }
                
                if (!$checkResult['message']) {
                    $status['message'] = "{$checkName} check failed";
                }
                
            } catch (Exception $e) {
                $status['checks'][$checkName] = [
                    'healthy' => false,
                    'critical' => true,
                    'message' => $e->getMessage(),
                    'details' => $this->getErrorDetails($e)
                ];
                
                $overallHealthy = false;
                $status['status'] = 'critical';
                $status['message'] = "Critical failure in {$checkName}: " . $e->getMessage();
            }
        }
        
        $status['healthy'] = $overallHealthy;
        $status['timestamp'] = date('c');
        $status['version'] = $this->getVersion();
        
        return $status;
    }
    
    /**
     * Check database health
     */
    private function checkDatabase(): array
    {
        $startTime = microtime(true);
        
        try {
            $latency = $this->measureDatabaseLatency();
            $tableCount = $this->getTableCount();
            $connectionTest = $this->testDatabaseConnection();
            
            $healthy = $latency['avg_ms'] < 100 && $connectionTest['success'];
            $critical = $latency['avg_ms'] > 500 || !$connectionTest['success'];
            
            return [
                'healthy' => $healthy,
                'critical' => $critical,
                'message' => "Database operational ({$latency['avg_ms']:.2f}ms avg latency)",
                'details' => [
                    'latency' => $latency,
                    'table_count' => $tableCount,
                    'connection' => $connectionTest
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'critical' => true,
                'message' => $e->getMessage(),
                'details' => $this->getErrorDetails($e)
            ];
        }
    }
    
    /**
     * Measure database latency
     */
    private function measureDatabaseLatency(): array
    {
        $latencies = [];
        
        for ($i = 0; $i < 5; $i++) {
            $start = microtime(true);
            $this->pdo->query("SELECT 1");
            $latencies[] = (microtime(true) - $start) * 1000; // Convert to milliseconds
        }
        
        return [
            'min_ms' => min($latencies),
            'max_ms' => max($latencies),
            'avg_ms' => array_sum($latencies) / count($latencies),
            'queries_tested' => count($latencies)
        ];
    }
    
    /**
     * Get table count
     */
    private function getTableCount(): int
    {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->rowCount();
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT VERSION()");
            $version = $stmt->fetchColumn();
            return [
                'success' => true,
                'version' => $version
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check file system
     */
    private function checkFileSystem(): array
    {
        $requiredDirs = ['storage/cache', 'storage/logs', 'storage/uploads', 'themes'];
        $missingDirs = [];
        $issues = [];
        
        foreach ($requiredDirs as $dir) {
            $path = dirname(__DIR__) . "/{$dir}";
            if (!is_dir($path)) {
                $missingDirs[] = $dir;
            } else {
                // Check directory permissions
                if (!is_writable($path)) {
                    $issues[] = "Directory {$dir} is not writable";
                }
            }
        }
        
        $healthy = count($missingDirs) === 0 && count($issues) === 0;
        $critical = count($missingDirs) > 0;
        
        $message = $healthy ? 'File system OK' : 
            ($critical ? 'Missing required directories' : 'File system issues found');
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $message,
            'details' => [
                'missing_directories' => $missingDirs,
                'permission_issues' => $issues,
                'checked_directories' => $requiredDirs
            ]
        ];
    }
    
    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        $cachePath = $this->cacheDir;
        $cacheFiles = 0;
        $cacheSize = 0;
        
        if (is_dir($cachePath)) {
            $files = glob($cachePath . '/*.cache') ?: [];
            $cacheFiles = count($files);
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $cacheSize += filesize($file);
                }
            }
        }
        
        $cacheSizeMB = $cacheSize / (1024 * 1024);
        $healthy = $cacheFiles >= 0; // Cache can be empty
        $critical = $cacheSizeMB > 100; // Warning if cache is too large
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => "Cache system OK ({$cacheFiles} files, {$cacheSizeMB:.2f} MB)",
            'details' => [
                'cache_files' => $cacheFiles,
                'cache_size_mb' => $cacheSizeMB,
                'cache_path' => $cachePath
            ]
        ];
    }
    
    /**
     * Check uploads directory
     */
    private function checkUploads(): array
    {
        $uploadPath = 'storage/uploads';
        $path = dirname(__DIR__) . "/{$uploadPath}";
        
        if (!is_dir($path)) {
            return [
                'healthy' => false,
                'critical' => false,
                'message' => 'Uploads directory not found',
                'details' => ['path' => $path]
            ];
        }
        
        // Check for old files (older than 7 days)
        $files = glob($path . '/*.tmp') ?: [];
        $oldFiles = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < time() - (7 * 24 * 3600)) {
                $oldFiles++;
            }
        }
        
        $healthy = true;
        $critical = $oldFiles > 100; // Warning if too many old files
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => "Uploads directory OK ({$oldFiles} old temporary files)",
            'details' => [
                'path' => $path,
                'old_temp_files' => $oldFiles,
                'writable' => is_writable($path)
            ]
        ];
    }
    
    /**
     * Check API endpoints
     */
    private function checkApi(): array
    {
        $endpoints = ['/api/v1/products', '/api/v1/services', '/api/v1/news'];
        $failedEndpoints = [];
        
        foreach ($endpoints as $endpoint) {
            try {
                $response = $this->curlApiCall('GET', $endpoint);
                if ($response['http_code'] >= 400) {
                    $failedEndpoints[] = $endpoint;
                }
            } catch (Exception $e) {
                $failedEndpoints[] = $endpoint;
            }
        }
        
        $healthy = count($failedEndpoints) === 0;
        $critical = count($failedEndpoints) > ($endpointsCount = count($endpoints) * 0.5);
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $healthy ? 'API endpoints OK' : 'Some API endpoints failed',
            'details' => [
                'total_endpoints' => count($endpoints),
                'failed_endpoints' => $failedEndpoints,
                'success_rate' => ($endpointsCount - count($failedEndpoints)) / $endpointsCount * 100
            ]
        ];
    }
    
    /**
     * Check theme system
     */
    private function checkThemeSystem(): array
    {
        $themePath = dirname(__DIR__) . '/themes';
        $themes = ['blue_corporate', 'dark_professional', 'green_business', 'orange_startup', 'purple_modern'];
        $missingThemes = [];
        
        foreach ($themes as $theme) {
            $themeDir = "{$themePath}/{$theme}";
            if (!is_dir($themeDir) || !file_exists("{$themeDir}/style.css")) {
                $missingThemes[] = $theme;
            }
        }
        
        $healthy = count($missingThemes) === 0;
        $critical = count($missingThemes) > 0;
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $healthy ? 'Theme system OK' : 'Some themes are missing',
            'details' => [
                'total_themes' => count($themes),
                'missing_themes' => $missingThemes,
                'theme_path' => $themePath
            ]
        ];
    }
    
    /**
     * Check PHP version
     */
    private function checkPhpVersion(): array
    {
        $requiredVersion = '8.1';
        $currentVersion = PHP_VERSION;
        
        // Compare versions
        $versionParts = explode('.', $currentVersion);
        $requiredParts = explode('.', $requiredVersion);
        
        $majorMatch = (int)$versionParts[0] >= (int)$requiredParts[0];
        $minorMatch = (int)$versionParts[1] >= (int)$requiredParts[1];
        
        $healthy = $majorMatch && $minorMatch;
        $critical = $majorMatch && !$minorMatch;
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => "PHP {$currentVersion} (required: {$requiredVersion}+)",
            'details' => [
                'current_version' => $currentVersion,
                'required_version' => $requiredVersion,
                'major_match' => $majorMatch,
                'minor_match' => $minorMatch
            ]
        ];
    }
    
    /**
     * Check security settings
     */
    private function checkSecurity(): array
    {
        $securityChecks = [
            'ssl_enabled' => $this->checkSsl(),
            'security_headers' => $this->checkSecurityHeaders(),
            'file_permissions' => $this->checkFilePermissions(),
            'database_encryption' => $this->checkDatabaseEncryption()
        ];
        
        $healthy = true;
        $critical = false;
        $messages = [];
        
        foreach ($securityChecks as $checkName => $result) {
            if (isset($result['healthy'])) {
                if (!$result['healthy']) {
                    $healthy = false;
                }
                if ($result['critical'] ?? false) {
                    $critical = true;
                }
                $messages[] = "{$checkName}: " . ($result['message'] ?? 'Unknown');
            }
        }
        
        $message = implode(', ', $messages);
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $message ?: 'Security checks completed',
            'details' => $securityChecks
        ];
    }
    
    /**
     * Check SSL
     */
    private function checkSsl(): array
    {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   $_SERVER['SERVER_PORT'] === '443';
        
        return [
            'healthy' => $isHttps,
            'critical' => !$isHttps,
            'message' => $isHttps ? 'HTTPS enabled' : 'HTTPS not detected',
            'details' => ['is_https' => $isHttps, 'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown']
        ];
    }
    
    /**
     * Check security headers
     */
    private function checkSecurityHeaders(): array
    {
        $requiredHeaders = ['X-Content-Type-Options', 'X-Frame-Options', 'X-XSS-Protection'];
        $missingHeaders = [];
        
        foreach ($requiredHeaders as $header) {
            if (!getenv($header) && !apache_getenv($header, true)) {
                $missingHeaders[] = $header;
            }
        }
        
        $healthy = count($missingHeaders) === 0;
        $critical = $healthy ? false : true; // Security headers are important
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $healthy ? 'Security headers present' : 'Missing security headers',
            'details' => ['missing_headers' => $missingHeaders]
        ];
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions(): array
    {
        $criticalFiles = ['.env', 'php.ini'];
        $missingPermissions = [];
        
        foreach ($criticalFiles as $file) {
            $path = dirname(__DIR__) . "/{$file}";
            if (file_exists($path) && !is_writable($path)) {
                $missingPermissions[] = "{$file} is not writable";
            }
        }
        
        $healthy = count($missingPermissions) === 0;
        $critical = count($missingPermissions) > 0;
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $healthy ? 'File permissions OK' : 'Some files have incorrect permissions',
            'details' => ['permission_issues' => $missingPermissions]
        ];
    }
    
    /**
     * Check database encryption
     */
    private function checkDatabaseEncryption(): array
    {
        // Check if database encryption is configured
        $encryptionEnabled = getenv('DB_ENCRYPTION') === 'true' || 
                            getenv('MYSQL_ALLOW_CRYPTO_PLAINTEXT') === 'false';
        
        $healthy = $encryptionEnabled;
        $critical = !$encryptionEnabled;
        
        return [
            'healthy' => $healthy,
            'critical' => $critical,
            'message' => $healthy ? 'Database encryption enabled' : 'Database encryption not configured',
            'details' => ['encryption_enabled' => $encryptionEnabled]
        ];
    }
    
    /**
     * Make API curl call
     */
    private function curlApiCall(string $method, string $endpoint): array
    {
        $ch = curl_init();
        
        $baseUrl = $this->config['app_url'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
                   $_SERVER['HTTP_HOST'] . '/';
        $url = $baseUrl . ltrim($endpoint, '/');
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("API call failed: {$error}");
        }
        
        return [
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    /**
     * Get error details
     */
    private function getErrorDetails(Exception $e): array
    {
        return [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    /**
     * Get version information
     */
    private function getVersion(): string
    {
        $versionFile = dirname(__DIR__) . '/version.txt';
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        return '1.0.0';
    }
    
    /**
     * Get system status for API response
     */
    public function getApiStatus(): array
    {
        $status = $this->getOverallStatus();
        
        // Format for API response
        return [
            'status' => $status['status'],
            'message' => $status['message'],
            'timestamp' => $status['timestamp'],
            'version' => $status['version'],
            'checks' => $status['checks'],
            'healthy' => $status['healthy']
        ];
    }
    
    /**
     * Get detailed system status
     */
    public function getDetailedStatus(): array
    {
        $status = $this->getOverallStatus();
        
        return [
            'system_status' => $status,
            'database_info' => $this->getDatabaseInfo(),
            'file_system_info' => $this->getFileSystemInfo(),
            'api_info' => $this->getApiInfo()
        ];
    }
    
    /**
     * Get database information
     */
    private function getDatabaseInfo(): array
    {
        try {
            $latency = $this->measureDatabaseLatency();
            $tableCount = $this->getTableCount();
            $connectionTest = $this->testDatabaseConnection();
            
            return [
                'latency_ms' => $latency,
                'table_count' => $tableCount,
                'connection' => $connectionTest,
                'server_info' => $this->pdo->query("SELECT VERSION() as version, @@version as mysql_version")->fetchAll()
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get file system information
     */
    private function getFileSystemInfo(): array
    {
        $info = [];
        
        $directories = ['storage/cache', 'storage/logs', 'storage/uploads', 'themes'];
        
        foreach ($directories as $dir) {
            $path = dirname(__DIR__) . "/{$dir}";
            if (is_dir($path)) {
                $files = glob("$path/*") ?: [];
                $info[$dir] = [
                    'exists' => true,
                    'file_count' => count($files),
                    'writable' => is_writable($path),
                    'size_bytes' => array_sum(array_map('filesize', $files))
                ];
            } else {
                $info[$dir] = [
                    'exists' => false,
                    'file_count' => 0,
                    'writable' => false,
                    'size_bytes' => 0
                ];
            }
        }
        
        return $info;
    }
    
    /**
     * Get API information
     */
    private function getApiInfo(): array
    {
        return [
            'base_url' => $this->config['app_url'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
                        $_SERVER['HTTP_HOST'] . '/',
            'endpoints' => [
                '/api/v1/products' => 'GET - Product listing',
                '/api/v1/services' => 'GET - Services listing',
                '/api/v1/news' => 'GET - News and announcements',
                '/api/v1/testimonials' => 'GET - Testimonials',
                '/api/v1/gallery' => 'GET - Gallery images',
                '/api/v1/careers' => 'GET - Job listings',
                '/api/v1/contact' => 'POST - Contact form submission',
                '/api/v1/newsletter' => 'POST - Newsletter subscription',
                '/api/v1/search' => 'GET - Global search'
            ]
        ];
    }
}
