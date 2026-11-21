<?php

namespace BizProfile\Controllers;

use BizProfile\Core\SystemStatus;
use BizProfile\Core\Controller;

class SystemStatusController extends Controller
{
    private SystemStatus $systemStatus;
    
    public function __construct()
    {
        parent::__construct();
        $this->systemStatus = new SystemStatus([
            'cache_dir' => 'storage/cache',
            'database' => [
                'host' => getenv('DB_HOST', 'localhost'),
                'database' => getenv('DB_DATABASE', 'bizprofile'),
                'username' => getenv('DB_USERNAME', 'root'),
                'password' => getenv('DB_PASSWORD', '')
            ],
            'app_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
                         $_SERVER['HTTP_HOST'] . '/'
        ]);
    }
    
    public function index(): void
    {
        $status = $this->systemStatus->getApiStatus();
        
        $this->response->json($status);
    }
    
    public function detailed(): void
    {
        $status = $this->systemStatus->getDetailedStatus();
        
        $this->response->json($status);
    }
    
    public function health(): void
    {
        $status = $this->systemStatus->getOverallStatus();
        
        // Set appropriate HTTP status code based on health
        $httpStatus = $status['status'] === 'critical' ? 503 : 
                     ($status['status'] === 'degraded' ? 200 : 200);
        
        $this->response->setHttpStatus($httpStatus);
        $this->response->json($status);
    }
    
    public function database(): void
    {
        $status = $this->systemStatus->getDetailedStatus();
        
        $this->response->json($status['database_info']);
    }
    
    public function filesystem(): void
    {
        $status = $this->systemStatus->getDetailedStatus();
        
        $this->response->json($status['file_system_info']);
    }
    
    public function api(): void
    {
        $status = $this->systemStatus->getDetailedStatus();
        
        $this->response->json($status['api_info']);
    }
    
    public function logs(): void
    {
        $logPath = 'storage/logs';
        $logFiles = glob("$logPath/*.log") ?: [];
        
        $logs = [];
        foreach ($logFiles as $logFile) {
            $logs[] = [
                'filename' => basename($logFile),
                'path' => $logFile,
                'size' => filesize($logFile),
                'modified' => filemtime($logFile),
                'preview' => $this->getLogPreview($logFile)
            ];
        }
        
        $this->response->json([
            'log_files' => $logs,
            'log_path' => $logPath,
            'total_logs' => count($logs)
        ]);
    }
    
    public function cache(): void
    {
        $cachePath = 'storage/cache';
        $cacheFiles = glob("$cachePath/*.cache") ?: [];
        
        $cacheInfo = [];
        foreach ($cacheFiles as $cacheFile) {
            $cacheInfo[] = [
                'filename' => basename($cacheFile),
                'path' => $cacheFile,
                'size' => filesize($cacheFile),
                'modified' => filemtime($cacheFile)
            ];
        }
        
        $this->response->json([
            'cache_files' => $cacheInfo,
            'cache_path' => $cachePath,
            'total_cache_files' => count($cacheFiles)
        ]);
    }
    
    public function cleanup(): void
    {
        $cleanupResults = [
            'logs' => $this->cleanupLogs(),
            'cache' => $this->cleanupCache(),
            'uploads' => $this->cleanupUploads()
        ];
        
        $this->response->json([
            'cleanup_results' => $cleanupResults,
            'timestamp' => date('c')
        ]);
    }
    
    private function getLogPreview(string $logFile, int $lines = 10): array
    {
        if (!file_exists($logFile)) {
            return ['error' => 'Log file not found'];
        }
        
        $content = file_get_contents($logFile);
        $lines = explode(PHP_EOL, $content);
        
        return array_slice($lines, -min(count($lines), $lines), $lines);
    }
    
    private function cleanupLogs(): array
    {
        $logPath = 'storage/logs';
        $cleaned = 0;
        
        if (is_dir($logPath)) {
            $files = glob("$logPath/*.log") ?: [];
            
            foreach ($files as $file) {
                if (filemtime($file) < time() - (7 * 24 * 3600)) { // 7 days
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
        }
        
        return [
            'status' => 'completed',
            'files_cleaned' => $cleaned,
            'path' => $logPath
        ];
    }
    
    private function cleanupCache(): array
    {
        $cachePath = 'storage/cache';
        $cleaned = 0;
        
        if (is_dir($cachePath)) {
            $files = glob("$cachePath/*.cache") ?: [];
            
            foreach ($files as $file) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return [
            'status' => 'completed',
            'files_cleaned' => $cleaned,
            'path' => $cachePath
        ];
    }
    
    private function cleanupUploads(): array
    {
        $uploadPath = 'storage/uploads';
        $cleaned = 0;
        
        if (is_dir($uploadPath)) {
            $files = glob("$uploadPath/*.tmp") ?: [];
            
            foreach ($files as $file) {
                if (filemtime($file) < time() - (1 * 24 * 3600)) { // 1 day
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
        }
        
        return [
            'status' => 'completed',
            'files_cleaned' => $cleaned,
            'path' => $uploadPath
        ];
    }
}
