<?php

namespace Domain\Transcription\Repository\Criteria;

use DateTimeImmutable;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\Language;

class TranscriptionSearchCriteria
{
    private ?UserId $userId = null;
    private ?TranscriptionStatus $status = null;
    private ?Language $language = null;
    private ?string $searchText = null;
    private ?DateTimeImmutable $fromDate = null;
    private ?DateTimeImmutable $toDate = null;
    private ?bool $onlyYouTube = null;
    private ?int $minWordCount = null;
    private ?int $maxWordCount = null;
    private string $orderBy = 'created_at';
    private string $orderDirection = 'DESC';
    private ?int $limit = null;
    private ?int $offset = null;
    
    public static function create(): self
    {
        return new self();
    }
    
    public function forUser(UserId $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
    
    public function withStatus(TranscriptionStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    public function inLanguage(Language $language): self
    {
        $this->language = $language;
        return $this;
    }
    
    public function containingText(string $searchText): self
    {
        $this->searchText = $searchText;
        return $this;
    }
    
    public function createdBetween(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        $this->fromDate = $from;
        $this->toDate = $to;
        return $this;
    }
    
    public function createdAfter(DateTimeImmutable $date): self
    {
        $this->fromDate = $date;
        return $this;
    }
    
    public function createdBefore(DateTimeImmutable $date): self
    {
        $this->toDate = $date;
        return $this;
    }
    
    public function onlyYouTube(bool $onlyYouTube = true): self
    {
        $this->onlyYouTube = $onlyYouTube;
        return $this;
    }
    
    public function withWordCountBetween(int $min, int $max): self
    {
        $this->minWordCount = $min;
        $this->maxWordCount = $max;
        return $this;
    }
    
    public function orderBy(string $field, string $direction = 'DESC'): self
    {
        $this->orderBy = $field;
        $this->orderDirection = strtoupper($direction);
        return $this;
    }
    
    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
    
    // Getters
    public function getUserId(): ?UserId { return $this->userId; }
    public function getStatus(): ?TranscriptionStatus { return $this->status; }
    public function getLanguage(): ?Language { return $this->language; }
    public function getSearchText(): ?string { return $this->searchText; }
    public function getFromDate(): ?DateTimeImmutable { return $this->fromDate; }
    public function getToDate(): ?DateTimeImmutable { return $this->toDate; }
    public function isOnlyYouTube(): ?bool { return $this->onlyYouTube; }
    public function getMinWordCount(): ?int { return $this->minWordCount; }
    public function getMaxWordCount(): ?int { return $this->maxWordCount; }
    public function getOrderBy(): string { return $this->orderBy; }
    public function getOrderDirection(): string { return $this->orderDirection; }
    public function getLimit(): ?int { return $this->limit; }
    public function getOffset(): ?int { return $this->offset; }
}