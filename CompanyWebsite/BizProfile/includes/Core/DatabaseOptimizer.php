<?php

namespace BizProfile\Core;

class DatabaseOptimizer
{
    private Database $database;
    
    public function __construct()
    {
        $this->database = Database::getInstance();
    }
    
    public function optimizeTables(): array
    {
        $results = ['optimized' => [], 'errors' => []];
        
        $tables = [
            'users', 'website_settings', 'pages', 'products', 
            'product_categories', 'product_images', 'product_releases',
            'testimonials', 'clients', 'gallery', 'careers',
            'job_applications', 'enquiries', 'feedback', 'newsletter',
            'visitors', 'themes', 'sliders', 'menus', 'seo_settings',
            'media', 'logs', 'activity_logs', 'email_templates',
            'notifications', 'backup_logs', 'settings_history',
            'user_sessions', 'api_keys', 'product_attributes'
        ];
        
        foreach ($tables as $table) {
            try {
                $this->database->execute("OPTIMIZE TABLE `{$table}`");
                $results['optimized'][] = $table;
            } catch (Exception $e) {
                $results['errors'][] = "$table: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    public function analyzeTables(): array
    {
        $results = ['analyzed' => [], 'stats' => []];
        
        $tables = [
            'users', 'website_settings', 'pages', 'products', 
            'product_categories', 'product_images', 'product_releases',
            'testimonials', 'clients', 'gallery', 'careers',
            'job_applications', 'enquiries', 'feedback', 'newsletter',
            'visitors', 'themes', 'sliders', 'menus', 'seo_settings',
            'media', 'logs', 'activity_logs', 'email_templates',
            'notifications', 'backup_logs', 'settings_history',
            'user_sessions', 'api_keys', 'product_attributes'
        ];
        
        foreach ($tables as $table) {
            try {
                $analysis = $this->database->query("ANALYZE TABLE `{$table}`");
                $results['analyzed'][] = $table;
                $results['stats'][$table] = $analysis[0] ?? [];
            } catch (Exception $e) {
                $results['stats'][$table] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    public function vacuumTables(): array
    {
        $results = ['vacuumed' => [], 'errors' => []];
        
        $tables = [
            'logs', 'activity_logs', 'backup_logs', 'settings_history'
        ];
        
        foreach ($tables as $table) {
            try {
                // Remove old records (older than 90 days)
                $cutoffDate = date('Y-m-d', strtotime('-90 days'));
                $deleted = $this->database->execute(
                    "DELETE FROM `{$table}` WHERE created_at < ?",
                    [$cutoffDate]
                );
                
                $results['vacuumed'][] = "$table: $deleted records removed";
            } catch (Exception $e) {
                $results['errors'][] = "$table: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    public function getTableStats(string $table): array
    {
        try {
            return $this->database->query("SHOW TABLE STATUS LIKE ?", [$table])[0] ?? [];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    public function getDatabaseSize(): array
    {
        $tables = [
            'users', 'website_settings', 'pages', 'products', 
            'product_categories', 'product_images', 'product_releases',
            'testimonials', 'clients', 'gallery', 'careers',
            'job_applications', 'enquiries', 'feedback', 'newsletter',
            'visitors', 'themes', 'sliders', 'menus', 'seo_settings',
            'media', 'logs', 'activity_logs', 'email_templates',
            'notifications', 'backup_logs', 'settings_history',
            'user_sessions', 'api_keys', 'product_attributes'
        ];
        
        $sizeInfo = ['total_size' => 0, 'tables' => []];
        
        foreach ($tables as $table) {
            $stats = $this->getTableStats($table);
            if (isset($stats['Name'])) {
                $sizeInfo['tables'][$table] = [
                    'rows' => $stats['Rows'] ?? 0,
                    'size' => $stats['Data_length'] + $stats['Index_length'] ?? 0,
                    'engine' => $stats['Engine'] ?? 'unknown',
                    'collation' => $stats['Collation'] ?? 'unknown'
                ];
                $sizeInfo['total_size'] += $stats['Data_length'] + $stats['Index_length'] ?? 0;
            }
        }
        
        return $sizeInfo;
    }
    
    public function cleanupScheduled(): array
    {
        $results = [];
        
        // Clean up old logs
        $oldLogsDate = date('Y-m-d', strtotime('-365 days'));
        $logsCleaned = $this->database->execute(
            "DELETE FROM logs WHERE created_at < ?",
            [$oldLogsDate]
        );
        $results['logs_cleaned'] = $logsCleaned;
        
        // Clean up old sessions
        $oldSessionsDate = date('Y-m-d', strtotime('-30 days'));
        $sessionsCleaned = $this->database->execute(
            "DELETE FROM user_sessions WHERE last_activity < ?",
            [$oldSessionsDate]
        );
        $results['sessions_cleaned'] = $sessionsCleaned;
        
        // Clean up old notifications
        $oldNotificationsDate = date('Y-m-d', strtotime('-180 days'));
        $notificationsCleaned = $this->database->execute(
            "DELETE FROM notifications WHERE created_at < ? AND read = 1",
            [$oldNotificationsDate]
        );
        $results['notifications_cleaned'] = $notificationsCleaned;
        
        // Clean up old activity logs
        $oldActivityLogsDate = date('Y-m-d', strtotime('-90 days'));
        $activityLogsCleaned = $this->database->execute(
            "DELETE FROM activity_logs WHERE created_at < ?",
            [$oldActivityLogsDate]
        );
        $results['activity_logs_cleaned'] = $activityLogsCleaned;
        
        return $results;
    }
}