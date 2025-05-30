<?php

namespace Application\Query\Transcription;

use Application\Query\AbstractQuery;

/**
 * Query pour récupérer les statistiques des transcriptions
 */
final class GetTranscriptionStatsQuery extends AbstractQuery
{
    public function __construct(
        private readonly ?string $userId = null,
        private readonly ?\DateTimeImmutable $fromDate = null,
        private readonly ?\DateTimeImmutable $toDate = null,
        private readonly bool $includeDetailed = false
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if ($this->fromDate && $this->toDate && $this->fromDate > $this->toDate) {
            throw new \InvalidArgumentException('From date cannot be after to date');
        }
    }
    
    protected function getParameters(): array
    {
        return [
            'user_id' => $this->userId,
            'from_date' => $this->fromDate?->format('Y-m-d'),
            'to_date' => $this->toDate?->format('Y-m-d'),
            'include_detailed' => $this->includeDetailed
        ];
    }
    
    // Getters
    public function getUserId(): ?string { return $this->userId; }
    public function getFromDate(): ?\DateTimeImmutable { return $this->fromDate; }
    public function getToDate(): ?\DateTimeImmutable { return $this->toDate; }
    public function shouldIncludeDetailed(): bool { return $this->includeDetailed; }
    
    // Méthodes utilitaires
    public function hasDateFilter(): bool
    {
        return $this->fromDate !== null || $this->toDate !== null;
    }
    
    public function isUserSpecific(): bool
    {
        return $this->userId !== null;
    }
}