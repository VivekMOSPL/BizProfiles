<?php

namespace BizProfile\Core;

// Initialize theme system
$themeSelector = new ThemeSelector();

// Get current theme
$currentTheme = $themeSelector->getCurrentTheme();

// Get all available themes
$availableThemes = $themeSelector->getThemes();

// Handle theme selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme_name'])) {
    $selectedTheme = $_POST['theme_name'];
    if ($themeSelector->selectTheme($selectedTheme)) {
        // Clear cache for immediate effect
        $cache = new Cache();
        $cache->delete('themes_list');
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Calculate theme stats
$themeStats = $themeSelector->getThemeStats();

// Get featured themes
$featuredThemes = $themeSelector->getFeaturedThemes();

// Get themes by category
$professionalThemes = $themeSelector->getThemesByTag('corporate');
$creativeThemes = $themeSelector->getThemesByTag('creative');
$minimalThemes = $themeSelector->getThemesByTag('minimal');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Selection - BizProfile Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
            font-size: 1rem;
        }

        .theme-sections {
            display: grid;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .theme-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .section-header h2 {
            color: #333;
            font-size: 1.8rem;
        }

        .themes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .theme-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .theme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .theme-card.selected {
            border-color: #667eea;
            background: linear-gradient(to bottom, rgba(102, 126, 234, 0.1), white);
        }

        .theme-preview {
            height: 150px;
            background: var(--theme-color, #007bff);
            position: relative;
            overflow: hidden;
        }

        .theme-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
        }

        .theme-info {
            padding: 1.5rem;
        }

        .theme-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .theme-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .theme-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .tag {
            background: #f0f0f0;
            color: #666;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .theme-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #888;
        }

        .popular-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ffd700;
            color: #333;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .selection-form {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e0e0e0;
        }

        .selected-theme-info {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .selected-theme-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .selected-theme-desc {
            color: #555;
            margin-bottom: 1.5rem;
        }

        .save-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .search-box {
            width: 100%;
            max-width: 400px;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }

        .no-themes {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .themes-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Theme Selector</h1>
            <p>Choose your preferred theme - Exactly 5 professional themes available</p>
        </div>

        <div class="stats-cards">
            <div class="stat-card">
                <h3><?php echo count($availableThemes); ?></h3>
                <p>Total Themes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($featuredThemes); ?></h3>
                <p>Featured Themes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $themeStats['popular_themes']; ?></h3>
                <p>Popular Themes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($professionalThemes); ?></h3>
                <p>Corporate Themes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($creativeThemes); ?></h3>
                <p>Creative Themes</p>
            </div>
        </div>

        <?php if ($currentTheme): ?>
        <div class="selected-theme-info">
            <div class="selected-theme-name">Current Theme: <?php echo $availableThemes[$currentTheme]['name'] ?? 'Unknown'; ?></div>
            <div class="selected-theme-desc">Description: <?php echo $availableThemes[$currentTheme]['description'] ?? ''; ?></div>
        </div>
        <?php endif; ?>

        <div class="theme-sections">
            <?php if ($featuredThemes): ?>
            <div class="theme-section">
                <div class="section-header">
                    <h2>⭐ Featured Themes</h2>
                    <span>Most popular and recommended choices</span>
                </div>
                <div class="themes-grid">
                    <?php foreach ($featuredThemes as $themeKey => $theme): ?>
                    <div class="theme-card <?php echo $themeKey === $currentTheme ? 'selected' : ''; ?>"
                         onclick="selectTheme('<?php echo $themeKey; ?>')">
                        <?php if ($theme['is_popular']): ?>
                        <div class="popular-badge">⭐ Featured</div>
                        <?php endif; ?>
                        <div class="theme-preview" style="--theme-color: <?php echo $theme['colors'] ?? '#007bff'; ?>;"></div>
                        <div class="theme-info">
                            <div class="theme-name"><?php echo $theme['name']; ?></div>
                            <div class="theme-description"><?php echo $theme['description']; ?></div>
                            <div class="theme-tags">
                                <?php foreach ($theme['tags'] as $tag): ?>
                                <span class="tag"><?php echo $tag; ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="theme-stats">
                                <span>Created: <?php echo $theme['created_at']; ?></span>
                                <span>Updated: <?php echo $theme['updated_at']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($professionalThemes): ?>
            <div class="theme-section">
                <div class="section-header">
                    <h2>🏢 Corporate Themes</h2>
                    <span>Professional and business-oriented designs</span>
                </div>
                <div class="themes-grid">
                    <?php foreach ($professionalThemes as $themeKey => $theme): ?>
                    <div class="theme-card <?php echo $themeKey === $currentTheme ? 'selected' : ''; ?>"
                         onclick="selectTheme('<?php echo $themeKey; ?>')">
                        <div class="theme-preview" style="--theme-color: <?php echo $theme['colors'] ?? '#007bff'; ?>;"></div>
                        <div class="theme-info">
                            <div class="theme-name"><?php echo $theme['name']; ?></div>
                            <div class="theme-description"><?php echo $theme['description']; ?></div>
                            <div class="theme-tags">
                                <?php foreach ($theme['tags'] as $tag): ?>
                                <span class="tag"><?php echo $tag; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($creativeThemes): ?>
            <div class="theme-section">
                <div class="section-header">
                    <h2>🎨 Creative Themes</h2>
                    <span>Modern and innovative designs</span>
                </div>
                <div class="themes-grid">
                    <?php foreach ($creativeThemes as $themeKey => $theme): ?>
                    <div class="theme-card <?php echo $themeKey === $currentTheme ? 'selected' : ''; ?>"
                         onclick="selectTheme('<?php echo $themeKey; ?>')">
                        <div class="theme-preview" style="--theme-color: <?php echo $theme['colors'] ?? '#6f42c1'; ?>;"></div>
                        <div class="theme-info">
                            <div class="theme-name"><?php echo $theme['name']; ?></div>
                            <div class="theme-description"><?php echo $theme['description']; ?></div>
                            <div class="theme-tags">
                                <?php foreach ($theme['tags'] as $tag): ?>
                                <span class="tag"><?php echo $tag; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="selection-form">
            <h3 style="margin-bottom: 1.5rem;">Theme Selection</h3>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="theme_name" id="selected_theme_hidden" value="<?php echo $currentTheme; ?>">
                
                <p style="color: #666; margin-bottom: 1rem;">Current theme: <strong><?php echo $availableThemes[$currentTheme]['name'] ?? 'Not selected'; ?></strong></p>
                
                <button type="submit" class="save-button">💾 Save Theme Selection</button>
            </form>
        </div>
    </div>

    <script>
        function selectTheme(themeName) {
            // Update selected theme visual
            document.querySelectorAll('.theme-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            const selectedCard = Array.from(document.querySelectorAll('.theme-card')).find(
                card => card.onclick.toString().includes(themeName)
            );
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Update hidden input
            document.getElementById('selected_theme_hidden').value = themeName;
        }

        // Add search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-box');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const query = e.target.value.toLowerCase();
                    const themeCards = document.querySelectorAll('.theme-card');
                    
                    themeCards.forEach(card => {
                        const themeName = card.querySelector('.theme-name').textContent.toLowerCase();
                        const themeDesc = card.querySelector('.theme-description').textContent.toLowerCase();
                        
                        if (themeName.includes(query) || themeDesc.includes(query)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>