<?php

namespace BizProfile\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        $theme = $this->getCurrentTheme();
        $viewFile = BASE_PATH . '/themes/' . $theme . '/' . $template . '.php';
        if (!file_exists($viewFile)) {
            $viewFile = BASE_PATH . '/themes/default/' . $template . '.php';
        }

        extract($data);
        $settings = $data['settings'] ?? [];
        $companyName = $settings['company_name'] ?? 'BizProfile';
        $pageTitle = ucfirst($template) . ' - ' . $companyName;

        ob_start();
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            $sharedPages = BASE_PATH . '/themes/shared/pages.php';
            if (file_exists($sharedPages)) {
                require_once $sharedPages;
                $renderer = 'bp_page_' . $template;
                if (function_exists($renderer)) {
                    echo $renderer($data);
                } else {
                    echo '<p>No content available for this page.</p>';
                }
            } else {
                echo '<p>No content available for this page.</p>';
            }
        }
        $content = ob_get_clean();

        $layoutFile = BASE_PATH . '/themes/' . $theme . '/layout.php';
        if (!file_exists($layoutFile)) {
            $layoutFile = BASE_PATH . '/themes/default/layout.php';
        }
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }
    
    protected function adminView(string $template, array $data = []): void
    {
        $viewFile = BASE_PATH . '/admin/' . $template . '.php';
        
        if (!file_exists($viewFile)) {
            $viewFile = BASE_PATH . '/admin/views/' . $template . '.php';
        }
        
        extract($data);
        ob_start();
        include $viewFile;
        echo ob_get_clean();
    }
    
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
    
    protected function getCurrentTheme(): string
    {
        $settings = Database::query("SELECT theme FROM website_settings LIMIT 1");
        return $settings[0]['theme'] ?? 'blue_corporate';
    }
    
    protected function getWebsiteSettings(): array
    {
        $settings = Database::query("SELECT * FROM website_settings LIMIT 1");
        return $settings[0] ?? [];
    }
}