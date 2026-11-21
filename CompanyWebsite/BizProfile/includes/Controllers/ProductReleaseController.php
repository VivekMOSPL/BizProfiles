<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class ProductReleaseController extends Controller
{
    public function index(): void
    {
        $this->ensureLoggedIn();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $releases = Database::query(
            "SELECT * FROM product_releases WHERE status = 'active' ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        
        $total = count(Database::query("SELECT id FROM product_releases WHERE status = 'active'"));
        $totalPages = ceil($total / $perPage);
        
        $this->adminView('admin/product-releases', [
            'releases' => $releases,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function create(): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $this->adminView('admin/product-releases/create', [
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
            $this->redirect('/admin/product-releases/create');
        }
        
        $data = [
            'product_id' => $_POST['product_id'] ?? null,
            'title' => $_POST['title'] ?? '',
            'slug' => $this->generateSlug($_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '',
            'version' => $_POST['version'] ?? '',
            'release_date' => $_POST['release_date'] ?? null,
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'created_by' => $_SESSION['user_id']
        ];
        
        // Handle file upload
        if (isset($_FILES['download_file']) && $_FILES['download_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleFileUpload($_FILES['download_file'], 'product_downloads');
            if ($uploadResult['success']) {
                $data['download_file'] = $uploadResult['file_path'];
                $data['download_file_name'] = $_FILES['download_file']['name'];
            } else {
                $_SESSION['error'] = 'File upload failed: ' . $uploadResult['error'];
                $this->redirect('/admin/product-releases/create');
            }
        }
        
        if (empty($data['title'])) {
            $_SESSION['error'] = 'Title is required';
            $this->redirect('/admin/product-releases/create');
        }
        
        $result = Database::execute(
            "INSERT INTO product_releases (product_id, title, slug, content, version, release_date, status, sort_order, download_file, download_file_name, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['product_id'], $data['title'], $data['slug'], $data['content'],
                $data['version'], $data['release_date'], $data['status'], $data['sort_order'],
                $data['download_file'] ?? null, $data['download_file_name'] ?? null, $data['created_by']
            ]
        );
        
        if ($result) {
            $this->logAdminAction('product_release_create', "Created product release: {$data['title']}");
            
            $_SESSION['success'] = 'Product release created successfully';
            $this->redirect('/admin/product-releases');
        } else {
            $_SESSION['error'] = 'Failed to create product release';
            $this->redirect('/admin/product-releases/create');
        }
    }
    
    public function edit(string $slug): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $release = Database::query("SELECT * FROM product_releases WHERE slug = ? LIMIT 1", [$slug]);
        
        if (!$release) {
            $_SESSION['error'] = 'Product release not found';
            $this->redirect('/admin/product-releases');
        }
        
        $products = Database::query("SELECT * FROM products WHERE status = 'active' ORDER BY name ASC");
        
        $this->adminView('admin/product-releases/edit', [
            'release' => $release[0],
            'products' => $products,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function update(string $slug): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect("/admin/product-releases/edit/$slug");
        }
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'version' => $_POST['version'] ?? '',
            'release_date' => $_POST['release_date'] ?? null,
            'status' => $_POST['status'] ?? 'active',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'updated_by' => $_SESSION['user_id']
        ];
        
        if (empty($data['title'])) {
            $_SESSION['error'] = 'Title is required';
            $this->redirect("/admin/product-releases/edit/$slug");
        }
        
        $updateData = [
            'title' => $data['title'],
            'content' => $data['content'],
            'version' => $data['version'],
            'release_date' => $data['release_date'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle file upload if new file provided
        if (isset($_FILES['download_file']) && $_FILES['download_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleFileUpload($_FILES['download_file'], 'product_downloads');
            if ($uploadResult['success']) {
                $updateData['download_file'] = $uploadResult['file_path'];
                $updateData['download_file_name'] = $_FILES['download_file']['name'];
            } else {
                $_SESSION['error'] = 'File upload failed: ' . $uploadResult['error'];
                $this->redirect("/admin/product-releases/edit/$slug");
            }
        }
        
        $fields = implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($updateData)));
        $values = array_values($updateData);
        
        $result = Database::execute(
            "UPDATE product_releases SET $fields WHERE slug = ?",
            array_merge($values, [$slug])
        );
        
        if ($result) {
            $this->logAdminAction('product_release_update', "Updated product release: $slug");
            
            $_SESSION['success'] = 'Product release updated successfully';
            $this->redirect('/admin/product-releases');
        } else {
            $_SESSION['error'] = 'Failed to update product release';
            $this->redirect("/admin/product-releases/edit/$slug");
        }
    }
    
    public function delete(string $slug): void
    {
        $this->ensureLoggedIn();
        $this->ensureAdminRole();
        
        $release = Database::query("SELECT * FROM product_releases WHERE slug = ? LIMIT 1", [$slug]);
        
        if (!$release) {
            $_SESSION['error'] = 'Product release not found';
            $this->redirect('/admin/product-releases');
        }
        
        $result = Database::execute("UPDATE product_releases SET status = 'inactive' WHERE slug = ?", [$slug]);
        
        if ($result) {
            $this->logAdminAction('product_release_delete', "Deleted product release: $slug");
            
            $_SESSION['success'] = 'Product release deleted successfully';
            $this->redirect('/admin/product-releases');
        } else {
            $_SESSION['error'] = 'Failed to delete product release';
            $this->redirect('/admin/product-releases');
        }
    }
    
    public function download(int $id): void
    {
        $release = Database::query("SELECT * FROM product_releases WHERE id = ? AND status = 'active' LIMIT 1", [$id]);
        
        if (!$release) {
            http_response_code(404);
            echo 'Product release not found';
            return;
        }
        
        if (empty($release[0]['download_file'])) {
            http_response_code(404);
            echo 'No download file available';
            return;
        }
        
        $filePath = BASE_PATH . '/storage/uploads/product_downloads/' . $release[0]['download_file'];
        
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }
        
        $fileName = $release[0]['download_file_name'] ?? 'download.zip';
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }
    
    private function handleFileUpload(array $file, string $directory): array
    {
        $allowedTypes = ['application/zip', 'application/x-rar-compressed', 'application/vnd.ms-excel', 'text/plain', 'application/pdf'];
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        $uploadDir = BASE_PATH . '/storage/uploads/' . $directory;
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetFile = $uploadDir . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return [
                'success' => true,
                'file_path' => $fileName,
                'file_name' => basename($file['name'])
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to upload file'
        ];
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
}