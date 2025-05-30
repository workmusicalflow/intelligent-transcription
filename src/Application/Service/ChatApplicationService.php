<?php

namespace Application\Service;

use Application\Handler\CommandBus;
use Application\Handler\QueryBus;
use Application\Command\Chat\SendChatMessageCommand;
use Application\Query\Chat\GetChatPageDataQuery;
use Application\Query\Transcription\GetTranscriptionQuery;

/**
 * Service d'application pour les fonctionnalités de chat contextuel
 */
final class ChatApplicationService
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly TranscriptionApplicationService $transcriptionService
    ) {}
    
    /**
     * Use Case: Envoyer un message dans le chat avec contexte de transcription
     */
    public function sendMessage(
        string $userId,
        string $message,
        ?string $transcriptionId = null,
        ?string $conversationId = null,
        ?string $language = null
    ): array {
        // Récupérer le contexte de transcription si fourni
        $transcriptionContext = null;
        if ($transcriptionId) {
            $transcriptionContext = $this->transcriptionService->getTranscription($transcriptionId, $userId);
            
            if (!$transcriptionContext) {
                throw new \DomainException("Transcription not found or access denied: {$transcriptionId}");
            }
            
            if (!$transcriptionContext->isCompleted()) {
                throw new \DomainException("Cannot chat about incomplete transcription");
            }
        }
        
        // Préparer le contexte enrichi
        $context = $this->buildChatContext($transcriptionContext, $conversationId);
        
        // Créer la commande
        $command = new SendChatMessageCommand(
            userId: $userId,
            message: $message,
            transcriptionId: $transcriptionId,
            conversationId: $conversationId,
            language: $language,
            context: $context
        );
        
        // Envoyer le message et obtenir la réponse
        $chatResult = $this->commandBus->execute($command);
        
        // Générer la réponse IA contextuelle
        $aiResponse = $this->generateAIResponse($message, $context, $language);
        
        return [
            'user_message' => [
                'id' => $chatResult['message_id'] ?? uniqid(),
                'content' => $message,
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $userId
            ],
            'ai_response' => [
                'id' => uniqid('ai_'),
                'content' => $aiResponse['content'],
                'timestamp' => date('Y-m-d H:i:s'),
                'model' => $aiResponse['model'],
                'tokens_used' => $aiResponse['tokens_used'] ?? null
            ],
            'context' => [
                'transcription_id' => $transcriptionId,
                'conversation_id' => $conversationId,
                'has_transcription_context' => $transcriptionContext !== null
            ]
        ];
    }
    
    /**
     * Use Case: Obtenir les données de la page de chat
     */
    public function getChatPageData(
        string $userId,
        ?string $transcriptionId = null,
        ?string $conversationId = null,
        bool $includeHistory = true,
        int $historyLimit = 50
    ): array {
        $query = new GetChatPageDataQuery(
            userId: $userId,
            transcriptionId: $transcriptionId,
            conversationId: $conversationId,
            includeHistory: $includeHistory,
            historyLimit: $historyLimit
        );
        
        $pageData = $this->queryBus->execute($query);
        
        // Enrichir avec les données de transcription si disponible
        if ($transcriptionId) {
            $transcription = $this->transcriptionService->getTranscription($transcriptionId, $userId);
            $pageData['transcription'] = $transcription?->toArray();
        }
        
        return $pageData;
    }
    
    /**
     * Use Case: Exporter une conversation
     */
    public function exportConversation(
        string $conversationId,
        string $userId,
        string $format = 'json'
    ): array {
        // Récupérer la conversation complète
        $conversation = $this->getConversationHistory($conversationId, $userId);
        
        if (empty($conversation)) {
            throw new \DomainException("Conversation not found or empty: {$conversationId}");
        }
        
        // Générer l'export selon le format
        $exportData = match ($format) {
            'json' => $this->exportAsJson($conversation),
            'txt' => $this->exportAsText($conversation),
            'pdf' => $this->exportAsPdf($conversation),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}")
        };
        
        // Sauvegarder le fichier d'export
        $filename = $this->saveExportFile($exportData, $format, $conversationId);
        
        return [
            'filename' => $filename,
            'path' => $this->getExportPath($filename),
            'format' => $format,
            'size' => strlen($exportData['content']),
            'message_count' => count($conversation),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Use Case: Rechercher dans l'historique de conversation
     */
    public function searchConversationHistory(
        string $userId,
        string $searchTerm,
        ?string $transcriptionId = null,
        int $limit = 20
    ): array {
        // Rechercher dans les messages de l'utilisateur
        $results = $this->performConversationSearch($userId, $searchTerm, $transcriptionId, $limit);
        
        // Enrichir les résultats avec le contexte
        $enrichedResults = [];
        foreach ($results as $result) {
            $enrichedResults[] = [
                'message' => $result,
                'transcription' => $result['transcription_id'] 
                    ? $this->transcriptionService->getTranscription($result['transcription_id'], $userId)
                    : null,
                'relevance_score' => $this->calculateRelevanceScore($searchTerm, $result['content'])
            ];
        }
        
        // Trier par score de pertinence
        usort($enrichedResults, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);
        
        return [
            'query' => $searchTerm,
            'results' => $enrichedResults,
            'total_found' => count($enrichedResults),
            'search_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        ];
    }
    
    /**
     * Use Case: Obtenir les suggestions de questions pour une transcription
     */
    public function getSuggestedQuestions(string $transcriptionId, string $userId): array
    {
        $transcription = $this->transcriptionService->getTranscription($transcriptionId, $userId);
        
        if (!$transcription || !$transcription->isCompleted()) {
            return [];
        }
        
        // Analyser le contenu pour générer des suggestions
        return $this->generateQuestionSuggestions($transcription);
    }
    
    // Méthodes privées
    
    private function buildChatContext(?object $transcription, ?string $conversationId): array
    {
        $context = [];
        
        if ($transcription) {
            $context['transcription'] = [
                'id' => $transcription->getId(),
                'text' => $transcription->getText(),
                'language' => $transcription->getLanguage(),
                'duration' => $transcription->getDuration(),
                'word_count' => str_word_count($transcription->getText() ?? ''),
                'filename' => $transcription->getOriginalFilename()
            ];
            
            // Ajouter un excerpt pour le contexte IA
            $context['transcription']['excerpt'] = $this->getTranscriptionExcerpt($transcription->getText());
        }
        
        if ($conversationId) {
            $context['conversation'] = [
                'id' => $conversationId,
                'recent_messages' => $this->getRecentMessages($conversationId, 5)
            ];
        }
        
        return $context;
    }
    
    private function generateAIResponse(string $message, array $context, ?string $language): array
    {
        // Dans une vraie implémentation, appeler OpenAI GPT
        // Ici on simule une réponse contextuelle
        
        $hasTranscription = isset($context['transcription']);
        $transcriptionText = $context['transcription']['text'] ?? '';
        
        if ($hasTranscription) {
            $response = "Je peux vous aider à analyser votre transcription \"" . 
                       ($context['transcription']['filename'] ?? 'fichier') . "\". ";
            
            if (str_contains(strtolower($message), 'résumé') || str_contains(strtolower($message), 'résume')) {
                $response .= "Voici un résumé : " . $this->generateSummary($transcriptionText);
            } elseif (str_contains(strtolower($message), 'points clés') || str_contains(strtolower($message), 'important')) {
                $response .= "Voici les points clés : " . $this->extractKeyPoints($transcriptionText);
            } else {
                $response .= "D'après votre transcription, " . $this->generateContextualResponse($message, $transcriptionText);
            }
        } else {
            $response = "Je suis là pour vous aider avec vos questions. " . 
                       "Si vous avez une transcription, n'hésitez pas à me demander de l'analyser !";
        }
        
        return [
            'content' => $response,
            'model' => 'gpt-4-simulated',
            'tokens_used' => strlen($response) / 4 // Approximation
        ];
    }
    
    private function getTranscriptionExcerpt(?string $text, int $maxLength = 500): string
    {
        if (!$text || strlen($text) <= $maxLength) {
            return $text ?? '';
        }
        
        return substr($text, 0, $maxLength) . '...';
    }
    
    private function getRecentMessages(string $conversationId, int $limit): array
    {
        // Simuler la récupération des messages récents
        return [
            ['role' => 'user', 'content' => 'Message utilisateur précédent', 'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes'))],
            ['role' => 'assistant', 'content' => 'Réponse précédente', 'timestamp' => date('Y-m-d H:i:s', strtotime('-4 minutes'))]
        ];
    }
    
    private function generateSummary(string $text): string
    {
        // Simulation d'un résumé intelligent
        $words = explode(' ', $text);
        $wordCount = count($words);
        
        if ($wordCount < 50) {
            return "Ce texte court contient principalement : " . implode(' ', array_slice($words, 0, 20));
        }
        
        return "Ce texte de {$wordCount} mots traite de... [résumé simulé basé sur l'analyse du contenu]";
    }
    
    private function extractKeyPoints(string $text): string
    {
        // Simulation d'extraction de points clés
        return "1. Premier point important identifié\n2. Deuxième élément clé\n3. Conclusion principale";
    }
    
    private function generateContextualResponse(string $question, string $context): string
    {
        // Simulation de réponse contextuelle
        return "en analysant le contenu, je peux voir que votre question porte sur des éléments mentionnés dans la transcription.";
    }
    
    private function getConversationHistory(string $conversationId, string $userId): array
    {
        // Simuler la récupération de l'historique complet
        return [
            ['id' => 1, 'role' => 'user', 'content' => 'Premier message', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ['id' => 2, 'role' => 'assistant', 'content' => 'Première réponse', 'timestamp' => date('Y-m-d H:i:s', strtotime('-59 minutes'))],
            ['id' => 3, 'role' => 'user', 'content' => 'Deuxième message', 'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
        ];
    }
    
    private function exportAsJson(array $conversation): array
    {
        return [
            'content' => json_encode($conversation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'mime_type' => 'application/json'
        ];
    }
    
    private function exportAsText(array $conversation): array
    {
        $text = "Conversation Export\n";
        $text .= "==================\n\n";
        
        foreach ($conversation as $message) {
            $text .= "[{$message['timestamp']}] {$message['role']}: {$message['content']}\n\n";
        }
        
        return [
            'content' => $text,
            'mime_type' => 'text/plain'
        ];
    }
    
    private function exportAsPdf(array $conversation): array
    {
        // Simulation - dans une vraie implémentation, utiliser une lib PDF
        return [
            'content' => 'PDF content simulation for conversation export',
            'mime_type' => 'application/pdf'
        ];
    }
    
    private function saveExportFile(array $exportData, string $format, string $conversationId): string
    {
        $exportDir = dirname(__DIR__, 2) . '/exports/conversations/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filename = "conversation_{$conversationId}_" . date('Y-m-d_H-i-s') . ".{$format}";
        $filepath = $exportDir . $filename;
        
        file_put_contents($filepath, $exportData['content']);
        
        return $filename;
    }
    
    private function getExportPath(string $filename): string
    {
        return '/exports/conversations/' . $filename;
    }
    
    private function performConversationSearch(string $userId, string $searchTerm, ?string $transcriptionId, int $limit): array
    {
        // Simulation de recherche dans les conversations
        return [
            ['id' => 1, 'content' => 'Message contenant le terme recherché', 'transcription_id' => $transcriptionId, 'timestamp' => date('Y-m-d H:i:s')],
            ['id' => 2, 'content' => 'Autre message pertinent', 'transcription_id' => null, 'timestamp' => date('Y-m-d H:i:s')]
        ];
    }
    
    private function calculateRelevanceScore(string $searchTerm, string $content): float
    {
        // Calcul simple de pertinence basé sur la fréquence du terme
        $termCount = substr_count(strtolower($content), strtolower($searchTerm));
        $contentLength = strlen($content);
        
        return $contentLength > 0 ? ($termCount / $contentLength) * 100 : 0.0;
    }
    
    private function generateQuestionSuggestions(object $transcription): array
    {
        $text = $transcription->getText() ?? '';
        $filename = $transcription->getOriginalFilename();
        
        // Suggestions génériques basées sur le type de contenu
        $suggestions = [
            "Peux-tu me faire un résumé de cette transcription ?",
            "Quels sont les points clés de ce contenu ?",
            "Y a-t-il des informations importantes à retenir ?"
        ];
        
        // Suggestions spécifiques basées sur le contenu
        if (str_contains(strtolower($text), 'réunion') || str_contains(strtolower($filename), 'meeting')) {
            $suggestions[] = "Quelles sont les décisions prises dans cette réunion ?";
            $suggestions[] = "Y a-t-il des actions à faire suite à cette réunion ?";
        }
        
        if (str_contains(strtolower($text), 'interview') || str_contains(strtolower($filename), 'interview')) {
            $suggestions[] = "Quelles sont les qualifications mentionnées par le candidat ?";
            $suggestions[] = "Quelle est l'impression générale de cet entretien ?";
        }
        
        return array_slice($suggestions, 0, 5); // Max 5 suggestions
    }
}