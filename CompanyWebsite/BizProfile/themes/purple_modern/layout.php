<?php
// Purple Modern theme layout
// Variables available: $pageTitle, $content (HTML string), $settings
$settings = $settings ?? [];
$primaryColor = '#6f42c1';
$secondaryColor = '#6c757d';
$bgColor = '#f3e8ff';
$cardColor = '#ffffff';
$textColor = '#5b21b6';
$mutedColor = '#6b7280';
$companyName = $settings['company_name'] ?? 'BizProfile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? $companyName); ?></title>
    <style>
        :root {
            --primary: <?php echo $primaryColor; ?>;
            --secondary: <?php echo $secondaryColor; ?>;
            --bg: <?php echo $bgColor; ?>;
            --card: <?php echo $cardColor; ?>;
            --text: <?php echo $textColor; ?>;
            --muted: <?php echo $mutedColor; ?>;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        header.site-header {
            background: #ffffff;
            border-bottom: 1px solid #e9d5ff;
            color: var(--text);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        header.site-header a.brand {
            color: var(--primary);
            text-decoration: none;
            font-weight: bold;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
        }
        nav.main-nav a {
            color: var(--muted);
            text-decoration: none;
            margin-left: 1.5rem;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        nav.main-nav a:hover { color: var(--primary); }
        main.site-main {
            max-width: 1100px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }
        footer.site-footer {
            background: #ffffff;
            border-top: 1px solid #e9d5ff;
            text-align: center;
            padding: 2.5rem;
            margin-top: 4rem;
            color: var(--muted);
        }
        .btn {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            padding: 0.65rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.75rem;
            margin-top: 1.5rem;
        }
        .card {
            background: var(--card);
            border: 1px solid #e9d5ff;
            border-radius: 10px;
            padding: 1.5rem;
            transition: transform 0.2s, border-color 0.2s;
        }
        .card:hover {
            transform: translateY(-3px);
            border-color: var(--primary);
        }
        .card h3 { color: var(--primary); margin-bottom: 0.5rem; }
        .card p { color: var(--muted); font-size: 0.95rem; }
        h1 { color: var(--text); font-size: 2rem; margin-bottom: 1rem; }
        h2 { color: var(--primary); font-size: 1.5rem; margin-top: 2.5rem; margin-bottom: 1rem; }
        p { color: var(--text); margin-bottom: 1rem; }
        .hero {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            border: 1px solid #e9d5ff;
            border-radius: 12px;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .hero h1 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--text); }
        .hero p { color: var(--text); font-size: 1.1rem; max-width: 700px; margin: 0 auto 1.5rem; }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/"><?php echo htmlspecialchars($companyName); ?></a>
        <nav class="main-nav">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/services">Services</a>
            <a href="/products">Products</a>
            <a href="/blog">News</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>
    <main class="site-main">
        <?php echo $content ?? ''; ?>
    </main>
    <footer class="site-footer">
        <?php echo htmlspecialchars($settings['footer'] ?? "© " . date('Y') . " $companyName"); ?>
    </footer>
</body>
</html>
