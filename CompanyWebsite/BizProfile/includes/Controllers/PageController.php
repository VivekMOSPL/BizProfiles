<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;

class PageController extends Controller
{
    public function about(): void
    {
        $page = Database::query("SELECT * FROM pages WHERE slug = 'about' LIMIT 1");
        $this->view('about', [
            'page' => $page[0] ?? [],
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function services(): void
    {
        $page = Database::query("SELECT * FROM pages WHERE slug = 'services' LIMIT 1");
        $this->view('services', [
            'page' => $page[0] ?? [],
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function clients(): void
    {
        $clients = Database::query("SELECT * FROM clients WHERE status = 'active' ORDER BY sort_order ASC");
        $this->view('clients', [
            'clients' => $clients,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function testimonials(): void
    {
        $testimonials = Database::query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC");
        $this->view('testimonials', [
            'testimonials' => $testimonials,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function privacy(): void
    {
        $page = Database::query("SELECT * FROM pages WHERE slug = 'privacy-policy' LIMIT 1");
        $this->view('privacy', [
            'page' => $page[0] ?? [],
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function terms(): void
    {
        $page = Database::query("SELECT * FROM pages WHERE slug = 'terms' LIMIT 1");
        $this->view('terms', [
            'page' => $page[0] ?? [],
            'settings' => $this->getWebsiteSettings()
        ]);
    }
}