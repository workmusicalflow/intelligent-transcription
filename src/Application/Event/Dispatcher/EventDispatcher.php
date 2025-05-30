<?php

namespace Application\Event\Dispatcher;

use Domain\Common\Event\DomainEvent;

/**
 * Implémentation du dispatcher d'événements
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, callable[]> */
    private array $handlers = [];
    
    /** @var array<string, array> */
    private array $eventHistory = [];
    
    private bool $enableHistory = true;
    
    public function dispatch(DomainEvent $event): void
    {
        $eventClass = get_class($event);
        
        // Log l'événement
        $this->logEvent($event, 'dispatched');
        
        // Sauvegarder dans l'historique si activé
        if ($this->enableHistory) {
            $this->addToHistory($event);
        }
        
        // Récupérer les handlers pour ce type d'événement
        $handlers = $this->getHandlers($eventClass);
        
        if (empty($handlers)) {
            $this->logEvent($event, 'no_handlers');
            return;
        }
        
        // Exécuter chaque handler
        foreach ($handlers as $handler) {
            try {
                $this->logEvent($event, 'handler_started', get_debug_type($handler));
                
                $handler($event);
                
                $this->logEvent($event, 'handler_completed', get_debug_type($handler));
                
            } catch (\Exception $e) {
                // Log l'erreur mais continue avec les autres handlers
                $this->logEvent($event, 'handler_failed', get_debug_type($handler), $e);
                
                // Dans une vraie implémentation, on pourrait :
                // - Envoyer à un système de monitoring
                // - Retry le handler
                // - Mettre l'événement dans une dead letter queue
            }
        }
    }
    
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            if ($event instanceof DomainEvent) {
                $this->dispatch($event);
            }
        }
    }
    
    public function subscribe(string $eventClass, callable $handler): void
    {
        if (!isset($this->handlers[$eventClass])) {
            $this->handlers[$eventClass] = [];
        }
        
        $this->handlers[$eventClass][] = $handler;
        
        $this->logSubscription($eventClass, 'subscribed', get_debug_type($handler));
    }
    
    public function unsubscribe(string $eventClass, callable $handler): void
    {
        if (!isset($this->handlers[$eventClass])) {
            return;
        }
        
        $key = array_search($handler, $this->handlers[$eventClass], true);
        if ($key !== false) {
            unset($this->handlers[$eventClass][$key]);
            $this->handlers[$eventClass] = array_values($this->handlers[$eventClass]);
            
            $this->logSubscription($eventClass, 'unsubscribed', get_debug_type($handler));
        }
    }
    
    public function getHandlers(string $eventClass): array
    {
        return $this->handlers[$eventClass] ?? [];
    }
    
    public function hasHandlers(string $eventClass): bool
    {
        return !empty($this->handlers[$eventClass]);
    }
    
    /**
     * Active ou désactive l'historique des événements
     */
    public function setHistoryEnabled(bool $enabled): void
    {
        $this->enableHistory = $enabled;
    }
    
    /**
     * Obtient l'historique des événements
     */
    public function getEventHistory(?string $eventClass = null): array
    {
        if ($eventClass) {
            return $this->eventHistory[$eventClass] ?? [];
        }
        
        return $this->eventHistory;
    }
    
    /**
     * Vide l'historique des événements
     */
    public function clearHistory(): void
    {
        $this->eventHistory = [];
    }
    
    /**
     * Obtient les statistiques du dispatcher
     */
    public function getStats(): array
    {
        $totalHandlers = array_sum(array_map('count', $this->handlers));
        $totalEvents = array_sum(array_map('count', $this->eventHistory));
        
        return [
            'registered_event_types' => count($this->handlers),
            'total_handlers' => $totalHandlers,
            'total_events_dispatched' => $totalEvents,
            'history_enabled' => $this->enableHistory,
            'event_types' => array_keys($this->handlers),
            'handlers_per_event' => array_map('count', $this->handlers)
        ];
    }
    
    private function logEvent(DomainEvent $event, string $status, ?string $handlerType = null, ?\Exception $error = null): void
    {
        $logData = [
            'event_id' => $event->eventId(),
            'event_class' => get_class($event),
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s')
        ];
        
        if ($handlerType) {
            $logData['handler_type'] = $handlerType;
        }
        
        if ($error) {
            $logData['error'] = $error->getMessage();
            $logData['error_class'] = get_class($error);
        }
        
        // Log dans le système (en production, utiliser un logger approprié)
        error_log('EventDispatcher: ' . json_encode($logData));
    }
    
    private function logSubscription(string $eventClass, string $action, string $handlerType): void
    {
        $logData = [
            'event_class' => $eventClass,
            'action' => $action,
            'handler_type' => $handlerType,
            'timestamp' => date('Y-m-d H:i:s'),
            'total_handlers' => count($this->handlers[$eventClass] ?? [])
        ];
        
        error_log('EventDispatcher: ' . json_encode($logData));
    }
    
    private function addToHistory(DomainEvent $event): void
    {
        $eventClass = get_class($event);
        
        if (!isset($this->eventHistory[$eventClass])) {
            $this->eventHistory[$eventClass] = [];
        }
        
        $this->eventHistory[$eventClass][] = [
            'event_id' => $event->eventId(),
            'occurred_at' => $event->occurredAt(),
            'dispatched_at' => new \DateTimeImmutable(),
            'aggregate_id' => method_exists($event, 'aggregateId') ? $event->aggregateId() : null,
            'payload' => method_exists($event, 'toArray') ? $event->toArray() : []
        ];
        
        // Limiter l'historique pour éviter la consommation excessive de mémoire
        if (count($this->eventHistory[$eventClass]) > 1000) {
            $this->eventHistory[$eventClass] = array_slice($this->eventHistory[$eventClass], -500);
        }
    }
}