<?php

namespace Infrastructure\Http\Api\v2\Middleware;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;

/**
 * Interface pour tous les middlewares
 */
interface MiddlewareInterface
{
    /**
     * Traite la requête
     * 
     * @param ApiRequest $request
     * @return ApiResponse|null Retourne une réponse pour arrêter le traitement, ou null pour continuer
     */
    public function handle(ApiRequest $request): ?ApiResponse;
}