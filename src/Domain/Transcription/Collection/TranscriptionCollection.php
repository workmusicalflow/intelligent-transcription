<?php

namespace Domain\Transcription\Collection;

use Domain\Common\Collection\Collection;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\Language;

final class TranscriptionCollection extends Collection
{
    protected function type(): string
    {
        return Transcription::class;
    }
    
    /**
     * Filtre les transcriptions par statut
     */
    public function filterByStatus(TranscriptionStatus $status): self
    {
        return $this->filter(function (Transcription $transcription) use ($status) {
            return $transcription->status()->equals($status);
        });
    }
    
    /**
     * Filtre les transcriptions complétées
     */
    public function onlyCompleted(): self
    {
        return $this->filter(function (Transcription $transcription) {
            return $transcription->isCompleted();
        });
    }
    
    /**
     * Filtre les transcriptions échouées
     */
    public function onlyFailed(): self
    {
        return $this->filter(function (Transcription $transcription) {
            return $transcription->isFailed();
        });
    }
    
    /**
     * Filtre les transcriptions en cours
     */
    public function onlyProcessing(): self
    {
        return $this->filter(function (Transcription $transcription) {
            return $transcription->isProcessing();
        });
    }
    
    /**
     * Filtre les transcriptions YouTube
     */
    public function onlyYouTube(): self
    {
        return $this->filter(function (Transcription $transcription) {
            return $transcription->isYouTubeSource();
        });
    }
    
    /**
     * Filtre les transcriptions par langue
     */
    public function filterByLanguage(Language $language): self
    {
        return $this->filter(function (Transcription $transcription) use ($language) {
            return $transcription->language()->equals($language);
        });
    }
    
    /**
     * Trie par date de création (plus récent en premier)
     */
    public function sortByCreatedAtDesc(): self
    {
        $items = $this->items;
        usort($items, function (Transcription $a, Transcription $b) {
            return $b->createdAt() <=> $a->createdAt();
        });
        return new self($items);
    }
    
    /**
     * Trie par date de création (plus ancien en premier)
     */
    public function sortByCreatedAtAsc(): self
    {
        $items = $this->items;
        usort($items, function (Transcription $a, Transcription $b) {
            return $a->createdAt() <=> $b->createdAt();
        });
        return new self($items);
    }
    
    /**
     * Obtient le nombre total de mots transcrits
     */
    public function getTotalWordCount(): int
    {
        $total = 0;
        foreach ($this->items as $transcription) {
            if ($transcription->text()) {
                $total += $transcription->text()->wordCount();
            }
        }
        return $total;
    }
    
    /**
     * Obtient la durée totale transcrite en secondes
     */
    public function getTotalDuration(): float
    {
        $total = 0.0;
        foreach ($this->items as $transcription) {
            if ($transcription->text() && $transcription->text()->duration()) {
                $total += $transcription->text()->duration();
            }
        }
        return $total;
    }
    
    /**
     * Obtient les statistiques de la collection
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => $this->count(),
            'completed' => 0,
            'failed' => 0,
            'processing' => 0,
            'pending' => 0,
            'youtube_sources' => 0,
            'total_words' => 0,
            'total_duration' => 0.0,
            'languages' => []
        ];
        
        foreach ($this->items as $transcription) {
            // Statuts
            if ($transcription->isCompleted()) {
                $stats['completed']++;
            } elseif ($transcription->isFailed()) {
                $stats['failed']++;
            } elseif ($transcription->isProcessing()) {
                $stats['processing']++;
            } elseif ($transcription->isPending()) {
                $stats['pending']++;
            }
            
            // Sources
            if ($transcription->isYouTubeSource()) {
                $stats['youtube_sources']++;
            }
            
            // Métriques texte
            if ($transcription->text()) {
                $stats['total_words'] += $transcription->text()->wordCount();
                if ($transcription->text()->duration()) {
                    $stats['total_duration'] += $transcription->text()->duration();
                }
            }
            
            // Langues
            $langCode = $transcription->language()->code();
            if (!isset($stats['languages'][$langCode])) {
                $stats['languages'][$langCode] = 0;
            }
            $stats['languages'][$langCode]++;
        }
        
        return $stats;
    }
    
    /**
     * Paginer la collection
     */
    public function paginate(int $page, int $perPage): self
    {
        $offset = ($page - 1) * $perPage;
        $items = array_slice($this->items, $offset, $perPage);
        return new self($items);
    }
}