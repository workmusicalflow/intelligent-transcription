<?php

namespace Application\Service;

use Application\Event\Dispatcher\EventDispatcherInterface;
use Application\Event\Handler\TranscriptionEventHandler;
use Application\Event\Handler\NotificationEventHandler;
use Domain\Common\Entity\AggregateRoot;

/**
 * Service pour gérer les événements dans l'Application Layer
 */
final class EventService
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        $this->registerDefaultHandlers();
    }
    
    /**
     * Collecte et dispatche les événements d'un aggregate
     */
    public function dispatchEventsFrom(AggregateRoot $aggregate): void
    {
        $events = $aggregate->releaseEvents();
        
        if (empty($events)) {
            return;
        }
        
        $this->eventDispatcher->dispatchAll($events);
    }
    
    /**
     * Collecte et dispatche les événements de plusieurs aggregates
     */
    public function dispatchEventsFromMultiple(array $aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            if ($aggregate instanceof AggregateRoot) {
                $this->dispatchEventsFrom($aggregate);
            }
        }
    }
    
    /**
     * Enregistre un handler personnalisé
     */
    public function registerHandler(string $eventClass, callable $handler): void
    {
        $this->eventDispatcher->subscribe($eventClass, $handler);
    }
    
    /**
     * Obtient les statistiques des événements
     */
    public function getEventStats(): array
    {
        return $this->eventDispatcher->getStats();
    }
    
    /**
     * Obtient l'historique des événements
     */
    public function getEventHistory(?string $eventClass = null): array
    {
        return $this->eventDispatcher->getEventHistory($eventClass);
    }
    
    /**
     * Vide l'historique des événements
     */
    public function clearEventHistory(): void
    {
        $this->eventDispatcher->clearHistory();
    }
    
    /**
     * Active ou désactive l'historique
     */
    public function setHistoryEnabled(bool $enabled): void
    {
        $this->eventDispatcher->setHistoryEnabled($enabled);
    }
    
    /**
     * Enregistre les handlers par défaut du système
     */
    private function registerDefaultHandlers(): void
    {
        // Handler principal pour les événements de transcription
        $transcriptionHandler = new TranscriptionEventHandler();
        foreach ($transcriptionHandler->getHandledEventTypes() as $eventType) {
            $this->eventDispatcher->subscribe($eventType, [$transcriptionHandler, 'handle']);
        }
        
        // Handler pour les notifications
        $notificationHandler = new NotificationEventHandler();
        foreach ($notificationHandler->getHandledEventTypes() as $eventType) {
            $this->eventDispatcher->subscribe($eventType, [$notificationHandler, 'handle']);
        }
        
        // On peut ajouter d'autres handlers par défaut ici
        $this->registerAnalyticsHandlers();
        $this->registerAuditHandlers();
    }
    
    /**
     * Enregistre les handlers pour les analytics
     */
    private function registerAnalyticsHandlers(): void
    {
        // Handler pour collecter les métriques
        $analyticsHandler = function($event) {
            $this->collectMetrics($event);
        };
        
        // S'abonner à tous les événements de transcription pour les analytics
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionCreated',
            $analyticsHandler
        );
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionCompleted',
            $analyticsHandler
        );
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionFailed',
            $analyticsHandler
        );
    }
    
    /**
     * Enregistre les handlers pour l'audit
     */
    private function registerAuditHandlers(): void
    {
        // Handler pour l'audit trail
        $auditHandler = function($event) {
            $this->logForAudit($event);
        };
        
        // S'abonner à tous les événements pour l'audit
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionCreated',
            $auditHandler
        );
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionStarted',
            $auditHandler
        );
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionCompleted',
            $auditHandler
        );
        $this->eventDispatcher->subscribe(
            'Domain\Transcription\Event\TranscriptionFailed',
            $auditHandler
        );
    }
    
    /**
     * Collecte les métriques depuis les événements
     */
    private function collectMetrics($event): void
    {
        $metrics = [
            'event_type' => get_class($event),
            'event_id' => $event->eventId(),
            'timestamp' => $event->occurredAt()->format('Y-m-d H:i:s'),
            'aggregate_id' => method_exists($event, 'aggregateId') ? $event->aggregateId() : null
        ];
        
        // Dans une vraie implémentation :
        // - Envoyer à Prometheus, InfluxDB, etc.
        // - Calculer des métriques business (taux de succès, temps moyen, etc.)
        // - Mettre à jour des dashboards en temps réel
        
        error_log('Metrics: ' . json_encode($metrics));
    }
    
    /**
     * Log pour l'audit trail
     */
    private function logForAudit($event): void
    {
        $auditEntry = [
            'event_type' => get_class($event),
            'event_id' => $event->eventId(),
            'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s'),
            'aggregate_id' => method_exists($event, 'aggregateId') ? $event->aggregateId() : null,
            'event_data' => method_exists($event, 'toArray') ? $event->toArray() : [],
            'audit_timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Dans une vraie implémentation :
        // - Sauvegarder dans une table d'audit sécurisée
        // - Hacher les données pour éviter la manipulation
        // - Compresser les anciens logs
        
        error_log('Audit: ' . json_encode($auditEntry));
    }
}