<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class CareerController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        if (!Security::rateLimit('api_careers_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $careers = Database::query(
            "SELECT * FROM careers WHERE status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}"
        );

        $total = count(Database::query("SELECT id FROM careers WHERE status = 'active'"));

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $careers,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }
}