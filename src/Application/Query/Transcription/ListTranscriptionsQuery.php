<?php

namespace Application\Query\Transcription;

use Application\Query\AbstractQuery;

/**
 * Query pour lister les transcriptions avec filtres et pagination
 */
final class ListTranscriptionsQuery extends AbstractQuery
{
    public function __construct(
        private readonly ?string $userId = null,
        private readonly ?string $status = null,
        private readonly ?string $language = null,
        private readonly ?bool $isYouTube = null,
        private readonly int $page = 1,
        private readonly int $limit = 20,
        private readonly string $sortBy = 'created_at',
        private readonly string $sortDirection = 'DESC',
        private readonly ?\DateTimeImmutable $fromDate = null,
        private readonly ?\DateTimeImmutable $toDate = null
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }
        
        if ($this->limit < 1 || $this->limit > 100) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100');
        }
        
        $validSortFields = ['created_at', 'status', 'language', 'original_filename', 'duration'];
        if (!in_array($this->sortBy, $validSortFields)) {
            throw new \InvalidArgumentException('Invalid sort field: ' . $this->sortBy);
        }
        
        $validDirections = ['ASC', 'DESC'];
        if (!in_array($this->sortDirection, $validDirections)) {
            throw new \InvalidArgumentException('Invalid sort direction: ' . $this->sortDirection);
        }
        
        if ($this->status !== null) {
            $validStatuses = ['pending', 'processing', 'completed', 'failed'];
            if (!in_array($this->status, $validStatuses)) {
                throw new \InvalidArgumentException('Invalid status: ' . $this->status);
            }
        }
        
        if ($this->fromDate && $this->toDate && $this->fromDate > $this->toDate) {
            throw new \InvalidArgumentException('From date cannot be after to date');
        }
    }
    
    protected function getParameters(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
            'language' => $this->language,
            'is_youtube' => $this->isYouTube,
            'page' => $this->page,
            'limit' => $this->limit,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'from_date' => $this->fromDate?->format('Y-m-d'),
            'to_date' => $this->toDate?->format('Y-m-d')
        ];
    }
    
    // Getters
    public function getUserId(): ?string { return $this->userId; }
    public function getStatus(): ?string { return $this->status; }
    public function getLanguage(): ?string { return $this->language; }
    public function getIsYouTube(): ?bool { return $this->isYouTube; }
    public function getPage(): int { return $this->page; }
    public function getLimit(): int { return $this->limit; }
    public function getSortBy(): string { return $this->sortBy; }
    public function getSortDirection(): string { return $this->sortDirection; }
    public function getFromDate(): ?\DateTimeImmutable { return $this->fromDate; }
    public function getToDate(): ?\DateTimeImmutable { return $this->toDate; }
    
    // Méthodes utilitaires
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
    
    public function hasDateFilter(): bool
    {
        return $this->fromDate !== null || $this->toDate !== null;
    }
}