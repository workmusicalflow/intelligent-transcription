<?php

namespace Infrastructure\EventSourcing;

use Domain\EventSourcing\EventStoreInterface;
use Domain\Common\Event\DomainEvent;
use Domain\Common\ValueObject\AggregateId;
use Infrastructure\Persistence\SQLiteConnection;
use PDO;
use Exception;

/**
 * Implémentation SQLite du store d'événements
 */
class SQLiteEventStore implements EventStoreInterface
{
    private PDO $connection;
    
    public function __construct(SQLiteConnection $connection)
    {
        $this->connection = $connection->getConnection();
        $this->ensureTableExists();
    }
    
    public function append(DomainEvent $event): void
    {
        $sql = "INSERT INTO domain_events (
            event_id, aggregate_id, event_type, event_version,
            event_data, metadata, occurred_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->connection->prepare($sql);
        
        $success = $stmt->execute([
            $event->eventId(),
            $event->aggregateId(),
            get_class($event),
            $event->eventVersion(),
            json_encode($event->toArray()),
            json_encode($event->metadata()),
            $event->occurredAt()->format('Y-m-d H:i:s')
        ]);
        
        if (!$success) {
            throw new Exception('Failed to append event: ' . implode(', ', $stmt->errorInfo()));
        }
    }
    
    public function appendMultiple(array $events): void
    {
        $this->connection->beginTransaction();
        
        try {
            foreach ($events as $event) {
                $this->append($event);
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
    
    public function getEventsForAggregate(AggregateId $aggregateId): array
    {
        $sql = "SELECT * FROM domain_events 
                WHERE aggregate_id = ? 
                ORDER BY event_version ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$aggregateId->value()]);
        
        return $this->hydrateEvents($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function getEventsForAggregateFromVersion(AggregateId $aggregateId, int $fromVersion): array
    {
        $sql = "SELECT * FROM domain_events 
                WHERE aggregate_id = ? AND event_version >= ?
                ORDER BY event_version ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$aggregateId->value(), $fromVersion]);
        
        return $this->hydrateEvents($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function getEventsByType(string $eventType): array
    {
        $sql = "SELECT * FROM domain_events 
                WHERE event_type = ? 
                ORDER BY occurred_at ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$eventType]);
        
        return $this->hydrateEvents($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    public function getAggregateVersion(AggregateId $aggregateId): int
    {
        $sql = "SELECT MAX(event_version) as version FROM domain_events WHERE aggregate_id = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$aggregateId->value()]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['version'] ?? 0;
    }
    
    private function hydrateEvents(array $rows): array
    {
        $events = [];
        
        foreach ($rows as $row) {
            $eventClass = $row['event_type'];
            $eventData = json_decode($row['event_data'], true);
            $metadata = json_decode($row['metadata'], true);
            
            // Ici, on devrait avoir une factory pour reconstruire les événements
            // Pour l'instant, on retourne les données brutes
            $events[] = [
                'event_id' => $row['event_id'],
                'aggregate_id' => $row['aggregate_id'],
                'event_type' => $eventClass,
                'event_version' => $row['event_version'],
                'event_data' => $eventData,
                'metadata' => $metadata,
                'occurred_at' => new \DateTimeImmutable($row['occurred_at'])
            ];
        }
        
        return $events;
    }
    
    private function ensureTableExists(): void
    {
        // Créer la table
        $sql = "CREATE TABLE IF NOT EXISTS domain_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id VARCHAR(255) NOT NULL UNIQUE,
            aggregate_id VARCHAR(255) NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            event_version INTEGER NOT NULL,
            event_data TEXT NOT NULL,
            metadata TEXT,
            occurred_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
        
        // Créer les index séparément
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_aggregate_id ON domain_events (aggregate_id)");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_event_type ON domain_events (event_type)");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_occurred_at ON domain_events (occurred_at)");
    }
}