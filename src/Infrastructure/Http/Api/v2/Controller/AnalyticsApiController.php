<?php

namespace Infrastructure\Http\Api\v2\Controller;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Application\Analytics\Service\AnalyticsApplicationService;

/**
 * Controller API pour les fonctionnalités d'analytics
 */
class AnalyticsApiController extends BaseApiController
{
    private AnalyticsApplicationService $analyticsService;
    
    public function __construct()
    {
        parent::__construct();
        $this->analyticsService = $this->container->get(AnalyticsApplicationService::class);
    }
    
    /**
     * Obtenir les statistiques globales
     */
    public function getStats(ApiRequest $request): ApiResponse
    {
        try {
            $userId = $request->getUserId();
            $period = $request->getQueryParam('period', '30d'); // 7d, 30d, 90d, 1y
            
            $stats = $this->analyticsService->getUserStats($userId, $period);
            
            return ApiResponse::success([
                'period' => $period,
                'stats' => [
                    'transcriptions' => [
                        'total' => $stats['transcriptions_total'],
                        'completed' => $stats['transcriptions_completed'],
                        'processing' => $stats['transcriptions_processing'],
                        'failed' => $stats['transcriptions_failed']
                    ],
                    'usage' => [
                        'audio_hours' => $stats['audio_hours_processed'],
                        'total_cost' => $stats['total_cost'],
                        'avg_processing_time' => $stats['avg_processing_time']
                    ],
                    'activity' => [
                        'active_days' => $stats['active_days'],
                        'last_activity' => $stats['last_activity']
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la récupération des statistiques', 500, [
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtenir l'historique d'utilisation
     */
    public function getUsageHistory(ApiRequest $request): ApiResponse
    {
        try {
            $userId = $request->getUserId();
            $period = $request->getQueryParam('period', '30d');
            $granularity = $request->getQueryParam('granularity', 'day'); // hour, day, week, month
            
            $history = $this->analyticsService->getUsageHistory($userId, $period, $granularity);
            
            return ApiResponse::success([
                'period' => $period,
                'granularity' => $granularity,
                'data' => $history
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la récupération de l\'historique', 500, [
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtenir les statistiques de cache
     */
    public function getCacheStats(ApiRequest $request): ApiResponse
    {
        try {
            $userId = $request->getUserId();
            
            $cacheStats = $this->analyticsService->getCacheStatistics($userId);
            
            return ApiResponse::success([
                'cache_stats' => [
                    'hit_rate' => $cacheStats['hit_rate'],
                    'total_requests' => $cacheStats['total_requests'],
                    'cache_hits' => $cacheStats['cache_hits'],
                    'cache_misses' => $cacheStats['cache_misses'],
                    'cache_size' => $cacheStats['cache_size'],
                    'memory_usage' => $cacheStats['memory_usage']
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la récupération des stats de cache', 500, [
                'details' => $e->getMessage()
            ]);
        }
    }
}