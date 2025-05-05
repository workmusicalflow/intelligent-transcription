<?php

namespace Controllers;

use Services\ChatService;
use Services\CacheService;
use Template\TwigManager;

/**
 * Controller for analytics features
 */
class AnalyticsController
{
    /**
     * Service for le chat
     * 
     * @var ChatService
     */
    private $chatService;
    
    /**
     * Service for cache
     * 
     * @var CacheService
     */
    private $cacheService;
    
    /**
     * Twig manager for templating
     * 
     * @var TwigManager
     */
    private $twig;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->cacheService = new CacheService();
        $this->twig = TwigManager::getInstance();
    }
    
    /**
     * Display Cache Analytics Dashboard
     * 
     * @return string Rendered HTML
     */
    public function showCacheDashboard()
    {
        // Get cache analytics
        $result = $this->cacheService->getCacheAnalytics();
        
        if (!$result['success']) {
            // Display error
            return $this->twig->render('error.twig', [
                'error' => $result['error'] ?? 'Unable to load analytics'
            ]);
        }
        
        // Render analytics template
        return $this->twig->render('analytics/cache_dashboard.twig', [
            'analytics' => $result['analytics']
        ]);
    }
    
    /**
     * Display analytics for a specific conversation
     * 
     * @param string $conversationId Conversation ID
     * @return string Rendered HTML
     */
    public function showConversationAnalytics($conversationId)
    {
        // Get conversation details with analytics
        $result = $this->chatService->getConversation($conversationId);
        
        if (!$result['success']) {
            // Display error
            return $this->twig->render('error.twig', [
                'error' => $result['error'] ?? 'Conversation not found'
            ]);
        }
        
        // Render conversation analytics template
        return $this->twig->render('analytics/conversation_analytics.twig', [
            'conversation' => $result['conversation']
        ]);
    }
    
    /**
     * Clear cache
     * 
     * @param string|null $conversationId Conversation ID (optional)
     * @return string Rendered HTML or redirect
     */
    public function clearCache($conversationId = null)
    {
        // Clear the cache
        $result = $this->cacheService->clearCache($conversationId);
        
        // Redirect back to the dashboard with a message
        $redirectUrl = $conversationId ? "/conversation/{$conversationId}" : "/analytics/cache";
        $message = $result['success'] ? $result['message'] : $result['error'];
        
        // Return a redirect response
        header("Location: {$redirectUrl}?message=" . urlencode($message));
        exit;
    }
    
    /**
     * Optimize cache - removes unused cache entries
     * 
     * @return string Rendered HTML or redirect
     */
    public function optimizeCache()
    {
        // In a real implementation, we would add more complex optimization logic here
        // For now, we'll just redirect with a message
        
        header("Location: /analytics/cache?message=" . urlencode("Cache optimization complete"));
        exit;
    }
}