<?php

namespace Application\Handler;

use Application\Query\QueryInterface;

/**
 * Interface pour tous les Query Handlers
 * 
 * Un Query Handler orchestre l'exécution d'une Query en récupérant
 * les données depuis l'Infrastructure Layer.
 */
interface QueryHandlerInterface
{
    /**
     * Exécute la query et retourne le résultat
     * 
     * @param QueryInterface $query
     * @return mixed Résultat de la query
     * @throws \Exception en cas d'erreur lors de l'exécution
     */
    public function handle(QueryInterface $query): mixed;
    
    /**
     * Indique si ce handler peut gérer la query donnée
     * 
     * @param QueryInterface $query
     * @return bool
     */
    public function canHandle(QueryInterface $query): bool;
}