<?php

namespace Domain\Chat\Collection;

use Domain\Common\Collection\Collection;
use Domain\Chat\Entity\Conversation;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\TranscriptionId;

final class ConversationCollection extends Collection
{
    protected function type(): string
    {
        return Conversation::class;
    }
    
    /**
     * Filtre les conversations par utilisateur
     */
    public function filterByUser(UserId $userId): self
    {
        return $this->filter(function (Conversation $conversation) use ($userId) {
            return $conversation->userId()->equals($userId);
        });
    }
    
    /**
     * Filtre les conversations par transcription
     */
    public function filterByTranscription(TranscriptionId $transcriptionId): self
    {
        return $this->filter(function (Conversation $conversation) use ($transcriptionId) {
            return $conversation->transcriptionId() && 
                   $conversation->transcriptionId()->equals($transcriptionId);
        });
    }
    
    /**
     * Trie par date de mise à jour (plus récent en premier)
     */
    public function sortByUpdatedDesc(): self
    {
        $items = $this->items;
        usort($items, function (Conversation $a, Conversation $b) {
            return $b->updatedAt() <=> $a->updatedAt();
        });
        return new self($items);
    }
    
    /**
     * Obtient les conversations avec au moins N messages
     */
    public function withMinimumMessages(int $minMessages): self
    {
        return $this->filter(function (Conversation $conversation) use ($minMessages) {
            return $conversation->messageCount() >= $minMessages;
        });
    }
    
    /**
     * Obtient les statistiques de la collection
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => $this->count(),
            'total_messages' => 0,
            'avg_messages_per_conversation' => 0,
            'with_transcription' => 0,
            'without_transcription' => 0
        ];
        
        foreach ($this->items as $conversation) {
            $stats['total_messages'] += $conversation->messageCount();
            
            if ($conversation->transcriptionId()) {
                $stats['with_transcription']++;
            } else {
                $stats['without_transcription']++;
            }
        }
        
        if ($stats['total'] > 0) {
            $stats['avg_messages_per_conversation'] = $stats['total_messages'] / $stats['total'];
        }
        
        return $stats;
    }
}