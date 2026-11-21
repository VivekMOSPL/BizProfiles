<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class SearchController extends Controller
{
    public function search(): void
    {
        if (!Security::rateLimit('api_search_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        if (empty($query)) {
            http_response_code(400);
            echo json_encode(['error' => 'Search query is required']);
            return;
        }

        header('Content-Type: application/json');

        $results = [
            'products' => [],
            'pages' => [],
            'news' => [],
            'testimonials' => [],
            'services' => [],
            'clients' => []
        ];

        $searchTerm = '%' . $query . '%';

        // Search in products
        if (!$type || $type === 'products') {
            $products = Database::query(
                "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = 'active' LIMIT {$perPage}",
                [$searchTerm, $searchTerm]
            );
            $results['products'] = $products;
        }

        // Search in testimonials
        if (!$type || $type === 'testimonials') {
            $testimonials = Database::query(
                "SELECT * FROM testimonials WHERE (name LIKE ? OR content LIKE ?) AND status = 'active' LIMIT {$perPage}",
                [$searchTerm, $searchTerm]
            );
            $results['testimonials'] = $testimonials;
        }

        // Search in news
        if (!$type || $type === 'news') {
            $news = Database::query(
                "SELECT * FROM product_releases WHERE (title LIKE ? OR content LIKE ?) AND status = 'active' LIMIT {$perPage}",
                [$searchTerm, $searchTerm]
            );
            $results['news'] = $news;
        }

        // Search in pages
        if (!$type || $type === 'pages') {
            $pages = Database::query(
                "SELECT * FROM pages WHERE (title LIKE ? OR content LIKE ?) AND status = 'active' LIMIT {$perPage}",
                [$searchTerm, $searchTerm]
            );
            $results['pages'] = $pages;
        }

        // Search in services (if exists)
        if (!$type || $type === 'services') {
            try {
                $services = Database::query(
                    "SELECT * FROM services WHERE (name LIKE ? OR description LIKE ?) AND status = 'active' LIMIT {$perPage}",
                    [$searchTerm, $searchTerm]
                );
                $results['services'] = $services;
            } catch (Exception $e) {
                // Services table might not exist yet
                $results['services'] = [];
            }
        }

        // Search in clients
        if (!$type || $type === 'clients') {
            $clients = Database::query(
                "SELECT * FROM clients WHERE (name LIKE ? OR website LIKE ?) AND status = 'active' LIMIT {$perPage}",
                [$searchTerm, $searchTerm]
            );
            $results['clients'] = $clients;
        }

        // Get totals for each section
        $totalResults = 0;
        $totalResults += count(Database::query("SELECT COUNT(*) as count FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = 'active'", [$searchTerm, $searchTerm]));
        $totalResults += count(Database::query("SELECT COUNT(*) as count FROM testimonials WHERE (name LIKE ? OR content LIKE ?) AND status = 'active'", [$searchTerm, $searchTerm]));
        $totalResults += count(Database::query("SELECT COUNT(*) as count FROM product_releases WHERE (title LIKE ? OR content LIKE ?) AND status = 'active'", [$searchTerm, $searchTerm]));
        $totalResults += count(Database::query("SELECT COUNT(*) as count FROM pages WHERE (title LIKE ? OR content LIKE ?) AND status = 'active'", [$searchTerm, $searchTerm]));

        echo json_encode([
            'query' => $query,
            'results' => $results,
            'total_results' => $totalResults,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage
            ]
        ]);
    }
}