<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class ProductController extends Controller
{
    public function index(): void
    {
        $categoryId = $_GET['category'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;

        if (!Security::rateLimit('api_products_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $offset = ($page - 1) * $perPage;

        if ($categoryId) {
            $products = Database::query(
                "SELECT * FROM products WHERE category_id = ? AND status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}",
                [$categoryId]
            );
        } else {
            $products = Database::query(
                "SELECT * FROM products WHERE status = 'active' ORDER BY sort_order ASC LIMIT {$perPage} OFFSET {$offset}"
            );
        }

        $total = count(Database::query("SELECT id FROM products WHERE status = 'active'"));

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $products,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }

    public function show(int $id): void
    {
        if (!Security::rateLimit('api_product_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $product = Database::query("SELECT * FROM products WHERE id = ? AND status = 'active' LIMIT 1", [$id]);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        $images = Database::query("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$product[0]['id']]);
        $attributes = Database::query("SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order ASC", [$product[0]['id']]);

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $product[0],
            'images' => $images,
            'attributes' => $attributes
        ]);
    }
}