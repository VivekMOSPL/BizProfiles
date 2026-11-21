<?php
// Blue Corporate theme layout
// Variables available: $pageTitle, $content (HTML string), $settings
$settings = $settings ?? [];
$primaryColor = '#007bff';
$secondaryColor = '#6c757d';
$companyName = $settings['company_name'] ?? 'BizProfile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? $companyName); ?></title>
    <style>
        :root { --primary: <?php echo $primaryColor; ?>; --secondary: <?php echo $secondaryColor; ?>; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #333; line-height: 1.6; }
        header { background: var(--primary); color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 1.5rem; }
        .nav a:hover { text-decoration: underline; }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        footer { background: #f8f9fa; text-align: center; padding: 2rem; margin-top: 3rem; color: #666; }
        .btn { display: inline-block; background: var(--primary); color: #fff; padding: 0.6rem 1.4rem; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <strong><?php echo htmlspecialchars($companyName); ?></strong>
        <nav class="nav">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/services">Services</a>
            <a href="/products">Products</a>
            <a href="/blog">News</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>
    <main>
        <?php echo $content ?? ''; ?>
    </main>
    <footer>
        <?php echo htmlspecialchars($settings['footer'] ?? "© " . date('Y') . " $companyName"); ?>
    </footer>
</body>
</html>
