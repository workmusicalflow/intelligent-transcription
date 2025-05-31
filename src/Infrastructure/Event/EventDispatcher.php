<?php

namespace Infrastructure\Event;

use Domain\Common\Event\DomainEvent;
use Application\Event\Dispatcher\EventDispatcherInterface;

/**
 * Implémentation simple d'un dispatcher d'événements
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, callable[]>
     */
    private array $handlers = [];
    
    /**
     * @var array
     */
    private array $eventHistory = [];
    
    /**
     * @var bool
     */
    private bool $historyEnabled = false;
    
    /**
     * @var array
     */
    private array $stats = [];
    
    /**
     * Dispatch un événement vers tous les handlers enregistrés
     */
    public function dispatch(DomainEvent $event): void
    {
        $eventClass = get_class($event);
        
        // Enregistrer dans l'historique si activé
        if ($this->historyEnabled) {
            $this->eventHistory[] = [
                'event' => $event,
                'timestamp' => new \DateTimeImmutable(),
                'class' => $eventClass
            ];
        }
        
        // Mettre à jour les stats
        if (!isset($this->stats[$eventClass])) {
            $this->stats[$eventClass] = 0;
        }
        $this->stats[$eventClass]++;
        
        if (!isset($this->handlers[$eventClass])) {
            return; // Aucun handler pour cet événement
        }
        
        foreach ($this->handlers[$eventClass] as $handler) {
            try {
                if (is_callable($handler)) {
                    $handler($event);
                } elseif (is_object($handler) && method_exists($handler, 'handle')) {
                    $handler->handle($event);
                }
            } catch (\Exception $e) {
                // Log l'erreur mais continue avec les autres handlers
                error_log(sprintf(
                    "Error handling event %s: %s",
                    $eventClass,
                    $e->getMessage()
                ));
            }
        }
    }
    
    /**
     * Dispatch plusieurs événements
     */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
    
    /**
     * Enregistre un handler pour un type d'événement
     */
    public function subscribe(string $eventClass, callable $handler): void
    {
        if (!isset($this->handlers[$eventClass])) {
            $this->handlers[$eventClass] = [];
        }
        
        $this->handlers[$eventClass][] = $handler;
    }
    
    /**
     * Retire un handler pour un type d'événement
     */
    public function unsubscribe(string $eventClass, callable $handler): void
    {
        if (!isset($this->handlers[$eventClass])) {
            return;
        }
        
        $this->handlers[$eventClass] = array_filter(
            $this->handlers[$eventClass],
            fn($h) => $h !== $handler
        );
    }
    
    /**
     * Obtient tous les handlers enregistrés pour un type d'événement
     */
    public function getHandlers(string $eventClass): array
    {
        return $this->handlers[$eventClass] ?? [];
    }
    
    /**
     * Vérifie si des handlers sont enregistrés pour un type d'événement
     */
    public function hasHandlers(string $eventClass): bool
    {
        return !empty($this->handlers[$eventClass]);
    }
    
    /**
     * Obtient les statistiques des événements
     */
    public function getStats(): array
    {
        return $this->stats;
    }
    
    /**
     * Obtient l'historique des événements
     */
    public function getEventHistory(?string $eventClass = null): array
    {
        if ($eventClass === null) {
            return $this->eventHistory;
        }
        
        return array_filter(
            $this->eventHistory,
            fn($entry) => $entry['class'] === $eventClass
        );
    }
    
    /**
     * Vide l'historique des événements
     */
    public function clearHistory(): void
    {
        $this->eventHistory = [];
    }
    
    /**
     * Active ou désactive l'historique
     */
    public function setHistoryEnabled(bool $enabled): void
    {
        $this->historyEnabled = $enabled;
    }
    
    /**
     * Réinitialise tous les handlers
     */
    public function clear(): void
    {
        $this->handlers = [];
    }
}