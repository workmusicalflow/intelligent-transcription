<?php

namespace Domain\EventSourcing;

use Domain\Common\Event\DomainEvent;
use Domain\Common\ValueObject\AggregateId;

/**
 * Interface pour le store d'événements
 */
interface EventStoreInterface
{
    /**
     * Enregistre un événement dans le store
     */
    public function append(DomainEvent $event): void;
    
    /**
     * Enregistre plusieurs événements
     */
    public function appendMultiple(array $events): void;
    
    /**
     * Récupère tous les événements pour un agrégat
     */
    public function getEventsForAggregate(AggregateId $aggregateId): array;
    
    /**
     * Récupère les événements depuis une version donnée
     */
    public function getEventsForAggregateFromVersion(AggregateId $aggregateId, int $fromVersion): array;
    
    /**
     * Récupère tous les événements d'un certain type
     */
    public function getEventsByType(string $eventType): array;
    
    /**
     * Récupère la dernière version d'un agrégat
     */
    public function getAggregateVersion(AggregateId $aggregateId): int;
}