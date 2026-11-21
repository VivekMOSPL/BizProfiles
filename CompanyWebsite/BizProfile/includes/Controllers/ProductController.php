<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;

class ProductController extends Controller
{
    public function index(): void
    {
        $categoryId = $_GET['category'] ?? null;
        
        if ($categoryId) {
            $products = Database::query("SELECT * FROM products WHERE category_id = ? AND status = 'active' ORDER BY sort_order ASC", [$categoryId]);
        } else {
            $products = Database::query("SELECT * FROM products WHERE status = 'active' ORDER BY sort_order ASC");
        }
        
        $categories = Database::query("SELECT * FROM product_categories WHERE status = 'active' ORDER BY sort_order ASC");
        
        $this->view('products', [
            'products' => $products,
            'categories' => $categories,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function show(string $slug): void
    {
        $product = Database::query("SELECT * FROM products WHERE slug = ? AND status = 'active' LIMIT 1", [$slug]);
        
        if (!$product) {
            $this->redirect('/products');
        }
        
        $images = Database::query("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC", [$product[0]['id']]);
        $attributes = Database::query("SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order ASC", [$product[0]['id']]);
        
        $this->view('product-detail', [
            'product' => $product[0],
            'images' => $images,
            'attributes' => $attributes,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
}