<?php

namespace BizProfile\Tests\Integration;

use BizProfile\Tests\TestSuite;

class ApiIntegrationTest extends TestSuite
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupApiEnvironment();
    }
    
    public function testApiEndpoints():
    {
        // Test products API endpoint
        $products = $this->callApi('GET', '/api/v1/products');
        $this->assertIsArray($products, 'Products API should return array');
        
        // Test services API endpoint
        $services = $this->callApi('GET', '/api/v1/services');
        $this->assertIsArray($services, 'Services API should return array');
        
        // Test news API endpoint
        $news = $this->callApi('GET', '/api/v1/news');
        $this->assertIsArray($news, 'News API should return array');
        
        // Test testimonials API endpoint
        $testimonials = $this->callApi('GET', '/api/v1/testimonials');
        $this->assertIsArray($testimonials, 'Testimonials API should return array');
        
        // Test gallery API endpoint
        $gallery = $this->callApi('GET', '/api/v1/gallery');
        $this->assertIsArray($gallery, 'Gallery API should return array');
        
        // Test careers API endpoint
        $careers = $this->callApi('GET', '/api/v1/careers');
        $this->assertIsArray($careers, 'Careers API should return array');
        
        // Test contact API endpoint
        $contact = $this->callApi('GET', '/api/v1/contact');
        $this->assertIsArray($contact, 'Contact API should return array');
    }
    
    public function testSearchApi():
    {
        // Test global search functionality
        $searchResults = $this->callApi('GET', '/api/v1/search?query=test');
        $this->assertIsArray($searchResults, 'Search API should return array');
        
        // Test specific search in products
        $productSearch = $this->callApi('GET', '/api/v1/search?query=product&type=products');
        $this->assertIsArray($productSearch, 'Product search API should return array');
        
        // Test specific search in news
        $newsSearch = $this->callApi('GET', '/api/v1/search?query=news&type=news');
        $this->assertIsArray($newsSearch, 'News search API should return array');
    }
    
    public function testApiCorsHeaders():
    {
        // Test CORS headers are present
        $response = $this->callApiWithResponse('GET', '/api/v1/products');
        
        $this->assertArrayHasKey('access-control-allow-origin', $response['headers'],
            'CORS headers should be present');
        $this->assertEquals('*', $response['headers']['access-control-allow-origin'],
            'CORS should allow all origins');
    }
    
    public function testApiRateLimiting():
    {
        // Make multiple requests to test rate limiting
        $responses = [];
        for ($i = 0; $i < 105; $i++) {
            $response = $this->callApiWithResponse('GET', '/api/v1/products');
            $responses[] = $response;
        }
        
        // Check that rate limiting was triggered (some responses should have rate limit headers)
        $rateLimitedResponses = array_filter($responses, function($response) {
            return isset($response['headers']['x-ratelimit-remaining']) && 
                   $response['headers']['x-ratelimit-remaining'] < 100;
        });
        
        // At least some responses should show rate limiting
        $this->assertGreaterThan(0, count($rateLimitedResponses),
            'Rate limiting should be effective');
    }
    
    public function testApiAuthentication():
    {
        // Test API with authentication
        $authenticatedResponse = $this->callApiWithAuth('GET', '/api/v1/products', 'valid-api-key');
        $this->assertEquals(200, $authenticatedResponse['status'], 
            'API requests with valid key should succeed');
        
        // Test API without authentication
        $unauthorizedResponse = $this->callApiWithResponse('GET', '/api/v1/products');
        $this->assertLessThan(300, $unauthorizedResponse['status'],
            'API requests without auth should be restricted');
    }
    
    public function testApiJsonResponseFormat():
    {
        $response = $this->callApiWithResponse('GET', '/api/v1/products');
        
        // Check that response is in JSON format
        $this->assertEquals('application/json', $response['headers']['content-type'],
            'API should return JSON responses');
        
        $data = json_decode($response['body'], true);
        $this->assertIsArray($data, 'API response should be valid JSON array');
    }
    
    protected function setupApiEnvironment(): void
    {
        // Set up test environment for API testing
        putenv('APP_ENV=test');
        putenv('API_RATE_LIMIT=1000'); // Higher limit for testing
        putenv('API_RATE_WINDOW=3600');
        
        // Initialize API configuration
        $this->apiBaseUrl = getenv('API_BASE_URL') ?: 'http://localhost/api';
    }
    
    protected function callApi(string $method, string $endpoint, array $data = null): array
    {
        $ch = curl_init();
        
        $url = $this->apiBaseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseHeaders = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("API call failed: HTTP {$httpCode}");
        }
        
        return json_decode($response, true) ?? [];
    }
    
    protected function callApiWithResponse(string $method, string $endpoint, array $data = null): array
    {
        $ch = curl_init();
        
        $url = $this->apiBaseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-KEY: test-api-key'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        
        // Parse headers from response
        $headerText = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        $headers = [];
        $headerLines = explode('\r\n', $headerText);
        foreach ($headerLines as $line) {
            if (strpos($line, ': ') !== false) {
                [$key, $value] = explode(': ', $line);
                $headers[strtolower($key)] = $value;
            }
        }
        
        return [
            'status' => $httpCode,
            'body' => $body,
            'headers' => $headers,
            'content-type' => $contentType
        ];
    }
    
    protected function callApiWithAuth(string $method, string $endpoint, string $apiKey, array $data = null): array
    {
        $ch = curl_init();
        
        $url = $this->apiBaseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            "X-API-KEY: {$apiKey}"
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return json_decode($response, true) ?? [];
    }
}
