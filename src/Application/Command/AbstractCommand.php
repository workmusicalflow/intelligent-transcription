<?php

namespace Application\Command;

/**
 * Classe de base abstraite pour tous les Commands
 * 
 * Fournit une implémentation de base des méthodes communes aux Commands.
 */
abstract class AbstractCommand implements CommandInterface
{
    private string $commandId;
    private \DateTimeImmutable $createdAt;
    
    public function __construct()
    {
        $this->commandId = uniqid('cmd_', true);
        $this->createdAt = new \DateTimeImmutable();
    }
    
    public function getCommandId(): string
    {
        return $this->commandId;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function validate(): void
    {
        // Validation de base - peut être surchargée dans les classes filles
        if (empty($this->commandId)) {
            throw new \InvalidArgumentException('Command ID cannot be empty');
        }
    }
    
    public function toArray(): array
    {
        return [
            'command_id' => $this->commandId,
            'command_type' => static::class,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'payload' => $this->getPayload()
        ];
    }
    
    /**
     * Méthode abstraite pour récupérer les données spécifiques du command
     * 
     * @return array
     */
    abstract protected function getPayload(): array;
}