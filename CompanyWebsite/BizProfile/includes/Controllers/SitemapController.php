<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;

class SitemapController extends Controller
{
    public function index(): void
    {
        header('Content-Type: application/xml');
        
        $pages = Database::query("SELECT slug FROM pages WHERE status = 'active'");
        $products = Database::query("SELECT slug FROM products WHERE status = 'active'");
        $news = Database::query("SELECT slug FROM product_releases WHERE status = 'active'");
        
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        echo '<url><loc>' . $_ENV['APP_URL'] . '</loc></url>';
        
        foreach ($pages as $page) {
            echo '<url><loc>' . $_ENV['APP_URL'] . '/' . $page['slug'] . '</loc></url>';
        }
        
        foreach ($products as $product) {
            echo '<url><loc>' . $_ENV['APP_URL'] . '/products/' . $product['slug'] . '</loc></url>';
        }
        
        foreach ($news as $article) {
            echo '<url><loc>' . $_ENV['APP_URL'] . '/blog/' . $article['slug'] . '</loc></url>';
        }
        
        echo '</urlset>';
    }
}