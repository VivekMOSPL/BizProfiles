<?php

namespace BizProfile\Tests;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base test class for BizProfile
 * Provides common test setup and utility methods
 */
abstract class TestSuite extends TestCase
{
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load environment variables
        $this->loadEnvironment();
        
        // Initialize database connection
        $this->setupDatabase();
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupDatabase();
        parent::tearDown();
    }
    
    /**
     * Load environment configuration
     */
    protected function loadEnvironment(): void
    {
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    putenv(trim($key) . '=' . trim($value));
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
    
    /**
     * Set up test database
     */
    protected function setupDatabase(): void
    {
        // Use test database configuration
        $dbHost = getenv('DB_HOST_TEST') ?: getenv('DB_HOST', 'localhost');
        $dbName = getenv('DB_DATABASE_TEST') ?: getenv('DB_DATABASE', 'bizprofile_test');
        $dbUser = getenv('DB_USERNAME_TEST') ?: getenv('DB_USERNAME', 'root');
        $dbPass = getenv('DB_PASSWORD_TEST') ?: getenv('DB_PASSWORD', '');
        
        try {
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            
            // Disable foreign key checks for test data cleanup
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            
        } catch (Exception $e) {
            $this->markTestSkipped("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Clean up test database
     */
    protected function cleanupDatabase(): void
    {
        if (isset($this->pdo)) {
            // Delete test data in reverse order of foreign key dependencies
            $tables = ['activity_logs', 'system_logs', 'product_images', 'product_releases', 
                      'careers', 'gallery', 'clients', 'sliders', 'testimonials', 
                      'products', 'product_categories', 'pages', 'website_settings', 
                      'users'];
            
            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    $this->pdo->exec("DELETE FROM {$table}");
                }
            }
            
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
    
    /**
     * Check if table exists
     */
    protected function tableExists(string $tableName): bool
    {
        if (!isset($this->pdo)) return false;
        
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :table");
        $stmt->bindValue(':table', $tableName);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Execute SQL query
     */
    protected function executeQuery(string $sql, array $params = []): array
    {
        if (!isset($this->pdo)) return [];
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        
        if (stripos($sql, 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        
        return [];
    }
    
    /**
     * Insert test data and return ID
     */
    protected function insertTestData(string $table, array $data): int
    {
        if (!isset($this->pdo)) return 0;
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Assert database state
     */
    protected function assertDatabaseState(string $table, int $expectedCount, string $condition = null): void
    {
        $this->assertTrue($this->tableExists($table), "Table {$table} does not exist");
        
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if ($condition) {
            $sql .= " WHERE {$condition}";
        }
        
        $result = $this->executeQuery($sql);
        $actualCount = $result[0]['count'] ?? 0;
        
        $this->assertEquals($expectedCount, $actualCount, 
            "Expected {$expectedCount} rows in {$table}, found {$actualCount}");
    }
}
