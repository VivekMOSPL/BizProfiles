<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class TestimonialsController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        if (!Security::rateLimit('api_testimonials_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $testimonials = Database::query(
            "SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}"
        );

        $total = count(Database::query("SELECT id FROM testimonials WHERE status = 'active'"));

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $testimonials,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }

    public function show(int $id): void
    {
        if (!Security::rateLimit('api_testimonial_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $testimonial = Database::query("SELECT * FROM testimonials WHERE id = ? AND status = 'active' LIMIT 1", [$id]);

        if (!$testimonial) {
            http_response_code(404);
            echo json_encode(['error' => 'Testimonial not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['data' => $testimonial[0]]);
    }
}