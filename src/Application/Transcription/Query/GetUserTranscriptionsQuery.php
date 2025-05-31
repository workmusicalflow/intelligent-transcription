<?php

namespace Application\Transcription\Query;

use Application\Query\QueryInterface;

final class GetUserTranscriptionsQuery implements QueryInterface
{
    private readonly string $queryId;
    private readonly \DateTimeImmutable $createdAt;
    
    public function __construct(
        private readonly string $userId,
        private readonly int $page = 1,
        private readonly int $limit = 10,
        private readonly ?string $status = null,
        private readonly ?string $language = null
    ) {
        $this->queryId = uniqid('query_', true);
        $this->createdAt = new \DateTimeImmutable();
        $this->validate();
    }
    
    public function getUserId(): string
    {
        return $this->userId;
    }
    
    public function getPage(): int
    {
        return $this->page;
    }
    
    public function getLimit(): int
    {
        return $this->limit;
    }
    
    public function getStatus(): ?string
    {
        return $this->status;
    }
    
    public function getLanguage(): ?string
    {
        return $this->language;
    }
    
    public function getQueryId(): string
    {
        return $this->queryId;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function validate(): void
    {
        if (empty($this->userId)) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }
        
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }
        
        if ($this->limit < 1 || $this->limit > 100) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100');
        }
    }
    
    public function toArray(): array
    {
        return [
            'query_id' => $this->queryId,
            'user_id' => $this->userId,
            'page' => $this->page,
            'limit' => $this->limit,
            'status' => $this->status,
            'language' => $this->language,
            'created_at' => $this->createdAt->format('c')
        ];
    }
    
    public function getCacheKey(): string
    {
        return 'user_transcriptions_' . md5($this->userId . '_' . $this->page . '_' . $this->limit . '_' . $this->status . '_' . $this->language);
    }
}