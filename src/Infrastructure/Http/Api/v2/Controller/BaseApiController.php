<?php

namespace Infrastructure\Http\Api\v2\Controller;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Infrastructure\Container\ServiceLocator;
use Psr\Container\ContainerInterface;

/**
 * Controller de base pour l'API
 */
abstract class BaseApiController
{
    protected ContainerInterface $container;
    
    public function __construct()
    {
        $this->container = ServiceLocator::getContainer();
    }
    
    /**
     * Récupère un service du conteneur
     */
    protected function get(string $serviceId)
    {
        return $this->container->get($serviceId);
    }
    
    /**
     * Valide les données de la requête
     */
    protected function validate(ApiRequest $request, array $rules): ?ApiResponse
    {
        $errors = $request->validate($rules);
        
        if (!empty($errors)) {
            return ApiResponse::badRequest('Validation failed', $errors);
        }
        
        return null;
    }
    
    /**
     * Transforme une entité du domaine en array pour l'API
     */
    protected function transformEntity($entity): array
    {
        // À implémenter dans les sous-classes
        return [];
    }
    
    /**
     * Transforme une collection d'entités
     */
    protected function transformCollection(array $entities): array
    {
        return array_map([$this, 'transformEntity'], $entities);
    }
}