<?php
/**
 * BizProfile Sample Data Seeder
 *
 * Inserts idempotent sample data for development/demo purposes.
 * Safe to re-run: uses INSERT IGNORE / WHERE NOT EXISTS patterns.
 */

$dotenv = __DIR__ . '/../../.env';
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

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/Core/Database.php';

use BizProfile\Core\Database;

$pdo = Database::getInstance();

function tableExists($pdo, string $table): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        return $stmt->fetch() !== false;
    } catch (\PDOException $e) {
        return false;
    }
}

function seedIfNotExists($pdo, string $table, array $columns, array $values): void
{
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $colList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
    $checkCols = implode(' AND ', array_map(fn($c) => "`{$c}` = ?", $columns));

    $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$checkCols} LIMIT 1");
    $stmt->execute($values);
    $exists = $stmt->fetch();
    if ($exists) {
        echo "  SKIP {$table}: already exists\n";
        return;
    }

    $sql = "INSERT INTO {$table} ({$colList}) VALUES ({$placeholders})";
    try {
        $pdo->prepare($sql)->execute($values);
        echo "  OK {$table}: inserted\n";
    } catch (\PDOException $e) {
        echo "  ERR {$table}: " . $e->getMessage() . "\n";
    }
}

echo "=== BizProfile Sample Data Seeder ===\n\n";

// Users
if (tableExists($pdo, 'users')) {
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    seedIfNotExists($pdo, 'users',
        ['name', 'email', 'password', 'role', 'status'],
        ['Demo Admin', 'admin@bizprofile.com', $passwordHash, 'super_admin', 'active']);
    seedIfNotExists($pdo, 'users',
        ['name', 'email', 'password', 'role', 'status'],
        ['Demo Editor', 'editor@bizprofile.com', $passwordHash, 'editor', 'active']);
} else {
    echo "SKIP users: table not found\n";
}

// Website settings
if (tableExists($pdo, 'website_settings')) {
    seedIfNotExists($pdo, 'website_settings',
        ['company_name', 'email', 'phone', 'address', 'theme', 'maintenance_mode'],
        ['Acme Corporation', 'info@acme.com', '+1 555-0100', '123 Business Ave, Suite 100', 'blue_corporate', 0]);
} else {
    echo "SKIP website_settings: table not found\n";
}

// Pages
if (tableExists($pdo, 'pages')) {
    seedIfNotExists($pdo, 'pages',
        ['title', 'slug', 'content', 'status', 'sort_order'],
        ['Home', 'home', '<h1>Welcome to BizProfile</h1><p>Your business website is ready.</p>', 'active', 1]);
    seedIfNotExists($pdo, 'pages',
        ['title', 'slug', 'content', 'status', 'sort_order'],
        ['About Us', 'about-us', '<h1>About Us</h1><p>We build great business websites.</p>', 'active', 2]);
    seedIfNotExists($pdo, 'pages',
        ['title', 'slug', 'content', 'status', 'sort_order'],
        ['Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with our team.</p>', 'active', 3]);
} else {
    echo "SKIP pages: table not found\n";
}

// Product categories
if (tableExists($pdo, 'product_categories')) {
    seedIfNotExists($pdo, 'product_categories',
        ['name', 'slug', 'description', 'status', 'sort_order'],
        ['Web Design', 'web-design', 'Professional website design services', 'active', 1]);
    seedIfNotExists($pdo, 'product_categories',
        ['name', 'slug', 'description', 'status', 'sort_order'],
        ['Hosting', 'hosting', 'Reliable hosting solutions', 'active', 2]);
    seedIfNotExists($pdo, 'product_categories',
        ['name', 'slug', 'description', 'status', 'sort_order'],
        ['Maintenance', 'maintenance', 'Ongoing site maintenance', 'active', 3]);
} else {
    echo "SKIP product_categories: table not found\n";
}

// Products
if (tableExists($pdo, 'products')) {
    $webDesignCat = $pdo->query("SELECT id FROM product_categories WHERE slug = 'web-design' LIMIT 1")->fetch();
    $hostingCat = $pdo->query("SELECT id FROM product_categories WHERE slug = 'hosting' LIMIT 1")->fetch();

    $cat1Id = $webDesignCat ? $webDesignCat['id'] : null;
    $cat2Id = $hostingCat ? $hostingCat['id'] : null;

    seedIfNotExists($pdo, 'products',
        ['name', 'slug', 'description', 'category_id', 'price', 'featured', 'status', 'sort_order'],
        ['Starter Website', 'starter-website', 'Perfect for small businesses', $cat1Id, 499.00, 1, 'active', 1]);
    seedIfNotExists($pdo, 'products',
        ['name', 'slug', 'description', 'category_id', 'price', 'featured', 'status', 'sort_order'],
        ['Business Website', 'business-website', 'Full-featured business site', $cat1Id, 999.00, 0, 'active', 2]);
    seedIfNotExists($pdo, 'products',
        ['name', 'slug', 'description', 'category_id', 'price', 'featured', 'status', 'sort_order'],
        ['Monthly Hosting', 'monthly-hosting', 'Reliable monthly hosting', $cat2Id, 29.99, 0, 'active', 3]);
} else {
    echo "SKIP products: table not found\n";
}

// Activity logs (sample audit entries)
if (tableExists($pdo, 'activity_logs')) {
    seedIfNotExists($pdo, 'activity_logs',
        ['user_id', 'action', 'description', 'ip_address'],
        [1, 'system_seeded', 'Sample data seeded for development', '127.0.0.1']);
} else {
    echo "SKIP activity_logs: table not found\n";
}

echo "\n=== Sample data seeding complete ===\n";
