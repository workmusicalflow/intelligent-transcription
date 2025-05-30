<?php

namespace Application\Command;

/**
 * Interface pour tous les Commands de l'Application Layer
 * 
 * Un Command représente une intention d'effectuer une action qui modifie l'état du système.
 * Les Commands sont immutables et contiennent toutes les données nécessaires pour l'action.
 */
interface CommandInterface
{
    /**
     * Identifiant unique du command pour le suivi et le logging
     */
    public function getCommandId(): string;
    
    /**
     * Timestamp de création du command
     */
    public function getCreatedAt(): \DateTimeImmutable;
    
    /**
     * Validation du command avant exécution
     * 
     * @throws \InvalidArgumentException si le command n'est pas valide
     */
    public function validate(): void;
    
    /**
     * Conversion en tableau pour serialization/logging
     */
    public function toArray(): array;
}