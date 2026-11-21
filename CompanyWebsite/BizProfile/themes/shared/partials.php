<?php
// Shared partials for all themes.
// Provides renderPage($template, $data) which wraps content in a theme-aware layout.
// Each theme defines THEME_PRIMARY / THEME_SECONDARY constants before including this file.

if (!defined('THEME_PRIMARY')) {
    define('THEME_PRIMARY', '#007bff');
}
if (!defined('THEME_SECONDARY')) {
    define('THEME_SECONDARY', '#6c757d');
}
if (!defined('THEME_NAME')) {
    define('THEME_NAME', 'default');
}

function bp_render_page(string $content, array $data = []): void
{
    $settings = $data['settings'] ?? [];
    $companyName = $settings['company_name'] ?? 'BizProfile';
    $pageTitle = $data['pageTitle'] ?? $companyName;
    $primary = THEME_PRIMARY;
    $secondary = THEME_SECONDARY;
    $navLinks = [
        '/about' => 'About',
        '/services' => 'Services',
        '/products' => 'Products',
        '/blog' => 'News',
        '/gallery' => 'Gallery',
        '/careers' => 'Careers',
        '/contact' => 'Contact',
    ];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($settings['meta_description'] ?? $companyName); ?>">
        <style>
            :root { --primary: <?php echo $primary; ?>; --secondary: <?php echo $secondary; ?>; }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; line-height: 1.6; }
            header.site-header { background: var(--primary); color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
            header.site-header a.brand { color: #fff; text-decoration: none; font-weight: bold; font-size: 1.2rem; }
            nav.main-nav a { color: #fff; text-decoration: none; margin-left: 1.25rem; }
            nav.main-nav a:hover { text-decoration: underline; }
            main.site-main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
            footer.site-footer { background: #f8f9fa; text-align: center; padding: 2rem; margin-top: 3rem; color: #666; }
            .btn { display: inline-block; background: var(--primary); color: #fff; padding: 0.6rem 1.4rem; border-radius: 6px; text-decoration: none; }
            .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
            .card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 1.25rem; }
            .card h3 { margin-bottom: 0.5rem; }
        </style>
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="/"><?php echo htmlspecialchars($companyName); ?></a>
            <nav class="main-nav">
                <?php foreach ($navLinks as $url => $label): ?>
                    <a href="<?php echo $url; ?>"><?php echo htmlspecialchars($label); ?></a>
                <?php endforeach; ?>
            </nav>
        </header>
        <main class="site-main">
            <?php echo $content; ?>
        </main>
        <footer class="site-footer">
            <?php echo htmlspecialchars($settings['footer'] ?? "© " . date('Y') . " $companyName"); ?>
        </footer>
    </body>
    </html>
    <?php
}
