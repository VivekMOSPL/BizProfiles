<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class PageController extends Controller
{
    public function services(): void
    {
        if (!Security::rateLimit('api_services_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $services = Database::query("SELECT * FROM pages WHERE slug IN ('services', 'about') AND status = 'active'");

        header('Content-Type: application/json');
        echo json_encode(['data' => $services]);
    }

    public function testimonials(): void
    {
        if (!Security::rateLimit('api_testimonials_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $testimonials = Database::query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC");

        header('Content-Type: application/json');
        echo json_encode(['data' => $testimonials]);
    }
}