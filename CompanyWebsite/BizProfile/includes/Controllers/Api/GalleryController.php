<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class GalleryController extends Controller
{
    public function index(): void
    {
        $album = $_GET['album'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        if (!Security::rateLimit('api_gallery_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        if ($album) {
            $gallery = Database::query(
                "SELECT * FROM gallery WHERE album = ? AND status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}",
                [$album]
            );
        } else {
            $gallery = Database::query(
                "SELECT * FROM gallery WHERE status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}"
            );
        }

        $total = count(Database::query("SELECT id FROM gallery WHERE status = 'active'"));

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $gallery,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }
}