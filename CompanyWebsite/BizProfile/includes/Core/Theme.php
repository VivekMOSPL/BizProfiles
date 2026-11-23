<?php

namespace BizProfile\Core;

class Theme
{
    private array $themes;
    private string $currentTheme;
    private string $cacheDir;
    
    public function __construct()
    {
        $this->cacheDir = BASE_PATH . '/storage/cache/' ;
        
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        $this->loadThemes();
        $this->loadCurrentTheme();
    }
    
    private function loadThemes(): void
    {
        $this->themes = [
            'blue_corporate' => [
                'name' => 'Blue Corporate',
                'description' => 'Professional blue theme for corporate websites',
                'colors' => '#007bff',
                'is_active' => true
            ],
            'dark_professional' => [
                'name' => 'Dark Professional',
                'description' => 'Dark theme for modern professional look',
                'colors' => '#0d6efd',
                'is_active' => true
            ],
            'green_business' => [
                'name' => 'Green Business',
                'description' => 'Green theme for eco-friendly businesses',
                'colors' => '#28a745',
                'is_active' => true
            ],
            'orange_startup' => [
                'name' => 'Orange Startup',
                'description' => 'Orange theme for energetic startups',
                'colors' => '#fd7e14',
                'is_active' => true
            ],
            'purple_modern' => [
                'name' => 'Purple Modern',
                'description' => 'Purple theme for creative businesses',
                'colors' => '#6f42c1',
                'is_active' => true
            ],
            'default' => [
                'name' => 'Default',
                'description' => 'Classic default theme',
                'colors' => '#6c757d',
                'is_active' => true
            ],
            'admin' => [
                'name' => 'Admin Dark',
                'description' => 'Dark theme optimized for admin panel',
                'colors' => '#212529',
                'is_active' => true
            ]
        ];
    }
    
    private function loadCurrentTheme(): void
    {
        // Load saved theme from session or database, fallback to default
        if (isset($_SESSION['theme'])) {
            $this->currentTheme = $_SESSION['theme'];
        } else {
            // Try to get from website settings
            try {
                $settings = Database::query("SELECT theme FROM website_settings LIMIT 1");
                $this->currentTheme = $settings[0]['theme'] ?? 'blue_corporate';
            } catch (Exception $e) {
                $this->currentTheme = 'blue_corporate';
            }
        }
        
        // Validate theme exists
        if (!isset($this->themes[$this->currentTheme])) {
            $this->currentTheme = 'blue_corporate';
        }
    }
    
    public function getTheme(string $themeName): array
    {
        return $this->themes[$themeName] ?? null;
    }
    
    public function getAvailableThemes(): array
    {
        return $this->themes;
    }
    
    public function getCurrentTheme(): array
    {
        return $this->themes[$this->currentTheme] ?? null;
    }
    
    public function setCurrentTheme(string $themeName): bool
    {
        if (!isset($this->themes[$themeName])) {
            return false;
        }
        
        $this->currentTheme = $themeName;
        
        // Save preference
        $_SESSION['theme'] = $themeName;
        
        // Save to database if user is logged in
        if (isset($_SESSION['user_id'])) {
            Database::execute(
                "UPDATE users SET theme_preference = ? WHERE id = ?",
                [$themeName, $_SESSION['user_id']]
            );
        }
        
        // Clear theme cache
        $cacheFile = $this->cacheDir . 'theme_' . md5($_SERVER['REMOTE_ADDR']) . '.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        return true;
    }
    
    public function saveUserTheme(string $themeName, int $userId): bool
    {
        if (!isset($this->themes[$themeName])) {
            return false;
        }
        
        // Update user's theme preference
        Database::execute(
            "UPDATE users SET theme_preference = ? WHERE id = ?",
            [$themeName, $userId]
        );
        
        // If this user is currently logged in, update their current theme
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $userId) {
            $this->currentTheme = $themeName;
            $_SESSION['theme'] = $themeName;
        }
        
