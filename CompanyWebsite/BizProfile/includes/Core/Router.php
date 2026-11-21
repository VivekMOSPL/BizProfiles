<?php

namespace BizProfile\Core;

class Router
{
    private array $routes = [];
    private Request $request;
    private ?string $currentRoute = null;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put(string $path, string $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete(string $path, string $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    private function addRoute(string $method, string $path, string $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }
    
    public function dispatch(): void
    {
        $uri = parse_url($this->request->getUri(), PHP_URL_PATH);
        $method = $this->request->getMethod();
        
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $pattern = '#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern) . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $this->currentRoute = $handler;
                $this->callHandler($handler, $matches);
                return;
            }
        }
        
        $this->handle404();
    }
    
    private function callHandler(string $handler, array $params = []): void
    {
        [$controllerName, $methodName] = explode('@', $handler);
        
        $controllerFile = BASE_PATH . '/includes/Controllers/' . str_replace('\\', '/', $controllerName) . '.php';
        
        if (!file_exists($controllerFile)) {
            $this->handle404();
            return;
        }
        
        require_once $controllerFile;
        
        $controllerClass = 'BizProfile\\Controllers\\' . str_replace('/', '\\', $controllerName);
        $controller = new $controllerClass();
        
        call_user_func_array([$controller, $methodName], $params);
    }
    
    private function handle404(): void
    {
        http_response_code(404);
        $controllerFile = BASE_PATH . '/includes/Controllers/ErrorController.php';
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controller = new \BizProfile\Controllers\ErrorController();
            $controller->notFound();
        } else {
            echo '404 - Page Not Found';
        }
    }
}