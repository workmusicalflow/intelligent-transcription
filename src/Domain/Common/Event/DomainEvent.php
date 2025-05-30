<?php

namespace Domain\Common\Event;

/**
 * Interface de base pour tous les événements du domaine
 */
interface DomainEvent
{
    /**
     * ID unique de l'événement
     */
    public function eventId(): string;
    
    /**
     * ID de l'agrégat concerné
     */
    public function aggregateId(): string;
    
    /**
     * Version de l'événement pour cet agrégat
     */
    public function eventVersion(): int;
    
    /**
     * Date et heure de l'occurrence de l'événement
     */
    public function occurredAt(): \DateTimeImmutable;
    
    /**
     * Métadonnées de l'événement (user ID, IP, etc.)
     */
    public function metadata(): array;
    
    /**
     * Conversion en tableau pour la sérialisation
     */
    public function toArray(): array;
}