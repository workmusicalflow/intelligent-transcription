<?php

namespace Application\Query;

/**
 * Classe de base abstraite pour toutes les Queries
 * 
 * Fournit une implémentation de base des méthodes communes aux Queries.
 */
abstract class AbstractQuery implements QueryInterface
{
    private string $queryId;
    private \DateTimeImmutable $createdAt;
    
    public function __construct()
    {
        $this->queryId = uniqid('qry_', true);
        $this->createdAt = new \DateTimeImmutable();
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
        // Validation de base - peut être surchargée dans les classes filles
        if (empty($this->queryId)) {
            throw new \InvalidArgumentException('Query ID cannot be empty');
        }
    }
    
    public function toArray(): array
    {
        return [
            'query_id' => $this->queryId,
            'query_type' => static::class,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'parameters' => $this->getParameters()
        ];
    }
    
    public function getCacheKey(): string
    {
        $parameters = $this->getParameters();
        $key = static::class . ':' . md5(serialize($parameters));
        return $key;
    }
    
    /**
     * Méthode abstraite pour récupérer les paramètres spécifiques de la query
     * 
     * @return array
     */
    abstract protected function getParameters(): array;
}