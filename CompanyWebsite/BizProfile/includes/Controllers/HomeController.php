<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;

class HomeController extends Controller
{
    public function index(): void
    {
        $sliders = Database::query("SELECT * FROM sliders WHERE status = 'active' ORDER BY sort_order ASC");
        $products = Database::query("SELECT * FROM products WHERE featured = 1 AND status = 'active' ORDER BY sort_order ASC LIMIT 6");
        $testimonials = Database::query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC");
        $clients = Database::query("SELECT * FROM clients WHERE status = 'active' ORDER BY sort_order ASC");
        $news = Database::query("SELECT * FROM product_releases WHERE status = 'active' ORDER BY created_at DESC LIMIT 3");
        
        $this->view('home', [
            'sliders' => $sliders,
            'products' => $products,
            'testimonials' => $testimonials,
            'clients' => $clients,
            'news' => $news,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
}