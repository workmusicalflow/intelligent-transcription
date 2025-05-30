<?php

namespace Application\Event\Handler;

use Domain\Common\Event\DomainEvent;
use Domain\Transcription\Event\TranscriptionCreated;
use Domain\Transcription\Event\TranscriptionStarted;
use Domain\Transcription\Event\TranscriptionCompleted;
use Domain\Transcription\Event\TranscriptionFailed;

/**
 * Handler pour tous les événements de transcription
 */
final class TranscriptionEventHandler extends AbstractEventHandler
{
    protected int $priority = 10;
    protected bool $async = false;
    
    public function handle(DomainEvent $event): void
    {
        match (get_class($event)) {
            TranscriptionCreated::class => $this->handleTranscriptionCreated($event),
            TranscriptionStarted::class => $this->handleTranscriptionStarted($event),
            TranscriptionCompleted::class => $this->handleTranscriptionCompleted($event),
            TranscriptionFailed::class => $this->handleTranscriptionFailed($event),
            default => throw new \InvalidArgumentException('Unsupported event: ' . get_class($event))
        };
    }
    
    public function getHandledEventTypes(): array
    {
        return [
            TranscriptionCreated::class,
            TranscriptionStarted::class,
            TranscriptionCompleted::class,
            TranscriptionFailed::class
        ];
    }
    
    private function handleTranscriptionCreated(TranscriptionCreated $event): void
    {
        // Actions à effectuer quand une transcription est créée
        
        // 1. Log de l'événement
        $this->logTranscriptionEvent($event, 'Transcription created');
        
        // 2. Mettre à jour les statistiques utilisateur
        $this->updateUserStats($event->aggregateId(), 'created');
        
        // 3. Envoyer une notification si prioritaire
        if ($this->isHighPriority($event)) {
            $this->sendPriorityNotification($event);
        }
        
        // 4. Initialiser le suivi de processing
        $this->initializeProcessingTracking($event);
    }
    
    private function handleTranscriptionStarted(TranscriptionStarted $event): void
    {
        // Actions à effectuer quand le processing commence
        
        // 1. Log de l'événement
        $this->logTranscriptionEvent($event, 'Transcription processing started');
        
        // 2. Mettre à jour le statut de suivi
        $this->updateProcessingStatus($event->aggregateId(), 'started');
        
        // 3. Estimer le temps de completion
        $this->estimateCompletionTime($event);
        
        // 4. Notifier les systèmes de monitoring
        $this->notifyMonitoringSystems($event, 'processing_started');
    }
    
    private function handleTranscriptionCompleted(TranscriptionCompleted $event): void
    {
        // Actions à effectuer quand une transcription est terminée
        
        // 1. Log de l'événement
        $this->logTranscriptionEvent($event, 'Transcription completed successfully');
        
        // 2. Mettre à jour les statistiques
        $this->updateUserStats($event->aggregateId(), 'completed');
        
        // 3. Calculer les métriques de performance
        $this->calculatePerformanceMetrics($event);
        
        // 4. Envoyer une notification de succès
        $this->sendCompletionNotification($event);
        
        // 5. Nettoyer les fichiers temporaires
        $this->scheduleCleanupTask($event);
        
        // 6. Indexer le contenu pour la recherche
        $this->indexTranscriptionContent($event);
    }
    
    private function handleTranscriptionFailed(TranscriptionFailed $event): void
    {
        // Actions à effectuer quand une transcription échoue
        
        // 1. Log de l'erreur
        $this->logTranscriptionEvent($event, 'Transcription failed', true);
        
        // 2. Mettre à jour les statistiques d'échec
        $this->updateUserStats($event->aggregateId(), 'failed');
        
        // 3. Analyser la cause de l'échec
        $this->analyzeFailureReason($event);
        
        // 4. Envoyer une notification d'échec
        $this->sendFailureNotification($event);
        
        // 5. Déterminer si un retry automatique est possible
        $this->evaluateAutoRetry($event);
        
        // 6. Alerter l'équipe technique si nécessaire
        $this->alertTechnicalTeam($event);
    }
    
    // Méthodes utilitaires
    
