<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;

class NewsController extends Controller
{
    public function index(): void
    {
        $news = Database::query("SELECT * FROM product_releases WHERE status = 'active' ORDER BY created_at DESC");
        
        $this->view('news', [
            'news' => $news,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function show(string $slug): void
    {
        $article = Database::query("SELECT * FROM product_releases WHERE slug = ? AND status = 'active' LIMIT 1", [$slug]);
        
        if (!$article) {
            $this->redirect('/blog');
        }
        
        $this->view('news-detail', [
            'article' => $article[0],
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function releases(): void
    {
        $releases = Database::query("SELECT * FROM product_releases WHERE status = 'active' ORDER BY created_at DESC");
        
        $this->view('releases', [
            'releases' => $releases,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
}