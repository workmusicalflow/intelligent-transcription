<?php

namespace Application\Event\Handler;

use Domain\Common\Event\DomainEvent;
use Domain\Transcription\Event\TranscriptionCreated;
use Domain\Transcription\Event\TranscriptionCompleted;
use Domain\Transcription\Event\TranscriptionFailed;

/**
 * Handler spécialisé pour l'envoi de notifications
 */
final class NotificationEventHandler extends AbstractEventHandler
{
    protected int $priority = 20; // Moins prioritaire que TranscriptionEventHandler
    protected bool $async = true;  // Peut être exécuté de manière asynchrone
    
    public function handle(DomainEvent $event): void
    {
        match (get_class($event)) {
            TranscriptionCreated::class => $this->sendCreationNotification($event),
            TranscriptionCompleted::class => $this->sendCompletionNotification($event),
            TranscriptionFailed::class => $this->sendFailureNotification($event),
            default => throw new \InvalidArgumentException('Unsupported event: ' . get_class($event))
        };
    }
    
    public function getHandledEventTypes(): array
    {
        return [
            TranscriptionCreated::class,
            TranscriptionCompleted::class,
            TranscriptionFailed::class
        ];
    }
    
    private function sendCreationNotification(TranscriptionCreated $event): void
    {
        // Récupérer les informations utilisateur
        $userId = $this->extractUserId($event);
        $userPrefs = $this->getUserNotificationPreferences($userId);
        
        if (!$userPrefs['transcription_created']) {
            return; // L'utilisateur ne veut pas de notifications de création
        }
        
        // Préparer le message
        $message = $this->buildCreationMessage($event);
        
        // Envoyer selon les préférences
        if ($userPrefs['email_enabled']) {
            $this->sendEmailNotification($userId, 'Transcription créée', $message);
        }
        
        if ($userPrefs['push_enabled']) {
            $this->sendPushNotification($userId, 'Transcription en cours', $message);
        }
        
        if ($userPrefs['in_app_enabled']) {
            $this->createInAppNotification($userId, $message, 'transcription_created');
        }
    }
    
    private function sendCompletionNotification(TranscriptionCompleted $event): void
    {
        $userId = $this->extractUserId($event);
        $userPrefs = $this->getUserNotificationPreferences($userId);
        
        if (!$userPrefs['transcription_completed']) {
            return;
        }
        
        // Récupérer les détails de la transcription complétée
        $details = $this->getTranscriptionDetails($event);
        $message = $this->buildCompletionMessage($event, $details);
        
        // Actions spéciales pour les transcriptions complétées
        $actions = [
            [
                'text' => 'Voir la transcription',
                'url' => "/transcriptions/{$event->aggregateId()}"
            ],
            [
                'text' => 'Commencer le chat',
                'url' => "/chat?transcription={$event->aggregateId()}"
            ]
        ];
        
        if ($userPrefs['email_enabled']) {
            $this->sendEmailNotification(
                $userId, 
                'Transcription terminée ✅', 
                $message, 
                $actions
            );
        }
        
        if ($userPrefs['push_enabled']) {
            $this->sendPushNotification(
                $userId, 
                'Votre transcription est prête !', 
                $message,
                $actions
            );
        }
        
        if ($userPrefs['in_app_enabled']) {
            $this->createInAppNotification(
                $userId, 
                $message, 
                'transcription_completed',
                $actions
            );
        }
        
        // Notification spéciale pour les transcriptions longues (>30 min)
        if ($details['duration'] > 1800) {
            $this->sendSpecialLongTranscriptionNotification($userId, $details);
        }
    }
    
    private function sendFailureNotification(TranscriptionFailed $event): void
    {
        $userId = $this->extractUserId($event);
        $userPrefs = $this->getUserNotificationPreferences($userId);
        
        // Toujours notifier les échecs, même si les notifications sont désactivées
        
        $failureReason = $this->getFailureReason($event);
        $message = $this->buildFailureMessage($event, $failureReason);
        
        $actions = [
            [
                'text' => 'Réessayer',
                'url' => "/transcriptions/{$event->aggregateId()}/retry"
            ],
            [
                'text' => 'Support',
                'url' => "/support?ref=transcription_failed&id={$event->aggregateId()}"
            ]
        ];
        
        // Email avec priorité haute pour les échecs
        $this->sendEmailNotification(
            $userId, 
            '❌ Échec de transcription', 
            $message, 
            $actions,
            'high'
        );
        
        // Notification push critique
        $this->sendPushNotification(
            $userId, 
            'Problème avec votre transcription', 
            'Nous n\'avons pas pu traiter votre fichier. Appuyez pour plus d\'infos.',
            $actions,
            'critical'
        );
        
        // Notification in-app persistante
        $this->createInAppNotification(
            $userId, 
            $message, 
            'transcription_failed',
            $actions,
            true // persistant
        );
        
        // Log pour l'équipe technique
        $this->notifyTechnicalTeam($event, $failureReason);
    }
    
