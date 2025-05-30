<?php

namespace Application\Handler\Query;

use Application\Handler\QueryHandlerInterface;
use Application\Query\QueryInterface;
use Application\Query\Transcription\GetTranscriptionQuery;
use Application\Query\Transcription\ListTranscriptionsQuery;
use Application\Query\Transcription\GetTranscriptionStatsQuery;
use Application\DTO\Transcription\TranscriptionDTO;

use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\Specification\TranscriptionByStatusSpecification;
use Domain\Transcription\Specification\TranscriptionByLanguageSpecification;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\TranscriptionId;

/**
 * Handler pour toutes les queries relatives aux transcriptions
 */
final class TranscriptionQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository
    ) {}
    
    public function handle(QueryInterface $query): mixed
    {
        return match (get_class($query)) {
            GetTranscriptionQuery::class => $this->handleGetTranscription($query),
            ListTranscriptionsQuery::class => $this->handleListTranscriptions($query),
            GetTranscriptionStatsQuery::class => $this->handleGetStats($query),
            default => throw new \InvalidArgumentException('Unsupported query: ' . get_class($query))
        };
    }
    
    public function canHandle(QueryInterface $query): bool
    {
        return match (get_class($query)) {
            GetTranscriptionQuery::class,
            ListTranscriptionsQuery::class,
            GetTranscriptionStatsQuery::class => true,
            default => false
        };
    }
    
    private function handleGetTranscription(GetTranscriptionQuery $query): ?TranscriptionDTO
    {
        $transcriptionId = TranscriptionId::fromString($query->getTranscriptionId());
        $transcription = $this->transcriptionRepository->findById($transcriptionId);
        
        if (!$transcription) {
            return null;
        }
        
        // Vérifier les permissions si un userId est spécifié
        if ($query->getUserId() && (string) $transcription->userId() !== $query->getUserId()) {
            throw new \DomainException('Access denied to this transcription');
        }
        
        return $this->transcriptionToDTO($transcription, $query->shouldIncludeSegments());
    }
    
    private function handleListTranscriptions(ListTranscriptionsQuery $query): array
    {
        $specifications = [];
        
        // Construire les spécifications selon les filtres
        if ($query->getStatus()) {
            $status = TranscriptionStatus::fromString($query->getStatus());
            $specifications[] = new TranscriptionByStatusSpecification($status);
        }
        
        if ($query->getLanguage()) {
            $language = Language::fromCode($query->getLanguage());
            $specifications[] = new TranscriptionByLanguageSpecification($language);
        }
        
        // Pour simplifier, on utilise une spécification composite si plusieurs filtres
        $specification = null;
        if (count($specifications) === 1) {
            $specification = $specifications[0];
        } elseif (count($specifications) > 1) {
            $specification = $specifications[0];
            for ($i = 1; $i < count($specifications); $i++) {
                $specification = $specification->and($specifications[$i]);
            }
        }
        
        // Récupérer les transcriptions (implémentation temporaire pour les tests)
        if ($specification) {
            $transcriptionCollection = $this->transcriptionRepository->findBySpecification($specification);
            $transcriptions = $transcriptionCollection->items();
        } else {
            // Pour un vrai repository, on utiliserait une méthode findAllPaginated
            // Ici on simule avec les méthodes disponibles
            $transcriptions = [];
        }
        
        // Convertir en DTOs
        $results = [];
        foreach ($transcriptions as $transcription) {
            // Filtrer par userId si spécifié
            if ($query->getUserId() && (string) $transcription->userId() !== $query->getUserId()) {
                continue;
            }
            
            // Filtrer par date si spécifié
            if ($query->hasDateFilter()) {
                $createdAt = $transcription->createdAt();
                if ($query->getFromDate() && $createdAt < $query->getFromDate()) {
                    continue;
                }
                if ($query->getToDate() && $createdAt > $query->getToDate()) {
                    continue;
                }
            }
            
            // Filtrer par type YouTube si spécifié
            if ($query->getIsYouTube() !== null) {
                $isYoutube = $transcription->isYouTubeSource();
                if ($query->getIsYouTube() !== $isYoutube) {
                    continue;
                }
            }
            
            $results[] = $this->transcriptionToDTO($transcription, false); // Pas de segments pour les listes
        }
        
        return [
            'data' => $results,
            'pagination' => [
                'page' => $query->getPage(),
                'limit' => $query->getLimit(),
                'total' => count($results), // Dans une vraie implémentation, il faudrait un count séparé
                'has_more' => count($results) === $query->getLimit()
            ]
        ];
    }
    
    private function handleGetStats(GetTranscriptionStatsQuery $query): array
    {
        // Pour cette implémentation simplifiée, on récupère toutes les transcriptions
        // et on calcule les stats en mémoire. Dans une vraie implémentation,
        // cela se ferait au niveau de la base de données.
        
        $allTranscriptions = $this->transcriptionRepository->findAll();
        
        $stats = [
            'total' => 0,
            'by_status' => [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0
            ],
            'by_language' => [],
            'total_duration' => 0.0,
            'total_file_size' => 0,
            'youtube_count' => 0,
            'file_upload_count' => 0
        ];
        
        foreach ($allTranscriptions as $transcription) {
            // Filtrer par userId si spécifié
            if ($query->getUserId() && (string) $transcription->userId() !== $query->getUserId()) {
                continue;
            }
            
            // Filtrer par date si spécifié
            if ($query->hasDateFilter()) {
                $createdAt = $transcription->createdAt();
                if ($query->getFromDate() && $createdAt < $query->getFromDate()) {
                    continue;
                }
                if ($query->getToDate() && $createdAt > $query->getToDate()) {
                    continue;
                }
            }
            
            $stats['total']++;
            
            // Stats par statut
            $status = (string) $transcription->status();
            $stats['by_status'][$status]++;
            
            // Stats par langue
            $language = $transcription->language()->code();
            $stats['by_language'][$language] = ($stats['by_language'][$language] ?? 0) + 1;
            
            // Stats de durée et taille
            $audioFile = $transcription->audioFile();
            if ($audioFile->duration()) {
                $stats['total_duration'] += $audioFile->duration();
            }
            $stats['total_file_size'] += $audioFile->size();
            
            // Stats par type
            if ($transcription->isYouTubeSource()) {
                $stats['youtube_count']++;
            } else {
                $stats['file_upload_count']++;
            }
        }
        
        // Calculer les moyennes
        if ($stats['total'] > 0) {
            $stats['average_duration'] = $stats['total_duration'] / $stats['total'];
            $stats['average_file_size'] = $stats['total_file_size'] / $stats['total'];
        } else {
            $stats['average_duration'] = 0;
            $stats['average_file_size'] = 0;
        }
        
        // Ajouter les détails si demandés
        if ($query->shouldIncludeDetailed()) {
            $stats['completion_rate'] = $stats['total'] > 0 
                ? ($stats['by_status']['completed'] / $stats['total']) * 100 
                : 0;
            $stats['failure_rate'] = $stats['total'] > 0 
                ? ($stats['by_status']['failed'] / $stats['total']) * 100 
                : 0;
        }
        
        return $stats;
    }
    
    private function transcriptionToDTO(Transcription $transcription, bool $includeSegments = true): TranscriptionDTO
    {
        $audioFile = $transcription->audioFile();
        $language = $transcription->language();
        $text = $transcription->text();
        $youtubeMetadata = $transcription->youtubeMetadata();
        
        return TranscriptionDTO::fromArray([
            'id' => $transcription->id(),
            'user_id' => (string) $transcription->userId(),
            'original_filename' => $audioFile->originalName(),
            'language' => $language->code(),
            'status' => (string) $transcription->status(),
            'text' => $text?->content(),
            'youtube_url' => $youtubeMetadata?->originalUrl(),
            'youtube_title' => $youtubeMetadata?->title(),
            'segments' => $includeSegments ? $text?->segments() : null,
            'duration' => $audioFile->duration(),
            'file_size' => $audioFile->size(),
            'failure_reason' => $transcription->failureReason(),
            'created_at' => $transcription->createdAt()->format('Y-m-d H:i:s'),
            'completed_at' => $transcription->completedAt()?->format('Y-m-d H:i:s')
        ]);
    }
}