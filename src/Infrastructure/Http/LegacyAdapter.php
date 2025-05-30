<?php

namespace Infrastructure\Http;

use Infrastructure\Container\ServiceLocator;

/**
 * Adaptateur pour faire fonctionner l'ancien code avec la nouvelle architecture
 * 
 * Cette classe facilite la migration progressive en fournissant
 * des méthodes compatibles avec l'ancien code tout en utilisant
 * les nouveaux services en arrière-plan.
 */
class LegacyAdapter
{
    /**
     * Point d'entrée pour créer une transcription (compatible avec l'ancien code)
     */
    public static function createTranscription(array $data): array
    {
        try {
            $controller = new Controller\TranscriptionController();
            
            // Simuler les superglobales pour le controller
            $_POST = $data;
            if (isset($data['audio_file'])) {
                $_FILES['audio'] = $data['audio_file'];
            }
            
            // Capturer la sortie JSON
            ob_start();
            $controller->create();
            $output = ob_get_clean();
            
            return json_decode($output, true) ?? ['error' => 'Invalid response'];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
    }
    
    /**
     * Récupère une transcription par ID (compatible avec l'ancien code)
     */
    public static function getTranscription(string $transcriptionId): ?array
    {
        try {
            $repository = ServiceLocator::getTranscriptionRepository();
            $transcription = $repository->findById(
                new \Domain\Transcription\ValueObject\TranscriptionId($transcriptionId)
            );
            
            if (!$transcription) {
                return null;
            }
            
            // Convertir en format compatible avec l'ancien code
            return [
                'id' => $transcription->id()->value(),
                'file_name' => basename($transcription->audioFile()->path()),
                'text' => $transcription->transcribedText()->value(),
                'language' => $transcription->language()->code(),
                'status' => $transcription->status()->value(),
                'created_at' => $transcription->createdAt()->format('Y-m-d H:i:s'),
                'youtube_title' => $transcription->youtubeMetadata()?->title(),
                'youtube_id' => $transcription->youtubeMetadata()?->videoId(),
                'is_processed' => $transcription->status()->isCompleted() ? 1 : 0
            ];
            
        } catch (\Exception $e) {
            error_log("LegacyAdapter::getTranscription error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lance le traitement d'une transcription (compatible avec l'ancien code)
     */
    public static function processTranscription(string $transcriptionId): bool
    {
        try {
            $command = new \Application\Transcription\Command\ProcessTranscriptionCommand(
                new \Domain\Transcription\ValueObject\TranscriptionId($transcriptionId)
            );
            
            $handler = ServiceLocator::get(\Application\Transcription\Handler\ProcessTranscriptionHandler::class);
            $handler->handle($command);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("LegacyAdapter::processTranscription error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère le service de transcription (compatible avec l'ancien code)
     */
    public static function getTranscriptionService()
    {
        return new class {
            public function transcribe($audioPath, $language = 'fr') {
                try {
                    $transcriber = ServiceLocator::getTranscriber();
                    $result = $transcriber->transcribe(
                        \Domain\Transcription\ValueObject\AudioFile::fromPath($audioPath),
                        new \Domain\Transcription\ValueObject\Language($language)
                    );
                    
                    return [
                        'text' => $result->text()->value(),
                        'cost' => $result->cost()
                    ];
                    
                } catch (\Exception $e) {
                    throw new \Exception("Transcription failed: " . $e->getMessage());
                }
            }
        };
    }
    
    /**
     * Récupère le service de résumé (compatible avec l'ancien code)
     */
    public static function getSummaryService()
    {
        return new class {
            public function summarize($text, $style = 'concise') {
                try {
                    $summarizer = ServiceLocator::getSummarizer();
                    $summary = $summarizer->summarize(
                        new \Domain\Transcription\ValueObject\TranscribedText($text),
                        new \Domain\Analytics\ValueObject\SummaryStyle($style)
                    );
                    
                    return $summary->value();
                    
                } catch (\Exception $e) {
                    throw new \Exception("Summary failed: " . $e->getMessage());
                }
            }
        };
    }
    
    /**
     * Méthode utilitaire pour migrer les données existantes
     */
    public static function migrateExistingData(): array
    {
        $results = ['success' => 0, 'errors' => []];
        
        try {
            // Connexion directe pour lire l'ancien schéma
            $db = ServiceLocator::getDatabase()->getConnection();
            
            // Récupérer toutes les transcriptions existantes
            $stmt = $db->query("SELECT * FROM transcriptions");
            $oldTranscriptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($oldTranscriptions as $old) {
                try {
                    // Créer l'entité avec les nouvelles Value Objects
                    $transcription = \Domain\Transcription\Entity\Transcription::create(
                        new \Domain\Transcription\ValueObject\TranscriptionId($old['id']),
                        new \Domain\Common\ValueObject\UserId($old['user_id'] ?? 'legacy_user'),
                        \Domain\Transcription\ValueObject\AudioFile::fromPath($old['file_path'] ?? ''),
                        new \Domain\Transcription\ValueObject\Language($old['language'] ?? 'fr'),
                        new \Domain\Transcription\ValueObject\TranscriptionStatus(
                            $old['is_processed'] ? 'completed' : 'pending'
                        ),
                        new \Domain\Transcription\ValueObject\TranscribedText($old['text'] ?? ''),
                        null, // YouTube metadata à ajouter si nécessaire
                        null  // Cost à calculer si nécessaire
                    );
                    
                    // Sauvegarder via le nouveau repository
                    $repository = ServiceLocator::getTranscriptionRepository();
                    $repository->save($transcription);
                    
                    $results['success']++;
                    
                } catch (\Exception $e) {
                    $results['errors'][] = "ID {$old['id']}: " . $e->getMessage();
                }
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = "Migration error: " . $e->getMessage();
        }
        
        return $results;
    }
}