<?php

namespace BizProfile\Core;

use BizProfile\Core\Database;

class ThemeSelector
{
    private array $themes = [];
    private Cache $cache;
    
    public function __construct()
    {
        $this->cache = new Cache();
        $this->loadThemes();
    }
    
    private function loadThemes(): void
    {
        // Exactly 5 themes for web interface selection (as requested)
        $this->themes = [
            'blue_corporate' => [
                'name' => 'Blue Corporate',
                'description' => 'Professional blue theme for corporate websites',
                'preview' => '/themes/blue_corporate/preview.jpg',
                'tags' => ['corporate', 'professional', 'business'],
                'is_popular' => true,
                'created_at' => '2024-01-15',
                'updated_at' => '2024-01-15'
            ],
            'dark_professional' => [
                'name' => 'Dark Professional',
                'description' => 'Dark theme for modern professional look',
                'preview' => '/themes/dark_professional/preview.jpg',
                'tags' => ['modern', 'dark', 'minimal'],
                'is_popular' => true,
                'created_at' => '2024-01-15',
                'updated_at' => '2024-01-15'
            ],
            'green_business' => [
                'name' => 'Green Business',
                'description' => 'Green theme for eco-friendly businesses',
                'preview' => '/themes/green_business/preview.jpg',
                'tags' => ['eco', 'sustainable', 'green'],
                'is_popular' => false,
                'created_at' => '2024-01-15',
                'updated_at' => '2024-01-15'
            ],
            'purple_modern' => [
                'name' => 'Purple Modern',
                'description' => 'Purple theme for creative businesses',
                'preview' => '/themes/purple_modern/preview.jpg',
                'tags' => ['creative', 'tech', 'innovative'],
                'is_popular' => true,
                'created_at' => '2024-01-15',
                'updated_at' => '2024-01-15'
            ],
            'orange_startup' => [
                'name' => 'Orange Startup',
                'description' => 'Orange theme for energetic startups',
                'preview' => '/themes/orange_startup/preview.jpg',
                'tags' => ['startup', 'energetic', 'bold'],
                'is_popular' => false,
                'created_at' => '2024-01-15',
                'updated_at' => '2024-01-15'
            ]
        ];
    }
    
    public function getThemes(bool $includeCached = true): array
    {
        // Check cache first
        if ($includeCached) {
            $cachedThemes = $this->cache->get('themes_list');
            if ($cachedThemes !== null) {
                return $cachedThemes;
            }
        }
        
        // Filter themes (only active ones)
        $activeThemes = array_filter($this->themes, function($theme) {
            return $theme['is_active'] !== false;
        });
        
        // Sort by popularity and then by name
        uasort($activeThemes, function($a, $b) {
            if ($a['is_popular'] == $b['is_popular']) {
                return $a['name'] <=> $b['name'];
            }
            return $b['is_popular'] <=> $a['is_popular'];
        });
        
        // Cache the result
        $this->cache->set('themes_list', $activeThemes, 3600);
        
        return $activeThemes;
    }
    
    public function getTheme(string $themeName): ?array
    {
        return $this->themes[$themeName] ?? null;
    }
    
    public function getCurrentTheme(): string
    {
        if (isset($_SESSION['selected_theme'])) {
            return $_SESSION['selected_theme'];
        }
        
        // Try to get from database user preference
        if (isset($_SESSION['user_id'])) {
            try {
                $result = Database::query("SELECT theme_preference FROM users WHERE id = ?", [$_SESSION['user_id']]);
                if ($result && isset($result[0]['theme_preference'])) {
                    $theme = $result[0]['theme_preference'];
                    if (isset($this->themes[$theme])) {
                        $_SESSION['selected_theme'] = $theme;
                        return $theme;
                    }
                }
            } catch (Exception $e) {
                // Log error if needed
            }
        }
        
        // Fallback to default theme
        return 'blue_corporate';
    }
    
