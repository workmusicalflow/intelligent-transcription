<?php

namespace Application\Handler;

use Application\Command\CommandInterface;

/**
 * Bus pour router les Commands vers les bons Handlers
 */
final class CommandBus
{
    /** @var CommandHandlerInterface[] */
    private array $handlers = [];
    
    public function __construct()
    {
        // Les handlers seront injectés via registerHandler()
    }
    
    /**
     * Enregistre un handler
     */
    public function registerHandler(CommandHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }
    
    /**
     * Exécute un command en le routant vers le bon handler
     */
    public function execute(CommandInterface $command): mixed
    {
        $handler = $this->findHandler($command);
        
        if (!$handler) {
            throw new \RuntimeException('No handler found for command: ' . get_class($command));
        }
        
        try {
            // Log du début d'exécution
            $this->logCommandExecution($command, 'started');
            
            $result = $handler->handle($command);
            
            // Log du succès
            $this->logCommandExecution($command, 'completed');
            
            return $result;
            
        } catch (\Exception $e) {
            // Log de l'erreur
            $this->logCommandExecution($command, 'failed', $e);
            throw $e;
        }
    }
    
    /**
     * Trouve le handler approprié pour un command
     */
    private function findHandler(CommandInterface $command): ?CommandHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($command)) {
                return $handler;
            }
        }
        
        return null;
    }
    
    /**
     * Log l'exécution d'un command
     */
    private function logCommandExecution(CommandInterface $command, string $status, ?\Exception $error = null): void
    {
        $logData = [
            'command_id' => $command->getCommandId(),
            'command_class' => get_class($command),
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        if ($error) {
            $logData['error'] = $error->getMessage();
            $logData['trace'] = $error->getTraceAsString();
        }
        
        // Pour l'instant, on log dans le fichier d'erreur PHP
        // Dans une vraie implémentation, utiliser un logger approprié
        error_log('CommandBus: ' . json_encode($logData));
    }
    
    /**
     * Retourne la liste des handlers enregistrés
     */
    public function getRegisteredHandlers(): array
    {
        return $this->handlers;
    }
}