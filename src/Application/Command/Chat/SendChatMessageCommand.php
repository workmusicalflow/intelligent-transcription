<?php

namespace Application\Command\Chat;

use Application\Command\AbstractCommand;

/**
 * Command pour envoyer un message dans le chat contextuel
 */
final class SendChatMessageCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $userId,
        private readonly string $message,
        private readonly ?string $transcriptionId = null,
        private readonly ?string $conversationId = null,
        private readonly ?string $language = null,
        private readonly ?array $context = null
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
        
        if (empty($this->message)) {
            throw new \InvalidArgumentException('Message is required');
        }
        
        if (mb_strlen($this->message) > 4000) {
            throw new \InvalidArgumentException('Message cannot exceed 4000 characters');
        }
        
        if ($this->transcriptionId !== null && empty($this->transcriptionId)) {
            throw new \InvalidArgumentException('Transcription ID cannot be empty if provided');
        }
    }
    
    protected function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'message' => $this->message,
            'transcription_id' => $this->transcriptionId,
            'conversation_id' => $this->conversationId,
            'language' => $this->language,
            'context' => $this->context,
            'message_length' => mb_strlen($this->message),
            'has_transcription_context' => !empty($this->transcriptionId)
        ];
    }
    
    // Getters
    public function getUserId(): string { return $this->userId; }
    public function getMessage(): string { return $this->message; }
    public function getTranscriptionId(): ?string { return $this->transcriptionId; }
    public function getConversationId(): ?string { return $this->conversationId; }
    public function getLanguage(): ?string { return $this->language; }
    public function getContext(): ?array { return $this->context; }
    
    // Méthodes utilitaires
    public function hasTranscriptionContext(): bool
    {
        return !empty($this->transcriptionId);
    }
    
    public function hasConversationContext(): bool
    {
        return !empty($this->conversationId);
    }
    
    public function getMessageLength(): int
    {
        return mb_strlen($this->message);
    }
    
    public function getMessagePreview(int $maxLength = 50): string
    {
        if (mb_strlen($this->message) <= $maxLength) {
            return $this->message;
        }
        
        return mb_substr($this->message, 0, $maxLength) . '...';
    }
}