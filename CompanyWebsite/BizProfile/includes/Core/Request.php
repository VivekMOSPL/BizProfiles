<?php

namespace BizProfile\Core;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    
    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }
    
    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }
    
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }
    
    public function file(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? $default;
    }
    
    public function isAjax(): bool
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD']);
    }
    
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'];
    }
    
    public function getIpAddress(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($this->server[$key])) {
                return explode(',', $this->server[$key])[0];
            }
        }
        return '0.0.0.0';
    }
}