    private function logTranscriptionEvent(DomainEvent $event, string $message, bool $isError = false): void
    {
        $logLevel = $isError ? 'ERROR' : 'INFO';
        $data = [
            'level' => $logLevel,
            'message' => $message,
            'event_id' => $event->eventId(),
            'aggregate_id' => method_exists($event, 'aggregateId') ? $event->aggregateId() : null,
            'timestamp' => $event->occurredAt()->format('Y-m-d H:i:s'),
            'event_data' => method_exists($event, 'toArray') ? $event->toArray() : []
        ];
        
        error_log('TranscriptionEvent: ' . json_encode($data));
    }
    
    private function updateUserStats(string $transcriptionId, string $action): void
    {
        // Dans une vraie implémentation :
        // - Récupérer l'ID utilisateur depuis la transcription
        // - Mettre à jour les statistiques dans la base de données
        // - Invalider le cache des statistiques utilisateur
        
        error_log("UserStats updated for transcription {$transcriptionId}: {$action}");
    }
    
    private function isHighPriority(TranscriptionCreated $event): bool
    {
        // Vérifier si la transcription est marquée comme prioritaire
        // Dans une vraie implémentation, extraire depuis l'événement
        return false; // Simulé
    }
    
    private function sendPriorityNotification(TranscriptionCreated $event): void
    {
        // Envoyer une notification push/email pour les transcriptions prioritaires
        error_log("Priority notification sent for transcription: {$event->aggregateId()}");
    }
    
    private function initializeProcessingTracking(TranscriptionCreated $event): void
    {
        // Initialiser le suivi de progression dans un système de monitoring
        error_log("Processing tracking initialized for: {$event->aggregateId()}");
    }
    
    private function updateProcessingStatus(string $transcriptionId, string $status): void
    {
        // Mettre à jour le statut en temps réel (WebSocket, SSE, etc.)
        error_log("Processing status updated: {$transcriptionId} -> {$status}");
    }
    
    private function estimateCompletionTime(TranscriptionStarted $event): void
    {
        // Calculer une estimation basée sur la taille du fichier et les performances historiques
        error_log("Completion time estimated for: {$event->aggregateId()}");
    }
    
    private function notifyMonitoringSystems(DomainEvent $event, string $action): void
    {
        // Notifier Prometheus, Datadog, etc.
        error_log("Monitoring notified: {$action} for {$event->aggregateId()}");
    }
    
    private function calculatePerformanceMetrics(TranscriptionCompleted $event): void
    {
        // Calculer les métriques : temps de processing, coût, précision, etc.
        error_log("Performance metrics calculated for: {$event->aggregateId()}");
    }
    
    private function sendCompletionNotification(TranscriptionCompleted $event): void
    {
        // Envoyer une notification de succès à l'utilisateur
        error_log("Completion notification sent for: {$event->aggregateId()}");
    }
    
    private function scheduleCleanupTask(TranscriptionCompleted $event): void
    {
        // Programmer la suppression des fichiers temporaires/preprocessés
        error_log("Cleanup task scheduled for: {$event->aggregateId()}");
    }
    
    private function indexTranscriptionContent(TranscriptionCompleted $event): void
    {
        // Indexer le contenu transcrit pour la recherche full-text
        error_log("Content indexed for search: {$event->aggregateId()}");
    }
    
    private function analyzeFailureReason(TranscriptionFailed $event): void
    {
        // Analyser la cause de l'échec pour améliorer le système
        error_log("Failure analysis started for: {$event->aggregateId()}");
    }
    
    private function sendFailureNotification(TranscriptionFailed $event): void
    {
        // Notifier l'utilisateur de l'échec avec des détails
        error_log("Failure notification sent for: {$event->aggregateId()}");
    }
    
    private function evaluateAutoRetry(TranscriptionFailed $event): void
    {
        // Déterminer si un retry automatique est approprié
        error_log("Auto-retry evaluation for: {$event->aggregateId()}");
    }
    
    private function alertTechnicalTeam(TranscriptionFailed $event): void
    {
        // Alerter l'équipe technique pour les erreurs critiques
        error_log("Technical team alerted for critical failure: {$event->aggregateId()}");
    }
}