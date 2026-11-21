<?php

use PDO;

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize database for testing
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

function getDbConnection()
{
    $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'] . ";charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function setupTestDatabase()
{
    $pdo = getDbConnection();
    
    // Create test database if it doesn't exist
    $testDb = $_ENV['DB_DATABASE'] . '_test';
    
    $sql = "CREATE DATABASE IF NOT EXISTS {$testDb}";
    $pdo->exec($sql);
    
    // Switch to test database
    $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $testDb . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set global test connection
    global $testPdo;
    $testPdo = $pdo;
    
    // Run migrations from database/bizprofile.sql
    $sqlFile = __DIR__ . '/../database/bizprofile.sql';
    if (file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);
        
        // Split SQL by semicolon and execute each statement
        $statements = preg_split('/;(\s*)/', $sqlContent);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Continue on error for testing purposes
                    error_log("SQL Error: " . $e->getMessage() . "\nStatement: " . $statement . "\n");
                }
            }
        }
    }
    
    // Insert seed data
    insertTestSeedData($pdo);
}

function insertTestSeedData($pdo)
{
    try {
        // Insert test users
        $pdo->exec("INSERT INTO users (name, email, password, role, status) VALUES ('Test User', 'test@example.com', '\$2y\$10\$' . substr(md5(rand()), 0, 22), 'editor', 'active')");
        
        // Insert test products
        $pdo->exec("INSERT INTO products (name, slug, description, category_id, price, featured, status) VALUES ('Test Product', 'test-product', 'Test Description', 1, '99.99', 1, 'active')");
        
        // Insert test testimonials
        $pdo->exec("INSERT INTO testimonials (name, company, content, status) VALUES ('Test User', 'Test Company', 'Test Testimonial', 'active')");
        
    } catch (PDOException $e) {
        error_log("Seed data error: " . $e->getMessage());
    }
}

function getTestConnection()
{
    global $testPdo;
    return $testPdo;
}

// Initialize test database
if (!defined('TEST_INITIALIZED')) {
    setupTestDatabase();
    define('TEST_INITIALIZED', true);
}