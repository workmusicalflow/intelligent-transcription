<?php

namespace Domain\Chat\Entity;

use DateTimeImmutable;
use Domain\Common\Entity\AggregateRoot;
use Domain\Common\ValueObject\UserId;
use Domain\Chat\ValueObject\ConversationId;
use Domain\Transcription\ValueObject\TranscriptionId;

final class Conversation extends AggregateRoot
{
    private ConversationId $id;
    private UserId $userId;
    private ?TranscriptionId $transcriptionId;
    private string $title;
    private array $messages;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    
    private function __construct(
        ConversationId $id,
        UserId $userId,
        string $title,
        ?TranscriptionId $transcriptionId = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->transcriptionId = $transcriptionId;
        $this->messages = [];
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }
    
    public static function create(
        ConversationId $id,
        UserId $userId,
        string $title,
        ?TranscriptionId $transcriptionId = null
    ): self {
        return new self($id, $userId, $title, $transcriptionId);
    }
    
    public function id(): ConversationId
    {
        return $this->id;
    }
    
    public function userId(): UserId
    {
        return $this->userId;
    }
    
    public function transcriptionId(): ?TranscriptionId
    {
        return $this->transcriptionId;
    }
    
    public function title(): string
    {
        return $this->title;
    }
    
    public function messages(): array
    {
        return $this->messages;
    }
    
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    public function addMessage(string $role, string $content): void
    {
        $this->messages[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => new DateTimeImmutable()
        ];
        $this->updatedAt = new DateTimeImmutable();
    }
    
    public function updateTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new DateTimeImmutable();
    }
    
    public function messageCount(): int
    {
        return count($this->messages);
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'user_id' => $this->userId->value(),
            'transcription_id' => $this->transcriptionId?->value(),
            'title' => $this->title,
            'messages' => $this->messages,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'message_count' => $this->messageCount()
        ];
    }
}