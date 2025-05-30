<?php

namespace Infrastructure\External\OpenAI;

use Infrastructure\External\ApiClientInterface;
use Infrastructure\External\ApiResponse;
use Exception;

/**
 * Client OpenAI API avec retry et gestion d'erreurs
 */
class OpenAIClient implements ApiClientInterface
{
    private string $apiKey;
    private string $baseUrl;
    private array $headers;
    private int $timeout;
    private int $maxRetries;
    private float $retryDelay;
    
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.openai.com/v1',
        int $timeout = 30
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->maxRetries = 3;
        $this->retryDelay = 1.0;
        
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Intelligent-Transcription/1.0'
        ];
    }
    
    public function request(string $method, string $endpoint, array $data = []): ApiResponse
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $attempt = 0;
        
        while ($attempt <= $this->maxRetries) {
            try {
                $response = $this->makeHttpRequest($method, $url, $data);
                
                if ($response['http_code'] < 500) {
                    // Success ou erreur client (4xx) - ne pas retry
                    return new ApiResponse(
                        $response['http_code'],
                        $response['data'],
                        $response['headers'],
                        $response['http_code'] >= 400 ? $response['error'] : null
                    );
                }
                
                // Erreur serveur (5xx) - retry
                if ($attempt < $this->maxRetries) {
                    $this->sleep($this->retryDelay * pow(2, $attempt));
                }
                
            } catch (Exception $e) {
                if ($attempt >= $this->maxRetries) {
                    return new ApiResponse(0, [], [], $e->getMessage());
                }
                
                $this->sleep($this->retryDelay * pow(2, $attempt));
            }
            
            $attempt++;
        }
        
        return new ApiResponse(500, [], [], 'Max retries exceeded');
    }
    
    public function get(string $endpoint, array $params = []): ApiResponse
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        
        return $this->request('GET', $endpoint);
    }
    
    public function post(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('POST', $endpoint, $data);
    }
    
    public function put(string $endpoint, array $data = []): ApiResponse
    {
        return $this->request('PUT', $endpoint, $data);
    }
    
    public function delete(string $endpoint): ApiResponse
    {
        return $this->request('DELETE', $endpoint);
    }
    
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }
    
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }
    
    public function setRetryPolicy(int $maxRetries, float $delay = 1.0): void
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $delay;
    }
    
    private function makeHttpRequest(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => $this->headers['User-Agent']
        ]);
        
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: {$error}");
        }
        
        curl_close($ch);
        
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        $decodedData = json_decode($body, true);
        $error = null;
        
        if ($httpCode >= 400) {
            $error = $decodedData['error']['message'] ?? "HTTP {$httpCode} Error";
        }
        
        return [
            'http_code' => $httpCode,
            'data' => $decodedData ?: [],
            'headers' => $this->parseHeaders($headers),
            'error' => $error
        ];
    }
    
    private function buildHeaders(): array
    {
        $formatted = [];
        foreach ($this->headers as $name => $value) {
            $formatted[] = "{$name}: {$value}";
        }
        return $formatted;
    }
    
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        
        return $headers;
    }
    
    private function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1000000));
    }
    
    public function getStats(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'headers_count' => count($this->headers)
        ];
    }
}