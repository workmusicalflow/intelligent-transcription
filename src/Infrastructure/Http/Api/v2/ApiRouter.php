<?php

namespace Infrastructure\Http\Api\v2;

use Infrastructure\Http\Api\v2\Controller\TranscriptionApiController;
use Infrastructure\Http\Api\v2\Controller\AuthApiController;
use Infrastructure\Http\Api\v2\Controller\ChatApiController;
use Infrastructure\Http\Api\v2\Controller\AnalyticsApiController;
use App\Infrastructure\Http\Api\v2\Controller\TranslationApiController;
use Infrastructure\Http\Api\v2\Middleware\AuthMiddleware;
use Infrastructure\Http\Api\v2\Middleware\RateLimitMiddleware;
use Infrastructure\Http\Api\v2\Middleware\CorsMiddleware;
use Infrastructure\Http\Api\v2\Middleware\CacheMiddleware;

/**
 * Routeur principal pour l'API v2
 * 
 * Gère le routing, les middlewares et la documentation OpenAPI
 */
class ApiRouter
{
    private array $routes = [];
    private array $middlewares = [];
    
    public function __construct()
    {
        $this->setupMiddlewares();
        $this->setupRoutes();
    }
    
    /**
     * Configure les middlewares globaux
     */
    private function setupMiddlewares(): void
    {
        $this->middlewares = [
            new CorsMiddleware(),
            new CacheMiddleware(),
            new RateLimitMiddleware(),
        ];
    }
    
    /**
     * Configure toutes les routes de l'API
     */
    private function setupRoutes(): void
    {
        // Routes publiques
        $this->addRoute('POST', '/auth/login', [AuthApiController::class, 'login']);
        $this->addRoute('POST', '/auth/register', [AuthApiController::class, 'register']);
        $this->addRoute('POST', '/auth/refresh', [AuthApiController::class, 'refresh']);
        
        // Routes protégées - Transcriptions
        $this->addRoute('GET', '/transcriptions', [TranscriptionApiController::class, 'index'], [AuthMiddleware::class]);
        $this->addRoute('GET', '/transcriptions/{id}', [TranscriptionApiController::class, 'show'], [AuthMiddleware::class]);
        $this->addRoute('POST', '/transcriptions', [TranscriptionApiController::class, 'create'], [AuthMiddleware::class]);
        $this->addRoute('PUT', '/transcriptions/{id}', [TranscriptionApiController::class, 'update'], [AuthMiddleware::class]);
        $this->addRoute('DELETE', '/transcriptions/{id}', [TranscriptionApiController::class, 'delete'], [AuthMiddleware::class]);
        $this->addRoute('POST', '/transcriptions/{id}/process', [TranscriptionApiController::class, 'process'], [AuthMiddleware::class]);
        $this->addRoute('GET', '/transcriptions/{id}/download', [TranscriptionApiController::class, 'download'], [AuthMiddleware::class]);
        
        // Routes protégées - Chat
        $this->addRoute('GET', '/transcriptions/{id}/chat', [ChatApiController::class, 'getConversation'], [AuthMiddleware::class]);
        $this->addRoute('POST', '/transcriptions/{id}/chat', [ChatApiController::class, 'sendMessage'], [AuthMiddleware::class]);
        $this->addRoute('GET', '/chat/conversations', [ChatApiController::class, 'listConversations'], [AuthMiddleware::class]);
        
        // Routes protégées - Analytics
        $this->addRoute('GET', '/analytics/overview', [AnalyticsApiController::class, 'overview'], [AuthMiddleware::class]);
        $this->addRoute('GET', '/analytics/usage', [AnalyticsApiController::class, 'usage'], [AuthMiddleware::class]);
        $this->addRoute('GET', '/analytics/costs', [AnalyticsApiController::class, 'costs'], [AuthMiddleware::class]);
        
        // Routes protégées - Traductions (temporairement sans auth pour debug)
        $this->addRoute('GET', '/translations/capabilities', [TranslationApiController::class, 'getCapabilities']);
        $this->addRoute('POST', '/translations/create', [TranslationApiController::class, 'createTranslation']);
        $this->addRoute('GET', '/translations/list', [TranslationApiController::class, 'listTranslations']);
        $this->addRoute('GET', '/translations/status/{id}', [TranslationApiController::class, 'getTranslationStatus']);
        $this->addRoute('GET', '/translations/download/{id}', [TranslationApiController::class, 'downloadTranslation']);
        $this->addRoute('POST', '/translations/estimate', [TranslationApiController::class, 'estimateTranslationCost']);
        $this->addRoute('POST', '/translations/stop/{id}', [TranslationApiController::class, 'stopTranslation']);
        $this->addRoute('DELETE', '/translations/{id}', [TranslationApiController::class, 'deleteTranslation']);
        
        // Webhooks
        $this->addRoute('POST', '/webhooks/transcription-completed', [TranscriptionApiController::class, 'webhookCompleted']);
        
        // Documentation
        $this->addRoute('GET', '/docs', [$this, 'getOpenApiSpec']);
        $this->addRoute('GET', '/health', [$this, 'healthCheck']);
    }
    
