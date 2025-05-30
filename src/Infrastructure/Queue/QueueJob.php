<?php

namespace Infrastructure\Queue;

/**
 * Représente un job dans la queue
 */
class QueueJob
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly array $data,
        private readonly int $attempts = 0,
        private readonly ?int $createdAt = null
    ) {}
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
    
    public function getAttempts(): int
    {
        return $this->attempts;
    }
    
    public function getCreatedAt(): int
    {
        return $this->createdAt ?? time();
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
            'attempts' => $this->attempts,
            'created_at' => $this->getCreatedAt()
        ];
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['type'],
            $data['data'] ?? [],
            $data['attempts'] ?? 0,
            $data['created_at'] ?? null
        );
    }
}