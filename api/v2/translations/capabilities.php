<?php

/**
 * API v2 - Capacités des services de traduction
 * GET /api/v2/translations/capabilities
 * 
 * Endpoint pour découvrir les capacités et langues supportées
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../src/autoload.php';

use App\Services\Translation\TranslationServiceFactory;
use App\Services\Translation\TranslationCacheService;
use Psr\Log\NullLogger;

try {
    // Pas d'authentification nécessaire pour les capacités publiques
    
    // Initialiser services
    $logger = new NullLogger();
    $cache = new TranslationCacheService($logger);
    $openAIClient = createMockOpenAIClient(); // Mock pour la démo
    
    $factory = new TranslationServiceFactory($openAIClient, $logger, $cache);
    
    // Récupérer capacités de tous les services disponibles
    $availableServices = $factory->getAvailableServices();
    
    // Informations générales sur les capacités de traduction
    $response = [
        'success' => true,
        'data' => [
            'services' => $availableServices,
            'supported_languages' => [
                'fr' => [
                    'name' => 'Français',
                    'code' => 'fr',
                    'quality' => 'excellent',
                    'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                    'specialties' => ['dubbing', 'cinema', 'technical']
                ],
                'es' => [
                    'name' => 'Español',
                    'code' => 'es',
                    'quality' => 'excellent',
                    'optimal_providers' => ['gpt-4o-mini', 'whisper-1', 'hybrid'],
                    'specialties' => ['dubbing', 'cinema', 'news']
                ],
                'de' => [
                    'name' => 'Deutsch',
                    'code' => 'de',
                    'quality' => 'excellent',
                    'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                    'specialties' => ['technical', 'business', 'dubbing']
                ],
                'it' => [
                    'name' => 'Italiano',
                    'code' => 'it',
                    'quality' => 'very-good',
                    'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                    'specialties' => ['dubbing', 'arts', 'cuisine']
                ],
                'pt' => [
                    'name' => 'Português',
                    'code' => 'pt',
                    'quality' => 'very-good',
                    'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                    'specialties' => ['dubbing', 'business', 'technical']
                ],
                'nl' => [
                    'name' => 'Nederlands',
                    'code' => 'nl',
                    'quality' => 'good',
                    'optimal_providers' => ['gpt-4o-mini'],
                    'specialties' => ['business', 'technical']
                ],
                'en' => [
                    'name' => 'English',
                    'code' => 'en',
                    'quality' => 'excellent',
                    'optimal_providers' => ['whisper-1', 'gpt-4o-mini', 'hybrid'],
                    'specialties' => ['universal', 'technical', 'business', 'dubbing']
                ]
            ],
            'features' => [
                'timestamp_preservation' => [
                    'word_level' => true,
                    'segment_level' => true,
                    'precision' => 'millisecond',
                    'dubbing_ready' => true
                ],
                'intelligent_adaptation' => [
                    'length_optimization' => true,
                    'emotional_context' => true,
                    'character_preservation' => true,
                    'technical_terms' => true,
                    'style_adaptation' => true
                ],
                'quality_features' => [
                    'automatic_fallbacks' => true,
                    'quality_scoring' => true,
                    'cache_optimization' => true,
                    'batch_processing' => true,
                    'real_time_preview' => false // Future feature
                ],
                'export_formats' => [
                    'json' => 'Native format with all metadata',
                    'srt' => 'SubRip subtitles with timing',
                    'vtt' => 'WebVTT for web players',
                    'txt' => 'Plain text transcript',
                    'dubbing_json' => 'Optimized for TTS generation'
                ]
            ],
            'pricing' => [
                'gpt-4o-mini' => [
                    'base_cost_per_minute' => 0.008,
                    'currency' => 'USD',
                    'includes' => ['premium_quality', 'emotional_context', 'timing_optimization'],
                    'volume_discounts' => false
                ],
                'whisper-1' => [
                    'base_cost_per_minute' => 0.006,
                    'currency' => 'USD',
                    'includes' => ['native_openai', 'basic_timing'],
                    'limitations' => ['primarily_english_target', 'no_word_level']
                ],
                'hybrid' => [
                    'base_cost_per_minute' => 0.009,
                    'currency' => 'USD',
                    'includes' => ['maximum_reliability', 'automatic_fallbacks', 'optimization'],
                    'recommended_for' => 'production_workloads'
                ]
            ],
            'limits' => [
                'max_audio_duration_minutes' => 180, // 3 heures
                'max_segments_per_request' => 100,
                'max_file_size_mb' => 25,
                'rate_limits' => [
                    'requests_per_minute' => 60,
                    'concurrent_translations' => 5
                ],
                'cache_retention_hours' => 24
            ],
            'quality_guarantees' => [
                'timestamp_accuracy' => '±50ms',
                'translation_quality_score' => '>0.85',
                'service_availability' => '99.5%',
                'maximum_processing_time' => '30s_per_minute_audio',
                'fallback_success_rate' => '99.9%'
            ],
            'use_cases' => [
                'film_dubbing' => [
                    'description' => 'Professional film and TV dubbing with emotion preservation',
                    'recommended_providers' => ['gpt-4o-mini', 'hybrid'],
                    'features' => ['emotional_context', 'character_names', 'timing_strict']
                ],
                'educational_content' => [
                    'description' => 'Educational videos and courses translation',
                    'recommended_providers' => ['gpt-4o-mini', 'whisper-1'],
                    'features' => ['technical_terms', 'clear_language', 'timing_flexible']
                ],
                'business_presentations' => [
                    'description' => 'Corporate and business content translation',
                    'recommended_providers' => ['gpt-4o-mini'],
                    'features' => ['formal_tone', 'technical_accuracy', 'timing_moderate']
                ],
                'social_media' => [
                    'description' => 'Short-form content and social media videos',
                    'recommended_providers' => ['gpt-4o-mini'],
                    'features' => ['casual_tone', 'length_optimization', 'timing_strict']
                ]
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
}

/**
 * Mock OpenAI client pour démo
 */
function createMockOpenAIClient()
{
    return new class {
        // Mock minimal pour la factory
    };
}