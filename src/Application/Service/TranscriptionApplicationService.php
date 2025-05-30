<?php

namespace Application\Service;

use Application\Handler\CommandBus;
use Application\Handler\QueryBus;
use Application\Command\Transcription\CreateTranscriptionCommand;
use Application\Command\Transcription\StartProcessingCommand;
use Application\Command\Transcription\CompleteTranscriptionCommand;
use Application\Command\Transcription\FailTranscriptionCommand;
use Application\Query\Transcription\GetTranscriptionQuery;
use Application\Query\Transcription\ListTranscriptionsQuery;
use Application\Query\Transcription\GetTranscriptionStatsQuery;
use Application\DTO\Transcription\TranscriptionDTO;

/**
 * Service d'application pour orchestrer les use cases de transcription
 * 
 * Ce service encapsule la logique métier complexe et coordonne
 * les interactions entre Commands, Queries et services externes.
 */
final class TranscriptionApplicationService
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly ?EventService $eventService = null
    ) {}
    
    /**
     * Use Case: Créer une nouvelle transcription depuis un fichier uploadé
     */
    public function createFromUpload(
        string $userId,
        string $originalFilename,
        string $tempFilePath,
        string $mimeType,
        int $fileSize,
        string $language,
        bool $isPriority = false,
        ?float $estimatedDuration = null
    ): TranscriptionDTO {
        // Valider les paramètres métier
        $this->validateFileUpload($tempFilePath, $mimeType, $fileSize);
        
        // Déplacer le fichier vers un stockage permanent
        $permanentPath = $this->moveToSecureStorage($tempFilePath, $originalFilename);
        
        // Créer la commande
        $command = new CreateTranscriptionCommand(
            userId: $userId,
            originalFilename: $originalFilename,
            filePath: $permanentPath,
            mimeType: $mimeType,
            fileSize: $fileSize,
            language: $language,
            isPriority: $isPriority,
            duration: $estimatedDuration
        );
        
        // Exécuter la création
        $transcription = $this->commandBus->execute($command);
        
        // Dispatcher les événements domain si le service est disponible
        if ($this->eventService) {
            // Dans une vraie implémentation, récupérer l'aggregate depuis le repository
            // pour accéder aux événements domain générés
            // $aggregate = $this->transcriptionRepository->findById($transcription->getId());
            // $this->eventService->dispatchEventsFrom($aggregate);
        }
        
        // Si c'est prioritaire, démarrer immédiatement le processing
        if ($isPriority) {
            $this->startProcessingAsync($transcription->getId());
        }
        
        return $transcription;
    }
    
    /**
     * Use Case: Créer une transcription depuis une URL YouTube
     */
    public function createFromYouTube(
        string $userId,
        string $youtubeUrl,
        string $language,
        bool $isPriority = false
    ): TranscriptionDTO {
        // Extraire les métadonnées YouTube
        $youtubeData = $this->extractYouTubeMetadata($youtubeUrl);
        
        // Télécharger l'audio YouTube vers un fichier temporaire
        $audioPath = $this->downloadYouTubeAudio($youtubeData['video_id']);
        
        // Obtenir les infos du fichier téléchargé
        $fileInfo = $this->getFileInfo($audioPath);
        
        // Créer la commande
        $command = new CreateTranscriptionCommand(
            userId: $userId,
            originalFilename: $youtubeData['title'] . '.mp3',
            filePath: $audioPath,
            mimeType: 'audio/mpeg',
            fileSize: $fileInfo['size'],
            language: $language,
            isPriority: $isPriority,
            duration: $youtubeData['duration'] ?? null,
            youtubeUrl: $youtubeUrl,
            youtubeTitle: $youtubeData['title'],
            youtubeVideoId: $youtubeData['video_id']
        );
        
        // Exécuter la création
        $transcription = $this->commandBus->execute($command);
        
        // Si c'est prioritaire, démarrer immédiatement le processing
        if ($isPriority) {
            $this->startProcessingAsync($transcription->getId());
        }
        
        return $transcription;
    }
    
    /**
     * Use Case: Traitement complet d'une transcription
     */
    public function processTranscription(string $transcriptionId): TranscriptionDTO
    {
        try {
            // Récupérer la transcription
            $transcription = $this->getTranscription($transcriptionId);
            
            if (!$transcription) {
                throw new \DomainException("Transcription not found: {$transcriptionId}");
            }
            
            if (!$transcription->isPending()) {
                throw new \DomainException("Transcription is not in pending status");
            }
            
            // Démarrer le processing
            $this->commandBus->execute(new StartProcessingCommand($transcriptionId));
            
            // Préprocesser le fichier audio si nécessaire
            $processedPath = $this->preprocessAudioFile($transcription);
            
            if ($processedPath !== null) {
                // Mettre à jour avec le fichier preprocessé
                $this->commandBus->execute(new StartProcessingCommand(
                    transcriptionId: $transcriptionId,
                    processedFilePath: $processedPath
                ));
            }
            
            // Effectuer la transcription via le service externe (OpenAI, Whisper, etc.)
            $transcriptionResult = $this->performTranscription($transcription);
            
            // Compléter la transcription
            $completeCommand = new CompleteTranscriptionCommand(
                transcriptionId: $transcriptionId,
                transcribedText: $transcriptionResult['text'],
                segments: $transcriptionResult['segments'] ?? null,
                actualDuration: $transcriptionResult['duration'] ?? null,
                detectedLanguage: $transcriptionResult['detected_language'] ?? null,
                metadata: $transcriptionResult['metadata'] ?? null
            );
            
            return $this->commandBus->execute($completeCommand);
            
        } catch (\Exception $e) {
            // En cas d'erreur, marquer la transcription comme échouée
            $this->commandBus->execute(new FailTranscriptionCommand(
                transcriptionId: $transcriptionId,
                failureReason: $e->getMessage(),
                errorCode: $e->getCode() ? (string) $e->getCode() : null
            ));
            
            throw $e;
        }
    }
    
    /**
     * Use Case: Obtenir une transcription avec permissions
     */
    public function getTranscription(string $transcriptionId, ?string $userId = null): ?TranscriptionDTO
    {
        $query = new GetTranscriptionQuery(
            transcriptionId: $transcriptionId,
            userId: $userId
        );
        
        return $this->queryBus->execute($query);
    }
    
    /**
     * Use Case: Lister les transcriptions d'un utilisateur avec filtres
     */
    public function getUserTranscriptions(
        string $userId,
        ?string $status = null,
        ?string $language = null,
        ?bool $isYouTube = null,
        int $page = 1,
        int $limit = 20,
        string $sortBy = 'created_at',
        string $sortDirection = 'DESC'
    ): array {
        $query = new ListTranscriptionsQuery(
            userId: $userId,
            status: $status,
            language: $language,
            isYouTube: $isYouTube,
            page: $page,
            limit: $limit,
            sortBy: $sortBy,
            sortDirection: $sortDirection
        );
        
        return $this->queryBus->execute($query);
    }
    
    /**
     * Use Case: Obtenir les statistiques de transcription
     */
    public function getTranscriptionStats(
        ?string $userId = null,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
        bool $includeDetailed = false
    ): array {
        $query = new GetTranscriptionStatsQuery(
            userId: $userId,
            fromDate: $fromDate,
            toDate: $toDate,
            includeDetailed: $includeDetailed
        );
        
        return $this->queryBus->execute($query);
    }
    
    /**
     * Use Case: Retry d'une transcription échouée
     */
    public function retryTranscription(string $transcriptionId): TranscriptionDTO
    {
        $transcription = $this->getTranscription($transcriptionId);
        
        if (!$transcription) {
            throw new \DomainException("Transcription not found: {$transcriptionId}");
        }
        
        if (!$transcription->isFailed()) {
            throw new \DomainException("Can only retry failed transcriptions");
        }
        
        // Créer une nouvelle transcription basée sur la précédente
        // mais avec un nouvel ID (logique métier spécifique)
        return $this->processTranscription($transcriptionId);
    }
    
    // Méthodes privées pour la logique métier
    
    private function validateFileUpload(string $filePath, string $mimeType, int $fileSize): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }
        
        // Validation des types MIME autorisés
        $allowedMimeTypes = [
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/m4a',
            'video/mp4', 'video/mpeg', 'video/quicktime'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \InvalidArgumentException("Unsupported file type: {$mimeType}");
        }
        
        // Validation de la taille (25MB max)
        if ($fileSize > 25 * 1024 * 1024) {
            throw new \InvalidArgumentException("File too large. Maximum size is 25MB.");
        }
    }
    
    private function moveToSecureStorage(string $tempPath, string $originalFilename): string
    {
        $uploadDir = dirname(__DIR__, 2) . '/uploads/audio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $safeFilename = date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalFilename);
        $permanentPath = $uploadDir . $safeFilename;
        
        if (!move_uploaded_file($tempPath, $permanentPath)) {
            if (!rename($tempPath, $permanentPath)) {
                throw new \RuntimeException("Failed to move file to secure storage");
            }
        }
        
        return $permanentPath;
    }
    
    private function extractYouTubeMetadata(string $url): array
    {
        // Dans une vraie implémentation, utiliser youtube-dl ou yt-dlp
        // Ici on simule l'extraction de métadonnées
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches);
        
        if (!isset($matches[1])) {
            throw new \InvalidArgumentException("Invalid YouTube URL");
        }
        
        return [
            'video_id' => $matches[1],
            'title' => 'YouTube Video ' . $matches[1], // Simulé
            'duration' => 180, // Simulé (3 minutes)
            'url' => $url
        ];
    }
    
    private function downloadYouTubeAudio(string $videoId): string
    {
        // Dans une vraie implémentation, utiliser youtube-dl pour télécharger l'audio
        // Ici on simule en créant un fichier temporaire
        $tempDir = sys_get_temp_dir() . '/youtube_audio/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $audioPath = $tempDir . $videoId . '.mp3';
        file_put_contents($audioPath, 'simulated audio content for ' . $videoId);
        
        return $audioPath;
    }
    
    private function getFileInfo(string $filePath): array
    {
        return [
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath) ?: 'audio/mpeg'
        ];
    }
    
    private function startProcessingAsync(string $transcriptionId): void
    {
        // Dans une vraie implémentation, envoyer le job à une queue
        // Ici on simule le démarrage asynchrone
        error_log("Async processing started for transcription: {$transcriptionId}");
    }
    
    private function preprocessAudioFile(TranscriptionDTO $transcription): ?string
    {
        // Si le fichier a besoin de preprocessing (conversion de format, etc.)
        if (str_contains($transcription->getOriginalFilename(), '.mp4')) {
            // Simuler la conversion MP4 -> MP3
            $processedPath = sys_get_temp_dir() . '/processed_' . $transcription->getId() . '.mp3';
            file_put_contents($processedPath, 'processed audio content');
            return $processedPath;
        }
        
        return null;
    }
    
    private function performTranscription(TranscriptionDTO $transcription): array
    {
        // Dans une vraie implémentation, appeler OpenAI Whisper API
        // Ici on simule une transcription
        return [
            'text' => 'Ceci est une transcription simulée pour le fichier ' . $transcription->getOriginalFilename(),
            'segments' => [
                ['text' => 'Ceci est', 'start' => 0.0, 'end' => 1.0],
                ['text' => 'une transcription', 'start' => 1.0, 'end' => 2.5],
                ['text' => 'simulée', 'start' => 2.5, 'end' => 3.5]
            ],
            'duration' => 3.5,
            'detected_language' => $transcription->getLanguage(),
            'metadata' => [
                'model' => 'whisper-1',
                'processing_time' => 1.2,
                'confidence' => 0.95
            ]
        ];
    }
}