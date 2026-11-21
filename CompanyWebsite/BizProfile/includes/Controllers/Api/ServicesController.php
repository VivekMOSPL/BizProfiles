<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class ServicesController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        if (!Security::rateLimit('api_services_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $services = Database::query(
            "SELECT * FROM services WHERE status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}"
        );

        $total = count(Database::query("SELECT id FROM services WHERE status = 'active'"));

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $services,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }

    public function show(int $id): void
    {
        if (!Security::rateLimit('api_service_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $service = Database::query("SELECT * FROM services WHERE id = ? AND status = 'active' LIMIT 1", [$id]);

        if (!$service) {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['data' => $service[0]]);
    }
}