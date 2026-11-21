<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $this->view('admin/dashboard', [
            'stats' => $this->getDashboardStats(),
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function profile(): void
    {
        $this->ensureLoggedIn();
        
        $userId = $_SESSION['user_id'];
        $user = Database::query("SELECT * FROM users WHERE id = ? LIMIT 1", [$userId]);
        
        $this->adminView('admin/profile', [
            'user' => $user[0] ?? [],
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function updateProfile(): void
    {
        $this->ensureLoggedIn();
        
        $userId = $_SESSION['user_id'];
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/profile');
        }
        
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'Name and email are required';
            $this->redirect('/admin/profile');
        }
        
        // Check if email is already taken
        $existingUser = Database::query("SELECT * FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $userId]);
        
        if ($existingUser) {
            $_SESSION['error'] = 'Email already taken';
            $this->redirect('/admin/profile');
        }
        
        Database::execute(
            "UPDATE users SET name = ?, email = ? WHERE id = ?",
            [$name, $email, $userId]
        );
        
        $_SESSION['success'] = 'Profile updated successfully';
        $this->redirect('/admin/profile');
    }
    
    public function changePassword(): void
    {
        $this->ensureLoggedIn();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/profile');
        }
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = 'All password fields are required';
            $this->redirect('/admin/profile');
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'New passwords do not match';
            $this->redirect('/admin/profile');
        }
        
        $userId = $_SESSION['user_id'];
        $user = Database::query("SELECT * FROM users WHERE id = ? LIMIT 1", [$userId]);
        
        if (!$user || !Security::verifyPassword($currentPassword, $user[0]['password'])) {
            $_SESSION['error'] = 'Current password is incorrect';
            $this->redirect('/admin/profile');
        }
        
        $hashedNewPassword = Security::hashPassword($newPassword);
        
        Database::execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashedNewPassword, $userId]
        );
        
        // Invalidate all existing sessions
        Database::execute(
            "UPDATE user_sessions SET last_activity = NOW() WHERE user_id = ?",
            [$userId]
        );
        
        $_SESSION['success'] = 'Password changed successfully';
        $this->redirect('/admin/profile');
    }
    
    public function settings(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $settings = Database::query("SELECT * FROM website_settings LIMIT 1");
        
        $this->adminView('admin/settings', [
            'settings' => $settings[0] ?? [],
            'error' => $_SESSION['error'] ?? null,
            'success' => $_SESSION['success'] ?? null
        ]);
        
        // Clear session messages
        unset($_SESSION['error'], $_SESSION['success']);
    }
    
    public function updateSettings(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/settings');
        }
        
        $allowedSettings = [
            'company_name', 'logo', 'favicon', 'footer', 'address', 'phone', 'email',
            'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'google_map', 'theme', 'maintenance_mode'
        ];
        
        $updateData = [];
        foreach ($allowedSettings as $setting) {
            if (isset($_POST[$setting])) {
                $updateData[$setting] = $_POST[$setting];
            }
        }
        
        if (!empty($updateData)) {
            // Get current settings for history
            $currentSettings = Database::query("SELECT * FROM website_settings LIMIT 1");
            if ($currentSettings) {
                foreach ($updateData as $key => $value) {
                    if (isset($currentSettings[0][$key])) {
                        Database::execute(
                            "INSERT INTO settings_history (user_id, setting_key, old_value, new_value) VALUES (?, ?, ?, ?)",
                            [
                                $_SESSION['user_id'],
                                $key,
                                $currentSettings[0][$key],
                                $value
                            ]
                        );
                    }
                }
            }
            
            $updateFields = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($updateData)));
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            Database::execute(
                "UPDATE website_settings SET $updateFields WHERE id = 1",
                array_values($updateData)
            );
            
            // Log the action
            Database::execute(
                "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
                [
                    $_SESSION['user_id'],
                    'settings_update',
                    'Website settings updated',
                    $_SERVER['REMOTE_ADDR']
                ]
            );
            
            $_SESSION['success'] = 'Settings updated successfully';
        }
        
        $this->redirect('/admin/settings');
    }
    
    public function logs(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $page = $_GET['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $logs = Database::query(
            "SELECT * FROM logs ORDER BY created_at DESC LIMIT ?, ?",
            [$offset, $limit]
        );
        
        $totalLogs = Database::query("SELECT COUNT(*) as count FROM logs")[0]['count'] ?? 0;
        $totalPages = ceil($totalLogs / $limit);
        
        $this->adminView('admin/logs', [
            'logs' => $logs,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function activityLogs(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $page = $_GET['page'] ?? 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $logs = Database::query(
            "SELECT al.*, u.name as user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ?, ?",
            [$offset, $limit]
        );
        
        $totalLogs = Database::query("SELECT COUNT(*) as count FROM activity_logs")[0]['count'] ?? 0;
        $totalPages = ceil($totalLogs / $limit);
        
        $this->adminView('admin/activity_logs', [
            'logs' => $logs,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    private function ensureLoggedIn(): void
    {
        if (!isLoggedIn()) {
            $this->redirect('/admin/login');
        }
    }
    
    private function ensureAdminRole(): void
    {
        if (!isAdmin()) {
            $this->redirect('/admin/dashboard');
        }
    }
    
    private function getDashboardStats(): array
    {
        return [
            'total_users' => Database::query("SELECT COUNT(*) as count FROM users")[0]['count'] ?? 0,
            'total_products' => Database::query("SELECT COUNT(*) as count FROM products")[0]['count'] ?? 0,
            'total_visitors' => Database::query("SELECT COUNT(*) as count FROM visitors")[0]['count'] ?? 0,
            'total_enquiries' => Database::query("SELECT COUNT(*) as count FROM enquiries")[0]['count'] ?? 0,
            'active_sessions' => Database::query("SELECT COUNT(*) as count FROM user_sessions WHERE DATE(created_at) = CURDATE()")[0]['count'] ?? 0,
            'recent_activity' => Database::query("SELECT al.*, u.name as user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")[0] ?? []
        ];
    }
}