    // Méthodes utilitaires
    
    private function extractUserId(DomainEvent $event): string
    {
        // Dans une vraie implémentation, extraire depuis l'événement
        // Pour l'instant, on simule
        return 'user_123';
    }
    
    private function getUserNotificationPreferences(string $userId): array
    {
        // Récupérer les préférences depuis la base de données
        return [
            'email_enabled' => true,
            'push_enabled' => true,
            'in_app_enabled' => true,
            'transcription_created' => false, // Généralement désactivé par défaut
            'transcription_completed' => true,
            'transcription_failed' => true
        ];
    }
    
    private function buildCreationMessage(TranscriptionCreated $event): string
    {
        return "Votre transcription a été créée et sera traitée sous peu. " .
               "Vous recevrez une notification dès qu'elle sera terminée.";
    }
    
    private function buildCompletionMessage(TranscriptionCompleted $event, array $details): string
    {
        $duration = $this->formatDuration($details['duration']);
        $wordCount = number_format($details['word_count']);
        
        return "Votre transcription de {$duration} est maintenant disponible ! " .
               "Nous avons transcrit {$wordCount} mots. " .
               "Vous pouvez maintenant la consulter ou commencer une conversation avec l'IA.";
    }
    
    private function buildFailureMessage(TranscriptionFailed $event, string $reason): string
    {
        return "Nous n'avons pas pu traiter votre transcription. " .
               "Raison : {$reason}. " .
               "Vous pouvez réessayer ou contacter le support si le problème persiste.";
    }
    
    private function getTranscriptionDetails(TranscriptionCompleted $event): array
    {
        // Dans une vraie implémentation, récupérer depuis la base de données
        return [
            'filename' => 'example.mp3',
            'duration' => 300, // 5 minutes
            'word_count' => 750,
            'language' => 'fr'
        ];
    }
    
    private function getFailureReason(TranscriptionFailed $event): string
    {
        // Extraire la raison depuis l'événement
        return 'Format de fichier non supporté';
    }
    
    private function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        if ($minutes > 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return "{$hours}h{$minutes}m";
        }
        
        return "{$minutes}m{$seconds}s";
    }
    
    private function sendEmailNotification(
        string $userId, 
        string $subject, 
        string $message, 
        array $actions = [],
        string $priority = 'normal'
    ): void {
        // Dans une vraie implémentation, utiliser un service email (SendGrid, Mailgun, etc.)
        $emailData = [
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
            'actions' => $actions,
            'priority' => $priority,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        error_log('EmailNotification: ' . json_encode($emailData));
    }
    
    private function sendPushNotification(
        string $userId, 
        string $title, 
        string $message, 
        array $actions = [],
        string $priority = 'normal'
    ): void {
        // Dans une vraie implémentation, utiliser FCM, APNS, etc.
        $pushData = [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'actions' => $actions,
            'priority' => $priority,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        error_log('PushNotification: ' . json_encode($pushData));
    }
    
    private function createInAppNotification(
        string $userId, 
        string $message, 
        string $type,
        array $actions = [],
        bool $persistent = false
    ): void {
        // Créer une notification dans l'interface utilisateur
        $notificationData = [
            'user_id' => $userId,
            'message' => $message,
            'type' => $type,
            'actions' => $actions,
            'persistent' => $persistent,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        error_log('InAppNotification: ' . json_encode($notificationData));
    }
    
    private function sendSpecialLongTranscriptionNotification(string $userId, array $details): void
    {
        $duration = $this->formatDuration($details['duration']);
        $message = "Félicitations ! Vous avez transcrit un fichier de {$duration}. " .
                  "Pour des fichiers aussi longs, n'hésitez pas à utiliser notre chat IA " .
                  "pour analyser et résumer le contenu.";
        
        $this->sendEmailNotification(
            $userId, 
            '🎉 Grande transcription terminée !', 
            $message,
            [['text' => 'Analyser avec l\'IA', 'url' => "/chat?transcription={$details['id']}"]]
        );
    }
    
    private function notifyTechnicalTeam(TranscriptionFailed $event, string $reason): void
    {
        // Notifier l'équipe technique des échecs pour monitoring
        $alertData = [
            'event_id' => $event->eventId(),
            'aggregate_id' => $event->aggregateId(),
            'failure_reason' => $reason,
            'occurred_at' => $event->occurredAt()->format('Y-m-d H:i:s'),
            'severity' => 'medium'
        ];
        
        error_log('TechnicalAlert: ' . json_encode($alertData));
    }
}