<?php

namespace Infrastructure\Repository\SQLite;

use Domain\Chat\Repository\ConversationRepository;
use Domain\Chat\Entity\Conversation;
use Domain\Chat\ValueObject\ConversationId;
use Domain\Chat\Collection\ConversationCollection;
use Domain\Common\ValueObject\UserId;
use Domain\Common\Specification\SpecificationInterface;
use Infrastructure\Persistence\SQLiteConnection;
use PDO;
use Exception;

/**
 * Implémentation SQLite du Repository Conversation
 * Note: Les entités Chat Domain n'existent pas encore, on prépare la structure
 */
class SQLiteChatRepository implements ConversationRepository
{
    private PDO $connection;
    
    public function __construct(SQLiteConnection $connection)
    {
        $this->connection = $connection->getConnection();
    }
    
    public function save(Conversation $conversation): void
    {
        if ($this->exists($conversation->id())) {
            $this->updateConversation($conversation);
        } else {
            $this->insertConversation($conversation);
        }
    }
    
    public function findById(ConversationId $id): ?Conversation
    {
        $sql = "SELECT * FROM chat_conversations WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id->value()]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrateConversation($data);
    }
    
    public function findByUserId(UserId $userId): ConversationCollection
    {
        $sql = "
            SELECT * FROM chat_conversations 
            WHERE user_id = ? 
            ORDER BY updated_at DESC
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value()]);
        
        $conversations = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $conversations[] = $this->hydrateConversation($data);
        }
        
        return new ConversationCollection($conversations);
    }
    
    public function findByTranscriptionId(string $transcriptionId): ConversationCollection
    {
        $sql = "
            SELECT * FROM chat_conversations 
            WHERE transcription_id = ? 
            ORDER BY updated_at DESC
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$transcriptionId]);
        
        $conversations = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $conversations[] = $this->hydrateConversation($data);
        }
        
        return new ConversationCollection($conversations);
    }
    
    public function findRecent(UserId $userId, int $limit = 10): ConversationCollection
    {
        $sql = "
            SELECT * FROM chat_conversations 
            WHERE user_id = ? 
            ORDER BY updated_at DESC 
            LIMIT ?
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value(), $limit]);
        
        $conversations = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $conversations[] = $this->hydrateConversation($data);
        }
        
        return new ConversationCollection($conversations);
    }
    
    public function remove(ConversationId $id): void
    {
        // Supprimer d'abord les messages de la conversation
        $this->removeConversationMessages($id);
        
        // Puis supprimer la conversation
        $sql = "DELETE FROM chat_conversations WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt->execute([$id->value()])) {
            throw new Exception("Failed to delete conversation: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    public function exists(ConversationId $id): bool
    {
        $sql = "SELECT COUNT(*) FROM chat_conversations WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id->value()]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM chat_conversations";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }
    
    public function matching(SpecificationInterface $specification): ConversationCollection
    {
        // Implémentation basique - en production, convertir la Specification en SQL
        $sql = "SELECT * FROM chat_conversations ORDER BY updated_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $conversations = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $conversation = $this->hydrateConversation($data);
            if ($specification->isSatisfiedBy($conversation)) {
                $conversations[] = $conversation;
            }
        }
        
        return new ConversationCollection($conversations);
    }
    
    private function insertConversation(Conversation $conversation): void
    {
        $sql = "
            INSERT INTO chat_conversations (
                id, user_id, title, transcription_id, message_count,
                total_tokens, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->connection->prepare($sql);
        $this->bindConversationData($stmt, $conversation);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert conversation: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    private function updateConversation(Conversation $conversation): void
    {
        $sql = "
            UPDATE chat_conversations SET
                title = ?, transcription_id = ?, message_count = ?,
                total_tokens = ?, updated_at = ?
            WHERE id = ?
        ";
        
        $stmt = $this->connection->prepare($sql);
        $params = [
            $conversation->title(),
            $conversation->transcriptionId(),
            $conversation->messageCount(),
            $conversation->totalTokens(),
            date('Y-m-d H:i:s'),
            $conversation->id()->value()
        ];
        
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update conversation: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    private function bindConversationData(\PDOStatement $stmt, Conversation $conversation): void
    {
        $params = [
            $conversation->id()->value(),
            $conversation->userId()->value(),
            $conversation->title(),
            $conversation->transcriptionId(),
            $conversation->messageCount(),
            $conversation->totalTokens(),
            date('Y-m-d H:i:s'), // created_at
            date('Y-m-d H:i:s')  // updated_at
        ];
        
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
    }
    
    private function hydrateConversation(array $data): Conversation
    {
        // Pour le moment, retourner un objet factice
        // En réalité, il faudrait créer les vraies entités Domain\Chat
        return (object) [
            'id' => ConversationId::fromString($data['id']),
            'userId' => UserId::fromString($data['user_id']),
            'title' => $data['title'],
            'transcriptionId' => $data['transcription_id'],
            'messageCount' => (int) $data['message_count'],
            'totalTokens' => (int) $data['total_tokens'],
            'createdAt' => $data['created_at'],
            'updatedAt' => $data['updated_at']
        ];
    }
    
    private function removeConversationMessages(ConversationId $conversationId): void
    {
        $sql = "DELETE FROM chat_messages WHERE conversation_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$conversationId->value()]);
    }
    
    public function getStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_conversations,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(message_count) as total_messages,
                SUM(total_tokens) as total_tokens,
                AVG(message_count) as avg_messages_per_conversation
            FROM chat_conversations
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}