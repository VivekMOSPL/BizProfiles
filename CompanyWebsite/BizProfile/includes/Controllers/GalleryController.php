<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;

class GalleryController extends Controller
{
    public function index(): void
    {
        $album = $_GET['album'] ?? null;
        
        if ($album) {
            $images = Database::query("SELECT * FROM gallery WHERE album = ? AND status = 'active' ORDER BY sort_order ASC", [$album]);
        } else {
            $images = Database::query("SELECT * FROM gallery WHERE status = 'active' ORDER BY sort_order ASC");
        }
        
        $albums = Database::query("SELECT DISTINCT album FROM gallery WHERE album IS NOT NULL AND status = 'active'");
        
        $this->view('gallery', [
            'images' => $images,
            'albums' => $albums,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
}