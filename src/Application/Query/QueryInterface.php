<?php

namespace Application\Query;

/**
 * Interface pour toutes les Queries de l'Application Layer
 * 
 * Une Query représente une demande de lecture de données sans modification d'état.
 * Les Queries sont immutables et contiennent les critères de recherche.
 */
interface QueryInterface
{
    /**
     * Identifiant unique de la query pour le suivi et le caching
     */
    public function getQueryId(): string;
    
    /**
     * Timestamp de création de la query
     */
    public function getCreatedAt(): \DateTimeImmutable;
    
    /**
     * Validation des paramètres de la query
     * 
     * @throws \InvalidArgumentException si la query n'est pas valide
     */
    public function validate(): void;
    
    /**
     * Conversion en tableau pour caching/logging
     */
    public function toArray(): array;
    
    /**
     * Génère une clé de cache unique basée sur les paramètres
     */
    public function getCacheKey(): string;
}