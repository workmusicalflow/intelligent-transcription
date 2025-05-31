<?php

namespace Infrastructure\GraphQL\Controller;

use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Common\ValueObject\UserId;
use Application\Transcription\Command\CreateTranscriptionCommand;
use Application\Transcription\Command\ProcessTranscriptionCommand;
use Infrastructure\Container\ServiceLocator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Controller GraphQL pour les mutations de transcription
 */
class TranscriptionMutationController
{
    /**
     * @Mutation
     * @Logged
     */
    public function createTranscription(
        string $language,
        ?UploadedFileInterface $audioFile = null,
        ?string $youtubeUrl = null
    ): TranscriptionResult {
        try {
            // Validation
            if (!$audioFile && !$youtubeUrl) {
                throw new \InvalidArgumentException('Either audioFile or youtubeUrl must be provided');
            }
            
            $userId = $this->getCurrentUserId();
            $lang = new Language($language);
            
            // Gérer le fichier ou l'URL
            if ($audioFile) {
                $audio = $this->handleFileUpload($audioFile);
            } else {
                $audio = $this->handleYouTubeUrl($youtubeUrl);
            }
            
            // Créer la commande
            $command = new CreateTranscriptionCommand($userId, $audio, $lang);
            $handler = ServiceLocator::get(\Application\Transcription\Handler\CreateTranscriptionHandler::class);
            
            $transcriptionId = $handler->handle($command);
            
            return new TranscriptionResult(
                true,
                'Transcription created successfully',
                $transcriptionId->value()
            );
            
        } catch (\Exception $e) {
            return new TranscriptionResult(
                false,
                $e->getMessage(),
                null
            );
        }
    }
    
    /**
     * @Mutation
     * @Logged
     */
    public function processTranscription(string $id): TranscriptionResult
    {
        try {
            $transcriptionId = new TranscriptionId($id);
            $userId = $this->getCurrentUserId();
            
            // Vérifier que l'utilisateur a accès à cette transcription
            $repository = ServiceLocator::get(\Domain\Transcription\Repository\TranscriptionRepository::class);
            $transcription = $repository->findById($transcriptionId);
            
            if (!$transcription || !$transcription->userId()->equals($userId)) {
                throw new \RuntimeException('Transcription not found or access denied');
            }
            
            if (!$transcription->status()->isPending()) {
                throw new \RuntimeException('Transcription is not in pending status');
            }
            
            // Lancer le traitement
            $command = new ProcessTranscriptionCommand($transcriptionId);
            $handler = ServiceLocator::get(\Application\Transcription\Handler\ProcessTranscriptionHandler::class);
            
            $handler->handle($command);
            
            return new TranscriptionResult(
                true,
                'Processing started',
                $id
            );
            
        } catch (\Exception $e) {
            return new TranscriptionResult(
                false,
                $e->getMessage(),
                null
            );
        }
    }
    
    /**
     * @Mutation
     * @Logged
     */
    public function deleteTranscription(string $id): TranscriptionResult
    {
        try {
            $transcriptionId = new TranscriptionId($id);
            $userId = $this->getCurrentUserId();
            
            // Vérifier l'accès
            $repository = ServiceLocator::get(\Domain\Transcription\Repository\TranscriptionRepository::class);
            $transcription = $repository->findById($transcriptionId);
            
            if (!$transcription || !$transcription->userId()->equals($userId)) {
                throw new \RuntimeException('Transcription not found or access denied');
            }
            
            // Supprimer
            $repository->delete($transcriptionId);
            
            return new TranscriptionResult(
                true,
                'Transcription deleted successfully',
                $id
            );
            
        } catch (\Exception $e) {
            return new TranscriptionResult(
                false,
                $e->getMessage(),
                null
            );
        }
    }
    
    /**
     * @Mutation
     * @Logged
     */
    public function bulkDeleteTranscriptions(array $ids): BulkOperationResult
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($ids as $id) {
            try {
                $result = $this->deleteTranscription($id);
                if ($result->success) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
                $results[] = $result;
            } catch (\Exception $e) {
                $errorCount++;
                $results[] = new TranscriptionResult(false, $e->getMessage(), $id);
            }
        }
        
        return new BulkOperationResult(
            $successCount,
            $errorCount,
            $results
        );
    }
    
    /**
     * Gère l'upload d'un fichier
     */
    private function handleFileUpload(UploadedFileInterface $file): AudioFile
    {
        $uploadDir = __DIR__ . '/../../../../../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = uniqid() . '_' . $file->getClientFilename();
        $uploadPath = $uploadDir . $filename;
        
        $file->moveTo($uploadPath);
        
        return AudioFile::fromPath($uploadPath);
    }
    
    /**
     * Gère une URL YouTube
     */
    private function handleYouTubeUrl(string $url): AudioFile
    {
        $youtubeService = ServiceLocator::get(\Infrastructure\External\YouTube\YouTubeDownloader::class);
        $audioPath = $youtubeService->downloadAudio($url);
        
        return AudioFile::fromPath($audioPath);
    }
    
    /**
     * Récupère l'ID de l'utilisateur actuel
     */
    private function getCurrentUserId(): UserId
    {
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        return new UserId($userId);
    }
}

/**
 * @Type
 */
class TranscriptionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $transcriptionId = null
    ) {}
    
    /**
     * @Field
     */
    public function getSuccess(): bool
    {
        return $this->success;
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
    public function getTranscriptionId(): ?string
    {
        return $this->transcriptionId;
    }
}

/**
 * @Type
 */
class BulkOperationResult
{
    public function __construct(
        private int $successCount,
        private int $errorCount,
        private array $results
    ) {}
    
    /**
     * @Field
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
    
    /**
     * @Field
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }
    
    /**
     * @Field
     * @return TranscriptionResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
    
    /**
     * @Field
     */
    public function getTotalCount(): int
    {
        return $this->successCount + $this->errorCount;
    }
}