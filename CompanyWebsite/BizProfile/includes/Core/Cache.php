<?php

namespace BizProfile\Core;

class Cache
{
    private string $cacheDir;
    
    public function __construct()
    {
        $this->cacheDir = BASE_PATH . '/storage/cache/';
        
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get(string $key, int $ttl = 3600): ?array
    {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
            @unlink($cacheFile);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($cacheFile, json_encode($data)) !== false;
    }
    
    public function delete(string $key): bool
    {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '*.json');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
}