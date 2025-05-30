<?php

namespace Application\Handler;

use Application\Command\CommandInterface;

/**
 * Interface pour tous les Command Handlers
 * 
 * Un Command Handler orchestre l'exécution d'un Command en interagissant
 * avec le Domain Layer et l'Infrastructure Layer.
 */
interface CommandHandlerInterface
{
    /**
     * Exécute le command et retourne le résultat
     * 
     * @param CommandInterface $command
     * @return mixed Résultat de l'exécution
     * @throws \Exception en cas d'erreur lors de l'exécution
     */
    public function handle(CommandInterface $command): mixed;
    
    /**
     * Indique si ce handler peut gérer le command donné
     * 
     * @param CommandInterface $command
     * @return bool
     */
    public function canHandle(CommandInterface $command): bool;
}