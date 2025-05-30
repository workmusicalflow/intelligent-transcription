<?php

namespace Application\Query\Chat;

use Application\Query\AbstractQuery;

/**
 * Query pour récupérer les données de la page de chat
 */
final class GetChatPageDataQuery extends AbstractQuery
{
    public function __construct(
        private readonly string $userId,
        private readonly ?string $transcriptionId = null,
        private readonly ?string $conversationId = null,
        private readonly bool $includeHistory = true,
        private readonly int $historyLimit = 50
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if (empty($this->userId)) {
            throw new \InvalidArgumentException('User ID is required');
        }
        
        if ($this->historyLimit < 1 || $this->historyLimit > 200) {
            throw new \InvalidArgumentException('History limit must be between 1 and 200');
        }
    }
    
    protected function getParameters(): array
    {
        return [
            'user_id' => $this->userId,
            'transcription_id' => $this->transcriptionId,
            'conversation_id' => $this->conversationId,
            'include_history' => $this->includeHistory,
            'history_limit' => $this->historyLimit
        ];
    }
    
    // Getters
    public function getUserId(): string { return $this->userId; }
    public function getTranscriptionId(): ?string { return $this->transcriptionId; }
    public function getConversationId(): ?string { return $this->conversationId; }
    public function shouldIncludeHistory(): bool { return $this->includeHistory; }
    public function getHistoryLimit(): int { return $this->historyLimit; }
    
    // Méthodes utilitaires
    public function hasTranscriptionContext(): bool
    {
        return !empty($this->transcriptionId);
    }
    
    public function hasConversationContext(): bool
    {
        return !empty($this->conversationId);
    }
}