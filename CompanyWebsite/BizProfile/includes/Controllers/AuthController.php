<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class AuthController extends Controller
{
    public function login(): void
    {
        if (isLoggedIn()) {
            $this->redirect('/admin/dashboard');
        }
        
        $this->adminView('auth/login');
    }
    
    public function authenticate(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/login');
        }
        
        $user = Database::query("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
        
        if (!$user || !Security::verifyPassword($password, $user[0]['password'])) {
            $_SESSION['error'] = 'Invalid credentials';
            $this->redirect('/admin/login');
        }
        
        $_SESSION['user_id'] = $user[0]['id'];
        $_SESSION['user_name'] = $user[0]['name'];
        $_SESSION['user_email'] = $user[0]['email'];
        $_SESSION['user_role'] = $user[0]['role'];
        
        Database::execute(
            "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)",
            [$user[0]['id'], session_id(), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
        );
        
        Database::execute(
            "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
            [$user[0]['id'], 'login', 'User logged in', $_SERVER['REMOTE_ADDR']]
        );
        
        $this->redirect('/admin/dashboard');
    }
    
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            Database::execute(
                "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
                [$userId, 'logout', 'User logged out', $_SERVER['REMOTE_ADDR']]
            );
        }
        
        session_destroy();
        $this->redirect('/admin/login');
    }
}