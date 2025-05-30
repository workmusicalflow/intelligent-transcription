<?php

namespace Application\Query\Analytics;

use Application\Query\AbstractQuery;

/**
 * Query pour récupérer les analytics du cache
 */
final class GetCacheAnalyticsQuery extends AbstractQuery
{
    public function __construct(
        private readonly ?string $cacheType = null,
        private readonly ?\DateTimeImmutable $fromDate = null,
        private readonly ?\DateTimeImmutable $toDate = null,
        private readonly bool $includeDetails = false
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if ($this->cacheType !== null) {
            $validTypes = ['openai_prompts', 'conversations', 'transcriptions', 'all'];
            if (!in_array($this->cacheType, $validTypes)) {
                throw new \InvalidArgumentException('Invalid cache type: ' . $this->cacheType);
            }
        }
        
        if ($this->fromDate && $this->toDate && $this->fromDate > $this->toDate) {
            throw new \InvalidArgumentException('From date cannot be after to date');
        }
    }
    
    protected function getParameters(): array
    {
        return [
            'cache_type' => $this->cacheType,
            'from_date' => $this->fromDate?->format('Y-m-d'),
            'to_date' => $this->toDate?->format('Y-m-d'),
            'include_details' => $this->includeDetails
        ];
    }
    
    // Getters
    public function getCacheType(): ?string { return $this->cacheType; }
    public function getFromDate(): ?\DateTimeImmutable { return $this->fromDate; }
    public function getToDate(): ?\DateTimeImmutable { return $this->toDate; }
    public function shouldIncludeDetails(): bool { return $this->includeDetails; }
    
    // Méthodes utilitaires
    public function hasDateFilter(): bool
    {
        return $this->fromDate !== null || $this->toDate !== null;
    }
    
    public function isTypeSpecific(): bool
    {
        return $this->cacheType !== null && $this->cacheType !== 'all';
    }
}