<?php

namespace Application\DTO;

/**
 * Interface pour tous les Data Transfer Objects (DTOs)
 * 
 * Les DTOs transportent des données entre les couches sans logique métier.
 * Ils sont immutables et fournissent une interface claire pour le transfert de données.
 */
interface DTOInterface
{
    /**
     * Crée le DTO à partir d'un tableau de données
     * 
     * @param array $data
     * @return static
     * @throws \InvalidArgumentException si les données ne sont pas valides
     */
    public static function fromArray(array $data): static;
    
    /**
     * Conversion vers un tableau associatif
     * 
     * @return array
     */
    public function toArray(): array;
    
    /**
     * Validation des données du DTO
     * 
     * @throws \InvalidArgumentException si les données ne sont pas valides
     */
    public function validate(): void;
    
    /**
     * Sérialisation JSON pour les APIs
     * 
     * @return string
     */
    public function toJson(): string;
}