    public function selectTheme(string $themeName): bool
    {
        if (!isset($this->themes[$themeName])) {
            return false;
        }
        
        // Save to session
        $_SESSION['selected_theme'] = $themeName;
        
        // Save to database for logged-in users
        if (isset($_SESSION['user_id'])) {
            try {
                Database::execute("UPDATE users SET theme_preference = ? WHERE id = ?", [$themeName, $_SESSION['user_id']]);
            } catch (Exception $e) {
                // Log error if needed
            }
        }
        
        // Clear theme cache
        $cacheKey = 'user_theme_' . ($_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR']);
        $this->cache->delete($cacheKey);
        
        return true;
    }
    
    public function getUserSelectedTheme(int $userId = null): string
    {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if ($userId) {
            $cacheKey = 'user_theme_' . $userId;
            
            // Try to get from cache
            $cachedTheme = $this->cache->get($cacheKey);
            if ($cachedTheme !== null) {
                return $cachedTheme;
            }
            
            try {
                $result = Database::query("SELECT theme_preference FROM users WHERE id = ?", [$userId]);
                if ($result && isset($result[0]['theme_preference'])) {
                    $theme = $result[0]['theme_preference'];
                    if (isset($this->themes[$theme])) {
                        // Cache the result
                        $this->cache->set($cacheKey, $theme, 3600);
                        return $theme;
                    }
                }
            } catch (Exception $e) {
                // Log error if needed
            }
        }
        
        return $this->getCurrentTheme();
    }
    
    public function getThemesByTag(string $tag): array
    {
        $filteredThemes = [];
        
        foreach ($this->themes as $key => $theme) {
            if (in_array($tag, $theme['tags'])) {
                $filteredThemes[$key] = $theme;
            }
        }
        
        // Sort by popularity
        uasort($filteredThemes, function($a, $b) {
            return $b['is_popular'] <=> $a['is_popular'];
        });
        
        return $filteredThemes;
    }
    
    public function getFeaturedThemes(): array
    {
        return array_filter($this->themes, function($theme) {
            return $theme['is_popular'];
        });
    }
    
    public function getRecentThemes(): array
    {
        // For now, return popular themes as "recent"
        // In a full implementation, you'd track actual usage
        return array_slice($this->getFeaturedThemes(), 0, 3);
    }
    
    public function searchThemes(string $query): array
    {
        $searchResults = [];
        $query = strtolower($query);
        
        foreach ($this->themes as $key => $theme) {
            $matches = false;
            
            // Search in name
            if (strpos(strtolower($theme['name']), $query) !== false) {
                $matches = true;
            }
            
            // Search in description
            if (strpos(strtolower($theme['description']), $query) !== false) {
                $matches = true;
            }
            
            // Search in tags
            foreach ($theme['tags'] as $tag) {
                if (strpos($tag, $query) !== false) {
                    $matches = true;
                    break;
                }
            }
            
            if ($matches) {
                $searchResults[$key] = $theme;
            }
        }
        
        return $searchResults;
    }
    
    public function getThemeStats(): array
    {
        $stats = [
            'total_themes' => count($this->themes),
            'popular_themes' => count(array_filter($this->themes, fn($t) => $t['is_popular'])),
            'custom_themes' => 0, // Would be incremented if themes are created
            'most_used_theme' => 'blue_corporate', // Would be calculated from database in full implementation
            'theme_usage' => []
        ];
        
        return $stats;
    }
    
    public function exportThemes(): array
    {
        $exportedThemes = [];
        
        foreach ($this->themes as $key => $theme) {
            $exportedThemes[$key] = [
                'name' => $theme['name'],
                'description' => $theme['description'],
                'preview' => $theme['preview'] ?? null,
                'tags' => $theme['tags'] ?? [],
                'is_popular' => $theme['is_popular'] ?? false,
                'created_at' => $theme['created_at'] ?? date('Y-m-d'),
                'updated_at' => $theme['updated_at'] ?? date('Y-m-d')
            ];
        }
        
        return $exportedThemes;
    }
    
    public function importThemes(array $themeData): array
    {
        $importResults = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($themeData as $themeName => $themeData) {
            if (isset($themeData['name']) && isset($themeData['description'])) {
                $this->themes[$themeName] = [
                    'name' => $themeData['name'],
                    'description' => $themeData['description'],
                    'preview' => $themeData['preview'] ?? '/themes/default/preview.jpg',
                    'tags' => $themeData['tags'] ?? ['imported'],
                    'is_popular' => $themeData['is_popular'] ?? false,
                    'created_at' => $themeData['created_at'] ?? date('Y-m-d'),
                    'updated_at' => $themeData['updated_at'] ?? date('Y-m-d'),
                    'is_imported' => true
                ];
                
                $importResults['success']++;
            } else {
                $importResults['failed']++;
                $importResults['errors'][] = "Invalid theme data for $themeName";
            }
        }
        
        // Clear cache after import
        $this->cache->delete('themes_list');
        
        return $importResults;
    }
    
    public function upgradeTheme(string $themeName): bool
    {
        if (!isset($this->themes[$themeName])) {
            return false;
        }
        
        // Mark theme as popular (upgrade feature)
        $this->themes[$themeName]['is_popular'] = true;
        $this->themes[$themeName]['updated_at'] = date('Y-m-d H:i:s');
        
        // Clear cache
        $this->cache->delete('themes_list');
        
        return true;
    }
    
    public function backupThemes(string $backupDir): bool
    {
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/themes_backup_' . date('Y-m-d_H-i-s') . '.json';
        $themeData = $this->exportThemes();
        
        return file_put_contents($backupFile, json_encode($themeData, JSON_PRETTY_PRINT)) !== false;
    }
    
    public function restoreThemes(string $backupFile): bool
    {
        if (!file_exists($backupFile)) {
            return false;
        }
        
        $themeData = json_decode(file_get_contents($backupFile), true);
        
        if ($themeData === false) {
            return false;
        }
        
        foreach ($themeData as $themeName => $themeData) {
            $this->themes[$themeName] = [
                'name' => $themeData['name'],
                'description' => $themeData['description'],
                'preview' => $themeData['preview'] ?? null,
                'tags' => $themeData['tags'] ?? [],
                'is_popular' => $themeData['is_popular'] ?? false,
                'created_at' => $themeData['created_at'] ?? date('Y-m-d'),
                'updated_at' => $themeData['updated_at'] ?? date('Y-m-d'),
                'is_imported' => true
            ];
        }
        
        // Clear cache
        $this->cache->delete('themes_list');
        
        return true;
    }
    
    public function deleteTheme(string $themeName): bool
    {
        if (!isset($this->themes[$themeName]) || $themeName === 'blue_corporate') {
            return false; // Can't delete default theme
        }
        
        unset($this->themes[$themeName]);
        
        // Clear cache
        $this->cache->delete('themes_list');
        
        return true;
    }
    
    public function validateTheme(string $themeName, array $themeData): array
    {
        $errors = [];
        
        if (empty($themeData['name'])) {
            $errors[] = 'Theme name is required';
        }
        
        if (strlen($themeData['name']) < 2) {
            $errors[] = 'Theme name must be at least 2 characters';
        }
        
        if (empty($themeData['description'])) {
            $errors[] = 'Theme description is required';
        }
        
        if (isset($themeName) && $themeName !== 'new') {
            if (isset($this->themes[$themeName])) {
                $errors[] = 'Theme already exists';
            }
        }
        
        if (!isset($themeData['tags'])) {
            $errors[] = 'Theme tags are required';
        }
        
        return $errors;
    }
    
    public function getThemeDirectory(): string
    {
        return BASE_PATH . '/themes/';
    }
    
    public function getThemePreviewUrl(string $themeName): string
    {
        return '/themes/' . $themeName . '/preview.jpg';
    }
}