    /**
     * Ajoute une route
     */
    private function addRoute(string $method, string $path, array $handler, array $middlewares = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => array_merge($this->middlewares, $middlewares)
        ];
    }
    
    /**
     * Traite une requête
     */
    public function handle(string $method, string $uri): void
    {
        // Nettoyer l'URI - PATH_INFO n'a pas besoin de nettoyage
        // car router.php a déjà extrait la partie après /api/v2/
        $uri = $uri;
        
        // Trouver la route correspondante
        $route = $this->findRoute($method, $uri);
        
        if (!$route) {
            $this->sendJsonResponse(['error' => 'Route not found'], 404);
            return;
        }
        
        try {
            // Exécuter les middlewares
            $request = $this->createRequest($method, $uri, $route['params']);
            
            foreach ($route['middlewares'] as $middleware) {
                if (is_string($middleware)) {
                    $middleware = new $middleware();
                }
                
                $response = $middleware->handle($request);
                if ($response !== null) {
                    $this->sendResponse($response);
                    return;
                }
            }
            
            // Exécuter le handler
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = is_string($class) ? new $class() : $class;
                
                // Passer les paramètres d'URL selon la méthode
                if (in_array($method, ['getTranslationStatus', 'stopTranslation', 'deleteTranslation']) && isset($route['params']['id'])) {
                    $response = $controller->$method($route['params']['id']);
                } elseif (in_array($method, ['downloadTranslation']) && isset($route['params']['id'])) {
                    $response = $controller->$method($route['params']['id'], $request);
                } else {
                    $response = $controller->$method($request);
                }
            } else {
                $response = $handler($request);
            }
            
            $this->sendResponse($response);
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Trouve une route correspondante
     */
    private function findRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $params = $this->matchPath($route['path'], $uri);
            if ($params !== false) {
                return array_merge($route, ['params' => $params]);
            }
        }
        
        return null;
    }
    
    /**
     * Compare un pattern de route avec une URI
     */
    private function matchPath(string $pattern, string $uri): array|false
    {
        // Convertir le pattern en regex
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            // Extraire les paramètres nommés
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }
        
        return false;
    }
    
    /**
     * Crée un objet Request
     */
    private function createRequest(string $method, string $uri, array $params): ApiRequest
    {
        return new ApiRequest(
            $method,
            $uri,
            $params,
            $_GET,
            $this->getJsonBody(),
            getallheaders() ?: []
        );
    }
    
    /**
     * Récupère le body JSON
     */
    private function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Envoie une réponse
     */
    private function sendResponse(ApiResponse $response): void
    {
        http_response_code($response->getStatusCode());
        
        foreach ($response->getHeaders() as $name => $value) {
            header("$name: $value");
        }
        
        echo json_encode($response->getData());
    }
    
    /**
     * Envoie une réponse JSON simple
     */
    private function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        $this->sendResponse(new ApiResponse($data, $statusCode));
    }
    
    /**
     * Gère les exceptions
     */
    private function handleException(\Exception $e): void
    {
        $statusCode = 500;
        $message = 'Internal server error';
        
        if ($e instanceof \InvalidArgumentException) {
            $statusCode = 400;
            $message = $e->getMessage();
        } elseif ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'not found')) {
            $statusCode = 404;
            $message = $e->getMessage();
        } elseif ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'unauthorized')) {
            $statusCode = 401;
            $message = $e->getMessage();
        }
        
        error_log("API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        $this->sendJsonResponse([
            'error' => $message,
            'code' => $e->getCode() ?: $statusCode
        ], $statusCode);
    }
    
    /**
     * Retourne la spécification OpenAPI
     */
    public function getOpenApiSpec(): ApiResponse
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Intelligent Transcription API',
                'version' => '2.0.0',
                'description' => 'API REST pour le service de transcription intelligente'
            ],
            'servers' => [
                ['url' => '/api/v2']
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ]
            ],
            'paths' => $this->generateOpenApiPaths()
        ];
        
        return new ApiResponse($spec);
    }
    
    /**
     * Health check endpoint
     */
    public function healthCheck(): ApiResponse
    {
        return new ApiResponse([
            'status' => 'healthy',
            'version' => '2.0.0',
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Génère les paths OpenAPI
     */
    private function generateOpenApiPaths(): array
    {
        // Implémentation simplifiée
        // Dans une vraie app, on génèrerait automatiquement depuis les routes
        return [
            '/transcriptions' => [
                'get' => [
                    'summary' => 'List transcriptions',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'List of transcriptions']
                    ]
                ],
                'post' => [
                    'summary' => 'Create transcription',
                    'security' => [['bearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'audio' => ['type' => 'string', 'format' => 'binary'],
                                        'language' => ['type' => 'string'],
                                        'youtube_url' => ['type' => 'string']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Transcription created']
                    ]
                ]
            ]
        ];
    }
}