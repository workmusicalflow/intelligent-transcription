<?php

namespace Application\Bus;

use Application\Command\CommandInterface;
use Application\Handler\HandlerInterface;
use Exception;

/**
 * Command Bus - Pattern CQRS pour l'exécution des commandes
 */
class CommandBus
{
    private array $handlers = [];
    private bool $enableLogging = true;
    
    public function registerHandler(string $commandClass, callable $handler): void
    {
        $this->handlers[$commandClass] = $handler;
        
        if ($this->enableLogging) {
            $this->log('handler_registered', $commandClass, gettype($handler));
        }
    }
    
    public function dispatch($command): mixed
    {
        $commandClass = get_class($command);
        $commandId = 'cmd_' . uniqid();
        
        if ($this->enableLogging) {
            $this->log('started', $commandClass, null, $commandId);
        }
        
        if (!isset($this->handlers[$commandClass])) {
            throw new Exception("No handler registered for command: {$commandClass}");
        }
        
        $handler = $this->handlers[$commandClass];
        
        try {
            $result = $handler($command);
            
            if ($this->enableLogging) {
                $this->log('completed', $commandClass, null, $commandId);
            }
            
            return $result;
        } catch (Exception $e) {
            if ($this->enableLogging) {
                $this->log('failed', $commandClass, $e->getMessage(), $commandId);
            }
            throw $e;
        }
    }
    
    public function hasHandler(string $commandClass): bool
    {
        return isset($this->handlers[$commandClass]);
    }
    
    public function getRegisteredCommands(): array
    {
        return array_keys($this->handlers);
    }
    
    public function setLogging(bool $enabled): void
    {
        $this->enableLogging = $enabled;
    }
    
    private function log(string $status, string $commandClass, ?string $extra = null, ?string $commandId = null): void
    {
        $logData = [
            'command_class' => $commandClass,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($commandId) {
            $logData['command_id'] = $commandId;
        }
        
        if ($extra) {
            $logData['extra'] = $extra;
        }
        
        error_log("CommandBus: " . json_encode($logData));
    }
}