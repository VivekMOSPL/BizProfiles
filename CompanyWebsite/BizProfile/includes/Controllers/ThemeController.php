<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;
use BizProfile\Core\ThemeSelector;

class ThemeController extends Controller
{
    public function index(): void
    {
        $this->ensureLoggedIn();

        $themeSelector = new ThemeSelector();
        $themes = $themeSelector->getThemes();
        $currentTheme = $themeSelector->getCurrentTheme();

        $this->adminView('theme-selector', [
            'themes' => $themes,
            'currentTheme' => $currentTheme,
            'settings' => $this->getWebsiteSettings()
        ]);
    }

    public function select(): void
    {
        $this->ensureLoggedIn();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/theme');
        }

        $themeName = $_POST['theme_name'] ?? '';
        if (empty($themeName)) {
            $_SESSION['error'] = 'No theme selected';
            $this->redirect('/admin/theme');
        }

        $themeSelector = new ThemeSelector();
        if (!$themeSelector->selectTheme($themeName)) {
            $_SESSION['error'] = 'Invalid theme';
            $this->redirect('/admin/theme');
        }

        // Persist to website_settings so the public site uses it
        Database::execute(
            "UPDATE website_settings SET theme = ? WHERE id = 1",
            [$themeName]
        );

        Database::execute(
            "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
            [$_SESSION['user_id'], 'theme_change', "Theme changed to {$themeName}", $_SERVER['REMOTE_ADDR']]
        );

        $_SESSION['success'] = 'Theme saved successfully';
        $this->redirect('/admin/theme');
    }

    private function ensureLoggedIn(): void
    {
        if (!isLoggedIn()) {
            $this->redirect('/admin/login');
        }
    }
}
