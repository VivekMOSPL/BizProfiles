<?php

namespace BizProfile\Core;

class PerformanceMonitor
{
    private array $metrics = [];
    private string $logFile;
    private Database $database;
    
    public function __construct()
    {
        $this->logFile = BASE_PATH . '/storage/logs/performance.log';
        $this->database = Database::getInstance();
    }
    
    public function startMonitoring(): void
    {
        $this->log('Performance monitoring started', 'INFO');
    }
    
    public function stopMonitoring(): void
    {
        $this->log('Performance monitoring stopped', 'INFO');
        $this->recordMetrics();
    }
    
    public function recordMetrics(): void
    {
        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_space' => $this->getDiskSpace(),
            'database_connections' => $this->getDatabaseConnections(),
            'response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'load_average' => $this->getLoadAverage(),
            'uptime' => $this->getSystemUptime()
        ];
        
        $this->metrics[] = $metrics;
        $this->log('Performance metrics recorded', 'INFO', $metrics);
        
        // Store in database for historical tracking
        $this->storeMetrics($metrics);
        
        // Clean old metrics
        $this->cleanOldMetrics();
    }
    
    public function getPerformanceReport(array $filters = []): array
    {
        $query = "SELECT * FROM performance_metrics WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (isset($filters['metric_type'])) {
            $query .= " AND metric_type = ?";
            $params[] = $filters['metric_type'];
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 1000";
        
        $metrics = $this->database->query($query, $params);
        
        return [
            'total_metrics' => count($metrics),
            'latest_metrics' => !empty($metrics) ? $metrics[0] : null,
            'average_memory' => $this->calculateAverage('memory_usage', $metrics),
            'average_cpu' => $this->calculateAverage('cpu_usage', $metrics),
            'average_response_time' => $this->calculateAverage('response_time', $metrics),
            'performance_trends' => $this->calculateTrends($metrics)
        ];
    }
    
    public function getRealTimeMetrics(): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage_mb' => round(memory_get_usage(true) / (1024 * 1024), 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / (1024 * 1024), 2),
            'cpu_usage_percent' => $this->getCpuUsage(),
            'disk_usage_percent' => $this->getDiskUsage(),
            'database_connections' => $this->getCurrentDatabaseConnections(),
            'active_users' => $this->getActiveUsers(),
            'requests_per_second' => $this->getRequestsPerSecond(),
            'cache_hits' => $this->getCacheHits(),
            'cache_misses' => $this->getCacheMisses(),
            'errors_per_minute' => $this->getErrorsPerMinute()
        ];
    }
    
    public function getSystemAlerts(): array
    {
        $alerts = [];
        $thresholds = $this->getPerformanceThresholds();
        
        $currentMetrics = $this->getRealTimeMetrics();
        
        // Memory usage alert
        if ($currentMetrics['memory_usage_mb'] > $thresholds['memory_mb'] * 0.9) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Memory usage is high: {$currentMetrics['memory_usage_mb']}MB",
                'metric' => 'memory',
                'value' => $currentMetrics['memory_usage_mb'],
                'threshold' => $thresholds['memory_mb'] * 0.9,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // CPU usage alert
        if ($currentMetrics['cpu_usage_percent'] > $thresholds['cpu_percent'] * 0.8) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "CPU usage is high: {$currentMetrics['cpu_usage_percent']}%",
                'metric' => 'cpu',
                'value' => $currentMetrics['cpu_usage_percent'],
                'threshold' => $thresholds['cpu_percent'] * 0.8,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // Disk space alert
        if ($currentMetrics['disk_usage_percent'] > $thresholds['disk_percent'] * 0.9) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Disk space is running low: {$currentMetrics['disk_usage_percent']}%",
                'metric' => 'disk',
                'value' => $currentMetrics['disk_usage_percent'],
                'threshold' => $thresholds['disk_percent'] * 0.9,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // Error rate alert
        $errorRate = $currentMetrics['errors_per_minute'];
        if ($errorRate > $thresholds['errors_per_minute']) {
            $alerts[] = [
                'type' => 'error',
                'message' => "Error rate is high: {$errorRate} errors per minute",
                'metric' => 'error_rate',
                'value' => $errorRate,
                'threshold' => $thresholds['errors_per_minute'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $alerts;
    }
    
    public function log(string $message, string $level = 'INFO', array $context = []): bool
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'process_id' => getmypid(),
            'memory_usage' => memory_get_usage(true),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Write to file
        $logLine = json_encode($logEntry) . "\n";
        $result = file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        return $result !== false;
    }
    
    public function cleanupLogs(int $daysToKeep = 30): bool
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-$daysToKeep days"));
            
            $this->database->execute("DELETE FROM system_logs WHERE created_at < ?", [$cutoffDate]);
            $this->database->execute("DELETE FROM performance_metrics WHERE created_at < ?", [$cutoffDate]);
            $this->database->execute("DELETE FROM error_logs WHERE created_at < ?", [$cutoffDate]);
            
            // Clean file-based logs
            if (is_file($this->logFile)) {
                $handle = fopen($this->logFile, 'r');
                $tempFile = $this->logFile . '.' . time();
                $output = fopen($tempFile, 'w');
                
                while ($line = fgets($handle)) {
                    $logEntry = json_decode($line, true);
                    if ($logEntry && $logEntry['timestamp'] >= $cutoffDate) {
                        fwrite($output, $line);
                    }
                }
                
                fclose($handle);
                fclose($output);
                rename($tempFile, $this->logFile);
            }
            
            return true;
        } catch (Exception $e) {
            $this->log("Log cleanup failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function getPerformanceThresholds(): array
    {
        return [
            'memory_mb' => 512, // MB
            'cpu_percent' => 80, // %
            'disk_percent' => 85, // %
            'errors_per_minute' => 5, // errors
            'response_time_ms' => 500, // milliseconds
            'database_latency_ms' => 100, // milliseconds
            'memory_usage_percent' => 90 // %
        ];
    }
    
    // Private helper methods
    private function getCpuUsage(): float
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $load = sys_getloadavg();
            return $load[0] * 100 / getmxcpu();
        } else {
            $load = sys_getloadavg();
            return $load[0] * 100 / (count(@file('/proc/cpuinfo')) / 10);
        }
    }
    
    private function getDiskSpace(): array
    {
        $diskFree = disk_free_space(BASE_PATH);
        $diskTotal = disk_total_space(BASE_PATH);
        $diskUsed = $diskTotal - $diskFree;
        $diskUsedPercent = ($diskUsed / $diskTotal) * 100;
        
        return [
            'free_gb' => round($diskFree / (1024 * 1024 * 1024), 2),
            'used_gb' => round($diskUsed / (1024 * 1024 * 1024), 2),
            'total_gb' => round($diskTotal / (1024 * 1024 * 1024), 2),
            'used_percent' => round($diskUsedPercent, 2)
        ];
    }
    
    private function getDiskUsage(): float
    {
        $diskFree = disk_free_space(BASE_PATH);
        $diskTotal = disk_total_space(BASE_PATH);
        return (($diskTotal - $diskFree) / $diskTotal) * 100;
    }
    
    private function getDatabaseConnections(): array
    {
        try {
            $result = $this->database->query("SHOW STATUS LIKE 'Threads_connected'");
            return $result[0]['Value'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCurrentDatabaseConnections(): int
    {
        try {
            $result = $this->database->query("SHOW STATUS LIKE 'Threads_connected'");
            return (int)($result[0]['Value'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getAverageResponseTime(): array
    {
        try {
            $result = $this->database->query(
                "SELECT AVG(execution_time) as avg_time FROM system_logs WHERE level = 'INFO' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            return $result[0] ?? ['avg_time' => 0];
        } catch (Exception $e) {
            return ['avg_time' => 0];
        }
    }
    
    private function getErrorRate(): float
    {
        try {
            $result = $this->database->query(
                "SELECT COUNT(*) as error_count FROM system_logs WHERE level IN ('ERROR', 'CRITICAL') AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $errorCount = $result[0]['error_count'] ?? 0;
            $totalRequests = $result[0]['total_count'] ?? 1;
            
            return ($errorCount / max($totalRequests, 1)) * 100;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCacheHitRate(): array
    {
        try {
            $result = $this->database->query("SHOW STATUS LIKE 'Cache_hits', LIKE 'Cache_misses'");
            $hits = 0;
            $misses = 0;
            
            foreach ($result as $row) {
                if ($row['Key_name'] === 'Cache_hits') {
                    $hits = $row['Value'];
                } elseif ($row['Key_name'] === 'Cache_misses') {
                    $misses = $row['Value'];
                }
            }
            
            $total = $hits + $misses;
            $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;
            
            return [
                'hits' => $hits,
                'misses' => $misses,
                'hit_rate_percent' => round($hitRate, 2)
            ];
        } catch (Exception $e) {
            return ['hits' => 0, 'misses' => 0, 'hit_rate_percent' => 0];
        }
    }
    
    private function getLoadAverage(): array
    {
        $load = sys_getloadavg();
        $cores = $this->getCpuCores();
        
        return [
            'load_1min' => round($load[0], 2),
            'load_5min' => round($load[1], 2),
            'load_15min' => round($load[2], 2),
            'cores' => $cores,
            'utilization_1min' => round(($load[0] / $cores) * 100, 2)
        ];
    }
    
    private function getSystemUptime(): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'Windows uptime requires separate tool';
        }
        
        $uptime = file_exists('/proc/uptime') ? file_get_contents('/proc/uptime') : null;
        
        if ($uptime !== null) {
            $uptime = trim($uptime);
            $uptime = substr($uptime, 0, strpos($uptime, ' '));
            $uptime_seconds = floor($uptime);
            $days = floor($uptime_seconds / 86400);
            $hours = floor(($uptime_seconds % 86400) / 3600);
            
            return sprintf("%dd %dh", $days, $hours);
        }
        
        return 'Unknown';
    }
    
    private function getCpuCores(): int
    {
        $cores = 1;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('wmic cpu get NumberOfCores /format:list', $output);
            foreach ($output as $line) {
                if (strpos($line, '=') !== false) {
                    $cores = (int) explode('=', $line)[1];
                    break;
                }
            }
        } else {
            $cores = (int) shell_exec('nproc');
            if ($cores === '') {
                $cores = (int) exec('sysctl -n hw.ncpu');
            }
        }
        
        return $cores;
    }
    
    private function getActiveUsers(): int
    {
        try {
            $result = $this->database->query(
                "SELECT COUNT(DISTINCT user_id) as active_users FROM activity_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            return $result[0]['active_users'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getRequestsPerSecond(): float
    {
        try {
            $result = $this->database->query(
                "SELECT COUNT(*) as requests FROM system_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
            );
            $requestCount = $result[0]['requests'] ?? 0;
            $seconds = 60;
            
            return $requestCount / $seconds;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCacheHits(): int
    {
        try {
            $result = $this->database->query("SHOW STATUS LIKE 'Cache_hits'");
            return $result[0]['Value'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCacheMisses(): int
    {
        try {
            $result = $this->database->query("SHOW STATUS LIKE 'Cache_misses'");
            return $result[0]['Value'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getErrorsPerMinute(): int
    {
        try {
            $result = $this->database->query(
                "SELECT COUNT(*) as error_count FROM system_logs WHERE level IN ('ERROR', 'CRITICAL') AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
            );
            return $result[0]['error_count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function storeMetrics(array $metrics): bool
    {
        try {
            $data = [
                'timestamp' => $metrics['timestamp'],
                'memory_usage' => $metrics['memory_usage'],
                'memory_peak' => $metrics['memory_peak'],
                'cpu_usage' => $metrics['cpu_usage'],
                'disk_space' => json_encode($metrics['disk_space']),
                'database_connections' => $metrics['database_connections'],
                'response_time' => $metrics['response_time'],
                'error_rate' => $metrics['error_rate'],
                'cache_hit_rate' => $metrics['cache_hit_rate'],
                'load_average' => json_encode($metrics['load_average']),
                'uptime' => $metrics['uptime']
            ];
            
            return $this->database->execute(
                "INSERT INTO performance_metrics (timestamp, memory_usage, memory_peak, cpu_usage, disk_space, database_connections, response_time, error_rate, cache_hit_rate, load_average, uptime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array_values($data)
            );
        } catch (Exception $e) {
            $this->log("Failed to store performance metrics: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    private function cleanOldMetrics(): void
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        $this->database->execute("DELETE FROM performance_metrics WHERE created_at < ?", [$cutoffDate]);
    }
    
    private function calculateAverage(string $field, array $metrics): float
    {
        $total = 0;
        $count = 0;
        
        foreach ($metrics as $metric) {
            if (isset($metric[$field])) {
                $total += $metric[$field];
                $count++;
            }
        }
        
        return $count > 0 ? $total / $count : 0;
    }
    
    private function calculateTrends(array $metrics): array
    {
        if (count($metrics) < 2) {
            return [];
        }
        
        $trends = [
            'memory' => $this->calculateTrend('memory_usage', $metrics),
            'cpu' => $this->calculateTrend('cpu_usage', $metrics),
            'response_time' => $this->calculateTrend('response_time', $metrics)
        ];
        
        return $trends;
    }
    
    private function calculateTrend(string $field, array $metrics): array
    {
        $current = $metrics[0][$field] ?? 0;
        $previous = $metrics[1][$field] ?? 0;
        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        
        return [
            'current' => $current,
            'previous' => $previous,
            'change_percent' => round($change, 2),
            'trend' => $change > 0 ? 'increasing' : 'decreasing'
        ];
    }
}