        return true;
    }
    
    public function getThemeForUser(int $userId = null): string
    {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if ($userId) {
            $result = Database::query("SELECT theme_preference FROM users WHERE id = ?", [$userId]);
            if ($result && isset($result[0]['theme_preference'])) {
                $theme = $result[0]['theme_preference'];
                if (isset($this->themes[$theme])) {
                    return $theme;
                }
            }
        }
        
        return $this->currentTheme;
    }
    
    public function getThemesByCategory(string $category = 'professional'): array
    {
        $filteredThemes = [];
        
        foreach ($this->themes as $key => $theme) {
            switch ($category) {
                case 'corporate':
                    if (in_array($key, ['blue_corporate', 'dark_professional', 'green_business'])) {
                        $filteredThemes[$key] = $theme;
                    }
                    break;
                case 'creative':
                    if (in_array($key, ['orange_startup', 'purple_modern'])) {
                        $filteredThemes[$key] = $theme;
                    }
                    break;
                case 'minimal':
                    if (in_array($key, ['default', 'admin'])) {
                        $filteredThemes[$key] = $theme;
                    }
                    break;
                default:
                    $filteredThemes[$key] = $theme;
            }
        }
        
        return $filteredThemes;
    }
    
    public function createCustomTheme(string $name, string $baseTheme, array $customColors): array
    {
        $customKey = 'custom_' . md5($name);
        
        $this->themes[$customKey] = [
            'name' => $name,
            'description' => "Custom theme based on " . $this->themes[$baseTheme]['name'],
            'colors' => $customColors['primary'] ?? $this->themes[$baseTheme]['colors'],
            'is_active' => true,
            'is_custom' => true,
            'base_theme' => $baseTheme
        ];
        
        return $this->themes[$customKey];
    }
    
    public function getThemePath(string $themeName): string
    {
        return BASE_PATH . '/themes/' . $themeName . '/';
    }
    
    public function themeExists(string $themeName): bool
    {
        return isset($this->themes[$themeName]);
    }
    
    public function getPopularThemes(): array
    {
        // Return most popular themes based on usage
        $popularKeys = ['blue_corporate', 'dark_professional', 'default'];
        $popularThemes = [];
        
        foreach ($popularKeys as $key) {
            if (isset($this->themes[$key])) {
                $popularThemes[$key] = $this->themes[$key];
            }
        }
        
        return $popularThemes;
    }
    
    public function exportTheme(string $themeName): ?array
    {
        if (!isset($this->themes[$themeName])) {
            return null;
        }
        
        return [
            'name' => $this->themes[$themeName]['name'],
            'description' => $this->themes[$themeName]['description'],
            'colors' => $this->themes[$themeName]['colors'] ?? null,
            'is_custom' => $this->themes[$themeName]['is_custom'] ?? false,
            'base_theme' => $this->themes[$themeName]['base_theme'] ?? null
        ];
    }
    
    public function importTheme(array $themeData): bool
    {
        $themeKey = 'imported_' . md5($themeData['name']);
        
        if (isset($themeData['name']) && isset($themeData['description'])) {
            $this->themes[$themeKey] = [
                'name' => $themeData['name'],
                'description' => $themeData['description'],
                'colors' => $themeData['colors'] ?? '#007bff',
                'is_active' => true,
                'is_imported' => true
            ];
            
            return true;
        }
        
        return false;
    }
    
    public function getThemeDataCache(): ?array
    {
        $cacheKey = 'theme_data_' . md5(implode(',', array_keys($this->themes)));
        
        if (file_exists($this->cacheDir . $cacheKey . '.json')) {
            $cache = json_decode(file_get_contents($this->cacheDir . $cacheKey . '.json'), true);
            return $cache['data'] ?? null;
        }
        
        return null;
    }
    
    public function cacheThemeData(): bool
    {
        $themeData = [];
        
        foreach ($this->themes as $key => $theme) {
            $themeData[$key] = [
                'name' => $theme['name'],
                'description' => $theme['description'],
                'colors' => $theme['colors'] ?? null,
                'is_active' => $theme['is_active'] ?? true,
                'is_custom' => $theme['is_custom'] ?? false,
                'is_imported' => $theme['is_imported'] ?? false
            ];
        }
        
        $cacheData = [
            'timestamp' => time(),
            'data' => $themeData
        ];
        
        $cacheKey = 'theme_data_' . md5(implode(',', array_keys($themeData)));
        
        return file_put_contents($this->cacheDir . $cacheKey . '.json', json_encode($cacheData)) !== false;
    }
}