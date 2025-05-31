<?php

namespace Application\Transcription\Handler;

use Application\Handler\QueryHandlerInterface;
use Application\Query\QueryInterface;
use Application\Transcription\Query\GetUserTranscriptionsQuery;
use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\Language;

final class GetUserTranscriptionsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository
    ) {}
    
    public function handle(QueryInterface $query): array
    {
        if (!$query instanceof GetUserTranscriptionsQuery) {
            throw new \InvalidArgumentException('Invalid query type');
        }
        
        $userId = UserId::fromString($query->getUserId());
        
        // Get user transcriptions with pagination
        $transcriptions = $this->transcriptionRepository->findByUserPaginated(
            $userId,
            $query->getPage(),
            $query->getLimit()
        );
        
        // Apply filters if specified
        if ($query->getStatus()) {
            $status = TranscriptionStatus::fromString($query->getStatus());
            $transcriptions = $transcriptions->filterByStatus($status);
        }
        
        if ($query->getLanguage()) {
            $language = Language::fromCode($query->getLanguage());
            $transcriptions = $transcriptions->filterByLanguage($language);
        }
        
        // Convert to array format
        $data = [];
        foreach ($transcriptions as $transcription) {
            $data[] = $this->transcriptionToArray($transcription);
        }
        
        return [
            'data' => $data,
            'pagination' => [
                'page' => $query->getPage(),
                'limit' => $query->getLimit(),
                'total' => $this->transcriptionRepository->countByUser($userId)
            ]
        ];
    }
    
    public function canHandle(QueryInterface $query): bool
    {
        return $query instanceof GetUserTranscriptionsQuery;
    }
    
    private function transcriptionToArray($transcription): array
    {
        return [
            'id' => $transcription->id(),
            'user_id' => $transcription->userId()->value(),
            'audio_file' => [
                'original_name' => $transcription->audioFile()->originalName(),
                'duration' => $transcription->audioFile()->duration(),
                'size' => $transcription->audioFile()->size()
            ],
            'language' => $transcription->language()->code(),
            'status' => $transcription->status()->value(),
            'text' => $transcription->transcribedText(),
            'youtube_metadata' => $transcription->youtubeMetadata()?->toArray(),
            'created_at' => $transcription->createdAt()->format('Y-m-d H:i:s'),
            'completed_at' => $transcription->completedAt()?->format('Y-m-d H:i:s'),
            'is_youtube_source' => $transcription->isYouTubeSource(),
            'processing_duration' => $transcription->processingDuration()
        ];
    }
}