<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class ProductServiceController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        
        // Get products
        $products = Database::query(
            "SELECT * FROM products WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        
        // Get services
        $services = Database::query(
            "SELECT * FROM services WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        
        // Get categories for filtering
        $categories = Database::query("SELECT * FROM product_categories WHERE status = 'active' ORDER BY sort_order ASC");
        
        // Get featured products
        $featuredProducts = Database::query(
            "SELECT * FROM products WHERE featured = 1 AND status = 'active' ORDER BY sort_order ASC LIMIT 6"
        );
        
        $this->view('products-services', [
            'products' => $products,
            'services' => $services,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function create(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $categories = Database::query("SELECT * FROM product_categories WHERE status = 'active' ORDER BY name ASC");
        
        $this->adminView('admin/products-services/create', [
            'categories' => $categories,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function store(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/products-services/create');
        }
        
        $type = $_POST['type'] ?? 'product'; // product or service
        
        if ($type === 'product') {
            $result = $this->storeProduct();
        } else {
            $result = $this->storeService();
        }
        
        if ($result['success']) {
            $this->logAdminAction($result['action'], $result['message']);
            
            $_SESSION['success'] = $result['message'];
            $this->redirect('/admin/products-services');
        } else {
            $_SESSION['error'] = $result['message'];
            $this->redirect('/admin/products-services/create');
        }
    }
    
    public function edit(string $type, string $slug): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        if ($type === 'product') {
            $item = Database::query("SELECT * FROM products WHERE slug = ? LIMIT 1", [$slug]);
            $itemType = 'product';
        } else {
            $item = Database::query("SELECT * FROM services WHERE slug = ? LIMIT 1", [$slug]);
            $itemType = 'service';
        }
        
        if (!$item) {
            $_SESSION['error'] = 'Item not found';
            $this->redirect('/admin/products-services');
        }
        
        $categories = Database::query("SELECT * FROM product_categories WHERE status = 'active' ORDER BY name ASC");
        
        $this->adminView("admin/products-services/{$itemType}/edit", [
            'item' => $item[0],
            'itemType' => $itemType,
            'categories' => $categories,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function update(string $type, string $slug): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect("/admin/products-services/{$type}/edit/{$slug}");
        }
        
        if ($type === 'product') {
            $result = $this->updateProduct($slug);
        } else {
            $result = $this->updateService($slug);
        }
        
        if ($result['success']) {
            $this->logAdminAction($result['action'], $result['message']);
            
            $_SESSION['success'] = $result['message'];
            $this->redirect('/admin/products-services');
        } else {
            $_SESSION['error'] = $result['message'];
            $this->redirect("/admin/products-services/{$type}/edit/{$slug}");
        }
    }
    
    public function delete(string $type, string $slug): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        if ($type === 'product') {
            $result = Database::execute("UPDATE products SET status = 'inactive' WHERE slug = ?", [$slug]);
        } else {
            $result = Database::execute("UPDATE services SET status = 'inactive' WHERE slug = ?", [$slug]);
        }
        
        if ($result) {
            $this->logAdminAction('item_delete', "Deleted {$type}: $slug");
            
            $_SESSION['success'] = 'Item deleted successfully';
            $this->redirect('/admin/products-services');
        } else {
            $_SESSION['error'] = 'Failed to delete item';
            $this->redirect('/admin/products-services');
        }
    }
    
    public function demoRequest(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/products-services');
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'company' => $_POST['company'] ?? '',
            'product_interest' => $_POST['product_interest'] ?? '',
            'service_interest' => $_POST['service_interest'] ?? '',
            'message' => $_POST['message'] ?? '',
            'status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['product_interest'] && empty($data['service_interest']))) {
            $_SESSION['error'] = 'Name, email, and product/service interest are required';
            $this->redirect('/products-services');
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format';
            $this->redirect('/products-services');
        }
        
        $result = Database::execute(
            "INSERT INTO demo_requests (name, email, phone, company, product_interest, service_interest, message, status, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'], $data['email'], $data['phone'], $data['company'],
                $data['product_interest'], $data['service_interest'], $data['message'],
                $data['status'], $data['ip_address'], $data['user_agent']
            ]
        );
        
        if ($result) {
            $this->logDemoRequest($data['email']);
            
            $_SESSION['success'] = 'Thank you for your demo request! We will contact you soon.';
            $this->redirect('/products-services');
        } else {
            $_SESSION['error'] = 'Failed to submit demo request. Please try again.';
            $this->redirect('/products-services');
        }
    }
    
    private function storeProduct(): array
    {
        if (empty($_POST['name']) || empty($_POST['slug']) || empty($_POST['description'])) {
            return ['success' => false, 'message' => 'Product name, slug, and description are required'];
        }
        
        // Handle featured image upload
        $featuredImage = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleImageUpload($_FILES['featured_image'], 'product_featured');
            if ($uploadResult['success']) {
                $featuredImage = $uploadResult['file_path'];
            } else {
                return ['success' => false, 'message' => 'Featured image upload failed: ' . $uploadResult['error']];
            }
        }
        
        $data = [
            'name' => $_POST['name'],
            'slug' => $this->generateSlug($_POST['name']),
            'description' => $_POST['description'] ?? '',
            'category_id' => $_POST['category_id'] ?? null,
            'price' => $_POST['price'] ?? null,
            'featured' => $_POST['featured'] ?? 0,
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'created_by' => $_SESSION['user_id']
        ];
        
        if ($featuredImage) {
            $data['featured_image'] = $featuredImage;
        }
        
        $fields = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($data)));
        $values = array_values($data);
        
        $result = Database::execute(
            "INSERT INTO products SET $fields",
            $values
        );
        
        if ($result) {
            $productId = Database::lastInsertId();
            
            // Handle product images if provided
            if (isset($_FILES['product_images'])) {
                $this->handleProductImages($_FILES['product_images'], $productId);
            }
            
            return [
                'success' => true,
                'message' => 'Product created successfully',
                'action' => 'product_create'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create product'];
    }
    
    private function updateProduct(string $slug): array
    {
        $updateData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category_id' => $_POST['category_id'] ?? null,
            'price' => $_POST['price'] ?? null,
            'featured' => $_POST['featured'] ?? 0,
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (empty($updateData['name'])) {
            return ['success' => false, 'message' => 'Product name is required'];
        }
        
        // Handle featured image upload if new image provided
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleImageUpload($_FILES['featured_image'], 'product_featured');
            if ($uploadResult['success']) {
                $updateData['featured_image'] = $uploadResult['file_path'];
            } else {
                return ['success' => false, 'message' => 'Featured image upload failed: ' . $uploadResult['error']];
            }
        }
        
        $fields = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($updateData)));
        $values = array_values($updateData);
        
        $result = Database::execute(
            "UPDATE products SET $fields WHERE slug = ?",
            array_merge($values, [$slug])
        );
        
        if ($result) {
            // Handle product images if provided
            if (isset($_FILES['product_images'])) {
                // Clear old images and add new ones
                Database::execute("DELETE FROM product_images WHERE product_id = (SELECT id FROM products WHERE slug = ?)", [$slug]);
                $this->handleProductImages($_FILES['product_images'], $slug);
            }
            
            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'action' => 'product_update'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update product'];
    }
    
    private function storeService(): array
    {
        if (empty($_POST['name']) || empty($_POST['slug']) || empty($_POST['description'])) {
            return ['success' => false, 'message' => 'Service name, slug, and description are required'];
        }
        
        $data = [
            'name' => $_POST['name'],
            'slug' => $this->generateSlug($_POST['name']),
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? null,
            'featured' => $_POST['featured'] ?? 0,
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'created_by' => $_SESSION['user_id']
        ];
        
        $fields = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($data)));
        $values = array_values($data);
        
        $result = Database::execute(
            "INSERT INTO services SET $fields",
            $values
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Service created successfully',
                'action' => 'service_create'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create service'];
    }
    
    private function updateService(string $slug): array
    {
        $updateData = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? null,
            'featured' => $_POST['featured'] ?? 0,
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (empty($updateData['name'])) {
            return ['success' => false, 'message' => 'Service name is required'];
        }
        
        $fields = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($updateData)));
        $values = array_values($updateData);
        
        $result = Database::execute(
            "UPDATE services SET $fields WHERE slug = ?",
            array_merge($values, [$slug])
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Service updated successfully',
                'action' => 'service_update'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update service'];
    }
    
    private function handleImageUpload(array $file, string $directory): array
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $uploadDir = BASE_PATH . '/storage/uploads/' . $directory;
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetFile = $uploadDir . '/' . $fileName;
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds limit'];
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Create thumbnail if needed
            if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                $this->createThumbnail($targetFile, $uploadDir . '/thumb_' . $fileName);
            }
            
            return [
                'success' => true,
                'file_path' => $fileName,
                'file_name' => basename($file['name'])
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
    
    private function handleProductImages(array $files, $productIdOrSlug): void
    {
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'size' => [$files['size']],
                'error' => [$files['error']]
            ];
        }
        
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleImageUpload($files, 'product_gallery_' . $productIdOrSlug);
                
                if ($uploadResult['success']) {
                    $productId = is_numeric($productIdOrSlug) ? $productIdOrSlug : 
                        Database::query("SELECT id FROM products WHERE slug = ? LIMIT 1", [$productIdOrSlug])[0]['id'];
                    
                    Database::execute(
                        "INSERT INTO product_images (product_id, image, sort_order) VALUES (?, ?, ?)",
                        [$productId, $uploadResult['file_path'], $key]
                    );
                }
            }
        }
    }
    
    private function createThumbnail(string $sourcePath, string $targetPath): void
    {
        try {
            $imageInfo = getimagesize($sourcePath);
            if ($imageInfo === false) {
                return;
            }
            
            list($width, $height, $type, $attr) = $imageInfo;
            $newWidth = 300;
            $newHeight = ($height / $width) * $newWidth;
            
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagejpeg($thumbnail, $targetPath, 90);
                    imagedestroy($sourceImage);
                    imagedestroy($thumbnail);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($sourcePath);
                    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagepng($thumbnail, $targetPath, 6);
                    imagedestroy($sourceImage);
                    imagedestroy($thumbnail);
                    break;
            }
        } catch (Exception $e) {
            error_log("Thumbnail creation failed: " . $e->getMessage());
        }
    }
    
    private function generateSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
    
    private function ensureLoggedIn(): void
    {
        if (!isLoggedIn()) {
            $this->redirect('/admin/login');
        }
    }
    
    private function ensureAdminRole(): void
    {
        if (!isAdmin()) {
            $this->redirect('/admin/dashboard');
        }
    }
    
    private function logAdminAction(string $action, string $description): void
    {
        Database::execute(
            "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
            [
                $_SESSION['user_id'],
                $action,
                $description,
                $_SERVER['REMOTE_ADDR']
            ]
        );
    }
    
    private function logDemoRequest(string $email): void
    {
        Database::execute(
            "INSERT INTO demo_requests (email, status, ip_address, created_at) VALUES (?, ?, ?, NOW())",
            [
                $email,
                'requested',
                $_SERVER['REMOTE_ADDR']
            ]
        );
    }
}