<?php

namespace Infrastructure\Http\Controller;

use Psr\Container\ContainerInterface;
use Infrastructure\Container\ServiceLocator;

/**
 * Controller de base pour tous les controllers HTTP
 * 
 * Fournit l'accès au conteneur DI et des méthodes utilitaires
 */
abstract class BaseController
{
    protected ContainerInterface $container;
    
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container ?? ServiceLocator::getContainer();
    }
    
    /**
     * Récupère un service du conteneur
     */
    protected function get(string $serviceId)
    {
        return $this->container->get($serviceId);
    }
    
    /**
     * Retourne une réponse JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    /**
     * Retourne une erreur JSON
     */
    protected function jsonError(string $message, int $statusCode = 400, array $additionalData = []): void
    {
        $data = array_merge(['error' => $message], $additionalData);
        $this->json($data, $statusCode);
    }
    
    /**
     * Redirige vers une URL
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
    
    /**
     * Vérifie si la requête est AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Récupère les données JSON de la requête
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Valide les paramètres requis
     */
    protected function validateRequired(array $data, array $requiredFields): ?string
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return "Le champ '$field' est requis.";
            }
        }
        return null;
    }
}