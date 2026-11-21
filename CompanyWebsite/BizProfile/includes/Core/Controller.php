<?php

namespace BizProfile\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        $viewFile = BASE_PATH . '/themes/' . $this->getCurrentTheme() . '/' . $template . '.php';
        
        if (!file_exists($viewFile)) {
            $viewFile = BASE_PATH . '/themes/default/' . $template . '.php';
        }
        
        extract($data);
        ob_start();
        include $viewFile;
        echo ob_get_clean();
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