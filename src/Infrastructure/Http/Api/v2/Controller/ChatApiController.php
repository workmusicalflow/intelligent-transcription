<?php

namespace Infrastructure\Http\Api\v2\Controller;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Application\Chat\Service\ChatApplicationService;

/**
 * Controller API pour les fonctionnalités de chat
 */
class ChatApiController extends BaseApiController
{
    private ChatApplicationService $chatService;
    
    public function __construct()
    {
        parent::__construct();
        $this->chatService = $this->container->get(ChatApplicationService::class);
    }
    
    /**
     * Créer une nouvelle conversation
     */
    public function createConversation(ApiRequest $request): ApiResponse
    {
        try {
            $data = $request->getJsonBody();
            $userId = $request->getUserId();
            
            $transcriptionId = $data['transcription_id'] ?? null;
            $title = $data['title'] ?? 'Nouvelle conversation';
            
            $conversationId = $this->chatService->createConversation(
                $userId,
                $transcriptionId,
                $title
            );
            
            return ApiResponse::success([
                'conversation_id' => $conversationId,
                'message' => 'Conversation créée avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la création de la conversation', 500, [
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtenir les conversations d'un utilisateur
     */
    public function getConversations(ApiRequest $request): ApiResponse
    {
        try {
            $userId = $request->getUserId();
            $page = $request->getQueryParam('page', 1);
            $limit = $request->getQueryParam('limit', 20);
            
            $conversations = $this->chatService->getUserConversations(
                $userId,
                $page,
                $limit
            );
            
            return ApiResponse::success([
                'conversations' => array_map(fn($conv) => [
                    'id' => $conv->id(),
                    'title' => $conv->title(),
                    'transcription_id' => $conv->transcriptionId(),
                    'created_at' => $conv->createdAt()->format('c'),
                    'updated_at' => $conv->updatedAt()->format('c'),
                    'message_count' => $conv->messageCount()
                ], $conversations)
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la récupération des conversations', 500, [
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Envoyer un message dans une conversation
     */
    public function sendMessage(ApiRequest $request): ApiResponse
    {
        try {
            $data = $request->getJsonBody();
            $userId = $request->getUserId();
            
            $conversationId = $request->getPathParam('id');
            $message = $data['message'] ?? '';
            $context = $data['context'] ?? [];
            
            if (empty($message)) {
                return ApiResponse::error('Le message ne peut pas être vide', 400);
            }
            
            $response = $this->chatService->sendMessage(
                $conversationId,
                $userId,
                $message,
                $context
            );
            
            return ApiResponse::success([
                'message_id' => $response['message_id'],
                'response' => $response['response'],
                'timestamp' => $response['timestamp']
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'envoi du message', 500, [
                'details' => $e->getMessage()
            ]);
        }
    }
}