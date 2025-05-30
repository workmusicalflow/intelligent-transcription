<?php

namespace Application\Handler\Command;

use Application\Handler\CommandHandlerInterface;
use Application\Command\CommandInterface;
use Application\Command\Transcription\CreateTranscriptionCommand;
use Application\Command\Transcription\StartProcessingCommand;
use Application\Command\Transcription\CompleteTranscriptionCommand;
use Application\Command\Transcription\FailTranscriptionCommand;
use Application\DTO\Transcription\TranscriptionDTO;

use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\ValueObject\YouTubeMetadata;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Common\ValueObject\UserId;

/**
 * Handler pour toutes les commandes relatives aux transcriptions
 */
final class TranscriptionCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository
    ) {}
    
    public function handle(CommandInterface $command): mixed
    {
        return match (get_class($command)) {
            CreateTranscriptionCommand::class => $this->handleCreateTranscription($command),
            StartProcessingCommand::class => $this->handleStartProcessing($command),
            CompleteTranscriptionCommand::class => $this->handleCompleteTranscription($command),
            FailTranscriptionCommand::class => $this->handleFailTranscription($command),
            default => throw new \InvalidArgumentException('Unsupported command: ' . get_class($command))
        };
    }
    
    public function canHandle(CommandInterface $command): bool
    {
        return match (get_class($command)) {
            CreateTranscriptionCommand::class,
            StartProcessingCommand::class,
            CompleteTranscriptionCommand::class,
            FailTranscriptionCommand::class => true,
            default => false
        };
    }
    
    private function handleCreateTranscription(CreateTranscriptionCommand $command): TranscriptionDTO
    {
        // Créer le Value Object AudioFile
        $audioFile = AudioFile::create(
            path: $command->getFilePath(),
            originalName: $command->getOriginalFilename(),
            mimeType: $command->getMimeType(),
            size: $command->getFileSize(),
            duration: $command->getDuration()
        );
        
        // Créer le Value Object Language
        $language = Language::fromCode($command->getLanguage());
        
        // Créer le Value Object UserId
        $userId = UserId::fromString($command->getUserId());
        
        // Créer l'entité Transcription selon le type
        if ($command->isYouTubeSource()) {
            $youtubeMetadata = YouTubeMetadata::create(
                videoId: $command->getYoutubeVideoId(),
                originalUrl: $command->getYoutubeUrl(),
                title: $command->getYoutubeTitle()
            );
            
            $transcription = Transcription::createFromYouTube(
                audioFile: $audioFile,
                youtubeMetadata: $youtubeMetadata,
                language: $language,
                userId: $userId
            );
        } else {
            $transcription = Transcription::createFromFile(
                audioFile: $audioFile,
                language: $language,
                userId: $userId
            );
        }
        
        // Sauvegarder via le repository
        $this->transcriptionRepository->save($transcription);
        
        // Convertir en DTO pour le retour
        return $this->transcriptionToDTO($transcription);
    }
    
    private function handleStartProcessing(StartProcessingCommand $command): TranscriptionDTO
    {
        // Récupérer la transcription
        $transcriptionId = TranscriptionId::fromString($command->getTranscriptionId());
        $transcription = $this->transcriptionRepository->findById($transcriptionId);
        
        if (!$transcription) {
            throw new \DomainException("Transcription not found: " . $command->getTranscriptionId());
        }
        
        // Mettre à jour le fichier preprocessé si fourni
        if ($command->hasProcessedFile()) {
            $audioFile = $transcription->audioFile();
            $updatedAudioFile = $audioFile->withPreprocessedPath($command->getProcessedFilePath());
            
            // Note: Dans une implémentation complète, il faudrait une méthode pour update l'AudioFile
            // Pour l'instant, on procède au démarrage du processing
        }
        
        // Démarrer le processing
        $transcription->startProcessing();
        
        // Sauvegarder
        $this->transcriptionRepository->save($transcription);
        
        return $this->transcriptionToDTO($transcription);
    }
    
    private function handleCompleteTranscription(CompleteTranscriptionCommand $command): TranscriptionDTO
    {
        // Récupérer la transcription
        $transcriptionId = TranscriptionId::fromString($command->getTranscriptionId());
        $transcription = $this->transcriptionRepository->findById($transcriptionId);
        
        if (!$transcription) {
            throw new \DomainException("Transcription not found: " . $command->getTranscriptionId());
        }
        
        // Créer le texte transcrit
        $transcribedText = new TranscribedText(
            content: $command->getTranscribedText(),
            segments: $command->getSegments()
        );
        
        // Compléter la transcription
        $transcription->complete($transcribedText);
        
        // Sauvegarder
        $this->transcriptionRepository->save($transcription);
        
        return $this->transcriptionToDTO($transcription);
    }
    
    private function handleFailTranscription(FailTranscriptionCommand $command): TranscriptionDTO
    {
        // Récupérer la transcription
        $transcriptionId = TranscriptionId::fromString($command->getTranscriptionId());
        $transcription = $this->transcriptionRepository->findById($transcriptionId);
        
        if (!$transcription) {
            throw new \DomainException("Transcription not found: " . $command->getTranscriptionId());
        }
        
        // Marquer comme échouée
        $transcription->fail($command->getFailureReason());
        
        // Sauvegarder
        $this->transcriptionRepository->save($transcription);
        
        return $this->transcriptionToDTO($transcription);
    }
    
    private function transcriptionToDTO(Transcription $transcription): TranscriptionDTO
    {
        $audioFile = $transcription->audioFile();
        $language = $transcription->language();
        $text = $transcription->text();
        $youtubeMetadata = $transcription->youtubeMetadata();
        
        return TranscriptionDTO::fromArray([
            'id' => $transcription->id(),
            'user_id' => (string) $transcription->userId(),
            'original_filename' => $audioFile->originalName(),
            'language' => $language->code(),
            'status' => (string) $transcription->status(),
            'text' => $text?->content(),
            'youtube_url' => $youtubeMetadata?->originalUrl(),
            'youtube_title' => $youtubeMetadata?->title(),
            'segments' => $text?->segments(),
            'duration' => $audioFile->duration(),
            'file_size' => $audioFile->size(),
            'failure_reason' => $transcription->failureReason(),
            'created_at' => $transcription->createdAt()->format('Y-m-d H:i:s'),
            'completed_at' => $transcription->completedAt()?->format('Y-m-d H:i:s')
        ]);
    }
}