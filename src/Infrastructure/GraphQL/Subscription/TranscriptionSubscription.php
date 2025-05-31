<?php

namespace Infrastructure\GraphQL\Subscription;

use TheCodingMachine\GraphQLite\Annotations\Subscription;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Infrastructure\GraphQL\Type\TranscriptionUpdateType;
use React\Socket\SocketServer;
use React\Socket\ConnectionInterface;

/**
 * Subscriptions GraphQL pour les mises à jour temps réel
 */
class TranscriptionSubscription
{
    private array $subscribers = [];
    
    /**
     * @Subscription
     * @Logged
     */
    public function transcriptionUpdated(string $transcriptionId): TranscriptionUpdateType
    {
        // Cette méthode est appelée pour configurer la subscription
        // Le vrai stream sera géré par WebSocket/SSE
        
        return new TranscriptionUpdateType(
            $transcriptionId,
            'subscribed',
            'Subscription established'
        );
    }
    
    /**
     * @Subscription
     * @Logged
     */
    public function userTranscriptionUpdates(): TranscriptionUpdateType
    {
        // Subscription pour toutes les transcriptions de l'utilisateur
        
        return new TranscriptionUpdateType(
            'user',
            'subscribed',
            'User transcriptions subscription established'
        );
    }
    
    /**
     * @Subscription
     * @Logged
     */
    public function transcriptionProgress(string $transcriptionId): TranscriptionProgressType
    {
        // Subscription pour le progrès de traitement
        
        return new TranscriptionProgressType(
            $transcriptionId,
            0,
            'starting'
        );
    }
    
    /**
     * Émet une mise à jour pour une transcription
     */
    public function emitTranscriptionUpdate(
        Transcription $transcription,
        string $event,
        ?string $message = null
    ): void {
        $update = new TranscriptionUpdateType(
            $transcription->id()->value(),
            $event,
            $message ?? "Transcription {$event}",
            $transcription
        );
        
        // Broadcaster vers les WebSockets connectés
        $this->broadcastUpdate($update);
    }
    
    /**
     * Émet un progrès de traitement
     */
    public function emitProgress(
        string $transcriptionId,
        int $progress,
        string $stage
    ): void {
        $progressUpdate = new TranscriptionProgressType(
            $transcriptionId,
            $progress,
            $stage
        );
        
        $this->broadcastProgress($progressUpdate);
    }
    
    /**
     * Ajoute un subscriber WebSocket
     */
    public function addSubscriber(string $userId, ConnectionInterface $connection): void
    {
        if (!isset($this->subscribers[$userId])) {
            $this->subscribers[$userId] = [];
        }
        
        $this->subscribers[$userId][] = $connection;
        
        // Nettoyer quand la connexion se ferme
        $connection->on('close', function() use ($userId, $connection) {
            $this->removeSubscriber($userId, $connection);
        });
    }
    
    /**
     * Supprime un subscriber
     */
    public function removeSubscriber(string $userId, ConnectionInterface $connection): void
    {
        if (isset($this->subscribers[$userId])) {
            $this->subscribers[$userId] = array_filter(
                $this->subscribers[$userId],
                fn($conn) => $conn !== $connection
            );
            
            if (empty($this->subscribers[$userId])) {
                unset($this->subscribers[$userId]);
            }
        }
    }
    
    /**
     * Broadcaste une mise à jour
     */
    private function broadcastUpdate(TranscriptionUpdateType $update): void
    {
        $message = json_encode([
            'type' => 'transcription_update',
            'data' => [
                'transcriptionId' => $update->getTranscriptionId(),
                'event' => $update->getEvent(),
                'message' => $update->getMessage(),
                'timestamp' => time()
            ]
        ]);
        
        foreach ($this->subscribers as $connections) {
            foreach ($connections as $connection) {
                $connection->write($message);
            }
        }
    }
    
    /**
     * Broadcaste un progrès
     */
    private function broadcastProgress(TranscriptionProgressType $progress): void
    {
        $message = json_encode([
            'type' => 'transcription_progress',
            'data' => [
                'transcriptionId' => $progress->getTranscriptionId(),
                'progress' => $progress->getProgress(),
                'stage' => $progress->getStage(),
                'timestamp' => time()
            ]
        ]);
        
        foreach ($this->subscribers as $connections) {
            foreach ($connections as $connection) {
                $connection->write($message);
            }
        }
    }
}

/**
 * @Type
 */
class TranscriptionUpdateType
{
    public function __construct(
        private string $transcriptionId,
        private string $event,
        private string $message,
        private ?Transcription $transcription = null
    ) {}
    
    /**
     * @Field
     */
    public function getTranscriptionId(): string
    {
        return $this->transcriptionId;
    }
    
    /**
     * @Field
     */
    public function getEvent(): string
    {
        return $this->event;
    }
    
    /**
     * @Field
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * @Field
     */
    public function getTranscription(): ?Transcription
    {
        return $this->transcription;
    }
    
    /**
     * @Field
     */
    public function getTimestamp(): int
    {
        return time();
    }
}

/**
 * @Type
 */
class TranscriptionProgressType
{
    public function __construct(
        private string $transcriptionId,
        private int $progress,
        private string $stage
    ) {}
    
    /**
     * @Field
     */
    public function getTranscriptionId(): string
    {
        return $this->transcriptionId;
    }
    
    /**
     * @Field
     */
    public function getProgress(): int
    {
        return $this->progress;
    }
    
    /**
     * @Field
     */
    public function getStage(): string
    {
        return $this->stage;
    }
    
    /**
     * @Field
     */
    public function getPercentage(): float
    {
        return min(100, max(0, $this->progress));
    }
}