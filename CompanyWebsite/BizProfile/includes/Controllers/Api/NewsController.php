<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class NewsController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        if (!Security::rateLimit('api_news_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $news = Database::query(
            "SELECT * FROM product_releases WHERE status = 'active' ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        $total = count(Database::query("SELECT id FROM product_releases WHERE status = 'active'"));

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $news,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }

    public function show(int $id): void
    {
        if (!Security::rateLimit('api_news_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $news = Database::query("SELECT * FROM product_releases WHERE id = ? AND status = 'active' LIMIT 1", [$id]);

        if (!$news) {
            http_response_code(404);
            echo json_encode(['error' => 'News article not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['data' => $news[0]]);
    }
}