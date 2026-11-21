<?php

namespace BizProfile\Tests\Unit;

use BizProfile\Tests\TestSuite;

class CoreDatabaseTest extends TestSuite
{
    public function testDatabaseConnection():
    {
        $this->assertNotNull($this->pdo, 'Database connection should be established');
    }
    
    public function testUsersTableExists():
    {
        $this->assertTrue($this->tableExists('users'), 'Users table should exist');
    }
    
    public function testWebsiteSettingsTableExists():
    {
        $this->assertTrue($this->tableExists('website_settings'), 'Website settings table should exist');
    }
    
    public function function testInsertUser():
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('testpassword', PASSWORD_BCRYPT),
            'role' => 'editor',
            'status' => 'active'
        ];
        
        $userId = $this->insertTestData('users', $userData);
        
        $this->assertGreaterThan(0, $userId, 'User should be inserted with valid ID');
        
        $result = $this->executeQuery('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
        $this->assertCount(1, $result, 'User should be retrievable by ID');
        $this->assertEquals('Test User', $result[0]['name']);
        $this->assertEquals('test@example.com', $result[0]['email']);
    }
    
    public function testInsertWebsiteSettings():
    {
        $settingsData = [
            'company_name' => 'Test Company',
            'footer' => '© 2024 Test Company',
            'address' => '123 Test Street',
            'phone' => '+1234567890',
            'email' => 'test@example.com',
            'theme' => 'blue_corporate'
        ];
        
        $settingsId = $this->insertTestData('website_settings', $settingsData);
        
        $this->assertGreaterThan(0, $settingsId, 'Settings should be inserted with valid ID');
        
        $result = $this->executeQuery('SELECT * FROM website_settings WHERE id = :id', ['id' => $settingsId]);
        $this->assertCount(1, $result, 'Settings should be retrievable by ID');
        $this->assertEquals('Test Company', $result[0]['company_name']);
    }
    
    public function testDatabaseTransaction():
    {
        $this->assertNotNull($this->pdo, 'Database connection should exist');
        
        // Start transaction
        $this->pdo->beginTransaction();
        
        try {
            // Insert a user within transaction
            $userData = [
                'name' => 'Transaction Test User',
                'email' => 'transaction@test.com',
                'password' => password_hash('testpassword', PASSWORD_BCRYPT),
                'role' => 'editor',
                'status' => 'active'
            ];
            
            $userId = $this->insertTestData('users', $userData);
            
            // Verify user was inserted
            $result = $this->executeQuery('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
            $this->assertCount(1, $result, 'User should exist within transaction');
            
            // Rollback transaction
            $this->pdo->rollBack();
            
            // Verify user was rolled back
            $result = $this->executeQuery('SELECT * FROM users WHERE id = :id', ['id' => $userId]);
            $this->assertCount(0, $result, 'User should not exist after rollback');
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function testEmailUniqueness():
    {
        // Insert first user
        $userData1 = [
            'name' => 'User One',
            'email' => 'unique@test.com',
            'password' => password_hash('testpassword', PASSWORD_BCRYPT),
            'role' => 'editor',
            'status' => 'active'
        ];
        
        $this->insertTestData('users', $userData1);
        
        // Try to insert second user with same email
        $userData2 = $userData1;
        $userData2['name'] = 'User Two';
        
        $this->expectException(PDOException::class);
        $this->insertTestData('users', $userData2);
    }
    
    public function testUserRoles():
    {
        $roles = ['super_admin', 'admin', 'editor'];
        
        foreach ($roles as $role) {
            $userData = [
                'name' => "Test {$role}",
                'email' => "{$role}@test.com",
                'password' => password_hash('testpassword', PASSWORD_BCRYPT),
                'role' => $role,
                'status' => 'active'
            ];
            
            $userId = $this->insertTestData('users', $userData);
            $this->assertGreaterThan(0, $userId, "User with role '{$role}' should be inserted");
        }
        
        // Verify all roles exist
        $result = $this->executeQuery('SELECT COUNT(*) as count FROM users WHERE role = :role', ['role' => 'super_admin']);
        $this->assertGreaterThan(0, $result[0]['count'], 'At least one super_admin should exist');
        
        $result = $this->executeQuery('SELECT COUNT(*) as count FROM users WHERE role = :role', ['role' => 'admin']);
        $this->assertGreaterThan(0, $result[0]['count'], 'At least one admin should exist');
        
        $result = $this->executeQuery('SELECT COUNT(*) as count FROM users WHERE role = :role', ['role' => 'editor']);
        $this->assertGreaterThan(0, $result[0]['count'], 'At least one editor should exist');
    }
}
